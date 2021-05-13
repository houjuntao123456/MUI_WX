<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 * Author lhp
 * Date 2019/11/14
 * Description 货客精准
 */
class CargoGuest extends Common
{

    /**
     * 列表
     */
    public function index()
    {
        if (!empty(input('code'))) {
            $where[] = ['frenum', 'like', input('code') . '%'];
        } else {
            $where = true;
        }

        $data = Db::table($this->db . '.vip_goods')
            ->field('frenum as code, bar_code, color, sizes')
            ->where($where)
            ->page(input('page'), input('limit'))
            ->select();

        $count = Db::table($this->db . '.vip_goods')
            ->where($where)
            ->count();

        $data = [
            'data' => $data,
            'count' => $count
        ];
        webApi(200, 'ok', $data);
    }

    /**
     * 通过条码查找会员
     */
    public function user()
    {
        $goods = Db::table($this->db . '.vip_goods')->field('bar_code, color, sizes')->where('bar_code', input('bar_code'))->find();
        $lable = Db::table($this->db . '.vip_goods_labels')->where('goods_code', $goods['bar_code'])->select();
        $where = '1=1';
        if (empty($goods) && empty($lable)) {
            $data = [
                'count' => 0,
                'data' => []
            ];
            webApi(200, 'ok', $data);
        } else {
            if (!empty($goods['color'])) {
                $where .= ' and ' . 'goods.color =' . ' "' . $goods['color'] . '"';
            }
            if (!empty($goods['sizes'])) {
                $where .= ' and ' . 'goods.sizes =' . ' "' . $goods['sizes'] . '"';
            }
            $goods_code = [];
            if (!empty($lable)) {
                foreach ($lable as $k => $v) {
                    $lave = Db::table($this->db . '.vip_goods_labels')->where('label_code', $v['label_code'])->where('label_id', $v['label_id'])->select();
                    foreach ($lave as $val) {
                        if (!in_array($val['goods_code'], $goods_code)) {
                            array_push($goods_code, $val['goods_code']);
                        }
                    }
                }
                $vv = '';
                foreach ($goods_code as $av) {
                    $vv .= "'" . $av . "',";
                }
                $aaa = trim($vv, ','); // trim 去掉字符串两边符号
                $where .= " and goods.bar_code in (" . $aaa . ")";
            }
            // 加入判断是不是微信会员
            if (input('vvip') != "") {
                $where .= ' and vvip=' . input('vvip');
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
                $whereStaff[] = ['vip.store_code', 'in', $stores];
            } else {
                $whereStaff[] = ['vip.consultant_code', '=', $operate['code']];
            }
            // 判断条件不存在就都查询
            if (!isset($whereStaff)) {
                $whereStaff = true;
            }
            // 基本配置
            $sysCon = Db::table($this->db . '.vip_sys_con')->find();
            if ($sysCon['fen_is_org'] == "on") {
                $staffW = $whereStaff;
            } else {
                $staffW = true;
            }
            // 查询的数据
            $data = Db::table($this->db . '.vip_goods_order')
                ->alias('v')
                ->Join($this->db . '.vip_goods_order_info info', 'v.code = info.order_code')
                ->Join($this->db . '.vip_goods goods', 'goods.bar_code = info.goods_code')
                ->Join($this->db . '.vip_viplist vip', 'vip.code = v.vip_code')
                ->field('vip.consultant_code, vip.store_code, vip.vvip, v.vip_name username, v.vip_code code, v.vip_phone phone, goods.color color, goods.sizes sizes')
                // ->fetchSql(true)
                ->where($staffW)
                ->where($where)
                ->page(input('page'), input('limit'))
                ->group('v.vip_code')
                ->select();
            // 统计数量
            $count = Db::table($this->db . '.vip_goods_order')
                ->alias('v')
                ->Join($this->db . '.vip_goods_order_info info', 'v.code = info.order_code')
                ->Join($this->db . '.vip_goods goods', 'goods.bar_code = info.goods_code')
                ->Join($this->db . '.vip_viplist vip', 'vip.code = v.vip_code')
                ->where($staffW)
                ->where($where)
                ->group('v.vip_code')
                ->count();
            // 格式化数据
            foreach ($data as $k => $v) {
                if ($sysCon['yincang_is_phone'] == "on") {
                    if ($v['phone'] != "") {
                        $data[$k]['phone_g'] = substr($v['phone'], 0, 3) . '****' . substr($v['phone'], 7);
                    }
                } else {
                    $data[$k]['phone_g'] = $v['phone'];
                }
            }
            $data = [
                'count' => $count,
                'data' => $data
            ];

            webApi(200, 'ok', $data);
        }
    }

    /**
     * 按条件查询人数
     */
    public function lookPeople()
    {
        // 获取数据
        [$page, $limit, $id, $store, $rfmType, $barCode, $db] = [
            input('page'), input('limit'), input('id'), input('store_code'), input('rfm_type'), input('bar_code'), $this->db
        ];
        $es = new ErpWhere($db, "");
        $vipCode = $es->goodsVip($barCode);
        //判断
        if (empty($vipCode)) {
            $data = [
                'count' => 0,
                'data' => []
            ];
            //返回数据
            webApi(200, 'ok', $data);
        } else {
            //获取所需条件
            if ($rfmType == 'r') {
                //条件
                $rday = Db::table($db . '.vip_rfm_r')->where('id', $id)->find();
                //统计数量
                $count = Db::table($db . '.view_vipinfo')
                    // ->where('store_code', $store)
                    ->where('code', 'in', $vipCode)
                    ->where('rfm_days', '>=', $rday['r_intervalone'])
                    ->where('rfm_days', '<=', $rday['r_intervaltwo'])
                    ->count();
                //查询的数据
                $vipData = Db::table($db . '.view_vipinfo')
                    // ----卡号, ----姓名, --手机号, -----未消费天数, --门店, ------生日, ------消费次数, ---------件数,-------金额, -----图片, -------未消费天数
                    ->field('code, username, phone, rfm_days, store_code, birthday, consumption_times, consumption_number, total_consumption, img, final_purchases')
                    // ->where('store_code', $store)
                    ->where('code', 'in', $vipCode)
                    ->where('rfm_days', '>=', $rday['r_intervalone'])
                    ->where('rfm_days', '<=', $rday['r_intervaltwo'])
                    ->page($page, $limit)
                    ->select();
            } else if ($rfmType == 'f') {
                //条件
                $rday = Db::table($db . '.vip_rfm_f')->where('id', $id)->find();
                // 周期
                $f_consumption = Db::table($db . '.vip_rfm_days')->field('f_consumption')->where('store_code', $store)->find();
                //查询的数据
                if ($rday['f_type'] == "无") {
                    //查询大于未消费天数且已消费的人数
                    $vipData = Db::table($this->db . '.view_vipinfo')
                        // ->where('store_code', $store)
                        ->where('code', 'in', $vipCode)
                        ->where('final_purchases', '<>', '')
                        ->where('rfm_days', '>', $f_consumption['f_consumption'])
                        ->page($page, $limit)
                        ->select();
                    $count =  Db::table($this->db . '.view_vipinfo')
                        // ->where('store_code', $store)
                        ->where('code', 'in', $vipCode)
                        ->where('final_purchases', '<>', '')
                        ->where('rfm_days', '>', $f_consumption['f_consumption'])
                        ->count();
                } else {
                    //查询的数据
                    $vipData = Db::table($db . '.view_rfm_f')
                        ->alias('f')
                        ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                        ->field('count, v.code, v.username, v.phone, v.rfm_days, v.store_code, v.birthday, v.consumption_times, consumption_number, v.total_consumption, v.img, v.final_purchases')
                        //->where('v.store_code', $store)
                        ->where('v.code', 'in', $vipCode)
                        ->where('v.rfm_days', '<=', $f_consumption['f_consumption'])
                        ->where('v.rfm_days', '>', 0)
                        ->where('f.count', '>=', $rday['f_intervalone'])
                        ->where('f.count', '<', $rday['f_intervaltwo'])
                        ->page($page, $limit)
                        ->select();
                    //统计数量
                    $count = Db::table($db . '.view_rfm_f')
                        ->alias('f')
                        ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                        //->where('v.store_code', $store)
                        ->where('v.code', 'in', $vipCode)
                        ->where('v.rfm_days', '<=', $f_consumption['f_consumption'])
                        ->where('v.rfm_days', '>', 0)
                        ->where('f.count', '>=', $rday['f_intervalone'])
                        ->where('f.count', '<', $rday['f_intervaltwo'])
                        ->count();
                }
            } else if ($rfmType == 'm') {
                //限制
                $rday = Db::table($db . '.vip_rfm_m')->where('id', $id)->find();
                // 周期
                $m_consumption = Db::table($db . '.vip_rfm_days')->field('m_consumption')->where('store_code', $store)->find();
                //查询的数据
                $vipData = Db::table($db . '.view_rfm_c')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('sum, v.code, v.username, v.phone, v.rfm_days, v.store_code, v.birthday, v.consumption_times, consumption_number, v.total_consumption, v.img, v.final_purchases')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $m_consumption['m_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.sum', '>=', $rday['m_intervalone'])
                    ->where('f.sum', '<', $rday['m_intervaltwo'])
                    ->page($page, $limit)
                    ->select();
                //统计数量
                $count = Db::table($db . '.view_rfm_c')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $m_consumption['m_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.sum', '>=', $rday['m_intervalone'])
                    ->where('f.sum', '<', $rday['m_intervaltwo'])
                    ->count();
            } else if ($rfmType == 'n') {
                //限制
                $rday = Db::table($db . '.vip_rfm_n')->where('id', $id)->find();
                // 周期
                $n_consumption = Db::table($db . '.vip_rfm_days')->field('n_consumption')->where('store_code', $store)->find();
                //查询的数据
                if ($rday['n_type'] == "无") {
                    //查询大于未消费天数且已消费的人数
                    $vipData = Db::table($this->db . '.view_vipinfo')
                        // ->where('store_code', $store)
                        ->where('code', 'in', $vipCode)
                        ->where('final_purchases', '<>', '')
                        ->where('rfm_days', '>', $n_consumption['n_consumption'])
                        ->page($page, $limit)
                        ->select();
                    $count =  Db::table($this->db . '.view_vipinfo')
                        // ->where('store_code', $store)
                        ->where('code', 'in', $vipCode)
                        ->where('final_purchases', '<>', '')
                        ->where('rfm_days', '>', $n_consumption['n_consumption'])
                        ->count();
                } else {
                    //查询的数据
                    $vipData = Db::table($db . '.view_rfm_n')
                        ->alias('f')
                        ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                        ->field('num, v.code, v.username, v.phone, v.rfm_days, v.store_code, v.birthday, v.consumption_times, consumption_number, v.total_consumption, v.img, v.final_purchases')
                        //->where('v.store_code', $store)
                        ->where('v.code', 'in', $vipCode)
                        ->where('v.rfm_days', '<=', $n_consumption['n_consumption'])
                        ->where('v.rfm_days', '>', 0)
                        ->where('f.num', '>=', $rday['n_intervalone'])
                        ->where('f.num', '<', $rday['n_intervaltwo'])
                        ->page($page, $limit)
                        ->select();
                    //统计数量
                    $count = Db::table($db . '.view_rfm_n')
                        ->alias('f')
                        ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                        //->where('v.store_code', $store)
                        ->where('v.code', 'in', $vipCode)
                        ->where('v.rfm_days', '<=', $n_consumption['n_consumption'])
                        ->where('v.rfm_days', '>', 0)
                        ->where('f.num', '>=', $rday['n_intervalone'])
                        ->where('f.num', '<', $rday['n_intervaltwo'])
                        ->count();
                }
            } else if ($rfmType == 'i') {
                //限制
                $rday = Db::table($db . '.vip_rfm_i')->where('id', $id)->find();
                // 周期
                $i_consumption = Db::table($db . '.vip_rfm_days')->field('i_consumption')->where('store_code', $store)->find();
                //数据
                if ($rday['i_type'] != '无') {
                    //查询的数据
                    $vipData = Db::table($db . '.view_vipinfo')
                        ->alias('v')
                        ->leftJoin($db . '.view_referral r', 'r.lnt_code = v.code')
                        ->field('r.count, v.*')
                        //->where('v.store_code', $store)
                        ->where('v.code', 'in', $vipCode)
                        ->where('r.lnttime', '>=', time() - (86400 * $i_consumption['i_consumption']))
                        ->where('r.lnttime', '<=', time())
                        ->where('r.count', '>=', $rday['i_intervalone'])
                        ->where('r.count', '<', $rday['i_intervaltwo'])
                        ->page($page, $limit)
                        ->select();
                    //统计数量
                    $count = Db::table($db . '.view_vipinfo')
                        ->alias('v')
                        ->leftJoin($db . '.view_referral r', 'r.lnt_code = v.code')
                        //->where('v.store_code', $store)
                        ->where('v.code', 'in', $vipCode)
                        ->where('r.lnttime', '>=', time() - (86400 * $i_consumption['i_consumption']))
                        ->where('r.lnttime', '<=', time())
                        ->where('r.count', '>=', $rday['i_intervalone'])
                        ->where('r.count', '<', $rday['i_intervaltwo'])
                        ->count();
                } else {
                    //分页的条件
                    $page = 1;
                    $limit = 10;
                    $limitStart = $limit * ($page - 1);
                    $vipData = Db::query('select * from ' . $db . '.view_vipinfo where code not in(select lnt_code from ' . $db . '.view_referral) and FIND_IN_SET(code,\'' . $vipCode . '\') limit ' . $limitStart . ',' . $limit);
                    $count = Db::query('select count(id) as count from ' . $db . '.view_vipinfo where code not in(select lnt_code from ' . $db . '.view_referral) and FIND_IN_SET(code,\'' . $vipCode . '\')');
                    $count = $count[0]['count'];
                }
            } else if ($rfmType == 'p') {
                //限制
                $rday = Db::table($db . '.vip_rfm_p')->where('id', $id)->find();
                // 周期
                $p_consumption = Db::table($db . '.vip_rfm_days')->field('p_consumption')->where('store_code', $store)->find();
                //查询的数据 
                $vipData = Db::table($db . '.view_rfm_p')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('passenger, v.code, v.username, v.phone, v.rfm_days, v.store_code, v.birthday, v.consumption_times, consumption_number, v.total_consumption, v.img, v.final_purchases')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $p_consumption['p_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.passenger', '>=', $rday['p_intervalone'])
                    ->where('f.passenger', '<', $rday['p_intervaltwo'])
                    ->page($page, $limit)
                    ->select();
                //统计数量
                $count = Db::table($db . '.view_rfm_p')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $p_consumption['p_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.passenger', '>=', $rday['p_intervalone'])
                    ->where('f.passenger', '<', $rday['p_intervaltwo'])
                    ->count();
            } else if ($rfmType == 'a') {
                //限制
                $rday = Db::table($db . '.vip_rfm_a')->where('id', $id)->find();
                // 周期
                $a_consumption = Db::table($db . '.vip_rfm_days')->field('a_consumption')->where('store_code', $store)->find();
                //查询的数据 
                $vipData = Db::table($db . '.view_rfm_a')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('piece, v.code, v.username, v.phone, v.rfm_days, v.store_code, v.birthday, v.consumption_times, consumption_number, v.total_consumption, v.img, v.final_purchases')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $a_consumption['a_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.piece', '>=', $rday['a_intervalone'])
                    ->where('f.piece', '<', $rday['a_intervaltwo'])
                    ->page($page, $limit)
                    ->select();
                //统计数据
                $count = Db::table($db . '.view_rfm_a')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $a_consumption['a_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.piece', '>=', $rday['a_intervalone'])
                    ->where('f.piece', '<', $rday['a_intervaltwo'])
                    ->count();
            } else if ($rfmType == 'j') {
                //限制
                $rday = Db::table($db . '.vip_rfm_j')->where('id', $id)->find();
                // 周期
                $j_consumption = Db::table($db . '.vip_rfm_days')->field('j_consumption')->where('store_code', $store)->find();
                //查询的数据 
                $vipData = Db::table($db . '.view_rfm_j')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('rate, v.code, v.username, v.phone, v.rfm_days, v.store_code, v.birthday, v.consumption_times, consumption_number, v.total_consumption, v.img, v.final_purchases')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $j_consumption['j_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.rate', '>=', $rday['j_intervalone'])
                    ->where('f.rate', '<', $rday['j_intervaltwo'])
                    ->page($page, $limit)
                    ->select();
                //统计数量
                $count = Db::table($db . '.view_rfm_j')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $j_consumption['j_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.rate', '>=', $rday['j_intervalone'])
                    ->where('f.rate', '<', $rday['j_intervaltwo'])
                    ->count();
            } else if ($rfmType == 'c') {
                //限制
                $rday = Db::table($db . '.vip_rfm_c')->where('id', $id)->find();
                // 周期
                $c_consumption = Db::table($db . '.vip_rfm_days')->field('c_consumption')->where('store_code', $store)->find();
                //查询的数据 
                $vipData = Db::table($db . '.view_rfm_c')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('sum, v.code, v.username, v.phone, v.rfm_days, v.store_code, v.birthday, v.consumption_times, consumption_number, v.total_consumption, v.img, v.final_purchases')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $c_consumption['c_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.sum', '>=', $rday['c_intervalone'])
                    ->where('f.sum', '<', $rday['c_intervaltwo'])
                    ->page($page, $limit)
                    ->select();
                //统计数量
                $count = Db::table($db . '.view_rfm_c')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $c_consumption['c_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.sum', '>=', $rday['c_intervalone'])
                    ->where('f.sum', '<', $rday['c_intervaltwo'])
                    ->count();
            }
            //清除变量
            unset($id, $page, $limit, $rday, $store, $db, $rfmType, $barCode, $vipCode, $es);
            //格式化数据
            $data = [
                'count' => $count,
                'data' => $vipData
            ];
            //返回数据
            webApi(200, 'ok', $data);
        }
    }

    /**
     * 按条件查询人数发送短信和微信
     */
    public function messageMember()
    {
        // 获取数据
        [$id, $store, $message_content, $rfmType, $we, $barCode, $db] = [
            input('id'), input('store_code'), input('message_content'), input('rfm_type'), input('wxchat'), input('bar_code'), $this->db
        ];
        $es = new ErpWhere($db, "");
        $vipCode = $es->goodsVip($barCode);
        //判断
        if (empty($vipCode)) {
            //返回数据
            webApi(400, '发送失败!');
        } else {
            //获取所需条件
            if ($rfmType == 'r') {
                //条件
                $rday = Db::table($db . '.vip_rfm_r')->where('id', $id)->find();
                //查询的数据
                $vipData = Db::table($db . '.view_vipinfo')
                    // ----卡号, ----姓名, ----手机号
                    ->field('code, username, phone, rfm_days, openid')
                    // ->where('store_code', $store)
                    ->where('code', 'in', $vipCode)
                    ->where('rfm_days', '>=', $rday['r_intervalone'])
                    ->where('rfm_days', '<=', $rday['r_intervaltwo'])
                    ->select();
            } else if ($rfmType == 'f') {
                //条件
                $rday = Db::table($db . '.vip_rfm_f')->where('id', $id)->find();
                // 周期
                $f_consumption = Db::table($db . '.vip_rfm_days')->field('f_consumption')->where('store_code', $store)->find();
                //查询的数据
                if ($rday['f_type'] == "无") {
                    //查询大于未消费天数且已消费的人数
                    $vipData = Db::table($this->db . '.view_vipinfo')
                        // ->where('store_code', $store)
                        ->where('code', 'in', $vipCode)
                        ->where('final_purchases', '<>', '')
                        ->where('rfm_days', '>', $f_consumption['f_consumption'])
                        ->select();
                } else {
                    $vipData = Db::table($db . '.view_rfm_f')
                        ->alias('f')
                        ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                        ->field('count, v.code, v.username, v.phone, v.rfm_days, v.openid')
                        //->where('v.store_code', $store)
                        ->where('v.code', 'in', $vipCode)
                        ->where('v.rfm_days', '<=', $f_consumption['f_consumption'])
                        ->where('v.rfm_days', '>', 0)
                        ->where('f.count', '>=', $rday['f_intervalone'])
                        ->where('f.count', '<', $rday['f_intervaltwo'])
                        ->select();
                }
            } else if ($rfmType == 'm') {
                //限制
                $rday = Db::table($db . '.vip_rfm_m')->where('id', $id)->find();
                // 周期
                $m_consumption = Db::table($db . '.vip_rfm_days')->field('m_consumption')->where('store_code', $store)->find();
                //查询的数据
                $vipData = Db::table($db . '.view_rfm_c')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('sum, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $m_consumption['m_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.sum', '>=', $rday['m_intervalone'])
                    ->where('f.sum', '<', $rday['m_intervaltwo'])
                    ->select();
            } else if ($rfmType == 'n') {
                //限制
                $rday = Db::table($db . '.vip_rfm_n')->where('id', $id)->find();
                // 周期
                $n_consumption = Db::table($db . '.vip_rfm_days')->field('n_consumption')->where('store_code', $store)->find();
                //查询的数据
                if ($rday['n_type'] == "无") {
                    //查询大于未消费天数且已消费的人数 
                    $vipData = Db::table($this->db . '.view_vipinfo')
                        // ->where('store_code', $store)
                        ->where('code', 'in', $vipCode)
                        ->where('final_purchases', '<>', '')
                        ->where('rfm_days', '>', $n_consumption['n_consumption'])
                        ->select();
                } else {
                    $vipData = Db::table($db . '.view_rfm_n')
                        ->alias('f')
                        ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                        ->field('num, v.code, v.username, v.phone, v.rfm_days, v.openid')
                        //->where('v.store_code', $store)
                        ->where('v.code', 'in', $vipCode)
                        ->where('v.rfm_days', '<=', $n_consumption['n_consumption'])
                        ->where('v.rfm_days', '>', 0)
                        ->where('f.num', '>=', $rday['n_intervalone'])
                        ->where('f.num', '<', $rday['n_intervaltwo'])
                        ->select();
                }
            } else if ($rfmType == 'i') {
                //限制
                $rday = Db::table($db . '.vip_rfm_i')->where('id', $id)->find();
                // 周期
                $i_consumption = Db::table($db . '.vip_rfm_days')->field('i_consumption')->where('store_code', $store)->find();
                //查询的数据
                if ($rday['i_type'] != '无') {
                    $vipData = Db::table($db . '.view_vipinfo')
                        ->alias('v')
                        ->leftJoin($db . '.view_referral r', 'r.lnt_code = v.code')
                        ->field('r.count, v.*')
                        //->where('v.store_code', $store)
                        ->where('v.code', 'in', $vipCode)
                        ->where('r.lnttime', '>=', time() - (86400 * $i_consumption['i_consumption']))
                        ->where('r.lnttime', '<=', time())
                        ->where('r.count', '>=', $rday['i_intervalone'])
                        ->where('r.count', '<', $rday['i_intervaltwo'])
                        ->select();
                } else {
                    //有openid的数据
                    $vipData = Db::query('select * from ' . $db . '.view_vipinfo where code not in(select lnt_code from ' . $db . '.view_referral) and FIND_IN_SET(code,\'' . $vipCode . '\')');
                }
            } else if ($rfmType == 'p') {
                //限制
                $rday = Db::table($db . '.vip_rfm_p')->where('id', $id)->find();
                // 周期
                $p_consumption = Db::table($db . '.vip_rfm_days')->field('p_consumption')->where('store_code', $store)->find();
                //查询的数据 
                $vipData = Db::table($db . '.view_rfm_p')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('passenger, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $p_consumption['p_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.passenger', '>=', $rday['p_intervalone'])
                    ->where('f.passenger', '<', $rday['p_intervaltwo'])
                    ->select();
            } else if ($rfmType == 'a') {
                //限制
                $rday = Db::table($db . '.vip_rfm_a')->where('id', $id)->find();
                // 周期
                $a_consumption = Db::table($db . '.vip_rfm_days')->field('a_consumption')->where('store_code', $store)->find();
                //查询的数据 
                $vipData = Db::table($db . '.view_rfm_a')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('piece, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $a_consumption['a_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.piece', '>=', $rday['a_intervalone'])
                    ->where('f.piece', '<', $rday['a_intervaltwo'])
                    ->select();
            } else if ($rfmType == 'j') {
                //限制
                $rday = Db::table($db . '.vip_rfm_j')->where('id', $id)->find();
                // 周期
                $j_consumption = Db::table($db . '.vip_rfm_days')->field('j_consumption')->where('store_code', $store)->find();
                //查询的数据 
                $vipData = Db::table($db . '.view_rfm_j')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('rate, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $j_consumption['j_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.rate', '>=', $rday['j_intervalone'])
                    ->where('f.rate', '<', $rday['j_intervaltwo'])
                    ->select();
            } else if ($rfmType == 'c') {
                //限制
                $rday = Db::table($db . '.vip_rfm_c')->where('id', $id)->find();
                // 周期
                $c_consumption = Db::table($db . '.vip_rfm_days')->field('c_consumption')->where('store_code', $store)->find();
                //查询的数据 
                $vipData = Db::table($db . '.view_rfm_c')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('sum, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $c_consumption['c_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.sum', '>=', $rday['c_intervalone'])
                    ->where('f.sum', '<', $rday['c_intervaltwo'])
                    ->select();
            }
            //提取会员的手机号并更改格式
            $phone = array_column($vipData, 'phone');
            // 格式会员code
            $vip_code = array_column($vipData, 'code');
            //调用短信接口发送短信 $phone会员手机号 $message_content短信内容
            $count = array_chunk($phone, 100);
            if ($we == 1) {
                $es->wxG($vip_code, $message_content, input('type'));
                webApi(200, '发送成功!');
            } else if ($we == 2) {
                $message_count = count($vipData) * ceil($es->abslength($message_content) / 64);
                $msg = Db::table('company.vip_business')->field('code, usable_msg')->where('code', $db)->find();
                $sms = Db::table('company.vip_sms_autograph')->field('sms_autograph, business_code')->where('business_code', $db)->find();
                if (empty($sms)) {
                    webApi(400, '短信签名未配置，请联系管理员进行配置');
                }
                //判断短信条数
                if ($msg['usable_msg'] < $message_count) {
                    webApi(400, '短信条数不足，可用短信为' . $msg['usable_msg'] . '条' . '，当前发送短信为' . $message_count . '条');
                }
                // 启动事务
                Db::startTrans();
                try {
                    for ($i = 0; $i < count($count); $i++) {
                        $es->shortMessage($count[$i], '【'.$sms['sms_autograph'].'】'.$message_content);
                    }
                    // 提交事务
                    Db::commit();
                    $res = true;
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $res = false;
                }
            }
            //清除变量
            unset($id, $store, $rday, $message_content, $count, $phone, $es);
            //判断返回数据
            if ($res) {
                Db::table('company.vip_business')->where('code', $db)->setDec('usable_msg', $message_count);
                webApi(200, '发送成功!');
            } else {
                webApi(400, $res);
            }
        }
    }

    /**
     * 按条件查询人数群发视图
     */
    public function groupFuxin()
    {
        // 获取数据
        [$id, $store, $db, $rfmType, $barCode] = [input('id'), input('store_code'), $this->db, input('rfm_type'), input('bar_code')];
        $es = new ErpWhere($db, "");
        $vipCode = $es->goodsVip($barCode);
        //判断
        if (empty($vipCode)) {
            //返回数据
            webApi(400, '发送失败!');
        } else {
            //获取所需条件
            if ($rfmType == 'r') {
                //条件
                $rday = Db::table($db . '.vip_rfm_r')->where('id', $id)->find();
                //查询的数据 
                //没有openid的
                $vipDataT = Db::table($db . '.view_vipinfo')
                    // ----卡号, ----姓名, ----手机号
                    ->field('code, username, phone, rfm_days, openid')
                    // ->where('store_code', $store)
                    ->where('code', 'in', $vipCode)
                    ->where('rfm_days', '>=', $rday['r_intervalone'])
                    ->where('rfm_days', '<=', $rday['r_intervaltwo'])
                    ->select();
            } else if ($rfmType == 'f') {
                //条件
                $rday = Db::table($db . '.vip_rfm_f')->where('id', $id)->find();
                // 周期
                $f_consumption = Db::table($db . '.vip_rfm_days')->field('f_consumption')->where('store_code', $store)->find();
                //查询的数据
                if ($rday['f_type'] == "无") {
                    //查询大于未消费天数且已消费的人数
                    $vipDataT = Db::table($this->db . '.view_vipinfo')
                        // ->where('store_code', $store)
                        ->where('code', 'in', $vipCode)
                        ->where('final_purchases', '<>', '')
                        ->where('rfm_days', '>', $f_consumption['f_consumption'])
                        ->select();
                } else {
                    $vipDataT = Db::table($db . '.view_rfm_f')
                        ->alias('f')
                        ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                        ->field('count, v.code, v.username, v.phone, v.rfm_days, v.openid')
                        //->where('v.store_code', $store)
                        ->where('v.code', 'in', $vipCode)
                        ->where('v.rfm_days', '<=', $f_consumption['f_consumption'])
                        ->where('v.rfm_days', '>', 0)
                        ->where('f.count', '>=', $rday['f_intervalone'])
                        ->where('f.count', '<', $rday['f_intervaltwo'])
                        ->select();
                }
            } else if ($rfmType == 'm') {
                //限制
                $rday = Db::table($db . '.vip_rfm_m')->where('id', $id)->find();
                // 周期
                $m_consumption = Db::table($db . '.vip_rfm_days')->field('m_consumption')->where('store_code', $store)->find();
                //查询的数据 
                //没有openid的
                $vipDataT = Db::table($db . '.view_rfm_c')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('sum, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $m_consumption['m_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.sum', '>=', $rday['m_intervalone'])
                    ->where('f.sum', '<', $rday['m_intervaltwo'])
                    ->select();
            } else if ($rfmType == 'n') {
                //限制
                $rday = Db::table($db . '.vip_rfm_n')->where('id', $id)->find();
                // 周期
                $n_consumption = Db::table($db . '.vip_rfm_days')->field('n_consumption')->where('store_code', $store)->find();
                //查询的数据 
                if ($rday['n_type'] == "无") {
                    //查询大于未消费天数且已消费的人数 
                    $vipDataT = Db::table($this->db . '.view_vipinfo')
                        // ->where('store_code', $store)
                        ->where('code', 'in', $vipCode)
                        ->where('final_purchases', '<>', '')
                        ->where('rfm_days', '>', $n_consumption['n_consumption'])
                        ->select();
                } else {
                    $vipDataT = Db::table($db . '.view_rfm_n')
                        ->alias('f')
                        ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                        ->field('num, v.code, v.username, v.phone, v.rfm_days, v.openid')
                        //->where('v.store_code', $store)
                        ->where('v.code', 'in', $vipCode)
                        ->where('v.rfm_days', '<=', $n_consumption['n_consumption'])
                        ->where('v.rfm_days', '>', 0)
                        ->where('f.num', '>=', $rday['n_intervalone'])
                        ->where('f.num', '<', $rday['n_intervaltwo'])
                        ->select();
                }
            } else if ($rfmType == 'i') {
                //限制
                $rday = Db::table($db . '.vip_rfm_i')->where('id', $id)->find();
                // 周期
                $i_consumption = Db::table($db . '.vip_rfm_days')->field('i_consumption')->where('store_code', $store)->find();
                //查询的数据
                if ($rday['i_type'] != '无') {
                    $vipDataT = Db::table($db . '.view_vipinfo')
                        ->alias('v')
                        ->leftJoin($db . '.view_referral r', 'r.lnt_code = v.code')
                        ->field('r.count, v.*')
                        //->where('v.store_code', $store)
                        ->where('v.code', 'in', $vipCode)
                        ->where('r.lnttime', '>=', time() - (86400 * $i_consumption['i_consumption']))
                        ->where('r.lnttime', '<=', time())
                        ->where('r.count', '>=', $rday['i_intervalone'])
                        ->where('r.count', '<', $rday['i_intervaltwo'])
                        ->select();
                } else {
                    //有openid的数据
                    $vipDataT = Db::query('select * from ' . $db . '.view_vipinfo where code not in(select lnt_code from ' . $db . '.view_referral) and FIND_IN_SET(code,\'' . $vipCode . '\')');
                }
            } else if ($rfmType == 'p') {
                //限制
                $rday = Db::table($db . '.vip_rfm_p')->where('id', $id)->find();
                // 周期
                $p_consumption = Db::table($db . '.vip_rfm_days')->field('p_consumption')->where('store_code', $store)->find();
                //查询的数据 
                $vipDataT = Db::table($db . '.view_rfm_p')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('passenger, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $p_consumption['p_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.passenger', '>=', $rday['p_intervalone'])
                    ->where('f.passenger', '<', $rday['p_intervaltwo'])
                    ->select();
            } else if ($rfmType == 'a') {
                //限制
                $rday = Db::table($db . '.vip_rfm_a')->where('id', $id)->find();
                // 周期
                $a_consumption = Db::table($db . '.vip_rfm_days')->field('a_consumption')->where('store_code', $store)->find();
                //查询的数据 
                $vipDataT = Db::table($db . '.view_rfm_a')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('piece, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $a_consumption['a_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.piece', '>=', $rday['a_intervalone'])
                    ->where('f.piece', '<', $rday['a_intervaltwo'])
                    ->select();
            } else if ($rfmType == 'j') {
                //限制
                $rday = Db::table($db . '.vip_rfm_j')->where('id', $id)->find();
                // 周期
                $j_consumption = Db::table($db . '.vip_rfm_days')->field('j_consumption')->where('store_code', $store)->find();
                //查询的数据 
                $vipDataT = Db::table($db . '.view_rfm_j')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('rate, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $j_consumption['j_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.rate', '>=', $rday['j_intervalone'])
                    ->where('f.rate', '<', $rday['j_intervaltwo'])
                    ->select();
            } else if ($rfmType == 'c') {
                //限制
                $rday = Db::table($db . '.vip_rfm_c')->where('id', $id)->find();
                // 周期
                $c_consumption = Db::table($db . '.vip_rfm_days')->field('c_consumption')->where('store_code', $store)->find();
                //查询的数据 
                $vipDataT = Db::table($db . '.view_rfm_c')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('sum, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $c_consumption['c_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.sum', '>=', $rday['c_intervalone'])
                    ->where('f.sum', '<', $rday['c_intervaltwo'])
                    ->select();
            }
            //提取会员的手机号并更改格式
            $phone = array_column($vipDataT, 'phone');
            //调用接口发送视图 $phone会员手机号
            $count = array_chunk($phone, 100);
            $msg = Db::table('company.vip_business')->field('code, fuxin_msg')->where('code', $this->db)->find();
            //判断短信条数
            if ($msg['fuxin_msg'] < count($vipDataT)) {
                webApi(400, '视图条数不足，可用视图为' . $msg['fuxin_msg'] . '条' . '，当前发送视图信为' . count($vipDataT) . '条');
            }
            //获取缓存中图片帧数
            $imgcache = cache('getMsgId_' . session('info.staff'));
            //判断缓存中是否有值
            if ($imgcache == false) {
                webApi(400, '没有添加图文');
            }
            Db::startTrans();
            try {
                for ($i = 0; $i < count($count); $i++) {
                    $es->fuXin($count[$i], input('content'), $imgcache);
                }
                // 提交事务
                Db::commit();
                $res = true;
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $res = false;
            }
            unset($imgcache, $msg, $id, $store, $rday, $count, $phone, $es);
            cache('getMsgId_' . session('info.staff'), null);  // 请求完清除缓存信息
            if ($res) {
                Db::table('company.vip_business')->where('code', $this->db)->setDec('fuxin_msg', count($vipDataT));
                webApi(200, '发送成功');
            } else {
                webApi(400, '发送失败');
            }
        }
    }

    /**
     * 不分openid群发卡券
     */
    public function groupCardM()
    {
        // 获取数据
        [$id, $store, $db, $type, $coupon_code, $rfmType, $barCode] = [
            input('id'), input('store_code'), $this->db, input('card_type'), input('coupon'), input('rfm_type'), input('bar_code')
        ];
        if ($type == null) {
            webApi(400, '卡券类型错误');
        }
        $es = new ErpWhere($db, "");
        $vipCode = $es->goodsVip($barCode);
        //判断
        if (empty($vipCode)) {
            //返回数据
            webApi(400, '赠送失败!');
        } else {
            //获取所需条件
            if ($rfmType == 'r') {
                //条件
                $rday = Db::table($db . '.vip_rfm_r')->where('id', $id)->find();
                //查询的数据
                $vipData = Db::table($db . '.view_vipinfo')
                    // ----卡号, ----姓名, ----手机号
                    ->field('code, username, phone, rfm_days, openid')
                    // ->where('store_code', $store)
                    ->where('code', 'in', $vipCode)
                    ->where('rfm_days', '>=', $rday['r_intervalone'])
                    ->where('rfm_days', '<=', $rday['r_intervaltwo'])
                    ->select();
                //有openid的
                $vipDataT = Db::table($db . '.view_vipinfo')
                    // ----卡号, ----姓名, ----手机号
                    ->field('code, username, phone, rfm_days, openid')
                    ->where('openid', '<>', "")
                    // ->where('store_code', $store)
                    ->where('code', 'in', $vipCode)
                    ->where('rfm_days', '>=', $rday['r_intervalone'])
                    ->where('rfm_days', '<=', $rday['r_intervaltwo'])
                    ->select();
            } else if ($rfmType == 'f') {
                //条件
                $rday = Db::table($db . '.vip_rfm_f')->where('id', $id)->find();
                // 周期
                $f_consumption = Db::table($db . '.vip_rfm_days')->field('f_consumption')->where('store_code', $store)->find();
                //查询的数据
                if ($rday['f_type'] == "无") {
                    //查询大于未消费天数且已消费的人数 有openid的
                    $vipData = Db::table($this->db . '.view_vipinfo')
                        // ->where('store_code', $store)
                        ->where('code', 'in', $vipCode)
                        ->where('final_purchases', '<>', '')
                        ->where('rfm_days', '>', $f_consumption['f_consumption'])
                        ->select();
                    //没有openid的
                    $vipDataT = Db::table($this->db . '.view_vipinfo')
                        // ->where('store_code', $store)
                        ->where('code', 'in', $vipCode)
                        ->where('openid', '<>', "")
                        ->where('final_purchases', '<>', '')
                        ->where('rfm_days', '>', $f_consumption['f_consumption'])
                        ->select();
                } else {
                    //查询有openid的数据
                    $vipData = Db::table($db . '.view_rfm_f')
                        ->alias('f')
                        ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                        ->field('count, v.code, v.username, v.phone, v.rfm_days, v.openid')
                        //->where('v.store_code', $store)
                        ->where('v.code', 'in', $vipCode)
                        ->where('v.rfm_days', '<=', $f_consumption['f_consumption'])
                        ->where('v.rfm_days', '>', 0)
                        ->where('f.count', '>=', $rday['f_intervalone'])
                        ->where('f.count', '<', $rday['f_intervaltwo'])
                        ->select();
                    //没有openid的
                    $vipDataT = Db::table($db . '.view_rfm_f')
                        ->alias('f')
                        ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                        ->field('count, v.code, v.username, v.phone, v.rfm_days, v.openid')
                        //->where('v.store_code', $store)
                        ->where('v.code', 'in', $vipCode)
                        ->where('v.openid', '<>', "")
                        ->where('v.rfm_days', '<=', $f_consumption['f_consumption'])
                        ->where('v.rfm_days', '>', 0)
                        ->where('f.count', '>=', $rday['f_intervalone'])
                        ->where('f.count', '<', $rday['f_intervaltwo'])
                        ->select();
                }
            } else if ($rfmType == 'm') {
                //限制
                $rday = Db::table($db . '.vip_rfm_m')->where('id', $id)->find();
                // 周期
                $m_consumption = Db::table($db . '.vip_rfm_days')->field('m_consumption')->where('store_code', $store)->find();
                //查询的数据
                $vipData = Db::table($db . '.view_rfm_c')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('sum, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $m_consumption['m_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.sum', '>=', $rday['m_intervalone'])
                    ->where('f.sum', '<', $rday['m_intervaltwo'])
                    ->select();
                //有openid的
                $vipDataT = Db::table($db . '.view_rfm_c')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('sum, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    ->where('v.openid', '<>', "")
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $m_consumption['m_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.sum', '>=', $rday['m_intervalone'])
                    ->where('f.sum', '<', $rday['m_intervaltwo'])
                    ->select();
            } else if ($rfmType == 'n') {
                //限制
                $rday = Db::table($db . '.vip_rfm_n')->where('id', $id)->find();
                // 周期
                $n_consumption = Db::table($db . '.vip_rfm_days')->field('n_consumption')->where('store_code', $store)->find();
                //查询的数据
                if ($rday['n_type'] == "无") {
                    //查询大于未消费天数且已消费的人数 有openid的
                    $vipData = Db::table($this->db . '.view_vipinfo')
                        // ->where('store_code', $store)
                        ->where('code', 'in', $vipCode)
                        ->where('final_purchases', '<>', '')
                        ->where('rfm_days', '>', $n_consumption['n_consumption'])
                        ->select();
                    //没有openid的数据
                    $vipDataT = Db::table($this->db . '.view_vipinfo')
                        // ->where('store_code', $store)
                        ->where('code', 'in', $vipCode)
                        ->where('openid', '<>', "")
                        ->where('final_purchases', '<>', '')
                        ->where('rfm_days', '>', $n_consumption['n_consumption'])
                        ->select();
                } else {
                    //查询有openid的数据
                    $vipData = Db::table($db . '.view_rfm_n')
                        ->alias('f')
                        ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                        ->field('num, v.code, v.username, v.phone, v.rfm_days, v.openid')
                        //->where('v.store_code', $store)
                        ->where('v.code', 'in', $vipCode)
                        ->where('v.rfm_days', '<=', $n_consumption['n_consumption'])
                        ->where('v.rfm_days', '>', 0)
                        ->where('f.num', '>=', $rday['n_intervalone'])
                        ->where('f.num', '<', $rday['n_intervaltwo'])
                        ->select();
                    //没有openid的数据
                    $vipDataT = Db::table($db . '.view_rfm_n')
                        ->alias('f')
                        ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                        ->field('num, v.code, v.username, v.phone, v.rfm_days, v.openid')
                        ->where('v.openid', '<>', "")
                        //->where('v.store_code', $store)
                        ->where('v.code', 'in', $vipCode)
                        ->where('v.rfm_days', '<=', $n_consumption['n_consumption'])
                        ->where('v.rfm_days', '>', 0)
                        ->where('f.num', '>=', $rday['n_intervalone'])
                        ->where('f.num', '<', $rday['n_intervaltwo'])
                        ->select();
                }
            } else if ($rfmType == 'i') {
                //限制
                $rday = Db::table($db . '.vip_rfm_i')->where('id', $id)->find();
                // 周期
                $i_consumption = Db::table($db . '.vip_rfm_days')->field('i_consumption')->where('store_code', $store)->find();
                //查询的数据
                if ($rday['i_type'] != '无') {
                    //查询有openid的数据
                    $vipData = Db::table($db . '.view_vipinfo')
                        ->alias('v')
                        ->leftJoin($db . '.view_referral r', 'r.lnt_code = v.code')
                        ->field('r.count, v.*')
                        //->where('v.store_code', $store)
                        ->where('v.code', 'in', $vipCode)
                        ->where('r.lnttime', '>=', time() - (86400 * $i_consumption['i_consumption']))
                        ->where('r.lnttime', '<=', time())
                        ->where('r.count', '>=', $rday['i_intervalone'])
                        ->where('r.count', '<', $rday['i_intervaltwo'])
                        ->select();
                    //有openid的数据
                    $vipDataT = Db::table($db . '.view_vipinfo')
                        ->alias('v')
                        ->leftJoin($db . '.view_referral r', 'r.lnt_code = v.code')
                        ->field('r.count, v.*')
                        //->where('v.store_code', $store)
                        ->where('v.code', 'in', $vipCode)
                        ->where('v.openid', '<>', "")
                        ->where('r.lnttime', '>=', time() - (86400 * $i_consumption['i_consumption']))
                        ->where('r.lnttime', '<=', time())
                        ->where('r.count', '>=', $rday['i_intervalone'])
                        ->where('r.count', '<', $rday['i_intervaltwo'])
                        ->select();
                } else {
                    //有openid的数据
                    $vipData = Db::query('select * from ' . $db . '.view_vipinfo where code not in(select lnt_code from ' . $db . '.view_referral) AND FIND_IN_SET(code,\'' . $vipCode . '\')');
                    //没有openid的数据
                    $vipDataT = Db::query('select * from ' . $db . '.view_vipinfo where code not in(select lnt_code from ' . $db . '.view_referral) AND openid <> "" AND FIND_IN_SET(code,\'' . $vipCode . '\')');
                }
            } else if ($rfmType == 'p') {
                //限制
                $rday = Db::table($db . '.vip_rfm_p')->where('id', $id)->find();
                // 周期
                $p_consumption = Db::table($db . '.vip_rfm_days')->field('p_consumption')->where('store_code', $store)->find();
                //查询的数据 
                $vipData = Db::table($db . '.view_rfm_p')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('passenger, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $p_consumption['p_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.passenger', '>=', $rday['p_intervalone'])
                    ->where('f.passenger', '<', $rday['p_intervaltwo'])
                    ->select();
                //有openid的
                $vipDataT = Db::table($db . '.view_rfm_p')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('passenger, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    ->where('v.openid', '<>', "")
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $p_consumption['p_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.passenger', '>=', $rday['p_intervalone'])
                    ->where('f.passenger', '<', $rday['p_intervaltwo'])
                    ->select();
            } else if ($rfmType == 'a') {
                //限制
                $rday = Db::table($db . '.vip_rfm_a')->where('id', $id)->find();
                // 周期
                $a_consumption = Db::table($db . '.vip_rfm_days')->field('a_consumption')->where('store_code', $store)->find();
                //查询的数据 有openid的
                $vipData = Db::table($db . '.view_rfm_a')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('piece, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $a_consumption['a_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.piece', '>=', $rday['a_intervalone'])
                    ->where('f.piece', '<', $rday['a_intervaltwo'])
                    ->select();
                //没有openid的
                $vipDataT = Db::table($db . '.view_rfm_a')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('piece, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    ->where('v.openid', '<>', "")
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $a_consumption['a_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.piece', '>=', $rday['a_intervalone'])
                    ->where('f.piece', '<', $rday['a_intervaltwo'])
                    ->select();
            } else if ($rfmType == 'j') {
                //限制
                $rday = Db::table($db . '.vip_rfm_j')->where('id', $id)->find();
                // 周期
                $j_consumption = Db::table($db . '.vip_rfm_days')->field('j_consumption')->where('store_code', $store)->find();
                //查询的数据 有openid的
                $vipData = Db::table($db . '.view_rfm_j')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('rate, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $j_consumption['j_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.rate', '>=', $rday['j_intervalone'])
                    ->where('f.rate', '<', $rday['j_intervaltwo'])
                    ->select();
                //没有openid的
                $vipDataT = Db::table($db . '.view_rfm_j')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('rate, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    ->where('v.openid', '<>', "")
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $j_consumption['j_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.rate', '>=', $rday['j_intervalone'])
                    ->where('f.rate', '<', $rday['j_intervaltwo'])
                    ->select();
            } else if ($rfmType == 'c') {
                //限制
                $rday = Db::table($db . '.vip_rfm_c')->where('id', $id)->find();
                // 周期
                $c_consumption = Db::table($db . '.vip_rfm_days')->field('c_consumption')->where('store_code', $store)->find();
                //查询的数据 有openid的
                $vipData = Db::table($db . '.view_rfm_c')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('sum, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $c_consumption['c_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.sum', '>=', $rday['c_intervalone'])
                    ->where('f.sum', '<', $rday['c_intervaltwo'])
                    ->select();
                //没有openid的
                $vipDataT = Db::table($db . '.view_rfm_c')
                    ->alias('f')
                    ->leftJoin($db . '.view_vipinfo v', 'v.code = f.vip_code')
                    ->field('sum, v.code, v.username, v.phone, v.rfm_days, v.openid')
                    ->where('v.openid', '<>', "")
                    //->where('v.store_code', $store)
                    ->where('v.code', 'in', $vipCode)
                    ->where('v.rfm_days', '<=', $c_consumption['c_consumption'])
                    ->where('v.rfm_days', '>', 0)
                    ->where('f.sum', '>=', $rday['c_intervalone'])
                    ->where('f.sum', '<', $rday['c_intervaltwo'])
                    ->select();
            }
            //查询当前登入人
            $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
            //判断门店
            if ($operate['store_code'] == "") {
                $store_name = "无门店";
            } else {
                $store_name = Db::table($this->db . '.vip_store')->where('code', $operate['store_code'])->find()['name'];
            }
            //定义卡券金额, 折扣, 礼品
            $two = "";
            $three = "";
            //判断类型并返回数据
            if ($type == 0) {
                $one = Db::table($this->db . '.vip_cash_coupon')->where('code', $coupon_code)->find(); // 优惠券
                $two = $one['card_money'];
                $three = $one['money'];
                $one['coupon_name'] = $one['card_money'] . '元，优惠劵';
            } else if ($type == 1) {
                $one = Db::table($this->db . '.vip_coupon')->where('code', $coupon_code)->find(); // 折扣券
                $two = $one['card_discount'];
                $three = $one['money'];
                $one['coupon_name'] = $one['card_discount'] . '折，折扣劵';
            } else if ($type == 2) {
                $one = Db::table($this->db . '.vip_coupon_gift')->where('code', $coupon_code)->find(); // 礼品券
                $two = $one['gift_code'];
                $three = 0;
                $one['coupon_name'] = $one['gift_code'] . '礼品劵';
            }
            //备注替换
            if (input('remark') != "") {
                $one['remark'] = input('remark');
            } else {
                $one['remark'] = "无备注说明.";
            }
            if ($one['xz'] == 1) {
                $start_time = time() + (86400 * $one['takeEffect']);
                $end_time = $start_time + (86400 * $one['effective']);
                
            } else {
                $start_time = $one['start_time'];
                $end_time = $one['end_time'];
            }
            $vcbr = 'VCBR' . str_replace('.', '', microtime(1));
            //普通卡券必须有openid
            if ($one['coupon_type'] == 0) {
                if ($vipDataT) {
                    foreach ($vipDataT as $k => $v) {
                        //卡券记录表
                        $couponRecord = Db::table($this->db . '.vip_coupon_record')->where('vip_code', $v['code'])->where('card_code', $one['code'])->count();
                        //限领张数限制 
                        if ($one['receive_limit'] != 0) {
                            if ($couponRecord >= $one['receive_limit']) {
                                // webApi(0, 'error', 0, $v['code'] . '会员该条卡券的限领张数已用完！');
                                unset($vipDataT[$k]);
                            }
                        }
                    }
                    //群发表数据
                    $datae = [
                        'code' => $vcbr,
                        'name' => input('name'),
                        'coupon_name' => $one['name'],
                        'coupon_type' => $type,
                        'card_name' => $one['coupon_type'],
                        'number' => count($vipDataT),
                        'time' => time()
                    ];
                    $res = Db::table($this->db . '.vip_coupon_batch_record')->insert($datae);
                    if ($res) {
                        foreach ($vipDataT as $k => $v) {
                            $title = '您好，【' . $v['username'] . '】您有卡券待领取！';
                            $es = new ErpWhere($this->db, "");
                            $es->pushCoupon($v['openid'], $title, $one, session('info.staff'));
                        }
                        webApi(200, 'ok', '赠送成功!');
                    } else {
                        webApi(400, '赠送失败!');
                    }
                } else {
                    webApi(400, '没有绑定微信的会员, 无法赠送普通卡券!');
                }
            } else {
                foreach ($vipData as $k => $v) {
                    //卡券记录表
                    $couponRecord = Db::table($this->db . '.vip_coupon_record')->where('vip_code', $v['code'])->where('card_code', $one['code'])->count();
                    //限领张数限制
                    if ($one['receive_limit'] != 0) {
                        if ($couponRecord >= $one['receive_limit']) {
                            // webApi(0, 'error', 0, $v['code'] . '会员该条卡券的限领张数已用完！');
                            unset($vipData[$k]);
                        }
                    }
                }
                foreach ($vipData as $k => $v) {
                    //添加的数据
                    $data[] = [
                        'code' => 'QFWXKQ' . ($v['phone'] + str_replace('.', '', microtime(1))),
                        'vip_code' => $v['code'],
                        'card_type' => $type,
                        'card_name' => $one['name'],
                        'coupon_type' => $one['coupon_type'],
                        'card_many' => $two,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'card_code' => $coupon_code,
                        'remark' => input('remark'),
                        'create_time' => time(),
                        'money' => $three,
                        'superposition' => $one['superposition'], // 是否可叠加使用  0 不可叠加  1 可叠加
                        'delivery' => $one['delivery'], // 是否允许收银员送劵  0 否 1 是
                        'integral' => $one['integral'],  // 所需积分兑换
                        'receive_limit' => $one['receive_limit'], // 限制每人领取张数
                        'a_hsi' => $one['a_hsi'], // 开始时间段
                        'b_hsi' => $one['b_hsi'], // 结束时间段
                        'week' => $one['week'], // 限制周几消费
                        'store_code' => $one['store_code'],
                        'store_name' => $one['store_name'],
                        'off_store_code' => $one['off_store_code'],
                        'off_store_name' => $one['off_store_name'],
                        'g_staff' => $operate['code'],
                        'g_staff_name' => $operate['name'],
                        'g_store' => $operate['store_code'],
                        'g_store_name' => $store_name,
                        'batch_record' => $vcbr
                    ];
                }
                //群发表数据
                $datae = [
                    'code' => $vcbr,
                    'name' => input('name'),
                    'coupon_name' => $one['name'],
                    'coupon_type' => $type,
                    'card_name' => $one['coupon_type'],
                    'number' => count($vipDataT),
                    'time' => time()
                ];
                // 启动事务
                Db::startTrans();
                try {
                    Db::table($this->db . '.vip_coupon_record')->insertAll($data);
                    Db::table($this->db . '.vip_coupon_batch_record')->insert($datae);
                    // 提交事务
                    Db::commit();
                    $res = true;
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $res = false;
                }
                //判断返回数据
                if ($res) {
                    if ($type == 0) {
                        Db::table($this->db . '.vip_cash_coupon')->where('code', $coupon_code)->setInc('receive', count($data)); // 优惠券
                    } else if ($type == 1) {
                        Db::table($this->db . '.vip_coupon')->where('code', $coupon_code)->setInc('receive', count($data)); // 折扣券
                    } else if ($type == 2) {
                        Db::table($this->db . '.vip_coupon_gift')->where('code', $coupon_code)->setInc('receive', count($data)); // 礼品券
                    }
                    if ($vipDataT) {
                        foreach ($vipDataT as $k => $v) {
                            $title = '您好，【' . $v['username'] . '】您收到一张卡券，请到会员中心查看或使用！';
                            $es = new ErpWhere($this->db, "");
                            $es->pushCoupon($v['openid'], $title, $one);
                        }
                        webApi(200, 'ok', '赠送成功, 没有绑定微信的会员不会发送微信消息!');
                    } else {
                        webApi(200, 'ok', '赠送成功, 没有绑定微信的会员不会发送微信消息!');
                    }
                } else {
                    webApi(400, 'ok', '赠送失败!');
                }
            }
        }
    }
}
