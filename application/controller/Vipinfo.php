<?php

namespace app\controller;

use think\Db;
use think\Controller;

/**
 *  Author lxy
 * Date 2019/06/25
 * Description 会员资料
 */
class Vipinfo extends Common
{
    /**
     * 会员资料
     */
    public function vipinfoSel()
    {
        //获取openid
        $openId = input('openid') ?? null;
        if ($openId == null) {
            webApi(400, '参数错误!');
        }
        //查询的数据
        $data = Db::table($this->db . '.view_vipinfo')->where('openid', $openId)->find();
        $residual_value = Db::table($this->db.'.vip_viplist')->where('code', $data['code'])->find();
        $data['residual_value'] = $residual_value['residual_value'];
        //清除变量
        unset($openId);
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 销售流水
     */
    public function salesFlowSel()
    {
        // 获取所需数据
        [$limit, $page, $card] = [input('limit'), input('page'), input('code')];
        // 判断获取数据
        if ($card == null) {
            webApi(400, '参数错误!');
        }
        //查询所需数据
        $data = Db::table($this->db . '.vip_goods_order')
            ->alias('o')
            ->leftJoin($this->db . '.vip_store s', 'o.store_code = s.code')
            ->field('o.*,s.name sname')
            ->where('o.vip_code', $card)
            ->order('o.create_time', 'desc')
            ->page($page, $limit)
            ->select();
        //查询所需数据的数量
        $count = Db::table($this->db . '.vip_goods_order')
            ->alias('o')
            ->leftJoin($this->db . '.vip_store s', 'o.store_code = s.code')
            ->field('o.*,s.name sname')
            ->where('o.vip_code', $card)
            ->count();
        //组装格式化的数据
        $money = ['money', 'dis_money', 'real_pay', 'real_income', 'integral_balance', 'storage_balance', 'cash_pay', 'wechat_pay', 'ali_pay', 'union_pay', 'not_small_change', 'give_change', 'pay_return_money'];
        //计算数量
        $mc = count($money);
        // 格式化数据
        foreach ($data as $k => $v) {
            $data[$k]['time_g'] = date('Y-m-d H:i:s', $v['create_time']);
            for ($i = 0; $i < $mc; $i++) {
                $data[$k][$money[$i]] = number_format($v[$money[$i]], 2, '.', '');
            }
            if ($v['status'] == 0) {
                $data[$k]['status'] = '正常';
            } else {
                $data[$k]['status'] = '已退货';
            }
        }
        //清除变量
        unset($page, $limit, $card, $money, $mc, $store);
        //格式化数据
        $data = [
            'count' => $count,
            'data' => $data
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 积分流水
     */
    public function integralSel()
    {
        // 获取所需数据
        [$limit, $page, $card] = [input('limit'), input('page'), input('code')];
        // 判断获取数据
        if ($card == null) {
            webApi(400, '参数错误!');
        }
        //查询所需数据的数量
        $count = Db::table($this->db . '.vip_flow_integral')
            ->alias('v')
            ->leftJoin($this->db . '.vip_viplist vh', 'vh.code = v.vip_code')
            ->leftJoin($this->db . '.vip_store vp', 'vp.code = v.store_code')
            ->field('v.*,vp.name vpname, vh.username vhname')
            ->order('v.create_time', 'desc') //按照登记时间降序排列
            ->where('v.vip_code', $card)
            ->count();
        //查询所需数据
        $data = Db::table($this->db . '.vip_flow_integral')
            ->alias('v')
            ->leftJoin($this->db . '.vip_viplist vh', 'vh.code = v.vip_code')
            ->leftJoin($this->db . '.vip_store vp', 'vp.code = v.store_code')
            ->field('v.*,vp.name vpname, vh.username vhname')
            ->order('v.create_time', 'desc') //按照登记时间降序排列
            ->where('v.vip_code', $card)
            ->page($page, $limit)
            ->select();
        // 修改格式
        foreach ($data as $k => $v) {
            //时间格式的转换
            $data[$k]['time_g'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        // 清除变量
        unset($page, $limit, $card, $store);
        //格式化数据
        $data = [
            'count' => $count,
            'data' => $data
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 基本资料
     */
    public function staffInfo()
    {
        //查询当前登入人
        $staff = session('info.staff');
        //判断
        if ($staff == null) {
            webApi(400, '参数错误!');
        }
        $data = Db::table($this->db . '.view_vip_staff')->where('code', $staff)->find();
        //查询管理机构
        $admin_org = Db::table($this->db . '.vip_org')->where('code', 'in', $data['admin_org_code'])->select();
        if ($admin_org) {
            $admin_org = implode(',', array_column($admin_org, 'name'));
        } else {
            $admin_org = '';
        }
        //判断员工内容
        if ($data['vpname'] == "") {
            $data['vpname'] = '无职位';
        }
        //格式化数据
        $data = [
            'data' => $data,
            'admin' => $admin_org,
            'company' => $this->db
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 家属查表
     */
    public function familySel()
    {
        // 获取所需数据
        [$limit, $page, $card] = [input('limit'), input('page'), input('card_number')];
        // 判断获取数据
        if ($card == null) {
            webApi(1, '请重新读卡！');
        }
        //统计数量
        $count = Db::table($this->db . '.vip_family')
            ->where('vip_code', $card)
            ->order('execution_time', 'desc')
            ->count();
        //查询的数据
        $data = Db::table($this->db . '.vip_family')
            ->where('vip_code', $card)
            ->order('execution_time', 'desc') //按照登记时间降序排列
            ->page($page, $limit)
            ->select();
        //格式化数据
        foreach ($data as $k => $v) {
            $data[$k]['birthday_g'] = date('Y-m-d', $v['date']);
        }
        $data = [
            'count' => $count,
            'data' => $data
        ];
        //清除变量
        unset($page, $limit, $card);

        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 家属
     */
    public function familyAdd()
    {
        //添加的数据
        $data = [
            'vip_code' => input('card_number'),
            'vip_name' => input('name'),
            'relation' => input('relationship'),
            'date' => strtotime(input('birthday')),
            'phone' => input('cellphone'),
            'synopsis' => input('synopsis'),
            'execution_time' => time(),
            'code' => 'js' . str_replace('.', '', microtime(1)),
            'status' => input('status')
        ];
        //执行添加
        $res = Db::table($this->db . '.vip_family')->insert($data);
        //清除变量
        unset($data);
        //判断返回数据
        if ($res) {
            webApi(200, '添加成功!');
        } else {
            webApi(400, '添加失败!');
        }
    }

    /**
     * 家属编辑
     */
    public function familyEdit()
    {
        //修改的数据
        $data = [
            'id' => input('id'),
            'relation' => input('relationship'),
            'date' => strtotime(input('birthday')),
            'phone' => input('cellphone'),
            'synopsis' => input('synopsis')
        ];
        //执行添加
        $res = Db::table($this->db . '.vip_family')->update($data);
        //清除变量
        unset($data);
        //判断返回数据
        if ($res) {
            webApi(200, '修改成功!');
        } else {
            webApi(400, '修改失败!');
        }
    }
}
