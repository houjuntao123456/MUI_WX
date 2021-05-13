<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 * Author lhp
 * Date 2020/08/26
 * Description  复购流程
 */
class Strategy extends Common
{
    /**
     * 免费策略的列表
     */
    public function refreeIndex()
    {
        $data = Db::table($this->db.'.vip_refree')->page(input('page'), input('limit'))->select();
        $count = Db::table($this->db.'.vip_refree')->count();

        $data = [
            'data' => $data,
            'count' =>$count
        ];
        webApi(200, 'ok', $data);
    }

    /**
     * 价值策略的列表
     */
    public function revalueIndex()
    {
        $data = Db::table($this->db.'.vip_revalue')->where('revalue_id', input('id'))->page(input('page'), input('limit'))->select();
        $count = Db::table($this->db.'.vip_revalue')->where('revalue_id', input('id'))->count();
        $data = [
            'data' => $data,
            'count' =>$count
        ];
        webApi(200, 'ok', $data);
    }

    /**
     * 相信策略的列表
     */
    public function rebelieveIndex()
    {
        $data = Db::table($this->db.'.vip_rebelieve')->where('rebelieve_id', input('id'))->page(input('page'), input('limit'))->select();
        $count = Db::table($this->db.'.vip_rebelieve')->where('rebelieve_id', input('id'))->count();
        $data = [
            'data' => $data,
            'count' =>$count
        ];
        webApi(200, 'ok', $data);
    }

}