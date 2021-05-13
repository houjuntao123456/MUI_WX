<?php

namespace app\controller;

use think\Db;
use think\Controller;
// use app\service\ErpWhere;

/**
 * Author lhp
 * Date 2019/6/11
 * Description  8维RFM分析
 */
class Modelanalysis extends Common
{
    // R会员活跃度分析
    public function R()
    {
        // 定义/获取所需数据
        [$store, $staff_code, $db] = [input('store'), input('staff_code'), $this->db];
        // 数据内容
        $data = Db::table($db . '.vip_rfm_r')->field('r_intervalone, r_intervaltwo, r_score')->where('store_code', $store)->select();
        $xy = [];
        foreach ($data as $k => $v) {
            $data[$k]['numbertime'] = 0;
            $xy['x'][$k] = $v['r_score'];
        }
        // 获取所需会员
        if ($staff_code != "") {
            $where[] = ['v.consultant_code', '=', $staff_code];
        } else {
            $where[] = ['v.store_code', '=', $store];
        }
        $vipData = Db::table($db . '.vip_viplist')
            ->alias('v')
            ->leftJoin($db . '.vip_goods_order o', 'o.vip_code = v.code')
            // 会员的code，时间
            ->field('v.code, ifnull(MAX(o.create_time), 0) as order_time')
            ->where('(o.status = 0 or o.status IS null)')
            ->where($where)
            ->group('v.code')
            ->select();
        $t = time();
        $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
        foreach ($vipData as $k => $v) {
            if ($v['order_time'] >= $start) {
                $vipData[$k]['rfm_days'] = 0;
            } else {
                $vipData[$k]['rfm_days'] = intval(round((time() - $v['order_time']) / 86400));
            }
        }
        // 格式化数据
        foreach ($data as $k => $v) {
            foreach ($vipData as $val) {
                if ($v['r_intervalone'] <= $val['rfm_days'] && $v['r_intervaltwo'] >= $val['rfm_days']) {
                    $data[$k]['numbertime'] += 1;
                }
            }
        }
        foreach ($data as $k => $v) {
            $xy['y'][$k] = $v['numbertime'];
        }
        unset($data, $vipData);
        webApi(200, 'ok', $xy);
    }

    // F消费次数分析
    public function F()
    {
        // 定义/获取所需数据
        [$store, $staff_code, $db] = [input('store'), input('staff_code'), $this->db];
        $data = Db::table($db . '.vip_rfm_f')->field('f_intervalone, f_intervaltwo, f_type, f_score')->where('store_code', $store)->select();
        $xy = [];
        foreach ($data as $k => $v) {
            $data[$k]['numbertime'] = 0;
            $xy['x'][$k] = $v['f_score'];
        }
        // 查询周期
        $f_consumption = Db::table($db . '.vip_rfm_days')
            ->field('f_consumption')
            ->where('store_code', $store)
            ->find();
        // 获取所需会员
        if ($staff_code != "") {
            $where[] = ['v.consultant_code', '=', $staff_code];
        } else {
            $where[] = ['v.store_code', '=', $store];
        }
        $vipa = Db::table($db . '.vip_viplist')
            ->alias('v')
            ->leftJoin($db . '.vip_goods_order o', 'o.vip_code = v.code')
            // 会员code，时间， 总次数
            ->field('v.code, ifnull(MAX(o.create_time), 0) as order_time, count(o.id) as consumption_times')
            ->where('(o.status = 0 or o.status IS null)')
            ->where($where)
            ->group('v.code')
            ->select();
        $t = time();
        $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
        foreach ($vipa as $k => $v) {
            if ($v['order_time'] >= $start) {
                $vipa[$k]['rfm_days'] = 0;
            } else {
                $vipa[$k]['rfm_days'] = intval(round((time() - $v['order_time']) / 86400));
            }
        }
        // wu 
        $wu = 0;
        foreach ($vipa as $k => $v) {
            if ($v['rfm_days'] > $f_consumption['f_consumption']) {
                $wu += 1;
            }
            if (!(($v['rfm_days'] >= 0) && ($v['rfm_days'] <= $f_consumption['f_consumption']))) {
                unset($vipa[$k]);
            }
        }
        sort($vipa);
        // 格式化数据
        foreach ($data as $k => $v) {
            if ($v['f_type'] == "无") {
                $data[$k]['numbertime'] = $wu;
            } else {
                foreach ($vipa as $val) {
                    if ($v['f_intervalone'] <= $val['consumption_times'] && $v['f_intervaltwo'] > $val['consumption_times']) {
                        $data[$k]['numbertime'] += 1;
                    }
                }
            }
        }
        foreach ($data as $k => $v) {
            $xy['y'][$k] = $v['numbertime'];
        }
        unset($data, $vipa);
        webApi(200, 'ok', $xy);
    }

    // M消费总金额分析
    public function M()
    {
        // 定义/获取所需数据
        [$store, $staff_code, $db] = [input('store'), input('staff_code'), $this->db];
        $data = Db::table($db . '.vip_rfm_m')->where('store_code', $store)->field('m_intervalone, m_intervaltwo, m_score')->select();
        $xy = [];
        foreach ($data as $k => $v) {
            $data[$k]['numbertime'] = 0;
            $xy['x'][$k] = $v['m_score'];
        }
        //周期
        $m_consumption = Db::table($db . '.vip_rfm_days')
            ->field('m_consumption')
            ->where('store_code', $store)
            ->find();
        // 查询人数需要条件
        if ($staff_code != "") {
            $where[] = ['v.consultant_code', '=', $staff_code];
        } else {
            $where[] = ['v.store_code', '=', $store];
        }
        $vipa = Db::table($db . '.vip_viplist')
            ->alias('v')
            ->leftJoin($db . '.vip_goods_order o', 'o.vip_code = v.code')
            // 会员code，时间，实际支付总金额
            ->field('v.code, ifnull(MAX(o.create_time), 0) as order_time, ifnull(round(sum(o.real_pay), 2), 0) as total_consumption')
            ->where('(o.status = 0 or o.status IS null)')
            ->where($where)
            ->group('v.code')
            ->select();
        $t = time();
        $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
        foreach ($vipa as $k => $v) {
            if ($v['order_time'] >= $start) {
                $vipa[$k]['rfm_days'] = 0;
            } else {
                $vipa[$k]['rfm_days'] = intval(round((time() - $v['order_time']) / 86400));
            }
        }
        foreach ($vipa as $k => $v) {
            if (!(($v['rfm_days'] >= 0) && ($v['rfm_days'] <= $m_consumption['m_consumption']))) {
                if ($v['order_time'] != 0) {
                    unset($vipa[$k]);
                }
            }
        }
        sort($vipa);
        foreach ($data as $k => $v) {
            foreach ($vipa as $val) {
                if ($v['m_intervalone'] <= $val['total_consumption'] && $v['m_intervaltwo'] > $val['total_consumption']) {
                    $data[$k]['numbertime'] += 1;
                }
            }
        }
        foreach ($data as $k => $v) {
            $xy['y'][$k] = $v['numbertime'];
        }
        unset($data, $vipa);
        webApi(200, 'ok', $xy);
    }

    // N消费件数分析
    public function N()
    {
        // 定义/获取所需数据
        [$store, $staff_code, $db] = [input('store'), input('staff_code'), $this->db];
        $data = Db::table($db . '.vip_rfm_n')->field('n_intervalone, n_intervaltwo, n_type, n_score')->where('store_code', $store)->select();
        $xy = [];
        foreach ($data as $k => $v) {
            $data[$k]['numbertime'] = 0;
            $xy['x'][$k] = $v['n_score'];
        }
        //周期
        $n_consumption = Db::table($db . '.vip_rfm_days')
            ->field('n_consumption')
            ->where('store_code', $store)
            ->find();
        if ($staff_code != "") {
            $where[] = ['v.consultant_code', '=', $staff_code];
        } else {
            $where[] = ['v.store_code', '=', $store];
        }
        $vipa = Db::table($db . '.vip_viplist')
            ->alias('v')
            ->leftJoin($db . '.vip_goods_order o', 'o.vip_code = v.code')
            // 会员code，时间， 总件数
            ->field('v.code, ifnull(MAX(o.create_time), 0) as order_time, ifnull(round(sum(o.number)), 0) as consumption_number')
            ->where('(o.status = 0 or o.status IS null)')
            ->where($where)
            ->group('v.code')
            ->select();
        $t = time();
        $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
        foreach ($vipa as $k => $v) {
            if ($v['order_time'] >= $start) {
                $vipa[$k]['rfm_days'] = 0;
            } else {
                $vipa[$k]['rfm_days'] = intval(round((time() - $v['order_time']) / 86400));
            }
        }
        // wu 
        $wu = 0;
        foreach ($vipa as $k => $v) {
            if ($v['rfm_days'] > $n_consumption['n_consumption']) {
                $wu += 1;
            }
            if (!(($v['rfm_days'] >= 0) && ($v['rfm_days'] <= $n_consumption['n_consumption']))) {
                unset($vipa[$k]);
            }
        }
        sort($vipa);
        foreach ($data as $k => $v) {
            if ($v['n_type'] == "无") {
                $data[$k]['numbertime'] = $wu;
            } else {
                foreach ($vipa as $val) {
                    if ($v['n_intervalone'] <= $val['consumption_number'] && $v['n_intervaltwo'] > $val['consumption_number']) {
                        $data[$k]['numbertime'] += 1;
                    }
                }
            }
        }
        foreach ($data as $k => $v) {
            $xy['y'][$k] = $v['numbertime'];
        }
        unset($data, $vipa);
        webApi(200, 'ok', $xy);
    }

    // I转介绍人分析
    public function I()
    {
        [$store, $staff_code, $db] = [input('store'), input('staff_code'), $this->db];
        $data = Db::table($db . '.vip_rfm_i')->field('i_type, i_intervalone, i_intervaltwo, i_score')->where('store_code', $store)->select();
        $xy = [];
        foreach ($data as $k => $v) {
            $data[$k]['numbertime'] = 0;
            $xy['x'][$k] = $v['i_score'];
        }
        // 周期
        $i_consumption = Db::table($db . '.vip_rfm_days')->field('i_consumption')->where('store_code', $store)->find();
        //查询会员
        if ($staff_code != "") {
            $where[] = ['v.consultant_code', '=', $staff_code];
        } else {
            $where[] = ['v.store_code', '=', $store];
        }
        $vipa = Db::table($db . '.vip_viplist')
            ->alias('v')
            ->leftJoin($db . '.vip_goods_order o', 'o.vip_code = v.code')
            ->field('v.code, ifnull(MAX(o.create_time), 0) as order_time')
            ->where('(o.status = 0 or o.status IS null)')
            ->where($where)
            ->group('v.code')
            ->select();
        $t = time();
        $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
        foreach ($vipa as $k => $v) {
            if ($v['order_time'] >= $start) {
                $vipa[$k]['rfm_days'] = 0;
            } else {
                $vipa[$k]['rfm_days'] = intval(round((time() - $v['order_time']) / 86400));
            }
            $vipa[$k]['fcs'] = Db::table($db . '.vip_introducer')
                ->where('lnt_code', $v['code'])
                ->count();
        }
        foreach ($vipa as $k => $v) {
            if (!(($v['rfm_days'] >= 0) && ($v['rfm_days'] <= $i_consumption['i_consumption']))) {
                if ($v['order_time'] != 0) {
                    unset($vipa[$k]);
                }
            }
        }
        sort($vipa);
        foreach ($data as $k => $v) {
            foreach ($vipa as $val) {
                if ($v['i_intervalone'] <= $val['fcs'] && $v['i_intervaltwo'] > $val['fcs']) {
                    $data[$k]['numbertime'] += 1;
                }
            }
        }
        foreach ($data as $k => $v) {
            $xy['y'][$k] = $v['numbertime'];
        }
        unset($data, $vipa);
        webApi(200, 'ok', $xy);
    }

    // P消费客单价分析
    public function P()
    {
        // 定义/获取所需数据
        [$store, $staff_code, $db] = [input('store'), input('staff_code'), $this->db];
        $data = Db::table($db . '.vip_rfm_p')->field('p_intervalone, p_intervaltwo, p_score')->where('store_code', $store)->select();
        $xy = [];
        foreach ($data as $k => $v) {
            $data[$k]['numbertime'] = 0;
            $xy['x'][$k] = $v['p_score'];
        }
        // 周期
        $p_consumption = Db::table($db . '.vip_rfm_days')
            ->field('p_consumption')
            ->where('store_code', $store)
            ->find();
        // 查询人数需要条件
        if ($staff_code != "") {
            $where[] = ['v.consultant_code', '=', $staff_code];
        } else {
            $where[] = ['v.store_code', '=', $store];
        }
        $vipa = Db::table($db . '.vip_viplist')
            ->alias('v')
            ->leftJoin($db . '.vip_goods_order o', 'o.vip_code = v.code')
            // 会员code，时间， 实际支付总金额， 总次数
            ->field('v.code, ifnull(MAX(o.create_time), 0) as order_time, ifnull(round(sum(o.real_pay), 2), 0) as total_consumption, 
                    count(o.id) as consumption_times')
            ->where('(o.status = 0 or o.status IS null)')
            ->where($where)
            ->group('v.code')
            ->select();
        $t = time();
        $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
        foreach ($vipa as $k => $v) {
            if ($v['order_time'] >= $start) {
                $vipa[$k]['rfm_days'] = 0;
            } else {
                $vipa[$k]['rfm_days'] = intval(round((time() - $v['order_time']) / 86400));
            }
        }
        foreach ($vipa as $k => $v) {
            if (!(($v['rfm_days'] >= 0) && ($v['rfm_days'] <= $p_consumption['p_consumption']))) {
                if ($v['order_time'] != 0) {
                    unset($vipa[$k]);
                }
            }
        }
        sort($vipa);
        foreach ($data as $k => $v) {
            foreach ($vipa as $val) {
                //客单价 = 会员实际支付总金额/总次数
                if ($val['total_consumption'] ==  0 || $val['consumption_times'] == 0) {
                    $pm = 0;
                } else {
                    $pm = $val['total_consumption'] / $val['consumption_times'];
                }
                if ($v['p_intervalone'] <= $pm && $v['p_intervaltwo'] > $pm) {
                    $data[$k]['numbertime'] += 1;
                }
            }
        }
        foreach ($data as $k => $v) {
            $xy['y'][$k] = $v['numbertime'];
        }
        unset($data, $vipa);
        webApi(200, 'ok', $xy);
    }

    // A消费件单价分析
    public function A()
    {
        // 定义/获取所需数据
        [$store, $staff_code, $db] = [input('store'), input('staff_code'), $this->db];
        $data = Db::table($db . '.vip_rfm_a')->field('a_intervalone, a_intervaltwo, a_score')->where('store_code', $store)->select();
        $xy = [];
        foreach ($data as $k => $v) {
            $data[$k]['numbertime'] = 0;
            $xy['x'][$k] = $v['a_score'];
        }
        //周期
        $a_consumption = Db::table($db . '.vip_rfm_days')->field('a_consumption')
            ->where('store_code', $store)
            ->find();
        // 查询人数需要条件
        if ($staff_code != "") {
            $where[] = ['v.consultant_code', '=', $staff_code];
        } else {
            $where[] = ['v.store_code', '=', $store];
        }
        $vipa = Db::table($db . '.vip_viplist')
            ->alias('v')
            ->leftJoin($db . '.vip_goods_order o', 'o.vip_code = v.code')
            // 会员code，时间， 实际支付总金额， 总件数
            ->field('v.*, ifnull(MAX(o.create_time), 0) as order_time, ifnull(round(sum(o.real_pay), 2), 0) as total_consumption, 
                    ifnull(round(sum(o.number)), 0) as consumption_number')
            ->where('(o.status = 0 or o.status IS null)')
            ->where($where)
            ->group('v.code')
            ->select();
        $t = time();
        $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
        foreach ($vipa as $k => $v) {
            if ($v['order_time'] >= $start) {
                $vipa[$k]['rfm_days'] = 0;
            } else {
                $vipa[$k]['rfm_days'] = intval(round((time() - $v['order_time']) / 86400));
            }
        }
        foreach ($vipa as $k => $v) {
            if (!(($v['rfm_days'] >= 0) && ($v['rfm_days'] <= $a_consumption['a_consumption']))) {
                if ($v['order_time'] != 0) {
                    unset($vipa[$k]);
                }
            }
        }
        sort($vipa);
        foreach ($data as $k => $v) {
            foreach ($vipa as $val) {
                // 件单价 = 会员实际支付总金额/总件数
                if ($val['total_consumption'] == 0 || $val['consumption_number'] == 0) {
                    $pm = 0;
                } else {
                    $pm = $val['total_consumption'] / $val['consumption_number'];
                }
                if ($v['a_intervalone'] <= $pm && $v['a_intervaltwo'] > $pm) {
                    $data[$k]['numbertime'] += 1;
                }
            }
        }
        foreach ($data as $k => $v) {
            $xy['y'][$k] = $v['numbertime'];
        }
        unset($data, $vipa);
        webApi(200, 'ok', $xy);
    }

    // J消费连带率分析
    public function J()
    {
        // 定义/获取所需数据
        [$store, $staff_code, $db] = [input('store'), input('staff_code'), $this->db];
        $data = Db::table($db . '.vip_rfm_j')->field('j_intervalone, j_intervaltwo, j_score')->where('store_code', $store)->select();
        $xy = [];
        foreach ($data as $k => $v) {
            $data[$k]['numbertime'] = 0;
            $xy['x'][$k] = $v['j_score'];
        }
        $j_consumption = Db::table($db . '.vip_rfm_days')->field('j_consumption')
            ->where('store_code', $store)
            ->find();
        // 查询人数需要条件
        if ($staff_code != "") {
            $where[] = ['v.consultant_code', '=', $staff_code];
        } else {
            $where[] = ['v.store_code', '=', $store];
        }
        $vipa = Db::table($db . '.vip_viplist')
            ->alias('v')
            ->leftJoin($db . '.vip_goods_order o', 'o.vip_code = v.code')
            // 会员code，时间， 总次数， 总件数
            ->field('v.code, ifnull(MAX(o.create_time), 0) as order_time, 
                        count(o.id) as consumption_times, ifnull(round(sum(o.number)), 0) as consumption_number')
            ->where('(o.status = 0 or o.status IS null)')
            ->where($where)
            ->group('v.code')
            ->select();
        $t = time();
        $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
        foreach ($vipa as $k => $v) {
            if ($v['order_time'] >= $start) {
                $vipa[$k]['rfm_days'] = 0;
            } else {
                $vipa[$k]['rfm_days'] = intval(round((time() - $v['order_time']) / 86400));
            }
        }
        foreach ($vipa as $k => $v) {
            if (!(($v['rfm_days'] >= 0) && ($v['rfm_days'] <= $j_consumption['j_consumption']))) {
                if ($v['order_time'] != 0) {
                    unset($vipa[$k]);
                }
            }
        }
        sort($vipa);
        foreach ($data as $k => $v) {
            foreach ($vipa as $val) {
                // 连带率 = 总件数/总次数
                if ($val['consumption_number'] == 0 || $val['consumption_times'] == 0) {
                    $pm = 0;
                } else {
                    $pm = round($val['consumption_number'] / $val['consumption_times'], 2);
                }
                if ($v['j_intervalone'] <= $pm && $v['j_intervaltwo'] > $pm) {
                    $data[$k]['numbertime'] += 1;
                }
            }
        }
        foreach ($data as $k => $v) {
            $xy['y'][$k] = $v['numbertime'];
        }
        unset($data, $vipa);
        webApi(200, 'ok', $xy);
    }

    // C年消费分析
    public function C()
    {
        // 定义/获取所需数据
        [$store, $staff_code, $db] = [input('store'), input('staff_code'), $this->db];
        $data = Db::table($db . '.vip_rfm_c')->where('store_code', $store)->field('c_intervalone, c_intervaltwo, c_score')->select();
        $xy = [];
        foreach ($data as $k => $v) {
            $data[$k]['numbertime'] = 0;
            $xy['x'][$k] =  $v['c_score'];
        }
        //周期
        $c_consumption = Db::table($db . '.vip_rfm_days')->field('c_consumption')
            ->where('store_code', $store)
            ->find();
        // 查询人数需要条件
        if ($staff_code != "") {
            $where[] = ['v.consultant_code', '=', $staff_code];
        } else {
            $where[] = ['v.store_code', '=', $store];
        }
        $vipa = Db::table($db . '.vip_viplist')
            ->alias('v')
            ->leftJoin($db . '.vip_goods_order o', 'o.vip_code = v.code')
            // 会员code，时间，实际支付总金额
            ->field('v.code, ifnull(MAX(o.create_time), 0) as order_time, ifnull(round(sum(o.real_pay), 2), 0) as total_consumption')
            ->where('(o.status = 0 or o.status IS null)')
            ->where($where)
            ->group('v.code')
            ->select();
        $t = time();
        $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
        foreach ($vipa as $k => $v) {
            if ($v['order_time'] >= $start) {
                $vipa[$k]['rfm_days'] = 0;
            } else {
                $vipa[$k]['rfm_days'] = intval(round((time() - $v['order_time']) / 86400));
            }
        }
        foreach ($vipa as $k => $v) {
            if (!(($v['rfm_days'] >= 0) && ($v['rfm_days'] <= $c_consumption['c_consumption']))) {
                if ($v['order_time'] != 0) {
                    unset($vipa[$k]);
                }
            }
        }
        sort($vipa);
        foreach ($data as $k => $v) {
            foreach ($vipa as $val) {
                if ($v['c_intervalone'] <= $val['total_consumption'] && $v['c_intervaltwo'] > $val['total_consumption']) {
                    $data[$k]['numbertime'] += 1;
                }
            }
        }
        foreach ($data as $k => $v) {
            $xy['y'][$k] = $v['numbertime'];
        }
        unset($data, $vipa);
        webApi(200, 'ok', $xy);
    }
}
