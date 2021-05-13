<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 * Author lxy
 * Date 2019/03/15
 * Description 100天跟进
 */
class WreHundred extends Common
{
    /**
     * 100天跟进查询
     * 查询：有按跟进天数，机构，门店，形象顾问查询的
     * 默认的：跟进天数小于等于100天的
     * * * *：是员工登录的就查询，形象顾问是这个员工的会员
     * * * *：是管理登录的就查询，所属门店是这个管理的管理机构下的门店的会员
     * * * *：账号boss就查询，所有跟进天数小于等于100天的
     */
    public function WreHundredPeople()
    {
        // 获取数据
        [$page, $limit, $db] = [input('page'), input('limit'), $this->db];
        // 判断如果有员工就查询，形象顾问是这个员工的会员
        if (input('staff') != '') {
            $where[] = ['consultant_code', '=', input('staff')];
        } else {
            // 判断如果没有员工有门店就查询，所属门店是这个门店的会员
            if (input('store') != '') {
                $where[] = ['store_code', '=', input('store')];
            } else {
                // 判断如果没有员工也没有门店就查询，所属门店是这个组织机构下的门店的会员
                if (input('splb') != '') {
                    $ew = new ErpWhere($this->db, input('splb'));
                    $stores = $ew->org()->store()->get();
                    $where[] = ['store_code', 'in', $stores];
                } 
            }
        }
        // 跟进天数限制
        if (input('start') != '') {
            $where[] = ['visit_g', '>=', input('start')];
        } else {
            $where[] = ['visit_g', '>=', 0];
        }
        if (input('end') != '') {
            $where[] = ['visit_g', '<=', input('end')];
        } else {
            $where[] = ['visit_g', '<=', 100];
        }
        // 判断条件不存在就都查询，当前登入人 session('info.staff')
        $operate = Db::table($this->db . '.vip_staff')->where('status', 0)->where('code', session('info.staff'))->find();
        // 账号限制
        if ($operate['code'] == 'boss') {
            $w = true;
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $stores = $ew->org()->store()->get();
            $a_org = explode(',', $operate['admin_org_code']);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $stores = $stores . ', ';
            }
            $w[] = ['store_code', 'in', $stores];
        } else {
            $w[] = ['consultant_code', '=', $operate['code']];
        }
        //统计数量
        $count = Db::table($this->db . '.view_viplist_visit')
            ->where($w)
            ->where($where)
            ->count();
        //查询的数据
        $data = Db::table($this->db . '.view_viplist_visit')
            ->where($w)
            ->where($where)
            ->order('visit_g', 'desc')
            ->page($page, $limit)
            ->select();
        // 格式化数据
        foreach ($data as $k => $v) {
            // 最后购物时间
            $data[$k]['order_time'] = Db::table($this->db . '.vip_goods_order')
                ->field('ifnull(MAX(create_time), 0) as create_time')
                ->where('vip_code', $v['code'])
                ->find()['create_time'];
            // 订单实际支付金额
            $data[$k]['total_consumption'] = Db::table($this->db . '.vip_goods_order')
                ->field('ifnull(round(sum(real_pay), 2), 0) as real_pay')
                ->where('vip_code', $v['code'])
                ->find()['real_pay'];
            // 订单数量
            $data[$k]['consumption_times'] = Db::table($this->db . '.vip_goods_order')
                ->field('count(id) ids')
                ->where('vip_code', $v['code'])
                ->find()['ids'];
            // 订单件数
            $data[$k]['consumption_number'] = Db::table($this->db . '.vip_goods_order')
                ->field('ifnull(round(sum(number)), 0) as number')
                ->where('vip_code', $v['code'])
                ->find()['number'];
        }
        foreach ($data as $k => $v) {
            if ($v['order_time'] > time()) {
                $data[$k]['rfm_days'] = 0;
            } else {
                $data[$k]['rfm_days'] =  round((time() - $v['order_time']) / 86400);
            }
        }
        foreach ($data as $k => $v) {
            if ($v['rfm_days'] > 15000) {
                $data[$k]['rfm_days'] = '未消费';
            }
        }
        //清除变量
        unset($page, $limit, $db, $where, $w);
        //格式化数据
        $data = ['count' => $count, 'data' => $data];
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 查询100天跟进模板
     */
    public function WreHundredList()
    {
        //分页信息
        [$page, $limit, $search, $db] = [input('page'), input('limit'), input('search'), $this->db];
        //模糊查询
        if ($search != '') {
            $where[] = ['v.name', 'like', '%' . $search . '%'];
        } else {
            $where = true;
        }
        //查询当前登入人 //session('info.staff')
        $operate = Db::table($db . '.vip_staff')->where('code', session('info.staff'))->find();
        //所属机构限制
        if ($operate['code'] == 'boss') {
            $org = true;
        } else { //判断是否是管理
            if ($operate['role'] == 0) { //管理查询管理机构
                $org[] = ['v.org_code', 'in', $operate['admin_org_code'] . ',' . 'ZZJG15459825114064'];
            } else { //员工查询所属机构
                $org[] = ['v.org_code', 'in', $operate['org_code'] . ',' . 'ZZJG15459825114064'];
            }
        }
        //统计数量
        $count = Db::table($db . '.vip_inhundred_interaction')
            ->alias('v')
            ->leftJoin($db . '.vip_org vg', 'vg.code = v.org_code')
            ->field('v.*,vg.name vgname')
            ->where($where)
            ->where($org)
            ->count();
        //所需数据
        $data = Db::table($db . '.vip_inhundred_interaction')
            ->alias('v')
            ->leftJoin($db . '.vip_org vg', 'vg.code = v.org_code')
            ->field('v.*,vg.name vgname')
            ->where($where)
            ->where($org)
            ->order('v.time', 'asc') //按照时间排列
            ->page($page, $limit)
            ->select();
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

    /**
     * 100天互动记录
     */
    public function WreHundredRecord()
    { 
        //生成单号
        $foot = '100RHD' . str_replace('.', '', microtime(1));
        //按单号查询100模板
        $hundred = Db::table($this->db . '.vip_inhundred_interaction')->where('code', input('template_code'))->find();
        if ($hundred == null) {
            webApi(400, '参数错误!');
        }
        //添加记录的数据
        $record = [
            'code' => $foot,
            'name' => $hundred['name'],
            'time' => $hundred['time'],
            'vip_code' => input('vip_code'),
            'level_code' => input('level') ?? '',
            'executor_code' => session('info.staff'),
            'create_time' => time(),
            'template_code' => input('template_code')
        ];
        //回访记录添加
        $return_visit = [
            'user_name' => input('username'),
            'user_code' => input('vip_code'),
            'visit_operator' => session('info.staff'),
            'visit_mode' => '100天回访',
            'time' => time(),
            'content' => $hundred['speech']
        ];
        //修改会员的回访时间,以及微信记录操作人code
        $viplist_update = [
            'return_visit' => time(),
            'customer_service' => session('info.staff')
        ];
        // 启动事务并执行添加
        Db::startTrans();
        try {
            Db::table($this->db . '.vip_inhundred_record')->insert($record);
            Db::table($this->db . '.vip_returnvisit_record')->insert($return_visit);
            Db::table($this->db . '.vip_viplist')->where('code', input('vip_code'))->update($viplist_update);
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $res = false;
        }
        //清除变量
        unset($foot, $hundred, $record);
        //判断返回数据
        if ($res) {
            webApi(200, '添加成功!');
        } else {
            webApi(400, '添加失败!');
        }
    }

    /**
     * 按条件查询人数发送短信
     */
    public function messageMember()
    {
        // 获取数据
        [$message_content, $db] = [input('message_content'), $this->db];
        // 判断如果有员工就查询，形象顾问是这个员工的会员
        if (input('staff') != '') {
            $where[] = ['consultant_code', '=', input('staff')];
        } else {
            // 判断如果没有员工有门店就查询，所属门店是这个门店的会员
            if (input('store') != '') {
                $where[] = ['store_code', '=', input('store')];
            } else {
                // 判断如果没有员工也没有门店就查询，所属门店是这个组织机构下的门店的会员
                if (input('splb') != '') {
                    $ew = new ErpWhere($this->db, input('splb'));
                    $stores = $ew->org()->store()->get();
                    $where[] = ['store_code', 'in', $stores];
                }
            }
        }
        // 跟进天数限制
        if (input('start') != '') {
            $where[] = ['visit_g', '>=', input('start')];
        } else {
            $where[] = ['visit_g', '>=', 0];
        }
        if (input('end') != '') {
            $where[] = ['visit_g', '<=', input('end')];
        } else {
            $where[] = ['visit_g', '<=', 100];
        }
        // 判断条件不存在就都查询，当前登入人 session('info.staff')
        $operate = Db::table($this->db . '.vip_staff')->where('status', 0)->where('code', session('info.staff'))->find();
        // 账号限制
        if ($operate['code'] == 'boss') {
            $w = true;
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $stores = $ew->org()->store()->get();
            $a_org = explode(',', $operate['admin_org_code']);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $stores = $stores . ', ';
            }
            $w[] = ['store_code', 'in', $stores];
        } else {
            $w[] = ['consultant_code', '=', $operate['code']];
        }
        // 查询的数据
        $vipData = Db::table($this->db . '.view_viplist_visit')
            ->where($w)
            ->where($where)
            ->order('visit_g', 'desc')
            ->select();
        // 提取会员的手机号并更改格式
        $phone = array_column($vipData, 'phone');
        // 调用短信接口发送短信 $phone会员手机号 $message_content短信内容
        $count = array_chunk($phone, 100);
        $es = new ErpWhere($db, "");
        $message_count = count($vipData) * ceil($es->abslength($message_content) / 64);
        $msg = Db::table('company.vip_business')->field('code, usable_msg')->where('code', $db)->find();
        $sms = Db::table('company.vip_sms_autograph')->field('sms_autograph, business_code')->where('business_code', $db)->find();
        if (empty($sms)) {
            webApi(400, '短信签名未配置，请联系管理员进行配置');
        }
        // 判断短信条数
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
        // 清除变量
        unset($id, $store, $rday, $message_content, $db, $count, $phone, $es);
        // 判断返回数据
        if ($res) {
            Db::table('company.vip_business')->where('code', $db)->setDec('usable_msg', $message_count);
            webApi(200, '发送成功!');
        } else {
            webApi(400, $res);
        }
    }

    /**
     * 员工查询
     */
    public function staffSel()
    {
        //当前登入人
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['code'] == 'boss') {
            //查询的数据
            $data = Db::table($this->db . '.view_vip_staff')->select();
            //统计数量
            $count = Db::table($this->db . '.view_vip_staff')->count();
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $stores = $ew->org()->store()->get();
            $a_org = explode(',', $operate['admin_org_code']);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $stores = $stores . ', ';
            }
            //查询的数据
            $data = Db::table($this->db . '.view_vip_staff')
                ->where('store_code', 'in', $stores)
                ->select();
            //统计数量
            $count = Db::table($this->db . '.view_vip_staff')
                ->where('store_code', 'in', $stores)
                ->count();
        } else {
            //查询的数据
            $data = Db::table($this->db . '.view_vip_staff')
                ->where('code', session('info.staff'))
                ->select();
            //统计数量
            $count = Db::table($this->db . '.view_vip_staff')
                ->where('code', session('info.staff'))
                ->count();
        }
        //清除变量
        unset($operate, $where, $store, $stores, $a_org);
        //格式化数据
        $data = [
            'data' => $data,
            'count' => $count
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 跟进提醒查询数据
     */
    public function remindSel()
    {
        //获取数据
        $staff = input('staff');
        if (empty($staff)) {
            webApi(400, '缺少员工参数!');
        }
        //添加查询条件
        $where[] = ["consultant_code" , "=", $staff];
        //判断查询条件
        if (!isset($where)) {
            $where = true;
        }
        //查询数据
        $data = Db::table($this->db . '.vip_hundred_remind')->select();
        //格式化数据
        foreach ($data as $k => $v) {
            $data[$k]['count'] = Db::table($this->db . '.view_viplist_visit')
                ->where($where)
                ->where('visit_g', $v['number'])
                ->count();
        }
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 跟进提醒查询会员数据
     */
    public function remindPeopleSel()
    {
        [$page, $limit, $staff] = [input('page'), input('limit'), input('staff')];
        if (empty($staff)) {
            webApi(400, '缺少员工参数!');
        }
        //添加查询条件
        $where[] = ["consultant_code", "=", $staff];
        //判断查询条件
        if (!isset($where)) {
            $where = true;
        }
        //查询数据
        $data = Db::table($this->db . '.view_viplist_visit')
            ->where($where)
            ->where('visit_g', input('number'))
            ->page($page, $limit)
            ->select();
        //统计数量
        $count = Db::table($this->db . '.view_viplist_visit')
            ->where($where)
            ->where('visit_g', input('number'))
            ->count();
        // 格式化数据
        foreach ($data as $k => $v) {
            // 最后购物时间
            $data[$k]['order_time'] = Db::table($this->db . '.vip_goods_order')
                ->field('ifnull(MAX(create_time), 0) as create_time')
                ->where('vip_code', $v['code'])
                ->find()['create_time'];
            // 订单实际支付金额
            $data[$k]['total_consumption'] = Db::table($this->db . '.vip_goods_order')
                ->field('ifnull(round(sum(real_pay), 2), 0) as real_pay')
                ->where('vip_code', $v['code'])
                ->find()['real_pay'];
            // 订单数量
            $data[$k]['consumption_times'] = Db::table($this->db . '.vip_goods_order')
                ->field('count(id) ids')
                ->where('vip_code', $v['code'])
                ->find()['ids'];
            // 订单件数
            $data[$k]['consumption_number'] = Db::table($this->db . '.vip_goods_order')
                ->field('ifnull(round(sum(number)), 0) as number')
                ->where('vip_code', $v['code'])
                ->find()['number'];
        }
        foreach ($data as $k => $v) {
            if ($v['order_time'] > time()) {
                $data[$k]['rfm_days'] = 0;
            } else {
                $data[$k]['rfm_days'] =  round((time() - $v['order_time']) / 86400);
            }
        }
        foreach ($data as $k => $v) {
            if ($v['rfm_days'] > 15000) {
                $data[$k]['rfm_days'] = '未消费';
            }
            if ($v['visit_g'] > 15000) {
                $data[$k]['visit_g'] = '未回访';
            }
        }
        $data = [
            'data' => $data,
            'count' => $count
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 跟进报表
     */
    // public function WreHundredSel()
    // {
    //     // 分页信息
    //     [$page, $limit, $db] = [input('page'), input('limit'), $this->db];
    //     //判断如果有员工就查询员工
    //     if (input('staff') != '') {
    //         $where[] = ['v.executor_code', '=', input('staff')];
    //     } else {
    //         //判断如果没有员工有门店就查询门店
    //         if (input('store') != '') {
    //             $staff_code = Db::table($db . '.vip_staff')->where('store_code', input('store'))->field('code')->select();
    //             $arr = implode(',', array_column($staff_code, 'code'));
    //             $where[] = ['v.executor_code', 'in', $arr];
    //         } else {
    //             //判断如果没有员工也没有门店就查询组织机构,否则就查询登入人的管理机构
    //             if (input('splb') != '') {
    //                 $org = input('splb');
    //                 $staff_code = Db::table($db . '.vip_staff')->where('org_code', 'in', $org)->field('code')->select();
    //                 $arr = implode(',', array_column($staff_code, 'code'));
    //                 $where[] = ['v.executor_code', 'in', $arr];
    //             } else {
    //                 $org = session('info.admin_org');
    //                 $staff_code = Db::table($db . '.vip_staff')->where('org_code', 'in', $org)->field('code')->select();
    //                 $arr = implode(',', array_column($staff_code, 'code'));
    //                 $where[] = ['v.executor_code', 'in', $arr];
    //             }
    //         }
    //     }
    //     //时间限制
    //     if (input('start') != '') {
    //         $where[] = ['v.create_time', '>=', strtotime(input('start'))];
    //     }
    //     if (input('end') != '') {
    //         $where[] = ['v.create_time', '<=', strtotime(input('end'))];
    //     }
    //     //判断条件不存在就都查询
    //     if (!isset($where)) {
    //         $where = true;
    //     }
    //     //统计数量
    //     $count = Db::table($db . '.vip_interaction_record')
    //         ->alias('v')
    //         ->leftJoin($db . '.vip_staff vy', 'vy.code = v.executor_code')
    //         ->leftJoin($db . '.vip_store vm', 'vm.code = vy.store_code')
    //         ->leftJoin($db . '.vip_org vz', 'vz.code = vm.org_code')
    //         ->field('v.*,count(v.vip_code) member,vy.name vyname,vm.name vmname,vz.name vzname')
    //         ->where('v.remark', '100天跟进')
    //         ->where('v.status', 1)
    //         ->where('vy.status', 0)
    //         ->where($where)
    //         ->group('v.name,v.executor_code')
    //         ->count();
    //     //查询的数据
    //     $data = Db::table($db . '.vip_interaction_record')
    //         ->alias('v')
    //         ->leftJoin($db . '.vip_staff vy', 'vy.code = v.executor_code')
    //         ->leftJoin($db . '.vip_store vm', 'vm.code = vy.store_code')
    //         ->leftJoin($db . '.vip_org vz', 'vz.code = vm.org_code')
    //         ->field('v.*,count(v.vip_code) member,vy.name vyname,vm.name vmname,vz.name vzname')
    //         ->where('v.remark', '100天跟进')
    //         ->where('v.status', 1)
    //         ->where('vy.status', 0)
    //         ->where($where)
    //         ->group('v.name,v.executor_code')
    //         ->page($page, $limit)
    //         ->select();
    //     //格式化数据
    //     foreach ($data as $k => $v) {
    //         if ($v['vmname'] == "" || $v['vzname'] == "") {
    //             $data[$k]['vmname'] = '无门店';
    //             $data[$k]['vzname'] = '无机构';
    //         }
    //     }
    //     foreach ($data as $k => $v) {
    //         $data[$k]['total_name'] = $v['vzname'] . '-' . $v['vmname'] . '-' . $v['vyname'];
    //     }
    //     //格式化数据
    //     $data = [
    //         'count' => $count,
    //         'data' => $data
    //     ];
    //     //返回数据
    //     webApi(200, 'ok', $data);
    // }

    /**
     * 查询会员人数
     */
    // public function WreHundredPeople()
    // {
    //     //获取数据
    //     [$page, $limit, $remark, $name, $executor_code, $time] = [input('page'), input('limit'), input('remark'), input('name'), input('executor_code'), input('time')];
    //     if ($remark == null || $name == null || $executor_code == null || $time == null) {
    //         webApi(400, '参数错误！');
    //     }
    //     //组装查询条件
    //     $where = [
    //         'remark' => $remark,
    //         'name' => $name,
    //         'executor_code' => $executor_code,
    //         'time' => $time
    //     ];
    //     //获取查询数据
    //     $record = Db::table($this->db . '.vip_interaction_record')->where($where)->field('vip_code')->select();
    //     //格式化数据
    //     $vip_code = implode(',', array_column($record, 'vip_code'));
    //     //统计数量
    //     $count = Db::table($this->db . '.view_viplist')
    //         ->where('code', 'in', $vip_code)
    //         ->count();
    //     //查询的数据
    //     $data = Db::table($this->db . '.view_viplist')
    //         ->where('code', 'in', $vip_code)
    //         ->page($page, $limit)
    //         ->select();
    //     //清除变量
    //     unset($page, $limit, $remark, $name, $executor_code, $time, $where, $record, $vip_code);
    //     //格式化数据
    //     $data = [
    //         'count' => $count,
    //         'data' => $data
    //     ];
    //     //返回数据
    //     webApi(200, 'ok', $data);
    // }

}
