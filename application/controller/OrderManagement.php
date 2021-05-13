<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

class OrderManagement extends Common
{
    /**
     * 订单会员信息
     */
    public function index()
    {
        [$code, $store, $staff, $page, $limit, $org] = [input('code'), input('store'), input('staff'), input('page'), input('limit'), input('org')]; // 会员卡号, 门店ERP
        // 判断
        if (!empty($code)) {
            $wheres[] = ['v.vip_code', '=', $code];
        }
        if (!empty($staff)) {
            $wheres[] = ['v.operate_code', '=', $staff];
        }
        if (!empty($store)) {
            $wheres[] = ['v.store_code', '=', $store];
        }
        if (!empty($org)) {
            $ew = new ErpWhere($this->db, $org);
            $orgWhere = $ew->org()->store()->get();
            $a_org = explode(',', $org);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $orgWhere = $orgWhere . ', ';
            }
            $wheres[] = ['v.store_code', 'in', $orgWhere];
        }
        // 按登入人查询数据
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        // 门店限制
        if ($operate['code'] == 'boss') {
            $whereStaff = true;
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $stores = $ew->org()->store()->get();
            $a_org = explode(',', $operate['admin_org_code']);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $stores = $stores . ', ';
            }
            $whereStaff[] = ['v.store_code', 'in', $stores];
        } else {
            $whereStaff[] = ['v.operate_code', '=', $operate['code']];
        }
        // 判断条件不存在就都查询
        if (!isset($whereStaff)) {
            $whereStaff = true;
        }
        if (!isset($wheres)) {
            $wheres = true;
        }
        // 基本配置
        $sysCon = Db::table($this->db . '.vip_sys_con')->find();
        if ($sysCon['fen_is_org'] == "on") {
            $staffW = $whereStaff;
        } else {
            $staffW = true;
        }
        $data = Db::table($this->db . '.vip_goods_order')
            ->alias('v')
            ->leftJoin($this->db . '.vip_viplist vip', 'v.vip_code = vip.code')
            ->field('vip.img img, vip.level_code, v.vip_name, v.vip_code, v.number, v.store_code,v.operate_code, v.code, v.real_pay, v.create_time, v.give_integral, v.m_status')
            ->where($wheres)
            ->where($staffW)
            ->order('v.create_time', 'desc')
            ->page($page, $limit)
            ->select();
        $count = Db::table($this->db . '.vip_goods_order')
            ->alias('v')
            ->leftJoin($this->db . '.vip_viplist vip', 'v.vip_code = vip.code')
            ->where($wheres)
            ->where($staffW)
            ->count();
        foreach ($data as $k => $v) {
            $data[$k]['date'] = date('Y-m-d', $v['create_time']);
            $data[$k]['money'] = number_format($v['real_pay'], 2, '.', '');
        }
        unset($wheres);
        $data = [
            'data' => $data,
            'count' => $count
        ];

        webApi(200, 'ok', $data);
    }

    /**
     * 订单进行互动后修改状态  17392706991
     */
    public function m_status()
    {
        $code = input('code');
        if (empty($code)) {
            webApi(400, '订单号不存在');
        }
        $data = Db::table($this->db . '.vip_goods_order')->where('code', input('code'))->setField('m_status', 1);
        webApi(200, 'ok');
    }

    /**
     * 订单查看详情
     */
    public function see()
    {
        $bill = input('code'); // 订单号 

        if ($bill == null) {
            wxApi(400, '参数错误');
        }
        $status = Db::table('company.vip_business')->field('x_status')->where('code', $this->db)->find();

        $data = Db::table($this->db . '.vip_goods_order')
            ->alias('v')
            ->leftJoin($this->db . '.vip_store s', 'v.store_code = s.code')
            ->field('v.code, v.vip_code, v.operate_name, s.name store_name, v.real_pay, v.give_integral, v.status, v.create_time, v.number, v.use_integral')
            ->where('v.code', $bill)
            ->select();
        foreach ($data as $v) {
            $vipmg = Db::table($this->db . '.vip_viplist')->field('level_code')->where('code', $v['vip_code'])->find();
        }

        $level = Db::table($this->db . '.vip_viplevel')->field('username')->where('code', $vipmg['level_code'])->find();

        $order_code = Db::table($this->db . '.vip_goods_order_info')->where('order_code', $bill)->select();
        $goods = array_column($order_code, 'goods_code');
        $goods_name = [];
        foreach ($goods as $v) {
            $name = Db::table($this->db . '.vip_goods')->where('bar_code', $v)->find();
            array_push($goods_name, $name);
        }
        unset($bill);
        foreach ($data as $k => $v) {
            $data[$k]['date'] = date('Y-m-d', $v['create_time']);
            $data[$k]['money'] = number_format($v['real_pay'], 2, '.', '');
            $data[$k]['level'] = $level['username'];
            $data[$k]['status'] = $v['status'] = 0 ? '退货' : '正常';
            $data[$k]['x_status'] = $status['x_status'];
            $data[$k]['goods_name'] = $goods_name;
            unset($data[$k]['create_time'], $data[$k]['real_pay']);
        }
        unset($vipmg, $level, $goods_name);
        webApi(200, 'ok', $data);
    }

    /**
     * 点击添加明细中商品列表
     */
    public function goodsname()
    {
        if (!empty(input('where'))) {
            $where[] = ['frenum', 'like', input('where') . '%'];
        } else {
            $where = true;
        }
        $data = Db::table($this->db . '.vip_goods')->field('frenum as code, bar_code, name, color, sizes')->where($where)->page(input('page'), input('limit'))->select();
        $count = Db::table($this->db . '.vip_goods')->where($where)->count();

        $data = [
            'data' => $data,
            'count' => $count
        ];
        webApi(200, 'ok', $data);
    }

    /**
     * 点击商品时添加到订单明细中
     */
    public function detailed()
    {
        $code = input('bar_code');
        $order_code = input('order_code');
        $goods = Db::table($this->db . '.vip_goods')->where('bar_code', $code)->find();
        $staff = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        if (!empty($goods)) {
            $data = [
                'order_code' => $order_code,
                'goods_code' => $goods['bar_code'],
                'number' => 1,
                'pro_code' => $staff['code'],
                'pro_name' => $staff['name'],
                'create_time' => time(),
                'store_code' => session('info.store') !== '' ? session('info.store') : '',
                'color' => $goods['color'],
                'size' => $goods['sizes'],
                'vip_code' => input('vip_code')
            ];
            $res = Db::table($this->db . '.vip_goods_order_info')->insert($data);
            if ($res) {
                $number = Db::table($this->db . '.vip_goods_order_info')->where('order_code', $order_code)->count();
                $vip_code = Db::table($this->db . '.vip_viplist')->where('code', $number['vip_code'])->find();
                if ($number > 1) {
                    Db::table($this->db . '.vip_goods_order')->where('code', $order_code)->setInc('number');
                    Db::table($this->db . '.vip_viplist')->where('code', $number['vip_code'])->setInc('consumption_number');
                }
                webApi(200, 'ok', $goods);
            } else {
                webApi(400, '失败');
            }
        }
        webApi(400, '失败');
    }
}
