<?php

namespace app\controller;

use think\Db;
use think\Controller;

/**
 * Author lxy
 * Date 2020/08/26
 * Description 感动策略 
 */
class Removing extends Common
{
   /**
    * 查询感动策略名称表
    */
    public function removingName() 
    {
        //查询数据
        $data = Db::table($this->db . '.vip_removing_name')->select();
        //统计数量
        $count = Db::table($this->db . '.vip_removing_name')->count();
        //格式化数据
        $data = [
            'data' => $data,
            'count' => $count
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 查询感动策略
     */
    public function removingSel() 
    {
        //获取所需数据
        [$page, $limit, $name] = [input('page'), input('limit'), input('name')];
        //判断查询数据
        if ($name != "") {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        //判断查询条件
        if (!isset($where)) {
            $where = true;
        }
        //查询数据
        $data = Db::table($this->db . '.vip_removing')
                ->where($where)
                ->page($page, $limit)
                ->select();
        //统计数量
        $count = Db::table($this->db . '.vip_removing')
                ->where($where)
                ->count();
        //格式化数据
        $data = [
            'data' => $data,
            'count' => $count
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }
    
}
