<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 * Author lhp
 * Date 2019/11/20
 * Description 畅销排行
 */
class BestSeller extends Common
{

    /**
     * 列表   
     */
    public function index()
    {
        [$startTime, $endTime, $store, $org, $code] = [input('startTime'), input('endTime'), input('store'), input('org'), input('code')];
        $where = [];
        if (!empty($store)) {
            $where[] = ['v.store_code', '=', $store];
        } else if (!empty($org)) {
            $ew = new ErpWhere($this->db, $org);
            $orgWhere = $ew->org()->store()->get();
            $where[] = ['v.store_code', 'in', $orgWhere];
        } 
        
        $where[] = ['v.create_time', '>', strtotime($startTime)];
        $where[] = ['v.create_time', '<', strtotime($endTime)];
        $where[] = ['v.status', '=', 2];
        $field = 'goods.code, ifnull(sum(info.number), 0) as number, ifnull(sum(info.dis_price), 0) as dis_price,goods.name, goods.price, goods.img';
        $countMoney = Db::table($this->db . '.vip_goods_order') // 总销售额
            ->alias('v')
            ->leftJoin($this->db . '.vip_goods_order_info info', 'v.code = info.order_code')
            ->where($where)
            ->sum('info.dis_price');
        $countNumber = Db::table($this->db . '.vip_goods_order') // 总件数
            ->alias('v')
            ->leftJoin($this->db . '.vip_goods_order_info info', 'v.code = info.order_code')
            ->where($where)
            ->sum('info.number');
        if (!empty($code)) {
            $data = Db::table($this->db . '.vip_goods_order')
                ->alias('v')
                ->leftJoin($this->db . '.vip_goods_order_info info', 'v.code = info.order_code')
                ->leftJoin($this->db . '.vip_goods goods', 'info.goods_code = goods.bar_code')
                ->field($field)
                ->where('goods.code', 'like', $code.'%')
                ->where($where)
                ->group('goods.code')
                // ->page(input('page'), input('limit'))
                ->select();
        } else {
            $data = Db::table($this->db . '.vip_goods_order')
                ->alias('v')
                ->leftJoin($this->db . '.vip_goods_order_info info', 'v.code = info.order_code')
                ->leftJoin($this->db . '.vip_goods goods', 'info.goods_code = goods.bar_code')
                ->field($field)
                ->where('goods.code', '<>', '')
                ->where($where)
                ->group('goods.code')
                // ->page(input('page'), input('limit'))
                ->select();
        }
        foreach ($data as $k=>$v) {
            $data[$k]['dis_price'] = number_format($v['dis_price'], 2);
            $data[$k]['price'] = number_format($v['price'], 2);
            if ($v['dis_price'] == 0 || $countMoney == 0) {
                $data[$k]['countMoney'] = '0%';
            } else {
                $data[$k]['countMoney'] = number_format(($v['dis_price'] / $countMoney) * 100, 2) .'%';
            }
            if ($v['number'] == 0 || $countNumber == 0) {
                $data[$k]['countNumber'] = '0%';
            } else {
                $data[$k]['countNumber'] = number_format(($v['number'] / $countNumber) * 100, 2) .'%';
            }
            
        }
        array_multisort(array_column($data,'number'), SORT_DESC, $data);
        // foreach ($data as $k=>$v) {
        //     $data[$k]['ranking'] = $k + 1;
        // }
        unset($countMoney,$countNumber);
        $count = Db::table($this->db . '.vip_goods_order')
            ->alias('v')
            ->leftJoin($this->db . '.vip_goods_order_info info', 'v.code = info.order_code')
            ->leftJoin($this->db . '.vip_goods goods', 'info.goods_code = goods.bar_code')
            ->field($field)
            ->where($where)
            ->group('goods.code')
            ->count();

        $data = [
            'data' => $data,
            'count' => $count
        ];

        webApi(200, 'ok', $data);
        
    }

    /**
     * 进去查看
     */
    public function see()
    {
        [$startTime, $endTime] = [strtotime(input('startTime')), strtotime(input('endTime'))];
        $field = 'goods.code, ifnull(sum(info.number), 0) as number, ifnull(sum(info.dis_price), 0) as dis_price,goods.name,s.name as store_name';
        $total_field = 'goods.code, ifnull(sum(info.number), 0) as number, ifnull(sum(info.dis_price), 0) as dis_price';
        $data = Db::table($this->db . '.vip_goods_order')
            ->alias('v')
            ->leftJoin($this->db . '.vip_goods_order_info info', 'v.code = info.order_code')
            ->leftJoin($this->db . '.vip_goods goods', 'info.goods_code = goods.bar_code')
            ->leftJoin($this->db . '.vip_store s', 'v.store_code = s.code')
            ->field($field)
            ->where('goods.code', input('code'))
            ->where('v.create_time', '>',  $startTime)
            ->where('v.create_time', '<',  $endTime)
            ->where('v.status', 2)
            ->group('v.store_code')
            ->page(input('page'), input('limit'))
            ->select();
        foreach ($data as $k=>$v) {
            $data[$k]['dis_price'] = number_format($v['dis_price'], 2);
        }
        array_multisort(array_column($data,'number'), SORT_DESC, $data);
        // foreach ($data as $k=>$v) {
        //     $data[$k]['ranking'] = $k + 1;
        // }
        $count = Db::table($this->db . '.vip_goods_order')
            ->alias('v')
            ->leftJoin($this->db . '.vip_goods_order_info info', 'v.code = info.order_code')
            ->leftJoin($this->db . '.vip_goods goods', 'info.goods_code = goods.bar_code')
            ->leftJoin($this->db . '.vip_store s', 'v.store_code = s.code')
            ->field($field)
            ->where('goods.code', input('code'))
            ->where('v.create_time', '>',  $startTime)
            ->where('v.create_time', '<',  $endTime)
            ->where('v.status', 2)
            ->group('v.store_code')
            ->page(input('page'), input('limit'))
            ->count();
        $total = Db::table($this->db . '.vip_goods_order')
            ->alias('v')
            ->leftJoin($this->db . '.vip_goods_order_info info', 'v.code = info.order_code')
            ->leftJoin($this->db . '.vip_goods goods', 'info.goods_code = goods.bar_code')
            ->leftJoin($this->db . '.vip_store s', 'v.store_code = s.code')
            ->field($total_field)
            ->where('goods.code', input('code'))
            ->where('v.create_time', '>',  $startTime)
            ->where('v.create_time', '<',  $endTime)
            ->where('v.status', 2)
            ->select();
        foreach ($total as $k=>$v) {
            $total[$k]['dis_price'] = number_format($v['dis_price'], 2);
        }
        $data = [
            'data' => $data,
            'count' => $count,
            'total' => $total
        ];

        webApi(200, 'ok', $data);
    }
    
}
