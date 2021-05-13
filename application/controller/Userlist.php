<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;
use image\Image;
use think\facade\Env;

/**
 * Author lhp
 * Date 2019/2/19
 * Description  会员列表
 */
class Userlist extends Common
{

    /**
     * @param array $count 数据长度|数据条数
     * @param array $data 返回数据内容
     */
    public function list()
    {
        [$page, $limit] = [input('page'), input('limit')];
        [$vague, $store, $staff, $org, $type] = [input('vague'), input('store'), input('staff'), input('org'), input('type')];

        $vvipWhere = [];
        $where = [];
        if (!empty($staff)) {
            $where[] = ['consultant_code', '=', $staff];
        } else if (!empty($store)) {
            $where[] = ['store_code', '=', $store];
        } else if (!empty($org)) {
            $db = $this->db;
            $ew = new ErpWhere($db, $org);
            $orgWhere = $ew->org()->store()->get();
            $where[] = ['store_code', 'in', $orgWhere];
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
            $whereStaff[] = ['store_code', 'in', $stores];
        } else {
            $whereStaff[] = ['consultant_code', '=', $operate['code']];
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

        if ($type != 'true') {
            $vvipWhere[] = ['vvip', '=', $type];
        }
        if (!empty($vague)) {
            $where[] = ['code|username|phone', 'like', $vague . '%'];
        }
        //基本配置
        $data = Db::table($this->db . '.vip_viplist')
        //       会员姓名,    生日,      总消费金额,      会员照片, 未消费天数, 消费次数
            ->field('code, username, birthday, total_consumption, img, consumption_times, final_purchases, return_visit, phone, consumption_number, vvip')
            ->where($where)
            ->where($vvipWhere)
            ->where($staffW)
            ->order('final_purchases', 'desc')
            ->page($page, $limit)
            ->select();
        //统计数量
        $count = Db::table($this->db . '.vip_viplist')
            ->where($where)
            ->where($vvipWhere)
            ->where($staffW)
            ->count();
       
        foreach ($data as $k => $v) {
            $data[$k]['order_time'] = 0;
            if (strlen($v['birthday']) == 8) {
                $data[$k]['birthday'] = date('Y-m-d', strtotime($v['birthday']));
            } else {
                $data[$k]['birthday'] = date('Y-m-d', $v['birthday']);
            }
            $data[$k]['total_consumption'] = number_format($v['total_consumption'], 2, '.', '');
            // if ($v['final_purchases'] > time()) {
            //     $data[$k]['r_days'] = "0";
            // } else {
            //     $data[$k]['r_days'] =  number_format((time() - $v['final_purchases']) / 86400, 0);
            // }
            if ($v['return_visit'] > time()) {
                $data[$k]['return_visit'] = "0";
            } else {
                $data[$k]['return_visit'] =  number_format((time() - $v['return_visit']) / 86400, 0);
            }
        }
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
                $data[$k]['r_days'] = 0;
            } else {
                $data[$k]['r_days'] =  round((time() - $v['order_time']) / 86400);
            }
        }
        foreach ($data as $k => $v) {
            if ($v['r_days'] > 15000) {
                $data[$k]['r_days'] = '未消费';
            }
        }
        $aa = [
            'list' => [
                'count' => $count,
                'data' => $data
            ]
        ];

        webApi(200, 'ok', $aa);
    }

    /**
     * 会员资料
     * @param array $data 会员资料
     * @param array $ext 标签
     * @param array $snapshot 快照
     */
    public function memberdata()
    {

        $code = input('code'); //会员卡号

        if ($code == null) {
            webApi(400, '参数错误');
        }

        $data = Db::table($this->db . '.view_viplist')
            //      会员卡号, 会员姓名, 性别, 等级,   手机号,     所属门店,       登记门店,  生日,               形象顾问,           消费次数,          总消费金额,    会员头像, 会员照片，扩展标签,   未消费天数,       登记时间,     地区,  详细地址,     可用积分
            ->field('code,   username, sex, vlname, phone, store_code, vsname, vrname, birthdays, birthday, consultant_code, vgname, consumption_times, total_consumption,   img,  vimg,  extension,  r_days,    date_registration, area, address, residual_integral,residual_value, return_visit')
            ->where('code', $code)
            ->select();
        //基本配置
        $sysCon = Db::table($this->db . '.vip_sys_con')->find()['yincang_is_phone'];
        foreach ($data as $k => $v) {
            $data[$k]['extension'] = json_decode($v['extension'], true);
            // if ($v["r_days"] < 0) {
            //     $data[$k]['r_days'] = "0";
            // }
            if ($sysCon == "on") {
                if ($v['phone'] != "") {
                    $data[$k]['phone_g'] = substr($v['phone'], 0, 3) . '****' . substr($v['phone'], 7);
                }
            } else {
                $data[$k]['phone_g'] = $v['phone'];
            }
            $data[$k]['order_time'] = 0;
            // if (strlen($v['birthdays']) == 8) {
            //     $data[$k]['birthday'] = date('Y-m-d', strtotime($v['birthdays']));
            // } else {
            //     $data[$k]['birthday'] = date('Y-m-d', $v['birthdays']);
            // }
        }
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
        }
        foreach ($data as $k => $v) {
            if ($v['order_time'] > time()) {
                $data[$k]['r_days'] = 0;
            } else {
                $data[$k]['r_days'] =  round((time() - $v['order_time']) / 86400);
            }
        }
        foreach ($data as $k => $v) {
            if ($v['r_days'] > 15000) {
                $data[$k]['r_days'] = '未消费';
            }
        }

        $snapshot = Db::table($this->db . '.vip_goods_order')
            ->alias('o')
            ->leftJoin($this->db . '.vip_store s', 'o.store_code = s.code')
            //   订单号,    导购,           件数,       金额,      购买时间,      门店
            ->field('o.code, o.operate_name, o.number, o.real_pay, o.create_time, s.name sname')
            ->where('o.vip_code', $code)
            // ->where('o.status', 0)
            ->order('o.create_time', 'desc')
            ->page(input('page'), input('limit'))
            ->select();

        foreach ($snapshot as $k => $v) {
            $snapshot[$k]['time'] = date('Y-m-d H:i:s', $v['create_time']);
            $snapshot[$k]['money'] = number_format($v['real_pay'], 2, '.', '');
            unset($snapshot[$k]['real_pay'], $snapshot[$k]['create_time']);
        }
        $count = count($snapshot);
        $data = [
            'data' => $data,
            // 'extension' => $ext,
            'snapshot' => $snapshot,
            'count' => $count
        ];

        webApi(200, 'ok', $data);
    }

    /**
     * 会员标签
     */
    public function label()
    {
        cache('vip_label_cache' . session('info.staff'), null);
        $data = Db::table($this->db . '.vip_viplabel')->field('name, code, type')->where('type', '扩展型')->where('status', 1)->select();
        webApi(200, 'ok', $data);
    }

    /**
     * 筛选中标签
     */
    public function labelInfoq()
    {
        $name = input('name');
        $data = Db::table($this->db . '.vip_viplabel_info')->field('info, id')->where('name', $name)->select();
        webApi(200, 'ok', $data);
    }


    /**
     * 点击会员标签
     */
    public function labelInfo()
    {
        $data = Db::table($this->db . '.vip_viplabel_info')->field('id, info, label_code')->where('label_code', input('id'))->select();

        $ext = Db::table($this->db . '.vip_label_middle')->where('vip_code', input('code'))->where('label_code', input('id'))->select();

        if (!empty($ext) && !empty($data)) {
            foreach ($data as $k => $v) {
                $data[$k]['on'] = false;
                foreach ($ext as $val) {
                    if ($val['info_id'] == $v['id']) {
                        $data[$k]['on'] = true;
                    }
                }
            }
            unset($ext);
        }
        // $cachedata = cache('vip_label_cache' . session('info.staff'));
        // if (!$cachedata) $cachedata = [];
        // $name = input('name');
        // $data = Db::table($this->db . '.vip_viplabel_info')->field('info')->where('name', $name)->select();
        // $ext = Db::table($this->db . '.vip_viplist')->field('extension')->where('code', input('code'))->find();
        // $json_ext = json_decode($ext['extension'], true);
        // if (!empty($data)) {
        //     foreach ($data as $k => $v) {
        //         $data[$k]['on'] = false;
        //         if ($json_ext && array_key_exists(input('name'), $json_ext)) {
        //             for ($i = 0; $i < count($json_ext[input('name')]); $i++) {
        //                 if ($json_ext[input('name')][$i] == $v['info']) {
        //                     $data[$k]['on'] = true;
        //                     if (array_key_exists(input('name'), $cachedata)) {
        //                         array_push($cachedata[input('name')], $data[$k]['info']);
        //                     } else {
        //                         $cachedata[input('name')][0] = $data[$k]['info'];
        //                     }

        //                     cache('vip_label_cache' . session('info.staff'), $cachedata, 3600);
        //                 }
        //             }
        //         }
        //     }
        // }
        webApi(200, 'ok', $data);
    }

    /**
     * 点击将标签放入缓存
     */
    public function clickLabel()
    {
        $data = Db::table($this->db . '.vip_label_middle')->where('vip_code', input('code'))->where('info_id', input('id'))->find();
        if (!empty($data)) {
            Db::table($this->db . '.vip_label_middle')->where('vip_code', input('code'))->where('info_id', input('id'))->delete();
        } else {
            $label = [
                'vip_code' => input('code'),
                'label_code' => input('label_code'),
                'info_name' => input('name'),
                'info_id' => input('id'),
            ];
            Db::table($this->db . '.vip_label_middle')->insert($label);
        }
        // 获得缓存
        // $data = cache('vip_label_cache' . session('info.staff'));
        // if (!$data) $data = [];
        // // 判断该扩展型标签是否已经选择内容
        // if (array_key_exists(input('key'), $data)) {
        //     // 判断是选中还是取消
        //     if (in_array(input('val'), $data[input('key')])) { // 取消
        //         unset($data[input('key')][array_search(input('val'), $data[input('key')])]);
        //         sort($data[input('key')]);
        //     } else { // 选中
        //         array_push($data[input('key')], input('val'));
        //     }
        // } else {
        //     $data[input('key')][0] = input('val');
        // }

        // cache('vip_label_cache' . session('info.staff'), $data, 3600);
        webApi(0);
    }

    /**
     * 编辑会员标签
     */
    public function editLabel()
    {
        // $id = input('code');

        // $data = cache('vip_label_cache' . session('info.staff')) ?? [];
        // if (!empty($data)) {
        //     $str = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        // } else {
        //     $str = '';
        // }
        // $res = Db::table($this->db . '.vip_viplist')->where('code', $id)->setField('extension', $str);
        // if ($res) {
        //     cache('vip_label_cache' . session('info.staff'), null);
            webApi(200, '编辑成功');
        // } else {
        //     webApi(400, '编辑失败');
        // }
    }

    /**
     * 订单明细
     */
    public function sellinginfo()
    {
        $ordercode = input('ordercode') ?? null;

        if ($ordercode == null) {
            webApi(400);
        }
        $data = Db::table($this->db . '.view_vipinfo_goods')
            //  商品名称, 件数,   图片,    金额
            ->field('gname, number, photo, dis_money, color, sizes, frenum')
            ->where('order_code', $ordercode)
            ->page(input('page'), input('limit'))
            ->select();
        foreach ($data as $k => $v) {
            $data[$k]['dis_money'] = number_format($v['dis_money'], 2, '.', '');
        }
        $count = Db::table($this->db . '.view_vipinfo_goods')->where('order_code', $ordercode)->count();
        $data = [
            'data' => $data,
            'count' => $count
        ];

        webApi(200, 'ok', $data);
    }

    /**
     * 会员等级
     */
    public function memberlevel()
    {
        $whereStaff = [];
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['code'] == 'boss') {
            $whereStaff = [];
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $stores = $ew->org()->store()->get();
            $a_org = explode(',', $operate['admin_org_code']);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $stores = $stores . ', ';
            }
            $staff_code = Db::table($this->db . '.vip_staff')->where('store_code', 'in', $stores)->field('code')->select();
            if ($staff_code) {
                $arr = implode(',', array_column($staff_code, 'code'));
                $whereStaff[] = ['consultant_code', 'in', $arr];
            } else {
                //返回数据
                webApi(200, 'ok', []);
            }
        } else {
            $whereStaff[] = ['consultant_code', '=', $operate['code']];
        }

        $level = Db::table($this->db . '.vip_viplevel')->field('uid, username, code')->order('uid', 'asc')->select();
        $vip = Db::table($this->db . '.vip_viplist')->field('level_code')->where($whereStaff)->select();

        foreach ($level as $k => $v) {
            $level[$k]['count'] = 0;
        }

        foreach ($level as $k => $v) {
            foreach ($vip as $val) {
                if ($v['code'] == $val['level_code']) {
                    $level[$k]['count'] += 1;
                }
            }
        }

        webApi(200, 'ok', $level);
    }

    /**
     * 会员等级下会员信息, 查询该等级信息
     * @param array $count 数据长度|数据条数
     * @param array $data 返回数据内容
     */
    public function levellist()
    {
        [$page, $limit, $code, $vague] = [input('page'), input('limit'), input('code'), input('vague')]; // input('code'); 会员等级CODE

        if ($code == null) {
            webApi(400, '参数错误');
        }

        if (!empty($vague)) {
            $where[] = ['code|username|phone', 'like', $vague . '%'];
        } else {
            $where = true;
        }

        $whereStaff = [];
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['code'] == 'boss') {
            $whereStaff = [];
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $stores = $ew->org()->store()->get();
            $a_org = explode(',', $operate['admin_org_code']);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $stores = $stores . ', ';
            }
            $staff_code = Db::table($this->db . '.vip_staff')->where('store_code', 'in', $stores)->field('code')->select();
            if ($staff_code) {
                $arr = implode(',', array_column($staff_code, 'code'));
                $whereStaff[] = ['consultant_code', 'in', $arr];
            } else {
                //返回数据
                webApi(200, 'ok', []);
            }
        } else {
            $whereStaff[] = ['consultant_code', '=', $operate['code']];
        }

        $data = Db::table($this->db . '.vip_viplist')
            //       会员姓名,    生日,      总消费金额,      会员照片, 未消费天数, 消费次数
            ->field('code, username, birthday, total_consumption, img, consumption_times, final_purchases, return_visit, phone, consumption_number, vvip')
            ->where($where)
            ->where($whereStaff)
            ->where('level_code', $code)
            ->page($page, $limit)
            ->select();

        foreach ($data as $k => $v) {
            if (strlen($v['birthday']) == 8) {
                $data[$k]['birthday'] = date('Y-m-d', strtotime($v['birthday']));
            } else {
                $data[$k]['birthday'] = date('Y-m-d', $v['birthday']);
            }
            $data[$k]['total_consumption'] = number_format($v['total_consumption'], 2, '.', '');
            if ($v['final_purchases'] > time()) {
                $data[$k]['r_days'] = "0";
            } else {
                $data[$k]['r_days'] =  number_format((time() - $v['final_purchases']) / 86400, 0);
            }
            if ($v['return_visit'] > time()) {
                $data[$k]['return_visit'] = "0";
            } else {
                $data[$k]['return_visit'] =  number_format((time() - $v['return_visit']) / 86400, 0);
            }
        }
        //统计数量
        $count = Db::table($this->db . '.vip_viplist')
            ->where($where)
            ->where($whereStaff)
            ->where('level_code', $code)
            ->count();

        $data = [
            'count' => $count,
            'data' => $data
        ];

        webApi(200, 'ok', $data);
    }

    /**
     * 新入会员   
     * 需要传入input('type) 今日 : today, 本周 : week, 本月 : month
     * @param int $counttoday 今日新入会员数量
     * @param int $countweek 本周新入会员数量
     * @param int $countmonth 本月新入会员数量
     */
    public function newentry()
    {
        [$page, $limit] = [input('page'), input('limit')];

        $whereStaff = [];
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['code'] == 'boss') {
            $whereStaff = [];
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $stores = $ew->org()->store()->get();
            $a_org = explode(',', $operate['admin_org_code']);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $stores = $stores . ', ';
            }
            $staff_code = Db::table($this->db . '.vip_staff')->where('store_code', 'in', $stores)->field('code')->select();
            if ($staff_code) {
                $arr = implode(',', array_column($staff_code, 'code'));
                $whereStaff[] = ['consultant_code', 'in', $arr];
            } else {
                //返回数据
                webApi(200, 'ok', []);
            }
        } else {
            $whereStaff[] = ['consultant_code', '=', $operate['code']];
        }

        //统计数量
        $counttoday = Db::table($this->db . '.vip_viplist')->where($whereStaff)->whereTime('date_registration', 'today')->count();
        $countweek = Db::table($this->db . '.vip_viplist')->where($whereStaff)->whereTime('date_registration', 'week')->count();
        $countmonth = Db::table($this->db . '.vip_viplist')->where($whereStaff)->whereTime('date_registration', 'month')->count();

        $type = input('type');

        $data = Db::table($this->db . '.vip_viplist')
            //       会员姓名,    生日,      总消费金额,      会员照片, 未消费天数, 消费次数
            ->field('code, username, birthday, total_consumption, img, consumption_times, final_purchases, consumption_number, date_registration, return_visit, phone, vvip')
            ->whereTime('date_registration', $type)
            ->where($whereStaff)
            ->page($page, $limit)
            ->select();
        foreach ($data as $k => $v) {
            if (strlen($v['birthday']) == 8) {
                $data[$k]['birthday'] = date('Y-m-d', strtotime($v['birthday']));
            } else {
                $data[$k]['birthday'] = date('Y-m-d', $v['birthday']);
            }
            $data[$k]['total_consumption'] = number_format($v['total_consumption'], 2, '.', '');
            if ($v['final_purchases'] > time()) {
                $data[$k]['r_days'] = "0";
            } else {
                $data[$k]['r_days'] =  number_format((time() - $v['final_purchases']) / 86400, 0);
            }
            if ($v['return_visit'] > time()) {
                $data[$k]['return_visit'] = "0";
            } else {
                $data[$k]['return_visit'] =  number_format((time() - $v['return_visit']) / 86400, 0);
            }
        }
        $count = Db::table($this->db . '.vip_viplist')->whereTime('date_registration', $type)->where($whereStaff)->count();
        $data = [
            'data' => [
                'data' => $data,
                'count' => $count
            ],
            'count' => [
                'counttoday' => $counttoday,
                'countweek' => $countweek,
                'countmonth' => $countmonth
            ]
        ];

        webApi(200, 'ok', $data);
    }

    /**
     * 活跃会员
     * @param array $data 会员信息
     * @param int $countOne 30天以内
     * @param int $countThree 30-60天
     * @param int $countSix 60-180天
     * @param int $countNine 180-270天
     * @param int $countTwelve 270-360天
     */
    public function active()
    {
        [$page, $limit] = [input('page'), input('limit')];

        $whereStaff = [];
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['code'] == 'boss') {
            $whereStaff = [];
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $stores = $ew->org()->store()->get();
            $a_org = explode(',', $operate['admin_org_code']);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $stores = $stores . ', ';
            }
            $staff_code = Db::table($this->db . '.vip_staff')->where('store_code', 'in', $stores)->field('code')->select();
            if ($staff_code) {
                $arr = implode(',', array_column($staff_code, 'code'));
                $whereStaff[] = ['consultant_code', 'in', $arr];
            } else {
                //返回数据
                webApi(200, 'ok', []);
            }
        } else {
            $whereStaff[] = ['consultant_code', '=', $operate['code']];
        }

        //统计数量
        $countOne = Db::table($this->db . '.view_viplist')->where($whereStaff)->where('rfm_days', '>=', '0')->where('rfm_days', '<', '30')->count();
        $countThree = Db::table($this->db . '.view_viplist')->where($whereStaff)->where('rfm_days', '>=', '30')->where('rfm_days', '<', '90')->count();
        $countSix = Db::table($this->db . '.view_viplist')->where($whereStaff)->where('rfm_days', '>=', '90')->where('rfm_days', '<', '180')->count();
        $countNine = Db::table($this->db . '.view_viplist')->where($whereStaff)->where('rfm_days', '>=', '180')->where('rfm_days', '<', '270')->count();
        $countTwelve = Db::table($this->db . '.view_viplist')->where($whereStaff)->where('rfm_days', '>=', '270')->where('rfm_days', '<', '360')->count();

        $type = input('type');

        switch ($type) {
            case 'one':
                $a = 0;
                $b = 30;
                break;
            case 'three':
                $a = 31;
                $b = 90;
                break;
            case 'six':
                $a = 91;
                $b = 180;
                break;
            case 'nine':
                $a = 181;
                $b = 270;
                break;
            case 'twelve':
                $a = 271;
                $b = 360;
                break;
        }

        $data = Db::table($this->db . '.view_viplist')
            ->field('code, username, birthday, total_consumption, phone, img, r_days, consumption_times, consumption_number, date_registration, rfm_days, return_visit')
            ->where($whereStaff)->where('rfm_days', '>=', $a)->where('rfm_days', '<', $b)
            ->page($page, $limit)
            ->select();
        $count = Db::table($this->db . '.view_viplist')->where($whereStaff)->where('rfm_days', '>=', $a)->where('rfm_days', '<', $b)->count();
        $data = [
            'data' => [
                'data' => $data,
                'count' => $count
            ],
            'count' => [
                'countOne' => $countOne,
                'countThree' => $countThree,
                'countSix' => $countSix,
                'countNine' => $countNine,
                'countTwelve' => $countTwelve
            ]
        ];

        webApi(200, 'ok', $data);
    }

    /**
     * 高级筛选列表
     */
    public function wapFilterlist()
    {
        $data = Db::table($this->db . '.vip_filter')->field('id, filtertitle')->select();
        webApi(200, 'ok', $data);
    }

    /**
     * 点击列表后查询
     */
    public function wapFilter()
    {
        $id = input('id') ?? null;
        $sxq = Db::table($this->db . '.vip_filter')->where('id', $id)->find();
        $sx = json_decode($sxq['content'], true);

        $ext = Db::table($this->db . '.vip_viplabel')->field('code')->select();
        if (!empty($ext)) {
            $extend = array_column($ext, 'code');
        } else {
            $extend = [];
        }

        $whereStaff = [];
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['code'] == 'boss') {
            $whereStaff = [];
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $stores = $ew->org()->store()->get();
            $a_org = explode(',', $operate['admin_org_code']);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $stores = $stores . ', ';
            }
            $staff_code = Db::table($this->db . '.vip_staff')->where('store_code', 'in', $stores)->field('code')->select();
            if ($staff_code) {
                $arr = implode(',', array_column($staff_code, 'code'));
                $whereStaff[] = ['v.consultant_code', 'in', $arr];
            } else {
                //返回数据
                webApi(200, 'ok', []);
            }
        } else {
            $whereStaff[] = ['v.consultant_code', '=', $operate['code']];
        }

        $where = '1=1';
        if (!empty($sx)) {
            $float = ['acs', 'total_consumption', 'stored_value', 'residual_value', 'total_value', 'total_frozen_value'];
            $level_code = ['level_code'];
            $sex = ['sex'];
            $store_code = ['store_code', 'reg_store'];
            $staff_code = ['consultant_code'];
            $birthday = ['birthday'];
            $time = ['date_registration', 'vvip_time', 'first_time', 'final_purchases'];
            foreach ($sx as $v) {
                if (in_array($v['tit'], $extend)) {
                    if ($v['sym'] == 'LIKE') {
                        webApi(0, '', 0, []);
                    } else {
                        $label = Db::table($this->db . '.vip_label_middle')->where('label_code', $v['tit'])->where('info_name', $v['val'])->select();
                        if (!empty($label)) {
                            $arr = array_column($label, 'vip_code');
                            $vv = '';
                            foreach ($arr as $av) {
                                $vv .= "'" . $av . "',";
                            }
                            $aaa = trim($vv, ','); // trim 去掉字符串两边符号
                            $where .= " and v.code in (" . $aaa . ")";
                        } else {
                            webApi(0, '', 0, []);
                        }
                    }
                    // $where .= ' and json_search(json_extract(IF(IFNULL(extension, "[]") = "", "[]", extension), \'$."'.$v['tit'].'"\'), "all", "%'.$v['val'].'%")  is not null';
                } else if (in_array($v['tit'], $birthday)) {
                    if ($v['sym'] == 'LIKE') {
                        webApi(0, '', 0, []);
                    } else {
                        $where .= ' and month(b.birthday) = ' . date('m', strtotime('2019-' . $v['val'])) . ' and day(b.birthday) = '. date('d', strtotime('2019-' . $v['val']));
                    }
                } else if (in_array($v['tit'], $time)) {
                    if ($v['sym'] == 'LIKE') {
                        webApi(0, '', 0, []);
                    } else {
                        $whereTime[] = ['v.' . $v['tit'], $v['sym'], strtotime($v['val'])];
                    }
                    
                } else if (in_array($v['tit'], $sex)) {
                    if ($v['sym'] == 'LIKE') {
                        webApi(0, '', 0, []);
                    } else {
                        if ($this->db = 'guodian') {
                            $where .= ' and v.'. $v['tit'].' = ' . "'".$v['val']."'";
                        } else {
                            if ($v['val'] == '女') {
                            $where_sex = 'W';
                            } else {
                                $where_sex = 'M';
                            }
                            $where .= ' and v.'. $v['tit'].' = ' . "'".$where_sex."'";
                        }
                    }
                    
                } else if (in_array($v['tit'], $level_code)){
                    if ($v['sym'] == 'LIKE') {
                        webApi(0, '', 0, []);
                    } else {
                            $level = Db::table($this->db. '.vip_viplevel')->field('code')->where('username', $v['val'])->find();
                        $where .= ' and ' . 'v.' . $v['tit'] . $v['sym'] . ' "' . $level['code'] . '"';
                    }
                    
                } else if (in_array($v['tit'], $store_code)) {
                    if ($v['sym'] == 'LIKE') {
                        webApi(0, '', 0, []);
                    } else {
                        $store = Db::table($this->db . '.vip_store')->field('code')->where('name', $v['val'])->find();
                        $where .= ' and ' . 'v.' . $v['tit'] . $v['sym'] . ' "' . $store['code'] . '"';
                    }
                    
                } else if (in_array($v['tit'], $staff_code)) {
                    if ($v['sym'] == 'LIKE') {
                        webApi(0, '', 0, []);
                    } else {
                        $staff = Db::table($this->db . '.vip_staff')->field('code')->where('name', $v['val'])->find();
                        $where .= ' and ' . 'v.' . $v['tit'] . $v['sym'] . ' "' . $staff['code'] . '"';
                    }
                } else{
                    if ($v['sym'] == 'LIKE') {
                        $where .= ' and '.'v.'.$v['tit'].' ' .$v['sym'].' "'.$v['val'].'%"';
                    } else {
                        if (in_array($v['tit'], $float)) {
                            $where .= ' and ' . 'v.' . $v['tit'].$v['sym'].' '.$v['val'];
                        } else {
                            $where .= ' and ' . 'v.' . $v['tit'].$v['sym'].' "'.$v['val'].'"';
                        }
                    }
                }
            }
        }
        $data = Db::table($this->db . '.vip_viplist')
            ->alias('v')
            ->leftJoin($this->db . '.vip_viplevel level', 'level.code = v.level_code')
            ->leftJoin($this->db . '.vip_store store', 'store.code = v.store_code')
            ->leftJoin($this->db. '.view_viplist b', 'b.code = v.code')
            ->leftJoin($this->db . '.vip_staff staff', 'staff.code = v.consultant_code')
            ->leftJoin($this->db . '.vip_org org', 'org.code = store.org_code')
            ->field('v.id, v.code, v.username, v.sex, level.username levelname, v.phone, v.identity, v.nation, b.birthday, v.calendar, org.name orgname, store.name storename, staff.name staffname, v.acs, v.qq, v.weixin, v.area, v.address, v.consumption_times, v.consumption_number, v.total_consumption, v.first_time, v.final_purchases, v.residual_integral, v.total_integral, v.vvip, v.vvip_time, v.date_registration, v.img, v.return_visit')
            ->where($whereStaff)
            ->where($where)
            ->page(input('page'), input('limit'))
            ->select();


        foreach ($data as $k => $v) {
            // if (strlen($v['birthday']) == 8) {
            //     $data[$k]['birthday'] = date('Y-m-d', strtotime($v['birthday']));
            // } else {
            //     $data[$k]['birthday'] = date('Y-m-d', $v['birthday']);
            // }
            $data[$k]['date_registration'] = date('Y-m-d', $v['date_registration']);
            $data[$k]['total_consumption'] = number_format($v['total_consumption'], 2, '.', '');
            $data[$k]['r_days'] =  number_format((time() - $v['final_purchases']) / 86400, 0);
            $data[$k]['return_visit'] =  number_format((time() - $v['return_visit']) / 86400, 0);
        }
        $count = Db::table($this->db . '.vip_viplist')
            ->alias('v')
            ->leftJoin($this->db . '.vip_viplevel level', 'level.code = v.level_code')
            ->leftJoin($this->db . '.vip_store store', 'store.code = v.store_code')
            ->leftJoin($this->db. '.view_viplist b', 'b.code = v.code')
            ->leftJoin($this->db . '.vip_staff staff', 'staff.code = v.consultant_code')
            ->leftJoin($this->db . '.vip_org org', 'org.code = store.org_code')
            ->where($whereStaff)
            ->where($where)
            ->count();

        $data = [
            'data' => $data,
            'count' => $count
        ];
        webApi(200, 'ok', $data);
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $id = input('id');

        if ($id == null) {
            webApi(400, '参数错误');
        }

        $data = [];

        $res = Db::table($this->db . '.vip_viplist')->update($data);

        if ($res) {
            webApi(200, 'ok');
        } else if ($res === false) {
            webApi(400, 'no', '编辑信息失败!');
        } else {
            webApi(400, 'no', '未修改!');
        }
    }

    /**
     * 修改等级
     */
    public function Manualupgrade()
    {
        if (empty(input('code'))) {
            webApi(400, '参数错误');
        }

        $code = input('code');
        $data = [
            'level_code' => input('level_code')
        ];

        $before_level = Db::table($this->db . '.vip_viplist')->where('code', $code)->field('level_code, username, code')->find();
        $operate = session('info.staff');
        $operate_name = Db::table($this->db . '.vip_staff')->field('name')->where('code', $operate)->find();

        $rsdata = [
            'vip_name' => $before_level['username'], //会员姓名
            'vip_code' => $before_level['code'], //会员卡号
            'before_level' => $before_level['level_code'] ?? '', //更改前等级
            'after_level' => input('level_code'), //更改后等级
            'create_time' => time(), //更改时间
            'reason' => input('reason'), //更改原因
            'operator_code' => $operate == null ? 0 : $operate, //操作人工号
            'operator_name' => $operate_name['name'] == null ? '' : $operate_name['name'], //操作人姓名
        ];
        unset($before_level, $operate_name, $operate);
        //启动事务
        Db::startTrans();
        try {
            Db::table($this->db . '.vip_viplist')->where('code', $code)->update($data);
            unset($data);
            Db::table($this->db . '.vip_promote')->insert($rsdata);
            //提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            //回滚事务
            Db::rollback();
            $res = false;
        }

        //提示信息
        if ($res) {
            webApi(200, 'ok');
        } else {
            webApi(400, '失败');
        }
    }

    /**
     * 会员标签下一级
     */
    public function extend()
    {
        $data = Db::table($this->db . '.vip_viplabel_info')->field('id, info as name,name as type')->where('name', input('name'))->select();
        $ext = cache('vip_extend_edit' . input('access_token')) ?? [];
        if (!empty($ext) && array_key_exists(input('name'), $ext)) {
            foreach ($data as $k => $v) {
                $data[$k]['on'] = false;
                for ($i = 0; $i < count($ext[input('name')]); $i++) {
                    if ($ext[input('name')][$i] == $v['name']) {
                        $data[$k]['on'] = true;
                    }
                }
            }
            unset($ext);
        }
        webApi(200, 'ok', $ext);
    }

    /**
     * 组织机构
     */
    public function org()
    {
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['code'] == 'boss') {
            $data = Db::table($this->db . '.vip_org')->field('code, name')->select();
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $data = Db::table($this->db . '.vip_org')->field('code, name')->where('code', 'in', $operate['admin_org_code'])->select();
        } else {
            $data = Db::table($this->db . '.vip_org')->field('code, name')->where('code', $operate['org_code'])->select();
        }
        webApi(200, 'ok', $data);
    }

    /**
     * 查询会员级别下拉框信息
     */
    public function drop_level()
    {
        $data = Db::table($this->db . '.vip_viplevel')->field('code,username')->select();
        webApi(200, 'ok', $data);
    }

    /**
     * 门店下拉框
     */
    public function store()
    {
        $org = input('code');

        if (!empty($org)) {
            $orgs = Db::table($this->db . '.vip_org')->field('code,path')->where('pid',  $org)->select();
            if (empty($orgs)) {
                $data = Db::table($this->db . '.vip_store')->field('name, code')->where('status', 0)->where('org_code', $org)->select();
            } else {
                $oo = array_column($orgs, 'code');
                $data = Db::table($this->db . '.vip_store')->field('name, code')->where('status', 0)->where('org_code', 'in', $oo)->select();
            }
        } 
        else {
            $data = [];
        }
        webApi(200, 'ok', $data);
    }

    public function wsstore()
    {
        $data = Db::table($this->db . '.vip_store')->field('name, code')->where('status', 0)->select();
        webApi(200, 'ok', $data);
    }

    /**
     * 员工下拉框
     */
    public function staff()
    {
        $store = input('code');
        if (!empty($store)) {
            $data = Db::table($this->db . '.vip_staff')->where('store_code', $store)->where('status', '<>', 1)->field('name, code')->select();
        } else {
            $data = [];
        }
        // else {
        //     $data = Db::table($this->db . '.vip_staff')->field('name, code')->where('status', '<>', 1)->select();
        //     foreach ($data as $k => $v) { // 去掉员工中BOSS角色
        //         if ($v['code'] == 'boss') {
        //             unset($data[$k]);
        //         }
        //     }
        //     sort($data);
        // }
        webApi(200, 'ok', $data);
    }

    /**
     * 无限制员工下拉框
     */
    public function wstaff()
    {
        $data = Db::table($this->db . '.vip_staff')->where('status', '<>', 1)->field('name, code')->select();
        
        webApi(200, 'ok', $data);
    }


    /**
     * 首页会员信息
     */
    public function viplist()
    {
        $staff = session('info.staff');
        if ($staff == null) {
            webApi(400, '参数错误');
        }
        $data = Db::table($this->db . '.view_vip_staff')
            //-------卡号, 姓名, 职位, 组织机构, 门店, 手机节点
            ->field('code, name, vpname, vgname, vsname, m_auth_code')
            ->where('code', $staff)
            ->find();
        $data['phone_code'] = explode(',', $data['m_auth_code']);
        unset($staff);
        webApi(200, 'ok', $data);
    }

    /**
     * 会员中心广告
     */
    public function advertisement()
    {
        $data = Db::table($this->db . '.vip_advertisement')->where('position', 1)->where('display', 0)->select();

        webApi(200, 'ok', $data);
    }

    /**
     * 查询消费广告
     */
    public function consumerAdvertising()
    {
        $data = Db::table(input('company') . '.vip_advertisement')->where('position', 2)->where('display', 0)->select();

        webApi(200, 'ok', $data);
    }


    /**
     * 会员资料点击点击电话 修改回访  
     */
    public function visit()
    {

        $id = input('code');
        Db::table($this->db . '.vip_viplist')->where('code', $id)->setField('return_visit', time());
        $user = Db::table($this->db . '.vip_viplist')->where('code', $id)->find('username');
        $data = [
            'user_name' => $user['username'],
            'user_code' => $id,
            'visit_operator' => session('info.staff'),
            'visit_mode' => '电话回访',
            'time' => time()
        ];
        Db::table($this->db . '.vip_returnvisit_record')->insert($data);
    }


    /**
     * 线下门店地址信息
     */
    public function offlineStore()
    {
        [$db, $page, $limit] = [$this->db, input('page'), input('limit')];

        $data = Db::table($db . '.vip_store')
            ->field('name, contacts, phone, address, full_address')
            ->where('status', 0)
            ->page($page, $limit)
            ->select();
        $count = Db::table($db . '.vip_store')
            ->where('status', 0)
            ->count();
        $data = [
            'data' => $data,
            'count' => $count
        ];
        webApi(200, 'ok', $data);
    }


    /**
     * 图片上传
     */
    public function uploadImg()
    {
        if (input('model') == '') {
            webApi(400, '缺少参数');
        }
        // 获取 表单上传的文件
        $file = request()->file('file');
        // 移动文件到服务器
        $info = $file->move('../web/images');
        if ($info) {
            // 获取上传图片后文件的绝对路径
            $path = Env::get('root_path') . 'web/images/' . $info->getSaveName();
            $md5File = md5_file($path);
            // 实例化图片处理类
            $image = new Image();
            // 打开图片
            $image->open($path);
            // 压缩图片
            $image->thumb(512, 512);
            // 将压缩后的图片覆盖原图
            $image->save($path);
            // 拼接请求路径
            // $url = $this->ssl . 'company.com/' . config('api.imgUploadUrl');
            $url = $this->ssl . 'company.suokeduo.com/index.php/v1/webVipApiOss/';
            // 请求总后台上传接口
            $res = json_decode(doHttpPost($url, [
                'bus_account' => $this->db, 'type' => 'upload', 'file' => $path,
                'model' => input('model'), 'old_img' => strVal(input('old_img')), 'md5_file' => $md5File
            ]), true);
            if ($res['code'] == 200) {
                if (input('model') == 'vips') {
                    Db::table($this->db . '.vip_viplist')
                    ->where('code', input('code'))
                    ->data(['vimg' => $res['data']])
                    ->update();
                } else if (input('model') == 'goods') {
                    Db::table($this->db . '.vip_goods')
                    ->where('frenum', input('code'))
                    ->where('color', input('color'))
                    ->data(['img' => $res['data']])
                    ->update();
                }
                unset($info);
                is_file($path) && unlink($path);
                webApi(200, 'ok', $res['data']);
            } else {
                unset($info);
                is_file($path) && unlink($path);
                if ($res['msg'] == null) {
                    webApi(400, '上传失败！');
                } else {
                    webApi(400, $res['msg']);
                }
            }
        } else {
            $msg = '上传失败！' . $file->getError();
            webApi(400, $msg);
        }
    }

    /**
     * 被谁介绍来的
     */
    public function introducedMe()
    {
        //被谁介绍来的
        $introducedMe = Db::table($this->db.'.vip_introducer')->field('id, lnt_name, lnt_code, lnttime, remarks')->where('rsid_code', input('code'))->find();
        if (!empty($introducedMe)) {
            $introducedMe['lnttime'] = date('Y-m-d H:i:s', $introducedMe['lnttime']);
        }
        
        //介绍的谁来
        $youIntroduce = Db::table($this->db.'.vip_introducer')->field('id, rsid_name, rsid_code, lnttime, remarks')->where('lnt_code', input('code'))->select();
        foreach ($youIntroduce as $k=>$v) {
            $youIntroduce[$k]['lnttime'] = date('Y-m-d H:i:s', $v['lnttime']);
        }
        $data = [
            'introducedMe' => $introducedMe,
            'youIntroduce' => $youIntroduce
        ];
        unset($youIntroduce, $introducedMe);
        webApi(200, 'ok', $data);
    }

    /**
     * 编辑介绍信息的备注
     */
    public function introducedRemarks()
    {
        $data = Db::table($this->db.'.vip_introducer')->where('id', input('id'))->update(['remarks' => input('remarks')]);

        if ($data) {
            webApi(200, '编辑成功');
        } else {
            webApi(400, '未修改');
        }
    }

    /**
     * 查询专属
     */
    public function exclusive()
    {
        $data = Db::table($this->db.'.vip_viplist')->field('id, remarks')->where('code', input('code'))->find();
        webApi(200, 'ok', $data);
    }

    /**
     * 编辑专属信息
     */
    public function editExclusive()
    {
        $data = Db::table($this->db.'.vip_viplist')->where('id', input('id'))->update(['remarks' => input('remarks')]);
        
        if ($data) {
            webApi(200, '编辑成功');
        } else {
            webApi(400, '未修改');
        }
    }

}
