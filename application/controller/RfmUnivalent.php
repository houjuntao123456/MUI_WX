<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;
use app\controller\RfmActive as rfma;

/**
 * Author lxy
 * Date 2019/02/19
 * Description A高件单价分析
 */
class RfmUnivalent extends Common
{
    /**
     * 按门店查询A高件单价分析
     */
    public function univalentSel()
    {
        // 获取数据
        [$page, $limit, $search, $staff_code, $db] = [input('page'), input('limit'), input('search'), input('staff_code'), $this->db];
        // 门店查询
        if ($search != '') {
            // 统计数量
            $count = Db::table($db . '.vip_rfm_a')
                ->where('store_code', $search)
                ->count();
            // 查询的数据
            $data = Db::table($db . '.vip_rfm_a')
                ->where('store_code', $search)
                ->order('a_create_time', 'desc') // 按照时间降序排列
                ->page($page, $limit)
                ->select();
            // 周期
            $a_consumption = Db::table($db . '.vip_rfm_days')
                ->field('a_consumption')
                ->where('store_code', $search)
                ->find();
            // 查询人数需要的条件
            if ($staff_code != "") {
                $where[] = ['v.consultant_code', '=', $staff_code];
            } else {
                $where[] = ['v.store_code', '=', $search];
            }
            $vipa = Db::table($this->db . '.vip_viplist')
                ->alias('v')
                ->leftJoin($this->db . '.vip_goods_order o', 'o.vip_code = v.code')
                // 会员code，时间， 实际支付总金额， 总件数
                ->field('v.*, ifnull(MAX(o.create_time), 0) as order_time, ifnull(round(sum(o.real_pay), 2), 0) as total_consumption, 
                        ifnull(round(sum(o.number)), 0) as consumption_number')
                ->where('(o.status = 0 or o.status IS null)')
                ->where($where)
                ->group('v.code')
                ->select();
            $t = time();
            $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
            foreach ($vipa as $k => $v) {
                if ($v['order_time'] >= $start) {
                    $vipa[$k]['rfm_days'] = 0;
                } else {
                    $vipa[$k]['rfm_days'] = intval(round((time() - $v['order_time']) / 86400));
                }
            }
            foreach ($vipa as $k => $v) {
                if (!(($v['rfm_days'] >= 0) && ($v['rfm_days'] <= $a_consumption['a_consumption']))) {
                    if ($v['order_time'] != 0) {
                        unset($vipa[$k]);
                    }
                }
            }
            sort($vipa);
            // 格式化数据
            foreach ($data as $k => $v) {
                // 周期赋值
                $data[$k]['a_consumption'] =  $a_consumption['a_consumption'];
                $data[$k]['numbertime'] = 0;
            }
            // 人数
            foreach ($data as $k => $v) {
                foreach ($vipa as $val) {
                    // 件单价 = 会员实际支付总金额/总件数
                    if ($val['total_consumption'] == 0 || $val['consumption_number'] == 0) {
                        $pm = 0;
                    } else {
                        $pm = $val['total_consumption'] / $val['consumption_number'];
                    }
                    if ($v['a_intervalone'] <= $pm && $v['a_intervaltwo'] > $pm) {
                        $data[$k]['numbertime'] += 1;
                    }
                }
            }
            //清除变量
            unset($page, $limit, $search, $db, $vipa, $a_consumption);
        } else {
            $count = 0;
            $data = [];
        }
        //格式化数据
        $data = [
            'count' => $count,
            'data' => $data
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }
    public function univalentSelx()
    {
        //获取数据
        [$page, $limit, $search, $barCode, $staff_code, $db] = [input('page'), input('limit'), input('search'), input('bar_code'), input('staff_code'), $this->db];
        //模糊查询
        if ($search != '') {
            //统计数量
            $count = Db::table($db . '.vip_rfm_a')
                ->where('store_code', $search)
                // ->where('store_code', 'MDERP1548127744152')
                ->count();
            //查询的数据
            $data = Db::table($db . '.vip_rfm_a')
                ->where('store_code', $search)
                // ->where('store_code', 'MDERP1548127744152')
                ->order('a_create_time', 'desc') //按照登记时间降序排列
                ->page($page, $limit)
                ->select();
            //周期
            $a_consumption = Db::table($db . '.vip_rfm_days')->field('a_consumption')
                ->where('store_code', $search)
                // ->where('store_code', 'MDERP1548127744152')
                ->find();
            // 查询人数需要条件
            if ($barCode != "") {
                $es = new ErpWhere($db, "");
                $vipCode = $es->goodsVip($barCode);
                //判断
                if (empty($vipCode)) {
                    $data = [
                        'count' => 0,
                        'data' => []
                    ];
                    //返回数据
                    webApi(200, 'ok', $data);
                } else {
                    $vips = Db::table($db . '.view_vipinfo')
                        // ->where('store_code', $search)
                        ->where('code', 'in', $vipCode)
                        ->where('rfm_days', '<=', $a_consumption['a_consumption'])
                        ->where('rfm_days', '>=', 0)
                        ->field('code')
                        ->select();
                }
            } else {
                //获取所需条件
                if ($staff_code != "") {
                    $vips = Db::table($db . '.view_vipinfo')
                        ->where('consultant_code', $staff_code)
                        // ->where('store_code', 'MDERP1548127744152')
                        ->where('rfm_days', '<=', $a_consumption['a_consumption'])
                        ->where('rfm_days', '>=', 0)
                        ->field('code')
                        ->select();
                } else {
                    $vips = Db::table($db . '.view_vipinfo')
                        ->where('store_code', $search)
                        // ->where('store_code', 'MDERP1548127744152')
                        ->where('rfm_days', '<=', $a_consumption['a_consumption'])
                        ->where('rfm_days', '>=', 0)
                        ->field('code')
                        ->select();
                }
            }
            //判断并格式化数据
            if ($vips) {
                $cards = array_column($vips, 'code');
            } else {
                $cards = [];
            }
            //查询人数需要条件(金额)
            $fc_money = Db::table($db . '.vip_goods_order')
                ->field('sum(real_pay) as pay')
                ->where('status', 0)
                ->where('vip_code', 'in', $cards)
                ->group('vip_code')
                ->select();
            //查询人数需要条件(件数)
            $fc_number = Db::table($db . '.vip_goods_order')
                ->field('sum(number) as num')
                ->where('status', 0)
                ->where('vip_code', 'in', $cards)
                ->group('vip_code')
                ->select();
            //判断并格式化数据
            if ($fc_money && $fc_number) {
                $fct = array_column($fc_money, 'pay');
                $fctt = array_column($fc_number, 'num');
                $n = count($fc_money);
            } else {
                $fct = [];
                $fctt = [];
                $n = 0;
            }
            //格式化数据
            foreach ($data as $k => $v) {
                //时间格式的转换
                if ($data[$k]['a_update_time'] == "") {
                    $data[$k]['a_update_time_g'] = date('Y-m-d H:i:s', $v['a_create_time']);
                } else {
                    $data[$k]['a_update_time_g'] = date('Y-m-d H:i:s', $v['a_update_time']);
                }
                //金额格式
                $data[$k]['a_intervalone_g'] = number_format($v['a_intervalone'], 2);
                $data[$k]['a_intervaltwo_g'] = number_format($v['a_intervaltwo'], 2);
                //拼成在表中的显示
                $data[$k]['Index_interval'] = $data[$k]['a_intervalone_g'] . ' ≤ A < ' . $data[$k]['a_intervaltwo_g'];
                //周期赋值
                $data[$k]['a_consumption'] =  $a_consumption['a_consumption'];
                //会员人数
                if ($fct && $fctt) {
                    $c = 0;
                    for ($i = 0; $i < $n; $i++) {
                        //客单价 = 金额/件数
                        if ($fct[$i] == 0 || $fctt[$i] == 0) {
                            $am = 0;
                        } else {
                            $am = $fct[$i] / $fctt[$i];
                        }
                        if ($am >= $v['a_intervalone'] && $am < $v['a_intervaltwo']) {
                            $c++;
                        }
                    }
                    $data[$k]['numbertime'] = $c;
                } else {
                    $data[$k]['numbertime'] = 0;
                }
            }
            //清除变量
            unset($page, $limit, $search, $db, $vips, $a_consumption, $cards, $c, $fctt, $fct, $n, $fc_number, $fc_money);
        } else {
            $count = 0;
            $data = [];
        }
        //格式化数据
        $data = [
            'count' => $count,
            'data' => $data
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 按条件查询会员人数
     */
    public function lookPeople()
    {
        //获取数据
        [$page, $limit, $id, $store, $staff_code, $db] = [input('page'), input('limit'), input('id'), input('store_code'), input('staff_code'), $this->db];
        if ($id == null) {
            webApi(400, '参数错误！');
        }
        // 查询的会员数据
        $vipData = rfma::rfmMember($id, $store, $staff_code, $db, 'a');
        // 基本配置
        $sysCon = Db::table($this->db . '.vip_sys_con')->find();
        if ($vipData) {
            $count = count($vipData);
            foreach ($vipData as $k => $v) {
                if ($v['rfm_days'] > 15000) {
                    $vipData[$k]['rfm_days'] = '';
                }
                if ($sysCon['yincang_is_phone'] == "on") {
                    if ($v['phone'] != "") {
                        $vipData[$k]['phone_g'] = substr($v['phone'], 0, 3) . '****' . substr($v['phone'], 7);
                    }
                } else {
                    $vipData[$k]['phone_g'] = $v['phone'];
                }
                if (strlen($v['birthday']) == 8) {
                    $vipData[$k]['birthday_g'] = date('Y-m-d', strtotime($v['birthday']));
                } else if ($v['birthday'] == 0) {
                    $vipData[$k]['birthday_g'] = '无';
                } else {
                    $vipData[$k]['birthday_g'] = date('Y-m-d', $v['birthday']);
                }
            }
            // 数据分页
            $es = new ErpWhere($db, "");
            $da = $es->arrPage($vipData, $page, $limit);
        } else {
            $count = 0;
            $da = [];
        }
        //清除变量
        unset($id, $page, $limit, $store, $db);
        //格式化数据
        $data = [
            'count' => $count,
            'data' => $da
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 按条件查询人数发送短信和微信
     */
    public function messageMember()
    {
        // 获取数据
        [$id, $store, $staff_code, $message_content, $db, $we] = [input('id'), input('store_code'), input('staff_code'), input('message_content'), $this->db, input('wxchat')];
        if ($id == null) {
            webApi(400, '参数错误！');
        }
        // 查询的会员数据
        $vipData = rfma::rfmMember($id, $store, $staff_code, $db, 'a', input('vvip'));
        //提取会员的手机号并更改格式
        $phone = array_column($vipData, 'phone');
        // 格式会员code
        $vip_code = array_column($vipData, 'code');
        //调用短信接口发送短信
        $count = array_chunk($phone, 100);
        $es = new ErpWhere($db, "");
        if ($we == 1) {
            $es->wxG($vip_code, $message_content, input('type'));
            webApi(200, '发送成功!');
        } else if ($we == 2) {
            $message_count = count($vipData) * ceil($es->abslength($message_content) / 64);
            $msg = Db::table('company.vip_business')->field('code, usable_msg')->where('code', $db)->find();
            $sms = Db::table('company.vip_sms_autograph')->field('sms_autograph, business_code')->where('business_code', $db)->find();
            if (empty($sms)) {
                webApi(400, '短信签名未配置，请联系管理员进行配置');
            }
            //判断短信条数
            if ($msg['usable_msg'] < $message_count) {
                webApi(400, '短信条数不足，可用短信为' . $msg['usable_msg'] . '条' . '，当前发送短信为' . $message_count . '条');
            }
            // 启动事务
            Db::startTrans();
            try {
                for ($i = 0; $i < count($count); $i++) {
                    $es->shortMessage($count[$i], '【' . $sms['sms_autograph'] . '】' . $message_content);
                }
                // 提交事务
                Db::commit();
                $res = true;
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $res = false;
            }
        }
        //清除变量
        unset($id, $store, $message_content, $count, $phone, $es);
        //判断返回数据
        if ($res) {
            Db::table('company.vip_business')->where('code', $db)->setDec('usable_msg', $message_count);
            webApi(200, '发送成功!');
        } else {
            webApi(400, $res);
        }
    }
}
