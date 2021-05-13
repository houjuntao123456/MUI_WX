<?php

namespace app\controller;

use think\Db;
use think\Controller;

/**
 * Author lxy
 * Date 2019/10/12
 * Description 回访记录
 */
class ReturnVisit extends Common
{
    /**
     * 查询回访记录
     */
    public function ReturnVisitList()
    {
        //分页信息
        [$page, $limit, $search, $code, $db] = [input('page'), input('limit'), input('search'), input('vip_code'), $this->db];
        //模糊查询
        if ($search != '') {
            if ($search == 1) {
                $where[] = ['v.visit_mode', '=', '电话回访'];
            } else if ($search == 2) {
                $where[] = ['v.visit_mode', '=', '短信回访'];
            } else if ($search == 3) {
                $where[] = ['v.visit_mode', '=', '富信回访'];
            } else if ($search == 4) {
                $where[] = ['v.visit_mode', '=', '微信回访'];
            }
        } else {
            $where = true;
        }
        //统计数量
        $count = Db::table($db . '.vip_returnvisit_record')
            ->alias('v')
            ->leftJoin($db . '.vip_staff vg', 'vg.code = v.visit_operator')
            ->field('v.*,vg.name vgname')
            ->where('vg.status', 0)
            ->where('v.user_code', $code)
            ->where($where)
            ->count();
        //所需数据
        $data = Db::table($db . '.vip_returnvisit_record')
            ->alias('v')
            ->leftJoin($db . '.vip_staff vg', 'vg.code = v.visit_operator')
            ->field('v.*,vg.name vgname')
            ->where('vg.status', 0)
            ->where('v.user_code', $code)
            ->where($where)
            ->order('v.time', 'desc') //按照时间排列
            ->page($page, $limit)
            ->select();
        //格式化数据
        foreach ($data as $k => $v) {
            $data[$k]['time_g'] = date('Y-m-d H:i:s', $v['time']);
        }
        //清除变量 
        unset($page, $limit, $where, $org);
        //格式化数据
        $data = [
            'count' => $count,
            'data' => $data
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }

}
