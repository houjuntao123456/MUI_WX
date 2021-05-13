<?php

namespace app\controller;

use think\Db;
use think\Controller;

/**
 * Author lxy
 * Date 2019/10/10
 * Description 短信模板
 */
class WreMessage extends Common
{
    /**
     * 查询短信模板
     */
    public function WreMessageList()
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
        $count = Db::table($db . '.vip_inmessage_interaction')
            ->alias('v')
            ->leftJoin($db . '.vip_org vg', 'vg.code = v.org_code')
            ->field('v.*,vg.name vgname')
            ->where($where)
            ->where($org)
            ->count();
        //所需数据
        $data = Db::table($db . '.vip_inmessage_interaction')
            ->alias('v')
            ->leftJoin($db . '.vip_org vg', 'vg.code = v.org_code')
            ->field('v.*,vg.name vgname')
            ->where($where)
            ->where($org)
            ->order('v.time', 'asc') //按照时间排列
            ->page($page, $limit)
            ->select();
        //格式化数据
        // foreach ($data as $k => $v) {
        //     $data[$k]['time_g'] = date('Y-m-d', $v['time']);
        // }
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
     * 短信互动记录
     */
    public function WreMessageRecord()
    {
        //生成单号
        $foot = 'DXMB' . str_replace('.', '', microtime(1));
        //添加记录的数据
        $record = [
            'code' => $foot,
            'vip_name' => input('vip_name'),
            'vip_code' => input('vip_code'),
            'content' => input('content'),
            'executor_code' => session('info.staff'),
            'create_time' => time()
        ];
        //回访记录添加
        $return_visit = [
            'user_name' => input('username'),
            'user_code' => input('vip_code'),
            'visit_operator' => session('info.staff'),
            'visit_mode' => '短信回访',
            'time' => time(),
            'content' => input('content')
        ];
        // 启动事务并执行添加
        Db::startTrans();
        try {
            Db::table($this->db . '.vip_inmessage_record')->insert($record);
            Db::table($this->db . '.vip_returnvisit_record')->insert($return_visit);
            Db::table($this->db . '.vip_viplist')->where('code', input('vip_code'))->update(['return_visit' => time()]);
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $res = false;
        }
        //清除变量
        unset($foot, $message, $record);
        //判断返回数据
        if ($res) {
            webApi(200, '添加成功!');
        } else {
            webApi(400, '添加失败!');
        }
    }
}
