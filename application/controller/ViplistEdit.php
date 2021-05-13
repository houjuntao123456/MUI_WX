<?php

namespace app\controller;

use think\Db;
use think\Controller;

/**
 * Author lxy
 * Date 2019/06/14
 * Description 修改会员资料
 */
class ViplistEdit extends Common
{
    /**
     * 修改会员资料
     */
    public function vipEdit()
    {
        //获取卡号
        $card = input('card') ?? null;
        if ($card == null) {
            webApi(400, '会员卡号错误!');
        }
        //修改的数据
        $data = [
            'username' => input('username'),
            'sex' => input('sex'),
            'phone' => input('contact'),
            'birthday' => strtotime(input('birthday')),
            'area' => input('area'),
            'address' => input('address'),
            'consultant_code' => input('staff_code')
        ];
        
        //执行修改
        $res = Db::table($this->db . '.vip_viplist')->where('code', $card)->update($data);
        //清除变量
        unset($data, $card);
        //返回数据进行判断
        if ($res) {
            webApi(200, '修改成功!');
        } else {
            webApi(400, '资料未修改');
        }
    }

    //形象顾问
    public function consultant()
    {
        //获取门店
        $store = input('store_code');
        //修改的数据
        $staff = Db::table($this->db . '.vip_staff')->where('store_code', $store)->where('status', 0)->select();
        //清除变量
        unset($store);
        //返回数据
        webApi(200, 'ok', $staff);
    }
}
