<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 * Author lhp
 * Date 2019/10/08
 * Description  客服中心  
 */
class CustomerService extends Common
{
    /**
     * 客服列表，如果是boss
     */
    public function index()
    {
        [$page, $limit] = [input('page'), input('limit')];

        $where = [];

        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['code'] == 'boss') {
            $where[] = ['customer_service', '<>', ''];
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $stores = $ew->org()->store()->get();
            $staff_code = Db::table($this->db . '.vip_staff')->where('store_code', 'in', $stores)->field('code')->select();
            if ($staff_code) {
                $arr = implode(',', array_column($staff_code, 'code'));
                $where[] = ['customer_service', 'in', $arr];
            } else {
                //返回数据
                webApi(200, 'ok', []);
            }
            unset($ew, $stores, $staff_code);
        } else {
            $where[] = ['customer_service', '=', $operate['code']];
        }
        unset($operate);
        $data = Db::table($this->db . '.vip_viplist')
            ->field('code, username, birthday, total_consumption, img, consumption_times, final_purchases, return_visit, phone, vvip, customer_service, consumption_number')
            ->where($where)
            ->order('final_purchases', 'desc')
            // ->fetchSql(true)
            ->page($page, $limit)
            ->select();
        foreach ($data as $k => $v) {
            $data[$k]['birthday'] = date('Y-m-d', $v['birthday']);
            $data[$k]['total_consumption'] = number_format($v['total_consumption'], 2, '.', '');
            if ($v['final_purchases'] > time()) {
                $data[$k]['r_days'] = 0;
            } else {
                $data[$k]['r_days'] =  number_format((time() - $v['final_purchases']) / 86400, 0);
            }
            if ($v['return_visit'] > time()) {
                $data[$k]['return_visit'] = 0;
            } else {
                $data[$k]['return_visit'] =  number_format((time() - $v['return_visit']) / 86400, 0);
            }
        }

        //统计数量
        $count = Db::table($this->db . '.vip_viplist')
            ->where($where)
            ->count();
        unset($where, $page, $limit);
        $data = [
            'count' => $count,
            'data' => $data
        ];

        webApi(200, 'ok', $data);
    }


    /**
     * 筛选列表
     */
    public function shu()
    {
        // 定义数据库
        $db = $this->db;
        // 查询会员标签表
        $ext = Db::table($db . '.vip_viplabel')->field('code')->select();
        // 判断是否有标签并格式化标签数据
        if (!empty($ext)) {
            $extend = array_column($ext, 'code');
        } else {
            $extend = [];
        }
        // 定义条件为真
        $where = '1=1';
        $having = '1=1';
        // 判断数据类型
        if (input('id') != 'false') {
            $sxq = Db::table($db . '.vip_screen')->find(input('id'));
            $sx = json_decode($sxq['content'], true);
        } else {
            $sx = cache('in_' . session('info.staff'));
        }
        // 判断查询条件内容
        if (!empty($sx)) {
            // 级别与所属门店
            $level_code = ['level_code', 'store_code'];
            // 积分
            $integral = ['residual_integral'];
            // 总金额， 次数， 件数
            $float = ['total_consumption', 'consumption_times', 'consumption_number'];
            // 循环对比
            foreach ($sx as $k => $v) {
                // 标签
                if (in_array($k, $extend)) {
                    if (is_array($v)) {
                        foreach ($v as $val) {
                            // $where .= ' and json_search(json_extract(IF(IFNULL(extension, "[]") = "", "[]", extension), \'$."' . $k . '"\'), "all", "%' . $val . '%")  is not null';
                            $label = Db::table($db . '.vip_label_middle')->where('label_code', $k)->where('info_id', $val)->select();
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
                }
                // 级别
                else if (in_array($k, $level_code)) {
                    $vv = '';
                    foreach ($v as $av) {
                        $vv .= "'" . $av . "',";
                    }
                    $aaa = trim($vv, ','); // trim 去掉字符串两边符号
                    $where .= ' and v.' . $k . ' in (' . $aaa . ')';
                }
                // else if (in_array($k, $money)) {
                //     $arr = explode("-", $v[0]);
                //     $where .= ' and ' . $k . ' >=' . ' "' . $arr[0] . '"';
                //     $where .= ' and ' . $k . ' <=' . ' "' . $arr[1] . '"';
                // } 
                //
                // 积分
                else if (in_array($k, $integral)) {
                    $arr = explode("-", $v[0]);
                    $where .= ' and v.' . $k . ' >=' . ' ' . $arr[0];
                    $where .= ' and v.' . $k . ' <=' . ' ' . $arr[1];
                }
                // 金额，次数，件数
                else if (in_array($k, $float)) {
                    $arr = explode("-", $v[0]);
                    $having .= ' AND ' . $k . ' >=' . $arr[0];
                    $having .= ' AND ' . $k . ' <=' . $arr[1];
                }
                // 性别
                else if (in_array($k, ['sex'])) {
                    foreach ($v as $val) {
                        $where .= ' and v.' . $k . ' =' . ' "' . $val . '"';
                    }
                }
            }
        }
        // 里层判断是不是微信会员
        if (input('vvip') != "") {
            $where .= ' and v.vvip=' . input('vvip');
        }
        // 清除变量
        unset($sxq, $sx, $extend, $ext, $arr, $aaa, $level_code, $integral, $float, $vv, $av);
        // 按登录人查询数据
        $operate = Db::table($db . '.vip_staff')->where('code', session('info.staff'))->find();
        // 门店限制
        if ($operate['code'] == 'boss') {
            $whereStaff = true;
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($db, $operate['admin_org_code']);
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
        // 基本配置 按机构显示会员
        $sysCon = Db::table($db . '.vip_sys_con')->find();
        if ($sysCon['fen_is_org'] == "on") {
            $staffW = $whereStaff;
        } else {
            $staffW = true;
        }
        // 查询出得会员数据
        $data = Db::table($db . '.vip_viplist')
            ->alias('v')
            ->leftJoin($db . '.vip_goods_order o', 'o.vip_code = v.code')
            ->field('v.*, ifnull(MAX(o.create_time), 0) as order_time, ifnull(round(sum(o.real_pay), 2), 0) as total_consumption, 
                        count(o.id) as consumption_times, ifnull(round(sum(o.number)), 0) as consumption_number')
            ->where('(o.status = 0 or o.status IS null)')
            ->where($where)
            ->where($staffW)
            ->group('v.code')
            ->having($having)
            ->select();
        // 格式化会员数据
        sort($data);
        // 引用方法
        $es = new ErpWhere($db, "");
        if ($data) {
            // 统计数量
            $count = count($data);
            // 群发功能数据
            $vipData = $data;
            // 数据分页
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
            $vipData = [];
        }
        // 判断类型，是查询数据还是群发功能
        if (input('whole') == 1) {
            // 清除变量
            unset($where, $staffW, $t, $start, $having);
            // 格式化返回数据
            $data = [
                'count' => $count,
                'data' => $data
            ];
            webApi(200, 'ok', $data);
        } else if (input('whole') == 2) {
            // 判断
            if ($vipData) {
                // 格式会员手机号
                $result = array_column($vipData, 'phone');
                // 分割数据
                $count_result = array_chunk($result, 100);
                // 格式会员code
                $vip_code = array_column($vipData, 'code');
            } else {
                $result = [];
                $vip_code = [];
                $count_result = 0;
            }
            // 查询短信与视图数量
            $msg = Db::table('company.vip_business')->field('code, usable_msg, fuxin_msg')->where('code', $db)->find();
            switch (input('perhaps')) {
                case 1: // 1表示发送短信
                    $acount = $count * ceil($es->abslength(input('content')) / 64);
                    // 判断短信条数
                    if ($msg['usable_msg'] < $acount) {
                        webApi(400, '短信条数不足，可用短信为' . $msg['usable_msg'] . '条' . '，当前发送短信为' . $acount . '条');
                    }
                    // 查询短信签名并判断
                    $sms = Db::table('company.vip_sms_autograph')->field('sms_autograph, business_code')->where('business_code', $db)->find();
                    if (empty($sms)) {
                        webApi(400, '短信签名未配置，请联系管理员进行配置');
                    }
                    // 执行事务
                    Db::startTrans();
                    try {
                        // 循环
                        for ($i = 0; $i < count($count_result); $i++) {
                            $es->shortMessage($count_result[$i], '【' . $sms['sms_autograph'] . '】' . input('content'));
                        }
                        // 提交事务
                        Db::commit();
                        $res = true;
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $res = false;
                    }
                    unset($acount);
                    if ($res) {
                        Db::table('company.vip_business')->where('code', $db)->setDec('usable_msg', $acount);
                        webApi(200, '发送成功');
                    } else {
                        webApi(400, '发送失败');
                    }
                    break;
                case 2: // 2表示发送微信
                    // 判断群发微信数量，防止内存过载
                    // if ($count > 18000) {
                    //     webApi(400, '群发人数不能超过1.8万人！');
                    // } else {
                    $es->wxG($vip_code, input('content'), input('type'));
                    // }
                    webApi(200, 'ok', '发送成功');
                    break;
                case 3: // 3表示发送视图
                    // 判断视图条数
                    if ($msg['fuxin_msg'] < $count) {
                        webApi(400, '视图条数不足，可用视图为' . $msg['fuxin_msg'] . '条' . '，当前发送视图信为' . $count . '条');
                    }
                    // 获取缓存中图片帧数
                    $imgcache = cache('getMsgId_' . session('info.staff'));
                    // 判断缓存中是否有值
                    if ($imgcache == false) {
                        webApi(400, '没有添加图文');
                    }
                    // 执行事件
                    Db::startTrans();
                    try {
                        // 循环发送
                        for ($i = 0; $i < count($count_result); $i++) {
                            $es->fuXin($count_result[$i], input('content'), $imgcache);
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
                    unset($imgcache);
                    // 请求完清除缓存信息
                    cache('getMsgId_' . session('info.staff'), null);
                    // 判断并返回
                    if ($res) {
                        // 修改视图数量
                        Db::table('company.vip_business')->where('code', $db)->setDec('fuxin_msg', $count);
                        webApi(200, '发送成功');
                    } else {
                        webApi(400, '发送失败');
                    }
                    break;
                case 4: // 4表示发送卡券
                    // 获取数据
                    [$type, $coupon_code] = [input('card_type'), input('coupon')];
                    if ($type == null) {
                        webApi(400, '卡券类型错误');
                    }
                    // 判断门店
                    if ($operate['store_code'] == "") {
                        $store_name = "无门店";
                    } else {
                        $store_name = Db::table($db . '.vip_store')->where('code', $operate['store_code'])->find()['name'];
                    }
                    //定义卡券金额, 折扣, 礼品
                    $two = "";
                    $three = "";
                    //判断类型并返回数据
                    switch ($type) {
                        case 0:
                            $one = Db::table($db . '.vip_cash_coupon')->where('code', $coupon_code)->find(); // 优惠券
                            $two = $one['card_money'];
                            $three = $one['money'];
                            $one['coupon_name'] = $one['card_money'] . '元，优惠劵';
                            break;
                        case 1:
                            $one = Db::table($db . '.vip_coupon')->where('code', $coupon_code)->find(); // 折扣券
                            $two = $one['card_discount'];
                            $three = $one['money'];
                            $one['coupon_name'] = $one['card_discount'] . '折，折扣劵';
                            break;
                        case 2:
                            $one = Db::table($db . '.vip_coupon_gift')->where('code', $coupon_code)->find(); // 礼品券
                            $two = $one['gift_code'];
                            $three = 0;
                            $one['coupon_name'] = $one['gift_code'] . '礼品劵';
                            break;
                    }
                    //备注替换
                    if (input('remark') != "") {
                        $one['remark'] = input('remark');
                    } else {
                        $one['remark'] = "暂无备注说明";
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
                        //按条件查询的数据
                        if ($vipData) {
                            foreach ($vipData as $k => $v) {
                                if ($v['openid'] == "") {
                                    unset($vipData[$k]);
                                }
                            }
                            sort($vipData);
                            $vipDataT = $vipData;
                        } else {
                            $vipDataT = [];
                        }
                        if ($vipDataT) {
                            foreach ($vipDataT as $k => $v) {
                                $couponRecord = Db::table($db . '.vip_coupon_record')
                                    ->where('vip_code', $v['code'])
                                    ->where('card_code', $one['code'])
                                    ->count();
                                if ($one['receive_limit'] != 0) {
                                    if ($couponRecord >= $one['receive_limit']) {
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
                            $res = Db::table($db . '.vip_coupon_batch_record')->insert($datae);
                            if ($res) {
                                foreach ($vipDataT as $k => $v) {
                                    $title = '您好，【' . $v['username'] . '】您有卡券待领取！';
                                    $es->pushCoupon($v['openid'], $title, $one, session('info.staff'));
                                }
                                webApi(200, 'ok', '赠送成功!');
                            } else {
                                webApi(400, '赠送失败!');
                            }
                        } else {
                            webApi(400, '没有绑定微信的会员!');
                        }
                    } else {
                        if ($vipData) {
                            $vipDataV = $vipData;
                        } else {
                            $vipDataV = [];
                        }
                        if ($vipDataV) {
                            foreach ($vipDataV as $k => $v) {
                                $couponRecord = Db::table($db . '.vip_coupon_record')->where('vip_code', $v['code'])->where('card_code', $one['code'])->count();
                                if ($one['receive_limit'] != 0) {
                                    if ($couponRecord >= $one['receive_limit']) {
                                        unset($vipDataV[$k]);
                                    }
                                }
                            }
                            foreach ($vipDataV as $k => $v) {
                                //添加的数据
                                $dataT[] = [
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
                                    'batch_record' => $vcbr,
                                    'coupon_type' => $one['coupon_type'],
                                    'superposition' => $one['superposition'],
                                    'delivery' => $one['delivery'],
                                    'receive_limit' => $one['receive_limit'],
                                    'integral' => $one['integral'],
                                    'a_hsi' => $one['a_hsi'],
                                    'b_hsi' => $one['b_hsi'],
                                    'week' => $one['week']
                                ];
                            }
                            //群发表数据
                            $datae = [
                                'code' => $vcbr,
                                'name' => input('name'),
                                'coupon_name' => $one['name'],
                                'coupon_type' => $type,
                                'card_name' => $one['coupon_type'],
                                'number' => count($vipDataV),
                                'time' => time()
                            ];
                            // 启动事务
                            Db::startTrans();
                            try {
                                Db::table($db . '.vip_coupon_record')->insertAll($dataT);
                                Db::table($db . '.vip_coupon_batch_record')->insert($datae);
                                // 提交事务
                                Db::commit();
                                $res = true;
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                $res = false;
                            }
                            unset($datae, $dataT);
                            //判断返回数据
                            if ($res) {
                                if ($type == 0) {
                                    Db::table($db . '.vip_cash_coupon')->where('code', $coupon_code)->setInc('receive', count($vipDataV)); // 优惠券
                                } else if ($type == 1) {
                                    Db::table($db . '.vip_coupon')->where('code', $coupon_code)->setInc('receive', count($vipDataV)); // 折扣券
                                } else if ($type == 2) {
                                    Db::table($db . '.vip_coupon_gift')->where('code', $coupon_code)->setInc('receive', count($vipDataV)); // 礼品券
                                }
                                if ($vipDataV) {
                                    foreach ($vipDataV as $k => $v) {
                                        $title = '您好，【' . $v['username'] . '】您收到一张卡券，请到会员中心查看或使用！';
                                        $es->pushCoupon($v['openid'], $title, $one);
                                    }
                                }
                                webApi(200, 'ok', '赠送成功, 没有绑定微信的会员不会发送微信消息!');
                            } else {
                                webApi(400, '赠送失败!');
                            }
                        } else {
                            webApi(400, '没有绑定微信的会员!');
                        }
                    }
                    break;
            }
        }
    }

    /**
     * 清理缓存信息
     */
    public function CacheNull()
    {
        cache('in_' . session('info.staff'), null);
        cache('goodsWhere_' . session('info.staff'), null);
        webApi(0);
    }

    /**
     * 会员等级条件
     */
    public function level()
    {
        $level = Db::table($this->db . '.vip_viplevel')->field('username, code')->order('uid', 'asc')->select();
        webApi(200, 'ok', $level);
    }

    /**
     * 点击添加到缓存
     */
    public function clickLabel()
    {
        // 获得缓存
        $data = cache('in_' . session('info.staff'));
        if (!$data) $data = [];
        // 判断该扩展型标签是否已经选择内容
        if (array_key_exists(input('key'), $data)) {
            if (input('val') == 'false') {
                unset($data[input('key')]);
            } else {
                // 判断是选中还是取消
                if (in_array(input('val'), $data[input('key')])) { // 取消
                    unset($data[input('key')][array_search(input('val'), $data[input('key')])]);
                    sort($data[input('key')]);
                } else { // 选中
                    array_push($data[input('key')], input('val'));
                }
            }
        } else {
            if (input('val') != 'false') {
                $data[input('key')][0] = input('val');
            }
        }

        cache('in_' . session('info.staff'), $data, 3600);
        webApi(0);
    }

    /**
     * 保存筛选
     */
    public function preservation()
    {
        $data = [
            'title' => input('text'),
            'staff_code' => session('info.staff') == null ? '' : session('info.staff'),
            'type' => 0,
            'content' => json_encode(cache('in_' . session('info.staff')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ];
        if (empty(cache('in_' . session('info.staff')))) {
            webApi(400, '筛选条件为空!');
        }
        //筛选器标题是否存在
        $repeat = Db::table($this->db . '.vip_screen')->where('title', $data['title'])->where('type', 0)->find();
        if ($repeat) {
            webApi(400, '筛选标题已存在!');
        }
        unset($repeat);
        $res = Db::table($this->db . '.vip_screen')->insert($data);
        unset($data);
        //提示信息
        if ($res) {
            cache('in_' . session('info.staff'), null);
            webApi(200, 'ok');
        } else {
            webApi(400, 'no');
        }
    }

    /**
     * 顾客画像保存专场根进模板
     */
    public function vipSpecial()
    {
        $data = [
            'title' => input('text'),
            'staff_code' => session('info.staff') == null ? '' : session('info.staff'),
            'type' => 0,
            'content' => json_encode(cache('in_' . session('info.staff')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ];
        if (empty(cache('in_' . session('info.staff')))) {
            webApi(400, '筛选条件为空!');
        }
        //筛选器标题是否存在
        $repeat = Db::table($this->db . '.vip_field_interaction')->where('title', $data['title'])->find();
        if ($repeat) {
            webApi(400, '筛选标题已存在!');
        }
        unset($repeat);
        $res = Db::table($this->db . '.vip_field_interaction')->insert($data);
        unset($data);
        //提示信息
        if ($res) {
            cache('in_' . session('info.staff'), null);
            webApi(200, 'ok');
        } else {
            webApi(400, 'no');
        }
    }

    /**
     * 筛选器信息列表
     */
    public function screenList()
    {
        $data = Db::table($this->db . '.vip_screen')->field('id, title')->where('type', 0)->where('staff_code', session('info.staff'))->select();
        webApi(200, 'ok', $data);
    }

    /**
     * 删除筛选器
     */
    public function screenDel()
    {
        $id = input('id');
        if (input('id') == '') {
            webApi(400, '参数错误');
        }
        $res = Db::table($this->db . '.vip_screen')->where('id', $id)->delete();
        unset($id);
        if ($res) {
            webApi(200, '成功');
        } else {
            webApi(400, '失败');
        }
    }
}
