<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 * Author lhp
 * Date 2019/08/20
 * Description  生日折扣
 */
class BirthdayDiscount extends Common
{

    public function index()
    {
        [$page, $limit, $type, $vtype] = [input('page'), input('limit'), input('type'), input('vtype')];
        [$staff, $store, $org] = [input('staff'), input('store'), input('org')];
        // type: today 今日   lately 最近   thisMonth  本月
        // vtype: unconsumed 未消费  consumption 有消费   returnVisit 已回访  noReturnVisit 未回访
        $where = "";
        if (!empty($staff)) {
            $where .= " and consultant_code = " . ' "' . $staff . '"';
        } else if (!empty($store)) {
            $where .= " and store_code = " . ' "' . $store . '"';
        } else if (!empty($org)) {
            $ew = new ErpWhere($this->db, $org);
            $orgWhere = $ew->org()->store()->get();
            $a_org = explode(',', $org);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $orgWhere = $orgWhere . ', ';
            }
            $pen = explode(",", $orgWhere);
            $vv = '';
            foreach ($pen as $av) {
                $vv .= "'" . $av . "',";
            }
            $aaa = trim($vv, ','); // trim 去掉字符串两边符号
            $where .= " and store_code in (" . $aaa . ")";
        }
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //门店限制
        if ($operate['code'] == 'boss') {
            $where .= "";
        } else if ($operate['role'] == 0) {
            $ew = new ErpWhere($this->db, $operate['admin_org_code']);
            $stores = $ew->org()->store()->get();
            $a_org = explode(',', $operate['admin_org_code']);
            if (in_array('ZZJG15459825114064', $a_org)) {
                $stores = $stores . ', ';
            }
            $pen = explode(",", $stores);
            $vv = '';
            foreach ($pen as $av) {
                $vv .= "'" . $av . "',";
            }
            $aaa = trim($vv, ','); // trim 去掉字符串两边符号
            $where .= " and store_code in (" . $aaa . ")";
        } else {
            $where .= "and consultant_code = " . ' "' . $operate['code'] . '"';
        }
        $month = date('m'); //获得当前月
        $day = date('d');   //获得当前日
        $latelyTimeM = date('m', time() + 7 * 24 * 3600); //今日往后推7天
        $latelyTimeD = date('d', time() + 7 * 24 * 3600); //今日往后推7天
        
        switch ($type) {
            case 'today': // 今日
                switch ($vtype) {
                    case 'unconsumed': // 未消费  
                        $query = Db::query("SELECT code, username, phone, birthdays, birthday ,total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE month(birthday) = " . $month . " and day(birthday) = " . $day . " and rfm_days > 30 " . $where);
                        $data = array_slice($query, ($page - 1) * $limit, $limit);
                        $count = count($query);
                        break;
                    case 'consumption': // 有消费
                        $query = Db::query("SELECT code, username, phone, birthdays, birthday ,total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE month(birthday) = " . $month . " and day(birthday) = " . $day . " and rfm_days <= 30 " . $where);
                        $data = array_slice($query, ($page - 1) * $limit, $limit);
                        $count = count($query);
                        break;
                    case 'returnVisit': // 已回访
                        $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE month(birthday) = " . $month . " and day(birthday) = " . $day . " and visit_g = 0 " . $where);
                        $data = array_slice($query, ($page - 1) * $limit, $limit);
                        $count = count($query);
                        break;
                    case 'noReturnVisit': //未回访
                        $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE month(birthday) = " . $month . " and day(birthday) = " . $day . " and visit_g > 0 " . $where);
                        $data = array_slice($query, ($page - 1) * $limit, $limit);
                        $count = count($query);
                        break;
                }
                break;
            case 'lately': // 最近
                switch ($vtype) {
                    case 'unconsumed': // 未消费

                        if ($latelyTimeM > $month) {
                            $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE rfm_days > 30 ". $where ." and   ((month(birthday) = " . $month . " and day(birthday) >= " . $day . ") OR  (month(birthday) = " . $latelyTimeM  . " and day(birthday) <= " . $latelyTimeD . ")) ");
                        } else if ($latelyTimeM == 01 && $month == 12) {
                            $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE rfm_days > 30 ". $where ." and  ((month(birthday) = " . $month . " and day(birthday) >= " . $day . ") OR  (month(birthday) = " . $latelyTimeM  . " and day(birthday) <= " . $latelyTimeD . ")) " );
                        } else if ($latelyTimeM == $month) {
                            $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE month(birthday)>= " . $month . " and month(birthday) <= " . $latelyTimeM  . " and day(birthday)>= " . $day  . " and day(birthday)<= " . $latelyTimeD . " and rfm_days > 30 " . $where);
                        }
                        $data = array_slice($query, ($page - 1) * $limit, $limit);
                        $count = count($query);
                        break;
                    case 'consumption': // 有消费
                        if ($latelyTimeM > $month) {
                            $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE rfm_days <= 30".$where." and ((month(birthday) = " . $month . " and day(birthday) >= " . $day . ") OR  (month(birthday) = " . $latelyTimeM  . " and day(birthday) <= " . $latelyTimeD . "))");
                        } else if ($latelyTimeM == 01 && $month == 12) {
                            $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE rfm_days <= 30".$where." and ((month(birthday) = " . $month . " and day(birthday) >= " . $day . ") OR  (month(birthday) = " . $latelyTimeM  . " and day(birthday) <= " . $latelyTimeD . "))");
                        } else if ($latelyTimeM == $month) {
                            $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE month(birthday)>= " . $month . " and month(birthday) <= " . $latelyTimeM  . " and day(birthday)>= " . $day  . " and day(birthday)<= " . $latelyTimeD . " and rfm_days <= 30 " . $where);
                        }
                        $data = array_slice($query, ($page - 1) * $limit, $limit);
                        $count = count($query);
                        break;
                    case 'returnVisit': // 已回访
                        if ($latelyTimeM > $month) {
                            $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE visit_g <= 7 ".$where." and ((month(birthday) = " . $month . " and day(birthday) >= " . $day . ") OR  (month(birthday) = " . $latelyTimeM  . " and day(birthday) <= " . $latelyTimeD . "))");
                        } else if ($latelyTimeM == 01 && $month == 12) {
                            $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE visit_g <= 7 ".$where." and ((month(birthday) = " . $month . " and day(birthday) >= " . $day . ") OR  (month(birthday) = " . $latelyTimeM  . " and day(birthday) <= " . $latelyTimeD . "))");
                        } else if ($latelyTimeM == $month) {
                            $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE month(birthday)>= " . $month . " and month(birthday) <= " . $latelyTimeM  . " and day(birthday)>= " . $day  . " and day(birthday)<= " . $latelyTimeD . " and visit_g <= 7 " . $where);
                        }
                        $data = array_slice($query, ($page - 1) * $limit, $limit);
                        $count = count($query);
                        break;
                    case 'noReturnVisit': //未回访
                        if ($latelyTimeM > $month) {
                            $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE visit_g > 7 ".$where." and ((month(birthday) = " . $month . " and day(birthday) >= " . $day . ") OR  (month(birthday) = " . $latelyTimeM  . " and day(birthday) <= " . $latelyTimeD . ") )");
                        } else if ($latelyTimeM == 01 && $month == 12) {
                            $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE visit_g > 7 ".$where." and ((month(birthday) = " . $month . " and day(birthday) >= " . $day . ") OR  (month(birthday) = " . $latelyTimeM  . " and day(birthday) <= " . $latelyTimeD . "))");
                        } else if ($latelyTimeM == $month) {
                            $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE month(birthday)>= " . $month . " and month(birthday) <= " . $latelyTimeM  . " and day(birthday)>= " . $day  . " and day(birthday)<= " . $latelyTimeD . " and visit_g > 7 " . $where);
                        }
                        $data = array_slice($query, ($page - 1) * $limit, $limit);
                        $count = count($query);
                        break;
                }
                break;
            case 'thisMonth': // 本月
                switch ($vtype) {
                    case 'unconsumed': // 未消费
                        $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE month(birthday) = " . $month . " and rfm_days > 30 " . $where);
                        $data = array_slice($query, ($page - 1) * $limit, $limit);
                        $count = count($query);
                        break;
                    case 'consumption': // 有消费
                        $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE month(birthday) = " . $month . " and rfm_days <= 30 " . $where);
                        $data = array_slice($query, ($page - 1) * $limit, $limit);
                        $count = count($query);
                        break;
                    case 'returnVisit': // 已回访
                        $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE month(birthday) = " . $month . " and visit_g <= 30 " . $where);
                        $data = array_slice($query, ($page - 1) * $limit, $limit);
                        $count = count($query);
                        break;
                    case 'noReturnVisit': //未回访
                        $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number FROM " . $this->db . ".view_birthday WHERE month(birthday) = " . $month . " and visit_g > 30 " . $where);
                        $data = array_slice($query, ($page - 1) * $limit, $limit);
                        $count = count($query);
                        break;
                }
                break;
        }
        $data = [
            'data' => $data,
            'count' => $count
        ];
        webApi(200, 'ok', $data);
    }

    /**
     * 生日有礼
     */
    public function barthday($company)
    {
        $write_off = Db::table($company . '.vip_activity_courtesy')
            ->where('activity_type', '生日有礼')
            ->where('start_time', '<=', time())
            ->where('end_time', '>=', time())
            ->where('status', 0)
            ->select();
        $month = date('m'); //获得当前月
        $day = date('d');   //获得当前日
        $es = new ErpWhere($company, "");
        if (!empty($write_off)) {
            foreach ($write_off as $key=>$val) {
                $latelyTimeM = date('m', time() + $val['advance_date'] * 24 * 3600);
                $latelyTimeD = date('d', time() + $val['advance_date'] * 24 * 3600);
                if (empty($val['store_all'])) {
                    if ($val['advance_date'] == 0) {
                        $query = Db::query("SELECT code, username, phone, birthday FROM " . $company . ".view_birthday WHERE month(birthday) = " . $month . " and day(birthday) = " . $day);
                    } else {
                        if ($latelyTimeM > $month) {
                            $query = Db::query("SELECT code, username, phone, birthday FROM " . $company . ".view_birthday WHERE (month(birthday) = " . $month . " and day(birthday) >= " . $day . ") OR  (month(birthday) = " . $latelyTimeM  . " and day(birthday) <= " . $latelyTimeD . ")");
                        } else if ($latelyTimeM == 01 && $month == 12) {
                            $query = Db::query("SELECT code, username, phone, birthday FROM " . $company . ".view_birthday WHERE (month(birthday) = " . $month . " and day(birthday) >= " . $day . ") OR  (month(birthday) = " . $latelyTimeM  . " and day(birthday) <= " . $latelyTimeD . ")");
                        } else if ($latelyTimeM == $month) {
                            $query = Db::query("SELECT code, username, phone, birthday FROM " . $company . ".view_birthday WHERE month(birthday)>= " . $month . " and month(birthday) <= " . $latelyTimeM  . " and day(birthday)>= " . $day  . " and day(birthday)<= " . $latelyTimeD);
                        }
                        $query = Db::query("SELECT code, username, phone, birthday FROM " . $company . ".view_birthday WHERE month(birthday) = " . $latelyTimeM . " and day(birthday) = " . $latelyTimeD);
                    }
                } else {
                    $arr = explode(",", $val['store_all']);;
                    $vv = '';
                    foreach ($arr as $av) {
                        $vv .= "'" . $av . "',";
                    }
                    $aaa = trim($vv, ','); // trim 去掉字符串两边符号
                    if ($val['advance_date'] == 0) {
                        $query = Db::query("SELECT code, username, phone, birthday FROM " . $company . ".view_birthday WHERE month(birthday) = " . $month . " and day(birthday) = " . $day. " and store_code in (" . $aaa . ")");
                    } else {
                        if ($latelyTimeM > $month) {
                            $query = Db::query("SELECT code, username, phone, birthday FROM " . $company . ".view_birthday WHERE (month(birthday) = "  . $month . " and day(birthday) >= " . $day . ") OR  (month(birthday) = " . $latelyTimeM  . " and day(birthday) <= " . $latelyTimeD . ") and store_code in (" . $aaa . ")");
                        } else if ($latelyTimeM == 01 && $month == 12) {
                            $query = Db::query("SELECT code, username, phone, birthday FROM " . $company . ".view_birthday WHERE (month(birthday) = " . $month . " and day(birthday) >= " . $day . ") OR  (month(birthday) = " . $latelyTimeM  . " and day(birthday) <= " . $latelyTimeD . ") and store_code in (" . $aaa . ")");
                        } else if ($latelyTimeM == $month) {
                            $query = Db::query("SELECT code, username, phone, birthday FROM " . $company . ".view_birthday WHERE month(birthday)>= " . $month . " and month(birthday) <= " . $latelyTimeM  . " and day(birthday)>= " . $day  . " and day(birthday)<= " . $latelyTimeD ." and store_code in (" . $aaa . ")");
                        }
                        $query = Db::query("SELECT code, username, phone, birthday FROM " . $company . ".view_birthday WHERE month(birthday) = " . $latelyTimeM . " and day(birthday) = " . $latelyTimeD ." and store_code in (" . $aaa . ")");
                    }
                    unset($arr, $vv, $aaa);
                }

                $poupon = Db::table($company . '.vip_agive_coupon')->where('code', $val['coupon_code'])->select();
                if (!empty($query)) {
                    foreach ($query as $v) {
                        $vip = Db::table($company . '.vip_viplist')->where('code', $v['code'])->find();
                        if (!empty($val['integral'])) {
                            $integral_jilu = [
                                'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                                'vip_code' => $v['code'],
                                'vip_name' => $v['username'],
                                'integral' => $val['integral'],
                                'road' => '生日有礼赠送',
                                'create_time' => time(),
                            ];
                            Db::table($company . '.vip_flow_integral')->insert($integral_jilu);
                            Db::table($company . '.vip_viplist')->where('code', $v['code'])->setInc('residual_integral', $val['integral']); // 用户的剩余总积分加
                            Db::table($company . '.vip_viplist')->where('code', $v['code'])->setInc('total_integral', $val['integral']); // 用户的总积分加
                            $es->integral_promotes($val['integral'], $vip);
                        }
                        if (!empty($val['stored_value'])) {
                            $value_jilu = [
                                'documents' => 'CZLS' . str_replace('.', '', microtime(1)),
                                'vip_code' => $vip['code'],
                                'vip_name' => $vip['username'],
                                'stored_value' => $val['stored_value'],
                                'road' => '生日有礼赠送',
                                'create_time' => time(),
                            ];
                            Db::table($company . '.vip_stored_value')->insert($value_jilu);
                            Db::table($company . '.vip_viplist')->where('code', $v['code'])->setInc('residual_value', $val['stored_value']); // 用户的剩余储值加
                            Db::table($company . '.vip_viplist')->where('code', $v['code'])->setInc('total_value', $val['stored_value']); // 用户的总储值加
                            $es->value_promotes($val['stored_value'], $vip);
                        }
                        if (!empty($poupon)) {
                            foreach ($poupon as $pon) {
                                for ($i = 0; $i < $pon['coupon_number']; $i++) {
                                    $es->couponAdd($pon['card_type'], $pon['coupon_code'], $v['code'], '生日有礼赠送');
                                }
                            }
                        }
                    }
                    unset($vip);
                }
                unset($query, $latelyTimeM, $latelyTimeD, $integral_jilu, $integral_jilu, $poupon);
            }
        }
        webApi(200, 'ok');
    }

    /**
     * 生日提醒
     */
    public function bir_remind()
    {
        $staff = input('staff');
        if (empty($staff)) {
            webApi(400, '缺少参数');
        }
        $where = "consultant_code = " . ' "' . $staff . '"';
        $data = Db::table($this->db . '.vip_birthday_remind')->select();
        foreach ($data as $k => $v) {
            $latelyTimeM = date('m', time() + $v['number'] * 24 * 3600);
            $latelyTimeD = date('d', time() + $v['number'] * 24 * 3600);
            $query = Db::query("SELECT code, username, phone, birthdays, birthday, total_consumption,
                                level_code, img, consumption_times, rfm_days, visit_g, consumption_number,
                                store_code, consultant_code
                                FROM " . $this->db . ".view_viplist WHERE " . $where . " and month(birthday) = "
                . $latelyTimeM . " and day(birthday) = " . $latelyTimeD);
            $data[$k]['count'] = count($query);
        }
        webApi(200, 'ok', $data);
    }

    public function bir_select()
    {
        [$page, $limit, $staff] = [input('page'), input('limit'), input('staff')];
        if (empty($staff)) {
            webApi(400, '缺少参数');
        }
        $where = "consultant_code = " . ' "' . $staff . '"';
        $latelyTimeM = date('m', time() + input('number') * 24 * 3600);
        $latelyTimeD = date('d', time() + input('number') * 24 * 3600);
        $query = Db::query("SELECT code, username, phone, birthdays, birthday , total_consumption, level_code, img, consumption_times, rfm_days, visit_g, consumption_number,store_code FROM " . $this->db . ".view_viplist WHERE " . $where . " and month(birthday) = " . $latelyTimeM . " and day(birthday) = " . $latelyTimeD);
        $data = array_slice($query, ($page - 1) * $limit, $limit);
        $count = count($query);
        unset($query);
        $data = [
            'data' => $data,
            'count' => $count
        ];
        webApi(200, 'ok', $data);
    }
}
