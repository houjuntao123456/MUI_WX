<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 * Author lhp
 * Date 2019/2/24
 * Description  门店微会员
 */
class Storemicromember extends Common
{

    /**
     *  @param string name 门店名称
     *  @param int vvip 微会员新增
     *  @param int consume 消费人数
     *  @param int number 排名
     */
    public function index()
    {
        [$page,$limit] = [input('page'), input('limit')]; // 分页信息
        [$startTime, $endTime, $orgwhere, $storewhere] = [input('starttime'), input('endTime'), input('orgwhere'), input('storewhere')];// 开始时间, 结束时间, 机构条件, 门店条件
        $db = $this->db;

        $whereStaff = [];
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['code'] == 'boss') {
            $whereStaff = [];
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $stores = $ew->org()->store()->get();
            $whereStaff[] = ['code', 'in', $stores];
            
        } else {
            if (empty($operate['store'])) {
                $res = [
                    'data' => [],
                    'count' => 0
                ];
                webApi(200, 'ok', $res);
            }
            $whereStaff[] = ['code', '=', $operate['store']];
        }

        if (!empty($storewhere)) { // 门店不为空时
            $data = Db::table($db.'.vip_store')->field('code, name')->where('status', 0)->where($whereStaff)->where('code', $storewhere)->select();
            foreach ($data as $k=>$v) {
                $data[$k]['vvip']  = Db::table($db.'.vip_viplist')
                                    ->where('vvip', 1)
                                    
                                    ->where('store_code', $v['code'])
                                    ->whereBetweenTime('vvip_time', $startTime, $endTime)
                                    ->count();
                $data[$k]['consume'] = Db::table($db.'.vip_viplist')
                                    ->where('vvip', 1)
                                    
                                    ->where('store_code', $v['code'])
                                    ->whereBetweenTime('vvip_time', $startTime, $endTime)
                                    ->whereBetweenTime('first_time', $startTime, $endTime)
                                    ->count();
            }
            array_multisort(array_column($data,'vvip'), SORT_DESC, $data);
            foreach ($data as $k=>$v) {
                $data[$k]['number'] = $k + 1;
            }
            $count = Db::table($db . '.vip_store')->where('status', 0)->where($whereStaff)->where('code', $storewhere)->count();
        } else if (!empty($orgwhere)) { // 组织机构不为空时
           
            $org = Db::table($db.'.vip_org')->field('code,name')->where('pid', $orgwhere)->select();
            if (empty($org)) {
                $org = Db::table($this->db.'.vip_org')->field('code, name')->where('code', $orgwhere)->select();
            }
            $orgCodes = implode(',', array_column($org, 'code'));
            $ew = new ErpWhere($db, $orgCodes);
            $store = $ew->org()->store()->get();

            $data = Db::table($db.'.vip_store')->field('name, code')->where('status', 0)->where($whereStaff)->where('code', 'in', $store)->page($page,$limit)->select();

            foreach ($data as $k=>$v) {
            $data[$k]['vvip']  = Db::table($db.'.vip_viplist')
                                    ->where('vvip', 1)
                                    
                                    ->where('store_code', $v['code'])
                                    ->whereBetweenTime('vvip_time', $startTime, $endTime)
                                    ->count();
            $data[$k]['consume'] = Db::table($db.'.vip_viplist')
                                    ->where('vvip', 1)
                                    
                                    ->where('store_code', $v['code'])
                                    ->whereBetweenTime('vvip_time', $startTime, $endTime)
                                    ->where('first_time', '>', 0)
                                    ->count();
            }
            array_multisort(array_column($data,'vvip'), SORT_DESC, $data);
            foreach ($data as $k=>$v) {
                $data[$k]['number'] = $k + 1;
            }
            $count = Db::table($db . '.vip_store')->where('status', 0)->where($whereStaff)->where('code', 'in', $store)->count();
        } else { // 机构和门店条件为空时
            // $org = Db::table($db.'.vip_org')->field('code,name')->select();
            // if (!empty($org)) {
            //     $orgCodes = implode(',', array_column($org, 'code'));
            // if (session('info.admin_org') != '') {
            //     $orgCodes = '';
            // } else {
            //     $orgCodes = session('info.org');
            // }
            
            $data = Db::table($db.'.vip_store')->field('name, code')->where($whereStaff)->where('status', 0)->select();

            foreach ($data as $k=>$v) {
            $data[$k]['vvip']  = Db::table($db.'.vip_viplist')
                                    ->where('vvip', 1)
                                    
                                    ->where('store_code', $v['code'])
                                    ->whereBetweenTime('vvip_time', $startTime, $endTime)
                                    ->count();
            $data[$k]['consume'] = Db::table($db.'.vip_viplist')
                                    ->where('vvip', 1) 
                                    
                                    ->where('store_code', $v['code'])
                                    ->whereBetweenTime('vvip_time', $startTime, $endTime)
                                    ->where('first_time', '>', 0)
                                    ->count();
            }
            array_multisort(array_column($data,'vvip'), SORT_DESC, $data);
            foreach ($data as $k=>$v) {
                $data[$k]['number'] = $k + 1;
            }
            
            $count = Db::table($db . '.vip_store')->where($whereStaff)->where('status', 0)->count();
        }
        $res = [
            'data' => $data,
            'count' => $count
        ];
        webApi(200, 'ok', $res);
    }
    
}