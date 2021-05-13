<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 * Author lxy
 * Date 2019/02/19
 * Description R活跃分析
 */
class RfmActive extends Common
{
    /**
     * 查询门店
     */
    public function storeSel()
    {
        //查询当前登入人
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['code'] == 'boss') {
            $w = true;
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $stores = $ew->org()->store()->get();
            $w[] = ['code', 'in', $stores];
        } else {
            $w[] = ['code', '=', $operate['store_code']];
        }
        // 查询当前登入人的门店和所属织机构and管理机构作为条件查询门店
        //统计数量
        $count = Db::table($this->db . '.vip_store')->where($w)->where('status', 0)->count();
        //查询的数据
        $store = Db::table($this->db . '.vip_store')->where($w)->where('status', 0)->select();
        //格式化数据
        $data = [
            'count' => $count,
            'data' => $store
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 按照门店条件查询R活跃数据
     */
    public function activeSel()
    {
        // 获取数据
        [$page, $limit, $search, $staff_code, $db] = [input('page'), input('limit'), input('search'), input('staff_code'), $this->db];
        // 判断门店
        if ($search != '') {
            // 统计数量
            $count = Db::table($db . '.vip_rfm_r')
                ->where('store_code', $search)
                ->count();
            // 查询的数据
            $data = Db::table($db . '.vip_rfm_r')
                ->where('store_code', $search)
                ->order('r_create_time', 'desc') // 按照时间降序排列
                ->page($page, $limit)
                ->select();
            // 获取所需会员
            if ($staff_code != "") {
                $where[] = ['v.consultant_code', '=', $staff_code];
            } else {
                $where[] = ['v.store_code', '=', $search];
            }
            $vipData = Db::table($db . '.vip_viplist')
                ->alias('v')
                ->leftJoin($db . '.vip_goods_order o', 'o.vip_code = v.code')
                // 会员的code，时间
                ->field('v.code, ifnull(MAX(o.create_time), 0) as order_time')
                ->where('(o.status = 0 or o.status IS null)')
                ->where($where)
                ->group('v.code')
                ->select();
            $t = time();
            $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
            foreach ($vipData as $k => $v) {
                if ($v['order_time'] >= $start) {
                    $vipData[$k]['rfm_days'] = 0;
                } else {
                    $vipData[$k]['rfm_days'] = intval(round((time() - $v['order_time']) / 86400));
                }
            }
            // 格式化数据
            foreach ($data as $k => $v) {
                $data[$k]['numbertime'] = 0;
            }
            // 获取会员人数
            foreach ($data as $k => $v) {
                foreach ($vipData as $val) {
                    if ($v['r_intervalone'] <= $val['rfm_days'] && $v['r_intervaltwo'] >= $val['rfm_days']) {
                        $data[$k]['numbertime'] += 1;
                    }
                }
            }
            //清除变量
            unset($page, $limit, $vipData, $search, $db);
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
    public function activeSelx()
    {
        //获取数据
        [$page, $limit, $search, $barCode, $staff_code, $db] = [input('page'), input('limit'), input('search'), input('bar_code'), input('staff_code'), $this->db];
        //模糊查询
        if ($search != '') {
            //统计数量
            $count = Db::table($db . '.vip_rfm_r')
                ->where('store_code', $search)
                // ->where('store_code', 'MDERP1548127744152') 
                ->count();
            //查询的数据
            $data = Db::table($db . '.vip_rfm_r')
                ->where('store_code', $search)
                ->order('r_create_time', 'desc') //按照登记时间降序排列
                ->page($page, $limit)
                ->select();
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
                    dump($vipCode);
                    $vipData = Db::table($db . '.view_vipinfo')
                        // ->where('store_code', $search)
                        ->where('code', 'in', $vipCode)
                        ->field('rfm_days')
                        ->select();
                    dump($vipData);
                    exit;
                }
            } else {
                //获取所需条件
                if ($staff_code != "") {
                    $vipData = Db::table($db . '.view_vipinfo')
                        ->where('consultant_code', $staff_code)
                        ->field('rfm_days')
                        ->select();
                } else {
                    $vipData = Db::table($db . '.view_vipinfo')
                        ->where('store_code', $search)
                        ->field('rfm_days')
                        ->select();
                }
            }
            //格式化数据
            foreach ($data as $k => $v) {
                //时间格式的转换
                if ($data[$k]['r_update_time'] == "") {
                    $data[$k]['r_update_time_g'] = date('Y-m-d H:i:s', $v['r_create_time']);
                } else {
                    $data[$k]['r_update_time_g'] = date('Y-m-d H:i:s', $v['r_update_time']);
                }
                $data[$k]['Index_interval'] = $data[$k]['r_intervalone'] . ' - ' . $data[$k]['r_intervaltwo'];
                $data[$k]['numbertime'] = 0;
            }
            //获取会员人数
            foreach ($data as $k => $v) {
                foreach ($vipData as $val) {
                    if ($v['r_intervalone'] <= $val['rfm_days'] && $v['r_intervaltwo'] >= $val['rfm_days']) {
                        $data[$k]['numbertime'] += 1;
                    }
                }
            }
            //清除变量
            unset($page, $limit, $vipData, $search, $db);
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
     * 按条件查询人数
     */
    public function lookPeople()
    {
        // 获取数据
        [$page, $limit, $id, $store, $staff_code, $db] = [input('page'), input('limit'), input('id'), input('store_code'), input('staff_code'), $this->db];
        if ($id == null) {
            webApi(400, '参数错误！');
        }
        // 查询会员数据
        $vipData = $this->rfmMember($id, $store, $staff_code, $db, 'r');
        // 格式化数据
        if ($vipData) {
            $count = count($vipData);
            // 数据分页
            $es = new ErpWhere($db, "");
            $da = $es->arrPage($vipData, $page, $limit);
        } else {
            $count = 0;
            $da = [];
        }
        //清除变量
        unset($id, $page, $limit, $rday, $store, $db);
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
        // 查询会员数据
        $vipData = $this->rfmMember($id, $store, $staff_code, $db, 'r', input('vvip'));
        //提取会员的手机号并更改格式
        $phone = array_column($vipData, 'phone');
        // 格式会员code
        $vip_code = array_column($vipData, 'code');
        //调用短信接口发送短信 $phone会员手机号 $message_content短信内容
        $count = array_chunk($phone, 100);
        $es = new ErpWhere($db, "");
        if ($we == 1) {
            // for ($i = 0; $i < count($count); $i++) {
            $es->wxG($vip_code, $message_content, input('type'));
            // }
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
                return '短信条数不足，可用短信为' . $msg['usable_msg'] . '条' . '，当前发送短信为' . $message_count . '条';
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
        unset($id, $store, $rday, $message_content, $count, $phone, $es);
        //判断返回数据
        if ($res) {
            Db::table('company.vip_business')->where('code', $db)->setDec('usable_msg', $message_count);
            webApi(200, '发送成功!');
        } else {
            webApi(400, $res);
        }
    }

    /**
     * RFM
     */
    public function rfmSel()
    {
        //获取卡号
        $card = input('card_number') ?? null;
        if ($card == null) {
            webApi(400, '参数错误');
        }
        //获取当前会员
        $data = Db::table($this->db . '.vip_viplist')
            ->alias('v')
            ->leftJoin($this->db . '.vip_goods_order o', 'o.vip_code = v.code')
            // 会员code，M金额m_money，
            ->field('v.code, ifnull(MAX(o.create_time), 0) as order_time, ifnull(round(sum(o.real_pay), 2), 0) as m_money, 
                        count(o.id) as f_frequency, count(o.number) as consumption_number')
            ->where('(o.status = 0 or o.status IS null)')
            ->where('v.code', $card)
            ->find();
        //格式化数据
        $t = time();
        $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
        if ($data['order_time'] >= $start) {
            $data['rfm_days'] = 0;
        } else {
            $data['rfm_days'] = intval(round((time() - $data['order_time']) / 86400));
        }
        //R类型和R得分
        if (!empty(Db::table($this->db . '.vip_rfm_r')
            ->where('r_intervalone', '<=', $data['rfm_days'])
            ->where('r_intervaltwo', '>', $data['rfm_days'])
            ->find())) {
            $data['r_type'] = Db::table($this->db . '.vip_rfm_r')
                ->where('r_intervalone', '<=', $data['rfm_days'])
                ->where('r_intervaltwo', '>', $data['rfm_days'])
                ->find()['r_type'];
            $data['r_score'] = Db::table($this->db . '.vip_rfm_r')
                ->where('r_intervalone', '<=', $data['rfm_days'])
                ->where('r_intervaltwo', '>', $data['rfm_days'])
                ->find()['r_score'];
        } else {
            $data['r_type'] = '';
            $data['r_score'] = 0;
        }
        //F类型 F得分
        if (!empty(Db::table($this->db . '.vip_rfm_f')
            ->where('f_intervalone', '<=', $data['f_frequency'])
            ->where('f_intervaltwo', '>', $data['f_frequency'])
            ->find())) {
            $data['f_type'] = Db::table($this->db . '.vip_rfm_f')
                ->where('f_intervalone', '<=', $data['f_frequency'])
                ->where(
                    'f_intervaltwo',
                    '>',
                    $data['f_frequency']
                )->find()['f_type'];
            $data['f_score'] = Db::table($this->db . '.vip_rfm_f')
                ->where('f_intervalone', '<=', $data['f_frequency'])
                ->where('f_intervaltwo', '>', $data['f_frequency'])
                ->find()['f_score'];
        } else {
            $data['f_type'] = '';
            $data['f_score'] = 0;
        }
        //M类型 M得分
        if (!empty(Db::table($this->db . '.vip_rfm_m')
            ->where('m_intervalone', '<=', $data['m_money'])
            ->where('m_intervaltwo', '>', $data['m_money'])
            ->find())) {
            $data['m_type'] = Db::table($this->db . '.vip_rfm_m')
                ->where('m_intervalone', '<=', $data['m_money'])
                ->where('m_intervaltwo', '>', $data['m_money'])
                ->find()['m_type'];
            $data['m_score'] = Db::table($this->db . '.vip_rfm_m')
                ->where('m_intervalone', '<=', $data['m_money'])
                ->where('m_intervaltwo', '>', $data['m_money'])
                ->find()['m_score'];
        } else {
            $data['m_type'] = '';
            $data['m_score'] = 0;
        }
        // P客单价
        if ($data['m_money'] != 0 && $data['f_frequency'] != 0) {
            $data['p_univalent'] = number_format($data['m_money'] / $data['f_frequency'], 2, '.', '');
        } else {
            $data['p_univalent'] = '0.00';
        }
        $data['total_score'] = $data['r_score'] + $data['f_score'] + $data['m_score']; // rfm总分
        //格式化数据
        if ($data['order_time'] == 0) {
            $data['ftime_g'] = '未消费';
        } else {
            $data['ftime_g'] = date('Y-m-d', $data['order_time']);
        }
        //清除变量
        unset($card);
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 选择会员群发卡券
     */
    public function groupCardX()
    {
        // 获取数据
        [$vip_code, $db, $type, $coupon_code] = [input('vip_code'), $this->db, input('card_type'), input('coupon')];
        if ($vip_code == null) {
            webApi(400, '参数错误！');
        }
        if ($type == null) {
            webApi(400, '卡券类型错误');
        }
        $ids = implode(',', $vip_code);
        //查询当前登入人
        $operate = Db::table($db . '.vip_staff')->where('code', session('info.staff'))->find();
        //判断门店
        if ($operate['store_code'] == "") {
            $store_name = "无门店";
        } else {
            $store_name = Db::table($db . '.vip_store')->where('code', $operate['store_code'])->find()['name'];
        }
        //定义卡券金额, 折扣, 礼品
        $two = "";
        $three = "";
        //判断类型并返回数据
        if ($type == 0) {
            $one = Db::table($db . '.vip_cash_coupon')->where('code', $coupon_code)->find(); // 优惠券
            $two = $one['card_money'];
            $three = $one['money'];
            $one['coupon_name'] = $one['card_money'] . '元，优惠劵';
        } else if ($type == 1) {
            $one = Db::table($db . '.vip_coupon')->where('code', $coupon_code)->find(); // 折扣券
            $two = $one['card_discount'];
            $three = $one['money'];
            $one['coupon_name'] = $one['card_discount'] . '折，折扣劵';
        } else if ($type == 2) {
            $one = Db::table($db . '.vip_coupon_gift')->where('code', $coupon_code)->find(); // 礼品券
            $two = $one['gift_code'];
            $three = 0;
            $one['coupon_name'] = $one['gift_code'] . '礼品劵';
        }
        // $ids = '13773033340,1390,18915610815,13814957728,15162353172,13962309725,13812805978,15150370545,13812838728,13814980708,13862342502,15150254852,18118116887,18662129359';
        //按条件查询的数据
        $vipData = Db::table($db . '.vip_viplist')
            ->field('code, username, openid, phone')
            ->where('code', 'in', $ids)
            ->select();
        $vipDataT = Db::table($db . '.vip_viplist')
            ->field('code, username, openid, phone')
            ->where('openid', '<>', '')
            ->where('code', 'in', $ids)
            ->select();
        //备注替换
        if (input('remark') != "") {
            $one['remark'] = input('remark');
        } else {
            $one['remark'] = "无备注说明.";
        }
        if ($one['xz'] == 1) {
            $start_time = time() + (86400 * $one['takeEffect']);
            $end_time = $start_time + (86400 * $one['effective']);
        } else {
            $start_time = $one['start_time'];
            $end_time = $one['end_time'];
        }
        $vcbr = 'VCBR' . str_replace('.', '', microtime(1));
        //普通卡券必须有openid
        if ($one['coupon_type'] == 0) {
            if ($vipDataT) {
                foreach ($vipDataT as $k => $v) {
                    //卡券记录表
                    $couponRecord = Db::table($this->db . '.vip_coupon_record')->where('vip_code', $v['code'])->where('card_code', $one['code'])->count();
                    //限领张数限制
                    if ($one['receive_limit'] != 0) {
                        if ($couponRecord >= $one['receive_limit']) {
                            // webApi(0, 'error', 0, $v['code'] . '会员该条卡券的限领张数已用完！');
                            unset($vipDataT[$k]);
                        }
                    }
                }
                //群发表数据
                $datae = [
                    'code' => $vcbr,
                    'name' => input('name'),
                    'coupon_name' => $one['name'],
                    'coupon_type' => $type,
                    'card_name' => $one['coupon_type'],
                    'number' => count($vipDataT),
                    'time' => time()
                ];
                $res = Db::table($db . '.vip_coupon_batch_record')->insert($datae);
                if ($res) {
                    foreach ($vipDataT as $k => $v) {
                        $title = '您好，【' . $v['username'] . '】您有卡券待领取！';
                        $es = new ErpWhere($db, "");
                        $es->pushCoupon($v['openid'], $title, $one, session('info.staff'));
                    }
                    webApi(200, 'ok', '赠送成功!');
                } else {
                    webApi(400, '赠送失败!');
                }
            } else {
                webApi(400, '没有绑定微信的会员!');
            }
        } else {
            foreach ($vipData as $k => $v) {
                //卡券记录表
                $couponRecord = Db::table($this->db . '.vip_coupon_record')->where('vip_code', $v['code'])->where('card_code', $one['code'])->count();
                //限领张数限制
                if ($one['receive_limit'] != 0) {
                    if ($couponRecord >= $one['receive_limit']) {
                        // webApi(0, 'error', 0, $v['code'] . '会员该条卡券的限领张数已用完！');
                        unset($vipData[$k]);
                    }
                }
            }
            foreach ($vipData as $k => $v) {
                //添加的数据
                $data[] = [
                    'code' =>  'QFWXKQ' . ($v['phone'] + str_replace('.', '', microtime(1))),
                    'vip_code' => $v['code'],
                    'card_type' => $type,
                    'card_name' => $one['name'],
                    'coupon_type' => $one['coupon_type'],
                    'card_many' => $two,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'card_code' => $coupon_code,
                    'remark' => input('remark'),
                    'create_time' => time(),
                    'money' => $three,
                    'superposition' => $one['superposition'], // 是否可叠加使用  0 不可叠加  1 可叠加
                    'delivery' => $one['delivery'], // 是否允许收银员送劵  0 否 1 是
                    'integral' => $one['integral'],  // 所需积分兑换
                    'receive_limit' => $one['receive_limit'], // 限制每人领取张数
                    'a_hsi' => $one['a_hsi'], // 开始时间段
                    'b_hsi' => $one['b_hsi'], // 结束时间段
                    'week' => $one['week'], // 限制周几消费
                    'store_code' => $one['store_code'],
                    'store_name' => $one['store_name'],
                    'off_store_code' => $one['off_store_code'],
                    'off_store_name' => $one['off_store_name'],
                    'g_staff' => $operate['code'],
                    'g_staff_name' => $operate['name'],
                    'g_store' => $operate['store_code'],
                    'g_store_name' => $store_name,
                    'batch_record' => $vcbr
                ];
            }
            //限制群发code重复
            // foreach ($data as $k => $v) {
            //     $data[$k]['code'] = 'QFWXKQ' . ($v['code'] + str_replace('.', '', microtime(1)));
            // }
            //群发表数据
            $datae = [
                'code' => $vcbr,
                'name' => input('name'),
                'coupon_name' => $one['name'],
                'coupon_type' => $type,
                'card_name' => $one['coupon_type'],
                'number' => count($vipData),
                'time' => time()
            ];
            // 启动事务
            Db::startTrans();
            try {
                Db::table($db . '.vip_coupon_record')->insertAll($data);
                Db::table($db . '.vip_coupon_batch_record')->insert($datae);
                // 提交事务
                Db::commit();
                $res = true;
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $res = false;
            }
            //判断返回数据
            if ($res) {
                if ($type == 0) {
                    Db::table($db . '.vip_cash_coupon')->where('code', $coupon_code)->setInc('receive', count($vipData)); // 优惠券
                } else if ($type == 1) {
                    Db::table($db . '.vip_coupon')->where('code', $coupon_code)->setInc('receive', count($vipData)); // 折扣券
                } else if ($type == 2) {
                    Db::table($db . '.vip_coupon_gift')->where('code', $coupon_code)->setInc('receive', count($vipData)); // 礼品券
                }
                if ($vipDataT) {
                    foreach ($vipDataT as $k => $v) {
                        $title = '您好，【' . $v['username'] . '】您收到一张卡券，请到会员中心查看或使用！';
                        $es = new ErpWhere($db, "");
                        $es->pushCoupon($v['openid'], $title, $one);
                    }
                }
                webApi(200, 'ok', '赠送成功, 没有绑定微信的会员不会发送微信消息!');
            } else {
                webApi(400, '赠送失败!');
            }
        }
    }

    /**
     * 按条件查询人数群发视图
     */
    public function groupFuxin()
    {
        // 获取数据
        [$id, $store, $staff_code, $db, $rfmType] = [input('id'), input('store_code'), input('staff_code'), $this->db, input('rfm_type')];
        if ($id == null) {
            webApi(400, '参数错误！');
        }
        $vipData = $this->rfmMember($id, $store, $staff_code, $db, $rfmType);
        //提取会员的手机号并更改格式
        $phone = array_column($vipData, 'phone');
        //调用接口发送视图 $phone会员手机号
        $count = array_chunk($phone, 100);
        $es = new ErpWhere($db, "");
        $msg = Db::table('company.vip_business')->field('code, fuxin_msg')->where('code', $this->db)->find();
        //判断短信条数
        if ($msg['fuxin_msg'] < count($vipData)) {
            webApi(400, '视图条数不足，可用视图为' . $msg['fuxin_msg'] . '条' . '，当前发送视图信为' . count($vipData) . '条');
        }

        $imgcache = cache('getMsgId_' . session('info.staff')); // 获取缓存中图片帧数
        if ($imgcache == false) {   //判断缓存中是否有值
            webApi(400, '没有添加图文');
        }
        Db::startTrans();
        try {
            for ($i = 0; $i < count($count); $i++) {
                $es->fuXin($count[$i], input('content'), $imgcache);
            }
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $res = false;
        }
        unset($imgcache, $msg);
        cache('getMsgId_' . session('info.staff'), null);  // 请求完清除缓存信息
        if ($res) {
            Db::table('company.vip_business')->where('code', $this->db)->setDec('fuxin_msg', count($vipData));
            webApi(200, '发送成功');
        } else {
            webApi(400, '发送失败');
        }
    }

    /**
     * 不分openid群发卡券
     */
    public function groupCardM()
    {
        // 获取数据
        [$id, $store, $staff_code, $db, $type, $coupon_code, $rfmType] = [input('id'), input('store_code'), input('staff_code'), $this->db, input('card_type'), input('coupon'), input('rfm_type')];
        if ($id == null) {
            webApi(400, '参数错误！');
        }
        if ($type == null) {
            webApi(400, '卡券类型错误');
        }
        // 查询会员数据
        $vipData = $this->rfmMember($id, $store, $staff_code, $db, $rfmType);
        // 有openid的
        $vipDataT = [];
        foreach ($vipData as $k => $v) {
            if ($v['openid'] != "") {
                $vipDataT[] = $vipData[$k];
            }
        }
        //查询当前登入人
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //判断门店
        if ($operate['store_code'] == "") {
            $store_name = "无门店";
        } else {
            $store_name = Db::table($this->db . '.vip_store')->where('code', $operate['store_code'])->find()['name'];
        }
        //定义卡券金额, 折扣, 礼品
        $two = "";
        $three = "";
        //判断类型并返回数据
        if ($type == 0) {
            $one = Db::table($this->db . '.vip_cash_coupon')->where('code', $coupon_code)->find(); // 优惠券
            $two = $one['card_money'];
            $three = $one['money'];
            $one['coupon_name'] = $one['card_money'] . '元，优惠劵';
        } else if ($type == 1) {
            $one = Db::table($this->db . '.vip_coupon')->where('code', $coupon_code)->find(); // 折扣券
            $two = $one['card_discount'];
            $three = $one['money'];
            $one['coupon_name'] = $one['card_discount'] . '折，折扣劵';
        } else if ($type == 2) {
            $one = Db::table($this->db . '.vip_coupon_gift')->where('code', $coupon_code)->find(); // 礼品券
            $two = $one['gift_code'];
            $three = 0;
            $one['coupon_name'] = $one['gift_code'] . '礼品劵';
        }
        //备注替换
        if (input('remark') != "") {
            $one['remark'] = input('remark');
        } else {
            $one['remark'] = "无备注说明.";
        }
        if ($one['xz'] == 1) {
            $start_time = time() + (86400 * $one['takeEffect']);
            $end_time = $start_time + (86400 * $one['effective']);
        } else {
            $start_time = $one['start_time'];
            $end_time = $one['end_time'];
        }
        $vcbr = 'VCBR' . str_replace('.', '', microtime(1));
        //普通卡券必须有openid
        if ($one['coupon_type'] == 0) {
            if ($vipDataT) {
                foreach ($vipDataT as $k => $v) {
                    //卡券记录表
                    $couponRecord = Db::table($this->db . '.vip_coupon_record')->where('vip_code', $v['code'])->where('card_code', $one['code'])->count();
                    //限领张数限制
                    if ($one['receive_limit'] != 0) {
                        if ($couponRecord >= $one['receive_limit']) {
                            // webApi(0, 'error', 0, $v['code'] . '会员该条卡券的限领张数已用完！');
                            unset($vipDataT[$k]);
                        }
                    }
                }
                //群发表数据
                $datae = [
                    'code' => $vcbr,
                    'name' => input('name'),
                    'coupon_name' => $one['name'],
                    'coupon_type' => $type,
                    'card_name' => $one['coupon_type'],
                    'number' => count($vipDataT),
                    'time' => time()
                ];
                $res = Db::table($this->db . '.vip_coupon_batch_record')->insert($datae);
                if ($res) {
                    foreach ($vipDataT as $k => $v) {
                        $title = '您好，【' . $v['username'] . '】您有卡券待领取！';
                        $es = new ErpWhere($this->db, "");
                        $es->pushCoupon($v['openid'], $title, $one, session('info.staff'));
                    }
                    webApi(200, 'ok', '赠送成功!');
                } else {
                    webApi(400, '赠送失败!');
                }
            } else {
                webApi(400, '没有绑定微信的会员, 无法赠送普通卡券!');
            }
        } else {
            foreach ($vipData as $k => $v) {
                //卡券记录表
                $couponRecord = Db::table($this->db . '.vip_coupon_record')->where('vip_code', $v['code'])->where('card_code', $one['code'])->count();
                //限领张数限制
                if ($one['receive_limit'] != 0) {
                    if ($couponRecord >= $one['receive_limit']) {
                        // webApi(0, 'error', 0, $v['code'] . '会员该条卡券的限领张数已用完！');
                        unset($vipData[$k]);
                    }
                }
            }
            foreach ($vipData as $k => $v) {
                //添加的数据
                $data[] = [
                    'code' => 'QFWXKQ' . ($v['phone'] + str_replace('.', '', microtime(1))),
                    'vip_code' => $v['code'],
                    'card_type' => $type,
                    'card_name' => $one['name'],
                    'coupon_type' => $one['coupon_type'],
                    'card_many' => $two,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'card_code' => $coupon_code,
                    'remark' => input('remark'),
                    'create_time' => time(),
                    'money' => $three,
                    'superposition' => $one['superposition'], // 是否可叠加使用  0 不可叠加  1 可叠加
                    'delivery' => $one['delivery'], // 是否允许收银员送劵  0 否 1 是
                    'integral' => $one['integral'],  // 所需积分兑换
                    'receive_limit' => $one['receive_limit'], // 限制每人领取张数
                    'a_hsi' => $one['a_hsi'], // 开始时间段
                    'b_hsi' => $one['b_hsi'], // 结束时间段
                    'week' => $one['week'], // 限制周几消费
                    'store_code' => $one['store_code'],
                    'store_name' => $one['store_name'],
                    'off_store_code' => $one['off_store_code'],
                    'off_store_name' => $one['off_store_name'],
                    'g_staff' => $operate['code'],
                    'g_staff_name' => $operate['name'],
                    'g_store' => $operate['store_code'],
                    'g_store_name' => $store_name,
                    'batch_record' => $vcbr
                ];
            }
            //限制群发code重复
            // foreach ($data as $k => $v) {
            //     $data[$k]['code'] = 'QFWXKQ' . ($v['code'] + str_replace('.', '', microtime(1)));
            // }
            //群发表数据
            $datae = [
                'code' => $vcbr,
                'name' => input('name'),
                'coupon_name' => $one['name'],
                'coupon_type' => $type,
                'card_name' => $one['coupon_type'],
                'number' => count($vipData),
                'time' => time()
            ];
            // 启动事务
            Db::startTrans();
            try {
                Db::table($this->db . '.vip_coupon_record')->insertAll($data);
                Db::table($this->db . '.vip_coupon_batch_record')->insert($datae);
                // 提交事务
                Db::commit();
                $res = true;
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $res = false;
            }
            //判断返回数据
            if ($res) {
                if ($type == 0) {
                    Db::table($this->db . '.vip_cash_coupon')->where('code', $coupon_code)->setInc('receive', count($data)); // 优惠券
                } else if ($type == 1) {
                    Db::table($this->db . '.vip_coupon')->where('code', $coupon_code)->setInc('receive', count($data)); // 折扣券
                } else if ($type == 2) {
                    Db::table($this->db . '.vip_coupon_gift')->where('code', $coupon_code)->setInc('receive', count($data)); // 礼品券
                }
                if ($vipDataT) {
                    foreach ($vipDataT as $k => $v) {
                        $title = '您好，【' . $v['username'] . '】您收到一张卡券，请到会员中心查看或使用！';
                        $es = new ErpWhere($this->db, "");
                        $es->pushCoupon($v['openid'], $title, $one);
                    }
                    webApi(200, 'ok', '赠送成功, 没有绑定微信的会员不会发送微信消息!');
                } else {
                    webApi(200, 'ok', '赠送成功, 没有绑定微信的会员不会发送微信消息!');
                }
            } else {
                webApi(400, 'ok', '赠送失败!');
            }
        }
    }

    /**
     * rfm添加专场会员
     */
    public function rfmField()
    {
        // 获取数据
        $data = [
            'title' => input('title'),
            'staff_code' => session('info.staff'),
            'content' => '',
            'type' => 2,
            'rfm_id' => input('id'),
            'rfm_type' => input('rfm_type'),
            'rfm_store' => input('store_code'),
            'create_time' => time()
        ];
        $res = Db::table($this->db . '.vip_field_interaction')->insert($data);
        //判断返回数据
        if ($res) {
            webApi(200, '添加成功!');
        } else {
            webApi(400, '添加失败!');
        }
    }

    /**
     * 定义查询人数方法
     */
    public static function rfmMember($id, $store, $staff_code, $db, $rfmType, $vvip = '')
    {
        if ($staff_code != "") {
            $where[] = ['v.consultant_code', '=', $staff_code];
        } else {
            $where[] = ['v.store_code', '=', $store];
        }
        // 加入判断是不是微信会员
        if ($vvip != "") {
            $where[] = ['v.vvip', '=', $vvip];
        }
        // 会员数据
        $vipData = Db::table($db . '.vip_viplist')
            ->alias('v')
            ->leftJoin($db . '.vip_goods_order o', 'o.vip_code = v.code')
            ->field('v.*, ifnull(MAX(o.create_time), 0) as order_time, ifnull(round(sum(o.real_pay), 2), 0) as total_consumption, 
                        count(o.id) as consumption_times, ifnull(round(sum(o.number)), 0) as consumption_number')
            ->where('(o.status = 0 or o.status IS null)')
            ->where($where)
            ->group('v.code')
            ->select();
        if (empty($vipData)) {
            return [];
        }
        $t = time();
        $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
        foreach ($vipData as $k => $v) {
            if ($v['order_time'] >= $start) {
                $vipData[$k]['rfm_days'] = 0;
            } else {
                $vipData[$k]['rfm_days'] = intval(round((time() - $v['order_time']) / 86400));
            }
            $vipData[$k]['fcs'] = Db::table($db . '.vip_introducer')
                ->where('lnt_code', $v['code'])
                ->count();
        }
        // 获取所需条件
        if ($rfmType == 'r') {
            // 条件
            $rday = Db::table($db . '.vip_rfm_r')->where('id', $id)->find();
            // 查询的数据
            foreach ($vipData as $k => $v) {
                if (!($rday['r_intervalone'] <= $v['rfm_days'] && $rday['r_intervaltwo'] >= $v['rfm_days'])) {
                    unset($vipData[$k]);
                }
            }
            sort($vipData);
        } else if ($rfmType == 'f') {
            // 条件
            $rday = Db::table($db . '.vip_rfm_f')->where('id', $id)->find();
            // 周期
            $f_consumption = Db::table($db . '.vip_rfm_days')->field('f_consumption')->where('store_code', $store)->find();
            // 查询的数据
            if ($rday['f_type'] == "无") {
                foreach ($vipData as $k => $v) {
                    if (!($v['rfm_days'] > $f_consumption['f_consumption'])) {
                        unset($vipData[$k]);
                    }
                }
            } else {
                foreach ($vipData as $k => $v) {
                    if (!(($v['rfm_days'] >= 0) && ($v['rfm_days'] <= $f_consumption['f_consumption']))) {
                        unset($vipData[$k]);
                    }
                }
                foreach ($vipData as $k => $v) {
                    if (!(($v['consumption_times'] >= $rday['f_intervalone']) && ($v['consumption_times'] < $rday['f_intervaltwo']))) {
                        unset($vipData[$k]);
                    }
                }
            }
            sort($vipData);
        } else if ($rfmType == 'm') {
            // 限制
            $rday = Db::table($db . '.vip_rfm_m')->where('id', $id)->find();
            // 周期
            $m_consumption = Db::table($db . '.vip_rfm_days')->field('m_consumption')->where('store_code', $store)->find();
            // 查询的数据 
            foreach ($vipData as $k => $v) {
                if (!(($v['rfm_days'] >= 0) && ($v['rfm_days'] <= $m_consumption['m_consumption']))) {
                    if ($v['order_time'] != 0) {
                        unset($vipData[$k]);
                    }
                }
            }
            foreach ($vipData as $k => $v) {
                if (!(($v['total_consumption'] >= $rday['m_intervalone']) && ($v['total_consumption'] < $rday['m_intervaltwo']))) {
                    unset($vipData[$k]);
                }
            }
            sort($vipData);
        } else if ($rfmType == 'n') {
            // 限制
            $rday = Db::table($db . '.vip_rfm_n')->where('id', $id)->find();
            // 周期
            $n_consumption = Db::table($db . '.vip_rfm_days')->field('n_consumption')->where('store_code', $store)->find();
            // 查询的数据 
            if ($rday['n_type'] == "无") {
                foreach ($vipData as $k => $v) {
                    if (!($v['rfm_days'] > $n_consumption['n_consumption'])) {
                        unset($vipData[$k]);
                    }
                }
            } else {
                foreach ($vipData as $k => $v) {
                    if (!(($v['rfm_days'] >= 0) && ($v['rfm_days'] <= $n_consumption['n_consumption']))) {
                        unset($vipData[$k]);
                    }
                }
                foreach ($vipData as $k => $v) {
                    if (!(($v['consumption_number'] >= $rday['n_intervalone']) && ($v['consumption_number'] < $rday['n_intervaltwo']))) {
                        unset($vipData[$k]);
                    }
                }
            }
            sort($vipData);
        } else if ($rfmType == 'i') {
            // 限制
            $rday = Db::table($db . '.vip_rfm_i')->where('id', $id)->find();
            // 周期
            $i_consumption = Db::table($db . '.vip_rfm_days')->field('i_consumption')->where('store_code', $store)->find();
            // 查询的数据
            foreach ($vipData as $k => $v) {
                if (!(($v['rfm_days'] >= 0) && ($v['rfm_days'] <= $i_consumption['i_consumption']))) {
                    if ($v['order_time'] != 0) {
                        unset($vipData[$k]);
                    }
                }
            }
            foreach ($vipData as $k => $v) {
                if (!(($v['fcs'] >= $rday['i_intervalone']) && ($v['fcs'] < $rday['i_intervaltwo']))) {
                    unset($vipData[$k]);
                }
            }
            sort($vipData);
        } else if ($rfmType == 'p') {
            // 限制
            $rday = Db::table($db . '.vip_rfm_p')->where('id', $id)->find();
            // 周期
            $p_consumption = Db::table($db . '.vip_rfm_days')->field('p_consumption')->where('store_code', $store)->find();
            // 查询的数据 
            foreach ($vipData as $k => $v) {
                if (!(($v['rfm_days'] >= 0) && ($v['rfm_days'] <= $p_consumption['p_consumption']))) {
                    if ($v['order_time'] != 0) {
                        unset($vipData[$k]);
                    }
                }
            }
            foreach ($vipData as $k => $v) {
                //客单价 = 会员实际支付总金额/总次数
                if ($v['total_consumption'] == 0 || $v['consumption_times'] == 0) {
                    $pm = 0;
                } else {
                    $pm = $v['total_consumption'] / $v['consumption_times'];
                }
                if (!(($pm >= $rday['p_intervalone']) && ($pm < $rday['p_intervaltwo']))) {
                    unset($vipData[$k]);
                }
            }
            sort($vipData);
        } else if ($rfmType == 'a') {
            // 限制
            $rday = Db::table($db . '.vip_rfm_a')->where('id', $id)->find();
            // 周期
            $a_consumption = Db::table($db . '.vip_rfm_days')->field('a_consumption')->where('store_code', $store)->find();
            // 查询的数据 
            foreach ($vipData as $k => $v) {
                if (!(($v['rfm_days'] >= 0) && ($v['rfm_days'] <= $a_consumption['a_consumption']))) {
                    if ($v['order_time'] != 0) {
                        unset($vipData[$k]);
                    }
                }
            }
            foreach ($vipData as $k => $v) {
                // 件单价 = 会员实际支付总金额/总件数
                if ($v['total_consumption'] == 0 || $v['consumption_number'] == 0) {
                    $pm = 0;
                } else {
                    $pm = $v['total_consumption'] / $v['consumption_number'];
                }
                if (!(($pm >= $rday['a_intervalone']) && ($pm < $rday['a_intervaltwo']))) {
                    unset($vipData[$k]);
                }
            }
            sort($vipData);
        } else if ($rfmType == 'j') {
            // 限制
            $rday = Db::table($db . '.vip_rfm_j')->where('id', $id)->find();
            // 周期
            $j_consumption = Db::table($db . '.vip_rfm_days')->field('j_consumption')->where('store_code', $store)->find();
            // 查询的数据 
            foreach ($vipData as $k => $v) {
                if (!(($v['rfm_days'] >= 0) && ($v['rfm_days'] <= $j_consumption['j_consumption']))) {
                    if ($v['order_time'] != 0) {
                        unset($vipData[$k]);
                    }
                }
            }
            foreach ($vipData as $k => $v) {
                // 连带率 = 总件数/总次数
                if ($v['consumption_number'] == 0 || $v['consumption_times'] == 0) {
                    $pm = 0;
                } else {
                    $pm = round($v['consumption_number'] / $v['consumption_times'], 2);
                }
                if (!(($pm >= $rday['j_intervalone']) && ($pm < $rday['j_intervaltwo']))) {
                    unset($vipData[$k]);
                }
            }
            sort($vipData);
        } else if ($rfmType == 'c') {
            //限制
            $rday = Db::table($db . '.vip_rfm_c')->where('id', $id)->find();
            // 周期
            $c_consumption = Db::table($db . '.vip_rfm_days')->field('c_consumption')->where('store_code', $store)->find();
            // 查询的数据 
            foreach ($vipData as $k => $v) {
                if (!(($v['rfm_days'] >= 0) && ($v['rfm_days'] <= $c_consumption['c_consumption']))) {
                    if ($v['order_time'] != 0) {
                        unset($vipData[$k]);
                    }
                }
            }
            foreach ($vipData as $k => $v) {
                if (!(($v['total_consumption'] >= $rday['c_intervalone']) && ($v['total_consumption'] < $rday['c_intervaltwo']))) {
                    unset($vipData[$k]);
                }
            }
            sort($vipData);
        }
        if ($vipData) {
            //基本配置
            $sysCon = Db::table($db . '.vip_sys_con')->find();
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
        }
        return $vipData;
    }
}
