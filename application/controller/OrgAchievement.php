<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;
use app\service\Time;

/**
 * Author hrl
 * Date 2019/2/19
 * Description 业绩
 * 1. 默认 就是 进来的情况 通过session 进行查询
 * 2. 返回当前时间的前六天或者当前月的前六个月
 * 3. 业绩只存在于门店统计的时候如果一家店就统计一家店一天的或者一个月的业绩或者多家店的一天的或者一个月的业绩
 */
class OrgAchievement extends Common
{
    /**
     * 机构业绩查询
     */
    public function index()
    {
        $db = $this->db;
        // 选了就是选择的日期类型, 没选择默认是天
        $type = input('type') ?? 'd';
        // 选择了就是选择的, 没选默认是false当天
        $check = input('check') ?? false;
        // 表格选择的时间处理
        $tableTime = $this->achieveTable($type, $check);
        // $timeWhere[] = ['o.create_time', '>=', $tableTime[0]];
        // $timeWhere[] = ['o.create_time', '<=', $tableTime[1]];
        $timeWhere[] = ['create_time', '>=', $tableTime[0]];
        $timeWhere[] = ['create_time', '<=', $tableTime[1]];
        // dump(date('Y-m-d H:i:s', $tableTime[0]));
        // dump(date('Y-m-d H:i:s', $tableTime[1])); exit;
        // 条件 round 四舍五入  real_pay->业绩, code->单数, number->件数, custUnitPrice->客单价，joint->连带率 
        $field = 'round(ifnull(sum(real_pay), 0), 2) as achievement,
                count(code) as codeNumber,ifnull(sum(number), 0) as number, 
                round(ifnull(sum(real_pay) / count(code), 0), 2) as custUnitPrice,
                round(ifnull(sum(number) / count(code), 0), 2) as joint';
        //查询当前登入人 session('info.staff')
        $operate = Db::table($db . '.vip_staff')->where('code', session('info.staff'))->find();
        //组织限制
        if ($operate['code'] == 'boss') {
            $orgdata = Db::table($db . '.vip_org')
                ->field('code, name')
                ->select();
            $orgs = implode(',', array_column($orgdata, 'code'));
        } else if ($operate['role'] == 0) {
            $org = $this->orgSubordinate($operate['admin_org_code']);
            $orgdata = Db::table($db . '.vip_org')
                ->field('code, name')
                ->where('code', 'in', $org)
                ->select();
            $orgs = $operate['admin_org_code'];
        } else {
            $orgdata = Db::table($db . '.vip_org')
                ->field('code, name')
                ->where('code', $operate['org_code'])
                ->select();
            $orgs = $operate['org_code'];
        }
        //查询 曲线图y轴 业绩 x轴 时间点
        [$time, $picData] = $this->getPicData($db, $orgs, $type, $check);
        //定义变量
        $data = [];
        if (!empty($orgs)) { // 不为空就是组织的业绩
            // for ($i = 0; $i < count($orgAll); $i++) {
            //     $orgAllStore = Db::table($this->db . '.vip_store')->field('code')->where('org_code', $orgAll[$i])->where('status', 0)->select();
            //     $orgAllStore = implode(',', array_column($orgAllStore, 'code'));
            //     $find = Db::table($db . '.vip_goods_order')
            //         ->field($field)
            //         ->where('status', 0)
            //         ->where('store_code', 'in', $orgAllStore)
            //         ->where($timeWhere)
            //         ->find();
            //     $find['level'] = 'org';
            //     $orgName = Db::table($db . '.vip_org')->field('name')->where('code', $orgAll[$i])->find();
            //     $find['name'] = '机构 - ' . $orgName['name'];
            //     $data[] = $find;
            // }
            foreach ($orgdata as  $v) {
                // $orgName = Db::table($this->db . '.vip_org')->field('name')->where('code', $v['code'])->find();
                // $result['name'] = $orgName['name'];
                // $data[$k] = $result;
                $ew = new ErpWhere($db, $v['code']);
                // 循环查找业绩
                $find = Db::table($this->db . '.vip_goods_order')
                    ->field($field)
                    ->where('status', 0)
                    ->where('store_code', 'in', $ew->org()->store()->get())
                    ->where($timeWhere)
                    ->find();
                // 获取机构名称
                $find['name'] = '机构 - ' . $v['name'];
                $data[] = $find;
            }
            array_multisort(array_column($data, 'achievement'), SORT_DESC, $data); // 业绩排序
        }
        // 批量返回数据
        $allData = [
            'pic' => $picData,   // 曲线图y轴 业绩
            'time' => $time,     // x轴 时间点
            'table' => $data     // 表格数据  包含总计
        ];
        webApi(200, 'ok', $allData);
    }

    /**
     * 门店业绩查询
     */
    public function storeAchievement()
    {
        $db = $this->db;
        // 选了就是选择的日期类型 没选择默认是天
        $type = input('type') ?? 'd';
        // 选择了就是选择的没选默认是false  false  当天日期
        $check = input('check') ?? false;
        // 表格选择的时间处理
        $tableTime = $this->achieveTable($type, $check);
        $timeWhere[] = ['create_time', '>=', $tableTime[0]];
        $timeWhere[] = ['create_time', '<=', $tableTime[1]];
        // 条件 round 四舍五入  real_pay->业绩, code->单数, number->件数, custUnitPrice->客单价，joint->连带率 
        $field = 'round(ifnull(sum(real_pay), 0), 2) as achievement,
                count(code) as codeNumber,ifnull(sum(number), 0) as number, 
                round(ifnull(sum(real_pay) / count(code), 0), 2) as custUnitPrice,
                round(ifnull(sum(number) / count(code), 0), 2) as joint';
        //查询当前登入人 session('info.staff')
        $operate = Db::table($db . '.vip_staff')->where('code', session('info.staff'))->find();
        //组织限制 $orgs = 'ZZJG15459825114064';
        if ($operate['code'] == 'boss') {
            $orgdata = Db::table($db . '.vip_org')
                ->field('code, name')
                ->select();
            $orgs = implode(',', array_column($orgdata, 'code'));
        } else if ($operate['role'] == 0) {
            $org = $this->orgSubordinate($operate['admin_org_code']);
            $orgdata = Db::table($db . '.vip_org')
                ->field('code, name')
                ->where('code', 'in', $org)
                ->select();
            $orgs = $operate['admin_org_code'];
        } else {
            $orgs = $operate['store_code'];
        }
        // 图形的时间
        [$time, $picData] = $this->getPicData($db, $orgs, $type, $check);
        // 查询总计条件
        $ew = new ErpWhere($db, $orgs);
        $store = $ew->org()->store()->get();
        // 查询循环条件
        $storeArray = explode(',', $store);
        // 定义变量,判断是否为空
        $data = [];
        if (!empty($store)) {
            // 查询各个门店的业绩
            for ($i = 0; $i < count($storeArray); $i++) {
                $find = Db::table($db . '.vip_goods_order')
                    ->field($field)
                    ->where('status', 0)
                    ->where('store_code', $storeArray[$i])
                    ->where($timeWhere)
                    ->find();
                $find['level'] = 'store';
                $storeName = Db::table($db . '.vip_store')->field('name')->where('code', $storeArray[$i])->where('status', 0)->find();
                $find['name'] = '门店 - ' . $storeName['name'];
                $data[] = $find;
            }
            // 计算总计            
            $orgData = Db::table($db . '.vip_goods_order')
                ->field($field)
                ->where('status', 0)
                ->where('store_code', 'in', $store)
                ->where($timeWhere)
                ->find();
            $orgData['name'] = '总计';
            array_unshift($data, $orgData);
            array_multisort(array_column($data, 'achievement'), SORT_DESC, $data); // 业绩排序
        }
        // 批量返回数据
        $allData = [
            'pic' => $picData,   // 曲线图y轴 业绩
            'time' => $time,   // x轴 时间点
            'table' => $data   // 表格数据  包含总计
        ];

        webApi(200, 'ok', $allData);
    }

    /**
     * 员工业绩查询
     */
    public function staffAchievement()
    {
        $db = $this->db;
        // 选了就是选择的日期类型 没选择默认是天
        $type = input('type') ?? 'd';
        // 选择了就是选择的没选默认是false  false  当天日期
        $check = input('check') ?? false;
        // 只有session 的情况 如果存在就查询各个员工的业绩 不存在就查询管理机构的门店
        $store = session('info.store') ?? null;
        //查询当前登入人 session('info.staff')
        $operate = Db::table($db . '.vip_staff')->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['code'] == 'boss') {
            $boss_org = Db::table($db . '.vip_org')->field('code')->select();
            $orgs = implode(',', array_column($boss_org, 'code'));
            $ew = new ErpWhere($db, $orgs);
            $store = $ew->org()->store()->get();
            $store = $store . ', ';
            [$time, $picData] = $this->getPicData($db, $orgs, $type, $check);
        } else if ($operate['role'] == 0) {
            $orgs = $operate['admin_org_code'];
            $ew = new ErpWhere($db, $orgs);
            $store = $ew->org()->store()->get();
            $a_org = explode(',', $orgs);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $store = $store . ', ';
            }
            [$time, $picData] = $this->getPicData($db, $orgs, $type, $check);
        } else {
            $store = $operate['store_code'];
            [$time, $picData] = $this->getPicData($db, $store, $type, $check);
        }
        // 表格选择的时间处理
        $tableTime = $this->achieveTable($type, $check);
        $timeWhere[] = ['create_time', '>=', $tableTime[0]];
        $timeWhere[] = ['create_time', '<=', $tableTime[1]];
        //数据计算处理
        $field = 'round(ifnull(sum(real_pay), 0), 2) as achievement,
                count(code) as codeNumber,ifnull(sum(number), 0) as number, 
                round(ifnull(sum(real_pay) / count(code), 0), 2) as custUnitPrice,
                round(ifnull(sum(number) / count(code), 0), 2) as joint';
        //判断是否为空的情况
        $data = [];
        // 查询该门店下所有员工
        $staffData = Db::table($db . '.vip_staff')
            ->alias('v')
            ->leftJoin($this->db . '.vip_store vm', 'vm.code = v.store_code')
            ->field('v.code, v.name, ifnull(vm.name, "无门店") AS vmname')
            ->where('v.store_code', 'in', $store)
            ->where('v.status', 0)
            ->select();
        foreach ($staffData as $k => $v) {
            $find = Db::table($db . '.vip_goods_order')
                ->field($field)
                ->where('status', 0)
                ->where('operate_code', $v['code'])
                ->where($timeWhere)
                ->find();
            $find['name'] = '员工 - ' . $v['name'] . '(' . $v['vmname'] . ')';
            $data[] = $find;
            $total[] = $v['code'];
        }
        $totalStaff = implode(',', $total);
        // 总计  计算所有员工的业绩
        $staffAmount = Db::table($db . '.vip_goods_order')
            ->field($field)
            ->where('status', 0)
            ->where('operate_code', 'in', $totalStaff)
            ->where($timeWhere)
            ->find();
        $staffAmount['name'] = '总计';
        array_unshift($data, $staffAmount);
        array_multisort(array_column($data, 'achievement'), SORT_DESC, $data); // 业绩排序
        // 批量返回数据
        $allData = [
            'pic' => $picData,   // 曲线图y轴 业绩
            'time' => $time,   // x轴 时间点
            'table' => $data   // 表格数据  包含总计
        ];
        webApi(200, 'ok', $allData);
    }

    /**
     * 获取自己管理的组织机构及子机构
     */
    private function orgSubordinate($orgs)
    {
        $db = $this->db;
        // 为空就是自己
        if ($orgs == '') {
            $orgs = session('info.admin_org');
        }
        // 不为空就是子机构
        if ($orgs != '') {
            $codes = explode(',', $orgs);
            $orgInfo = Db::table($db . '.vip_org')->where('code', 'in', $orgs)->select();
            if (!empty($orgInfo)) {
                $where = '';
                $orgLength = count($orgInfo);
                for ($i = 0; $i < $orgLength; $i++) {
                    $where .= ' or path like "' . $orgInfo[$i]['path'] . $orgInfo[$i]['code'] . ',%"';
                }
                $where = substr(trim($where), 2);
                $orgData = Db::table($db . '.vip_org')->field('code')->where($where)->select();
                if (!empty($orgData)) $codes = array_merge($codes, array_column($orgData, 'code')); //$codes += array_column($orgData, 'code'); 错误写法 偶尔会出错
            }
            $orgs = array_unique($codes);
        }
        return $orgs;
    }

    /**
     * 封装数据的时间
     * $orgs = 门店或者组织的code
     * $type = 时间类型  默认是天  否则是月 
     * $check = 时间格式 
     */
    private function getPicData($db, $orgs, $type = 'd', $check = false)
    {
        switch ($type) {
            case 'm':       // 如果是月
                if (!$check) {  // 如果不是false
                    $check = date('Y-m');   // 当前月
                }
                // 获取前七个月时间戳 getBeforeSevenMonth  年月  getTimestamps转换为时间戳
                $months = $this->getTimestamps(Time::getBeforeSevenMonth($check));
                $keys = ['seven', 'six', 'five', 'four', 'three', 'two', 'one'];
                // array_combine 通过合并两个数组来创建一个新数组，其中的一个数组元素为键，另一个数组元素为值
                // 年月时间戳
                $timestamps = array_combine($keys, $months);
                $dates = $this->getDates('y-m', $timestamps);
                break;
            case 'd':   // 如果是天
                if (!$check) {
                    $today = strtotime(date('Y-m-d'));
                } else {
                    $today = strtotime($check) + 86400;
                }
                $timestamps = [
                    'seven' => [$today - 86400, $today],
                    'six' => [$today - 86400 * 2, $today - 86400],
                    'five' => [$today - 86400 * 3, $today - 86400 * 2],
                    'four' => [$today - 86400 * 4, $today - 86400 * 3],
                    'three' => [$today - 86400 * 5, $today - 86400 * 4],
                    'two' => [$today - 86400 * 6, $today - 86400 * 5],
                    'one' => [$today - 86400 * 7, $today - 86400 * 6]
                ];
                $dates = $this->getDates('m-d', $timestamps);
                break;
        }
        // 传递点击的组织或者门店
        $ew = new ErpWhere($db, $orgs);
        // 门店的数据
        $store = $ew->org()->store()->get();
        if ($store == "") {
            $store = 0;
        }
        //时间查询
        $time_one = Db::table($db . '.vip_goods_order')
            ->field('round(ifnull(sum(real_pay), 0), 2) as one')
            ->where('status', 0)
            ->where('create_time', '>=', $timestamps['one'][0])
            ->where('create_time', '<', $timestamps['one'][1])
            ->where('store_code', 'in', $store)
            ->find()['one'];
        $time_two = Db::table($db . '.vip_goods_order')
            ->field('round(ifnull(sum(real_pay), 0), 2) as two')
            ->where('status', 0)
            ->where('create_time', '>=', $timestamps['two'][0])
            ->where('create_time', '<', $timestamps['two'][1])
            ->where('store_code', 'in', $store)
            ->find()['two'];
        $time_three = Db::table($db . '.vip_goods_order')
            ->field('round(ifnull(sum(real_pay), 0), 2) as three')
            ->where('status', 0)
            ->where('create_time', '>=', $timestamps['three'][0])
            ->where('create_time', '<', $timestamps['three'][1])
            ->where('store_code', 'in', $store)
            ->find()['three'];
        $time_four = Db::table($db . '.vip_goods_order')
            ->field('round(ifnull(sum(real_pay), 0), 2) as four')
            ->where('status', 0)
            ->where('create_time', '>=', $timestamps['four'][0])
            ->where('create_time', '<', $timestamps['four'][1])
            ->where('store_code', 'in', $store)
            ->find()['four'];
        $time_five = Db::table($db . '.vip_goods_order')
            ->field('round(ifnull(sum(real_pay), 0), 2) as five')
            ->where('status', 0)
            ->where('create_time', '>=', $timestamps['five'][0])
            ->where('create_time', '<', $timestamps['five'][1])
            ->where('store_code', 'in', $store)
            ->find()['five'];
        $time_six = Db::table($db . '.vip_goods_order')
            ->field('round(ifnull(sum(real_pay), 0), 2) as six')
            ->where('status', 0)
            ->where('create_time', '>=', $timestamps['six'][0])
            ->where('create_time', '<', $timestamps['six'][1])
            ->where('store_code', 'in', $store)
            ->find()['six'];
        $time_seven = Db::table($db . '.vip_goods_order')
            ->field('round(ifnull(sum(real_pay), 0), 2) as seven')
            ->where('status', 0)
            ->where('create_time', '>=', $timestamps['seven'][0])
            ->where('create_time', '<', $timestamps['seven'][1])
            ->where('store_code', 'in', $store)
            ->find()['seven'];
        $data = [
            '0' => $time_one == null ? floatval(0) : $time_one,
            '1' => $time_two == null ? floatval(0) : $time_two,
            '2' => $time_three == null ? floatval(0) : $time_three,
            '3' => $time_four == null ? floatval(0) : $time_four,
            '4' => $time_five == null ? floatval(0) : $time_five,
            '5' => $time_six == null ? floatval(0) : $time_six,
            '6' => $time_seven == null ? floatval(0) : $time_seven
        ];
        return [$data, $dates];
    }

    /**
     *  mktime(0, 0, 0, $m, $d, $y), 今日开始的时间戳
     *  mktime(23, 59, 59, $m, $d, $y)  今日结束的时间戳
     */
    private function getTimestamps($dates)
    {
        // 拆分拼接成时间戳
        $timestamps = [];
        foreach ($dates as $v) {
            [$y, $m, $t] = explode('-', date('Y-m-t', strtotime($v['y'] . '-' . $v['m'])));
            array_push($timestamps, [
                // 时 分 秒 月  日  年  
                mktime(0, 0, 0, $m, 1, $y),
                mktime(23, 59, 59, $m, $t, $y)
            ]);
        }
        return $timestamps;
    }

    /**
     * 格式化时间戳
     */
    private function getDates($type, $timestamps)
    {
        return [
            date($type, $timestamps['one'][0]),
            date($type, $timestamps['two'][0]),
            date($type, $timestamps['three'][0]),
            date($type, $timestamps['four'][0]),
            date($type, $timestamps['five'][0]),
            date($type, $timestamps['six'][0]),
            date($type, $timestamps['seven'][0])
        ];
    }

    /** table
     * 查询当前组织下的门店的业绩()  或者就是该组织下 某一个门店的业绩(门店一定在组织里)  * 依据机构    统计某一天的  关联机构表  店铺表 和 订单表
     * $types 时间类型  日 月  自定义       如果选择了就是 选择的 日期 否则就当天 或者 当月的 
     * $ttime 时间 默认等于false  就是当天/月的  否则就是传入的
     * $end  自定义类型的末尾时间 直接拿传递的时间做开始时间 只需要获得末尾时间 默认没传递  没传递就返回false
     */
    private function achieveTable($type, $ttime = false, $end = false)
    {
        // 判断时间 类型 格式化时间
        switch ($type) {
            case 'm':       // 如果是月
                if (!$ttime) {  // 是false 
                    $ttime = date('Y-m');    // 当前月
                }
                // 转换为时间戳
                [$y, $m, $t] = explode('-', date('Y-m-t', strtotime($ttime)));
                return [
                    // 时 分 秒 月  日  年  
                    mktime(0, 0, 0, $m, 1, $y),
                    mktime(23, 59, 59, $m, $t, $y)
                ];
                break;
            case 'd':   // 如果是天
                if (!$ttime) {
                    $ttime = date('Y-m-d');  // 当前天
                }
                $timestamps = strtotime($ttime);
                return [$timestamps, $timestamps + 86400];
                break;
            case 'c':
                if (!$ttime || !$end) {
                    return false;
                }
                return [strtotime($ttime), strtotime($end)];
                break;
        }
    }
}
