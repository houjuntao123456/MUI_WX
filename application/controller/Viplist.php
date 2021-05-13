<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 * Author lhp
 * Date 2020/04/26    
 * Description  会员中心
 */
class Viplist extends Common
{
    /**
     * 积分流水
     */
    public function integralFlow()
    {
        $code = input('code');

        if (empty($code)) {
            webApi(400, '缺少参数');
        }

        $data = Db::table($this->db.'.vip_flow_integral')->where('vip_code', $code)->where('integral', '<>', 0)->page(input('page'), input('limit'))->order('create_time','desc')->select();
        $count = Db::table($this->db.'.vip_flow_integral')->where('integral', '<>', 0)->where('vip_code', $code)->count();
        if (!empty($data)) {
            foreach($data as $k=>$v) {
                $flow_store = Db::table($this->db.'.vip_store')->where('code', $v['store_code'])->find()['name'];
                $data[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                if ($v['staff_code'] == 'boss') {
                    $data[$k]['store_name'] = '无门店';
                } else {
                    if (empty($flow_store)) {
                        $data[$k]['store_name'] = '';
                    } else {
                        $data[$k]['store_name'] = $flow_store;
                    }
                }
            }
        }
        unset($flow_store, $code);
        $data = [
            'data' => $data,
            'count' => $count
        ];

        webApi(200,'ok',$data);
    }

    /**
     * 储值流水表
     */
    public function storedValueFlow()
    {
        $code = input('code');
        if (empty($code)) {
            webApi(400, '缺少参数');
        }
        $data = Db::table($this->db.'.vip_stored_value')->where('vip_code', $code)->where('stored_value', '<>', 0)->page(input('page'), input('limit'))->order('create_time','desc')->select();
        $count = Db::table($this->db.'.vip_stored_value')->where('vip_code', $code)->where('stored_value', '<>', 0)->count();
        if (!empty($data)) {
            foreach($data as $k=>$v) {
                $flow_store = Db::table($this->db.'.vip_store')->where('code', $v['store_code'])->find()['name'];
                $data[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                if ($v['value_status'] == 0) {
                    $data[$k]['stored_value'] = $v['stored_value']."( 充:".$v['actual_receipt']." 送:".$v['give_amount']." )";
                } else {
                    $data[$k]['stored_value'] = "-".$v['stored_value'];
                }
                if ($v['staff_code'] == 'boss') {
                    $data[$k]['store_name'] = '无门店';
                } else {
                    if (empty($flow_store)) {
                        $data[$k]['store_name'] = '';
                    } else {
                        $data[$k]['store_name'] = $flow_store;
                    }
                }
            }
        }
        unset($flow_store, $code);
        $data = [
            'data' => $data,
            'count' => $count
        ];
        webApi(200,'ok',$data);
    }

    /**
     * 需要积分兑换的卡劵
     */
    public function pointExchange()
    {
        $type = input('type');
        
        if ($type == 0) {
            $one = Db::table($this->db . '.vip_cash_coupon')->where('end_time', '>=', time())->where('store_code', "")->where('xz', 2)->where('status', 0)->where('integral', '<>', 0)->select(); 
            $v = Db::table($this->db . '.vip_cash_coupon')->where('xz', 1)->where('status', 0)->where('integral', '<>', 0)->select(); //时间限制
            if (!empty($one)) {
                foreach ($v as $kay=>$val) {
                    array_push($one, $val);
                }
            } else {
                $one = $v;
            }
        } else if ($type == 2) {
            $one = Db::table($this->db . '.vip_coupon')->where('end_time', '>=', time())->where('store_code', "")->where('status', 0)->where('xz', 2)->where('integral', '<>', 0)->select();
            $v = Db::table($this->db . '.vip_coupon')->where('status', 0)->where('xz', 1)->where('integral', '<>', 0)->select();
            if (!empty($one)) {
                foreach ($v as $kay=>$val) {
                    array_push($one, $val);
                }
            } else {
                $one = $v;
            }
        } else if ($type == 1) {
            $one = Db::table($this->db . '.vip_coupon_gift')->where('end_time', '>=', time())->where('store_code', "")->where('xz', 2)->where('status', 0)->where('integral', '<>', 0)->select();
            $v = Db::table($this->db . '.vip_coupon_gift')->where('xz', 1)->where('status', 0)->where('integral', '<>', 0)->select();
            if (!empty($one)) {
                foreach ($v as $kay=>$val) {
                    array_push($one, $val);
                }
            } else {
                $one = $v;
            }
        }
        foreach ($one as $k => $v) {
            if ($v['xz'] == 2) {
                $one[$k]['start_time'] = date('Y-m-d', $v['start_time']);
                $one[$k]['end_time'] = date('Y-m-d', $v['end_time']);
            } else {
                $one[$k]['start_time'] = '';
                $one[$k]['end_time'] = '';
            }
        }
        
        webApi(200,'ok',$one);
    }

    /**
     * 查询会员积分
     */
    public function listIntegral()
    {
        if (empty(input('code'))) {
            webApi(400, '缺少code参数');
        }
        $data = Db::table($this->db.'.vip_viplist')->field('residual_integral')->where('code', input('code'))->find();
        webApi(200, 'ok', $data);
    }

    /**
     * 兑换后扣除积分，增加卡劵
     */
    public function offExchange()
    {
        $vip_code = input('vip_code');
        $coupon_code = input('coupon_code');
        $type = input('type');
        $vip = Db::table($this->db.'.vip_viplist')->where('code', $vip_code)->find();
        $cou_count = Db::table($this->db.'.vip_coupon_record')->where('vip_code', $vip_code)->where('card_code', $coupon_code)->count();
        //定义卡券金额, 折扣, 礼品
        $two = "";
        $three = "";
        //判断类型并返回数据
        if ($type == 0) {
            $one = Db::table($this->db . '.vip_cash_coupon')->where('code', $coupon_code)->find(); // 优惠券
            $two = $one['card_money'];
            $three = $one['money'];
            $one['coupon_name'] = $one['card_money'] . '元，优惠劵';
            $v_type = '优惠劵';
        } else if ($type == 1) {
            $one = Db::table($this->db . '.vip_coupon')->where('code', $coupon_code)->find(); 
            $two = $one['card_discount'];
            $three = $one['money'];
            $one['coupon_name'] = $one['card_discount'] . '折，折扣劵';
            $v_type = '折扣劵';
            
        } else if ($type == 2) {
            $one = Db::table($this->db . '.vip_coupon_gift')->where('code', $coupon_code)->find(); 
            $two = $one['gift_code'];
            $three = 0;
            $one['coupon_name'] = $one['gift_code'] . '礼品劵';
            $v_type = '礼品劵';
        }
        if ($one['xz'] == 1) {
            $start_time = time() + (86400 * $one['takeEffect']);
            $end_time = $start_time + (86400 * $one['effective']);
            
        } else {
            $start_time = $one['start_time'];
            $end_time = $one['end_time'];
        }
        if ($one['coupon_number'] <= 0) {
            webApi(400, '该卡券剩余数量以为 0 !');
        }

        if ($one['receive_limit'] < $cou_count) {
            webApi(400, '该卡劵已达到兑换上限');
        }
        
        if ($vip['residual_integral'] < $one['integral']) {
            webApi(400,'积分不足');
        }

        $data = [
            'code' => 'ZSKQ' . str_replace('.', '', microtime(1)),
            'vip_code' => $vip_code,
            'card_type' => $type,
            'card_name' => $one['name'],
            'coupon_type' => $one['coupon_type'],
            'card_many' => $two,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'card_code' => $coupon_code,
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
            'remark' => '使用积分兑换卡劵'
        ];

        //积分流水记录
        $flow = [
            'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
            'vip_code' => $vip['code'],
            'vip_name' => $vip['username'],
            'integral' => '-'.$one['integral'],
            'road' => '会员积分兑换卡券',
            'remarks' => '会员积分兑换卡券',
            'create_time' => time(),
            'integral_status' => 1
        ];
        //积分兑换记录
        $exchange = [
            'code' => 'DHLS' . str_replace('.', '', microtime(1)),
            'vip_code' => $vip['code'],
            'vip_name' => $vip['username'],
            'integral' => $one['integral'],
            'coupon_code' => $data['card_code'],
            'coupon_name' => $data['card_name'],
            'remarks' => '会员积分兑换卡券',
            'create_time' => time()
        ];

        $ww = [
            'code' => $coupon_code,
            'card_type' => $v_type,
            'coupon_code' => $one['code'],
            'coupon_name' => $one['name']
        ];

         //执行赠送
        $title = '您好，【' . $vip['username'] . '】您收到一张卡券，请到会员中心查看或使用！';
        //清除变量    
        // 启动事务
        $es = new ErpWhere($this->db, "");
        Db::startTrans();
        try {
            $res = Db::table($this->db . '.vip_coupon_record')->insert($data);
            //判断返回数据
            if ($res) {
                Db::table($this->db.'.vip_viplist')->where('code', $vip_code)->setDec('residual_integral', $one['integral']); // 领取成功扣除会员的剩余积分
                Db::table($this->db.'.vip_viplist')->where('code', $vip_code)->setInc('exchange_number');  // 兑换次数加一
                Db::table($this->db.'.vip_exchange_integral')->insert($exchange); //积分兑换表
                Db::table($this->db.'.vip_flow_integral')->insert($flow);         //积分流水表
                Db::table($this->db . '.vip_agive_coupon')->insert($ww);
                if ($type == 0) {
                    Db::table($this->db . '.vip_cash_coupon')->where('code', $coupon_code)->setInc('receive'); // 优惠券
                } else if ($type == 1) {
                    Db::table($this->db . '.vip_coupon')->where('code', $coupon_code)->setInc('receive'); // 折扣券
                } else if ($type == 2) {
                    Db::table($this->db . '.vip_coupon_gift')->where('code', $coupon_code)->setInc('receive'); // 礼品券
                }
                if (empty($vip['openid'])) {
                    $resa = true;
                } else {
                    $es->pushCoupon($vip['openid'], $title, $one);
                    $resa = true;
                }
                unset($one, $exchange, $flow, $ww, $coupon_code, $vip_code, $data);
            } else {
                $resa = false;
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $resa = false;
        }

        if ($resa) {
            webApi(200, '兑换成功!');
        } else {
            webApi(400, '兑换失败!');
        }

    }

    public function obtainStaff()
    {
        $company = input('company');
        $staff = input('staff_code');
        if (empty($company)) {
            webApi(0, '缺少company参数');
        }
        $data = Db::table($company.'.vip_staff')->field('openid')->where('code', $staff)->find();

        webApi(0, 'ok', $data);
    }


    /**
     * 根据门店code查询门店信息
     */
    public function storeInformation()
    {
        $code = input('code');
        $company = input('company');

        if (empty($code)) {
            webApi(400, '缺少code参数');
        }

        if (empty($company)) {
            webApi(400, '缺少company参数');
        }

        $data = Db::table($company.'.vip_store')->where('code', $code)->find();
        if ($data) {
            $data['create_time'] = date('Y-m-d H:i:s', $data['create_time']);
        }

        webApi(200, 'ok', $data);
    }

    /**
     * 根据openid查询会员信息
     */
    public function vipInformation()
    {
        $code = input('code');
        $company = input('company');

        if (empty($code)) {
            webApi(400, '缺少openid参数');
        }

        if (empty($company)) {
            webApi(400, '缺少company参数');
        }

        $data = Db::table($company.'.vip_viplist')->where('openid', $code)->find();

        $level = Db::table($company.'.vip_viplevel')->where('code', $data['level_code'])->find();

        if ($data) {
            $month = date('m'); //获得当前月
            $day = date('d');   //获得当前日
            $bir_month = date('m', $data['birthday']); 
            $bir_day = date('d', $data['birthday']); 
            $dis = $this->birthdayOnly($company, $data);
            
            if (!empty($dis) && $month == $bir_month && $day == $bir_day) {
                $data['birthday_dis'] = number_format($dis['money'] / 10, 2);
            } else {
                $data['birthday_dis'] = -1;
            }

            if (!empty($level)) {
                $data['discount'] = number_format($level['discount'] / 10, 2);
            } else {
                $data['discount'] = 0;
            }  

            $data['residual_value'] = number_format($data['residual_value'], 2);
            $data['stored_value'] = number_format($data['discount'] , 2);
            $data['total_value'] = number_format($data['total_value'], 2);
            $value_limit = Db::table('company.vip_business')->where('code', $this->db)->find();
            if (!empty($value_limit)) {
                if ($value_limit['value_limit'] == 2) {
                    $data['residual_value'] = 0;
                }
            }
        }
        webApi(200, 'ok', $data);
    }

    /**
     * 判断会员生日专享
     */
    public function birthdayOnly($db, $vip)
    {
        if (empty($vip['level_code'])) {
            $vip['level_code'] = 0; 
        }
        $write_off_W = Db::table($db.'.vip_activity_courtesy')
                ->where('activity_type', '生日专享')
                ->where('start_time', '<=', time())
                ->where('end_time', '>=', time())
                ->where('level_all', 'like', '%'.$vip['level_code'].'%')
                ->where('status', 0)
                ->select();
        if (!empty($write_off_W)) {
            foreach ($write_off_W as $k=>$v) {
                $write_off_W[$k]['store_w'] = false;
            }

            foreach ($write_off_W as $k=>$v) {
                $fstore_all = explode(',',$v['store_all']); 
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

            foreach ($write_off_W as $k=>$v) {
                if ($v['store_w'] == false) {
                    unset($write_off_W[$k]);
                }
            }
            
            if (count($write_off_W) > 1) {
                foreach ($write_off_W as $k=>$v) {
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
     * 专属顾问
     */
    public function vipStaff()
    {
        $vip_staff = Db::table($this->db.'.vip_viplist')->field('consultant_code')->where('code', input('code'))->find();
        $data = Db::table($this->db.'.vip_staff')->where('code', $vip_staff['consultant_code'])->find();
        webApi(200, 'ok', $data);
    }

    /**
     * 根据openid查询会员卡劵
     */
    public function Coupons()
    {
        $openid = input('openid');
        $db = input('company') ;
        $ymd = date('Y-m-d'); //获取当前年月日
        $wdate = date('w'); //获取当前周
        if (empty($openid) || empty($db)) {
            webApi(400, 'openid或company不存在');
        }

        $vip = Db::table($db.'.vip_viplist')->field('code')->where('openid', $openid)->find();
        $data = Db::table($db . '.vip_coupon_record')
                ->where('vip_code', $vip['code'])
                ->where('status', 0)
                ->where('card_type', '<>', 2)
                ->where('money', '<=', input('coupon_money'))
                ->where('start_time', '<=', time())
                ->where('end_time', '>=', time())
                ->select();
        if ($data) {
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
                } else if ($whereDate == false) {
                    unset($data[$k]);
                }
            }
            sort($data);
        }
        webApi(200, 'ok', $data);
    }

    /**
     * 查询会员的消费记录   还需要如果使用优惠劵核销掉优惠劵   消费有礼   等级晋升
     */
    public function consumptionRecord()
    {
        $openid = input('openid');
        $db = input('company') ;
        $staff = input('staff_code') ;
        $store = input('store_code') ;
        $coupon_code = input('coupon_code'); // 使用的卡劵记录code
        $payment = input('payment') ;  // 支付方式 0 微信支付  4 会员钱包
        if (empty($openid) || empty($db)) {
            webApi(400, 'openid或company不存在');
        }
        $vip = Db::table($db.'.vip_viplist')->where('openid', $openid)->find();
        
        if ($payment == 4) {
            if ($vip['residual_value'] < input('real_pay')) {
                webApi(400, '会员钱包余额不足 , 余额 : '.$vip['residual_value']);
            }
        }

        $month = date('m'); //获得当前月
        $day = date('d');   //获得当前日
        $bir_month = date('m', $vip['birthday']); 
        $bir_day = date('d', $vip['birthday']);
        $bir_off = $this->birthdayOnly($db, $vip);
        
        if (empty(input('order_code'))) {
            $kscode = 'KSXF' . str_replace('.', '', microtime(1));
        } else {
            $is_set = Db::table($db . '.vip_details')->where('code', input('order_code'))->find();
            if ($is_set) {
                webApi(400, '失败,交易编号重复');
            } else {
                $kscode = input('order_code');
            }
        }

        if (empty($staff)) {
            $staff_code = '';
            $staff_name = '';
        } else {
            $staff_code = $staff;
            $vaname = Db::table($db.'.vip_staff')->where('code', $staff)->find();
            $staff_name = $vaname['name'] == null ? '' : $vaname['name'];
        }
        if (!empty($store)) {
            $store_code = $store == null ? '' : $store;
            $ssname = Db::table($db.'.vip_store')->where('code', $store_code)->find();
            $store_name = $ssname['name'] == null ? '' : $ssname['name'];
        } else {
            $store_code = '';
            $store_name = '';
        }
        $level = false;
        $store = false;
        $payment_l = false;
        $whereWeek = false;
        $whereDate = false;
        if ($vip) {
            $vip_viprights_integral_zs = Db::table($db . '.vip_viprights_integral')->where('single_selection', 1)->find();
            if (!empty($vip_viprights_integral_zs) && input('real_pay') >= $vip_viprights_integral_zs['element']) {
                if (!empty($bir_off) && $month == $bir_month && $day == $bir_day) {
                    if (!empty($bir_off['integral'])) {
                        $Totalpoints = floor(((input('real_pay') /  $vip_viprights_integral_zs['element']) * $vip_viprights_integral_zs['integral']) * $bir_off['integral']);
                    } else {
                        $Totalpoints = floor((input('real_pay') /  $vip_viprights_integral_zs['element']) * $vip_viprights_integral_zs['integral']);
                    }
                } else {
                    $Totalpoints = floor((input('real_pay') /  $vip_viprights_integral_zs['element']) * $vip_viprights_integral_zs['integral']);
                }
            } else {
                $Totalpoints = 0;
            }
            //修改的数据
            $datasell = [
                'code' => $kscode, // '销售单据号（销售单唯一标识）',
                'store_code' => $store_code, // '销售门店编号（销售门店的唯一标识）',
                'operate_name' => $staff_name, //  '操作员名字（收银员名字）',
                'operate_code' => $staff_code, // '员工code',
                'vip_name' => $vip['username'], // '会员名字',
                'vip_code' => $vip['code'], // '会员卡号',
                'vip_phone' => $vip['phone'], // '会员手机号',
                'number' => 1, // '订单总数量',
                'money' => input('s_money'), // '总金额（原价合计金额）',
                'dis_money' => input('real_pay'), // '折后合计金额',
                'real_pay' => input('real_pay'), // '实际支付金额',
                'real_income' => input('real_pay'), // '实际收入金额',
                'wechat_pay' => input('real_pay'), // '微信支付金额',
                'payment' => $payment, // '支付方式',
                'give_integral' => $Totalpoints, // '获得积分数量',
                'create_time' => time()
            ];

            $details = [
                'code' => $kscode,
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
                't_time' => time()
            ];
            $vipConsumption = [
                'consumption_times' => $vip['consumption_times'] + 1, // 消费次数  
                'consumption_number' => $vip['consumption_number'] + 1, // 消费件数
                'total_consumption' => $vip['total_consumption'] + input('real_pay'), // 总消费额
                'final_purchases' => time() // 最后购物时间
            ];
            if (empty($vip['level_code'])) {
                $vip['level_code'] = 0; 
            }
            if (empty($vip['store_code'])) {
                $vip['store_code'] = 0; 
            }

            $int_vip = Db::table($db . '.vip_introducer')->where('rsid_code', $vip['code'])->find();
            if (!empty($int_vip)) {
                $introducer_vip = Db::table($db. '.vip_viplist')->where('code', $int_vip['lnt_code'])->find();
                if (empty($introducer_vip['level_code'])) {
                    $introducer_vip['level_code'] = 0; 
                }
                // 转介绍消费有礼
                $int_write_off_s = Db::table($db.'.vip_activity_courtesy')
                        ->where('activity_type', '转介绍消费有礼')
                        ->where('start_time', '<=', time())
                        ->where('end_time', '>=', time())
                        ->where('level_all', 'like', '%'.$introducer_vip['level_code'].'%')
                        ->where('status', 0)
                        ->select();
                if (!empty($int_write_off_s)) {
                    $int_write_off = $int_write_off_s[0];
                } else {
                    $int_write_off = [];
                }
               
            }
            

            $write_off_W = Db::table($db.'.vip_activity_courtesy')
                    ->where('activity_type', '消费有礼')
                    ->where('start_time', '<=', time())
                    ->where('end_time', '>=', time())
                    ->where('level_all', 'like', '%'.$vip['level_code'].'%')
                    ->where('week', 'like', '%'.date('w').'%')
                    ->where('payment_all', 'like', '%'.input('payment').'%')
                    // ->where('store_all', 'like', '%'.$vip['store_code'].'%')
                    ->where('status', 0)
                    ->select();
            if (!empty($write_off_W)) {
                foreach ($write_off_W as $k=>$v) {
                    $write_off_W[$k]['store_w'] = false;
                    $write_off_W[$k]['time_w'] = false;
                }

                foreach ($write_off_W as $k=>$v) {
                    $fstore_all = explode(',',$v['store_all']); 
                    if (count($fstore_all) == 1 && empty($fstore_all[0])) {
                        $write_off_W[$k]['store_w'] = true;
                    } else {
                        foreach ($fstore_all as $val) {
                            if ($val === $vip['store_code']) { // 判断门店
                                $write_off_W[$k]['store_w'] = true;
                            }
                        }
                    }
                    $a_hsi = strtotime(date('Y-m-d').$v['a_hsi']);
                    $b_hsi = strtotime(date('Y-m-d').$v['b_hsi']);
                    if (time() > $a_hsi && time() < $b_hsi ) {  //判断是否符合几点到几点
                        $write_off_W[$k]['time_w'] = true;
                    } 
                }

                foreach ($write_off_W as $k=>$v) {
                    if ($v['store_w'] == false) {
                        unset($write_off_W[$k]);
                    }
                }
                foreach ($write_off_W as $k=>$v) {
                    if ($v['time_w'] == false) {
                        unset($write_off_W[$k]);
                    }
                }
                if (count($write_off_W) > 1) {
                    foreach ($write_off_W as $k=>$v) {
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
            $memberLevel = $this->levelVip($db, $vip, input('real_pay'));  //会员晋升条件
            //修改的数据
            $coupon_record_jl = [
                'status' => 1,
                'remarking' => '扫码消费使用',
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
                // 'give_integral' => $give_integral, // '获得积分数量',
                'create_time' => time()
            ];
            $details = [
                'code' => $kscode,
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
                't_time' => time()
            ];
            $memberLevel = [];
        }
        
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
                    Db::table($db.'.vip_stored_value')->insert($v_value_jilu);
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
                    Db::table($db.'.vip_flow_integral')->insert($gz_integral_jilu);
                    $ew->integral_promotes($Totalpoints, $vip);
                }
                unset($Totalpoints); 
            }
            if (!empty($bir_off) && $month == $bir_month && $day == $bir_day) {
                if (!empty($bir_off['stored_code'])) { // 赠送储值
                    $stored_code_type = Db::table($db.'.vip_agive_share')->where('code', $bir_off['stored_code'])->find();
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
                    Db::table($db.'.vip_stored_value')->insert($value_jilu);
                    $ew->value_promotes($m, $vip);
                        
                }
                if (!empty($bir_off['coupon_code'])) { // 赠送卡劵
                    if (!empty($bir_off['coupon_code'])) { 
                        $poupon = Db::table($db . '.vip_agive_coupon')->where('code', $bir_off['coupon_code'])->select(); 
                        if (!empty($poupon)) {
                            foreach ($poupon as $pon) {
                                for($i = 0; $i < $pon['coupon_number']; $i++) {
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
                        $introducer_stored_type = Db::table($db.'.vip_agive_share')->where('code', $int_write_off['stored_code'])->find();
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
                        Db::table($db.'.vip_stored_value')->insert($int_value_jilu);
                        $ew->value_promotes($int_money, $introducer_vip);
                        unset( $int_money);
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
                        $introducer_stored_type = Db::table($db.'.vip_agive_share')->where('code', $int_write_off['stored_code'])->find();
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
                        Db::table($db.'.vip_stored_value')->insert($int_value_jilu);
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

            if (!empty($write_off)) {  // 如果都符合赠送积分储值和卡劵
                if (!empty($write_off['integral_code'])) { //赠送积分
                    $agive_integral_type = Db::table($db.'.vip_agive_integral')->where('code', $write_off['integral_code'])->find();
                    $agive_integral = Db::table($db.'.vip_agive_integral')->where('code', $write_off['integral_code'])->select();
                    $newArr = [];
                    if (!empty($agive_integral)) {
                        foreach ($agive_integral as $v) {
                            if ($v['reward_money'] <= input('real_pay')) {
                                array_push($newArr, $v['reward_money']);
                            }
                        }
                        if (count($newArr) > 0) {
                            $max_integral = Db::table($db.'.vip_agive_integral')->where('code', $write_off['integral_code'])->where('reward_money', max($newArr))->find();
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
                            Db::table($db.'.vip_flow_integral')->insert($integral_jilu);
                            $ew->integral_promotes($fs, $vip);
                                
                        }
                        unset($agive_integral_type, $agive_integral, $newArr, $Multiple, $max_integral, $integral_jilu, $f);
                    }
                    
                }
                if (!empty($write_off['stored_code'])) { // 赠送储值
                    $stored_code_type = Db::table($db.'.vip_agive_share')->where('code', $write_off['stored_code'])->find();
                    $stored_code = Db::table($db.'.vip_agive_share')->where('code', $write_off['stored_code'])->select();
                    $newZarr = [];
                    if (!empty($stored_code)) {
                        foreach ($stored_code as $v) {
                            if ($v['reward_money'] <= input('real_pay')) {
                                array_push($newZarr, $v['reward_money']);
                            }
                        }
                        if (count($newZarr) > 0) {
                            $max_stored = Db::table($db.'.vip_agive_share')->where('code', $write_off['stored_code'])->where('reward_money', max($newZarr))->find();
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
                            Db::table($db.'.vip_stored_value')->insert($value_jilu);
                            $ew->value_promotes($m, $vip);
                        }
                        unset($newZarr, $stored_code, $stored_code_type, $max_stored, $m);
                    }
                }
                if (!empty($write_off['coupon_code'])) { // 赠送卡劵
                    $coupon_type = Db::table($db.'.vip_agive_coupon')->where('code', $write_off['coupon_code'])->find();
                    $coupon = Db::table($db.'.vip_agive_coupon')->where('code', $write_off['coupon_code'])->select();
                    $newCarr = [];
                    if (!empty($coupon)) {
                        foreach ($coupon as $v) {
                            if ($v['reward_money'] <= input('real_pay')) {
                                array_push($newCarr, $v['reward_money']);
                            }
                        }
                        if (count($newCarr) > 0) { 
                            $max_coupon = Db::table($db.'.vip_agive_coupon')->where('code', $write_off['coupon_code'])->where('reward_money', max($newCarr))->find();
                            if ($max_coupon['reward_type'] == 0) {
                                $c = $max_coupon['coupon_number'];
                            } else {
                                $Cultiple = floor(input('real_pay') / max($newCarr)); // 满了几次
                                $c = $max_coupon['coupon_number'] * $Cultiple;
                                
                            }
                            for($i = 0; $i < $c; $i++) {
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
            webApi(200, '成功', $status_a);
        } else {
            webApi(400, '失败');
        }
    }


    /**
     * 会员等级晋升
     */
    public function levelVip($db, $vipMember, $real_pay)
    {
        $memLv = Db::table($db.'.vip_viplevel')->field('uid')->where('code', $vipMember['level_code'])->find();
        if (!empty($memLv)) {
            $nowLv = $memLv['uid'];
        } else {
            $nowLv = -1;
        }
        // 级别晋升 查询该会员当前的级别折扣力度 查询比他力度更大的第一条 比较所有的晋升条件 如果有一条符合 晋升
        $promotes = Db::table($db.'.vip_vippromote')
                    ->alias('a')
                    ->leftJoin($db.'.vip_viplevel l', 'l.code = a.levelname')
                    ->where('l.uid', '>', $nowLv)
                    // ->where('a.state', 1)
                    ->select();
        
        if (!empty($promotes)) {
            foreach ($promotes as $k=>$v) {
                $promotes[$k]['js'] = false;
            }
            foreach ($promotes as $k=>$v) {
                // 判断首次消费
                if ( intval($vipMember['consumption_times']) < 1 && $real_pay  >= $v['first_amount'] ) {
                    $promotes[$k]['js'] = true;
                }

                // 判断单次消费
                if ( $real_pay  >= $v['single_amount'] ) {
                    $promotes[$k]['js'] = true;
                }

                // 判断累计总积分
                $vip_viprights_integral = Db::table($db.'.vip_viprights_integral')->where('single_selection', 1)->find();
                if (!empty($vip_viprights_integral)) {
                    $give_integral = floor(($real_pay /  $vip_viprights_integral['element']) * $vip_viprights_integral['integral']);
                    $getIntegral = Db::table($db.'.vip_flow_integral')->field('sum(integral) as integral')->where('vip_code', $vipMember['code'])->where('create_time', '>=', time() - ($v['total_integral_time'] * 86400))->find();
                    if ( $getIntegral['integral'] + $give_integral  >= $v['total_integral'] ) {
                        $promotes[$k]['js'] = true;
                    }
                } 
                unset($rec, $ord, $getIntegral, $vip_viprights_integral);

                // 判断消费总金额
                $mpay = Db::table($db.'.vip_goods_order')->field('sum(real_pay) as pay_sum')->where('vip_code', $vipMember['code'])->where('status', 0)->where('create_time', '>=', time() - ($v['total_amount_time'] * 86400))->select();
                if ((floatval($mpay[0]['pay_sum']) + $real_pay ) >= $v['total_amount']) {
                    $promotes[$k]['js'] = true;
                }
                unset($mpay);

            }
            foreach ($promotes as $k=>$v) {
                if ($v['js'] == false) {
                    unset($promotes[$k]);
                }
            }
            if (!empty($promotes)) {
                foreach ($promotes as $k=>$v) {
                    $promotes[$k]['l'] =
                        !empty( Db::table($db.'.vip_viplevel')->field('uid')->where('code', $v['levelname'])->find() )
                            ?
                        Db::table($db.'.vip_viplevel')->field('uid')->where('code', $v['levelname'])->find()['uid']
                            : '';
                }
                rsort($promotes);
                $memberLevel = Db::table($db.'.vip_viplevel')->where('code',  $promotes[0]['levelname'])->find();
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
     * 查询系统设置
     */
    public function chasys()
    {
        $data = Db::table($this->db.'.vip_sys_con')->find()['staff_is_discount'];

        webApi(200, '成功', $data);
    } 

    /**
     * 查询系统设置
     */
    public function chasysMoney()
    {
        $data = Db::table($this->db.'.vip_sys_con')->find()['staff_is_money'];

        webApi(200, '成功', $data);
    } 


    /**
     * 充值   
     */
    public function recharge()
    {
        $code = input('code') ; 
        $openid = input('openid');
        $db = input('company');
        $coupon_code = input('coupon_code') ; //赠送卡劵的code
        $give_integral = input('give_integral') ; //赠送的积分
        $money_g = input('money_g') ; // 充值的金额
        $stored_value = input('stored_value') ; //赠送的金额
        $staff = input('staff_code');
        $money = $money_g + $stored_value;
        if (empty($openid) || empty($db)) {
            webApi(400, 'openid或company不存在');
        }
        $vip = Db::table($db.'.vip_viplist')->where('openid', $openid)->find();

        if (empty(input('order_code'))) {
            $kscode = 'CZLS' . str_replace('.', '', microtime(1));
        } else {
            $is_set = Db::table($db . '.vip_stored_value')->where('documents', input('order_code'))->find();
            if ($is_set) {
                webApi(400, '失败,编号重复');
            } else {
                $kscode = input('order_code');
            }
        }
        if (empty($staff)) {
            $staff_code = '';
            $staff_name = '';
        } else {
            $staff_code = $staff;
            $vaname = Db::table($db.'.vip_staff')->where('code', $staff)->find();
            $staff_name = $vaname['name'] == null ? '' : $vaname['name'];
        }
        if ($vip) {
            $residual_value_data = [
                'documents' => $kscode,
                'vip_code' => $vip['code'],  
                'vip_name' => $vip['username'],
                'road' => '微信充值',
                'stored_value' => $money,
                'actual_receipt' => $money_g,
                'give_amount' => $stored_value,
                'give_integral' => $give_integral,
                'staff_code' => $staff_code,
                'staff_name' => $staff_name,
                'store_code' =>input('store_code'),
                'create_time' => time(),
                'value_status' => 0,
                'payment_method' => '微信支付',
                'stored_code'  => $code
            ];
            $es = new ErpWhere($db, "");
            Db::startTrans();
            try {
                Db::table($db . '.vip_stored_value')->insert($residual_value_data);
                Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('residual_value', $money); // 用户的剩余储值加
                Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('total_value', $money); // 用户的总储值加
                $es->value_promotes($money, $vip);
                if (!empty($give_integral)) {
                    $integral_jilu = [
                        'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                        'vip_code' => $vip['code'],
                        'vip_name' => $vip['username'],
                        'integral' => $give_integral,
                        'road' => '充值赠送',
                        'create_time' => time(),
                    ];
                    $es->integral_promotes($give_integral, $vip);
                    Db::table($db.'.vip_flow_integral')->insert($integral_jilu);
                    Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('residual_integral', $give_integral); // 用户的剩余总积分加
                    Db::table($db . '.vip_viplist')->where('code', $vip['code'])->setInc('total_integral', $give_integral); // 用户的总积分加
                }
                if (!empty($coupon_code)) { 
                    $coupon = Db::table($db.'.vip_agive_coupon')->where('code', $coupon_code)->select();
                    if (!empty($coupon)) {
                        foreach ($coupon as $v) {
                            for($i = 0; $i < $v['coupon_number']; $i++) {
                                $es->couponAdd($v['card_type'], $v['coupon_code'], $vip['code'], '充值有礼赠送');
                            }
                        }
                    }
                }
                unset($coupon, $vip, $coupon_code, $vipConsumption);
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
                webApi(200, '成功');
            } else {
                webApi(400, '失败');
            }
        } else {
            webApi(400, '非会员或未绑定会员无法充值');
        }

    }

    public function vipStore()
    {
        $data = Db::table(input('company') . '.vip_store')->field('name, code')->where('status', 0)->select();
        webApi(0, 'ok', $data);
    }

    /**
     * 员工下拉框
     */
    public function vipStaffs()
    {
        $store = input('store_code');
        if (!empty($store)) {
            $data = Db::table(input('company') . '.vip_staff')->where('store_code', $store)->where('status', '<>', 1)->field('name, code')->select();
        } else {
            $data = [];
        }
        
        webApi(0, 'ok', $data);
    }

    /**
     * 注册时添加介绍人
     */
    public function vipIntroducer()
    {
        
        $data = Db::table(input('company') . '.vip_viplist')
                ->where('phone', input('phone'))
                ->field('username, code, phone')
                ->find();
        
        webApi(0, 'ok', $data);
    }

    /**
     * 会员注册
     */
    public function vipRegister()
    {
        $rfmTime = Db::table(input('company') . '.vip_vippromote')->field('introduction_time')->find();
        $data = [
            'username' => input('username'), // 姓名
            'code' => input('phone'), // 会员卡号
            'sex' => input('sex'), // 性别
            'phone' => input('phone'), // 手机号
            'store_code' => input('store_code'), //所属门店
            'consultant_code' => input('staff_code'), //导购
            'birthday' => strtotime(input('birthday')), // 生日
            'vvip' => 1, //是否是微会员
            'vvip_time' => time(), // 微会员登记时间
            'introducer_name' => input('introducer_name'), //转介绍人姓名
            'introducer_code' => input('introducer_code'), // 转介绍人卡号
            'img' => input('img'), // 头像
            'date_registration' => time(), //登记时间
            'openid' => input('openId'),
            'wx_code' => input('wx_code')
        ];
        //手机号是否存在
        $phonerepeat = Db::table(input('company') . '.vip_viplist')->where('phone', $data['phone'])->find();
        if ($phonerepeat) {
            Db::table(input('company') . '.vip_viplist')->where('phone', $data['phone'])->update(['wx_code' => input('wx_code')]);
            
            webApi(200, 'ok', ['code' => $phonerepeat['code']]);
        }
        $introducer = [
            'lnt_name' => input('introducer_name'), // 介绍人姓名
            'lnt_code' => input('introducer_code'), // 介绍人卡号
            'rsid_name' => input('username'), // 被介绍人姓名
            'rsid_code' => input('phone'), // 被介绍人卡号
            'lnttime' => time(), // 时间
        ];
        if (!empty(input('introducer_code'))) {
            $intMen = Db::table(input('company') . '.vip_viplevel')
                ->alias('l')
                ->join(input('company') . '.vip_viplist v', 'v.level_code = l.code')
                ->field('l.uid')
                ->where('v.code', input('introducer_code'))
                ->find();
            if (!empty($intMen)) {
                $levelSort = $intMen['uid'];
            } else {
                $levelSort = -1;
            }
            $promotes = Db::table(input('company') . '.vip_vippromote')
                ->alias('a')
                ->leftJoin(input('company') . '.vip_viplevel l', 'l.code = a.levelname')
                ->field('a.*,l.code,l.username,l.uid')
                ->where('l.uid', '>', $levelSort)
                ->select();
            if (!empty($promotes)) {
                foreach ($promotes as $k => $v) {
                    $promotes[$k]['js'] = false;
                }
                //统计转介绍人数
                $isempty = Db::table(input('company') . '.vip_introducer')->where('lnttime', '>=', time() - (86400 * $rfmTime['introduction_time']))->where('lnt_code', input('introducer_code'))->count();
                unset($rfmTime);
                $isempty += 1;
                foreach ($promotes as $k => $v) {
                    //判断转介绍数
                    if ($isempty >= $v['introduction']) {
                        $promotes[$k]['js'] = true;
                    }
                }
                foreach ($promotes as $k => $v) {
                    if ($v['js'] == false) {
                        unset($promotes[$k]);
                    }
                }
                if (!empty($promotes)) {
                    rsort($promotes);
                    $nextLevel = ['level_code' => $promotes[0]['levelname']];
                    unset($promotes);
                }
            }
        }
        //执行事务
        Db::startTrans();
        try {
            Db::table(input('company') . '.vip_viplist')->insert($data);
            $ew = new ErpWhere(input('company'), '');
            if (!empty(input('introducer_code'))) {  // 转介绍记录
                Db::table(input('company') . '.vip_introducer')->insert($introducer);
                $int_vip = Db::table(input('company') . '.vip_viplist')->where('code', input('introducer_code'))->find();
                $write_off_W = Db::table(input('company') . '.vip_activity_courtesy')  // 转介绍送礼
                    ->where('activity_type', '转介绍有礼')
                    ->where('start_time', '<=', time())
                    ->where('end_time', '>=', time())
                    ->where('level_all', 'like', '%'.$int_vip['level_code'].'%')
                    ->where('status', 0)
                    ->select();
                if (!empty($write_off_W)) {
                    
                    foreach ($write_off_W as $k=>$v) {
                        $write_off_W[$k]['store_w'] = false;
                    }
                    foreach ($write_off_W as $k=>$v) {
                        $fstore_all = explode(',',$v['store_all']); 
                        if (count($fstore_all) == 1 && empty($fstore_all[0])) {
                            $write_off_W[$k]['store_w'] = true;
                        } else {
                            foreach ($fstore_all as $val) {
                                if ($val === input('store_code')) { // 判断门店
                                    $write_off_W[$k]['store_w'] = true;
                                }
                            }
                        }
                    }
                    foreach ($write_off_W as $k=>$v) {
                        if ($v['store_w'] == false) {
                            unset($write_off_W[$k]);
                        }
                    }
                    if (count($write_off_W) > 1) {
                        foreach ($write_off_W as $k=>$v) {
                            if (empty($v['store_all'])) {
                                unset($write_off_W[$k]);
                            }
                        }
                    }
                    sort($write_off_W);
                    if (!empty($write_off_W)) {
                        $write_off_introducer = $write_off_W[0];
                    } else {
                        $write_off_introducer = [];
                    }
                    if (!empty($write_off_introducer)) {
                        $int_vip_count = Db::table(input('company') . '.vip_introducer')->where('lnt_code', input('introducer_code'))->where('lnttime', '>=', $write_off_introducer['start_time'])->where('lnttime', '<=', $write_off_introducer['end_time'])->count();
                        if ($int_vip_count == $write_off_introducer['advance_date']) {  // 转介绍人数大于等于规则人数
                            if (!empty($write_off_introducer['integral'])) {
                                Db::table(input('company') . '.vip_viplist')->where('code', $int_vip['code'])->setInc('residual_integral', $write_off_introducer['integral']); // 用户的剩余总积分加
                                Db::table(input('company') . '.vip_viplist')->where('code', $int_vip['code'])->setInc('total_integral', $write_off_introducer['integral']); // 用户的总积分加
                                $integral_jilu_introducer = [
                                    'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                                    'vip_code' => $int_vip['code'],
                                    'vip_name' => $int_vip['username'],
                                    'integral' => $write_off_introducer['integral'],
                                    'road' => '转介绍有礼赠送',
                                    'create_time' => time(),
                                ];
                                Db::table(input('company') . '.vip_flow_integral')->insert($integral_jilu_introducer);
                                unset($integral_jilu_introducer);
                                $ew->integral_promotes($write_off_introducer['integral'], $int_vip);
                            }
                            if (!empty($write_off_introducer['stored_value'])) {
                                Db::table(input('company') . '.vip_viplist')->where('code', $int_vip['code'])->setInc('residual_value', $write_off_introducer['stored_value']); // 用户的剩余储值加
                                Db::table(input('company') . '.vip_viplist')->where('code', $int_vip['code'])->setInc('total_value', $write_off_introducer['stored_value']); // 用户的总储值加
                                $value_jilu_introducer = [
                                    'documents' => 'CZLS' . str_replace('.', '', microtime(1)),
                                    'vip_code' => $int_vip['code'],
                                    'vip_name' => $int_vip['username'],
                                    'stored_value' => $write_off_introducer['stored_value'],
                                    'road' => '转介绍有礼赠送',
                                    'create_time' => time(),
                                ];
                                Db::table(input('company') . '.vip_stored_value')->insert($value_jilu_introducer);
                                $ew->value_promotes($write_off_introducer['stored_value'], $int_vip);
                                unset($value_jilu_introducer);
                            }
                            if (!empty($write_off_introducer['coupon_code'])) {
                                $poupon_introducer = Db::table(input('company') . '.vip_agive_coupon')->where('code', $write_off_introducer['coupon_code'])->select();
                                if (!empty($poupon_introducer)) {
                                    foreach ($poupon_introducer as $val) {
                                        for ($i = 0; $i < $val['coupon_number']; $i++) {
                                            $ew->couponAdd($val['card_type'], $val['coupon_code'], $int_vip['code'], '转介绍有礼赠送');
                                        }
                                    }
                                }
                                unset($poupon_introducer);
                            }
                            
                        }
                    }
                }
                unset($write_off_introducer);
            }
            $write_off_k = Db::table(input('company') . '.vip_activity_courtesy')
                ->where('activity_type', '开卡有礼')
                ->where('start_time', '<=', time())
                ->where('end_time', '>=', time())
                ->where('status', 0)
                ->select();
            if (!empty($write_off_k)) {
                
                foreach ($write_off_k as $k=>$v) {
                    $write_off_k[$k]['store_w'] = false;
                }
                foreach ($write_off_k as $k=>$v) {
                    $fstore_all = explode(',',$v['store_all']); 
                    if (count($fstore_all) == 1 && empty($fstore_all[0])) {
                        $write_off_k[$k]['store_w'] = true;
                    } else {
                        foreach ($fstore_all as $val) {
                            if ($val === input('store_code')) { // 判断门店
                                $write_off_k[$k]['store_w'] = true;
                            }
                        }
                    }
                }
                foreach ($write_off_k as $k=>$v) {
                    if ($v['store_w'] == false) {
                        unset($write_off_k[$k]);
                    }
                }
                if (count($write_off_k) > 1) {
                    foreach ($write_off_k as $k=>$v) {
                        if (empty($v['store_all'])) {
                            unset($write_off_k[$k]);
                        }
                    }
                }
                sort($write_off_k);
                if (!empty($write_off_k)) {
                    $write_off_ka = $write_off_k[0];
                } else {
                    $write_off_ka = [];
                }
                if (!empty($write_off_ka['integral'])) {
                    Db::table(input('company') . '.vip_viplist')->where('code', input('phone'))->setInc('residual_integral', $write_off_ka['integral']); // 用户的剩余总积分加
                    Db::table(input('company') . '.vip_viplist')->where('code', input('phone'))->setInc('total_integral', $write_off_ka['integral']); // 用户的总积分加
                    $kaika = Db::table(input('company') . '.vip_viplist')->where('code', input('phone'))->find();
                    $integral_jilu = [
                        'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                        'vip_code' => input('phone'),
                        'vip_name' => input('username'),
                        'integral' => $write_off_ka['integral'],
                        'road' => '开卡有礼赠送',
                        'create_time' => time(),
                    ];
                    Db::table(input('company') . '.vip_flow_integral')->insert($integral_jilu);
                    $ew->integral_promotes($write_off_ka['integral'], $kaika);
                    unset($kaika, $integral_jilu);
                }
                if (!empty($write_off_ka['coupon_code'])) {
                    $poupon = Db::table(input('company') . '.vip_agive_coupon')->where('code', $write_off_ka['coupon_code'])->select();
                    if (!empty($poupon)) {
                        foreach ($poupon as $pon) {
                            for ($i = 0; $i < $pon['coupon_number']; $i++) {
                                $ew->couponAdd($pon['card_type'], $pon['coupon_code'], input('phone'), '开卡有礼赠送');
                            }
                        }
                    }
                    unset($poupon);
                }
            unset($write_off_ka);
            }
            if (isset($nextLevel)) {
                $promote_insert = [
                    'vip_code' => $int_vip['code'],
                    'vip_name' => $int_vip['username'],
                    'before_level' => $int_vip['level_code'],
                    'after_level' => $nextLevel['level_code'],
                    'reason' => '转介绍晋升',
                    'create_time' => time()
                ];
                Db::table(input('company') . '.vip_promote')->insert($promote_insert);
                Db::table(input('company') . '.vip_viplist')->where('code', input('introducer_code'))->update($nextLevel);
            }
            unset($nextLevel);
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $res = false;
        }
        if ($res) {

            webApi(200, '成功', ['code' => $data['code']]);
        } else {
            webApi(400, '失败');
        }
    }

}