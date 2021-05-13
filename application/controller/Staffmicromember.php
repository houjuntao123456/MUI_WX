<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 * Author lhp
 * Date 2019/2/21
 * Description  员工微会员
 */
class Staffmicromember extends Common
{

    /**
     *  @param string name 员工姓名
     *  @param string storename 门店名称
     *  @param int vvip 微会员新增
     *  @param int consume 消费人数
     *  @param int number 排名
     */
    public function index()
    {
        [$page, $limit] = [input('page'), input('limit')];
        [$startTime, $endTime] = [input('starttime'), input('endTime')];// 开始时间, 结束时间 

        [$orgwhere, $storewhere, $staffwhere] = [input('orgwhere'), input('storewhere'), input('staffwhere')];// 机构条件, 门店条件, 员工条件
        $db = $this->db;

        $whereStaff = [];
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['code'] == 'boss') {
            $whereStaff = [];
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $stores = $ew->org()->store()->get();
            $a_org = explode(',', $operate['admin_org_code']);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $stores = $stores . ', ';
            }
            $staff_code = Db::table($this->db . '.vip_staff')->where('store_code', 'in', $stores)->field('code')->select();
            if ($staff_code) {
                $arr = implode(',', array_column($staff_code, 'code'));
                $whereStaff[] = ['v.code', 'in', $arr];
            } else {
                //返回数据
                $res = [
                    'data' => [],
                    'count' => 0
                ];
                webApi(200, 'ok', $res);
            }
        } else {
            if (empty($operate['code'])) {
                $res = [
                    'data' => [],
                    'count' => 0
                ];
                webApi(200, 'ok', $res);
            }
            $whereStaff[] = ['v.code', '=', $operate['code']];
        }
        if (!empty($staffwhere)) { // 员工不为空时
            $data = Db::table($db.'.vip_staff')
                    ->alias('v')
                    ->leftJoin($db.'.vip_store vs', 'vs.code = v.store_code')
                    ->field('v.name, vs.name storename')
                    ->where($whereStaff)
                    ->where('v.code', $staffwhere)
                    ->where('v.status', '<>', 1)
                    ->select();
            foreach ($data as $k=>$v) {
                $data[$k]['vvip']  = Db::table($db.'.vip_viplist')
                                    ->where('vvip', 1)
                                    ->where('consultant_code', $staffwhere)
                                    ->whereBetweenTime('vvip_time', $startTime, $endTime)
                                    ->count();
                $data[$k]['consume'] = Db::table($db.'.vip_viplist')
                                    ->where('vvip', 1)
                                    ->where('consultant_code', $staffwhere)
                                    ->whereBetweenTime('vvip_time', $startTime, $endTime)
                                    ->whereBetweenTime('first_time', $startTime, $endTime)
                                    ->count();
            }
            array_multisort(array_column($data,'vvip'), SORT_DESC, $data);
            foreach ($data as $k=>$v) {
                $data[$k]['number'] = $k + 1;
            }
            $count = Db::table($db . '.vip_staff')->where('code', $staffwhere)->where('status', '<>', 1)->count();
        } else if (!empty($storewhere)) { // 门店不为空时
            $whereStaffs = [];
            $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
            //门店限制
            if ($operate['code'] == 'boss') {
                $whereStaffs = [];
            } else if ($operate['role'] == 0) {
                $ew = new ErpWhere($this->db, $operate['admin_org_code']);
                $stores = $ew->org()->store()->get();
                $a_org = explode(',', $operate['admin_org_code']);
                if (in_array('ZZJG15459825114064', $a_org)) {
                    $stores = $stores . ', ';
                }
                $staff_code = Db::table($this->db . '.vip_staff')->where('store_code', 'in', $stores)->field('code')->select();
                if ($staff_code) {
                    $arr = implode(',', array_column($staff_code, 'code'));
                    $whereStaffs[] = ['code', 'in', $arr];
                } else {
                    //返回数据
                    $res = [
                        'data' => [],
                        'count' => 0
                    ];
                    webApi(200, 'ok', $res);
                }
            } else {
                $whereStaffs[] = ['code', '=', $operate['code']];
            }
            $store = Db::table($db.'.vip_store')->field('code, name')->where('code', $storewhere)->find();
            $data = Db::table($db.'.vip_staff')->field('code, name')->where($whereStaffs)->where('store_code', $store['code'])->where('status', '<>', 1)->select();
            foreach ($data as $k=>$v) {
                $data[$k]['storename'] = $store['name'];
                $data[$k]['vvip']  = Db::table($db.'.vip_viplist')
                                    ->where('vvip', 1)
                                    ->where('consultant_code', $v['code'])
                                    ->whereBetweenTime('vvip_time', $startTime, $endTime)
                                    ->count();
                $data[$k]['consume'] = Db::table($db.'.vip_viplist')
                                    ->where('vvip', 1)
                                    ->where('consultant_code', $v['code'])
                                    ->whereBetweenTime('vvip_time', $startTime, $endTime)
                                    ->whereBetweenTime('first_time', $startTime, $endTime)
                                    ->count();
            }
            array_multisort(array_column($data,'vvip'), SORT_DESC, $data);
            foreach ($data as $k=>$v) {
                $data[$k]['number'] = $k + 1;
            }
            $count = Db::table($db . '.vip_staff')->where($whereStaffs)->where('store_code', $store['code'])->where('status', '<>', 1)->count();
        } else if (!empty($orgwhere)) { // 组织机构不为空时
            $org = Db::table($db.'.vip_org')->field('code,name')->where('pid', $orgwhere)->select();
            if (!empty($org)) {
                $org = Db::table($this->db . '.vip_org')->field('code, name')->where('code', $orgwhere)->select();
            }
            $orgCodes = implode(',', array_column($org, 'code'));
            $ew = new ErpWhere($db, $orgCodes);
            $storeWhere = $ew->org()->store()->get();

            $data = Db::table($db.'.vip_staff')
                    ->alias('v')
                    ->leftJoin($db.'.vip_store vs', 'vs.code = v.store_code')
                    ->field('v.code, v.name, vs.name storename')
                    ->where($whereStaff)
                    ->where('v.store_code', 'in', $storeWhere)
                    ->where('v.status', '<>', 1)
                    ->select();

            foreach ($data as $k=>$v) {
            $data[$k]['vvip'] = Db::table($db.'.vip_viplist')
                                    ->where('vvip', 1)
                                    ->where('consultant_code', $v['code'])
                                    ->whereBetweenTime('vvip_time', $startTime, $endTime)
                                    ->count();
            $data[$k]['consume'] = Db::table($db.'.vip_viplist')
                                    ->where('vvip', 1)
                                    ->where('consultant_code', $v['code'])
                                    ->whereBetweenTime('vvip_time', $startTime, $endTime)
                                    ->whereBetweenTime('first_time', $startTime, $endTime)
                                    ->count();
            }
            array_multisort(array_column($data,'vvip'), SORT_DESC, $data); // 1572
            foreach ($data as $k=>$v) {
                $data[$k]['number'] = $k + 1;
            }

            $count = Db::table($db . '.vip_staff')->where($whereStaff)->where('store_code', 'in', $storeWhere)->where('status', '<>', 1)->count();
        } else { // 其他条件都为空时

            $data = Db::table($db.'.vip_staff')
                    ->alias('v')
                    ->leftJoin($db.'.vip_store vs', 'vs.code = v.store_code')
                    ->field('v.code, v.name, vs.name storename')
                    ->where($whereStaff)
                    ->where('v.status', '<>', 1)
                    ->select();

            foreach ($data as $k=>$v) {
            $data[$k]['vvip']  = Db::table($db.'.vip_viplist')
                                    ->where('vvip', 1)
                                    ->where('consultant_code', $v['code'])
                                    ->whereBetweenTime('vvip_time', $startTime, $endTime)
                                    ->count();
            $data[$k]['consume'] = Db::table($db.'.vip_viplist')
                                    ->where('vvip', 1)
                                    ->where('consultant_code', $v['code'])
                                    ->whereBetweenTime('vvip_time', $startTime, $endTime)
                                    ->whereBetweenTime('first_time', $startTime, $endTime)
                                    ->count();
            }
            array_multisort(array_column($data,'vvip'), SORT_DESC, $data);
            foreach ($data as $k=>$v) {
                $data[$k]['number'] = $k + 1;
            }
            $count = Db::table($db . '.vip_staff')
                    ->alias('v')
                    ->leftJoin($db.'.vip_store vs', 'vs.code = v.store_code')
                    ->where($whereStaff)
                    ->where('v.status', '<>', 1)
                    ->count();
        }

        $res = [
            'data' => $data,
            'count' => $count
        ];

        webApi(200, 'ok', $res);
    }

}