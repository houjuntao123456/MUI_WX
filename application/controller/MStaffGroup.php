<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 * Author lxy
 * Date 2019/12/17
 * Description 员工PK分组
 */
class MStaffGroup extends Common
{
    /**
     * 员工PK分组
     */
    public function groupSel()
    {
        // 获取分页数据
        [$page, $limit, $search, $staff] = [input('page'), input('limit'), input('search'), input('staff_code')];
        //模糊查询
        if ($search != '') {
            $where[] = ['name', 'like', '%' . $search . '%'];
        }
        if ($staff != '') {
            $where[] = ['staff_code_all', 'like', '%' . $staff . '%'];
            $staffName = Db::table($this->db . '.vip_staff')->where('code', $staff)->find()['name'];
            $where[] = ['staff_name_all', 'like', '%' . $staffName . '%'];
        }
        //如果都没有条件
        if ($search == "" && $staff == "") {
            $where[] = ['staff_code_all', 'like', '%' . session('info.staff') . '%']; // session('info.staff')
            $staffName = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find()['name'];
            $where[] = ['staff_name_all', 'like', '%' . $staffName . '%'];
            // //查询当前登入人 session('info.staff')
            // $operate = Db::table($this->db . '.vip_staff')->where('status', 0)->where('code', session('info.staff'))->find();
            // //门店限制
            // if ($operate['code'] == 'boss') {
            //     $ws = true;
            // } else if ($operate['role'] == 0) {
            //     $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            //     $stores = $ew->org()->store()->get();
            //     $a_org = explode(',', $operate['admin_org_code']);
            //     if (in_array('ZZJG15459825114064', $a_org)) {
            //         $stores = $stores . ', ';
            //     }
            //     if ($stores) {
            //         $ws[] = ['store_code', 'in', $stores];
            //     } else {
            //         //返回数据
            //         $ws[] = ['store_code', 'in', ""];
            //     }
            // } else {
            //     $ws[] = ['store_code', '=', $operate['store_code']];
            // }
            // //查询数据
            // $data = Db::table($this->db . '.vip_staff')
            //     ->field('name, code')
            //     ->where('status', 0)
            //     ->where($ws)
            //     ->select();
            // foreach ($data as $k => $v) {
            //     $where[] = ['staff_code_all', 'like', '%' . $v['code'] . '%'];
            //     $where[] = ['staff_name_all', 'like', '%' . $v['name'] . '%'];
            // }
        }
        //判断条件不存在就都查询
        if (!isset($where)) {
            $where = true;
        }
        //统计数量
        $count = Db::table($this->db . '.vip_staff_grouping')
            ->where($where)
            ->count();
        //查询的数据
        $data = Db::table($this->db . '.vip_staff_grouping')
            ->where($where)
            ->order('create_time', 'desc') //按照创建时间降序排列
            ->page($page, $limit)
            ->select();
        //清除变量
        unset($page, $limit, $where);
        //格式化数据
        $data = [
            'data' => $data,
            'count' => $count
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 员工下拉框
     */
    public function staffSel()
    {
        //查询当前登入人 session('info.staff')
        $operate = Db::table($this->db . '.vip_staff')->where('status', 0)->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['code'] == 'boss') {
            $ws = true;
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $stores = $ew->org()->store()->get();
            $a_org = explode(',', $operate['admin_org_code']);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $stores = $stores . ', ';
            }
            if ($stores) {
                $ws[] = ['store_code', 'in', $stores];
            } else {
                $ws[] = ['store_code', 'in', ""];
            }
        } else {
            $ws[] = ['store_code', '=', $operate['store_code']];
        }
        //查询数据
        $data = Db::table($this->db . '.vip_staff')
            ->field('name, code')
            ->where('status', 0)
            ->where($ws)
            ->select();
        foreach ($data as $k => $v) {
            $grouping = Db::table($this->db . '.vip_staff_grouping')
                ->where('staff_code_all', 'like', '%' . $v['code'] . '%')
                ->where('staff_name_all', 'like', '%' . $v['name'] . '%')
                ->select();
            if (!empty($grouping)) {
                $data[$k];
            } else {
                unset($data[$k]);
            }
        }
        sort($data);
        webApi(200, 'ok', $data);
    }

    /**
     * 计算员工PK分组数据
     */
    public function planData()
    {
        $id = input('group_id') ?? null;
        if ($id == null) {
            webApi(400, '参数错误!');
        }
        //时间限制 (时间必须有,否则无法进行)
        if (input('start') != '') {
            //分割时间
            $start = explode('-', input('start'));
            $startNumber = intval($start['2']); // 日
            $where[] = ['create_time', '>=', strtotime(input('start'))];
        }
        if (input('end') != '') {
            //分割时间
            $end = explode('-', input('end'));
            $endNumber = intval($end['2']); // 日
            $where[] = ['create_time', '<=', strtotime(input('end'))];
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
        if (!isset($where)) {
            $where = true;
        }
        if (!isset($timeWhere)) {
            $timeWhere = true;
        }
        if (!isset($timeWhere_two)) {
            $timeWhere_two = true;
        }
        //查询分组员工
        $staffGroup = Db::table($this->db . '.vip_staff_grouping')->where('id', $id)->find();
        $array_codeAll = explode(',', $staffGroup['staff_code_all']);
        $array_nameAll = explode(',', $staffGroup['staff_name_all']);
        //格式化员工数据
        foreach ($array_codeAll as $k => $v) {
            $staffAll[$k]['name'] = $array_nameAll[$k];
            $staffAll[$k]['code'] = $array_codeAll[$k];
        }
        // $c = input('staff_code') ?? null;
        // if ($c == "") {
        //     $f = Db::table($this->db . '.vip_staff')->where('status', 0)->where('code', session('info.staff'))->find();
        //     array_push($staffAll, ['name' => $f['name'], 'code' => $f['code']]);
        //     $c = $f['code'];
        // }
        //数据计算处理 
        /**
         * realNumber 业绩金额
         * codeNumber 单数
         * totalNumber 件数
         * priceNumber 客单价
         * univalentNumber 件单价
         * jointNumber 连带率
         */
        $field = 'round(ifnull(sum(real_pay), 0), 2) as realNumber,
                    count(code) as codeNumber,
                    ifnull(sum(number), 0) as totalNumber, 
                    round(ifnull(sum(real_pay) / count(code), 0), 2) as priceNumber,
                    round(ifnull(sum(real_pay) / sum(number), 0), 2) as univalentNumber,
                    round(ifnull(sum(number) / count(code), 0), 2) as jointNumber';
        //数据处理
        foreach ($staffAll as $v) {
            $find = Db::table($this->db . '.vip_goods_order')
                ->field($field)
                ->where('status', 0)
                ->where('operate_code', $v['code'])
                ->where($where)
                ->find();
            $storeCode = Db::table($this->db . '.vip_staff')->where('code', $v['code'])->find()['store_code'];
            $store = Db::table($this->db . '.vip_store')->where('code', $storeCode)->find()['name'];
            if ($store == "") {
                $find['storeName'] = '无门店';
            } else {
                $find['storeName'] = $store;
            }
            $find['staffName'] = $v['name'];
            $find['staffCode'] = $v['code'];
            $find['totalMission'] = 0;
            //任务计划基本目标
            $staffMission = Db::table($this->db . '.vip_staff_mission')->where('mission_type', 1)->where('staff_code', $v['code'])->where($timeWhere)->select();
            if ($staffMission) {
                if (is_array($timeWhere_two)) {
                    $staffMission_two = Db::table($this->db . '.vip_staff_mission')->where('mission_type', 1)->where('staff_code', $v['code'])->where($timeWhere_two)->select();
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
                            // $staffMission[$key]['e'] =  369;
                        } else {
                            for ($i = $startNumber; $i <= $endNumber; $i++) {
                                $staffMission[$key]['d'] += $vlaue['day_' . $i];
                            }
                            // $staffMission[$key]['a'] =  123;
                        }
                    } else if ($vlaue['year'] == $end['0'] && $vlaue['month'] == $end['1']) {
                        for ($i = 1; $i <= $endNumber; $i++) {
                            $staffMission[$key]['d'] += $vlaue['day_' . $i];
                        }
                        // $staffMission[$key]['b'] = 456;
                    } else {
                        for ($i = 1; $i <= $vlaue['day']; $i++) {
                            $staffMission[$key]['d'] += $vlaue['day_' . $i];
                        }
                        // $staffMission[$key]['c'] = 789;
                    }
                }
                //基本目标数量
                foreach ($staffMission as $key => $val) {
                    if ($v['code'] == $val['staff_code']) {
                        $find['totalMission'] += $val['d'];
                    }
                }
            }
            //完成率
            if ($find['totalMission'] == 0 || $find['realNumber'] == 0) {
                $find['completion'] = 0;
            } else {
                $find['completion'] = number_format(($find['realNumber'] / $find['totalMission']) * 100, 2);
            }
            $data[] = $find;
        }
        //排序
        $group_type = input('group_type');
        if ($group_type == 1) {
            array_multisort(array_column($data, 'completion'), SORT_DESC, $data); // 完成率排序
        } else if ($group_type == 2) {
            array_multisort(array_column($data, 'univalentNumber'), SORT_DESC, $data); // 件单价排序
        } else if ($group_type == 3) {
            array_multisort(array_column($data, 'priceNumber'), SORT_DESC, $data); // 客单价排序
        } else if ($group_type == 4) {
            array_multisort(array_column($data, 'jointNumber'), SORT_DESC, $data); // 连带率排序
        } else if ($group_type == 5) {
            array_multisort(array_column($data, 'codeNumber'), SORT_DESC, $data); // 单数排序
        } else if ($group_type == 6) {
            array_multisort(array_column($data, 'totalNumber'), SORT_DESC, $data); // 件数排序
        }
        $c = input('staff_code') ?? null;
        if ($c == null) {
            $c = session('info.staff');
        }
        $t = [];
        foreach ($data as $k => $v) {
            if ($v['staffCode'] ==  $c) {
                $t = $data[$k];
            }
        }
        $dataAll = [
            'data' => $data,
            'staffOne' => $t
        ];
        //返回数据
        webApi(200, 'ok', $dataAll);
    }
}
