<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 * Author lxy
 * Date 2020/08/03
 * Description V票
 */
class Vticket extends Common
{

    /**
     * 奖励V票  
     */
    public function vticket_reward()
    {
        if (empty(input('code'))) {
            webApi(400, '没有选择奖励人!');
        }
        $staff_name = Db::table($this->db.'.vip_staff')->where('code', session('info.staff'))->find()['name'];
        if (empty($staff_name)) {
            $staff_name = '';
        }
        $data = [
            'code' => input('code'),
            'name' => input('name'),
            'operator_code' => session('info.staff'),
            'operator_name' => $staff_name,
            'v_number' => input('number'),
            'v_time' => time(),
            'remarks' => input('remarks')
        ];
        $vticket = Db::table($this->db.'.vip_vticket')->where('code', input('code'))->find();
        Db::startTrans();
        try {
            Db::table($this->db.'.vip_vticket_reward')->insert($data);
            if (empty($vticket)) {
                $v_data = [
                    'code' => input('code'),
                    'name' => input('name'),
                    'number' => input('number')
                ];
                Db::table($this->db.'.vip_vticket')->insert($v_data);
            } else {
                Db::table($this->db.'.vip_vticket')->where('code', input('code'))->setInc('number', input('number'));
            }
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $res = false;
        }
        if ($res) {
            webApi(200, '成功');
        } else {
            webApi(400, '失败');
        }
    }

    /**
     * 兑换V票
     */
    public function vticket_exchange()
    {
        if (empty(input('code'))) {
            webApi(400, '没有选择兑换人!');
        }
        $staff_name = Db::table($this->db.'.vip_staff')->where('code', session('info.staff'))->find()['name'];
        if (empty($staff_name)) {
            $staff_name = '';
        }
        $data = [
            'code' => input('code'),
            'name' => input('name'),
            'operator_code' => session('info.staff'),
            'operator_name' => $staff_name,
            'v_number' => input('number'),
            'v_time' => time(),
            'remarks' => input('remarks')
        ];
        $vticket = Db::table($this->db.'.vip_vticket')->where('code', input('code'))->find();
        if ($vticket['number'] < input('number')) {
            webApi(400, '剩余卡劵不足,剩余:'.$vticket['number'].'张');
        } 
        Db::startTrans();
        try {
            Db::table($this->db.'.vip_vticket_exchange')->insert($data);
            Db::table($this->db.'.vip_vticket')->where('code', input('code'))->setDec('number', input('number'));
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $res = false;
        }
        if ($res) {
            webApi(200, '成功');
        } else {
            webApi(400, '失败');
        }
    }

    /**
     * 奖励记录
     */
    public function reward_record()
    {
        if (empty(input('code'))) {
            webApi(400, '缺少参数');
        }
            $where[] = ['code', '=', input('code')];
        $data = Db::table($this->db.'.vip_vticket_reward')->where($where)->order('v_time', 'desc')->page(input('page'), input('limit'))->select();
        if (!empty($data)) {
            foreach ($data as $k=>$v) {
                $data[$k]['time'] = date('Y-m-d H:i:s', $v['v_time']);
            }
        }
        $count = Db::table($this->db.'.vip_vticket_reward')->where($where)->count();
        $data = [
            'data' => $data,
            'count' => $count
        ];
        webApi(200, '成功', $data);
    }

    /**
     * 兑换记录
     */
    public function exchange_record()
    {
        if (empty(input('code'))) {
            webApi(400, '缺少参数');
        }
        $where[] = ['code', '=', input('code')];
        $data = Db::table($this->db.'.vip_vticket_exchange')->where($where)->order('v_time', 'desc')->page(input('page'), input('limit'))->select();
        if (!empty($data)) {
            foreach ($data as $k=>$v) {
                $data[$k]['time'] = date('Y-m-d H:i:s', $v['v_time']);
            }
        }
        $count = Db::table($this->db.'.vip_vticket_exchange')->where($where)->count();
        $data = [
            'data' => $data,
            'count' => $count
        ];
        webApi(200, '成功', $data);
    }

    /**
     * 我的V票剩余数量
     */
    public function vnumber()
    {
        [$page, $limit] = [input('page'), input('limit')];
        [$store, $staff, $org] = [input('store'), input('staff'), input('org')];
        $where = [];
        if (!empty($staff)) {
            $where[] = ['code', '=', $staff];
        } else if (!empty($store)) {
            $staff_code = Db::table($this->db . '.vip_staff')->where('store_code', $store)->field('code')->select();
            $arr = implode(',', array_column($staff_code, 'code'));
            $where[] = ['code', 'in', $arr];
        } else if (!empty($org)) {
            $db = $this->db;
            $ew = new ErpWhere($db, $org);
            $orgWhere = $ew->org()->store()->get();
            $a_org = explode(',', $operate['admin_org_code']);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $orgWhere = $orgWhere . ', ';
            }
            $staff_code = Db::table($this->db . '.vip_staff')->where('store_code', 'in', $orgWhere)->field('code')->select();
            if ($staff_code) {
                $arr = implode(',', array_column($staff_code, 'code'));
                $where[] = ['code', 'in', $arr];
            }
        }

        $data = Db::table($this->db.'.vip_vticket')->where($where)->page($page, $limit)->select();
        
        webApi(200, '成功', $data);
    }

    /**
     * 查询员工
     */
    public function Vstaff()
    {
        $where[] = ['name|phone|code', 'like', input('like').'%'];

        $data = Db::table($this->db. '.vip_staff')->field('name, code, phone')->where($where)->select();

        webApi(200, 'ok', $data);
    }


}