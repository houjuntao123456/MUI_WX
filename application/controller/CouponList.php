<?php

namespace app\controller;

use think\Db;
use think\Controller;

/**
 * Author lhp
 * Date 2019/08/14
 * Description  卡劵
 */
class CouponList extends Common 
{
    public function index()
    {
        [$page, $limit] = [input('page'), input('limit')];
        [$coupon, $type] = [input('coupon'), input('type')];
        // vip_cash_coupon 代金券   vip_coupon 折扣劵   vip_coupon_gift 礼品劵
        $where = [];

        if (!empty($coupon)) {
            $where[] = ['name', 'like', $coupon . '%'];
        }

        $data = Db::table($this->db. '.'.$type)
            ->where('status', 0)
            ->where($where)
            ->page($page, $limit)
            ->select();
        //统计数量
        $count = Db::table($this->db. '.' . $type)
            ->where('status', 0)
            ->where($where)
            ->count();

        foreach ($data as $k => $v) {
            $data[$k]['start_time'] = date('Y-m-d H:i:s', $v['start_time']);
            $data[$k]['end_time'] = date('Y-m-d H:i:s', $v['end_time']);
            if ($v['coupon_number'] <= $v['receive']) {
                $data[$k]['number_g'] = 0;
            } else {
                $data[$k]['number_g'] = $v['coupon_number'] - $v['receive'];
            }
            if ($v['store_code'] == "") {
                $data[$k]['store_g'] = '无门店限制';
            } else {
                $data[$k]['store_g'] = $v['store_name'];
            }
            if ($v['off_store_code'] == "") {
                $data[$k]['off_store_g'] = '无门店限制';
            } else {
                $data[$k]['off_store_g'] = $v['off_store_name'];
            }
        }

        $data = [
            'count' => $count,
            'data' => $data
        ];

        webApi(200, 'ok', $data);
    }

    /**
     * 我的卡劵
     */
    public function myCoupon()
    {
        [$page, $limit] = [input('page'), input('limit')];
        // [$vip_code, $used, $not_used, $expired] = [input('vip_code'), input('used'), input('not_used'), input('expired')];
        [$vip_code, $type] = [input('vip_code'), input('type')];
        // not_used 未使用   used 已使用   expired 已过期
        switch ($type) {
            case 'not_used':
                $data = Db::table($this->db . '.vip_coupon_record')
                    ->where('vip_code', $vip_code)
                    ->where('status', 0)
                    ->where('end_time', '>=', time())
                    ->page($page, $limit)
                    ->order('create_time desc')
                    ->select();
                //统计数量
                $count = Db::table($this->db . '.vip_coupon_record')
                    ->where('vip_code', $vip_code)
                    ->where('status', 0)
                    ->where('end_time', '>=', time())
                    ->order('create_time desc')
                    ->count();
                break;
            case 'used':
                $data = Db::table($this->db . '.vip_coupon_record')
                    ->where('vip_code', $vip_code)
                    ->where('status', 1)
                    ->page($page, $limit)
                    ->order('create_time desc')
                    ->select();
                //统计数量
                $count = Db::table($this->db . '.vip_coupon_record')
                    ->where('vip_code', $vip_code)
                    ->where('status', 1)
                    ->count();
                break;
            case 'expired': 
                $data = Db::table($this->db . '.vip_coupon_record')
                    ->where('vip_code', $vip_code)
                    ->where('status', 0)
                    ->where('end_time', '<', time())
                    ->page($page, $limit)
                    ->order('create_time desc')
                    ->select();
                //统计数量
                $count = Db::table($this->db . '.vip_coupon_record')
                    ->where('vip_code', $vip_code)
                    ->where('status', 0)
                    ->where('end_time', '<', time())
                    ->count();
                break;
        }

        foreach ($data as $k=>$v) {  
            if ($v['card_type'] == 0 ) {
                $data[$k]['card_remark'] = Db::table($this->db.'.vip_cash_coupon')->where('code', $v['card_code'])->find()['remark'];
            } else if ($v['card_type'] == 1 ) {
                $data[$k]['card_remark'] = Db::table($this->db.'.vip_coupon')->where('code', $v['card_code'])->find()['remark'];
            } else if ($v['card_type'] == 2 ) {
                $data[$k]['card_remark'] = Db::table($this->db.'.vip_coupon_gift')->where('code', $v['card_code'])->find()['remark'];
            }
            $data[$k]['start_time'] = date('Y-m-d H:i:s', $v['start_time']);
            $data[$k]['end_time'] = date('Y-m-d H:i:s', $v['end_time']);
            $data[$k]['edit_time'] = date('Y-m-d H:i:s', $v['edit_time']);

            if ($v['a_hsi'] == 0 && $v['b_hsi'] == 0) {
                $data[$k]['hsi'] = '全天';
            } else {
                $data[$k]['hsi'] = $v['a_hsi'] . ' - ' .$v['b_hsi'];
            }

            if (!empty($v['week'])){
                $week = explode(',', $v['week']);
            } else {
                $week = [9];
            }
            $data[$k]['a'] = [];
            foreach($week as $v) {
                switch ($v) {
                    case 1:
                    array_push($data[$k]['a'], '周一');
                    break;
                    case 2:
                    array_push($data[$k]['a'], '周二');
                    break;
                    case 3:
                    array_push($data[$k]['a'], '周三');
                    break;
                    case 4:
                    array_push($data[$k]['a'], '周四');
                    break;
                    case 5:
                    array_push($data[$k]['a'], '周五');
                    break;
                    case 6:
                    array_push($data[$k]['a'], '周六');
                    break;
                    case 0:
                    array_push($data[$k]['a'], '周日');
                    break;
                    default:
                    array_push($data[$k]['a'], '无限制');
                }
            }
        }

        foreach ($data as $k=>$v) {
            $data[$k]['week'] = implode(',', $v['a']);
            unset($data[$k]['a']);
        }

        $data = [
            'count' => $count,
            'data' => $data
        ];
        webApi(200, 'ok', $data);
    }


    /**
     * 卡劵详情
     */
    public function details()
    {
        [$card_code, $type] = [input('card_code'), input('type')];

        switch ($type) {
            case '0':
                $data = Db::table($this->db . '.vip_cash_coupon')
                    ->where('code', $card_code)
                    ->select();
                break;
            case '1':
                $data = Db::table($this->db . '.vip_coupon')
                    ->where('code', $card_code)
                    ->select();
                break;
            case '2':
                $data = Db::table($this->db . '.vip_coupon_gift')
                    ->where('code', $card_code)
                    ->select();
                break;
        }

        foreach ($data as $k => $v) {
            $data[$k]['time'] = date('Y-m-d H:i:s', $v['start_time']).'  至  '. date('Y-m-d H:i:s', $v['end_time']);
        }

        webApi(200, 'ok', $data);
    }

    /**
     * 扫码时的卡劵详情
     */
    public function malist()
    {
        $code = input('code');
        if (!empty($code)) {
            $vip_coupon_record = Db::table($this->db.'.vip_coupon_record')->where('code', $code)->find();
            if ($vip_coupon_record['card_type'] == 0) {
                $vip_coupon_record['type'] = '优惠劵';
            } else if ($vip_coupon_record['card_type'] == 1) {
                $vip_coupon_record['type'] = '折扣劵';
            } else if ($vip_coupon_record['card_type'] == 2) {
                $vip_coupon_record['type'] = '礼品劵';
            }
            $vip_coupon_record['time'] = date('Y-m-d H:i:s', $vip_coupon_record['start_time']).'  至  '. date('Y-m-d H:i:s', $vip_coupon_record['end_time']);
            webApi(200, 'ok', $vip_coupon_record);
        } else {
            webApi(400, '缺少参数');
        }
    }

    /**
     * 查询卡劵
     */
    public function couponSelect()
    {
        if (!empty(input('name'))) {
            $where[] = ['card_name', 'like', input('name') . '%'];
        } else {
            $where = true;
        }

        $data = Db::table($this->db.'.vip_coupon_record')->field('card_code, card_name')->where($where)->group('card_code')->select();

        webApi(200, 'ok', $data);
    }

    /**
     * 卡劵营销 按照卡劵找未使用这张卡劵的人
     */
    public function couponVip()
    {

        [$card_code, $card_type, $start, $end] = [input('card_code'), input('card_type'), strtotime(input('start')), strtotime(input('end'))];

        if (empty($card_code) && empty($start) && empty($card_type) && empty($end)) {
            $data = [
                'count' => 0,
                'data' => []
            ];
            webApi(200, 'ok', $data);
        }

        if (!empty($card_type)) {
            $where[] = ['card_type', '=', $card_type];
        }

        if (!empty($card_code)) {
            $where[] = ['card_code', '=', $card_code];
        }

        if (!empty($start) && !empty($end)) {
            $where[] = ['end_time', '>=', $start];
            $where[] = ['end_time', '<=', $end];
        } else {
            $where[] = ['end_time', '>=', time()];
        }
        $data = Db::table($this->db . '.vip_coupon_record')
            ->alias('v')
            ->leftJoin($this->db . '.vip_viplist vip', 'v.vip_code = vip.code')
            ->field('vip.code, vip.username, vip.birthday, vip.total_consumption, vip.img, vip.consumption_times, vip.final_purchases, vip.return_visit, vip.phone, vip.consumption_number, vip.vvip')
            ->where($where)
            ->where('status', 0)
            ->page(input('page'), input('limit'))
            ->group('vip_code')
            ->select();
        //统计数量
        $count = Db::table($this->db . '.vip_coupon_record')
            ->where($where)
            ->where('status', 0)
            ->group('vip_code')
            ->count();

        $data = [
            'count' => $count,
            'data' => $data
        ];
        webApi(200, 'ok', $data);
    }
}