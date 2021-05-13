<?php

namespace app\controller;

use think\Db;
use think\Controller;

/**
 * Author lhp
 * Date 2020/11/03    
 * Description  付费卡劵
 */
class PayCoupon extends Common 
{

    public function index()
    {
        $data = Db::table($this->db.'.vip_pay_coupon')
            ->field('card_name, card_brief, money, dis_money, card_photo, start_time, end_time, a_hsi, b_hsi, week')
            ->where('start_time', '<=', time())
            ->where('end_time', '>=', time())
            ->where('status', 0)
            ->select();
        foreach ($data as $k=>$v) {
            $data[$k]['inverted_time'] = ceil(($v['end_time'] - time())/86400);
            $data[$k]['weeks'] = false; 
            $data[$k]['hsi'] = false;
        }

        foreach ($data as $k=>$v) {
            $a_hsi = strtotime(date('Y-m-d').$v['a_hsi']);
            $b_hsi = strtotime(date('Y-m-d').$v['b_hsi']);
            if (time() > $a_hsi && time() < $b_hsi ) {  //判断是否符合几点到几点
                $data[$k]['hsi'] = true;
            } 
            
            if (!empty($v['week'])) {
                $week = explode(',',$v['week']); 
                foreach ($week as $val) {
                    if ($val === date('w')) { // 判断门店
                        $data[$k]['weeks'] = true;
                    }
                }
            }
        }

        foreach ($data as $k=>$v) { 
            if ($v['weeks'] == false || $v['hsi'] == false) {
                unset($data[$k]);
            }
        }
        
        webApi(200, 'ok', $data);
    }

}
