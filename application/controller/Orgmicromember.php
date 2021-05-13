<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 * Author lhp
 * Date 2019/2/25
 * Description  机构微会员
 */
class Orgmicromember extends Common
{

    /**
     *  @param string name 机构名称
     *  @param int vvip 微会员新增
     *  @param int consume 消费人数
     *  @param int number 排名
     */
    public function index()
    {
        [$page, $limit] = [input('page'), input('limit')];
        [$startTime, $endTime, $orgwhere] = [input('starttime'), input('endTime'), input('orgwhere')]; // 开始时间, 结束时间, 机构条件
        $db = $this->db;
        $whereStaff = [];
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['code'] == 'boss') {
            $whereStaff = [];
        } else if ($operate['role'] == 0) {
            $whereStaff[] = ['code', 'in', $operate['admin_org_code']];
        } else {
            if (empty($operate['org'])) {
                $res = [
                    'data' => [],
                    'count' => 0
                ];
                webApi(200, 'ok', $res);
            }
            $whereStaff[] = ['code', '=', $operate['org']];
        }
        if (!empty($orgwhere)) { // 组织机构不为空时
            $orgs = Db::table($db.'.vip_org')->field('code,name')->where($whereStaff)->where('pid', $orgwhere)->select();
            $count = Db::table($db . '.vip_org')->where($whereStaff)->where('pid', $orgwhere)->count();
            if (empty($orgs)) {
                $orgs = Db::table($this->db . '.vip_org')->field('code, name')->where('code', $orgwhere)->select();
            }
            foreach ($orgs as $k=>$v) {
                $ew = new ErpWhere($db, $v['code']);
                $storeWhere = $ew->org()->store()->get();
                $orgs[$k]['vvip']  = Db::table($db.'.vip_viplist')
                                    ->where('vvip', 1)
                                    ->where('store_code', 'in', $storeWhere)
                                    ->whereBetweenTime('vvip_time', $startTime, $endTime)
                                    ->count();
                $orgs[$k]['consume'] = Db::table($db.'.vip_viplist')
                                        ->where('vvip', 1)
                                        ->where('store_code', 'in', $storeWhere)
                                        ->whereBetweenTime('vvip_time', $startTime, $endTime)
                                        ->whereBetweenTime('first_time', $startTime, $endTime)
                                        ->count();
            }
            array_multisort(array_column($orgs,'vvip'), SORT_DESC, $orgs);
            foreach ($orgs as $k=>$v) {
                $orgs[$k]['number'] = $k + 1;
            }
        } else { // 组织机构条件为空时
            $orgs = Db::table($db.'.vip_org')->field('code,name')->where($whereStaff)->select();
            $count = Db::table($db . '.vip_org')->where($whereStaff)->count();
            if (!empty($orgs)) {
                foreach ($orgs as $k=>$v) {
                    $ew = new ErpWhere($db, $v['code']);
                    $storeWhere = $ew->org()->store()->get();
                    $orgs[$k]['vvip']  = Db::table($db.'.vip_viplist')
                                        ->where('vvip', 1)
                                        ->where('store_code', 'in', $storeWhere)
                                        ->whereBetweenTime('vvip_time', $startTime, $endTime)
                                        ->count();
                    $orgs[$k]['consume'] = Db::table($db.'.vip_viplist')
                                            ->where('vvip', 1)
                                            ->where('store_code', 'in', $storeWhere)
                                            ->whereBetweenTime('vvip_time', $startTime, $endTime)
                                            ->whereBetweenTime('first_time', $startTime, $endTime)
                                            ->count();
                }
                array_multisort(array_column($orgs,'vvip'), SORT_DESC, $orgs);
                foreach ($orgs as $k=>$v) {
                    $orgs[$k]['number'] = $k + 1;
                }
            } 
        }
        $data = [
            'data' => $orgs,
            'count' => $count
        ];
        webApi(200, 'ok', $data);
    }

    /**
     * 查看微会员信息
     */
    public function wMemberNewly() {
        $code = input('code');
        $type = input('type');
        $startTime = input('startTime');
        $endTime = input('endTime');
        if ($type == 'org') {
            $ew = new ErpWhere($this->db, $code);
            $storeWhere = $ew->org()->store()->get();
            $a_org = explode(',', $code);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $storeWhere = $storeWhere . ', ';
            }
            $where[] = ['v.store_code', 'in', $storeWhere];
        } else if ($type == 'store') {
            $where[] = ['v.store_code', '=', $code];
        } else if ($type == 'staff') {
            $where[] = ['v.consultant_code', '=',$code];
        }
        //判断条件不存在就都查询
        if (!isset($where)) {
            $where = true;
        }
        // 会员数据
        $data = Db::table($this->db . '.vip_viplist')
            ->alias('v')
            ->leftJoin($this->db . '.vip_goods_order o', 'o.vip_code = v.code')
            ->field('v.*, ifnull(MAX(o.create_time), 0) as order_time, ifnull(round(sum(o.real_pay), 2), 0) as total_consumption, 
                        count(o.id) as consumption_times, ifnull(round(sum(o.number)), 0) as consumption_number')
            ->where('(o.status = 0 or o.status IS null)')
            ->where('v.vvip', 1)
            ->whereBetweenTime('v.vvip_time', $startTime, $endTime)
            ->where($where)
            ->group('v.code')
            ->select();
        $t = time();
        $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
        // 数据分页
        $es = new ErpWhere($this->db, "");
        $count = count($data);
        $data = $es->arrPage($data, input('page'), input('limit'));
        if ($data) {
            foreach ($data as $k => $v) {
                if ($v['return_visit'] > time()) {
                    $data[$k]['return_visit'] = 0;
                } else {
                    $data[$k]['return_visit'] =  round((time() - $v['return_visit']) / 86400);
                }
                if ($v['order_time'] >= $start) {
                    $data[$k]['r_days'] = 0;
                } else {
                    $data[$k]['r_days'] = intval(round((time() - $v['order_time']) / 86400));
                }
            }
            foreach ($data as $k => $v) {
                if ($v['return_visit'] > 15000) {
                    $data[$k]['return_visit'] = '未回访';
                }
                if ($v['r_days'] > 15000) {
                    $data[$k]['r_days'] = '未消费';
                }
                if (strlen($v['birthday']) == 8) {
                    $data[$k]['birthday'] = date('Y-m-d', strtotime($v['birthday']));
                } else if ($v['birthday'] == 0) {
                    $data[$k]['birthday'] = '无';
                } else {
                    $data[$k]['birthday'] = date('Y-m-d', $v['birthday']);
                }
            }
        } else {
            $data = [];
            $count = 0;
        }
        $data = [
            'count' => $count,
            'data' => $data
        ];
        webApi(200, 'ok', $data);
    }

    /**
     * 查看消费微会员信息
     */
    public function XMemberNewly() {
        $code = input('code');
        $type = input('type');
        $startTime = input('startTime');
        $endTime = input('endTime');
        if ($type == 'org') {
            $ew = new ErpWhere($this->db, $code);
            $storeWhere = $ew->org()->store()->get();
            $a_org = explode(',', $code);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $storeWhere = $storeWhere . ', ';
            }
            $where[] = ['v.store_code', 'in', $storeWhere];
        } else if ($type == 'store') {
            $where[] = ['v.store_code', '=', $code];
        } else if ($type == 'staff') {
            $where[] = ['v.consultant_code', '=', $code];
        }
        //判断条件不存在就都查询
        if (!isset($where)) {
            $where = true;
        }
        // 会员数据
        $data = Db::table($this->db . '.vip_viplist')
            ->alias('v')
            ->leftJoin($this->db . '.vip_goods_order o', 'o.vip_code = v.code')
            ->field('v.*, ifnull(MAX(o.create_time), 0) as order_time, ifnull(round(sum(o.real_pay), 2), 0) as total_consumption, 
                        count(o.id) as consumption_times, ifnull(round(sum(o.number)), 0) as consumption_number')
            ->where('(o.status = 0 or o.status IS null)')
            ->where('v.vvip', 1)
            ->whereBetweenTime('v.vvip_time', $startTime, $endTime)
            ->whereBetweenTime('v.first_time', $startTime, $endTime)
            ->where($where)
            ->group('v.code')
            ->select();
        $t = time();
        $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
        // 数据分页
        $es = new ErpWhere($this->db, "");
        $count = count($data);
        $data = $es->arrPage($data, input('page'), input('limit'));
        if ($data) {
            foreach ($data as $k => $v) {
                if ($v['return_visit'] > time()) {
                    $data[$k]['return_visit'] = 0;
                } else {
                    $data[$k]['return_visit'] =  round((time() - $v['return_visit']) / 86400);
                }
                if ($v['order_time'] >= $start) {
                    $data[$k]['r_days'] = 0;
                } else {
                    $data[$k]['r_days'] = intval(round((time() - $v['order_time']) / 86400));
                }
            }
            foreach ($data as $k => $v) {
                if ($v['return_visit'] > 15000) {
                    $data[$k]['return_visit'] = '未回访';
                }
                if ($v['r_days'] > 15000) {
                    $data[$k]['r_days'] = '未消费';
                }
                if (strlen($v['birthday']) == 8) {
                    $data[$k]['birthday'] = date('Y-m-d', strtotime($v['birthday']));
                } else if ($v['birthday'] == 0) {
                    $data[$k]['birthday'] = '无';
                } else {
                    $data[$k]['birthday'] = date('Y-m-d', $v['birthday']);
                }
            }
        } else {
            $data = [];
            $count = 0;
        }
        $data = [
            'count' => $count,
            'data' => $data
        ];
        webApi(200, 'ok', $data);
    }

}