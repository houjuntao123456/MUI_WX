<?php

namespace app\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;
use app\service\ErpWhere;

/**
 * Author lxy
 * Date 2019/0/14
 * Description 卡券核销
 */
class OffCoupon extends Common
{
    /**
     * 用卡号查询会员
     */
    public function offCouponSel()
    {
        //基本配置
        $sysCon = Db::table($this->db . '.vip_sys_con')->find()['yincang_is_phone'];
        //接受获取的号码
        $number = input('card_code') ?? null;
        if ($number == null) {
            webApi(400, '参数错误!');
        }
        //读卡号
        $dataone = Db::table($this->db . '.view_vipinfo')
            ->where('code', $number)
            ->find();
        //读手机号
        $datatwo = Db::table($this->db . '.view_vipinfo')
            ->where('phone', $number)
            ->find();
        //清除变量
        unset($number);
        //判断是卡号还是手机号
        if ($datatwo) {
            if ($sysCon == "on") {
                if ($datatwo['phone'] != "") {
                    $datatwo['phone_g'] = substr($datatwo['phone'], 0, 3) . '****' . substr($datatwo['phone'], 7);
                }
            } else {
                $datatwo['phone_g'] = $datatwo['phone'];
            }
            webApi(200, '读手机号成功!', $datatwo);
        } else if ($dataone) {
            if ($sysCon == "on") {
                if ($dataone['phone'] != "") {
                    $dataone['phone_g'] = substr($dataone['phone'], 0, 3) . '****' . substr($dataone['phone'], 7);
                }
            } else {
                $dataone['phone_g'] = $dataone['phone'];
            }
            webApi(200, '读卡号成功!', $dataone);
        } else {
            webApi(400, '输入的手机号/卡号不存在，请核对后输入');
        }
    }

    /**
     * 查询核销列表 
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
            $where[] = ['v.edit_time', '>=', strtotime(input('start'))];
        }
        if (input('end') != '') {
            $where[] = ['v.edit_time', '<=', strtotime(input('end'))];
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
            ->where('v.status', 1)
            ->page($page, $limit)
            ->order('v.edit_time', 'desc') //按照登记时间降序排列
            ->select();
        //统计数量
        $count = Db::table($db . '.vip_coupon_record')
            ->alias('v')
            ->leftJoin($db . '.view_viplist vh', 'vh.code = v.vip_code')
            ->field('v.*,vh.username vhname,vh.img img')
            ->where($where)
            ->where('v.status', 1)
            ->count();
        //格式化数据
        foreach ($data as $k => $v) {
            //核销时间
            $data[$k]['time_g'] = date('Y-m-d H:i:s', $v['edit_time']);
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
     * 按卡号查询卡券
     */
    public function couponQuery()
    {
        // 当前登入人 
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        // 门店限制
        if ($operate['code'] == 'boss') {
            $stores = Db::table($this->db . '.vip_store')->field('code')->select();
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $orgs = $ew->org()->get();
            $stores = Db::table($this->db . '.vip_store')->field('code')->where('org_code', 'in', $orgs)->select();
        } else {
            $stores = Db::table($this->db . '.vip_store')->field('code')->where('code', $operate['store_code'])->select();
        }
        // 获取数据
        [$number, $type] = [input('card_code'), input('card_type')];
        if ($number == null || $type == null) {
            webApi(400, '参数错误!');
        }
        // 读卡号
        $dataone = Db::table($this->db . '.vip_viplist')->field('code')->where('code', $number)->find();
        // 读手机号
        $datatwo = Db::table($this->db . '.vip_viplist')->field('code')->where('phone', $number)->find();
        // 判断条件
        if ($dataone) {
            $vipWhere[] = ['vip_code', '=', $dataone['code']];
        } else if ($datatwo) {
            $vipWhere[] = ['vip_code', '=', $datatwo['code']];
        } else {
            webApi(400, "卡号/手机号错误！");
        }
        $ymd = date('Y-m-d'); // 获取当前年月日
        $wdate = date('w'); // 获取当前周
        // 定义数组
        $one = [];
        // 判断类型并返回数据
        if ($type == 0) { // 优惠券
            // 查询记录中门店下的券
            foreach ($stores as $k => $v) {
                $coupon = Db::table($this->db . '.vip_coupon_record')
                    // ->field('card_code, code')
                    ->where($vipWhere)
                    ->where('status', 0)
                    ->where('card_type', 0)
                    ->where('start_time', '<=', time())
                    ->where('end_time', '>=', time())
                    ->where('off_store_code', 'like', '%' . $v['code'])
                    ->select();
                $one[] = $coupon;
            }
            $w = Db::table($this->db . '.vip_coupon_record')
                // ->field('card_code, code')
                ->where($vipWhere)
                ->where('status', 0)
                ->where('card_type', 0)
                ->where('start_time', '<=', time())
                ->where('end_time', '>=', time())
                ->where('off_store_code', "")
                ->select(); //无门店限制的券
            $one = array_merge($one, [0 => $w]);
            //格式化数据
            foreach ($one as $k => $v) {
                if ($v == null) {
                    unset($one[$k]);
                }
            }
        } else if ($type == 1) { // 折扣券
            //查询记录中门店下的券
            foreach ($stores as $k => $v) {
                $coupon = Db::table($this->db . '.vip_coupon_record')
                    // ->field('card_code, code')
                    ->where($vipWhere)
                    ->where('status', 0)
                    ->where('card_type', 1)
                    ->where('start_time', '<=', time())
                    ->where('end_time', '>=', time())
                    ->where('off_store_code', 'like', '%' . $v['code'])
                    ->select();
                $one[] = $coupon;
            }
            $w = Db::table($this->db . '.vip_coupon_record')
                // ->field('card_code, code')
                ->where($vipWhere)
                ->where('status', 0)
                ->where('card_type', 1)
                ->where('start_time', '<=', time())
                ->where('end_time', '>=', time())
                ->where('off_store_code', "")
                ->select(); //无门店限制的券
            $one = array_merge($one, [0 => $w]);
            //格式化数据
            foreach ($one as $k => $v) {
                if ($v == null) {
                    unset($one[$k]);
                }
            }
        } else if ($type == 2) { // 礼品券
            //查询记录中门店下的券
            foreach ($stores as $k => $v) {
                $coupon = Db::table($this->db . '.vip_coupon_record')
                    // ->field('card_code, code')
                    ->where($vipWhere)
                    ->where('status', 0)
                    ->where('card_type', 2)
                    ->where('start_time', '<=', time())
                    ->where('end_time', '>=', time())
                    ->where('off_store_code', 'like', '%' . $v['code'])
                    ->select();
                $one[] = $coupon;
            }
            $w = Db::table($this->db . '.vip_coupon_record')
                // ->field('card_code, code')
                ->where($vipWhere)
                ->where('status', 0)
                ->where('card_type', 2)
                ->where('start_time', '<=', time())
                ->where('end_time', '>=', time())
                ->where('off_store_code', "")
                ->select(); //无门店限制的券
            $one = array_merge($one, [0 => $w]);
            //格式化数据
            foreach ($one as $k => $v) {
                if ($v == null) {
                    unset($one[$k]);
                }
            }
        }
        sort($one);
        if ($one) {
            foreach ($one as $value) {
                foreach ($value as $v) {
                    $pop[] = $v;
                }
            }
            //格式化数据并查询数据
            if ($pop) {
                foreach ($pop as $k => $v) {
                    $whereDate = false;
                    if (!empty($v['week'])) { // 判断是否符合周几
                        $exp = explode(',', $v['week']);
                        foreach ($exp as $val) {
                            if ($val == $wdate) {
                                $whereDate = true;
                            }
                        }
                    } else {
                        $whereDate = true;
                    }
                    $a_hsi = strtotime($ymd . $v['a_hsi']);
                    $b_hsi = strtotime($ymd . $v['b_hsi']);
                    if (!empty($v['a_hsi']) && !empty($v['b_hsi'])) {  //判断是否符合几点到几点
                        if (time() < $a_hsi || time() > $b_hsi) {
                            unset($pop[$k]);
                        }
                    } else if ($whereDate == false) {
                        unset($pop[$k]);
                    }
                }
                foreach ($pop as $k => $v) {
                    if ($v['coupon_type'] == 0) {
                        $pop[$k]['type_name'] = '普通卡券';
                    } else if ($v['coupon_type'] == 1) {
                        $pop[$k]['type_name'] = '赠送卡券';
                    }
                }
            }
            sort($pop);
        } else {
            $pop = [];
        }
        webApi(200, 'ok', $pop);
    }

    /**
     * 卡券核销
     */
    public function writeOff()
    {
        //查询当前登入人
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //判断门店
        if ($operate['store_code'] == "") {
            $store_name = "无门店";
        } else {
            $store_name = Db::table($this->db . '.vip_store')->where('code', $operate['store_code'])->find()['name'];
        }
        [$type, $record_code] = [input('card_type'), input('coupon')];
        if ($type == null) {
            webApi(400, '卡券类型错误!');
        }
        $ymd = date('Y-m-d'); //获取当前年月日
        $wdate = date('w'); //获取当前周
        $coupon_record_status = Db::table($this->db . '.vip_coupon_record')->where('code', $record_code)->find();
        if ($coupon_record_status['status'] == 1) {
            webApi(400, '该卡券已经核销, 无需重复核销!');
        }
        if (time() <= $coupon_record_status['start_time']) {
            webApi(400, '该卡券开始未到');
        }
        if (time() >= $coupon_record_status['end_time']) {
            webApi(400, '该卡券以过期');
        }
        $whereDate = false;
        if (!empty($coupon_record_status['week'])) { // 判断是否符合周几
            $exp = explode(',', $coupon_record_status['week']);
            foreach ($exp as $val) {
                if ($val == $wdate) {
                    $whereDate = true;
                }
            }
            if ($whereDate == false) {
                webApi(400, '该卡券不可在今天使用');
            }
        }

        $coupon_store = false;
        if (!empty($coupon_record_status['off_store_code'])) {  // 判断是否符合核销门店
            $coupon_store_all = explode(',', $coupon_record_status['off_store_code']);
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
            webApi(400, '该门店没有权限核销该卡劵');
        }

        if (!empty($coupon_record_status['a_hsi']) && !empty($coupon_record_status['b_hsi'])) {
            $a_hsi = strtotime($ymd . $coupon_record_status['a_hsi']);
            $b_hsi = strtotime($ymd . $coupon_record_status['b_hsi']);
            if (time() < $a_hsi || time() > $b_hsi) {
                webApi(400, '该卡券不能在这个时间段使用');
            }
        }
        unset($coupon_store, $coupon_store_all, $whereDate, $coupon_record_status, $a_hsi, $b_hsi);
        //修改的数据
        $data = [
            'status' => 1,
            'remarking' => input('remarking'),
            'edit_time' => time(),
            'o_staff' => $operate['code'],
            'o_staff_name' => $operate['name'],
            'o_store' => $operate['store_code'],
            'o_store_name' => $store_name
        ];
        //执行赠送
        $res = Db::table($this->db . '.vip_coupon_record')->where('code', $record_code)->update($data);
        //清除变量
        unset($data);
        //判断返回数据
        if ($res) {
            $coupon_record = Db::table($this->db . '.vip_coupon_record')->where('code', $record_code)->find();
            // $vip = Db::table($this->db . '.vip_viplist')->where('code', $coupon_record['vip_code'])->find();
            //判断类型并返回数据
            if ($type == 0) {
                $one = Db::table($this->db . '.vip_cash_coupon')->where('code', $coupon_record['card_code'])->find(); // 优惠券
                $one['coupon_name'] = $one['card_money'] . '元，优惠劵';
            } else if ($type == 1) {
                $one = Db::table($this->db . '.vip_coupon')->where('code', $coupon_record['card_code'])->find(); // 折扣券
                $one['coupon_name'] = $one['card_discount'] . '折，折扣劵';
            } else if ($type == 2) {
                $one = Db::table($this->db . '.vip_coupon_gift')->where('code', $coupon_record['card_code'])->find(); // 礼品券
                $one['coupon_name'] = $one['gift_code'] . '礼品劵';
            }
            //备注替换
            $one['remark'] = input('remarking');
            //核销状态
            $one['coupon_type'] = 2;


            $coupon = Db::table($this->db . '.vip_coupon_record')->where('code', input('coupon'))->find()['vip_code'];
            $vip = Db::table($this->db . '.vip_viplist')->where('code', $coupon)->find();
            if (empty($vip['level_code'])) {
                $vip['level_code'] = 0;
            }
            if (empty($vip['store_code'])) {
                $vip['store_code'] = 0;
            }
            $card_code = Db::table($this->db . '.vip_coupon_record')->where('code', input('coupon'))->find();
            $write_off_W = Db::table($this->db . '.vip_activity_courtesy')
                ->where('activity_type', '核销有礼')
                ->where('start_time', '<=', time())
                ->where('end_time', '>=', time())
                ->where('level_all', 'like', '%' . $vip['level_code'] . '%')
                ->where('coupon_all', 'like', '%' . $card_code['card_code'] . '%')
                ->where('status', 0)
                ->select();

            foreach ($write_off_W as $k => $v) {
                $write_off_W[$k]['store_w'] = false;
            }
            foreach ($write_off_W as $k => $v) {
                $fstore_all = explode(',', $v['store_all']);
                if (count($fstore_all) == 1 && empty($fstore_all[0])) {
                    $write_off_W[$k]['store_w'] = true;
                } else {
                    foreach ($fstore_all as $val) {
                        if ($val === $vip['store_code']) { // 判断门店
                            $write_off_W[$k]['store_w'] = true;
                        }
                    }
                }
            }
            foreach ($write_off_W as $k => $v) {
                if ($v['store_w'] == false) {
                    unset($write_off_W[$k]);
                }
            }
            if (count($write_off_W) > 1) {
                foreach ($write_off_W as $k => $v) {
                    if (empty($v['store_all'])) {
                        unset($write_off_W[$k]);
                    }
                }
            }
            sort($write_off_W);
            if (!empty($write_off_W)) {
                $write_off = $write_off_W[0];
            } else {
                $write_off = [];
            }

            // $write_off = Db::table($this->db . '.vip_activity_courtesy')
            //     ->where('activity_type', '核销有礼')
            //     ->where('start_time', '<=', time())
            //     ->where('end_time', '>=', time())
            //     ->where('status', 0)
            //     ->find();
            // $level = false;
            // $store = false;
            // $offCoupon = false;
            // if (!empty($write_off)) {
            // $level_all = explode(',', $write_off['level_all']);
            // $store_all = explode(',', $write_off['store_all']);
            // $coupon_all = explode(',', $write_off['coupon_all']);
            // if (empty($write_off['level_all'])) {
            //     $level = true;
            // } else {
            //     foreach ($level_all as $v) {
            //         if ($v === $vip['level_code']) {
            //             $level = true;
            //         }
            //     }
            // }

            // if (empty($write_off['store_all'])) {
            //     $store = true;
            // } else {
            //     foreach ($store_all as $val) {
            //         if ($val === $vip['store_code']) {
            //             $store = true;
            //         }
            //     }
            // }

            // $card_code = Db::table($this->db . '.vip_coupon_record')->where('code', input('coupon'))->find();
            // foreach ($coupon_all as $aal) {
            //     if ($aal === $card_code['card_code']) {
            //         $offCoupon = true;
            //     }
            // }
            $es = new ErpWhere($this->db, "");
            $title = '您好，【' . $vip['username'] . '】您成功使用了一张卡券！';
            // if ($level == true && $store == true && $offCoupon == true) {
            $staff_name = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
            if (empty($vip['openid'])) {
                if (!empty($write_off)) {
                    if (!empty($write_off['integral'])) {
                        Db::table($this->db . '.vip_viplist')->where('code', $coupon)->setInc('residual_integral', $write_off['integral']); // 用户的剩余总积分加
                        Db::table($this->db . '.vip_viplist')->where('code', $coupon)->setInc('total_integral', $write_off['integral']); // 用户的总积分加
                        $integral_jilu = [
                            'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                            'vip_code' => $vip['code'],
                            'vip_name' => $vip['username'],
                            'integral' => $write_off['integral'],
                            'road' => '核销有礼赠送',
                            'staff_code' => session('info.staff'),
                            'staff_name' => $staff_name['name'],
                            'store_code' => session('info.store'),
                            'create_time' => time(),
                        ];
                        Db::table($this->db . '.vip_flow_integral')->insert($integral_jilu);

                        $es->integral_promotes($write_off['integral'], $vip);
                    }
                    if (!empty($write_off['stored_value'])) {
                        Db::table($this->db . '.vip_viplist')->where('code', $coupon)->setInc('residual_value', $write_off['stored_value']); // 用户的剩余储值加
                        Db::table($this->db . '.vip_viplist')->where('code', $coupon)->setInc('total_value', $write_off['stored_value']); // 用户的总储值加
                        $value_jilu = [
                            'documents' => 'CZLS' . str_replace('.', '', microtime(1)),
                            'vip_code' => $vip['code'],
                            'vip_name' => $vip['username'],
                            'stored_value' => $write_off['stored_value'],
                            'road' => '核销有礼赠送',
                            'staff_code' => session('info.staff'),
                            'staff_name' => $staff_name['name'],
                            'store_code' => session('info.store'),
                            'create_time' => time(),
                        ];
                        Db::table($this->db . '.vip_stored_value')->insert($value_jilu);
                        $es->value_promotes($write_off['stored_value'], $vip);
                    }
                    if (!empty($write_off['coupon_code'])) {
                        $poupon = Db::table($this->db . '.vip_agive_coupon')->where('code', $write_off['coupon_code'])->select();
                        if (!empty($poupon)) {
                            foreach ($poupon as $pon) {
                                for ($i = 0; $i < $pon['coupon_number']; $i++) {
                                    $es->couponAdd($pon['card_type'], $pon['coupon_code'], $coupon, '核销有礼');
                                }
                            }
                        }
                    }
                }
                webApi(200, 'ok', '核销成功, 会员未绑定微信不会发送微信消息!');
            } else {
                $es->pushCoupon($vip['openid'], $title, $one, session('info.staff'));
                if (!empty($write_off)) {
                    if (!empty($write_off['integral'])) {
                        Db::table($this->db . '.vip_viplist')->where('code', $coupon)->setInc('residual_integral', $write_off['integral']); // 用户的剩余总积分加
                        Db::table($this->db . '.vip_viplist')->where('code', $coupon)->setInc('total_integral', $write_off['integral']); // 用户的总积分加
                        $integral_jilu = [
                            'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                            'vip_code' => $vip['code'],
                            'vip_name' => $vip['username'],
                            'integral' => $write_off['integral'],
                            'road' => '核销有礼赠送',
                            'staff_code' => session('info.staff'),
                            'staff_name' => $staff_name['name'],
                            'store_code' => session('info.store'),
                            'create_time' => time(),
                        ];
                        Db::table($this->db . '.vip_flow_integral')->insert($integral_jilu);
                        $es->integral_promotes($write_off['integral'], $vip);
                    }
                    if (!empty($write_off['stored_value'])) {
                        Db::table($this->db . '.vip_viplist')->where('code', $coupon)->setInc('residual_value', $write_off['stored_value']); // 用户的剩余储值加
                        Db::table($this->db . '.vip_viplist')->where('code', $coupon)->setInc('total_value', $write_off['stored_value']); // 用户的总储值加
                        $value_jilu = [
                            'documents' => 'CZLS' . str_replace('.', '', microtime(1)),
                            'vip_code' => $vip['code'],
                            'vip_name' => $vip['username'],
                            'stored_value' => $write_off['stored_value'],
                            'road' => '核销有礼赠送',
                            'staff_code' => session('info.staff'),
                            'staff_name' => $staff_name['name'],
                            'store_code' => session('info.store'),
                            'create_time' => time(),
                        ];
                        Db::table($this->db . '.vip_stored_value')->insert($value_jilu);
                        $es->value_promotes($write_off['stored_value'], $vip);
                    }
                    if (!empty($write_off['coupon_code'])) {
                        $poupon = Db::table($this->db . '.vip_agive_coupon')->where('code', $write_off['coupon_code'])->select();
                        if (!empty($poupon)) {
                            foreach ($poupon as $pon) {
                                for ($i = 0; $i < $pon['coupon_number']; $i++) {
                                    $es->couponAdd($pon['card_type'], $pon['coupon_code'], $coupon, '核销有礼赠送');
                                }
                            }
                        }
                    }
                }
                webApi(200, 'ok', '核销成功!');
            }
            // } else {
            //     webApi(200, 'ok', '核销成功!');
            // }
            // } else {
            //     webApi(200, 'ok', '核销成功!');
            // }

        } else {
            webApi(400, '核销失败!');
        }
    }

    /**
     * 扫码卡券核销
     */
    public function sweepCodeOff()
    {
        $ymd = date('Y-m-d'); //获取当前年月日
        $wdate = date('w'); //获取当前周
        $record_code = input('coupon');
        if ($record_code == null) {
            webApi(400, '卡券code错误!');
        }
        //查询当前登入人
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //判断门店
        if ($operate['store_code'] == "") {
            $store_name = "无门店";
        } else {
            $store_name = Db::table($this->db . '.vip_store')->where('code', $operate['store_code'])->find()['name'];
        }
        //验证
        $coupon_record_status = Db::table($this->db . '.vip_coupon_record')->field('status, start_time, end_time, a_hsi, b_hsi, week, off_store_code')->where('code', $record_code)->find();
        if ($coupon_record_status['status'] == 1) {
            webApi(400, '该卡券已经核销, 无需重复核销!');
        }
        if (time() <= $coupon_record_status['start_time']) {
            webApi(400, '该卡券开始未到');
        }
        if (time() >= $coupon_record_status['end_time']) {
            webApi(400, '该卡券以过期');
        }
        $whereDate = false;
        if (!empty($coupon_record_status['week'])) { // 判断是否符合周几
            $exp = explode(',', $coupon_record_status['week']);
            foreach ($exp as $val) {
                if ($val == $wdate) {
                    $whereDate = true;
                }
            }
            if ($whereDate == false) {
                webApi(400, '该卡券不可在今天使用');
            }
        }
        $coupon_store = false;
        if (!empty($coupon_record_status['off_store_code'])) {  // 判断是否符合核销门店
            $coupon_store_all = explode(',', $coupon_record_status['off_store_code']);
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
            webApi(400, '该门店没有权限核销该卡劵');
        }

        if (!empty($coupon_record_status['a_hsi']) && !empty($coupon_record_status['b_hsi'])) {
            $a_hsi = strtotime($ymd . $coupon_record_status['a_hsi']);
            $b_hsi = strtotime($ymd . $coupon_record_status['b_hsi']);
            if (time() < $a_hsi || time() > $b_hsi) {
                webApi(400, '该卡券不能在这个时间段使用');
            }
        }
        unset($coupon_store, $coupon_store_all, $coupon_record_status, $a_hsi, $b_hsi);
        //修改的数据
        $data = [
            'status' => 1,
            'remarking' => '扫码卡券核销',
            'edit_time' => time(),
            'o_staff' => $operate['code'],
            'o_staff_name' => $operate['name'],
            'o_store' => $operate['store_code'],
            'o_store_name' => $store_name
        ];
        //执行赠送
        $res = Db::table($this->db . '.vip_coupon_record')->where('code', $record_code)->update($data);
        //清除变量
        unset($data);
        //判断返回数据
        if ($res) {
            $coupon_record = Db::table($this->db . '.vip_coupon_record')->where('code', $record_code)->find();
            $vip = Db::table($this->db . '.vip_viplist')->where('code', $coupon_record['vip_code'])->find();
            //判断类型并返回数据
            if ($coupon_record['card_type'] == 0) {
                $one = Db::table($this->db . '.vip_cash_coupon')->where('code', $coupon_record['card_code'])->find(); // 优惠券
                $one['coupon_name'] = $one['card_money'] . '元，优惠劵';
            } else if ($coupon_record['card_type'] == 1) {
                $one = Db::table($this->db . '.vip_coupon')->where('code', $coupon_record['card_code'])->find(); // 折扣券
                $one['coupon_name'] = $one['card_discount'] . '折，折扣劵';
            } else if ($coupon_record['card_type'] == 2) {
                $one = Db::table($this->db . '.vip_coupon_gift')->where('code', $coupon_record['card_code'])->find(); // 礼品券
                $one['coupon_name'] = $one['gift_code'] . '礼品劵';
            }
            //备注替换
            $one['remark'] = '扫码卡券核销';
            //核销状态
            $one['coupon_type'] = 2;
            $coupon = Db::table($this->db . '.vip_coupon_record')->where('code', input('coupon'))->find()['vip_code'];
            $vip = Db::table($this->db . '.vip_viplist')->where('code', $coupon)->find();
            if (empty($vip['level_code'])) {
                $vip['level_code'] = 0;
            }
            if (empty($vip['store_code'])) {
                $vip['store_code'] = 0;
            }
            // $write_off = Db::table($this->db . '.vip_activity_courtesy')
            //     ->where('activity_type', '核销有礼')
            //     ->where('start_time', '<=', time())
            //     ->where('end_time', '>=', time())
            //     ->find();
            // $level = false;
            // $store = false;
            // $offCoupon = false;
            // if (!empty($write_off)) {
            //     $level_all = explode(',', $write_off['level_all']);
            //     $store_all = explode(',', $write_off['store_all']);
            //     $coupon_all = explode(',', $write_off['coupon_all']);
            //     if (empty($write_off['level_all'])) {
            //         $level = true;
            //     } else {
            //         foreach ($level_all as $v) {
            //             if ($v == $vip['level_code']) {
            //                 $level = true;
            //             }
            //         }
            //     }
            //     if (empty($write_off['store_all'])) {
            //         $store = true;
            //     } else {
            //         foreach ($store_all as $val) {
            //             if ($val == $vip['store_code']) {
            //                 $store = true;
            //             }
            //         }
            //     }
            //     $card_code = Db::table($this->db.'.vip_coupon_record')->where('code', input('coupon'))->find();
            //     foreach ($coupon_all as $aal) {
            //         if ($aal == $card_code['card_code']) {
            //             $offCoupon = true;
            //         }
            //     }
            // }
            $card_code = Db::table($this->db . '.vip_coupon_record')->where('code', input('coupon'))->find();
            $write_off_W = Db::table($this->db . '.vip_activity_courtesy')
                ->where('activity_type', '核销有礼')
                ->where('start_time', '<=', time())
                ->where('end_time', '>=', time())
                ->where('level_all', 'like', '%' . $vip['level_code'] . '%')
                ->where('coupon_all', 'like', '%' . $card_code['card_code'] . '%')
                ->where('status', 0)
                ->select();

            foreach ($write_off_W as $k => $v) {
                $write_off_W[$k]['store_w'] = false;
            }
            foreach ($write_off_W as $k => $v) {
                $fstore_all = explode(',', $v['store_all']);
                if (count($fstore_all) == 1 && empty($fstore_all[0])) {
                    $write_off_W[$k]['store_w'] = true;
                } else {
                    foreach ($fstore_all as $val) {
                        if ($val === $vip['store_code']) { // 判断门店
                            $write_off_W[$k]['store_w'] = true;
                        }
                    }
                }
            }
            foreach ($write_off_W as $k => $v) {
                if ($v['store_w'] == false) {
                    unset($write_off_W[$k]);
                }
            }
            if (count($write_off_W) > 1) {
                foreach ($write_off_W as $k => $v) {
                    if (empty($v['store_all'])) {
                        unset($write_off_W[$k]);
                    }
                }
            }
            sort($write_off_W);
            if (!empty($write_off_W)) {
                $write_off = $write_off_W[0];
            } else {
                $write_off = [];
            }
            $title = '您好，【' . $vip['username'] . '】您成功使用了一张卡券！';
            $es = new ErpWhere($this->db, "");
            if (empty($vip['openid'])) {
                if (!empty($write_off)) {
                    if (!empty($write_off['integral'])) {
                        Db::table($this->db . '.vip_viplist')->where('code', $coupon)->setInc('residual_integral', $write_off['integral']); // 用户的剩余总积分加
                        Db::table($this->db . '.vip_viplist')->where('code', $coupon)->setInc('total_integral', $write_off['integral']); // 用户的总积分加
                        $integral_jilu = [
                            'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                            'vip_code' => $vip['code'],
                            'vip_name' => $vip['username'],
                            'integral' => $write_off['integral'],
                            'road' => '核销有礼赠送',
                            'create_time' => time(),
                        ];
                        $es->integral_promotes($write_off['integral'], $vip);
                        Db::table($this->db . '.vip_flow_integral')->insert($integral_jilu);
                    }
                    if (!empty($write_off['stored_value'])) {
                        Db::table($this->db . '.vip_viplist')->where('code', $coupon)->setInc('residual_value', $write_off['stored_value']); // 用户的剩余储值加
                        Db::table($this->db . '.vip_viplist')->where('code', $coupon)->setInc('total_value', $write_off['stored_value']); // 用户的总储值加
                        $value_jilu = [
                            'documents' => 'CZLS' . str_replace('.', '', microtime(1)),
                            'vip_code' => $vip['code'],
                            'vip_name' => $vip['username'],
                            'stored_value' => $write_off['stored_value'],
                            'road' => '核销有礼赠送',
                            'create_time' => time(),
                        ];
                        Db::table($this->db . '.vip_stored_value')->insert($value_jilu);
                        $es->value_promotes($write_off['stored_value'], $vip);
                    }
                    if (!empty($write_off['coupon_code'])) {
                        $poupon = Db::table($this->db . '.vip_agive_coupon')->where('code', $write_off['coupon_code'])->select();
                        if (!empty($poupon)) {
                            foreach ($poupon as $pon) {
                                for ($i = 0; $i < $pon['coupon_number']; $i++) {
                                    $es->couponAdd($pon['card_type'], $pon['coupon_code'], $coupon, '核销有礼赠送');
                                }
                            }
                        }
                    }
                }
                webApi(200, 'ok', '核销成功, 会员未绑定微信不会发送微信消息!');
            } else {
                $es->pushCoupon($vip['openid'], $title, $one, session('info.staff'));
                if (!empty($write_off)) {
                    if (!empty($write_off['integral'])) {
                        Db::table($this->db . '.vip_viplist')->where('code', $coupon)->setInc('residual_integral', $write_off['integral']); // 用户的剩余总积分加
                        Db::table($this->db . '.vip_viplist')->where('code', $coupon)->setInc('total_integral', $write_off['integral']); // 用户的总积分加
                        $integral_jilu = [
                            'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                            'vip_code' => $vip['code'],
                            'vip_name' => $vip['username'],
                            'integral' => $write_off['integral'],
                            'road' => '核销有礼赠送',
                            'create_time' => time(),
                        ];
                        $es->integral_promotes($write_off['integral'], $vip);
                        Db::table($this->db . '.vip_flow_integral')->insert($integral_jilu);
                    }
                    if (!empty($write_off['stored_value'])) {
                        Db::table($this->db . '.vip_viplist')->where('code', $coupon)->setInc('residual_value', $write_off['stored_value']); // 用户的剩余储值加
                        Db::table($this->db . '.vip_viplist')->where('code', $coupon)->setInc('total_value', $write_off['stored_value']); // 用户的总储值加
                        $value_jilu = [
                            'documents' => 'CZLS' . str_replace('.', '', microtime(1)),
                            'vip_code' => $vip['code'],
                            'vip_name' => $vip['username'],
                            'stored_value' => $write_off['stored_value'],
                            'road' => '核销有礼赠送',
                            'create_time' => time(),
                        ];
                        Db::table($this->db . '.vip_stored_value')->insert($value_jilu);
                        $es->value_promotes($write_off['stored_value'], $vip);
                    }
                    $poupon = Db::table($this->db . '.vip_agive_coupon')->where('code', $write_off['coupon_code'])->select();
                    if (!empty($poupon)) {
                        foreach ($poupon as $pon) {
                            for ($i = 0; $i < $pon['coupon_number']; $i++) {
                                $es->couponAdd($pon['card_type'], $pon['coupon_code'], $coupon, '核销有礼赠送');
                            }
                        }
                    }
                }
                webApi(200, 'ok', '核销成功!');
            }
        } else {
            webApi(400, '核销失败!');
        }
    }
}
