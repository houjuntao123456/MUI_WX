<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 * Author lxy
 * Date 2019/11/15
 * Description 复购率
 */
class Repurchase extends Common
{
    /**
     * 复购率
     */
    public function RepurchaseSel()
    {
        //分页信息
        [$page, $limit, $db] = [input('page'), input('limit'), $this->db];
        //判断如果有员工就查询员工
        if (input('staff') != '') {
            $where_staff[] = ['v.code', '=', input('staff')];
        } else {
            //判断如果没有员工有门店就查询门店
            if (input('store') != '') {
                $where_staff[] = ['v.store_code', '=', input('store')];
            } else {
                //判断如果没有员工也没有门店就查询组织机构,否则就查询登入人的管理机构
                if (input('splb') != '') {
                    $ew = new ErpWhere($db, input('splb'));
                    $stores = $ew->org()->store()->get();
                    $a_org = explode(',', input('splb'));
                    if (in_array('ZZJG15459825114064', $a_org)) {
                        $stores = $stores . ', ';
                    }
                    $where_staff[] = ['v.store_code', 'in', $stores];
                } else {
                    //查询当前登入人 session('info.staff')
                    $operate = Db::table($db . '.vip_staff')->where('code', session('info.staff'))->find();
                    //门店限制
                    if ($operate['code'] == 'boss') {
                        $where_staff = true;
                    } else if ($operate['role'] == 0) {
                        $ew = new ErpWhere($db, $operate['admin_org_code']);
                        $stores = $ew->org()->store()->get();
                        $a_org = explode(',', $operate['admin_org_code']);
                        if (in_array('ZZJG15459825114064', $a_org)) {
                            $stores = $stores . ', ';
                        }
                        $where_staff[] = ['v.store_code', 'in', $stores];
                    } else {
                        $where_staff[] = ['v.code', '=', $operate['code']];
                    }
                }
            }
        }
        //时间限制 定义默认时间
        if (input('start') != '') {
            $where[] = ['create_time', '>=', strtotime(input('start'))];
            $vip_where[] = ['date_registration', '>=', strtotime(input('start'))]; //消费人数时间条件
            $follow_where[] = ['time', '>=', strtotime(input('start'))]; //跟进人数时间条件
        }
        if (input('end') != '') {
            $where[] = ['create_time', '<=', strtotime(input('end'))];
            $vip_where[] = ['date_registration', '<=', strtotime(input('end'))];
            $follow_where[] = ['time', '<=', strtotime(input('end'))];
        }
        //判断条件不存在就都查询
        if (!isset($where)) {
            $where = true;
        }
        if (!isset($vip_where)) {
            $vip_where = true;
        }
        if (!isset($follow_where)) {
            $follow_where = true;
        }
        $staffdata = Db::table($db . '.vip_staff')
            ->alias('v')
            ->leftJoin($db . '.vip_store vm', 'vm.code = v.store_code')
            ->field('v.code, v.name, ifnull(vm.name, "无门店") AS vmname')
            ->where('v.status', 0)
            ->where($where_staff)
            ->page($page, $limit)
            ->select();
        $count = Db::table($db . '.vip_staff')
            ->alias('v')
            ->leftJoin($db . '.vip_store vm', 'vm.code = v.store_code')
            ->field('v.code, v.name, ifnull(vm.name, "无门店") AS vmname')
            ->where('v.status', 0)
            ->where($where_staff)
            ->count();
        // 更改格式
        foreach ($staffdata as $k => $v) {
            $staffdata[$k]['staff_name'] = '员工 - ' . $v['name'] . '(' . $v['vmname'] . ')';
            //新增会员人数
            $staffdata[$k]['vip_number'] = Db::table($db . '.vip_viplist')
                ->where($vip_where)
                ->where('consultant_code', $v['code'])
                ->count();
            //跟进人数
            $staffdata[$k]['follow_up'] = Db::table($db . '.vip_returnvisit_record')
                ->where($follow_where)
                ->where('visit_operator', $v['code'])
                ->group('user_code')
                ->count();
            $staffdata[$k]['order_amount'] = Db::table($db . '.vip_goods_order')
                // 条件 round 四舍五入 real_pay->业绩
                ->field('ifnull(round(sum(real_pay), 2),0) as order_amount')
                ->where($where)
                ->where('operate_code', $v['code'])
                ->find()['order_amount'];
            $staffdata[$k]['operate_code'] = $v['code'];
            $staffdata[$k]['repeat_purchase'] = 0; //复购人数
            $staffdata[$k]['re_purchase_amount'] = 0; //复购金额
            $staffdata[$k]['amount_order_total'] = 0; //复购人的总金额
            $staffdata[$k]['amount_order_first'] = 0; //第一次金额
        }
        //更改格式,组装 (员工, 复购人数)
        foreach ($staffdata as $k => $v) {
            $member_order = Db::table($db . '.vip_goods_order')
                ->field('count(id) num, ifnull(round(real_pay, 2),0) as real_pay, ifnull(round(sum(real_pay), 2),0) as r_pay')
                ->where('operate_code', $v['code'])
                ->where($where)
                ->where('vip_code', '<>', '非会员')
                ->group('vip_code')
                ->select();
            foreach ($member_order as $val) {
                if ($val['num'] > 1) {
                    $staffdata[$k]['repeat_purchase'] += 1;
                    $staffdata[$k]['amount_order_total'] += $val['r_pay'];
                    $staffdata[$k]['amount_order_first'] += $val['real_pay'];
                }
            }
        }
        //更改格式,组装 (复购金额, 复购金额占比(%), 复购会员占比(%))
        foreach ($staffdata as $k => $v) {
            $staffdata[$k]['order_amount_g'] = number_format($v['order_amount'], 2, '.', '');
            if ($v['repeat_purchase'] == 0) {
                $staffdata[$k]['re_purchase_amount'] = 0;
                $staffdata[$k]['member_proportion'] = 0;
                $staffdata[$k]['amount_proportion'] = 0;
            } else {
                $staffdata[$k]['re_purchase_amount'] = number_format($v['amount_order_total'] - $v['amount_order_first'], 2, '.', ''); //复购金额
                if ($v['order_amount'] == 0 || ($v['amount_order_total'] - $v['amount_order_first']) == 0) {
                    $staffdata[$k]['amount_proportion'] = 0;
                } else {
                    $staffdata[$k]['amount_proportion'] = number_format((($v['amount_order_total'] - $v['amount_order_first']) / $v['order_amount']) * 100, 2); //金额比例
                }
            }
        }
        // 业绩金额排序
        array_multisort(array_column($staffdata, 'order_amount'), SORT_DESC, $staffdata);
        //清除变量
        unset($page, $where, $limit, $db, $member_order);
        //格式化数据
        $data = [
            'data' => $staffdata,
            'count' => $count
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }
    public function RepurchaseSelp()
    {
        //获取的数据
        [$page, $limit, $db, $start, $end, $staff, $store, $org] =
            [input('page'), input('limit'), $this->db, input('start'), input('end'), input('staff'), input('store'), input('splb')];
        //时间限制
        if ($start != '') {
            $where[] = ['o.create_time', '>=', strtotime($start)];
            $w[] = ['o.create_time', '>=', strtotime($start)];
            $vip_where[] = ['date_registration', '>=', strtotime($start)]; //消费人数时间条件
            $follow_where[] = ['time', '>=', strtotime($start)]; //跟进人数时间条件
        }
        if ($end != '') {
            $where[] = ['o.create_time', '<=', strtotime($end)];
            $w[] = ['o.create_time', '<=', strtotime($end)];
            $vip_where[] = ['date_registration', '<=', strtotime($end)];
            $follow_where[] = ['time', '<=', strtotime($end)];
        }
        //判断如果有员工就查询员工
        if ($staff != '') {
            $where[] = ['o.operate_code', '=', $staff];
        } else {
            //判断如果没有员工有门店就查询门店
            if ($store != '') {
                $where[] = ['o.store_code', '=', $store];
            } else {
                //判断如果没有员工也没有门店就查询组织机构,否则就查询登入人的管理机构
                if ($org != '') {
                    $ew = new ErpWhere($db, $org);
                    $stores = $ew->org()->store()->get();
                    $a_org = explode(',', $org);
                    if (in_array('ZZJG15459825114064', $a_org)) {
                        $stores = $stores . ', ';
                    }
                    $where[] = ['o.store_code', 'in', $stores];
                } else {
                    //查询当前登入人 session('info.staff')
                    $operate = Db::table($db . '.vip_staff')->where('status', 0)->where('code', session('info.staff'))->find();
                    //门店限制
                    if ($operate['code'] == 'boss') {
                        $where = true;
                    } else if ($operate['role'] == 0) {
                        $ew = new ErpWhere($db, $operate['admin_org_code']); // 'ZZJG15459825114064'
                        $stores = $ew->org()->store()->get();
                        $a_org = explode(',', $operate['admin_org_code']);
                        if (in_array('ZZJG15459825114064', $a_org)) {
                            $stores = $stores . ', ';
                        }
                        $where[] = ['o.store_code', 'in', $stores];
                    } else {
                        $where[] = ['o.operate_code', '=', $operate['code']];
                    }
                }
            }
        }
        unset($arr, $staff_code, $ew, $stores, $operate, $start, $end);
        //判断条件不存在就都查询
        if (!isset($where)) {
            $where = true;
        }
        if (!isset($vip_where)) {
            $vip_where = true;
        }
        if (!isset($w)) {
            $w = true;
        }
        if (!isset($follow_where)) {
            $follow_where = true;
        }
        //统计数量
        $count = Db::table($db . '.vip_goods_order')
            ->alias('o')
            ->leftJoin($db . '.vip_store vs', 'vs.code = o.store_code')
            ->leftJoin($db . '.vip_org vz', 'vz.code = vs.org_code')
            ->leftJoin($db . '.vip_staff vy', 'vy.code = o.operate_code')
            ->field('round(ifnull(sum(o.real_pay), 0), 2) as order_amount, o.store_code, o.operate_code,
	                    ifnull(vs.name, "无门店") vsname, vz.name vzname, vy.name vyname')
            ->where($where)
            ->where('vy.status', 0)
            ->where('o.status', 0)
            ->group('o.operate_code')
            ->count();
        // 查询数据
        $data = Db::table($db . '.vip_goods_order')
            ->alias('o')
            ->leftJoin($db . '.vip_store vs', 'vs.code = o.store_code')
            ->leftJoin($db . '.vip_org vz', 'vz.code = vs.org_code')
            ->leftJoin($db . '.vip_staff vy', 'vy.code = o.operate_code')
            ->field('round(ifnull(sum(o.real_pay), 0), 2) as order_amount, o.store_code, o.operate_code,
	                    ifnull(vs.name, "无门店") vsname, vz.name vzname, vy.name vyname')
            ->where($where)
            ->where('vy.status', 0)
            ->where('o.status', 0)
            ->group('o.operate_code')
            ->order('order_amount', 'desc')
            ->page($page, $limit)
            ->select();
        // 更改格式
        foreach ($data as $k => $v) {
            //新增会员人数
            $data[$k]['vip_number'] = Db::table($db . '.vip_viplist')
                ->where($vip_where)
                ->where('consultant_code', $v['operate_code'])
                ->count();
            //跟进人数
            $data[$k]['follow_up'] = Db::table($db . '.vip_returnvisit_record')
                ->where($follow_where)
                ->where('visit_operator', $v['operate_code'])
                ->group('user_code')
                ->count();
            $data[$k]['staff_name'] = $v['vyname'] . ' (' . $v['vzname'] . '-' . $v['vsname'] . ')'; // 员工
            $data[$k]['order_amount_g'] = number_format($v['order_amount'], 2, '.', ''); // 会员订单金额
            $data[$k]['repeat_purchase'] = 0; // 复购人数
            $data[$k]['re_purchase_amount'] = 0; // 复购金额
            $data[$k]['amount_order_total'] = 0; // 复购人的总金额
            $data[$k]['amount_order_first'] = 0; // 第一次金额
            $data[$k]['total_status'] = 1; // 区分合计
        }
        //更改格式,组装 (复购人数)
        foreach ($data as $k => $v) {
            $member_order = Db::table($db . '.vip_goods_order')
                ->alias('o')
                ->field('count(o.vip_code) num, o.real_pay, round(ifnull(sum(o.real_pay), 0), 2) as r_pay')
                ->where('o.operate_code', $v['operate_code'])
                ->where('o.status', 0)
                ->where($w)
                ->where('o.vip_code', '<>', '非会员')
                ->group('o.vip_code')
                ->select();
            foreach ($member_order as $val) {
                if ($val['num'] > 1) {
                    $data[$k]['repeat_purchase'] += 1;
                    $data[$k]['amount_order_total'] += $val['r_pay'];
                    $data[$k]['amount_order_first'] += $val['real_pay'];
                }
            }
        }
        //更改格式,组装 (复购金额, 复购金额占比(%), 复购会员占比(%))
        foreach ($data as $k => $v) {
            if ($v['repeat_purchase'] == 0 || $v['order_amount'] == 0) {
                $data[$k]['re_purchase_amount'] = 0;
                $data[$k]['amount_proportion'] = 0;
            } else {
                $data[$k]['re_purchase_amount'] = number_format($v['amount_order_total'] - $v['amount_order_first'], 2, '.', ''); //复购金额
                $data[$k]['amount_proportion'] = number_format((($v['amount_order_total'] - $v['amount_order_first']) / $v['order_amount']) * 100); //金额比例
            }
        }
        //销售金额排序
        // array_multisort(array_column($data, 'order_amount'), SORT_DESC, $data);
        //清除变量
        unset($page, $limit, $db, $staff, $store, $org, $member_order);
        //格式化数据
        $data = [
            'data' => $data,
            'count' => $count
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 计算总数据
     */
    public function RepurchaseTotal()
    {
        //获取的数据
        [$db, $start, $end, $staff, $store, $org] =
            [$this->db, input('start'), input('end'), input('staff'), input('store'), input('splb')];
        //时间限制
        if ($start != '') {
            // $where[] = ['o.create_time', '>=', strtotime($start)];
            $w[] = ['o.create_time', '>=', strtotime($start)];
            $vip_where[] = ['date_registration', '>=', strtotime($start)]; //消费人数时间条件
            $follow_where[] = ['time', '>=', strtotime($start)]; //跟进人数时间条件
        }
        if ($end != '') {
            // $where[] = ['o.create_time', '<=', strtotime($end)];
            $w[] = ['o.create_time', '<=', strtotime($end)];
            $vip_where[] = ['date_registration', '<=', strtotime($end)];
            $follow_where[] = ['time', '<=', strtotime($end)];
        }
        //判断如果有员工就查询员工
        if ($staff != '') {
            $where[] = ['o.operate_code', '=', $staff];
        } else {
            //判断如果没有员工有门店就查询门店
            if ($store != '') {
                $where[] = ['o.store_code', '=', $store];
            } else {
                //判断如果没有员工也没有门店就查询组织机构,否则就查询登入人的管理机构
                if ($org != '') {
                    $ew = new ErpWhere($db, $org);
                    $stores = $ew->org()->store()->get();
                    $a_org = explode(',', $org);
                    if (in_array('ZZJG15459825114064', $a_org)) {
                        $stores = $stores . ', ';
                    }
                    $where[] = ['o.store_code', 'in', $stores];
                } else {
                    //查询当前登入人 session('info.staff')
                    $operate = Db::table($db . '.vip_staff')->where('status', 0)->where('code', session('info.staff'))->find();
                    //门店限制
                    if ($operate['code'] == 'boss') {
                        $where = true;
                    } else if ($operate['role'] == 0) {
                        $ew = new ErpWhere($db, $operate['admin_org_code']); // 'ZZJG15459825114064'
                        $stores = $ew->org()->store()->get();
                        $a_org = explode(',', $operate['admin_org_code']);
                        if (in_array('ZZJG15459825114064', $a_org)) {
                            $stores = $stores . ', ';
                        }
                        $where[] = ['o.store_code', 'in', $stores];
                    } else {
                        $where[] = ['o.operate_code', '=', $operate['code']];
                    }
                }
            }
        }
        unset($arr, $staff_code, $ew, $stores, $operate, $start, $end);
        //判断条件不存在就都查询
        if (!isset($where)) {
            $where = true;
        }
        if (!isset($vip_where)) {
            $vip_where = true;
        }
        if (!isset($w)) {
            $w = true;
        }
        if (!isset($follow_where)) {
            $follow_where = true;
        }
        //计算订单中的员工
        $data_operate = Db::table($db . '.vip_goods_order')
            ->alias('o')
            ->leftJoin($db . '.vip_staff vy', 'vy.code = o.operate_code')
            ->field('o.operate_code')
            ->where('o.status', 0)
            ->where($w)
            ->where($where)
            ->where('vy.status', 0)
            ->group('o.operate_code')
            ->select();
        //取出员工的code计算总计 条件
        $data_staff = "";
        $data_staff = implode(',', array_column($data_operate, 'operate_code'));
        $data_staff_array = array_column($data_operate, 'operate_code');
        // 按条件计算总数
        //总金额
        $totaldata = Db::table($db . '.vip_goods_order')
            ->alias('o')
            ->leftJoin($db . '.vip_staff vy', 'vy.code = o.operate_code')
            ->field('round(ifnull(sum(o.real_pay), 0), 2) as order_amount')
            ->where($w)
            ->where($where)
            ->where('o.status', 0)
            ->where('vy.status', 0)
            ->find();
        //新增会员人数
        $vip_number = Db::table($db . '.vip_viplist')
            ->where($vip_where) //时间条件
            ->where('consultant_code', 'in', $data_staff) //限制条件
            ->count();
        //跟进人数
        $follow_up = Db::table($db . '.vip_returnvisit_record')
            ->where($follow_where) //时间条件
            ->where('visit_operator', 'in', $data_staff) //限制条件
            ->group('user_code')
            ->count();
        //初始数据
        $totaldata = array_merge($totaldata, [
            'staff_name' => '总数(合计)', //名称
            'operate_code' => $data_staff,
            'vip_number' => $vip_number, //新增会员人数
            'follow_up' => $follow_up, //跟进人数
            'repeat_purchase'  => 0, //复购人数
            'amount_order_total' => 0, //复购人的总金额
            'amount_order_first' => 0, //第一次金额
            're_purchase_amount' => 0, //复购金额
            'amount_proportion' => 0, //金额比例
            'total_status' => 0 // 区分合计
        ]);
        //计算 复购人的总金额 第一次金额
        foreach ($data_operate as $v) {
            $t_member_order = Db::table($db . '.vip_goods_order')
                ->alias('o')
                ->field('count(o.id) num, o.real_pay, round(ifnull(sum(o.real_pay), 0), 2) as r_pay')
                ->where('o.status', 0)
                ->where($w)
                ->where('o.vip_code', '<>', '非会员')
                ->where('o.operate_code', $v['operate_code'])
                ->group('o.vip_code')
                ->select();
            foreach ($t_member_order as $val) {
                if ($val['num'] > 1) {
                    $totaldata['repeat_purchase'] += 1;
                    $totaldata['amount_order_total'] += $val['r_pay'];
                    $totaldata['amount_order_first'] += $val['real_pay'];
                }
            }
        }
        // $t_member_order = Db::table($db . '.vip_goods_order')
        //     ->alias('o')
        //     ->leftJoin($db . '.vip_viplist v', 'v.code = o.vip_code')
        //     ->field('count(o.id) num, o.real_pay, round(ifnull(sum(o.real_pay), 0), 2) as r_pay, o.vip_code, v.code as vvcode')
        //     ->where('o.operate_code', 'in', $data_staff)
        //     ->where('o.status', 0)
        //     ->where($w)
        //     ->where('o.vip_code', '<>', '非会员')
        //     ->group('o.vip_code')
        //     ->select();
        // foreach ($t_member_order as $k => $v) {
        //     if ($v['vvcode'] == '') {
        //         unset($t_member_order[$k]);
        //     }
        // }
        // sort($t_member_order);
        // foreach ($t_member_order as $val) {
        //     if ($val['num'] > 1) {
        //         $totaldata['repeat_purchase'] += 1;
        //         $totaldata['amount_order_total'] += $val['r_pay'];
        //         $totaldata['amount_order_first'] += $val['real_pay'];
        //     }
        // }
        $totaldata['re_purchase_amount'] = $totaldata['amount_order_total'] - $totaldata['amount_order_first']; //复购金额
        if ($totaldata['re_purchase_amount'] == 0 || $totaldata['order_amount'] == 0) {
            $totaldata['amount_proportion'] = 0;
            $totaldata['re_purchase_amount'] = 0;
        } else {
            $totaldata['amount_proportion'] = number_format(($totaldata['re_purchase_amount'] / $totaldata['order_amount']) * 100); //金额比例
            $totaldata['re_purchase_amount'] = number_format($totaldata['re_purchase_amount'], 2, '.', ''); //复购金额
        }
        //清除变量
        unset($db, $staff, $store, $org, $member_order,
        $repurchase_staff, $data_staff, $vip_number, $follow_up, $t_member_order);
        //返回数据
        webApi(200, 'ok', $totaldata);
    }

    /**
     * 新增会员人数
     * 有订单的员工是这个会员的形象顾问的会员
     */
    public function newMember()
    {
        //获取的数据
        [$page, $limit, $db, $start, $end, $staff, $totalStatus, $search] =
            [input('page'), input('limit'), $this->db, input('start'), input('end'), input('staff'), input('total_status'), input('search')];
        //时间限制
        if ($start != '') {
            $where[] = ['v.date_registration', '>=', strtotime($start)]; // 开始时间
        }
        if ($end != '') {
            $where[] = ['v.date_registration', '<=', strtotime($end)]; // 结束时间
        }
        if ($totalStatus == 0) {
            $where[] = ['v.consultant_code', 'in', $staff]; // 总计员工
        } else {
            $where[] = ['v.consultant_code', '=', $staff]; // 单个员工
        }
        if ($search != "") {
            $where[] = ['v.username|v.code|v.phone', 'like', '%' . $search . '%']; // 模糊搜素
        }
        //判断条件不存在就都查询
        if (!isset($where)) {
            $where = true;
        }
        //新增会员人数
        // 会员数据
        $data = Db::table($db . '.vip_viplist')
            ->alias('v')
            ->leftJoin($db . '.vip_goods_order o', 'o.vip_code = v.code')
            ->field('v.code, v.username, v.phone, v.consultant_code, v.birthday, v.img, v.date_registration, 
            ifnull(MAX(o.create_time), 0) as order_time, ifnull(round(sum(o.real_pay), 2), 0) as total_consumption, 
                        count(o.id) as consumption_times, ifnull(round(sum(o.number)), 0) as consumption_number')
            ->where('(o.status = 0 or o.status IS null)')
            ->where($where)
            ->group('v.code')
            ->select();
        $t = time();
        $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
        foreach ($data as $k => $v) {
            if ($v['order_time'] >= $start) {
                $data[$k]['rfm_days'] = 0;
            } else {
                $data[$k]['rfm_days'] = intval(round((time() - $v['order_time']) / 86400));
            }
        }
        $count = count($data);
        //基本配置
        $sysCon = Db::table($this->db . '.vip_sys_con')->find();
        foreach ($data as $k => $v) {
            if ($v['rfm_days'] > 15000) {
                $data[$k]['rfm_days'] = '未消费';
            }
            if ($sysCon['yincang_is_phone'] == "on") {
                if ($v['phone'] != "") {
                    $data[$k]['phone_g'] = substr($v['phone'], 0, 3) . '****' . substr($v['phone'], 7);
                }
            } else {
                $data[$k]['phone_g'] = $v['phone'];
            }
        }
        //清除变量
        unset($page, $limit, $db, $staff, $where, $totalStatus);
        //格式化数据
        $data = [
            'data' => $data,
            'count' => $count
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 跟进人数
     */
    public function followMember()
    {
        //获取的数据
        [$page, $limit, $db, $start, $end, $staff, $totalStatus, $search] =
            [input('page'), input('limit'), $this->db, input('start'), input('end'), input('staff'), input('total_status'), input('search')];
        //卡号,姓名
        if ($search != '') {
            $where[] = ['o.user_name|o.user_code', 'like', '%' . $search . '%']; // 模糊搜索
        }
        //时间限制
        if ($start != '') {
            $where[] = ['o.time', '>=', strtotime($start)]; // 开始时间
        }
        if ($end != '') {
            $where[] = ['o.time', '<=', strtotime($end)]; // 结束时间
        }
        if ($totalStatus == 0) {
            $where[] = ['o.visit_operator', 'in', $staff]; // 总计员工
        } else {
            $where[] = ['o.visit_operator', '=', $staff]; // 单个员工
        }
        //判断条件不存在就都查询
        if (!isset($where)) {
            $where = true;
        }
        //跟进人数
        $count = Db::table($db . '.vip_returnvisit_record')
            ->alias('o')
            ->leftJoin($db . '.vip_viplist v', 'v.code = o.user_code')
            ->where($where)
            ->count();
        //查询数据
        $data = Db::table($db . '.vip_returnvisit_record')
            ->alias('o')
            ->leftJoin($db . '.vip_viplist v', 'v.code = o.user_code')
            ->leftJoin($db . '.vip_staff vs', 'vs.code = o.visit_operator')
            ->field('o.user_name, o.user_code, o.visit_operator, o.visit_mode, o.time, o.content, v.img img, vs.name vsname')
            ->where($where)
            ->order('o.time', 'desc')
            ->page($page, $limit)
            ->select();
        //格式化数据
        foreach ($data as $k => $v) {
            if ($v['time'] == 0) {
                $data[$k]['time_g'] = '无时间';
            } else {
                $data[$k]['time_g'] = date('Y-m-d H:i:s', $v['time']);
            }
        }
        //清除变量
        unset($page, $limit, $db, $staff, $where, $totalStatus);
        //格式化数据
        $data = [
            'data' => $data,
            'count' => $count
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 回购人数
     * 订单中有这个会员,而会员列表中没有这个会员
     */
    public function buyBack()
    {
        //获取的数据
        [$page, $limit, $db, $start, $end, $staff, $totalStatus] =
            [input('page'), input('limit'), $this->db, input('start'), input('end'), input('staff'), input('total_status')];
        //时间限制
        if ($start != '') {
            $where[] = ['o.create_time', '>=', strtotime($start)]; // 开始时间
        }
        if ($end != '') {
            $where[] = ['o.create_time', '<=', strtotime($end)]; // 结束时间
        }
        if ($totalStatus == 0) {
            $where[] = ['o.operate_code', 'in', $staff]; // 总计员工
        } else {
            $where[] = ['o.operate_code', '=', $staff]; // 单个员工
        }
        //判断条件不存在就都查询
        if (!isset($where)) {
            $where = true;
        }
        $data = Db::table($db . '.vip_goods_order')
            ->alias('o')
            ->leftJoin($db . '.vip_viplist v', 'v.code = o.vip_code')
            ->field('o.operate_code, o.vip_code, count(o.id) consumption_times, round(ifnull(o.real_pay, 0), 2) as real_pay, 
                    round(ifnull(sum(o.real_pay), 0), 2) as total_consumption,
                    ifnull(MAX(o.create_time), 0) as order_time, 
                    ifnull(round(sum(o.number)), 0) as consumption_number,
                    v.username, v.phone, v.birthday, v.img, v.code as code')
            ->where('o.status', 0)
            ->where($where)
            ->where('o.vip_code', '<>', '非会员')
            ->group('o.vip_code')
            ->select();
        foreach ($data as $k => $v) {
            if ($v['consumption_times'] <= 1) {
                unset($data[$k]);
            }
        }
        sort($data);
        foreach ($data as $k => $v) {
            if ($v['code'] == "") {
                unset($data[$k]);
            }
        }
        sort($data);
        if ($data) {
            foreach ($data as $k => $v) {
                $data[$k]['r_pay'] = $v['total_consumption'] - $v['real_pay'];
            }
            $t = time();
            $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
            foreach ($data as $k => $v) {
                if ($v['order_time'] >= $start) {
                    $data[$k]['rfm_days'] = 0;
                } else {
                    $data[$k]['rfm_days'] = intval(round((time() - $v['order_time']) / 86400));
                }
            }
            $count = count($data);
            //基本配置
            $sysCon = Db::table($this->db . '.vip_sys_con')->find();
            foreach ($data as $k => $v) {
                if ($v['rfm_days'] > 15000) {
                    $data[$k]['rfm_days'] = '未消费';
                }
                if ($sysCon['yincang_is_phone'] == "on") {
                    if ($v['phone'] != "") {
                        $data[$k]['phone_g'] = substr($v['phone'], 0, 3) . '****' . substr($v['phone'], 7);
                    }
                } else {
                    $data[$k]['phone_g'] = $v['phone'];
                }
                if (strlen($v['birthday']) == 8) {
                    $data[$k]['birthday_g'] = date('Y-m-d', strtotime($v['birthday']));
                } else if ($v['birthday'] == 0) {
                    $data[$k]['birthday_g'] = '无';
                } else {
                    $data[$k]['birthday_g'] = date('Y-m-d', $v['birthday']);
                }
                $data[$k]['r_pay'] = number_format($v['r_pay'], 2, '.', '');
            }
        } else {
            $data = [];
            $count = 0;
        }
        //清除变量
        unset($page, $limit, $db, $staff, $where, $totalStatus);
        //格式化数据
        $data = [
            'data' => $data,
            'count' => $count
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }
}
