<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;


/**
 * Author lhp
 * Date 2020/11/06    
 * Description  手机端消费
 */

class WxSell extends Common
{

    /**
     * 查询会员信息
     * @param string input('code') 会员手机号或卡号
     */
    public function vipQuery()
    {
        $data = Db::table($this->db . '.vip_viplist')->where('code', input('code'))->find();
        if (empty($data)) {
            $data = Db::table($this->db . '.vip_viplist')->where('phone', input('code'))->find();
        }

        if (!empty($data)) {
            $month = date('m');
            $day = date('d');
            $bir_month = date('m', $data['birthday']);
            $bir_day = date('d', $data['birthday']);
            $dis = $this->birthdayOnly($this->db, $data);
            $level = Db::table($this->db . '.vip_viplevel')->where('code', $data['level_code'])->find();
            $data['vlname'] = $level['username'] != null ? $level['username'] : '';
            $data['birthday'] = date('Y-m-d', $data['birthday']);
            $data['discount'] = $level['discount'] != null ? $level['discount'] : '';
            if (!empty($dis) && $month == $bir_month && $day == $bir_day) {
                $data['bir_a'] = $dis['money'];
            } else {
                $data['bir_a'] = '无折扣';
            }
            webApi(200, 'ok', $data);
        } else {
            webApi(400, '没有查询到这个会员，请核对手机号或卡号');
        }
    }

    /**
     * 查询商品信息
     * @param string code 查找的条件
     */
    public function goodsQuery()
    {
        [$code, $page, $limit] = [input('code'), input('page'), input('limit')];

        if (empty($code)) {
            $where = true;
        } else {
            $where[] = ['frenum|name|bar_code', 'like', $code . '%'];
        }

        $special_is_discount = Db::table($this->db . '.vip_sys_con')->find()['special_is_discount'];

        $data = Db::table($this->db . '.vip_goods')
            ->where($where)
            ->page(input('page'), input('limit'))
            ->select();

        $count = Db::table($this->db . '.vip_goods')->where($where)->count();

        $data = [
            'special_is_discount' => $special_is_discount,
            'data' => $data,
            'count' => $count
        ];

        webApi(200, 'ok', $data);
    }

    /**
     * 查询优惠劵   
     * @param string vip_code  会员的code
     * @param string money  消费的金额
     */
    public function StackableCoupon()
    {
        [$code, $money] = [input('vip_code'), input('money')];
        if ($code == '非会员') {
            $data = [];
            $count = 0;
        } else {
            $data = Db::table($this->db . '.vip_coupon_record')
                ->field('card_name, card_type, card_many, money, code as value, week, a_hsi, b_hsi')
                ->where('vip_code', $code)
                ->where('status', 0)
                ->where('card_type', '<>', 2)
                ->where('money', '<=', $money)
                ->where('start_time', '<=', time())
                ->where('end_time', '>=', time())
                ->select();
        }
        $ymd = date('Y-m-d');
        $wdate = date('w');
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                if ($v['card_type'] == 0) {
                    $data[$k]['name'] = $v['card_name'] . ' 金额:' . $v['card_many'] . '元';
                } else if ($v['card_type'] == 1) {
                    $data[$k]['name'] = $v['card_name'] . ' 折扣:' . $v['card_many'] . '折';
                }
            }
            foreach ($data as $k => $v) {
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
                        unset($data[$k]);
                    }
                }
                if ($whereDate == false) {
                    unset($data[$k]);
                }
            }
            sort($data);
        }

        webApi(200, 'ok', $data);
    }

    /**
     * 消费添加
     */
    public function salesAdd()
    {
        $db = $this->db;
        $goods = input('goods');
        $staff = session('info.staff');
        $store = session('info.store');
        $coupon_code = input('coupon_code'); // 使用的卡劵记录code
        
        if (empty($staff)) {
            $staff_code = '';
            $staff_name = '';
        } else {
            $staff_code = $staff;
            $vaname = Db::table($db . '.vip_staff')->where('code', $staff)->find();
            $staff_name = $vaname['name'] == null ? '' : $vaname['name'];
        }
        if (!empty($store)) {
            $store_code = $store == null ? '' : $store;
            $ssname = Db::table($db . '.vip_store')->where('code', $store_code)->find();
            $store_name = $ssname['name'] == null ? '' : $ssname['name'];
        } else {
            $store_code = '';
            $store_name = '';
        }

        $kscode = 'KSXF' . str_replace('.', '', microtime(1));
        $jycode = 'JYMX' . str_replace('.', '', microtime(1));

        $vip = Db::table($db . '.vip_viplist')->where('code', input('code'))->find();
        $month = date('m'); //获得当前月
        $day = date('d');   //获得当前日
        $bir_month = date('m', $vip['birthday']);
        $bir_day = date('d', $vip['birthday']); 
        if ($vip) {
            $bir_off = $this->birthdayOnly($db, $vip);
            $vip_viprights_integral_zs = Db::table($db . '.vip_viprights_integral')->find();
            $vip_sys = Db::table($db . '.vip_sys_con')->find();
            if (!empty($vip_sys)) {
                if ($vip_sys['special_is_integral'] == 'off') {
                    $real_pay = input('real_pay') - input('special_offer');
                    $number = input('number') - input('num_s');
                } else {
                    $real_pay = input('real_pay');
                    $number = input('number');
                }
            } else {
                $real_pay = input('real_pay') - input('special_offer');
                $number = input('number') - input('num_s');
            }
            if (!empty($vip_viprights_integral_zs) && $vip_viprights_integral_zs['single_selection'] == 1  && $real_pay >= $vip_viprights_integral_zs['element']) {
                if (!empty($bir_off) && $month == $bir_month && $day == $bir_day) {
                    if (!empty($bir_off['integral'])) {
                        $Totalpoints = floor((($real_pay /  $vip_viprights_integral_zs['element']) * $vip_viprights_integral_zs['integral']) * $bir_off['integral']);
                    } else {
                        $Totalpoints = floor(($real_pay /  $vip_viprights_integral_zs['element']) * $vip_viprights_integral_zs['integral']);
                    }
                } else {
                    $Totalpoints = floor(($real_pay /  $vip_viprights_integral_zs['element']) * $vip_viprights_integral_zs['integral']);
                }
            } else if (!empty($vip_viprights_integral_zs) && $vip_viprights_integral_zs['single_selection'] == 0  && $number >= $vip_viprights_integral_zs['element']) {
                if (!empty($bir_off) && $month == $bir_month && $day == $bir_day) {
                    if (!empty($bir_off['integral'])) {
                        $Totalpoints = floor((($number /  $vip_viprights_integral_zs['element']) * $vip_viprights_integral_zs['integral']) * $bir_off['integral']);
                    } else {
                        $Totalpoints = floor(($number /  $vip_viprights_integral_zs['element']) * $vip_viprights_integral_zs['integral']);
                    }
                } else {
                    $Totalpoints = floor(($number /  $vip_viprights_integral_zs['element']) * $vip_viprights_integral_zs['integral']);
                }
            } else {
                $Totalpoints = 0;
            }

            $data = [
                'code' => $kscode, // '销售单据号（销售单唯一标识）',
                'store_code' => $store_code, // '销售门店编号（销售门店的唯一标识）',
                'operate_name' => $staff_name, //  '操作员名字（收银员名字）',
                'operate_code' => $staff_code, // '员工code',
                'vip_name' => $vip['username'], // '会员名字',
                'vip_code' => $vip['code'], // '会员卡号',
                'vip_phone' => $vip['phone'], // '会员手机号',
                'number' => input('number'), // '订单总数量',
                'money' => input('s_money'), // '总金额（原价合计金额）',
                'dis_money' => input('real_pay'), // '折后合计金额',
                'real_pay' => input('real_pay'), // '实际支付金额',
                'real_income' => input('real_pay'), // '实际收入金额',
                'payment' => input('payment'), // '支付方式',
                'give_integral' => $Totalpoints, // '获得积分数量',
                'create_time' => time(), // '下单时间'
                'remark' => input('remark'), // 备注
                'status' => 2
            ];

            $details = [
                'code' => $jycode,
                'order_code' => $kscode,
                'vip_code' => $vip['code'],
                'vip_name' => $vip['username'],
                'store_code' => $store_code,
                'store_name' => $store_name,
                'staff_code' => $staff_code,
                'staff_name' => $staff_name,
                'source' => '手机端消费', //来源
                'payment' => input('payment'), // 支付方式
                'type' => '消费订单', // 类型
                'money' => input('s_money'), // 订单金额
                'discount' => input('dis_money'), //折扣金额
                'coupon' => input('coupon_money'), //优惠劵金额
                'real_income' => input('real_pay'), //实际收入金额
                't_time' => time(),
                'status' => 2,
                'coupon_record_code' => $coupon_code,
                'remark' => input('remark')
            ];
        } else {
            $data = [
                'code' => $kscode, // '销售单据号（销售单唯一标识）',
                'store_code' => $store_code, // '销售门店编号（销售门店的唯一标识）',
                'operate_name' => $staff_name, //  '操作员名字（收银员名字）',
                'operate_code' => $staff_code, // '员工code',
                'vip_name' => '非会员', // '会员名字',
                'vip_code' => '非会员', // '会员卡号',
                'vip_phone' => '', // '会员手机号',
                'number' => input('number'), // '订单总数量',
                'money' => input('s_money'), // '总金额（原价合计金额）',
                'dis_money' => input('real_pay'), // '折后合计金额',
                'real_pay' => input('real_pay'), // '实际支付金额',
                'real_income' => input('real_pay'), // '实际收入金额',
                'payment' => input('payment'), // '支付方式',
                'create_time' => time(), // '下单时间'
                'remark' => input('remark'), // 备注
                'status' => 2
            ];

            $details = [
                'code' => $jycode,
                'order_code' => $kscode,
                'vip_code' => '非会员',
                'vip_name' => '非会员',
                'store_code' => $store_code,
                'store_name' => $store_name,
                'staff_code' => $staff_code,
                'staff_name' => $staff_name,
                'source' => '手机端消费', //来源
                'payment' => input('payment'), // 支付方式
                'type' => '消费订单', // 类型
                'money' => input('s_money'), // 订单金额
                'discount' => input('dis_money'), //折扣金额
                'coupon' => input('coupon_money'), //优惠劵金额
                'real_income' => input('real_pay'), //实际收入金额
                't_time' => time(),
                'status' => 2,
                'remark' => input('remark')
            ];
        }

        $orderInfo = [];
        if (!empty($goods)) {
            foreach ($goods as $k => $v) {
                $orderInfo[$k]['order_code'] = $kscode;
                $orderInfo[$k]['goods_code'] = $v['bar_code'];
                $orderInfo[$k]['price'] = $v['price'];
                $orderInfo[$k]['number'] = 1;
                $orderInfo[$k]['pro_code'] = $staff_code; //促销员code
                $orderInfo[$k]['pro_name'] = $staff_name;
                $orderInfo[$k]['create_time'] = time();
                $orderInfo[$k]['store_code'] = $store_code; //门店编号 门店code
                $orderInfo[$k]['color'] = $v['color'];
                $orderInfo[$k]['size'] = $v['sizes'];
            }
        }

        $status_a = Db::table('company.vip_business')->where('code', $db)->find();

        // 启动事务
        Db::startTrans();
        try {
            if ($status_a['x_status'] == 1) {
                Db::table($db . '.vip_goods_order')->insert($data);
                Db::table($db . '.vip_details')->insert($details);
            } else {
                Db::table($db . '.vip_details')->insert($details);
            }
            if (isset($orderInfo)) {
                // 明细表数据
                Db::table($db . '.vip_goods_order_info')->insertAll($orderInfo);
            }
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
            webApi(200, '成功', $kscode);
        } else {
            webApi(400, '失败');
        }
    }

    /**
     * 直接支付
     */
    public function directPayment()
    {
        $db = $this->db;
        $staff = session('info.staff');
        $store = session('info.store');
        $coupon_code = input('coupon_code'); // 使用的卡劵记录code
        $payment = input('payment');  // 支付方式 0 微信支付  4 会员钱包
        $goods = input('goods');

        $vip = Db::table($db . '.vip_viplist')->where('code', input('code'))->find();

        if ($payment == 4) {
            if ($vip['residual_value'] < input('real_pay')) {
                webApi(400, '会员钱包余额不足 , 余额 : ' . $vip['residual_value']);
            }
        }

        $month = date('m'); //获得当前月
        $day = date('d');   //获得当前日
        $bir_month = date('m', $vip['birthday']);
        $bir_day = date('d', $vip['birthday']);

        $kscode = 'KSXF' . str_replace('.', '', microtime(1));
        $jycode = 'JYMX' . str_replace('.', '', microtime(1));

        if (empty($staff)) {
            $staff_code = '';
            $staff_name = '';
        } else {
            $staff_code = $staff;
            $vaname = Db::table($db . '.vip_staff')->where('code', $staff)->find();
            $staff_name = $vaname['name'] == null ? '' : $vaname['name'];
        }
        if (!empty($store)) {
            $store_code = $store == null ? '' : $store;
            $ssname = Db::table($db . '.vip_store')->where('code', $store_code)->find();
            $store_name = $ssname['name'] == null ? '' : $ssname['name'];
        } else {
            $store_code = '';
            $store_name = '';
        }
        // $level = false;
        $store = false;
        // $payment_l = false;
        // $whereWeek = false;
        // $whereDate = false;
        if ($vip) {
            $bir_off = $this->birthdayOnly($db, $vip);
            $vip_viprights_integral_zs = Db::table($db . '.vip_viprights_integral')->find();  //->where('single_selection', 1)
            $vip_sys = Db::table($db . '.vip_sys_con')->find();
            if (!empty($vip_sys)) {
                if ($vip_sys['special_is_integral'] == 'off') {
                    $real_pay = input('real_pay') - input('special_offer');
                    $number = input('number') - input('num_s');
                } else {
                    $real_pay = input('real_pay');
                    $number = input('number');
                }
            } else {
                $real_pay = input('real_pay') - input('special_offer');
                $number = input('number') - input('num_s');
            }
            if (!empty($vip_viprights_integral_zs) && $vip_viprights_integral_zs['single_selection'] == 1  && $real_pay >= $vip_viprights_integral_zs['element']) {
                if (!empty($bir_off) && $month == $bir_month && $day == $bir_day) {
                    if (!empty($bir_off['integral'])) {
                        $Totalpoints = floor((($real_pay /  $vip_viprights_integral_zs['element']) * $vip_viprights_integral_zs['integral']) * $bir_off['integral']);
                    } else {
                        $Totalpoints = floor(($real_pay /  $vip_viprights_integral_zs['element']) * $vip_viprights_integral_zs['integral']);
                    }
                } else {
                    $Totalpoints = floor(($real_pay /  $vip_viprights_integral_zs['element']) * $vip_viprights_integral_zs['integral']);
                }
            } else if (!empty($vip_viprights_integral_zs) && $vip_viprights_integral_zs['single_selection'] == 0  && $number >= $vip_viprights_integral_zs['element']) {
                if (!empty($bir_off) && $month == $bir_month && $day == $bir_day) {
                    if (!empty($bir_off['integral'])) {
                        $Totalpoints = floor((($number /  $vip_viprights_integral_zs['element']) * $vip_viprights_integral_zs['integral']) * $bir_off['integral']);
                    } else {
                        $Totalpoints = floor(($number /  $vip_viprights_integral_zs['element']) * $vip_viprights_integral_zs['integral']);
                    }
                } else {
                    $Totalpoints = floor(($number /  $vip_viprights_integral_zs['element']) * $vip_viprights_integral_zs['integral']);
                }
            } else {
                $Totalpoints = 0;
            }
            unset($real_pay, $number, $vip_sys, $vip_viprights_integral_zs);
            //修改的数据
            $datasell = [
                'code' => $kscode, // '销售单据号（销售单唯一标识）',
                'store_code' => $store_code, // '销售门店编号（销售门店的唯一标识）',
                'operate_name' => $staff_name, //  '操作员名字（收银员名字）',
                'operate_code' => $staff_code, // '员工code',
                'vip_name' => $vip['username'], // '会员名字',
                'vip_code' => $vip['code'], // '会员卡号',
                'vip_phone' => $vip['phone'], // '会员手机号',
                'number' => input('number'), // '订单总数量',
                'money' => input('s_money'), // '总金额（原价合计金额）',
                'dis_money' => input('real_pay'), // '折后合计金额',
                'real_pay' => input('real_pay'), // '实际支付金额',
                'real_income' => input('real_pay'), // '实际收入金额',
                'wechat_pay' => input('real_pay'), // '微信支付金额',
                'payment' => $payment, // '支付方式',
                'give_integral' => $Totalpoints, // '获得积分数量',
                'create_time' => time(),
                'remark' => input('remark')
            ];

            $details = [
                'code' => $jycode,
                'order_code' => $kscode,
                'vip_code' => $vip['code'],
                'vip_name' => $vip['username'],
                'store_code' => $store_code,
                'store_name' => $store_name,
                'staff_code' => $staff_code,
                'staff_name' => $staff_name,
                'source' => '微信支付', //来源
                'payment' => $payment, // 支付方式
                'type' => '消费订单', // 类型
                'money' => input('s_money'), // 订单金额
                'discount' => input('dis_money'), //折扣金额
                'coupon' => input('coupon_money'), //优惠劵金额
                'real_income' => input('real_pay'), //实际收入金额
                't_time' => time(),
                'remark' => input('remark')
            ];
            $vipConsumption = [
                'consumption_times' => $vip['consumption_times'] + 1, // 消费次数  
                'consumption_number' => $vip['consumption_number'] + input('number'), // 消费件数
                'total_consumption' => $vip['total_consumption'] + input('real_pay'), // 总消费额
                'final_purchases' => time() // 最后购物时间
            ];
            if (empty($vip['level_code'])) {
                $vip['level_code'] = '无级别';
            }
            if (empty($vip['store_code'])) {
                $vip['store_code'] = 0;
            }
            // 转介绍
            $int_vip = Db::table($db . '.vip_introducer')->where('rsid_code', $vip['code'])->find();
            if (!empty($int_vip)) {
                $introducer_vip = Db::table($db . '.vip_viplist')->where('code', $int_vip['lnt_code'])->find();
                if (empty($introducer_vip['level_code'])) {
                    $introducer_vip['level_code'] = '无级别';
                }
                // 转介绍消费有礼规则
                $int_write_off_s = Db::table($db . '.vip_activity_courtesy')
                    ->where('activity_type', '转介绍消费有礼')
                    ->where('start_time', '<=', time())
                    ->where('end_time', '>=', time())
                    ->where('level_all', 'like', '%' . $introducer_vip['level_code'] . '%')
                    ->where('status', 0)
                    ->select();
                if (!empty($int_write_off_s)) {
                    $int_write_off = $int_write_off_s[0];
                } else {
                    $int_write_off = [];
                }
            }
            // 消费有礼规则
            $write_off_W = Db::table($db . '.vip_activity_courtesy')
                ->where('activity_type', '消费有礼')
                ->where('start_time', '<=', time())
                ->where('end_time', '>=', time())
                ->where('level_all', 'like', '%' . $vip['level_code'] . '%')
                ->where('week', 'like', '%' . date('w') . '%')
                ->where('payment_all', 'like', '%' . input('payment') . '%')
                ->where('status', 0)
                ->select();
            if (!empty($write_off_W)) {
                foreach ($write_off_W as $k => $v) {
                    $write_off_W[$k]['store_w'] = false;
                    $write_off_W[$k]['time_w'] = false;
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
                    $a_hsi = strtotime(date('Y-m-d') . $v['a_hsi']);
                    $b_hsi = strtotime(date('Y-m-d') . $v['b_hsi']);
                    if (time() > $a_hsi && time() < $b_hsi) {  //判断是否符合几点到几点
                        $write_off_W[$k]['time_w'] = true;
                    }
                }

                foreach ($write_off_W as $k => $v) {
                    if ($v['store_w'] == false) {
                        unset($write_off_W[$k]);
                    }
                }
                foreach ($write_off_W as $k => $v) {
                    if ($v['time_w'] == false) {
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
            } else {
                $write_off = [];
            }
            // 会员晋升条件
            $memberLevel = $this->levelVip($db, $vip, input('real_pay'));  
            //修改的数据
            $coupon_record_jl = [
                'status' => 1,
                'remarking' => '消费使用',
                'edit_time' => time(),
                'o_staff' => $staff_code,
                'o_staff_name' => $staff_name,
                'o_store' => $store_code,
                'o_store_name' => $store_name
            ];
        } else {
            $datasell = [
                'code' => $kscode, // '销售单据号（销售单唯一标识）',
                'store_code' => $store_code, // '销售门店编号（销售门店的唯一标识）',
                'operate_name' => $staff_name, //  '操作员名字（收银员名字）',
                'operate_code' => $staff_code, // '员工code',
                'vip_name' => '非会员', // '会员名字',
                'vip_code' => '非会员', // '会员卡号',
                'vip_phone' => '', // '会员手机号',
                'number' => 1, // '订单总数量',
                'money' => input('s_money'), // '总金额（原价合计金额）',
                'dis_money' => input('real_pay'), // '折后合计金额',
                'real_pay' => input('real_pay'), // '实际支付金额',
                'real_income' => input('real_pay'), // '实际收入金额',
                'wechat_pay' => input('real_pay'), // '微信支付金额',
                'payment' => $payment, // '支付方式',
                'create_time' => time(),
                'remark' => input('remark')

            ];
            $details = [
                'code' => $jycode,
                'order_code' => $kscode,
                'vip_code' => '非会员',
                'vip_name' => '非会员',
                'store_code' => $store_code,
                'store_name' => $store_name,
                'staff_code' => $staff_code,
                'staff_name' => $staff_name,
                'source' => '微信支付', //来源
                'payment' => $payment, // 支付方式
                'type' => '消费订单', // 类型
                'money' => input('s_money'), // 订单金额
                'discount' => input('dis_money'), //折扣金额
                'coupon' => input('coupon_money'), //优惠劵金额
                'real_income' => input('real_pay'), //实际收入金额
                't_time' => time(),
                'remark' => input('remark')
            ];
            $memberLevel = [];
            $bir_off = [];
        }

        $orderInfo = [];
        if (!empty($goods)) {
            foreach ($goods as $k => $v) {
                $orderInfo[$k]['order_code'] = $kscode;
                $orderInfo[$k]['goods_code'] = $v['bar_code'];
                $orderInfo[$k]['price'] = $v['price'];
                $orderInfo[$k]['number'] = 1;
                $orderInfo[$k]['pro_code'] = $staff_code; //促销员code
                $orderInfo[$k]['pro_name'] = $staff_name;
                $orderInfo[$k]['create_time'] = time();
                $orderInfo[$k]['store_code'] = $store_code; //门店编号 门店code
                $orderInfo[$k]['color'] = $v['color'];
                $orderInfo[$k]['size'] = $v['sizes'];
            }
            unset($goods);
        }
        // 查询企业
        $status_a = Db::table('company.vip_business')->where('code', $db)->find();
        // 启动事务
        Db::startTrans();
        try {
            $ew = new ErpWhere($db, '');
            if (!empty($coupon_code) && !empty($vip)) {
                $res_record = Db::table($db . '.vip_coupon_record')->where('code', $coupon_code)->update($coupon_record_jl);
                $title = '您好，【' . $vip['username'] . '】您成功使用了一张卡券！';
                if ($res_record) {
                    $coupon_record = Db::table($db . '.vip_coupon_record')->where('code', $coupon_code)->find();
                    //判断类型并返回数据
                    if ($coupon_record['card_type'] == 0) {
                        $one = Db::table($db . '.vip_cash_coupon')->where('code', $coupon_record['card_code'])->find(); // 优惠券
                        $one['coupon_name'] = $one['card_money'] . '元，优惠劵';
                    } else if ($coupon_record['card_type'] == 1) {
                        $one = Db::table($db . '.vip_coupon')->where('code', $coupon_record['card_code'])->find(); // 折扣券
                        $one['coupon_name'] = $one['card_discount'] . '折，折扣劵';
                    } else if ($coupon_record['card_type'] == 2) {
                        $one = Db::table($db . '.vip_coupon_gift')->where('code', $coupon_record['card_code'])->find(); // 礼品券
                        $one['coupon_name'] = $one['gift_code'] . '礼品劵';
                    }
                    //备注替换
                    $one['remark'] = '微信消费使用';
                    //核销状态
                    $one['coupon_type'] = 2;
                }
                $ew->pushCoupon($vip['openid'], $title, $one, $staff_code);
            }
            if (isset($orderInfo)) {
                // 明细表数据
                Db::table($db . '.vip_goods_order_info')->insertAll($orderInfo);
            }
            if ($status_a['x_status'] == 1) {
                Db::table($db . '.vip_goods_order')->insert($datasell);
                Db::table($db . '.vip_details')->insert($details);
                if (isset($vipConsumption)) {
                    Db::table($db . '.vip_viplist')->where('code', $vip['code'])->update($vipConsumption);
                }
            } else {
                Db::table($db . '.vip_details')->insert($details);
            }

            if ($payment == 4 && $vip['residual_value'] >= input('real_pay')) { // 会员钱包支付扣除会员储值
                $value = Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setDec('residual_value', input('real_pay'));
                if ($value) {
                    $v_value_jilu = [
                        'documents' => 'CZLS' . str_replace('.', '', microtime(1)),
                        'vip_code' => $vip['code'],
                        'vip_name' => $vip['username'],
                        'stored_value' => input('real_pay'),
                        'road' => '钱包消费扣除',
                        'staff_code' => $staff_code,
                        'staff_name' => $staff_name,
                        'store_code' => $store_code,
                        'value_status' => 1,
                        'create_time' => time(),
                    ];
                    Db::table($db . '.vip_stored_value')->insert($v_value_jilu);
                }
            }
            // 会员晋升
            if (!empty($memberLevel)) {
                $promote_insert = [
                    'vip_code' => $vip['code'],
                    'vip_name' => $vip['username'],
                    'before_level' => $vip['level_code'],
                    'after_level' => $memberLevel['code'],
                    'reason' => '自动晋升',
                    'create_time' => time()
                ];
                Db::table($db . '.vip_promote')->insert($promote_insert);
                Db::table($db . '.vip_viplist')->where('code', $vip['code'])->update(['level_code' => $memberLevel['code']]);
            }
            if (!empty($vip)) {
                if (!empty($Totalpoints)) {
                    Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('residual_integral', $Totalpoints); // 用户的剩余总积分加
                    Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('total_integral', $Totalpoints); // 用户的总积分加
                    $gz_integral_jilu = [
                        'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                        'vip_code' => $vip['code'],
                        'vip_name' => $vip['username'],
                        'integral' => $Totalpoints,
                        'road' => '积分规则赠送',
                        'staff_code' => $staff_code,
                        'staff_name' => $staff_name,
                        'store_code' => $store_code,
                        'create_time' => time(),
                    ];
                    Db::table($db . '.vip_flow_integral')->insert($gz_integral_jilu);
                    $ew->integral_promotes($Totalpoints, $vip);
                }
                unset($Totalpoints);
            }
            if (!empty($bir_off) && $month == $bir_month && $day == $bir_day) {
                if (!empty($bir_off['stored_code'])) { // 赠送储值
                    $stored_code_type = Db::table($db . '.vip_agive_share')->where('code', $bir_off['stored_code'])->find();
                    if ($stored_code_type['reward_type'] == 0) {
                        $m = number_format(input('real_pay') * ($stored_code_type['money_ratio'] / 100), 2); //向下取整 储值金额
                    } else {
                        $m = $stored_code_type['stored_value'];
                    }
                    Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('residual_value', $m); // 剩余储值
                    Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('total_value', $m); // 总储值
                    $value_jilu = [
                        'documents' => 'CZLS' . str_replace('.', '', microtime(1)),
                        'vip_code' => $vip['code'],
                        'vip_name' => $vip['username'],
                        'stored_value' => $m,
                        'road' => '生日专享赠送',
                        'staff_code' => $staff_code,
                        'staff_name' => $staff_name,
                        'store_code' => $store_code,
                        'create_time' => time(),
                    ];
                    Db::table($db . '.vip_stored_value')->insert($value_jilu);
                    $ew->value_promotes($m, $vip);
                }
                if (!empty($bir_off['coupon_code'])) { // 赠送卡劵
                    if (!empty($bir_off['coupon_code'])) {
                        $poupon = Db::table($db . '.vip_agive_coupon')->where('code', $bir_off['coupon_code'])->select();
                        if (!empty($poupon)) {
                            foreach ($poupon as $pon) {
                                for ($i = 0; $i < $pon['coupon_number']; $i++) {
                                    $ew->couponAdd($pon['card_type'], $pon['coupon_code'], $vip['code'], '生日专享赠送');
                                }
                            }
                        }
                    }
                    unset($poupon, $value_jilu, $m);
                }
            }
            if (!empty($int_write_off)) {
                if ($int_write_off['intelligence_type'] == 0 && $vip['consumption_times'] < 1) {  // 1 是首次  2 每次

                    //赠送积分 
                    if (!empty($int_write_off['integral'])) {
                        Db::table($db . '.vip_viplist')->where('code', $introducer_vip['code'])->setInc('residual_integral', $int_write_off['integral']); // 用户的剩余总积分加
                        Db::table($db . '.vip_viplist')->where('code', $introducer_vip['code'])->setInc('total_integral', $int_write_off['integral']); // 用户的总积分加
                        $integral_jilu_introducer = [
                            'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                            'vip_code' => $introducer_vip['code'],
                            'vip_name' => $introducer_vip['username'],
                            'integral' => $int_write_off['integral'],
                            'road' => '转介绍消费赠送',
                            'create_time' => time(),
                        ];
                        Db::table($db . '.vip_flow_integral')->insert($integral_jilu_introducer);
                        unset($integral_jilu_introducer);
                        $ew->integral_promotes($int_write_off['integral'], $introducer_vip);
                    }
                    // 赠送储值
                    if (!empty($int_write_off['stored_code'])) {
                        $introducer_stored_type = Db::table($db . '.vip_agive_share')->where('code', $int_write_off['stored_code'])->find();
                        if ($introducer_stored_type['reward_type'] == 0) {
                            $int_money_v = input('real_pay') * ($introducer_stored_type['money_ratio'] / 100); //保留两位小数
                            $int_money = number_format($int_money_v, 2);
                        } else {
                            $int_money = $introducer_stored_type['stored_value'];
                        }
                        Db::table($db . '.vip_viplist')->where('code', $introducer_vip['code'])->setInc('residual_value', $int_money); // 剩余储值
                        Db::table($db . '.vip_viplist')->where('code', $introducer_vip['code'])->setInc('total_value', $int_money); // 总储值
                        $int_value_jilu = [
                            'documents' => 'CZLS' . str_replace('.', '', microtime(1)),
                            'vip_code' => $introducer_vip['code'],
                            'vip_name' => $introducer_vip['username'],
                            'stored_value' => $int_money,
                            'road' => '转介绍消费返利',
                            'staff_code' => $staff_code,
                            'staff_name' => $staff_name,
                            'store_code' => $store_code,
                            'create_time' => time(),
                        ];
                        Db::table($db . '.vip_stored_value')->insert($int_value_jilu);
                        $ew->value_promotes($int_money, $introducer_vip);
                        unset($int_money, $int_money_v);
                    }

                    // 赠送卡劵
                    if (!empty($int_write_off['coupon_code'])) {
                        $poupon_int = Db::table($db . '.vip_agive_coupon')->where('code', $int_write_off['coupon_code'])->select();
                        if (!empty($poupon_int)) {
                            foreach ($poupon_int as $val) {
                                for ($i = 0; $i < $val['coupon_number']; $i++) {
                                    $ew->couponAdd($val['card_type'], $val['coupon_code'], $introducer_vip['code'], '转介绍消费赠送');
                                }
                            }
                        }
                        unset($poupon_int);
                    }
                } else if ($int_write_off['intelligence_type'] == 1) {
                    //赠送积分 
                    if (!empty($int_write_off['integral'])) {
                        Db::table($db . '.vip_viplist')->where('code', $introducer_vip['code'])->setInc('residual_integral', $int_write_off['integral']); // 用户的剩余总积分加
                        Db::table($db . '.vip_viplist')->where('code', $introducer_vip['code'])->setInc('total_integral', $int_write_off['integral']); // 用户的总积分加
                        $integral_jilu_introducer = [
                            'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                            'vip_code' => $introducer_vip['code'],
                            'vip_name' => $introducer_vip['username'],
                            'integral' => $int_write_off['integral'],
                            'road' => '转介绍消费赠送',
                            'create_time' => time(),
                        ];
                        Db::table($db . '.vip_flow_integral')->insert($integral_jilu_introducer);
                        unset($integral_jilu_introducer);
                        $ew->integral_promotes($int_write_off['integral'], $introducer_vip);
                    }
                    // 赠送储值
                    if (!empty($int_write_off['stored_code'])) {
                        $introducer_stored_type = Db::table($db . '.vip_agive_share')->where('code', $int_write_off['stored_code'])->find();
                        if ($introducer_stored_type['reward_type'] == 0) {
                            $int_money_v = input('real_pay') * ($introducer_stored_type['money_ratio'] / 100); //保留两位小数
                            $int_money = number_format($int_money_v, 2);
                        } else {
                            $int_money = $introducer_stored_type['stored_value'];
                        }
                        Db::table($db . '.vip_viplist')->where('code', $introducer_vip['code'])->setInc('residual_value', $int_money); // 剩余储值
                        Db::table($db . '.vip_viplist')->where('code', $introducer_vip['code'])->setInc('total_value', $int_money); // 总储值
                        $int_value_jilu = [
                            'documents' => 'CZLS' . str_replace('.', '', microtime(1)),
                            'vip_code' => $introducer_vip['code'],
                            'vip_name' => $introducer_vip['username'],
                            'stored_value' => $int_money,
                            'road' => '转介绍消费返利',
                            'staff_code' => $staff_code,
                            'staff_name' => $staff_name,
                            'store_code' => $store_code,
                            'create_time' => time(),
                        ];
                        Db::table($db . '.vip_stored_value')->insert($int_value_jilu);
                        $ew->value_promotes($int_money, $introducer_vip);
                        unset($int_money, $int_money_v);
                    }

                    // 赠送卡劵
                    if (!empty($int_write_off['coupon_code'])) {
                        $poupon_int = Db::table($db . '.vip_agive_coupon')->where('code', $int_write_off['coupon_code'])->select();
                        if (!empty($poupon_int)) {
                            foreach ($poupon_int as $val) {
                                for ($i = 0; $i < $val['coupon_number']; $i++) {
                                    $ew->couponAdd($val['card_type'], $val['coupon_code'], $introducer_vip['code'], '转介绍消费赠送');
                                }
                            }
                        }
                        unset($poupon_int);
                    }
                }
            }

            if (!empty($write_off)) {  // 如果都符合赠送积分储值和卡劵
                if (!empty($write_off['integral_code'])) { //赠送积分
                    $agive_integral_type = Db::table($db . '.vip_agive_integral')->where('code', $write_off['integral_code'])->find();
                    $agive_integral = Db::table($db . '.vip_agive_integral')->where('code', $write_off['integral_code'])->select();
                    $newArr = [];
                    if (!empty($agive_integral)) {
                        foreach ($agive_integral as $v) {
                            if ($v['reward_money'] <= input('real_pay')) {
                                array_push($newArr, $v['reward_money']);
                            }
                        }
                        if (count($newArr) > 0) {
                            $max_integral = Db::table($db . '.vip_agive_integral')->where('code', $write_off['integral_code'])->where('reward_money', max($newArr))->find();
                            if ($agive_integral_type['reward_type'] == 0) { // 0单次满赠奖励   1 每满赠奖励
                                $fs = $max_integral['integral'];
                            } else {
                                $Multiple = floor(input('real_pay') / max($newArr)); // 满了几次
                                $fs = $max_integral['integral'] * $Multiple;
                            }
                            Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('residual_integral', $fs); // 用户的剩余总积分加
                            Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('total_integral', $fs); // 用户的总积分加
                            $integral_jilu = [
                                'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                                'vip_code' => $vip['code'],
                                'vip_name' => $vip['username'],
                                'integral' => $fs,
                                'road' => '消费有礼赠送',
                                'staff_code' => $staff_code,
                                'staff_name' => $staff_name,
                                'store_code' => $store_code,
                                'create_time' => time(),
                            ];
                            Db::table($db . '.vip_flow_integral')->insert($integral_jilu);
                            $ew->integral_promotes($fs, $vip);
                        }
                        unset($agive_integral_type, $agive_integral, $newArr, $Multiple, $max_integral, $integral_jilu, $f);
                    }
                }
                if (!empty($write_off['stored_code'])) { // 赠送储值
                    $stored_code_type = Db::table($db . '.vip_agive_share')->where('code', $write_off['stored_code'])->find();
                    $stored_code = Db::table($db . '.vip_agive_share')->where('code', $write_off['stored_code'])->select();
                    $newZarr = [];
                    if (!empty($stored_code)) {
                        foreach ($stored_code as $v) {
                            if ($v['reward_money'] <= input('real_pay')) {
                                array_push($newZarr, $v['reward_money']);
                            }
                        }
                        if (count($newZarr) > 0) {
                            $max_stored = Db::table($db . '.vip_agive_share')->where('code', $write_off['stored_code'])->where('reward_money', max($newZarr))->find();
                            if ($stored_code_type['reward_type'] == 0) {
                                $m = number_format(input('real_pay') * ($max_stored['money_ratio'] / 100), 2); //向下取整 储值金额
                                if ($m > $max_stored['money_limit']) {
                                    $m = $max_stored['money_limit'];
                                }
                            } else {
                                $m = $max_stored['stored_value'];
                            }
                            Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('residual_value', $m); // 剩余储值
                            Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('total_value', $m); // 总储值
                            $value_jilu = [
                                'documents' => 'CZLS' . str_replace('.', '', microtime(1)),
                                'vip_code' => $vip['code'],
                                'vip_name' => $vip['username'],
                                'stored_value' => $m,
                                'road' => '消费有礼赠送',
                                'staff_code' => $staff_code,
                                'staff_name' => $staff_name,
                                'store_code' => $store_code,
                                'create_time' => time(),
                            ];
                            Db::table($db . '.vip_stored_value')->insert($value_jilu);
                            $ew->value_promotes($m, $vip);
                        }
                        unset($newZarr, $stored_code, $stored_code_type, $max_stored, $m);
                    }
                }
                if (!empty($write_off['coupon_code'])) { // 赠送卡劵
                    $coupon_type = Db::table($db . '.vip_agive_coupon')->where('code', $write_off['coupon_code'])->find();
                    $coupon = Db::table($db . '.vip_agive_coupon')->where('code', $write_off['coupon_code'])->select();
                    $newCarr = [];
                    if (!empty($coupon)) {
                        foreach ($coupon as $v) {
                            if ($v['reward_money'] <= input('real_pay')) {
                                array_push($newCarr, $v['reward_money']);
                            }
                        }
                        if (count($newCarr) > 0) {
                            $max_coupon = Db::table($db . '.vip_agive_coupon')->where('code', $write_off['coupon_code'])->where('reward_money', max($newCarr))->find();
                            if ($max_coupon['reward_type'] == 0) {
                                $c = $max_coupon['coupon_number'];
                            } else {
                                $Cultiple = floor(input('real_pay') / max($newCarr)); // 满了几次
                                $c = $max_coupon['coupon_number'] * $Cultiple;
                            }
                            for ($i = 0; $i < $c; $i++) {
                                $ew->couponAdd($max_coupon['card_type'], $max_coupon['coupon_code'], $vip['code'], '消费有礼赠送');
                            }
                        }
                        unset($coupon_type, $coupon, $newCarr, $max_coupon, $Cultiple);
                    }
                }
            }
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $res = false;
        }
        unset($vip, $payment, $datasell, $details, $vipConsumption);
        //判断返回数据  
        if ($res) {
            webApi(200, '成功');
        } else {
            webApi(400, '失败');
        }
    }

    /**
     * 扫码支付成功后添加
     * @param string company    企业号
     * @param string order_code 销售记录code
     */
    public function sweepPayment()
    {
        $db = input('company');
        $gods_order_code = input('order_code');

        $vip_details = Db::table($db . '.vip_details')
            ->field('vip_code, money, payment, discount, coupon, coupon_record_code, store_code, store_name, staff_code, staff_name, real_income')
            ->where('order_code', $gods_order_code)
            ->where('status', 2)
            ->find();
        if (empty($vip_details)) {
            webApi(400, '没有查到这个订单');
        }
        //订单数量   
        $gods_order_number = Db::table($db . '.vip_goods_order')->field('number, give_integral')->where('code', $gods_order_code)->where('status', 2)->find();

        $vip = Db::table($db . '.vip_viplist')->where('code', $vip_details['vip_code'])->find();
        $real_pay = $vip_details['real_income'];
        $month = date('m'); //获得当前月
        $day = date('d');   //获得当前日
        $bir_month = date('m', $vip['birthday']);
        $bir_day = date('d', $vip['birthday']);



        $staff_code = $vip_details['staff_code'];
        $staff_name = $vip_details['staff_name'];

        $store_code = $vip_details['store_code'];
        $store_name = $vip_details['store_name'];

        // $level = false;
        // $store = false;
        // $payment_l = false;
        // $whereWeek = false;
        // $whereDate = false;
        if ($vip) {
            $bir_off = $this->birthdayOnly($db, $vip);
            $vipConsumption = [
                'consumption_times' => $vip['consumption_times'] + 1, // 消费次数  
                'consumption_number' => $vip['consumption_number'] + $gods_order_number['number'], // 消费件数
                'total_consumption' => $vip['total_consumption'] + $real_pay, // 总消费额
                'final_purchases' => time() // 最后购物时间
            ];

            if (empty($vip['level_code'])) {
                $vip['level_code'] = '无级别';
            }
            if (empty($vip['store_code'])) {
                $vip['store_code'] = 0;
            }

            /**
             * 转介绍消费有礼
             */
            $int_vip = Db::table($db . '.vip_introducer')->where('rsid_code', $vip['code'])->find();
            if (!empty($int_vip)) {
                $introducer_vip = Db::table($this->db . '.vip_viplist')->where('code', $int_vip['lnt_code'])->find();
                $int_write_off = $this->introducer_sell($db, $int_vip);
            }

            /**
             * 消费有礼
             */
            $write_off = $this->consumption_gift($db, $vip, $vip_details['payment']);

            $memberLevel = $this->levelVip($db, $vip, $real_pay);  //会员晋升条件

            //修改的数据
            $coupon_record_jl = [
                'status' => 1,
                'remarking' => '消费使用',
                'edit_time' => time(),
                'o_staff' => $staff_code,
                'o_staff_name' => $staff_name,
                'o_store' => $store_code,
                'o_store_name' => $store_name
            ];
        } else {
            $bir_off = [];
            $memberLevel = [];
        }

        $status_a = Db::table('company.vip_business')->where('code', $db)->find();
        $ew = new ErpWhere($db, '');
        // 启动事务
        Db::startTrans();
        try {
            // 修改订单状态为正常
            if ($status_a['x_status'] == 1) {
                Db::table($db . '.vip_goods_order')->where('code', $gods_order_code)->update(['status' => 0]);
                Db::table($db . '.vip_details')->where('order_code', $gods_order_code)->update(['status' => 0]);
                if (isset($vipConsumption)) {
                    Db::table($db . '.vip_viplist')->where('code', $vip['code'])->update($vipConsumption);
                }
            } else {
                Db::table($db . '.vip_details')->where('order_code', $gods_order_code)->update(['status' => 0]);
            }

            if (!empty($vip)) {
                // 会员晋升
                if (!empty($memberLevel)) {
                    $promote_insert = [
                        'vip_code' => $vip['code'],
                        'vip_name' => $vip['username'],
                        'before_level' => $vip['level_code'],
                        'after_level' => $memberLevel['code'],
                        'reason' => '自动晋升',
                        'create_time' => time()
                    ];
                    Db::table($db . '.vip_promote')->insert($promote_insert);
                    Db::table($db . '.vip_viplist')->where('code', $vip['code'])->update(['level_code' => $memberLevel['code']]);
                }

                $Totalpoints = $gods_order_number['give_integral'];
                if (!empty($Totalpoints)) {
                    $gz_integral_jilu = [
                        'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                        'vip_code' => $vip['code'],
                        'vip_name' => $vip['username'],
                        'integral' => $Totalpoints,
                        'road' => '积分规则赠送',
                        'staff_code' => $staff_code,
                        'staff_name' => $staff_name,
                        'store_code' => $store_code,
                        'create_time' => time(),
                    ];
                    Db::table($db . '.vip_flow_integral')->insert($gz_integral_jilu);
                    Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('residual_integral', $Totalpoints); // 用户的剩余总积分加
                    Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('total_integral', $Totalpoints); // 用户的总积分加
                    $ew->integral_promotes($Totalpoints, $vip);
                }
                unset($Totalpoints);

                /**
                 * 生日专享
                 */
                if (!empty($bir_off) && $month == $bir_month && $day == $bir_day) {
                    if (!empty($bir_off['stored_code'])) { // 赠送储值
                        $stored_code_type = Db::table($db . '.vip_agive_share')->where('code', $bir_off['stored_code'])->find();
                        if ($stored_code_type['reward_type'] == 0) {
                            $m = number_format($real_pay * ($stored_code_type['money_ratio'] / 100), 2); //向下取整 储值金额
                        } else {
                            $m = $stored_code_type['stored_value'];
                        }
                        $value_jilu = [
                            'documents' => 'CZLS' . str_replace('.', '', microtime(1)),
                            'vip_code' => $vip['code'],
                            'vip_name' => $vip['username'],
                            'stored_value' => $m,
                            'road' => '生日专享赠送',
                            'staff_code' => $staff_code,
                            'staff_name' => $staff_name,
                            'store_code' => $store_code,
                            'create_time' => time(),
                        ];
                        Db::table($db . '.vip_stored_value')->insert($value_jilu);
                        Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('residual_value', $m); // 剩余储值
                        Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('total_value', $m); // 总储值
                        $ew->value_promotes($m, $vip);
                    }
                    if (!empty($bir_off['coupon_code'])) { // 赠送卡劵
                        if (!empty($bir_off['coupon_code'])) {
                            $poupon = Db::table($db . '.vip_agive_coupon')->where('code', $bir_off['coupon_code'])->select();
                            if (!empty($poupon)) {
                                foreach ($poupon as $pon) {
                                    for ($i = 0; $i < $pon['coupon_number']; $i++) {
                                        $ew->couponAdd($pon['card_type'], $pon['coupon_code'], $vip['code'], '生日专享赠送');
                                    }
                                }
                            }
                        }
                        unset($poupon, $value_jilu, $m);
                    }
                }

                /**
                 * 转介绍消费有礼
                 */
                if (!empty($int_write_off)) {
                    if ($int_write_off['intelligence_type'] == 0 && $vip['consumption_times'] < 1) {  // 1 是首次  2 每次
                        //赠送积分 
                        if (!empty($int_write_off['integral'])) {
                            $integral_jilu_introducer = [
                                'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                                'vip_code' => $introducer_vip['code'],
                                'vip_name' => $introducer_vip['username'],
                                'integral' => $int_write_off['integral'],
                                'road' => '转介绍消费赠送',
                                'create_time' => time(),
                            ];
                            Db::table($db . '.vip_flow_integral')->insert($integral_jilu_introducer);
                            Db::table($db . '.vip_viplist')->where('code', $introducer_vip['code'])->setInc('residual_integral', $int_write_off['integral']);
                            Db::table($db . '.vip_viplist')->where('code', $introducer_vip['code'])->setInc('total_integral', $int_write_off['integral']);
                            unset($integral_jilu_introducer);
                            $ew->integral_promotes($int_write_off['integral'], $introducer_vip);
                        }
                        // 赠送储值
                        if (!empty($int_write_off['stored_code'])) {
                            $introducer_stored_type = Db::table($db . '.vip_agive_share')->where('code', $int_write_off['stored_code'])->find();
                            if ($introducer_stored_type['reward_type'] == 0) {
                                $int_money_v = $real_pay * ($introducer_stored_type['money_ratio'] / 100); //保留两位小数
                                $int_money = number_format($int_money_v, 2);
                            } else {
                                $int_money = $introducer_stored_type['stored_value'];
                            }
                            $int_value_jilu = [
                                'documents' => 'CZLS' . str_replace('.', '', microtime(1)),
                                'vip_code' => $introducer_vip['code'],
                                'vip_name' => $introducer_vip['username'],
                                'stored_value' => $int_money,
                                'road' => '转介绍消费返利',
                                'staff_code' => $staff_code,
                                'staff_name' => $staff_name,
                                'store_code' => $store_code,
                                'create_time' => time(),
                            ];
                            Db::table($db . '.vip_stored_value')->insert($int_value_jilu);
                            Db::table($db . '.vip_viplist')->where('code', $introducer_vip['code'])->setInc('residual_value', $int_money); // 剩余储值
                            Db::table($db . '.vip_viplist')->where('code', $introducer_vip['code'])->setInc('total_value', $int_money); // 总储值
                            $ew->value_promotes($int_money, $introducer_vip);
                            unset($int_money);
                        }

                        // 赠送卡劵
                        if (!empty($int_write_off['coupon_code'])) {
                            $poupon_int = Db::table($db . '.vip_agive_coupon')->where('code', $int_write_off['coupon_code'])->select();
                            if (!empty($poupon_int)) {
                                foreach ($poupon_int as $val) {
                                    for ($i = 0; $i < $val['coupon_number']; $i++) {
                                        $ew->couponAdd($val['card_type'], $val['coupon_code'], $introducer_vip['code'], '转介绍消费赠送');
                                    }
                                }
                            }
                            unset($poupon_int);
                        }
                    } else if ($int_write_off['intelligence_type'] == 1) {
                        //赠送积分 
                        if (!empty($int_write_off['integral'])) {
                            $integral_jilu_introducer = [
                                'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                                'vip_code' => $introducer_vip['code'],
                                'vip_name' => $introducer_vip['username'],
                                'integral' => $int_write_off['integral'],
                                'road' => '转介绍消费赠送',
                                'create_time' => time(),
                            ];
                            Db::table($db . '.vip_flow_integral')->insert($integral_jilu_introducer);
                            Db::table($db . '.vip_viplist')->where('code', $introducer_vip['code'])->setInc('residual_integral', $int_write_off['integral']); // 用户的剩余总积分加
                            Db::table($db . '.vip_viplist')->where('code', $introducer_vip['code'])->setInc('total_integral', $int_write_off['integral']); // 用户的总积分加
                            unset($integral_jilu_introducer);
                            $ew->integral_promotes($int_write_off['integral'], $introducer_vip);
                        }
                        // 赠送储值
                        if (!empty($int_write_off['stored_code'])) {
                            $introducer_stored_type = Db::table($db . '.vip_agive_share')->where('code', $int_write_off['stored_code'])->find();
                            if ($introducer_stored_type['reward_type'] == 0) {
                                $int_money_v = $real_pay * ($introducer_stored_type['money_ratio'] / 100); //保留两位小数
                                $int_money = number_format($int_money_v, 2);
                            } else {
                                $int_money = $introducer_stored_type['stored_value'];
                            }
                            $int_value_jilu = [
                                'documents' => 'CZLS' . str_replace('.', '', microtime(1)),
                                'vip_code' => $introducer_vip['code'],
                                'vip_name' => $introducer_vip['username'],
                                'stored_value' => $int_money,
                                'road' => '转介绍消费返利',
                                'staff_code' => $staff_code,
                                'staff_name' => $staff_name,
                                'store_code' => $store_code,
                                'create_time' => time(),
                            ];
                            Db::table($db . '.vip_stored_value')->insert($int_value_jilu);
                            Db::table($db . '.vip_viplist')->where('code', $introducer_vip['code'])->setInc('residual_value', $int_money); // 剩余储值
                            Db::table($db . '.vip_viplist')->where('code', $introducer_vip['code'])->setInc('total_value', $int_money); // 总储值
                            $ew->value_promotes($int_money, $introducer_vip);
                            unset($int_money);
                        }

                        // 赠送卡劵
                        if (!empty($int_write_off['coupon_code'])) {
                            $poupon_int = Db::table($db . '.vip_agive_coupon')->where('code', $int_write_off['coupon_code'])->select();
                            if (!empty($poupon_int)) {
                                foreach ($poupon_int as $val) {
                                    for ($i = 0; $i < $val['coupon_number']; $i++) {
                                        $ew->couponAdd($val['card_type'], $val['coupon_code'], $introducer_vip['code'], '转介绍消费赠送');
                                    }
                                }
                            }
                            unset($poupon_int);
                        }
                    }
                }

                /**
                 * 消费有礼
                 */
                if (!empty($write_off)) {
                    if (!empty($write_off['integral_code'])) { //赠送积分
                        $agive_integral_type = Db::table($db . '.vip_agive_integral')->where('code', $write_off['integral_code'])->find();
                        $agive_integral = Db::table($db . '.vip_agive_integral')->where('code', $write_off['integral_code'])->select();
                        $newArr = [];
                        if (!empty($agive_integral)) {
                            foreach ($agive_integral as $v) {
                                if ($v['reward_money'] <= $real_pay) {
                                    array_push($newArr, $v['reward_money']);
                                }
                            }
                            if (count($newArr) > 0) {
                                $max_integral = Db::table($db . '.vip_agive_integral')->where('code', $write_off['integral_code'])->where('reward_money', max($newArr))->find();
                                if ($agive_integral_type['reward_type'] == 0) { // 0单次满赠奖励   1 每满赠奖励
                                    $fs = $max_integral['integral'];
                                } else {
                                    $Multiple = floor($real_pay / max($newArr)); // 满了几次
                                    $fs = $max_integral['integral'] * $Multiple;
                                }
                                $integral_jilu = [
                                    'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                                    'vip_code' => $vip['code'],
                                    'vip_name' => $vip['username'],
                                    'integral' => $fs,
                                    'road' => '消费有礼赠送',
                                    'staff_code' => $staff_code,
                                    'staff_name' => $staff_name,
                                    'store_code' => $store_code,
                                    'create_time' => time(),
                                ];
                                Db::table($db . '.vip_flow_integral')->insert($integral_jilu);
                                Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('residual_integral', $fs); // 用户的剩余总积分加
                                Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('total_integral', $fs); // 用户的总积分加
                                $ew->integral_promotes($fs, $vip);
                            }
                            unset($agive_integral_type, $agive_integral, $newArr, $Multiple, $max_integral, $integral_jilu, $fs);
                        }
                    }
                    if (!empty($write_off['stored_code'])) { // 赠送储值
                        $stored_code_type = Db::table($db . '.vip_agive_share')->where('code', $write_off['stored_code'])->find();
                        $stored_code = Db::table($db . '.vip_agive_share')->where('code', $write_off['stored_code'])->select();
                        $newZarr = [];
                        if (!empty($stored_code)) {
                            foreach ($stored_code as $v) {
                                if ($v['reward_money'] <= $real_pay) {
                                    array_push($newZarr, $v['reward_money']);
                                }
                            }
                            if (count($newZarr) > 0) {
                                $max_stored = Db::table($db . '.vip_agive_share')->where('code', $write_off['stored_code'])->where('reward_money', max($newZarr))->find();
                                if ($stored_code_type['reward_type'] == 0) {
                                    $m = number_format($real_pay * ($max_stored['money_ratio'] / 100), 2); //向下取整 储值金额
                                    if ($m > $max_stored['money_limit']) {
                                        $m = $max_stored['money_limit'];
                                    }
                                } else {
                                    $m = $max_stored['stored_value'];
                                }
                                Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('residual_value', $m); // 剩余储值
                                Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('total_value', $m); // 总储值
                                $value_jilu = [
                                    'documents' => 'CZLS' . str_replace('.', '', microtime(1)),
                                    'vip_code' => $vip['code'],
                                    'vip_name' => $vip['username'],
                                    'stored_value' => $m,
                                    'road' => '消费有礼赠送',
                                    'staff_code' => $staff_code,
                                    'staff_name' => $staff_name,
                                    'store_code' => $store_code,
                                    'create_time' => time(),
                                ];
                                Db::table($db . '.vip_stored_value')->insert($value_jilu);
                                $ew->value_promotes($m, $vip);
                            }
                            unset($newZarr, $stored_code, $stored_code_type, $max_stored, $m);
                        }
                    }
                    if (!empty($write_off['coupon_code'])) { // 赠送卡劵
                        $coupon_type = Db::table($db . '.vip_agive_coupon')->where('code', $write_off['coupon_code'])->find();
                        $coupon = Db::table($db . '.vip_agive_coupon')->where('code', $write_off['coupon_code'])->select();
                        $newCarr = [];
                        if (!empty($coupon)) {
                            foreach ($coupon as $v) {
                                if ($v['reward_money'] <= $real_pay) {
                                    array_push($newCarr, $v['reward_money']);
                                }
                            }
                            if (count($newCarr) > 0) {
                                $max_coupon = Db::table($db . '.vip_agive_coupon')->where('code', $write_off['coupon_code'])->where('reward_money', max($newCarr))->find();
                                if ($max_coupon['reward_type'] == 0) {
                                    $c = $max_coupon['coupon_number'];
                                } else {
                                    $Cultiple = floor($real_pay / max($newCarr)); // 满了几次
                                    $c = $max_coupon['coupon_number'] * $Cultiple;
                                }
                                for ($i = 0; $i < $c; $i++) {
                                    $ew->couponAdd($max_coupon['card_type'], $max_coupon['coupon_code'], $vip['code'], '消费有礼赠送');
                                }
                            }
                            unset($coupon_type, $coupon, $newCarr, $max_coupon, $Cultiple);
                        }
                    }
                }
            }
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
            if (!empty($vip_details['coupon_record_code']) && !empty($vip)) {
                $res_record = Db::table($db . '.vip_coupon_record')->where('code', $vip_details['coupon_record_code'])->update($coupon_record_jl);
                $title = '您好，【' . $vip['username'] . '】您成功使用了一张卡券！';
                if ($res_record) {
                    $coupon_record = Db::table($db . '.vip_coupon_record')->where('code', $vip_details['coupon_record_code'])->find();
                    //判断类型并返回数据
                    if ($coupon_record['card_type'] == 0) {
                        $one = Db::table($db . '.vip_cash_coupon')->where('code', $coupon_record['card_code'])->find(); // 优惠券
                        $one['coupon_name'] = $one['card_money'] . '元，优惠劵';
                    } else if ($coupon_record['card_type'] == 1) {
                        $one = Db::table($db . '.vip_coupon')->where('code', $coupon_record['card_code'])->find(); // 折扣券
                        $one['coupon_name'] = $one['card_discount'] . '折，折扣劵';
                    } else if ($coupon_record['card_type'] == 2) {
                        $one = Db::table($db . '.vip_coupon_gift')->where('code', $coupon_record['card_code'])->find(); // 礼品券
                        $one['coupon_name'] = $one['gift_code'] . '礼品劵';
                    }
                    //备注替换
                    $one['remark'] = '微信消费使用';
                    //核销状态
                    $one['coupon_type'] = 2;
                }
                $ew->pushCoupon($vip['openid'], $title, $one, $staff_code);
            }
            unset($vip, $db, $one, $res_record, $coupon_record);
            webApi(200, '成功');
        } else {
            webApi(400, '失败');
        }
    }

    /**
     * 通过订单号查询商品信息和金额
     * @param string company  企业号
     * @param string order_code 记录code
     */
    public function queryCommodity()
    {
        [$db, $order_code] =  [input('company'), input('order_code')];

        $goods = Db::table($db . '.vip_goods_order_info')
            ->alias('v')
            ->leftJoin($db . '.vip_goods g', 'v.goods_code = g.bar_code')
            ->field('g.name, g.price, g.color, g.sizes')
            ->where('v.order_code', $order_code)
            ->select();

        $order = Db::table($db . '.vip_details')
            ->alias('v')
            ->leftJoin($db . '.vip_viplist vip', 'vip.code = v.vip_code')
            ->field('v.order_code, v.real_income as money, v.status, v.store_code, v.staff_code, vip.openid, v.vip_code')
            ->where('v.order_code', $order_code)
            ->find();

        $data = [
            'goods' => $goods,
            'order' => $order
        ];
        webApi(200, 'ok', $data);
    }

    /**
     * 转介绍消费有礼
     * @param string $db 企业号
     * @param array $int_vip 介绍人
     */
    private function introducer_sell($db, $int_vip)
    {
        $introducer_vip = Db::table($db . '.vip_viplist')->where('code', $int_vip['lnt_code'])->find();
        if (empty($introducer_vip['level_code'])) {
            $introducer_vip['level_code'] = '无级别';
        }
        // 转介绍消费有礼
        $int_write_off_s = Db::table($db . '.vip_activity_courtesy')
            ->where('activity_type', '转介绍消费有礼')
            ->where('start_time', '<=', time())
            ->where('end_time', '>=', time())
            ->where('level_all', 'like', '%' . $introducer_vip['level_code'] . '%')
            ->where('status', 0)
            ->select();
        if (!empty($int_write_off_s)) {
            $int_write_off = $int_write_off_s[0];
        } else {
            $int_write_off = [];
        }

        return $int_write_off;
    }

    /**
     * 消费有礼
     * @param string $db 企业号
     * @param array $vip 会员信息
     * @param string $payment 支付方式
     */
    private function consumption_gift($db, $vip, $payment)
    {
        $write_off_W = Db::table($db . '.vip_activity_courtesy')
            ->where('activity_type', '消费有礼')
            ->where('start_time', '<=', time())
            ->where('end_time', '>=', time())
            ->where('level_all', 'like', '%' . $vip['level_code'] . '%')
            ->where('week', 'like', '%' . date('w') . '%')
            ->where('payment_all', 'like', '%' . $payment . '%')
            ->where('status', 0)
            ->select();
        if (!empty($write_off_W)) {
            foreach ($write_off_W as $k => $v) {
                $write_off_W[$k]['store_w'] = false;
                $write_off_W[$k]['time_w'] = false;
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
                $a_hsi = strtotime(date('Y-m-d') . $v['a_hsi']);
                $b_hsi = strtotime(date('Y-m-d') . $v['b_hsi']);
                if (time() > $a_hsi && time() < $b_hsi) {  //判断是否符合几点到几点
                    $write_off_W[$k]['time_w'] = true;
                }
            }

            foreach ($write_off_W as $k => $v) {
                if ($v['store_w'] == false) {
                    unset($write_off_W[$k]);
                }
            }
            foreach ($write_off_W as $k => $v) {
                if ($v['time_w'] == false) {
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
        } else {
            $write_off = [];
        }

        return $write_off;
    }

    /**
     * 判断会员生日专享
     * @param array $vip 会员信息
     */
    private function birthdayOnly($db, $vip)
    {
        if (empty($vip['level_code'])) {
            $vip['level_code'] = '无级别';
        }
        $write_off_W = Db::table($db . '.vip_activity_courtesy')
            ->where('activity_type', '生日专享')
            ->where('start_time', '<=', time())
            ->where('end_time', '>=', time())
            ->where('level_all', 'like', '%' . $vip['level_code'] . '%')
            ->where('status', 0)
            ->select();
        if (!empty($write_off_W)) {
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
        } else {
            $write_off = [];
        }
        return $write_off;
    }

    /**
     * 会员等级晋升
     */
    private function levelVip($db, $vipMember, $real_pay)
    {
        $memLv = Db::table($db . '.vip_viplevel')->field('uid')->where('code', $vipMember['level_code'])->find();
        if (!empty($memLv)) {
            $nowLv = $memLv['uid'];
        } else {
            $nowLv = -1;
        }
        // 级别晋升 查询该会员当前的级别折扣力度 查询比他力度更大的第一条 比较所有的晋升条件 如果有一条符合 晋升
        $promotes = Db::table($db . '.vip_vippromote')
            ->alias('a')
            ->leftJoin($db . '.vip_viplevel l', 'l.code = a.levelname')
            ->where('l.uid', '>', $nowLv)
            ->select();

        if (!empty($promotes)) {
            foreach ($promotes as $k => $v) {
                $promotes[$k]['js'] = false;
            }
            foreach ($promotes as $k => $v) {
                // 判断首次消费
                if (intval($vipMember['consumption_times']) < 1 && $real_pay  >= $v['first_amount']) {
                    $promotes[$k]['js'] = true;
                }

                // 判断单次消费
                if ($real_pay  >= $v['single_amount']) {
                    $promotes[$k]['js'] = true;
                }

                // 判断累计总积分
                $vip_viprights_integral = Db::table($db . '.vip_viprights_integral')->where('single_selection', 1)->find();
                if (!empty($vip_viprights_integral)) {
                    $give_integral = floor(($real_pay /  $vip_viprights_integral['element']) * $vip_viprights_integral['integral']);
                    $getIntegral = Db::table($db . '.vip_flow_integral')->field('sum(integral) as integral')->where('vip_code', $vipMember['code'])->where('create_time', '>=', time() - ($v['total_integral_time'] * 86400))->find();
                    if ($getIntegral['integral'] + $give_integral  >= $v['total_integral']) {
                        $promotes[$k]['js'] = true;
                    }
                }
                unset($rec, $ord, $getIntegral, $vip_viprights_integral);

                // 判断消费总金额
                $mpay = Db::table($db . '.vip_goods_order')->field('sum(real_pay) as pay_sum')->where('vip_code', $vipMember['code'])->where('status', 0)->where('create_time', '>=', time() - ($v['total_amount_time'] * 86400))->select();
                if ((floatval($mpay[0]['pay_sum']) + $real_pay) >= $v['total_amount']) {
                    $promotes[$k]['js'] = true;
                }
                unset($mpay);
            }
            foreach ($promotes as $k => $v) {
                if ($v['js'] == false) {
                    unset($promotes[$k]);
                }
            }
            if (!empty($promotes)) {
                foreach ($promotes as $k => $v) {
                    $promotes[$k]['l'] =
                        !empty(Db::table($db . '.vip_viplevel')->field('uid')->where('code', $v['levelname'])->find())
                        ?
                        Db::table($db . '.vip_viplevel')->field('uid')->where('code', $v['levelname'])->find()['uid']
                        : '';
                }
                rsort($promotes);
                $memberLevel = Db::table($db . '.vip_viplevel')->where('code',  $promotes[0]['levelname'])->find();
            }
        }
        unset($promotes);
        if (isset($memberLevel)) {
            $Level = $memberLevel;
        } else {
            $Level = [];
        }
        return $Level;
    }

    /**
     * 扫码后员工端成功接口
     * @param string $db 企业号
     * @param string order_code 记录code
     */
    public function successful()
    {
        $order = Db::table(input('company') . '.vip_details')
            ->where('order_code', input('order_code'))
            ->find()['status'];
        if ($order == 0) {
            webApi(200, '支付成功');
        } else {
            webApi(400, '未支付');
        }
    }

    /**
     * 查询会员储值，扣除储值
     * @param string input('company') 企业号
     * @param string order_code  记录code
     */
    public function deductionMoney()
    {
        $db = input('company');
        if (empty(input('order_code'))) {
            webApi(400, '缺少参数');
        }
        $order = Db::table($db . '.vip_details')->field('vip_code, real_income, store_code, store_name, staff_code, staff_name')->where('order_code', input('order_code'))->find();
        if (!empty($order)) {
            $vip = Db::table($db . '.vip_viplist')->where('code', $order['vip_code'])->find();
            if ($order['real_income'] > $vip['residual_value']) {
                webApi(400, '会员钱包余额不足');
            }
            $value = Db::table($db . '.vip_viplist')->where('code', $order['vip_code'])->setDec('residual_value', $order['real_income']);
            if ($value) {
                $v_value_jilu = [
                    'documents' => 'CZLS' . str_replace('.', '', microtime(1)),
                    'vip_code' => $vip['code'],
                    'vip_name' => $vip['username'],
                    'stored_value' => $order['real_income'],
                    'road' => '钱包消费扣除',
                    'staff_code' => $order['staff_code'],
                    'staff_name' => $order['staff_name'],
                    'store_code' => $order['store_code'],
                    'value_status' => 1,
                    'create_time' => time(),
                ];
                $jl = Db::table($db . '.vip_stored_value')->insert($v_value_jilu);
                if ($jl) {
                    $status_a = Db::table('company.vip_business')->where('code', $db)->find();
                    if ($status_a['x_status'] == 1) {
                        Db::table($db . '.vip_details')->where('order_code', input('order_code'))->setField('payment', 4);
                        Db::table($db . '.vip_goods_order')->where('code', input('order_code'))->setField('payment', 4);
                    } else {
                        Db::table($db . '.vip_details')->where('order_code', input('order_code'))->setField('payment', 4);
                    }
                    webApi(200, '成功');
                }
            } else {
                webApi(200, '失败');
            }
            unset($order, $vip, $value, $v_value_jilu, $jl, $status_a);
        } else {
            webApi(400, '没有查询到该订单');
        }
    }
}
