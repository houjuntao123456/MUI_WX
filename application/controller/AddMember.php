<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;

/**
 * Author lhp
 * Date 2019/2/19
 * Description  新建会员
 */
class AddMember extends Common
{

    public function add()
    {
        $data = [
            'code' => input('code'), // 卡号
            'username' => input('username'), // 姓名
            'sex' => input('sex'), // 性别
            'phone' => input('phone'), // 手机号
            'birthday' => strtotime(input('birthday')), // 生日
            'calendar' => input('calendar'), // 生日类型
            'area' => input('area'), // 地区
            'address' => input('address'), // 详细地址
            'store_code' => input('store_code'), //所属门店
            'vvip' => 1, //是否是微会员
            'vvip_time' => time(), // 微会员登记时间
            'date_registration' => time(), //登记时间
            'openid' => input('openId')
        ];

        //卡号是否存在
        $cardrepeat = Db::table($this->db . '.vip_viplist')->where('code', $data['code'])->find();
        if ($cardrepeat) {
            webApi(400, '卡号已存在!');
        }

        //手机号是否存在
        $phonerepeat = Db::table($this->db . '.vip_viplist')->where('phone', $data['phone'])->find();
        if ($phonerepeat) {
            webApi(400, '手机号码已存在!');
        }

        $res = Db::table($this->db . '.vip_viplist')->insert($data);

        if ($res) {
            webApi(200, '成功');
        } else {
            webApi(400, '失败');
        }
    }

    /**
     * 会员注册时查询转介绍人
     */
    public function introducer()
    {
        $where[] = ['username|phone|code', 'like', input('like') . '%'];

        $data = Db::table($this->db . '.vip_viplist')->field('username, code, phone')->where($where)->select();

        webApi(200, 'ok', $data);
    }

    /**
     * 会员注册
     */
    public function register()
    {
        $rfmTime = Db::table($this->db . '.vip_vippromote')->field('introduction_time')->find();
        $data = [
            'username' => input('username'), // 姓名
            'code' => input('phone'), // 会员卡号
            'sex' => input('sex'), // 性别
            'phone' => input('phone'), // 手机号
            'store_code' => input('store_code'), //所属门店
            'consultant_code' => input('staff_code'), //导购
            'birthday' => strtotime(input('birthday')), // 生日
            'vvip' => 1, //是否是微会员
            'vvip_time' => time(), // 微会员登记时间
            'introducer_name' => input('introducer_name'), //转介绍人姓名
            'introducer_code' => input('introducer_code'), // 转介绍人卡号
            'img' => input('img'), // 头像
            'date_registration' => time(), //登记时间
            'openid' => input('openId')
        ];
        //手机号是否存在
        $phonerepeat = Db::table($this->db . '.vip_viplist')->where('phone', $data['phone'])->find();
        if ($phonerepeat) {
            webApi(400, '手机号码已存在!');
        }
        $introducer = [
            'lnt_name' => input('introducer_name'), // 介绍人姓名
            'lnt_code' => input('introducer_code'), // 介绍人卡号
            'rsid_name' => input('username'), // 被介绍人姓名
            'rsid_code' => input('phone'), // 被介绍人卡号
            'lnttime' => time(), // 时间
        ];
        if (!empty(input('introducer_code'))) {
            $intMen = Db::table($this->db . '.vip_viplevel')
                ->alias('l')
                ->join($this->db . '.vip_viplist v', 'v.level_code = l.code')
                ->field('l.uid')
                ->where('v.code', input('introducer_code'))
                ->find();
            if (!empty($intMen)) {
                $levelSort = $intMen['uid'];
            } else {
                $levelSort = -1;
            }
            $promotes = Db::table($this->db . '.vip_vippromote')
                ->alias('a')
                ->leftJoin($this->db . '.vip_viplevel l', 'l.code = a.levelname')
                ->field('a.*,l.code,l.username,l.uid')
                ->where('l.uid', '>', $levelSort)
                ->select();
            if (!empty($promotes)) {
                foreach ($promotes as $k => $v) {
                    $promotes[$k]['js'] = false;
                }
                //统计转介绍人数
                $isempty = Db::table($this->db . '.vip_introducer')->where('lnttime', '>=', time() - (86400 * $rfmTime['introduction_time']))->where('lnt_code', input('introducer_code'))->count();
                unset($rfmTime);
                $isempty += 1;
                foreach ($promotes as $k => $v) {
                    //判断转介绍数
                    if ($isempty >= $v['introduction']) {
                        $promotes[$k]['js'] = true;
                    }
                }
                foreach ($promotes as $k => $v) {
                    if ($v['js'] == false) {
                        unset($promotes[$k]);
                    }
                }
                if (!empty($promotes)) {
                    rsort($promotes);
                    $nextLevel = ['level_code' => $promotes[0]['levelname']];
                    unset($promotes);
                }
            }
        }
        //执行事务
        Db::startTrans();
        try {
            Db::table($this->db . '.vip_viplist')->insert($data);
            $ew = new ErpWhere($this->db, '');
            if (!empty(input('introducer_code'))) {  // 转介绍记录
                Db::table($this->db . '.vip_introducer')->insert($introducer);
                $int_vip = Db::table($this->db . '.vip_viplist')->where('code', input('introducer_code'))->find();
                $write_off_W = Db::table($this->db . '.vip_activity_courtesy')  // 转介绍送礼
                    ->where('activity_type', '转介绍有礼')
                    ->where('start_time', '<=', time())
                    ->where('end_time', '>=', time())
                    ->where('level_all', 'like', '%'.$int_vip['level_code'].'%')
                    ->where('status', 0)
                    ->select();
                // $introducer_store = false;
                if (!empty($write_off_W)) {
                    // if (empty($write_off_introducer['store_all'])) {
                    //     $introducer_store = true;
                    // } else {
                    //     $introducer_store_all = explode(',', $write_off_introducer['store_all']);
                    //     foreach ($introducer_store_all as $val) {
                    //         if ($val == input('store_code')) { // 判断门店
                    //             $introducer_store = true;
                    //         }
                    //     }
                    // }
                    foreach ($write_off_W as $k=>$v) {
                        $write_off_W[$k]['store_w'] = false;
                    }
                    foreach ($write_off_W as $k=>$v) {
                        $fstore_all = explode(',',$v['store_all']); 
                        if (count($fstore_all) == 1 && empty($fstore_all[0])) {
                            $write_off_W[$k]['store_w'] = true;
                        } else {
                            foreach ($fstore_all as $val) {
                                if ($val === input('store_code')) { // 判断门店
                                    $write_off_W[$k]['store_w'] = true;
                                }
                            }
                        }
                    }
                    foreach ($write_off_W as $k=>$v) {
                        if ($v['store_w'] == false) {
                            unset($write_off_W[$k]);
                        }
                    }
                    if (count($write_off_W) > 1) {
                        foreach ($write_off_W as $k=>$v) {
                            if (empty($v['store_all'])) {
                                unset($write_off_W[$k]);
                            }
                        }
                    }
                    sort($write_off_W);
                    if (!empty($write_off_W)) {
                        $write_off_introducer = $write_off_W[0];
                    } else {
                        $write_off_introducer = [];
                    }
                    if (!empty($write_off_introducer)) {
                        $int_vip_count = Db::table($this->db . '.vip_introducer')->where('lnt_code', input('introducer_code'))->where('lnttime', '>=', $write_off_introducer['start_time'])->where('lnttime', '<=', $write_off_introducer['end_time'])->count();
                        if ($int_vip_count == $write_off_introducer['advance_date']) {  // 转介绍人数大于等于规则人数
                            if (!empty($write_off_introducer['integral'])) {
                                Db::table($this->db . '.vip_viplist')->where('code', $int_vip['code'])->setInc('residual_integral', $write_off_introducer['integral']); // 用户的剩余总积分加
                                Db::table($this->db . '.vip_viplist')->where('code', $int_vip['code'])->setInc('total_integral', $write_off_introducer['integral']); // 用户的总积分加
                                $integral_jilu_introducer = [
                                    'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                                    'vip_code' => $int_vip['code'],
                                    'vip_name' => $int_vip['username'],
                                    'integral' => $write_off_introducer['integral'],
                                    'road' => '转介绍有礼赠送',
                                    'create_time' => time(),
                                ];
                                Db::table($this->db . '.vip_flow_integral')->insert($integral_jilu_introducer);
                                unset($integral_jilu_introducer);
                                $ew->integral_promotes($write_off_introducer['integral'], $int_vip);
                            }
                            if (!empty($write_off_introducer['stored_value'])) {
                                Db::table($this->db . '.vip_viplist')->where('code', $int_vip['code'])->setInc('residual_value', $write_off_introducer['stored_value']); // 用户的剩余储值加
                                Db::table($this->db . '.vip_viplist')->where('code', $int_vip['code'])->setInc('total_value', $write_off_introducer['stored_value']); // 用户的总储值加
                                $value_jilu_introducer = [
                                    'documents' => 'CZLS' . str_replace('.', '', microtime(1)),
                                    'vip_code' => $int_vip['code'],
                                    'vip_name' => $int_vip['username'],
                                    'stored_value' => $write_off_introducer['stored_value'],
                                    'road' => '转介绍有礼赠送',
                                    'create_time' => time(),
                                ];
                                Db::table($this->db . '.vip_stored_value')->insert($value_jilu_introducer);
                                $ew->value_promotes($write_off_introducer['stored_value'], $int_vip);
                                unset($value_jilu_introducer);
                            }
                            if (!empty($write_off_introducer['coupon_code'])) {
                                $poupon_introducer = Db::table($this->db . '.vip_agive_coupon')->where('code', $write_off_introducer['coupon_code'])->select();
                                if (!empty($poupon_introducer)) {
                                    foreach ($poupon_introducer as $val) {
                                        for ($i = 0; $i < $val['coupon_number']; $i++) {
                                            $ew->couponAdd($val['card_type'], $val['coupon_code'], $int_vip['code'], '转介绍有礼赠送');
                                        }
                                    }
                                }
                                unset($poupon_introducer);
                            }
                            
                        }
                    }
                }
                unset($write_off_introducer);
            }
            $write_off_k = Db::table($this->db . '.vip_activity_courtesy')
                ->where('activity_type', '开卡有礼')
                ->where('start_time', '<=', time())
                ->where('end_time', '>=', time())
                ->where('status', 0)
                ->select();
            // $store = false;
            if (!empty($write_off_k)) {
                // if (empty($write_off['store_all'])) {
                //     $store = true;
                // } else {
                //     $store_all = explode(',', $write_off['store_all']);
                //     foreach ($store_all as $val) {
                //         if ($val == input('store_code')) { // 判断门店
                //             $store = true;
                //         }
                //     }
                // }
                foreach ($write_off_k as $k=>$v) {
                    $write_off_k[$k]['store_w'] = false;
                }
                foreach ($write_off_k as $k=>$v) {
                    $fstore_all = explode(',',$v['store_all']); 
                    if (count($fstore_all) == 1 && empty($fstore_all[0])) {
                        $write_off_k[$k]['store_w'] = true;
                    } else {
                        foreach ($fstore_all as $val) {
                            if ($val === input('store_code')) { // 判断门店
                                $write_off_k[$k]['store_w'] = true;
                            }
                        }
                    }
                }
                foreach ($write_off_k as $k=>$v) {
                    if ($v['store_w'] == false) {
                        unset($write_off_k[$k]);
                    }
                }
                if (count($write_off_k) > 1) {
                    foreach ($write_off_k as $k=>$v) {
                        if (empty($v['store_all'])) {
                            unset($write_off_k[$k]);
                        }
                    }
                }
                sort($write_off_k);
                if (!empty($write_off_k)) {
                    $write_off_ka = $write_off_k[0];
                } else {
                    $write_off_ka = [];
                }
                if (!empty($write_off_ka['integral'])) {
                    Db::table($this->db . '.vip_viplist')->where('code', input('phone'))->setInc('residual_integral', $write_off_ka['integral']); // 用户的剩余总积分加
                    Db::table($this->db . '.vip_viplist')->where('code', input('phone'))->setInc('total_integral', $write_off_ka['integral']); // 用户的总积分加
                    $kaika = Db::table($this->db . '.vip_viplist')->where('code', input('phone'))->find();
                    $integral_jilu = [
                        'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                        'vip_code' => input('phone'),
                        'vip_name' => input('username'),
                        'integral' => $write_off_ka['integral'],
                        'road' => '开卡有礼赠送',
                        'create_time' => time(),
                    ];
                    Db::table($this->db . '.vip_flow_integral')->insert($integral_jilu);
                    $ew->integral_promotes($write_off_ka['integral'], $kaika);
                    unset($kaika, $integral_jilu);
                }
                if (!empty($write_off_ka['coupon_code'])) {
                    $poupon = Db::table($this->db . '.vip_agive_coupon')->where('code', $write_off_ka['coupon_code'])->select();
                    if (!empty($poupon)) {
                        foreach ($poupon as $pon) {
                            for ($i = 0; $i < $pon['coupon_number']; $i++) {
                                $ew->couponAdd($pon['card_type'], $pon['coupon_code'], input('phone'), '开卡有礼赠送');
                            }
                        }
                    }
                    unset($poupon);
                }
            unset($write_off_ka);
            }
            if (isset($nextLevel)) {
                $promote_insert = [
                    'vip_code' => $int_vip['code'],
                    'vip_name' => $int_vip['username'],
                    'before_level' => $int_vip['level_code'],
                    'after_level' => $nextLevel['level_code'],
                    'reason' => '转介绍晋升',
                    'create_time' => time()
                ];
                Db::table($this->db . '.vip_promote')->insert($promote_insert);
                Db::table($this->db . '.vip_viplist')->where('code', input('introducer_code'))->update($nextLevel);
            }
            unset($nextLevel);
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $res = false;
        }
        if ($res) {
            webApi(200, '成功');
        } else {
            webApi(400, '失败');
        }
    }
}
