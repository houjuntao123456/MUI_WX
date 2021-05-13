<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 * Author lxy
 * Date 2020/06/02
 * Description 锁客设置中心
 */
class SuoActivity extends Common
{
    /**
     * 支付锁客查表
     */
    public function activitySel()
    {
        //获取消费金额数据
        $money = input('money');
        if ($money == null) {
            webApi(400, '消费金额不能为空!');
        }
        $company = input('company');
        if ($company == null) {
            webApi(400, '公司不能为空!');
        }
        $store = input('store_code'); 
        if ($store != "") { //有门店查询,有门店的和通用的
            $data = Db::table($company . '.vip_activity_courtesy')
                ->where('start_time', '<=', time())
                ->where('end_time', '>', time())
                ->where('start_money', '<=', $money)
                ->where('end_money', '>=', $money)
                ->where('activity_type', '支付锁客')
                ->where('status', 0)
                ->where('store_all', 'like', '%' . $store . '%')
                ->select();
            $dataOne = Db::table($company . '.vip_activity_courtesy')
                ->where('start_time', '<=', time())
                ->where('end_time', '>', time())
                ->where('start_money', '<=', $money)
                ->where('end_money', '>=', $money)
                ->where('activity_type', '支付锁客')
                ->where('status', 0)
                ->where('(store_all = "" or store_all IS null)')
                ->select();
            $data = array_merge($data, $dataOne);
        } else { //无门店,查询通用的
            $data = Db::table($company . '.vip_activity_courtesy')
                ->where('start_time', '<=', time())
                ->where('end_time', '>', time())
                ->where('start_money', '<=', $money)
                ->where('end_money', '>=', $money)
                ->where('activity_type', '支付锁客')
                ->where('status', 0)
                ->where('(store_all = "" or store_all IS null)')
                ->select();
        }
        //修改格式
        if ($data) {
            foreach ($data as $k => $v) {
                $data[$k]['coupon_data'] = '';
                //创建时间格式的转换
                $data[$k]['create_g'] = date('Y-m-d H:i:s', $v['create_time']);
                //开始时间格式的转换
                if ($v['start_time'] == 0) {
                    $data[$k]['start_g'] = "无限制";
                } else {
                    $data[$k]['start_g'] = date('Y-m-d H:i:s', $v['start_time']);
                }
                //结束时间格式的转换
                if ($v['end_time'] == 0) {
                    $data[$k]['end_g'] = "无限制";
                } else {
                    $data[$k]['end_g'] = date('Y-m-d H:i:s', $v['end_time']);
                }
                if ($v['store_all'] == "") {
                    $data[$k]['store_name'] = "无门店";
                }
                if ($v['level_all'] == "") {
                    $data[$k]['level_name'] = "无级别";
                }
                if ($v['reward_name'] == "") {
                    $data[$k]['reward_name'] = "无奖励";
                }
                if ($v['coupon_code']) {
                    $t = Db::table($company . '.vip_agive_coupon')->where('code', $v['coupon_code'])->select();
                    $data[$k]['coupon_name'] = array_column($t, 'coupon_name');
                    $data[$k]['coupon_data'] = $t;
                }
                $data[$k]['money_g'] = floatval(bcadd($v['money'],$money,2));
                $data[$k]['give_reward'] = $v['name'];
                $data[$k]['give_integral'] = $v['integral'];
            }
        }
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 智能储值规则查询
     */
    public function storedSel()
    {
        //获取消费金额数据
        $money = input('money');
        if ($money == null) {
            webApi(400, '消费金额不能为空!');
        }
        if ($money == 0) {
            webApi(400, '消费金额不能为0!');
        }
        $company = input('company');
        if ($company == null) {
            webApi(400, '公司不能为空!');
        }
        //查询的数据
        $storeddata = Db::table($company . '.vip_activity_courtesy')
            ->where('start_time', '<=', time())
            ->where('end_time', '>', time())
            ->where('activity_type', '智能储值')
            ->where('status', 0)
            ->find();
        $data = []; $ddata = [];
        if ($storeddata) {
            if ($storeddata['intelligence_type'] == 0) { //智能储值
                $viplist = Db::table($company . '.vip_viplist')->where('openid', input('openid'))->find();
                if (!empty($viplist['level_code'])) {
                    $adata = Db::table($company . '.vip_arecharge')
                        ->alias('a')
                        ->leftJoin($company . '.vip_viplevel l', 'l.code = a.level_code')
                        ->field('a.*, ifnull(l.username, "无级别") lname')
                        ->where('a.recharge_money', '>', $money)
                        ->where('a.start_time', '<=', time())
                        ->where('a.end_time', '>', time())
                        ->where('a.level_code', $viplist['level_code'])
                        ->select();
                    $ddata = array_merge($adata);
                }
                $bdata = Db::table($company . '.vip_arecharge')
                    ->alias('a')
                    ->leftJoin($company . '.vip_viplevel l', 'l.code = a.level_code')
                    ->field('a.*, ifnull(l.username, "无级别") lname')
                    ->where('a.recharge_money', '>', $money)
                    ->where('a.start_time', '<=', time())
                    ->where('a.end_time', '>', time())
                    ->where('a.level_code', '')
                    ->select();
                $cdata = Db::table($company . '.vip_arecharge')
                    ->alias('a')
                    ->leftJoin($company . '.vip_viplevel l', 'l.code = a.level_code')
                    ->field('a.*, ifnull(l.username, "无级别") lname')
                    ->where('a.recharge_money', '>', $money)
                    ->where('a.start_time', '')
                    ->where('a.end_time', '')
                    ->select();
                if ($ddata) {
                    $data = array_merge($bdata, $cdata, $ddata);
                } else {
                    $data = array_merge($bdata, $cdata);
                }
                if ($data) {
                    foreach ($data as $k => $v) {
                        $data[$k]['money_g'] = $v['recharge_money'];
                        $data[$k]['give_reward'] = 0;
                        $data[$k]['give_stored'] = 0;
                        $data[$k]['coupon_name'] = '';
                        $data[$k]['coupon_data'] = '';
                        $data[$k]['intelligence_type'] = $storeddata['intelligence_type'];
                    }
                    foreach ($data as $k => $v) {
                        $data[$k]['stored_value'] = 0;
                        if ($v['reward_type'] == 0) {
                            $data[$k]['give_reward'] = $v['give_money'];
                        } else {
                            $data[$k]['give_reward'] = $v['recharge_money'] * ($v['give_money'] / 100);
                        }
                        if ($v['coupon_code']) {
                            $t = Db::table($company . '.vip_agive_coupon')->where('code', $v['coupon_code'])->select();
                            $data[$k]['coupon_name'] = array_column($t, 'coupon_name');
                            $data[$k]['coupon_data'] = $t;
                        }
                    }
                    foreach ($data as $k => $v) {
                        $data[$k]['stored_value'] = $v['give_reward'];
                        $data[$k]['give_stored'] = round(($v['recharge_money'] / ($v['recharge_money'] + $v['give_reward'])) * 10, 1) . '折';
                    }
                }
            } else if ($storeddata['intelligence_type'] == 1) { //充值免单
                $data = [0 => [
                                'code' => $storeddata['code'],
                                'money_g' => $money * $storeddata['reward_free'], 
                                'give_reward' => '免单', 
                                'give_integral' => 0,
                                'coupon_name' => '',
                                'coupon_data' => '',
                                'give_stored'=> '0折',
                                'stored_value' => 0,
                                'remark' => $storeddata['remark'],
                                'intelligence_type' => $storeddata['intelligence_type']
                            ]
                        ];
            } else if ($storeddata['intelligence_type'] == 2) { //自定义充值赠送比例
                $data = [0 => [
                                'code' => $storeddata['code'], 
                                'money_g' => $money * $storeddata['reward_free'], 
                                'give_reward' => $money * ($storeddata['reward_proportion']/100),
                                'give_integral' => 0,
                                'coupon_name' => '',
                                'coupon_data' => '',
                                'give_stored' => '0折',
                                'stored_value' => $money * ($storeddata['reward_proportion'] / 100),
                                'remark' => $storeddata['remark'],
                                'intelligence_type' => $storeddata['intelligence_type']
                            ]
                        ];
            }
        }
        //返回数据
        webApi(200, 'ok', $data);
    }
}
