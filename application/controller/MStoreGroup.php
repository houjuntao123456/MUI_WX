<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 * Author lxy
 * Date 2020/08/10
 * Description 门店PK分组
 */
class MStoreGroup extends Common
{
    /**
     * 门店PK分组
     */
    public function groupSel()
    {
        // 获取分页数据
        [$page, $limit, $search, $store] = [input('page'), input('limit'), input('search'), input('store_code')];
        //模糊查询
        if ($search != '') {
            $where[] = ['name', 'like', '%' . $search . '%'];
        }
        if ($store != '') {
            $where[] = ['store_code_all', 'like', '%' . $store . '%'];
            $storeName = Db::table($this->db . '.vip_store')->where('code', $store)->find()['name'];
            $where[] = ['store_name_all', 'like', '%' . $storeName . '%'];
        }
        //如果都没有条件
        if ($search == "" && $store == "") {
            if (session('info.store') == "") {
                $where[] = ['store_code_all', 'like', '%' . 0 . '%'];
                $where[] = ['store_name_all', 'like', '%' . 0 . '%'];
            } else {
                $where[] = ['store_code_all', 'like', '%' . session('info.store') . '%']; // session('info.staff')
                $storeName = Db::table($this->db . '.vip_store')->where('code', session('info.store'))->find()['name'];
                $where[] = ['store_name_all', 'like', '%' . $storeName . '%'];
            }
            // //查询当前登入人 session('info.staff')
            // $operate = Db::table($this->db . '.vip_staff')->where('status', 0)->where('code', session('info.staff'))->find();
            // //门店限制
            // if ($operate['code'] == 'boss') {
            //     $ws = true;
            // } else if ($operate['role'] == 0) {
            //     $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            //     $stores = $ew->org()->store()->get();
            //     $ws[] = ['code', 'in', $stores];
            // } else {
            //     if ($operate['store_code'] == "") {
            //         //返回数据
            //         webApi(200, 'ok', ['data' => [],'count' => 0]);
            //     } else {
            //         $ws[] = ['code', '=', $operate['store_code']];
            //     }
            // }
            // //查询数据
            // $data = Db::table($this->db . '.vip_store')
            //     ->field('name, code')
            //     ->where('status', 0)
            //     ->where($ws)
            //     ->select();
            // foreach ($data as $k => $v) {
            //     $where[] = ['store_code_all', 'like', '%' . $v['code'] . '%'];
            //     $where[] = ['store_name_all', 'like', '%' . $v['name'] . '%'];
            // }
        }
        //判断条件不存在就都查询
        if (!isset($where)) {
            $where = true;
        }
        //统计数量
        $count = Db::table($this->db . '.vip_store_grouping')
            ->where($where)
            ->count();
        //查询的数据
        $data = Db::table($this->db . '.vip_store_grouping')
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
     * 门店下拉框
     */
    public function storeSel()
    {
        //查询当前登入人 session('info.staff')
        $operate = Db::table($this->db . '.vip_staff')->where('status', 0)->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['code'] == 'boss') {
            $ws = true;
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $stores = $ew->org()->store()->get();
            $ws[] = ['code', 'in', $stores];
        } else {
            if ($operate['store_code'] == "") {
                //返回数据
                webApi(200, 'ok', []);
            } else {
                $ws[] = ['code', '=', $operate['store_code']];
            }
        }
        //查询数据
        $data = Db::table($this->db . '.vip_store')
            ->field('name, code')
            ->where('status', 0)
            ->where($ws)
            ->select();
        foreach ($data as $k => $v) {
            $grouping = Db::table($this->db . '.vip_store_grouping')
                ->where('store_code_all', 'like', '%' . $v['code'] . '%')
                ->where('store_name_all', 'like', '%' . $v['name'] . '%')
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
     * 计算门店PK分组数据
     */
    public function planData()
    {
        $id = input('group_id') ?? null;
        if ($id == null) {
            webApi(400, '参数错误!');
        }
        //时间限制 (时间必须有,否则无法进行)
        $times = array();
        $timeArray = explode('-', input('times'));
        $times['begin'] = mktime(0, 0, 0, $timeArray[1], 1, $timeArray[0]);
        $times['end'] = mktime(23, 59, 59, ($timeArray[1] + 1), 0, $timeArray[0]);
        $where[] = ['create_time', '>=', $times['begin']];
        $where[] = ['create_time', '<=', $times['end']];
        $timeWhere[] = ['year', '=', $timeArray[0]];
        if ($timeArray[1] == '01') { //一月
            $month = 'january';
        } else if ($timeArray[1] == '02') { //二月
            $month = 'february';
        } else if ($timeArray[1] == '03') { //三月
            $month = 'march';
        } else if ($timeArray[1] == '04') { //四月
            $month = 'april';
        } else if ($timeArray[1] == '05') { //五月
            $month = 'may';
        } else if ($timeArray[1] == '06') { //六月
            $month = 'june';
        } else if ($timeArray[1] == '07') { //七月
            $month = 'july';
        } else if ($timeArray[1] == '08') { //八月
            $month = 'august';
        } else if ($timeArray[1] == '09') { //九月
            $month = 'september';
        } else if ($timeArray[1] == '10') { //十月
            $month = 'october';
        } else if ($timeArray[1] == '11') { //十一月
            $month = 'november';
        } else if ($timeArray[1] == '12') { //十二月
            $month = 'december';
        }
        //判断条件不存在就都查询
        if (!isset($where)) {
            $where = true;
        }
        if (!isset($timeWhere)) {
            $timeWhere = true;
        }
        //查询分组员工
        $storeGroup = Db::table($this->db . '.vip_store_grouping')->where('id', $id)->find();
        $array_codeAll = explode(',', $storeGroup['store_code_all']);
        $array_nameAll = explode(',', $storeGroup['store_name_all']);
        //格式化员工数据
        $storeArray = array();
        foreach ($array_codeAll as $k => $v) {
            $storeArray[$k]['name'] = $array_nameAll[$k];
            $storeArray[$k]['code'] = $array_codeAll[$k];
        }
        // $c = input('store_code') ?? null;
        // if ($c == "") {
        //     $f = Db::table($this->db . '.vip_store')->where('status', 0)->where('code', session('info.store'))->find();
        //     if ($f) {
        //         array_push($storeArray, ['name' => $f['name'], 'code' => $f['code']]);
        //         $c = $f['code'];
        //     } else {
        //         array_push($storeArray, ['name' => '无门店数据', 'code' => ""]);
        //         $c = "";
        //     }
        // }
        /**
         * 数据计算处理 
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
        foreach ($storeArray as $v) {
            $find = Db::table($this->db . '.vip_goods_order')
                ->field($field)
                ->where('status', 0)
                ->where('store_code', $v['code'])
                ->where($where)
                ->find();
            $find['findName'] = $v['name'];
            $find['findCode'] = $v['code'];
            $find['totalMission'] = 0;
            //任务计划基本目标
            $storeMission = Db::table($this->db . '.vip_store_mission')->where('store_code', $v['code'])->where($timeWhere)->find();
            if ($storeMission) {
                //基本目标数量
                $find['totalMission'] = $storeMission[$month];
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
        $c = input('store_code') ?? null;
        if ($c == null) {
            $c = 0;
        }
        $t = [];
        foreach ($data as $k => $v) {
            if (trim($v['findCode']) == $c) {
                $t = $data[$k];
            }
        }
        $dataAll = [
            'data' => $data,
            'dataOne' => $t
        ];
        //返回数据
        webApi(200, 'ok', $dataAll);
    }
}
