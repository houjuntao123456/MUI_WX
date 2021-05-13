<?php

namespace app\controller;

use think\Db;
use think\Controller;

/**
 * Author lxy
 * Date 2020/08/26
 * Description 印象策略 
 */
class Reimpression extends Common
{
    /**
     * 查询印象策略
     */
    public function reimpressionSel() 
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
        $data = Db::table($this->db . '.vip_reimpression')
                ->where($where)
                ->page($page, $limit)
                ->select();
        //统计数量
        $count = Db::table($this->db . '.vip_reimpression')
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
