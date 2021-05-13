<?php

namespace app\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;
use app\service\ErpWhere;

/**
 * Author lxy
 * Date 2019/0/14
 * Description 卡券赠送
 */
class GiveCoupon extends Common
{
    /**
     * 查询卡券赠送列表
     */
    public function couponList()
    {
        //获取所需数据
        [$page, $limit, $card, $db] = [input('page'), input('limit'), input('search'), $this->db];
        //判断卡号
        if ($card != '') {
            $where[] = ['v.vip_code', '=', $card];
        }
        //时间限制
        if (input('start') != '') {
            $where[] = ['v.create_time', '>=', strtotime(input('start'))];
        }
        if (input('end') != '') {
            $where[] = ['v.create_time', '<=', strtotime(input('end'))];
        }
        //判断条件不存在就都查询
        if (!isset($where)) {
            $where = true;
        }
        //查询的数据
        $data = Db::table($db . '.vip_coupon_record')
            ->alias('v')
            ->leftJoin($db . '.view_viplist vh', 'vh.code = v.vip_code')
            ->field('v.*,vh.username vhname,vh.img img')
            ->where($where)
            ->page($page, $limit)
            ->order('v.create_time', 'desc') //按照登记时间降序排列
            ->select();
        //统计数量
        $count = Db::table($db . '.vip_coupon_record')
            ->alias('v')
            ->leftJoin($db . '.view_viplist vh', 'vh.code = v.vip_code')
            ->field('v.*,vh.username vhname,vh.img img')
            ->where($where)
            ->count();
        //格式化数据
        foreach ($data as $k => $v) {
            //创建时间
            $data[$k]['time_g'] = date('Y-m-d H:i:s', $v['create_time']);
            //类型
            if ($v['card_type'] == 0) {
                $data[$k]['type'] = '优惠劵';
            } else if ($v['card_type'] == 1) {
                $data[$k]['type'] = '折扣券';
            } else if ($v['card_type'] == 2) {
                $data[$k]['type'] = '礼品券';
            }
            //状态
            $data[$k]['status'] !== 1 ? $data[$k]['status_g'] = '正常' : $data[$k]['status_g'] = '核销';
        }
        //清除变量
        unset($page, $limit, $card, $db, $where);
        //格式化数据
        $data = [
            'count' => $count,
            'data' => $data
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }
    /**
     * 按类型查询卡券
     */
    public function couponQuery()
    {
        //当前登入人
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['code'] == 'boss') {
            $stores = Db::table($this->db . '.vip_store')->field('code')->select();
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $orgs = $ew->org()->get();
            $stores = Db::table($this->db . '.vip_store')->field('code')->where('org_code', 'in', $orgs)->select();
        } else {
            $stores = Db::table($this->db . '.vip_store')->field('code')->where('code', $operate['store_code'])->select();
        }
        //接受获取的号码
        $type = input('card_type') ?? null;
        if ($type == null) {
            webApi(400, '卡券类型错误');
        }
        // 定义数组
        $one = [];
        //判断类型并返回数据
        if ($type == 0) {
            //查询赠送门店下的优惠券
            foreach ($stores as $k => $v) {
                $d =  Db::table($this->db . '.vip_cash_coupon')->where('end_time', '>=', time())->where('store_code', 'like', '%' . $v['code'])->where('xz', 2)->where('status', 0)->select(); //优惠券
                $one[] = $d;
            }
            $w = Db::table($this->db . '.vip_cash_coupon')->where('end_time', '>=', time())->where('store_code', "")->where('xz', 2)->where('status', 0)->select(); //无门店限制的优惠券
            $v = Db::table($this->db . '.vip_cash_coupon')->where('xz', 1)->where('status', 0)->select(); //时间限制
            $one = array_merge($one, [0 => $w, 1=>$v]);
        } else if ($type == 1) {
            //查询赠送门店下的折扣券
            foreach ($stores as $k => $v) {
                $d =  Db::table($this->db . '.vip_coupon')->where('end_time', '>=', time())->where('store_code', 'like', '%' . $v['code'])->where('xz', 2)->where('status', 0)->select(); //折扣券
                $one[] = $d;
            }
            $w = Db::table($this->db . '.vip_coupon')->where('end_time', '>=', time())->where('store_code', "")->where('status', 0)->where('xz', 2)->select(); //无门店限制的券
            $v = Db::table($this->db . '.vip_coupon')->where('status', 0)->where('xz', 1)->select();
            $one = array_merge($one, [0 => $w, 1=>$v]);
        } else if ($type == 2) {
            //查询赠送门店下的礼品券
            foreach ($stores as $k => $v) {
                $d =  Db::table($this->db . '.vip_coupon_gift')->where('end_time', '>=', time())->where('store_code', 'like', '%' . $v['code'])->where('xz', 2)->where('status', 0)->select(); //礼品券
                $one[] = $d;
            }
            $w = Db::table($this->db . '.vip_coupon_gift')->where('end_time', '>=', time())->where('store_code', "")->where('xz', 2)->where('status', 0)->select(); //无门店限制的券
            $v = Db::table($this->db . '.vip_coupon_gift')->where('xz', 1)->where('status', 0)->select();
            $one = array_merge($one, [0 => $w, 1=>$v]);
        }
        //格式化数据
        foreach ($one as $k => $v) {
            if ($v == null) {
                unset($one[$k]);
            }
        }
        sort($one);
        if ($one) {
            foreach ($one as $value) {
                foreach ($value as $v) {
                    $arr[] = $v;
                }
            }
            foreach ($arr as $k => $v) {
                if ($v['coupon_type'] == 0) {
                    $arr[$k]['type_name'] = '普通卡券';
                } else if ($v['coupon_type'] == 1) {
                    $arr[$k]['type_name'] = '赠送卡券';
                }
                if ($v['coupon_number'] <= $v['receive']) {
                    $arr[$k]['number_g'] = 0;
                } else {
                    $arr[$k]['number_g'] = $v['coupon_number'] - $v['receive'];
                }
                if ($v['xz'] == 2) {
                    $arr[$k]['time_g'] = date('Y-m-d H:i:s', $v['start_time']) . '-' . date('Y-m-d H:i:s', $v['end_time']);
                } else {
                    $arr[$k]['time_g'] = '无';
                }
            }
        } else {
            $arr = [];
        }
        webApi(200, 'ok', $arr);
    }

    /**
     * 卡券赠送
     */
    public function couponAdd()
    {
        //查询当前登入人
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //判断门店
        if ($operate['store_code'] == "") {
            $store_name = "无门店";
        } else {
            $store_name = Db::table($this->db . '.vip_store')->where('code', $operate['store_code'])->find()['name'];
        }
        //获取卡券类型
        [$type, $coupon_code, $vip_code] = [input('card_type'), input('coupon'), input('card_number')];
        if ($type == null) {
            webApi(400, '卡券类型错误');
        }
        //查询会员
        $vip = Db::table($this->db . '.vip_viplist')->where('code', $vip_code)->find();
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
        if ($one['xz'] == 1) {
            $start_time = time() + (86400 * $one['takeEffect']);
            $end_time = $start_time + (86400 * $one['effective']);
            
        } else {
            $start_time = $one['start_time'];
            $end_time = $one['end_time'];
        }
        // //卡券记录表
        // $couponRecord = Db::table($this->db . '.vip_coupon_record')->where('vip_code', $vip_code)->where('card_code', $one['code'])->count();
        // //限领张数限制
        // if ($one['receive_limit'] != 0) {
        //     if ($couponRecord == $one['receive_limit']) {
        //         webApi(400, '该会员该条卡券的限领张数已用完！'); 
        //     } else {
        //         $one['receive_limit'] = $one['receive_limit'] - $couponRecord;
        //     }
        //     if (intval(input('send_numbers')) > $one['receive_limit']) {
        //         webApi(400, '赠送数量不能大于该条卡券的限领张数!');
        //     }
        // }
        //剩余卡券数量限制
        if ($one['coupon_number'] <= $one['receive']) {
            $one['number_g'] = 0;
            webApi(400, '该卡券剩余数量以为 0 !');
        } else {
            $one['number_g'] = $one['coupon_number'] - $one['receive'];
        }
        if (intval(input('send_numbers')) > $one['number_g']) {
            webApi(400, '赠送数量大于剩余卡券数量!');
        }
        $coupon_store = false;
        if (!empty($one['store_code'])) {  // 判断是否符合赠送门店
            $coupon_store_all = explode(',', $one['store_code']);
            foreach ($coupon_store_all as $val) {
                if ($val == session('info.store')) {
                    $coupon_store = true;
                }
                if (session('info.staff') == 'boss') {
                    $coupon_store = true;
                }
            }
        } else {
            $coupon_store = true;
        }
        if ($coupon_store == false) {
            webApi(400, '该门店没有权限赠送该卡劵');
        }
        unset($coupon_store, $coupon_store_all);
        for ($i = 0; $i < input('send_numbers'); $i++) {
            //添加的数据
            $d = [
                'code' => 'ZSKQ' . $i . ($i + str_replace('.', '', microtime(1))),
                'vip_code' => $vip_code,
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
                'g_store_name' => $store_name
            ];
            $data[] = $d;
        }
        //备注替换
        $one['remark'] = input('remark');
        $coupon_record = Db::table($this->db . '.vip_coupon_record')->where('card_code', $one['code'])->where('vip_code', $vip_code)->count();

        if ($one['receive_limit'] > 0) {
            if ($coupon_record >= $one['receive_limit']) {
                webApi(400, '会员卡劵数量超过限制领取张数!'); 
            }
        }
        
        //判断普通卡券与赠送卡券
        if ($one['coupon_type'] == 0) {
            // $coupon_record = Db::table($this->db . '.vip_coupon_record')
            //     ->where('vip_code', $vip['code'])
            //     ->where('card_type', $type)
            //     ->where('card_code', $coupon_code)
            //     ->find();
            // if ($coupon_record) {
            //     webApi(400, '该会员已经领取过该卡券, 不能重复赠送普通卡券!');
            // }
            if (empty($vip['openid'])) {
                webApi(400, '会员未绑定微信, 无法赠送普通卡券!');
            }
            $title = '您好，【' . $vip['username'] . '】您有卡券待领取！';
            $es = new ErpWhere($this->db, "");
            for ($i = 0; $i < input('send_numbers'); $i++) {
                $es->pushCoupon($vip['openid'], $title, $one, session('info.staff'));
            }
            webApi(200, 'ok', '赠送成功!');
        } else {
            //执行赠送
            $res = Db::table($this->db . '.vip_coupon_record')->insertAll($data);
            $title = '您好，【' . $vip['username'] . '】您收到一张卡券，请到会员中心查看或使用！';
            //清除变量
            unset($data);
            //判断返回数据
            if ($res) {
                if ($type == 0) {
                    Db::table($this->db . '.vip_cash_coupon')->where('code', $coupon_code)->setInc('receive', input('send_numbers')); // 优惠券
                } else if ($type == 1) {
                    Db::table($this->db . '.vip_coupon')->where('code', $coupon_code)->setInc('receive', input('send_numbers')); // 折扣券
                } else if ($type == 2) {
                    Db::table($this->db . '.vip_coupon_gift')->where('code', $coupon_code)->setInc('receive', input('send_numbers')); // 礼品券
                }
                if (empty($vip['openid'])) {
                    webApi(200, 'ok', '赠送成功, 会员未绑定微信不会发送微信消息!');
                } else {
                    $es = new ErpWhere($this->db, "");
                    for ($i = 0; $i < input('send_numbers'); $i++) {
                        $es->pushCoupon($vip['openid'], $title, $one);
                    }
                    webApi(200, 'ok', '赠送成功!');
                }
            } else {
                webApi(400, '赠送失败!');
            }
        }
    }

    /**
     * 卡券详情
     */
    public function couponDetails()
    {
        //接受获取的号码
        [$coupon_code, $staff_code] = [input('coupon_code'), input('staff_code')];
        if ($coupon_code == null) {
            webApi(400, '卡券code错误');
        }
        $operate = Db::table($this->db . '.vip_staff')->where('code', $staff_code)->find();
        //判断门店
        if ($operate['store_code'] == "") {
            $off_name = "无门店";
        } else {
            $off_name = Db::table($this->db . '.vip_store')->where('code', $operate['store_code'])->find()['name'];
        }
        //查询卡券数据
        $one = Db::table($this->db . '.vip_cash_coupon')->where('code', $coupon_code)->find(); // 优惠券
        $two = Db::table($this->db . '.vip_coupon')->where('code', $coupon_code)->find(); // 折扣券
        $three = Db::table($this->db . '.vip_coupon_gift')->where('code', $coupon_code)->find(); // 礼品券
        //判断卡券并返回数据
        if ($one) {
            $one = array_merge($one, ['type' => '优惠券', 'day_time' => "", 'already_number' => 0, 'card_type' => 0, 'off_name' => $off_name]);
            if ($one['xz'] == 2) {
                $one['day_time'] = date('Y-m-d H:i:s', $one['start_time']) . '-' . date('Y-m-d H:i:s', $one['end_time']);
            } else {
                if ($one['takeEffect'] == 0) {
                    $one['day_time'] = '领取后' . $one['effective'] . '天有效';
                } else {
                    $one['day_time'] = '领取' . $one['takeEffect'] . '天后' . $one['effective'] . '天有效';
                }
            }
            $one['coupon_name'] = $one['card_money'] . '元';
            $one['already_number'] = $one['coupon_number'] - $one['receive'];
            webApi(200, 'ok', $one);
        } else if ($two) {
            $two = array_merge($two, ['type' => '折扣券', 'day_time' => "", 'already_number' => 0, 'card_type' => 1, 'off_name' => $off_name]);
            if ($two['xz'] == 2) {
                $two['day_time'] = date('Y-m-d H:i:s', $two['start_time']) . '-' . date('Y-m-d H:i:s', $two['end_time']);
            } else {
                if ($two['takeEffect'] == 0) {
                    $two['day_time'] = '领取后' . $two['effective'] . '天有效';
                } else {
                    $two['day_time'] = '领取' . $two['takeEffect'] . '天后' . $two['effective'] . '天有效';
                }
            }
            $two['coupon_name'] = $two['card_discount'] . '折';
            $two['already_number'] = $two['coupon_number'] - $two['receive'];
            webApi(200, 'ok', $two);
        } else if ($three) {
            $three = array_merge($three, ['type' => '礼品券', 'day_time' => "", 'already_number' => 0, 'card_type' => 2, 'off_name' => $off_name]);
            if ($three['xz'] == 2) {
                $three['day_time'] = date('Y-m-d H:i:s', $three['start_time']) . '-' . date('Y-m-d H:i:s', $three['end_time']);
            } else {
                if ($three['takeEffect'] == 0) {
                    $three['day_time'] = '领取后' . $three['effective'] . '天有效';
                } else {
                    $three['day_time'] = '领取' . $three['takeEffect'] . '天后' . $three['effective'] . '天有效';
                }
            }
            $three['coupon_name'] = $three['gift_code'];
            $three['already_number'] = $three['coupon_number'] - $three['receive'];
            webApi(200, 'ok', $three);
        } else {
            webApi(200, 'ok', []);
        }
    }

    /**
     * 领取卡券
     */
    public function couponReceive()
    {
        //获取卡券类型
        [$type, $coupon_code, $openid, $staff_code] = [input('card_type'), input('coupon_code'), input('openid'), input('staff_code')];
        if ($type == null) {
            webApi(400, '卡券类型错误');
        }
        //查询操作员工
        $operate = Db::table($this->db . '.vip_staff')->where('code', $staff_code)->find();
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
        if ($one['xz'] == 1) {
            $start_time = time() + (86400 * $one['takeEffect']);
            $end_time = $start_time + (86400 * $one['effective']);
            
        } else {
            $start_time = $one['start_time'];
            $end_time = $one['end_time'];
        }
        //查询会员
        $vip = Db::table($this->db . '.vip_viplist')->where('openid', $openid)->find();
        if ($vip) {
            //卡券记录表
            $couponRecord = Db::table($this->db . '.vip_coupon_record')->where('vip_code', $vip['code'])->where('card_code', $coupon_code)->count();
            //限领张数限制
            if ($one['receive_limit'] != 0) {
                if ($couponRecord >= $one['receive_limit']) {
                    webApi(400, '该会员该条卡券的限领张数已用完！');
                }
            }
        } else {
            webApi(400, '该会员未绑定微信!');
        }
        //添加的数据
        $data = [
            'code' => 'CADD' . str_replace('.', '', microtime(1)),
            'vip_code' => $vip['code'],
            'card_type' => $type,
            'card_name' => $one['name'],
            'card_many' => $two,
            'coupon_type' => 0,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'card_code' => $coupon_code,
            'remark' => '会员领取的卡券',
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
            'g_store_name' => $store_name
        ];
        //执行赠送
        $res = Db::table($this->db . '.vip_coupon_record')->insert($data);
        $title = '您好，【' . $vip['username'] . '】您收到一张卡券，请到会员中心查看或使用！';
        //清除变量
        unset($data, $end_time, $start_time);
        //判断返回数据
        if ($res) {
            if ($type == 0) {
                Db::table($this->db . '.vip_cash_coupon')->where('code', $coupon_code)->setInc('receive', 1); // 优惠券
            } else if ($type == 1) {
                Db::table($this->db . '.vip_coupon')->where('code', $coupon_code)->setInc('receive', 1); // 折扣券
            } else if ($type == 2) {
                Db::table($this->db . '.vip_coupon_gift')->where('code', $coupon_code)->setInc('receive', 1); // 礼品券
            }
            $es = new ErpWhere($this->db, "");
            $es->pushCoupon($openid, $title, $one);
            webApi(200, 'ok', '领取成功!');
        } else {
            webApi(400, 'ok', '领取失败!');
        }
    }

    /**
     * 卡劵记录卡券详情
     */
    public function cardDetails()
    {
        //获取code查询记录
        $code = input('code') ?? null;
        if ($code == null) {
            webApi(400, '卡券code错误');
        }
        //查询数据
        $data = Db::table($this->db . '.vip_coupon_record')->where('code', $code)->select();
        //格式化数据
        foreach ($data as $k => $v) {
            //类型
            if ($v['card_type'] == 0) {
                $data[$k]['type'] = '优惠劵';
            } else if ($v['card_type'] == 1) {
                $data[$k]['type'] = '折扣券';
            } else if ($v['card_type'] == 2) {
                $data[$k]['type'] = '礼品券';
            }
            //种类
            if ($v['coupon_type'] == 0) {
                $data[$k]['type_name'] = '普通卡券';
            } else if ($v['coupon_type'] == 1) {
                $data[$k]['type_name'] = '赠送卡券';
            }
        }

        webApi(200, 'ok', $data);
    }
}
