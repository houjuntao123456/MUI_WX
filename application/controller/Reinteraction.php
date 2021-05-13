<?php

namespace app\controller;

use think\Db;
use think\Controller;

/**
 * Author lxy
 * Date 2020/08/26
 * Description 感动策略 
 */
class Reinteraction extends Common
{
    /**
     * 查询感动策略
     */
    public function reinteractionSel() 
    {
        //获取所需数据
        [$page, $limit, $type] = [input('page'), input('limit'), input('type')];
        //查询当前登入人
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //所属机构限制
        if ($operate['code'] == 'boss') {
            $org = true;
        } else { //判断是否是管理
            if ($operate['role'] == 0) { //管理查询管理机构
                $org[] = ['v.org_code', 'in', $operate['admin_org_code'] . ',' . 'ZZJG15459825114064'];
            } else { //员工查询所属机构
                $org[] = ['v.org_code', 'in', $operate['org_code'] . ',' . 'ZZJG15459825114064'];
            }
        }
        //判断查询数据
        if (intval($type) == 1) { //100天模板
            //统计数量
            $count = Db::table($this->db . '.vip_inhundred_interaction')
                ->alias('v')
                ->leftJoin($this->db . '.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($org)
                ->count();
            //所需数据
            $data = Db::table($this->db . '.vip_inhundred_interaction')
                ->alias('v')
                ->leftJoin($this->db . '.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($org)
                ->order('v.time', 'asc') //排列
                ->page($page, $limit)
                ->select();
            //格式化数据
            foreach ($data as $k => $v) {
                $data[$k]['time_g'] = $v['time'];
            }
        } else if (intval($type) == 2) { //专场模板
            //统计数量
            $count = Db::table($this->db . '.vip_infield_interaction')
                ->alias('v')
                ->leftJoin($this->db . '.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($org)
                ->count();
            //所需数据
            $data = Db::table($this->db . '.vip_infield_interaction')
                ->alias('v')
                ->leftJoin($this->db . '.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($org)
                ->order('v.time', 'desc') //按照时间排列
                ->page($page, $limit)
                ->select();
            //格式化数据
            foreach ($data as $k => $v) {
                $data[$k]['time_g'] = date('Y-m-d', $v['time']);
            }
        } else if (intval($type) == 3) { //生日模板
            //统计数量
            $count = Db::table($this->db . '.vip_inbirthday_interaction')
                ->alias('v')
                ->leftJoin($this->db . '.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($org)
                ->count();
            //所需数据
            $data = Db::table($this->db . '.vip_inbirthday_interaction')
                ->alias('v')
                ->leftJoin($this->db . '.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($org)
                ->order('v.time', 'asc') //按照时间排列
                ->page($page, $limit)
                ->select();
            //格式化数据
            foreach ($data as $k => $v) {
                $data[$k]['time_g'] = $v['time'];
            }
        } else if (intval($type) == 4) { //传统模板
            //统计数量
            $count = Db::table($this->db . '.vip_intradition_interaction')
                ->alias('v')
                ->leftJoin($this->db . '.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($org)
                ->count();
            //所需数据
            $data = Db::table($this->db . '.vip_intradition_interaction')
                ->alias('v')
                ->leftJoin($this->db . '.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($org)
                ->order('v.time', 'asc') //按照时间排列
                ->page($page, $limit)
                ->select();
            //格式化数据
            foreach ($data as $k => $v) {
                $data[$k]['time_g'] = date('Y-m-d', $v['time']);
            }
        } else if (intval($type) == 5) { //公众模板
            //统计数量
            $count = Db::table($this->db . '.vip_inpublic_interaction')
                ->alias('v')
                ->leftJoin($this->db . '.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($org)
                ->count();
            //所需数据
            $data = Db::table($this->db . '.vip_inpublic_interaction')
                ->alias('v')
                ->leftJoin($this->db . '.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($org)
                ->order('v.time', 'asc') //按照时间排列
                ->page($page, $limit)
                ->select();
            //格式化数据
            foreach ($data as $k => $v) {
                $data[$k]['time_g'] = date('Y-m-d', $v['time']);
            }
        } else if (intval($type) == 6) { //短信模板
            //统计数量
            $count = Db::table($this->db . '.vip_inmessage_interaction')
                ->alias('v')
                ->leftJoin($this->db . '.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($org)
                ->count();
            //所需数据
            $data = Db::table($this->db . '.vip_inmessage_interaction')
                ->alias('v')
                ->leftJoin($this->db . '.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($org)
                ->order('v.time', 'asc') //排列
                ->page($page, $limit)
                ->select();
            //格式化数据
            foreach ($data as $k => $v) {
                $data[$k]['time_g'] = date('Y-m-d', $v['time']);
            }
        } else {
            $count = 0;
            $data = [];
        }
        //格式化数据
        $data = [
            'data' => $data,
            'count' => $count
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }
    
}
