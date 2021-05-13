<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 *  Author lxy
 * Date 2019/12/25
 * Description 目标达成
 */
class MAttainment extends Common
{
    /**
     * 目标达成
     */
    public function attainmentSel()
    {
        //获取的数据
        [$page, $limit, $db, $timeMonth, $staff, $store, $org] =
            [input('page'), input('limit'), $this->db, input('time_month'), input('staff'), input('store'), input('splb')];
        //时间限制
        if ($timeMonth != '') {
            //格式化时间
            $timestamp = strtotime($timeMonth);
            $start_time = date('Y-m-1 00:00:00', $timestamp); // 开始时间
            $where[] = ['o.create_time', '>=', strtotime($start_time)];
            $mdays = date('t', $timestamp);
            $end_time = date('Y-m-' . $mdays . ' 23:59:59', $timestamp); // 结束时间
            $where[] = ['o.create_time', '<=', strtotime($end_time)];
            //任务计划时间 分割时间
            $missionTime = explode('-', $timeMonth);
            $missionW[] = ['year', '=', $missionTime['0']]; //年
            $missionW[] = ['month', '=', str_replace("0", "", $missionTime['1'])]; //月
            $missionWhere[] = ['year', '=', $missionTime['0']]; //年
            $missionWhere[] = ['month', '=', str_replace("0", "", $missionTime['1'])]; //月
            //总计任务计划时间
            $missionTotalWhere[] = ['o.year', '=', $missionTime['0']]; //年
            $missionTotalWhere[] = ['o.month', '=', $missionTime['1']]; //月
            //同比时间 今年与去年比 (现在/之前)
            $sameTime = strtotime("-1 years", $timestamp);
            $sameStartTime = date('Y-m-1 00:00:00', $sameTime); // 开始时间
            $sameWhere[] = ['create_time', '>=', strtotime($sameStartTime)];
            $sameTotalWhere[] = ['o.create_time', '>=', strtotime($sameStartTime)];
            $sameMdays = date('t', $sameTime);
            $sameEndTime = date('Y-m-' . $sameMdays . ' 23:59:59', $sameTime); // 结束时间
            $sameWhere[] = ['create_time', '<=', strtotime($sameEndTime)];
            $sameTotalWhere[] = ['o.create_time', '<=', strtotime($sameEndTime)];
            //环比时间 这个月与上个月比
            $ringTime = strtotime("-1 month", $timestamp);
            $ringStartTime = date('Y-m-1 00:00:00', $ringTime); // 开始时间
            $ringWhere[] = ['create_time', '>=', strtotime($ringStartTime)];
            $ringTotalWhere[] = ['o.create_time', '>=', strtotime($ringStartTime)];
            $ringMdays = date('t', $ringTime);
            $ringEndTime = date('Y-m-' . $ringMdays . ' 23:59:59', $ringTime); // 结束时间
            $ringWhere[] = ['create_time', '<=', strtotime($ringEndTime)];
            $ringTotalWhere[] = ['o.create_time', '<=', strtotime($ringEndTime)];
        }
        unset($timestamp, $start_time, $mdays, $end_time,
        $missionTime,
        $sameTime, $sameStartTime, $sameMdays, $sameEndTime,
        $ringTime, $ringStartTime, $ringMdays, $ringEndTime);
        //判断如果有员工就查询员工
        if ($staff != '') {
            $where[] = ['o.operate_code', '=', $staff];

            $sameTotalWhere[] = ['o.operate_code', '=', $staff];
            $ringTotalWhere[] = ['o.operate_code', '=', $staff];
            $missionTotalWhere[] = ['o.staff_code', '=', $staff];

            $sameWhere[] = ['operate_code', '=', $staff];
            $ringWhere[] = ['operate_code', '=', $staff];
            $missionWhere[] = ['staff_code', '=', $staff];
        } else {
            //判断如果没有员工有门店就查询门店
            if ($store != '') {
                $staff_code = Db::table($db . '.vip_staff')->where('status', 0)->where('store_code', $store)->field('code')->select();
                $arr = implode(',', array_column($staff_code, 'code'));
                $where[] = ['o.operate_code', 'in', $arr];

                $sameTotalWhere[] = ['o.operate_code', 'in', $arr];
                $ringTotalWhere[] = ['o.operate_code', 'in', $arr];
                $missionTotalWhere[] = ['o.staff_code', 'in', $arr];

                $sameWhere[] = ['operate_code', 'in', $arr];
                $ringWhere[] = ['operate_code', 'in', $arr];
                $missionWhere[] = ['staff_code', 'in', $arr];
            } else {
                //判断如果没有员工也没有门店就查询组织机构,否则就查询登入人的管理机构
                if ($org != '') {
                    $ew = new ErpWhere($db, $org);
                    $stores = $ew->org()->store()->get();
                    $staff_code = Db::table($db . '.vip_staff')->where('status', 0)->where('store_code', 'in', $stores)->field('code')->select();
                    $arr = implode(',', array_column($staff_code, 'code'));
                    $where[] = ['o.operate_code', 'in', $arr];

                    $sameTotalWhere[] = ['o.operate_code', 'in', $arr];
                    $ringTotalWhere[] = ['o.operate_code', 'in', $arr];
                    $missionTotalWhere[] = ['o.staff_code', 'in', $arr];

                    $sameWhere[] = ['operate_code', 'in', $arr];
                    $ringWhere[] = ['operate_code', 'in', $arr];
                    $missionWhere[] = ['staff_code', 'in', $arr];
                } else {
                    //查询当前登入人 session('info.staff')
                    $operate = Db::table($db . '.vip_staff')->where('status', 0)->where('code', session('info.staff'))->find();
                    //门店限制
                    if ($operate['code'] == 'boss') {
                        $staff_code = Db::table($db . '.vip_staff')->where('status', 0)->field('code')->select();
                        if ($staff_code) {
                            $arr = implode(',', array_column($staff_code, 'code'));
                            $where[] = ['o.operate_code', 'in', $arr];

                            $sameTotalWhere[] = ['o.operate_code', 'in', $arr];
                            $ringTotalWhere[] = ['o.operate_code', 'in', $arr];
                            $missionTotalWhere[] = ['o.staff_code', 'in', $arr];

                            $sameWhere[] = ['operate_code', 'in', $arr];
                            $ringWhere[] = ['operate_code', 'in', $arr];
                            $missionWhere[] = ['staff_code', 'in', $arr];
                        }
                    } else if ($operate['role'] == 0) {
                        $ew = new ErpWhere($db, $operate['admin_org_code']); // 'ZZJG15459825114064'
                        $stores = $ew->org()->store()->get();
                        $a_org = explode(',', $operate['admin_org_code']);
                        if (in_array('ZZJG15459825114064', $a_org)) {
                            $stores = $stores . ', ';
                        }
                        $staff_code = Db::table($db . '.vip_staff')->where('status', 0)->where('store_code', 'in', $stores)->field('code')->select();
                        if ($staff_code) {
                            $arr = implode(',', array_column($staff_code, 'code'));
                            $where[] = ['o.operate_code', 'in', $arr];

                            $sameTotalWhere[] = ['o.operate_code', 'in', $arr];
                            $ringTotalWhere[] = ['o.operate_code', 'in', $arr];
                            $missionTotalWhere[] = ['o.staff_code', 'in', $arr];

                            $sameWhere[] = ['operate_code', 'in', $arr];
                            $ringWhere[] = ['operate_code', 'in', $arr];
                            $missionWhere[] = ['staff_code', 'in', $arr];
                        } else {
                            $where[] = ['o.operate_code', '=', $operate['code']];

                            $sameTotalWhere[] = ['o.operate_code', '=', $operate['code']];
                            $ringTotalWhere[] = ['o.operate_code', '=', $operate['code']];
                            $missionTotalWhere[] = ['o.staff_code', '=', $operate['code']];

                            $sameWhere[] = ['operate_code', '=', $operate['code']];
                            $ringWhere[] = ['operate_code', '=', $operate['code']];
                            $missionWhere[] = ['staff_code', '=', $operate['code']];
                        }
                    } else {
                        $where[] = ['o.operate_code', '=', $operate['code']];

                        $sameTotalWhere[] = ['o.operate_code', '=', $operate['code']];
                        $ringTotalWhere[] = ['o.operate_code', '=', $operate['code']];
                        $missionTotalWhere[] = ['o.staff_code', '=', $operate['code']];

                        $sameWhere[] = ['operate_code', '=', $operate['code']];
                        $ringWhere[] = ['operate_code', '=', $operate['code']];
                        $missionWhere[] = ['staff_code', '=', $operate['code']];
                    }
                }
            }
        }
        //判断条件不存在就都查询
        if (!isset($where)) {
            $where = true;
        }
        if (!isset($sameTotalWhere)) {
            $sameTotalWhere = true;
        }
        if (!isset($ringTotalWhere)) {
            $ringTotalWhere = true;
        }
        if (!isset($missionTotalWhere)) {
            $missionTotalWhere = true;
        }
        if (!isset($sameWhere)) {
            $sameWhere = true;
        }
        if (!isset($ringWhere)) {
            $ringWhere = true;
        }
        if (!isset($missionWhere)) {
            $missionWhere = true;
        }
        if (!isset($missionW)) {
            $missionW = true;
        }
        // dump($missionW);
        // exit;
        $count = Db::table($db . '.vip_goods_order')
            ->alias('o')
            ->leftJoin($db . '.vip_store vs', 'vs.code = o.store_code')
            ->leftJoin($db . '.vip_staff vy', 'vy.code = o.operate_code')
            ->field('round(sum(o.real_pay), 2) realNumber,
	                    o.create_time, o.store_code, o.operate_code,
                        ifnull(vs.name, "无门店") vsname, vy.name vyname')
            ->where('o.status', 0)
            ->where($where)
            ->where('vy.status', 0)
            ->group('o.operate_code')
            ->count();
        // 查询的数据
        $data = Db::table($db . '.vip_goods_order')
            ->alias('o')
            ->leftJoin($db . '.vip_store vs', 'vs.code = o.store_code')
            ->leftJoin($db . '.vip_staff vy', 'vy.code = o.operate_code')
            ->field('round(sum(o.real_pay), 2) realNumber,
	                    o.create_time, o.store_code, o.operate_code,
                        ifnull(vs.name, "无门店") vsname, vy.name vyname')
            ->where('o.status', 0)
            ->where($where)
            ->where('vy.status', 0)
            ->group('o.operate_code')
            ->page($page, $limit)
            ->select();
        //增加所需数据
        foreach ($data as $k => $v) {
            $data[$k]['totalMission'] = 0; // 基本目标数量
            $data[$k]['completion'] = 0; // 达成率
            $data[$k]['sameNumber'] = 0; // 同比金额
            $data[$k]['ringNumber'] = 0; // 环比金额
        }
        //计算数据
        foreach ($data as $k => $v) {
            $data[$k]['sameNumber'] = Db::table($this->db . '.vip_goods_order')
                ->field('round(ifnull(sum(real_pay), 0), 2) as sameNumber')
                ->where('status', 0)
                ->where('operate_code', $v['operate_code'])
                ->where($sameWhere)
                ->find()['sameNumber']; // 同比金额
            $data[$k]['ringNumber'] = Db::table($this->db . '.vip_goods_order')
                ->field('round(ifnull(sum(real_pay), 0), 2) as ringNumber')
                ->where('status', 0)
                ->where('operate_code', $v['operate_code'])
                ->where($ringWhere)
                ->find()['ringNumber']; // 环比金额
            //任务计划基本目标
            $staffMission = Db::table($this->db . '.vip_staff_mission')
                ->where('mission_type', 1)
                ->where('staff_code', $v['operate_code'])
                ->where($missionW)
                ->find();
            if ($staffMission) {
                //基本目标数量
                if ($v['operate_code'] == $staffMission['staff_code']) {
                    $data[$k]['totalMission'] += $staffMission['total_number'];
                }
            }
        }
        //计算达成率,同比(现在/之前),环比
        foreach ($data as $k => $v) {
            if ($v['totalMission'] == 0 || $v['realNumber'] == 0) { // 达成率
                $data[$k]['completion'] = 0;
            } else {
                $data[$k]['completion'] = number_format(($v['realNumber'] / $v['totalMission']) * 100);
            }
            if ($v['sameNumber'] == 0 || $v['realNumber'] == 0) { // 同比
                $data[$k]['sameRate'] = 0;
            } else {
                $data[$k]['sameRate'] = number_format(($v['realNumber'] / $v['sameNumber']) * 100, 2);
            }
            if ($v['ringNumber'] == 0 || $v['realNumber'] == 0) { // 环比
                $data[$k]['ringRate'] = 0;
            } else {
                $data[$k]['ringRate'] = number_format(($v['realNumber'] / $v['ringNumber']) * 100, 2);
            }
        }
        //计算总数
        $realNumber = Db::table($this->db . '.vip_goods_order')
            ->alias('o')
            ->leftJoin($db . '.vip_staff vy', 'vy.code = o.operate_code')
            ->field('round(ifnull(sum(o.real_pay), 0), 2) as realNumber')
            ->where('o.status', 0)
            ->where($where)
            ->where('vy.status', 0)
            ->find()['realNumber'];
        $sameNumber = Db::table($this->db . '.vip_goods_order')
            ->alias('o')
            ->leftJoin($db . '.vip_staff vy', 'vy.code = o.operate_code')
            ->field('round(ifnull(sum(o.real_pay), 0), 2) as sameNumber')
            ->where('o.status', 0)
            ->where('vy.status', 0)
            ->where($sameTotalWhere)
            ->find(); // 同比金额
        $ringNumber = Db::table($this->db . '.vip_goods_order')
            ->alias('o')
            ->leftJoin($db . '.vip_staff vy', 'vy.code = o.operate_code')
            ->field('round(ifnull(sum(o.real_pay), 0), 2) as ringNumber')
            ->where('o.status', 0)
            ->where('vy.status', 0)
            ->where($ringTotalWhere)
            ->find(); // 环比金额 
        $staffMission = Db::table($this->db . '.vip_staff_mission')
            ->alias('o')
            ->leftJoin($db . '.vip_staff vy', 'vy.code = o.staff_code')
            ->where('vy.status', 0)
            ->where('o.mission_type', 1)
            ->where($missionWhere)
            ->select();
        $totalMission = 0;
        foreach ($staffMission as $k => $v) {
            $totalMission += $v['total_number'];
        }
        $totalData = [
            "realNumber" => $realNumber,
            "vsname" => '无门店',
            "vyname" => '总数',
            "totalMission" => $totalMission,
            "completion" => 0,
            "sameRate" => 0,
            "ringRate" => 0,
        ];
        if ($totalMission == 0 || $realNumber == 0) { // 达成率
            $totalData['completion'] = 0;
        } else {
            $totalData['completion'] = number_format(($realNumber / $totalMission) * 100);
        }
        if ($sameNumber['sameNumber'] == 0 || $realNumber == 0) { // 同比
            $totalData['sameRate'] = 0;
        } else {
            $totalData['sameRate'] = number_format(($realNumber / $sameNumber['sameNumber']) * 100, 2);
        }
        if ($ringNumber['ringNumber'] == 0 || $realNumber == 0) { // 环比
            $totalData['ringRate'] = 0;
        } else {
            $totalData['ringRate'] = number_format(($realNumber / $ringNumber['ringNumber']) * 100, 2);
        }
        //清除变量
        unset($page, $limit, $db, $timeMonth, $staff, $store, $org,
        $where, $totalWhere, $sameWhere, $ringWhere, $missionWhere,
        $staff_code, $arr, $es, $stores, $operate,
        $staffMission, $realNumber, $sameNumber, $ringNumber, $sameTotalMission, $ringTotalWhere, $missionTotalWhere);
        //格式化数据
        $data = [
            'totalData' => $totalData,
            'data' => $data,
            'count' => $count
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }
}
