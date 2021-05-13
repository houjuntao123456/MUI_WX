<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;
use app\controller\RfmActive as rfma;

/**
 * Author lxy
 * Date 2019/02/19
 * Description F回头率分析
 */
class RfmBack extends Common
{
    /**
     * 按照门店条件查询F回头率数据
     */
    public function backSel()
    {
        // 获取数据
        [$page, $limit, $search, $staff_code, $db] = [input('page'), input('limit'), input('search'), input('staff_code'), $this->db];
        // 判断门店
        if ($search != '') {
            // 统计数量
            $count = Db::table($db . '.vip_rfm_f')
                ->where('store_code', $search)
                ->count();
            // 查询的数据
            $data = Db::table($db . '.vip_rfm_f')
                ->where('store_code', $search)
                ->order('f_create_time', 'desc') //按照登记时间降序排列
                ->page($page, $limit)
                ->select();
            //查询周期
            $f_consumption = Db::table($db . '.vip_rfm_days')->field('f_consumption')
                ->where('store_code', $search)
                ->find();
            // 获取所需会员
            if ($staff_code != "") {
                $where[] = ['v.consultant_code', '=', $staff_code];
            } else {
                $where[] = ['v.store_code', '=', $search];
            }
            //查询人数需要的条件
            $vipa = Db::table($this->db . '.vip_viplist')
                ->alias('v')
                ->leftJoin($this->db . '.vip_goods_order o', 'o.vip_code = v.code')
                // 会员code，时间， 总次数
                ->field('v.code, ifnull(MAX(o.create_time), 0) as order_time, count(o.id) as consumption_times')
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
            // wu 
            $wu = 0;
            foreach ($vipa as $k => $v) {
                if ($v['rfm_days'] > $f_consumption['f_consumption']) {
                    $wu += 1;
                }
                if (!(($v['rfm_days'] >= 0) && ($v['rfm_days'] <= $f_consumption['f_consumption']))) {
                    unset($vipa[$k]);
                }
            }
            sort($vipa);
            // 格式化数据
            foreach ($data as $k => $v) {
                // 赋值周期
                $data[$k]['f_consumption'] =  $f_consumption['f_consumption'];
                $data[$k]['numbertime'] = 0;
            }
            // 人数
            foreach ($data as $k => $v) {
                if ($v['f_type'] == "无") {
                    $data[$k]['numbertime'] = $wu;
                } else {
                    foreach ($vipa as $val) {
                        if ($v['f_intervalone'] <= $val['consumption_times'] && $v['f_intervaltwo'] > $val['consumption_times']) {
                            $data[$k]['numbertime'] += 1;
                        }
                    }
                }
            }
            //清除变量
            unset($page, $limit, $search, $db, $vipa, $f_consumption);
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
    public function backSelx()
    {
        //获取数据
        [$page, $limit, $search, $barCode, $staff_code, $db] = [input('page'), input('limit'), input('search'), input('bar_code'), input('staff_code'), $this->db];
        //模糊查询
        if ($search != '') {
            //统计数量
            $count = Db::table($db . '.vip_rfm_f')
                ->where('store_code', $search)
                // ->where('store_code', 'MDERP1548127744152')
                ->count();
            //查询的数据
            $data = Db::table($db . '.vip_rfm_f')
                ->where('store_code', $search)
                ->order('f_create_time', 'desc') //按照登记时间降序排列
                ->page($page, $limit)
                ->select();
            //查询周期
            $f_consumption = Db::table($db . '.vip_rfm_days')->field('f_consumption')
                ->where('store_code', $search)
                ->find();
            //查询人数需要的条件
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
                    //查询会员未消费天数大于零且小于等于周期的卡号
                    $vips = Db::table($db . '.view_vipinfo')
                        // ->where('store_code', $search)
                        ->where('code', 'in', $vipCode)
                        ->where('rfm_days', '<=', $f_consumption['f_consumption'])
                        ->where('rfm_days', '>=', 0)
                        ->field('code')
                        ->select();
                }
            } else {
                //查询会员未消费天数大于零且小于等于周期的卡号
                if ($staff_code != "") {
                    $vips = Db::table($db . '.view_vipinfo')
                        ->where('consultant_code', $staff_code)
                        ->where('rfm_days', '<=', $f_consumption['f_consumption'])
                        ->where('rfm_days', '>=', 0)
                        ->field('code')
                        ->select();
                } else {
                    $vips = Db::table($db . '.view_vipinfo')
                        ->where('store_code', $search)
                        ->where('rfm_days', '<=', $f_consumption['f_consumption'])
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
            //查询人数需要的条件
            $fc = Db::table($db . '.vip_goods_order')->field('count(id) as count')->where('status', 0)->where('vip_code', 'in', $cards)->group('vip_code')->select();
            //判断并格式化数据
            if ($fc) {
                $fct = array_column($fc, 'count');
                $n = count($fct);
            } else {
                $fct = [];
                $n = 0;
            }
            //格式化数据
            foreach ($data as $k => $v) {
                //时间格式的转换
                if ($data[$k]['f_update_time'] == "") {
                    $data[$k]['f_update_time_g'] = date('Y-m-d H:i:s', $v['f_create_time']);
                } else {
                    $data[$k]['f_update_time_g'] = date('Y-m-d H:i:s', $v['f_update_time']);
                }
                //次数拼接
                $data[$k]['Index_interval'] = $data[$k]['f_intervalone'] . ' ≤ F < ' . $data[$k]['f_intervaltwo'];
                //赋值周期
                $data[$k]['f_consumption'] =  $f_consumption['f_consumption'];
                //人数
                if ($v['f_type'] == "无") {
                    //查询大于未消费天数且已消费的人数
                    //查询人数需要的条件
                    if ($barCode != "") {
                        $es = new ErpWhere($db, "");
                        $vipCode = $es->goodsVip($barCode);
                        //判断
                        if (empty($vipCode)) {
                            $data[$k]['numbertime'] = 0;
                        } else {
                            $data[$k]['numbertime'] = Db::table($db . '.view_viplist')->where('code', 'in', $vipCode)->where('final_purchases', '<>', '未消费')->where('rfm_days', '>', $f_consumption['f_consumption'])->count();
                        }
                    } else {
                        if ($staff_code != "") {
                            $data[$k]['numbertime'] = Db::table($db . '.view_viplist')->where('consultant_code', $staff_code)->where('final_purchases', '<>', '未消费')->where('rfm_days', '>', $f_consumption['f_consumption'])->count();
                        } else {
                            $data[$k]['numbertime'] = Db::table($db . '.view_viplist')->where('store_code', $search)->where('final_purchases', '<>', '未消费')->where('rfm_days', '>', $f_consumption['f_consumption'])->count();
                        }
                    }
                } else {
                    if ($fct) {
                        $c = 0;
                        for ($i = 0; $i < $n; $i++) {
                            if ($fct[$i] >= $v['f_intervalone'] && $fct[$i] < $v['f_intervaltwo']) {
                                $c++;
                            }
                        }
                        $data[$k]['numbertime'] = $c;
                    } else {
                        $data[$k]['numbertime'] = 0;
                    }
                }
            }
            //清除变量
            unset($page, $limit, $search, $db, $vips, $f_consumption, $cards, $c, $fc, $fct, $n);
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
     * 查人数
     */
    public function lookPeople()
    {
        //获取数据
        [$page, $limit, $id, $store, $staff_code, $db] = [input('page'), input('limit'), input('id'), input('store_code'), input('staff_code'), $this->db];
        if ($id == null) {
            webApi(400, '参数错误！');
        }
        // 查询的会员数据
        $vipData = rfma::rfmMember($id, $store, $staff_code, $db, 'f');
        //基本配置
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
        $vipData = rfma::rfmMember($id, $store, $staff_code, $db, 'f', input('vvip'));
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
                    $es->shortMessage($count[$i], '【'.$sms['sms_autograph'].'】'.$message_content);
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
        unset($id, $store, $rday, $message_content, $count, $phone, $es);
        //判断返回数据
        if ($res) {
            Db::table('company.vip_business')->where('code', $db)->setDec('usable_msg', $message_count);
            webApi(200, '发送成功!');
        } else {
            webApi(400, $res);
        }
    }
}
