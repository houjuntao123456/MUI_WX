<?php

namespace app\controller;

use think\Db;
use think\Controller;

/**
 * Author lxy
 * Date 2019/06/13
 * Description 365天跟进
 */
class WreThree extends Common
{
    /**
     * 感动日子
     */
    public function WreThreeGd()
    {
        // 获取的信息
        [$page, $limit, $db, $staff, $store, $splb, $start, $end] = [
            input('page'),
            input('limit'),
            $this->db,
            input('staff'),
            input('store'),
            input('splb'),
            input('start'),
            input('end')
        ];
        //调用方法
        $data = $this->hd($page, $limit, $db, $staff, $store, $splb, $start, $end, '感动日子');
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 传统日子
     */
    public function WreThreeCt()
    {
        // 获取的信息
        [$page, $limit, $db, $staff, $store, $splb, $start, $end] = [
            input('page'),
            input('limit'),
            $this->db,
            input('staff'),
            input('store'),
            input('splb'),
            input('start'),
            input('end')
        ];
        //调用方法
        $data = $this->hd($page, $limit, $db, $staff, $store, $splb, $start, $end, '传统日子');
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 公众日子
     */
    public function WreThreeGz()
    {
        // 获取的信息
        [$page, $limit, $db, $staff, $store, $splb, $start, $end] = [
            input('page'),
            input('limit'),
            $this->db,
            input('staff'),
            input('store'),
            input('splb'),
            input('start'),
            input('end')
        ];
        //调用方法
        $data = $this->hd($page, $limit, $db, $staff, $store, $splb, $start, $end, '公众日子');
        //返回数据
        webApi(200, 'ok', $data);
    }

    //本控制器共用的方法
    private function hd($page, $limit, $db, $staff, $store, $splb, $start, $end, $data_name)
    {
        //判断如果有员工就查询员工
        if ($staff != '') {
            $where[] = ['v.executor_code', '=', $staff];
        } else {
             //判断如果没有员工有门店就查询门店
            if ($store != '') {
                $staff_code = Db::table($db . '.vip_staff')->where('store_code', $store)->where('status', 0)->field('code')->select();
                $arr = implode(',', array_column($staff_code, 'code'));
                $where[] = ['v.executor_code', 'in', $arr];
            } else {
                //判断如果没有员工也没有门店就查询组织机构,否则就查询登入人的管理机构
                if ($splb != '') {
                    $staff_code = Db::table($db . '.vip_staff')->where('org_code', 'in', $splb)->where('status', 0)->field('code')->select();
                    $arr = implode(',', array_column($staff_code, 'code'));
                    $where[] = ['v.executor_code', 'in', $arr];
                } else {
                    $org = session('info.admin_org');
                    $staff_code = Db::table($db . '.vip_staff')->where('org_code', 'in', $org)->where('status', 0)->field('code')->select();
                    $arr = implode(',', array_column($staff_code, 'code'));
                    $where[] = ['v.executor_code', 'in', $arr];
                }
            }
        }
        //时间限制
        if ($start != '') {
            $where[] = ['v.create_time', '>=', strtotime($start)];
        }
        if ($end != '') {
            $where[] = ['v.create_time', '<=', strtotime($end)];
        }
        //判断条件不存在就都查询
        if (!isset($where)) {
            $where = true;
        }
        //统计数量
        $count = Db::table($db . '.vip_interaction_record')
            ->alias('v')
            ->leftJoin($db . '.vip_staff vy', 'vy.code = v.executor_code')
            ->leftJoin($db . '.vip_store vm', 'vm.code = vy.store_code')
            ->leftJoin($db . '.vip_org vz', 'vz.code = vm.org_code')
            ->field('v.*,count(v.vip_code) member,vy.name vyname,vm.name vmname,vz.name vzname')
            ->where('v.remark', '365天跟进')
            ->where('v.date_name', $data_name)
            ->where('v.status', 1)
            ->where('vy.status', 0)
            ->where($where)
            ->group('v.name,v.executor_code')
            ->count();
        //查询的数据
        $data = Db::table($db . '.vip_interaction_record')
            ->alias('v')
            ->leftJoin($db . '.vip_staff vy', 'vy.code = v.executor_code')
            ->leftJoin($db . '.vip_store vm', 'vm.code = vy.store_code')
            ->leftJoin($db . '.vip_org vz', 'vz.code = vm.org_code')
            ->field('v.*,count(v.vip_code) member,vy.name vyname,vm.name vmname,vz.name vzname')
            ->where('v.remark', '365天跟进')
            ->where('v.date_name', $data_name)
            ->where('v.status', 1)
            ->where('vy.status', 0)
            ->where($where)
            ->group('v.name,v.executor_code')
            ->page($page, $limit)
            ->select();
        //格式化数据
        foreach ($data as $k => $v) {
            if ($v['vmname'] == "" || $v['vzname'] == "") {
                $data[$k]['vmname'] = '无门店';
                $data[$k]['vzname'] = '无机构';
            }
            $data[$k]['time_g'] = date('Y-m-d', $v['time']);
        }
        foreach ($data as $k => $v) {
            $data[$k]['total_name'] = $v['vzname'] . '-' . $v['vmname'] . '-' . $v['vyname'];
        }
        //格式化数据
        $data = [
            'count' => $count,
            'data' => $data
        ];
        //返回数据
        return $data;
    }
}
