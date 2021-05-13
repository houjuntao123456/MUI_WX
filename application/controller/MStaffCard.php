<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 * Author lxy
 * Date 2020/09/03
 * Description 员工开卡任务
 */
class MStaffCard extends Common
{
    /**
     * 目标达成
     */
    public function MStaffCardSel()
    {
        //获取的数据
        [$page, $limit, $db, $staff, $store, $org, $staffCode] = 
        [input('page'), input('limit'), $this->db, input('staff'), input('store'), input('splb'), ''];
        //时间限制 (时间必须有,否则无法进行)
        if (input('start') != '') {
            //分割时间
            $start = explode('-', input('start'));
            $startNumber = intval($start['2']); // 日
            $vipwhere[] = ['date_registration', '>=', strtotime(input('start'))];
        }
        if (input('end') != '') {
            //分割时间
            $end = explode('-', input('end'));
            $endNumber = intval($end['2']); // 日
            $vipwhere[] = ['date_registration', '<=', strtotime(input('end'))];
        }
        /**
         *  由于计划任务表原因与时间变化多, 会出现四种情况:
         */
        $timeStatus = 0;
        if ($start['0'] == $end['0'] && $start['1'] == $end['1']) { //同年同月就一种情况
            //等于同年等于同月
            $timeWhere[] = ['year', '=', $start['0']]; //年
            $timeWhere[] = ['month', '=', $start['1']]; //月
            $timeStatus = 1;
        } else if ($start['0'] == $end['0'] && $start['1'] != $end['1']) { //同年不同月就一种情况
            //等于同年大于等开始月小于等于结束月
            $timeWhere[] = ['year', '=', $start['0']]; //年
            $timeWhere[] = ['month', '>=', $start['1']]; //开始月
            $timeWhere[] = ['month', '<=', $end['1']]; //结束月
            $timeStatus = 2;
        } else if ($start['0'] != $end['0'] && $start['1'] != $end['1']) { //不同年不同月 分开两种情况  
            //等于开始年&&大于等于开始月
            $timeWhere[] = ['year', '>=', $start['0']]; //年
            $timeWhere[] = ['month', '>=', $start['1']]; //月
            //等于结束年&&小于等于结束月
            $timeWhere_two[] = ['year', '<=', $end['0']]; //年
            $timeWhere_two[] = ['month', '<=', $end['1']]; //月
            $timeStatus = 2;
        } else if ($start['0'] != $end['0'] && $start['1'] == $end['1']) { //不同年同月 分开两种情况
            //等于开始年&&大于等于开始月
            $timeWhere[] = ['year', '>=', $start['0']]; //年
            $timeWhere[] = ['month', '>=', $start['1']]; //月
            //等于结束年&&小于等于结束月
            $timeWhere_two[] = ['year', '<=', $end['0']]; //年
            $timeWhere_two[] = ['month', '<=', $end['1']]; //月
            $timeStatus = 2;
        }
        //判断条件不存在就都查询
        if (!isset($timeWhere)) {
            $timeWhere = true;
        }
        if (!isset($timeWhere_two)) {
            $timeWhere_two = true;
        }
        unset($timestamp, $start_time, $mdays, $end_time);
        //判断如果有员工就查询员工
        if ($staff != '') {
            $where[] = ['y.code', '=', $staff];
            $staffCode = $staff;
        } else {
            //判断如果没有员工有门店就查询门店
            if ($store != '') {
                $staff_code = Db::table($db . '.vip_staff')->where('status', 0)->where('store_code', $store)->field('code')->select();
                $arr = implode(',', array_column($staff_code, 'code'));
                $where[] = ['y.code', 'in', $arr];
                $staffCode = $arr;
            } else {
                //判断如果没有员工也没有门店就查询组织机构,否则就查询登入人的管理机构
                if ($org != '') {
                    $ew = new ErpWhere($db, $org);
                    $stores = $ew->org()->store()->get();
                    $staff_code = Db::table($db . '.vip_staff')->where('status', 0)->where('store_code', 'in', $stores)->field('code')->select();
                    $arr = implode(',', array_column($staff_code, 'code'));
                    $where[] = ['y.code', 'in', $arr];
                    $staffCode = $arr;
                } else {
                    //查询当前登入人 session('info.staff')
                    $operate = Db::table($db . '.vip_staff')->where('status', 0)->where('code', session('info.staff'))->find();
                    //门店限制
                    if ($operate['code'] == 'boss') {
                        $staff_code = Db::table($db . '.vip_staff')->where('status', 0)->field('code')->select();
                        if ($staff_code) {
                            $arr = implode(',', array_column($staff_code, 'code'));
                            $where[] = ['y.code', 'in', $arr];
                            $staffCode = $arr;
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
                            $where[] = ['y.code', 'in', $arr];
                            $staffCode = $arr;
                        } else {
                            $where[] = ['y.code', '=', $operate['code']];
                            $staffCode = $operate['code'];
                        }
                    } else {
                        $where[] = ['y.code', '=', $operate['code']];
                        $staffCode = $operate['code'];
                    }
                }
            }
        }
        //判断条件不存在就都查询
        if (!isset($where)) {
            $where = true;
        }
        if (!isset($vipwhere)) {
            $vipwhere = true;
        }
        $count = Db::table($db . '.vip_staff')
            ->alias('y')
            ->leftJoin($db . '.vip_store vs', 'vs.code = y.store_code')
            ->field('y.name, y.code, ifnull(vs.name, "") vsname')
            ->where($where)
            ->where('y.status', 0)
            ->count();
        // 查询的数据
        $data = Db::table($db . '.vip_staff')
            ->alias('y')
            ->leftJoin($db . '.vip_store vs', 'vs.code = y.store_code')
            ->field('y.name, y.code, ifnull(vs.name, "无门店") vsname')
            ->where($where)
            ->where('y.status', 0)
            ->page($page, $limit)
            ->select();
        //增加所需数据
        foreach ($data as $k => $v) {
            $data[$k]['vipCount'] = 0; // 会员数量
            $data[$k]['totalMission'] = 0; // 目标数量
            $data[$k]['completion'] = 0; // 达成率
        }
        //计算数据
        foreach ($data as $k => $v) {
            //会员数量
            $data[$k]['vipCount'] = Db::table($this->db . '.vip_viplist')->where('consultant_code', $v['code'])->where($vipwhere)->count();
            //任务计划目标
            $staffMission = Db::table($this->db . '.vip_staff_card')->where('staff_code', $v['code'])->where($timeWhere)->select();
            if ($staffMission) {
                if (is_array($timeWhere_two)) {
                    $staffMission_two = Db::table($this->db . '.vip_staff_mission')->where('staff_code', $v['code'])->where($timeWhere_two)->select();
                } else {
                    $staffMission_two = null;
                }
                if ($staffMission_two) {
                    $staffMission = array_merge($staffMission, $staffMission_two);
                }
                foreach ($staffMission as $key => $vlaue) {
                    $staffMission[$key]['d'] = 0;
                    if ($vlaue['year'] == $start['0'] && $vlaue['month'] == $start['1']) {
                        if ($timeStatus == 2) {
                            for ($i = $startNumber; $i <= $vlaue['day']; $i++) {
                                $staffMission[$key]['d'] += $vlaue['day_' . $i];
                            }
                        } else {
                            for ($i = $startNumber; $i <= $endNumber; $i++) {
                                $staffMission[$key]['d'] += $vlaue['day_' . $i];
                            }
                        }
                    } else if ($vlaue['year'] == $end['0'] && $vlaue['month'] == $end['1']) {
                        for ($i = 1; $i <= $endNumber; $i++) {
                            $staffMission[$key]['d'] += $vlaue['day_' . $i];
                        }
                    } else {
                        for ($i = 1; $i <= $vlaue['day']; $i++) {
                            $staffMission[$key]['d'] += $vlaue['day_' . $i];
                        }
                    }
                }
                //基本目标数量
                foreach ($staffMission as $key => $val) {
                    if ($v['code'] == $val['staff_code']) {
                        $data[$k]['totalMission'] += $val['d'];
                    }
                }
            }
        }
        //计算达成率,同比(现在/之前),环比
        foreach ($data as $k => $v) {
            if ($v['totalMission'] == 0 || $v['vipCount'] == 0) { // 达成率
                $data[$k]['completion'] = 0;
            } else {
                $data[$k]['completion'] = number_format(($v['vipCount'] / $v['totalMission']) * 100);
            } 
        }
        //定义总数数组
        $totalData = [
            "vipCount" => 0,
            "vsname" => '无门店',
            "name" => '总数',
            "totalMission" => 0,
            "completion" => 0
        ];
        //计算总数
        $totalData['vipCount'] = Db::table($this->db . '.vip_viplist')
                ->where('consultant_code', 'in', $staffCode)
                ->where($vipwhere)
                ->count();
        $staffMission = Db::table($this->db . '.vip_staff_card')->where('staff_code', 'in', $staffCode)->where($timeWhere)->select();
        if ($staffMission) {
            if (is_array($timeWhere_two)) {
                $staffMission_two = Db::table($this->db . '.vip_staff_mission')->where('staff_code', 'in', $staffCode)->where($timeWhere_two)->select();
            } else {
                $staffMission_two = null;
            }
            if ($staffMission_two) {
                $staffMission = array_merge($staffMission, $staffMission_two);
            }
            foreach ($staffMission as $key => $vlaue) {
                $staffMission[$key]['d'] = 0;
                if ($vlaue['year'] == $start['0'] && $vlaue['month'] == $start['1']) {
                    if ($timeStatus == 2) {
                        for ($i = $startNumber; $i <= $vlaue['day']; $i++) {
                            $staffMission[$key]['d'] += $vlaue['day_' . $i];
                        }
                    } else {
                        for ($i = $startNumber; $i <= $endNumber; $i++) {
                            $staffMission[$key]['d'] += $vlaue['day_' . $i];
                        }
                    }
                } else if ($vlaue['year'] == $end['0'] && $vlaue['month'] == $end['1']) {
                    for ($i = 1; $i <= $endNumber; $i++) {
                        $staffMission[$key]['d'] += $vlaue['day_' . $i];
                    }
                } else {
                    for ($i = 1; $i <= $vlaue['day']; $i++) {
                        $staffMission[$key]['d'] += $vlaue['day_' . $i];
                    }
                }
            }
            //基本目标数量
            foreach ($staffMission as $key => $val) {
                $totalData['totalMission'] += $val['d'];
            }
        }
        if ($totalData['vipCount'] == 0 || $totalData['totalMission'] == 0) { // 达成率
            $totalData['completion'] = 0;
        } else {
            $totalData['completion'] = number_format(($totalData['vipCount'] / $totalData['totalMission']) * 100);
        }
        //清除变量
        unset($page, $limit, $db, $timeMonth, $staff, $store, $org, 
                $where, $vipWhere, $staffCode, $staff_code, $arr, $es, $stores, $operate,
                $staffMission, $timeStatus, $timeWhere, $timeWhere_two, $staffMission_two, $startNumber
            );
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
