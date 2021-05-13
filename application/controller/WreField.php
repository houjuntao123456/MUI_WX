<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;
use app\controller\RfmActive as rfma;

/**
 * Author lxy
 * Date 2019/08/21
 * Description 专场模板
 */
class WreField extends Common
{
    /**
     * 查询会员
     */
    public function WreFieldPeople()
    {
        // 获取查询所需数据
        [
            $page, $limit, $db, $card, $id, $splb, $store, $staff
        ] = [
            input('page'), input('limit'), $this->db, input('search'), input('id'), input('org'), input('store'), input('staff')
        ];
        // 判断筛选器值
        if ($id != "") {
            // 筛选器条件组装
            $sxq = Db::table($db . '.vip_filter')->where('id', $id)->find();
            $sx = json_decode($sxq['content'], true);

            $ext = Db::table($db . '.vip_viplabel')->field('name')->select();
            if (!empty($ext)) {
                $extenison = array_column($ext, 'name');
            } else {
                $extenison = [];
            }
            $where = '1=1';
            if (!empty($sx)) {
                foreach ($sx as $k => $v) {
                    if (!in_array($v['tit'], $extenison)) {
                        if ($v['sym'] == 'LIKE') {
                            $where .= ' and ' . $v['tit'] . ' ' . $v['sym'] . ' "%' . $v['val'] . '%"';
                        } else {
                            $where .= ' and ' . $v['tit'] . $v['sym'] . '"' . $v['val'] . '"';
                        }
                    } else {
                        $where .= ' and json_search(json_extract(IF(IFNULL(extension, "[]") = "", "[]", extension), \'$."' . $v['tit'] . '"\'), "all", "%' . $v['val'] . '%")  is not null';
                    }
                }
            }
        } else {
            $where = true;
        }
        // 按卡号与手机号查询
        if ($card != "") {
            $ws[] = ['code|phone', '=', $card];
        }
        // 判断如果有员工就查询，形象顾问是这个员工的会员
        if ($staff != '') {
            $ws[] = ['consultant_code', '=', input('staff')];
        } else {
            // 判断如果没有员工有门店就查询，所属门店是这个门店的会员
            if ($store != '') {
                $ws[] = ['store_code', '=', input('store')];
            } else {
                // 判断如果没有员工也没有门店就查询，所属门店是这个组织机构下的门店的会员
                if ($splb != '') {
                    $ew = new ErpWhere($this->db, $splb);
                    $stores = $ew->org()->store()->get();
                    $ws[] = ['store_code', 'in', $stores];
                }
            }
        }
        // 判断变量是否存在
        if (!isset($ws)) {
            $ws = true;
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
        //查询的数据
        $data = Db::table($db . '.view_viplist_visit')
            ->where($w)
            ->where($ws)
            ->where($where)
            ->page($page, $limit)
            ->select();
        //统计数量
        $count = Db::table($db . '.view_viplist_visit')
            ->where($w)
            ->where($ws)
            ->where($where)
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
        //格式化数据
        $data = [
            'data' => $data,
            'count' => $count
        ];
        //返回数据
        webApi(200, 'ok', $data);
    }

    /**
     * 专场模板查询
     */
    public function WreFieldList()
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
        $count = Db::table($db . '.vip_infield_interaction')
            ->alias('v')
            ->leftJoin($db . '.vip_org vg', 'vg.code = v.org_code')
            ->field('v.*,vg.name vgname')
            ->where($where)
            ->where($org)
            ->count();
        //所需数据
        $data = Db::table($db . '.vip_infield_interaction')
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
     * 专场互动记录
     */
    public function WreFieldRecord()
    {
        //生成单号
        $foot = 'ZCRHD' . str_replace('.', '', microtime(1));
        //按单号查询模板
        $field = Db::table($this->db . '.vip_infield_interaction')->where('code', input('template_code'))->find();
        if ($field == null) {
            webApi(400, '参数错误!');
        }
        //添加记录的数据
        $record = [
            'code' => $foot,
            'name' => $field['name'],
            'time' => $field['time'],
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
            'visit_mode' => '专场回访',
            'time' => time(),
            'content' => $field['speech']
        ];
        //修改会员的回访时间,以及微信记录操作人code
        $viplist_update = [
            'return_visit' => time(),
            'customer_service' => session('info.staff')
        ];
        // 启动事务并执行添加
        Db::startTrans();
        try {
            Db::table($this->db . '.vip_infield_record')->insert($record);
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
        unset($foot, $field, $record);
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
        // 获取查询所需数据
        [
            $db, $message_content, $card, $id, $splb, $store, $staff
        ] = [
            $this->db, input('message_content'), input('search'), input('id'), input('org'), input('store'), input('staff')
        ];
        // 判断筛选器值
        if ($id !== "") {
            // 筛选器条件组装
            $sxq = Db::table($db . '.vip_filter')->where('id', $id)->find();
            $sx = json_decode($sxq['content'], true);
            $ext = Db::table($db . '.vip_viplabel')->field('name')->select();
            if (!empty($ext)) {
                $extenison = array_column($ext, 'name');
            } else {
                $extenison = [];
            }
            $where = '1=1';
            if (!empty($sx)) {
                foreach ($sx as $k => $v) {
                    if (!in_array($v['tit'], $extenison)) {
                        if ($v['sym'] == 'LIKE') {
                            $where .= ' and ' . $v['tit'] . ' ' . $v['sym'] . ' "%' . $v['val'] . '%"';
                        } else {
                            $where .= ' and ' . $v['tit'] . $v['sym'] . '"' . $v['val'] . '"';
                        }
                    } else {
                        $where .= ' and json_search(json_extract(IF(IFNULL(extension, "[]") = "", "[]", extension), \'$."' . $v['tit'] . '"\'), "all", "%' . $v['val'] . '%")  is not null';
                    }
                }
            }
        } else {
            $where = true;
        }
        // 按卡号查询
        if ($card != "") {
            $ws[] = ['code|phone', '=', $card];
        }
        // 判断如果有员工就查询，形象顾问是这个员工的会员
        if ($staff != '') {
            $ws[] = ['consultant_code', '=', input('staff')];
        } else {
            // 判断如果没有员工有门店就查询，所属门店是这个门店的会员
            if ($store != '') {
                $ws[] = ['store_code', '=', input('store')];
            } else {
                // 判断如果没有员工也没有门店就查询，所属门店是这个组织机构下的门店的会员
                if ($splb != '') {
                    $ew = new ErpWhere($this->db, $splb);
                    $stores = $ew->org()->store()->get();
                    $ws[] = ['store_code', 'in', $stores];
                }
            }
        }
        // 判断变量是否存在
        if (!isset($ws)) {
            $ws = true;
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
        $vipData = Db::table($db . '.view_viplist_visit')
            ->where($w)
            ->where($ws)
            ->where($where)
            ->select();
        //提取会员的手机号并更改格式
        $phone = array_column($vipData, 'phone');
        //调用短信接口发送短信 $phone会员手机号 $message_content短信内容
        $count = array_chunk($phone, 100);
        $es = new ErpWhere($db, "");
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
        //清除变量
        unset($id, $store, $rday, $message_content, $db, $count, $phone, $es);
        //判断返回数据
        if ($res) {
            Db::table('company.vip_business')->where('code', $db)->setDec('usable_msg', $message_count);
            webApi(200, '发送成功!');
        } else {
            webApi(400, $res);
        }
    }

    /**
     * 专场查询
     */
    public function WreFieldSel()
    {
        //分页信息
        [$page, $limit, $db] = [input('page'), input('limit'), $this->db];
        //查询当前登入人 //session('info.staff')
        $operate = Db::table($db . '.vip_staff')->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['code'] == 'boss') {
            $w = true;
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($db, $operate['admin_org_code']);
            $stores = $ew->org()->store()->get();
            $a_org = explode(',', $operate['admin_org_code']);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $stores = $stores . ', ';
            }
            $w[] = ['rfm_store', 'in', $stores];
        } else {
            $w[] = ['rfm_store', '=', $operate['store_code']];
        }
        //统计数量
        $count = Db::table($db . '.vip_field_interaction')
            ->where($w)
            ->count();
        //所需数据
        $data = Db::table($db . '.vip_field_interaction')
            ->where($w)
            ->order('create_time', 'asc') //按照时间排列
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
     * 专场查询会员
     */
    public function WreFieldMember()
    {
        // 获取数据
        [$page, $limit, $id, $db] = [input('page'), input('limit'), input('id'), $this->db];
        // 查询专场跟进表
        $interaction = Db::table($this->db . '.vip_field_interaction')->where('id', $id)->find();
        // 基本配置
        $sysCon = Db::table($this->db . '.vip_sys_con')->find();
        // 判断查询会员
        if ($interaction['type'] == 0) {
            $ext = Db::table($this->db . '.vip_viplabel')->field('code')->select();
            if (!empty($ext)) {
                $extend = array_column($ext, 'code');
            } else {
                $extend = [];
            }
            $where = '1=1';
            $sx = json_decode($interaction['content'], true);
            $having = '1=1';
            if (!empty($sx)) {
                // ----会员级别----所属门店
                $level_code = ['level_code', 'store_code'];
                // 剩余总积分
                $integral = ['residual_integral'];
                // ----总消费额----消费次数----消费件数
                $float = ['total_consumption', 'consumption_times', 'consumption_number'];
                $floatnum = 0;
                foreach ($sx as $k => $v) {
                    if (in_array($k, $extend)) {
                        if (is_array($v)) {
                            foreach ($v as $val) {
                                $label = Db::table($this->db . '.vip_label_middle')->where('label_code', $k)->where('info_id', $val)->select();
                                if (!empty($label)) {
                                    $arr = array_column($label, 'vip_code');
                                    $ii = '';
                                    foreach ($arr as $avl) {
                                        $ii .= "'" . $avl . "',";
                                    }
                                    $bb = trim($ii, ','); // trim 去掉字符串两边符号
                                    $where .= " and v.code in (" . $bb . ")";
                                } else {
                                    $data = [
                                        'count' => 0,
                                        'data' => ''
                                    ];
                                    webApi(200, 'ok', $data);
                                }
                            }
                        }
                    } else if (in_array($k, $level_code)) {
                        $vv = '';
                        foreach ($v as $av) {
                            $vv .= "'" . $av . "',";
                        }
                        $aaa = trim($vv, ','); // trim 去掉字符串两边符号
                        $where .= ' and v.' . $k . ' in (' . $aaa . ')';
                    } else if (in_array($k, $integral)) {
                        $arr = explode("-", $v[0]);
                        $where .= ' and v.' . $k . ' >=' . ' ' . $arr[0];
                        $where .= ' and v.' . $k . ' <=' . ' ' . $arr[1];
                    }  else if (in_array($k, $float)) {
                        $arr = explode("-", $v[0]);
                        $having .= ' AND ' . $k . ' >=' . $arr[0];
                        $having .= ' AND ' . $k . ' <=' . $arr[1];
                    } else if (in_array($k, ['sex', 'vvip'])) { // 性别与微信绑定
                        foreach ($v as $val) {
                            $where .= ' and v.' . $k . ' =' . ' "' . $val . '"';
                        }
                    }
                }
            }
            unset($sx, $extend, $ext, $arr, $aaa);
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
                $whereStaff[] = ['v.consultant_code', '=', $operate['code']];
            }
            // 判断条件不存在就都查询
            if (!isset($whereStaff)) {
                $whereStaff = true;
            }
            // 判断基本配置
            if ($sysCon['fen_is_org'] == "on") {
                $staffW = $whereStaff;
            } else {
                $staffW = true;
            }
            // 查询数据
            $data = Db::table($this->db . '.vip_viplist')
                ->alias('v')
                ->leftJoin($this->db . '.vip_goods_order o', 'o.vip_code = v.code')
                ->field('v.*, ifnull(MAX(o.create_time), 0) as order_time, ifnull(round(sum(o.real_pay), 2), 0) as total_consumption, 
                        count(o.id) as consumption_times, ifnull(round(sum(o.number)), 0) as consumption_number')
                ->where('(o.status = 0 or o.status IS null)')
                ->where($where)
                ->where($staffW)
                ->group('v.code')
                ->having($having)
                ->select();
            sort($data);
            if ($data) {
                // 统计数量
                $count = count($data);
                // 数据分页
                $es = new ErpWhere($this->db, "");
                $data = $es->arrPage($data, input('page'), input('limit'));
                // 格式化数据
                $t = time();
                $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
                foreach ($data as $k => $v) {
                    if ($v['return_visit'] > time()) {
                        $data[$k]['return_visit'] = 0;
                    } else {
                        $data[$k]['return_visit'] =  round((time() - $v['return_visit']) / 86400);
                    }
                    if ($v['order_time'] >= $start) {
                        $data[$k]['r_days'] = 0;
                    } else {
                        $data[$k]['r_days'] = intval(round((time() - $v['order_time']) / 86400));
                    }
                }
                foreach ($data as $k => $v) {
                    if ($v['return_visit'] > 15000) {
                        $data[$k]['return_visit'] = '未回访';
                    }
                    if ($v['r_days'] > 15000) {
                        $data[$k]['r_days'] = '未消费';
                    }
                    if ($sysCon['yincang_is_phone'] == "on") {
                        if ($v['phone'] != "") {
                            $data[$k]['phone_g'] = substr($v['phone'], 0, 3) . '****' . substr($v['phone'], 7);
                        }
                    } else {
                        $data[$k]['phone_g'] = $v['phone'];
                    }
                    if (strlen($v['birthday']) == 8) {
                        $data[$k]['birthday'] = date('Y-m-d', strtotime($v['birthday']));
                    } else if ($v['birthday'] == 0) {
                        $data[$k]['birthday'] = '无';
                    } else {
                        $data[$k]['birthday'] = date('Y-m-d', $v['birthday']);
                    }
                }
            } else {
                $count = 0;
                $data = [];
            }
            // 清除变量
            unset($where, $staffW);
            // 格式化并返回数据
            $data = ['count' => $count, 'data' => $data];
            webApi(200, 'ok', $data);
        } else if ($interaction['type'] == 1) {
            $ext = Db::table($this->db . '.vip_goods_label')->field('code')->select();
            if (!empty($ext)) {
                $extend = array_column($ext, 'code');
            } else {
                $extend = [];
            }
            $where = '1=1';
            $sx = json_decode($interaction['content'], true);
            if (!empty($sx)) {
                $type = ['color', 'sizes'];
                foreach ($sx as $k => $v) {
                    if (in_array($k, $extend)) {
                        foreach ($v as $val) {
                            $label = Db::table($this->db . '.vip_goods_labels')->where('label_code', $k)->where('label_id', $val)->select();
                            if (!empty($label)) { // 判断选择的标签在 会员标签中间表中有没有
                                $arr = array_column($label, 'goods_code');
                                $vv = '';
                                foreach ($arr as $av) {
                                    $vv .= "'" . $av . "',";
                                }
                                $aaa = trim($vv, ','); // trim 去掉字符串两边符号
                                $where .= " and goods.bar_code in (" . $aaa . ")";
                                unset($arr, $label, $vv);
                            } else {
                                $data = [
                                    'count' => 0,
                                    'data' => ''
                                ];
                                webApi(200, 'ok', $data);
                            }
                        }
                    } else if (in_array($k, $type)) {
                        foreach ($v as $val) {
                            $where .= ' and ' . 'goods.' . $k . ' =' . ' "' . $val . '"';
                        }
                    }
                }
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
            // 判断基本配置
            if ($sysCon['fen_is_org'] == "on") {
                $staffW = $whereStaff;
            } else {
                $staffW = true;
            }
            //查询数据
            $data = Db::table($this->db . '.vip_goods_order')
                ->alias('v')
                ->leftJoin($this->db . '.vip_goods_order_info info', 'v.code = info.order_code')
                ->leftJoin($this->db . '.vip_goods goods', 'goods.bar_code = info.goods_code')
                ->Join($this->db . '.vip_viplist vip', 'vip.code = v.vip_code')
                ->field('vip.consultant_code, vip.store_code, vip.birthday, vip.phone phone, vip.img img, vip.level_code level_code,
                            vip.username username, vip.return_visit return_visit, v.vip_code code, ifnull(MAX(v.create_time), 0) as order_time, 
                            ifnull(round(sum(v.real_pay), 2), 0) as total_consumption, 
                            count(v.id) as consumption_times, ifnull(round(sum(v.number)), 0) as consumption_number,  
                            goods.color color, goods.sizes sizes')
                ->where('v.status', 0)
                ->where('v.vip_code', '<>', '非会员')
                ->where($staffW)
                ->where($where)
                ->page($page, $limit)
                ->group('v.vip_code')
                ->select();
            //统计数量
            $count = Db::table($this->db . '.vip_goods_order')
                ->alias('v')
                ->leftJoin($this->db . '.vip_goods_order_info info', 'v.code = info.order_code')
                ->leftJoin($this->db . '.vip_goods goods', 'goods.bar_code = info.goods_code')
                ->Join($this->db . '.vip_viplist vip', 'vip.code = v.vip_code')
                ->where('v.status', 0)
                ->where('v.vip_code', '<>', '非会员')
                ->where($staffW)
                ->where($where)
                ->group('v.vip_code')
                ->count();
            $t = time();
            $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
            foreach ($data as $k => $v) {
                if ($v['return_visit'] > time()) {
                    $data[$k]['visit_g'] = 0;
                } else {
                    $data[$k]['visit_g'] =  round((time() - $v['return_visit']) / 86400);
                }
                if ($v['order_time'] >= $start) {
                    $data[$k]['rfm_days'] = 0;
                } else {
                    $data[$k]['rfm_days'] = intval(round((time() - $v['order_time']) / 86400));
                }
            }
            //基本配置
            $sysCon = Db::table($db . '.vip_sys_con')->find();
            foreach ($data as $k => $v) {
                if ($v['visit_g'] > 15000) {
                    $data[$k]['visit_g'] = '未回访';
                }
                if ($v['rfm_days'] > 15000) {
                    $data[$k]['rfm_days'] = '未消费';
                }
                if ($sysCon['yincang_is_phone'] == "on") {
                    if ($v['phone'] != "") {
                        $data[$k]['phone_g'] = substr($v['phone'], 0, 3) . '****' . substr($v['phone'], 7);
                    }
                } else {
                    $data[$k]['phone_g'] = $v['phone'];
                }
                if (strlen($v['birthday']) == 8) {
                    $data[$k]['birthday_g'] = date('Y-m-d', strtotime($v['birthday']));
                } else if ($v['birthday'] == 0) {
                    $data[$k]['birthday_g'] = '无';
                } else {
                    $data[$k]['birthday_g'] = date('Y-m-d', $v['birthday']);
                }
            }
            unset($where, $staffW, $sx, $whereStaff, $type);
            $data = [
                'count' => $count,
                'data' => $data
            ];
            webApi(200, 'ok', $data);
        } else if ($interaction['type'] == 2) {
            // 查询会员数据
            $vipData = rfma::rfmMember($interaction['rfm_id'], $interaction['rfm_store'], '', $this->db, $interaction['rfm_type']);
            foreach ($vipData as $k => $v) {
                if ($v['return_visit'] > time()) {
                    $vipData[$k]['visit_g'] = 0;
                } else {
                    $vipData[$k]['visit_g'] =  round((time() - $v['return_visit']) / 86400);
                }
            }
            foreach ($vipData as $k => $v) {
                if ($v['visit_g'] > 15000) {
                    $vipData[$k]['visit_g'] = '未回访';
                }
            }
            //清除变量
            unset($id, $page, $limit, $rday, $interaction, $db, $vipCode, $es);
            //格式化数据
            $data = [
                'count' => count($vipData),
                'data' => $vipData
            ];
            //返回数据
            webApi(200, 'ok', $data);
        }
    }

    /**
     * 专场删除
     */
    public function WreFieldDel()
    {
        //获取id
        $id = input('id');
        if ($id == "") {
            //返回数据
            webApi(400, '参数错误!');
        }
        //执行删除
        $res = Db::table($this->db . '.vip_field_interaction')->where('id', $id)->delete();
        //判断并返回数据
        if ($res) {
            webApi(200, 'ok', '删除成功!');
        } else {
            webApi(400, '删除失败!');
        }
    }
}
