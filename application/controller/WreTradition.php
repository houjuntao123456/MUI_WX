<?php

namespace app\controller;

use think\Db;
use think\Controller;

/**
 * Author lxy
 * Date 2019/08/21
 * Description 传统模板
 */
class WreTradition extends Common
{
    /**
     * 查询传统模板
     */
    public function WreTraditionList()
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
        $count = Db::table($db . '.vip_intradition_interaction')
            ->alias('v')
            ->leftJoin($db . '.vip_org vg', 'vg.code = v.org_code')
            ->field('v.*,vg.name vgname')
            ->where($where)
            ->where($org)
            ->count();
        //所需数据
        $data = Db::table($db . '.vip_intradition_interaction')
            ->alias('v')
            ->leftJoin($db . '.vip_org vg', 'vg.code = v.org_code')
            ->field('v.*,vg.name vgname')
            ->where($where)
            ->where($org)
            ->order('v.time', 'asc') //按照时间排列
            ->page($page, $limit)
            ->select();
        //格式化数据
        foreach ($data as $k => $v) {
            $data[$k]['time_g'] = date('Y-m-d', $v['time']);
        }
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
     * 传统互动记录
     */
    public function WreTraditionRecord()
    {
        //生成单号
        $foot = 'CTRHD' . str_replace('.', '', microtime(1));
        //按单号查询模板
        $tradition = Db::table($this->db . '.vip_intradition_interaction')->where('code', input('template_code'))->find();
        if ($tradition == null){
            webApi(400, '参数错误!');
        }
        //添加记录的数据
        $record = [
            'code' => $foot,
            'name' => $tradition['name'],
            'time' => $tradition['time'],
            'vip_code' => input('vip_code'),
            'level_code' => input('level'),
            'executor_code' => session('info.staff'),
            'create_time' => time(),
            'template_code' => input('template_code')
        ];
        //回访记录添加
        $return_visit = [
            'user_name' => input('username'),
            'user_code' => input('vip_code'),
            'visit_operator' => session('info.staff'),
            'visit_mode' => '生日回访',
            'time' => time(),
            'content' => $tradition['speech']
        ];
        //修改会员的回访时间,以及微信记录操作人code
        $viplist_update = [
            'return_visit' => time(),
            'customer_service' => session('info.staff')
        ];
        // 启动事务并执行添加
        Db::startTrans();
        try {
            Db::table($this->db . '.vip_intradition_record')->insert($record);
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
        unset($foot, $tradition, $record);
        //判断返回数据
        if ($res) {
            webApi(200, '添加成功!');
        } else {
            webApi(400, '添加失败!');
        }
    }
}
