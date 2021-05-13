<?php

namespace app\controller;

use think\Db;
use think\Controller;
use image\Image;
use think\facade\Env;

/**
 * Author lhp
 * Date 2019/11/07
 * Description  商品列表
 */
class Goods extends Common 
{

    public function index()
    {
        $where = input('where');
        $data = Db::table($this->db. '.vip_goods')
            ->field('name, frenum as code, price, color, sizes, img')
            ->where('frenum', 'like', $where . '%')
            ->group('frenum, color')
            ->page(input('page'), input('limit'))
            ->select();

        $count = Db::table($this->db . '.vip_goods')
            // ->field('name, code, price, color, sizes, img')
            ->where('frenum', 'like', $where . '%')
            ->group('frenum, color')
            ->count();

        $data = [
            'data' => $data,
            'count' => $count
        ];
        webApi(200, 'ok', $data);
    }

    /**
     * 标签列表
     */
    public function labelIndex()
    {
        if (!empty(input('code'))) {
            $where[] = ['frenum', 'like', input('code'). '%'];
        } else {
            $where = true;
        }

        $data = Db::table($this->db . '.vip_goods')
            ->field('frenum as code, name, price, bar_code')
            ->where($where)
            ->page(input('page'), input('limit'))
            ->group('frenum')
            ->select();

        $count = Db::table($this->db . '.vip_goods')
            ->where($where)
            ->group('frenum')
            ->count();

        $data = [
            'data' => $data,
            'count' => $count
        ];
        webApi(200, 'ok', $data);
    }

    /**
     * 商品标签
     */
    public function label()
    {
        
        $data = Db::table($this->db . '.vip_goods_label')->field('name, code, type')->where('type', '扩展型')->where('status', 1)->select();
        webApi(200, 'ok', $data);
    }

    /**
     * 筛选中标签
     */
    public function labelInfoq()
    {
        $code = input('code');
        $data = Db::table($this->db . '.vip_goods_label_info')->field('info name, id')->where('label_code', $code)->select();
        webApi(200, 'ok', $data);
    }


    /**
     * 点击会员标签
     */
    public function labelInfo()
    {
        $data = Db::table($this->db . '.vip_goods_label_info')->field('id, info name, label_code')->where('label_code', input('label_code'))->select();

        $ext = Db::table($this->db . '.vip_goods_labels')->where('goods_code', input('code'))->where('label_code', input('label_code'))->select();

        if (!empty($ext) && !empty($data)) {
            foreach ($data as $k => $v) {
                $data[$k]['on'] = false;
                foreach ($ext as $val) {
                    if ($val['label_id'] == $v['id']) {
                        $data[$k]['on'] = true;
                    }
                }
            }
            unset($ext);
        }
        
        webApi(200, 'ok', $data);
    }

    /**
     * 点击将标签放入缓存
     */
    public function clickLabel()
    {
        $goods = Db::table($this->db. '.vip_goods')->where('frenum', input('code'))->select();
        foreach ($goods as $k=>$v) {
            $data = Db::table($this->db . '.vip_goods_labels')->where('goods_code', $v['bar_code'])->where('label_id', input('id'))->find();
            if (!empty($data)) {
                Db::table($this->db . '.vip_goods_labels')->where('goods_code', $v['bar_code'])->where('label_id', input('id'))->delete();
            } else {
                $label = [
                    'goods_code' => $v['bar_code'],
                    'label_code' => input('label_code'),
                    'label_name' => input('name'),
                    'label_id' => input('id'),
                ];
                Db::table($this->db . '.vip_goods_labels')->insert($label);
            }
        }
        webApi(200, 'ok');
    }

    /**
     * 编辑会员标签
     */
    public function editLabel()
    {
        webApi(200, '编辑成功');
    }


    // 查询商品的颜色
    public function yanse()
    {
        $data = Db::table($this->db.'.vip_goods_spec')->where('name', '颜色')->select();
        
        webApi(200, 'ok', $data);
    }


    // 查询尺码的方法
    public function chima()
    {
        $data = Db::table($this->db.'.vip_goods_spec')->where('name', '尺码')->select();
        
        webApi(200, 'ok', $data);
    }


    /**
     * 添加商品
     */
    public function goodsAdd()
    {
        
        // 获得表单提交的数据
        $data = [
            'code' => trim(input('frenum')),    //货号
            'frenum' => trim(input('frenum')),      //商品名称
            'name' => trim(input('name')),      //商品名称
            'price' => trim(input('price')),     // 商品价格
            'img' => trim(input('img'))
        ];
        if ($data == '') {
            webApi(400, '参数错误');
        }
        // // 验证场景
        // $result = $this->validate($data,'app\web\validate\v1\GoodsValidate.addGoods');
        // if(true !== $result  ){
        //     webApi(0,'error',0,$result);
        // }

        // 货号是否可重复待定
        // $getfrenum = Db::table($this->db.'.vip_goods')->where('code',input('frenum'))->find();
        // if (!empty($getfrenum)) {
        //     webApi(400,'货号已存在');
        // }

        $barCode = input('frenum');
    
        $color = input('color');
            
        //  缓存中的尺码
        $size = input('sizes');

        $result = [];
        if (empty($color) || empty($size)) {
            if (!empty($color)) {
                // 没有尺码
                foreach ($color as $k=>$v) {
                    $result[$k] = $data;
                    $result[$k]['color'] = $v;
                    $result[$k]['sizes'] = '';
                    $result[$k]['bar_code'] = $barCode.'-'.$v;
                }
            }
            if (!empty($size)) {
                // 没有颜色
                foreach ($size as $k=>$v) {
                    $result[$k] = $data;
                    $result[$k]['sizes'] = $v;
                    $result[$k]['color'] = '';
                    $result[$k]['bar_code'] = $barCode.'-'.$v;
                }
            }
            if (empty($color) && empty($size)) {
                // 颜色尺码都没有
                $result[0] = $data;
                $result[0]['color'] = '';
                $result[0]['sizes'] = '';
                $result[0]['bar_code'] = $barCode;
            }
        } else {
            // 颜色尺码都有
            $i = 0;
            foreach($color as $k=>$v){
                foreach ($size as $key=>$val) {
                    $result[$i] = $data;
                    $result[$i]['color'] = $v;
                    $result[$i]['sizes'] = $val;
                    $result[$i]['bar_code'] = $barCode.'-'.$v.'-'.$val;
                    $i++;
                }
            }
            unset($size);
            unset($barCode);
        }
        foreach ($result as $k=>$v) {
            if (!empty(Db::table($this->db.'.vip_goods')->field('id,bar_code')->where('bar_code', $v['bar_code'])->find())) {
                $msg = '';
                if ($v['color'] != '') {
                    $msg .= ' 颜色：'.$v['color'];
                }
                if ($v['sizes'] != '') {
                    $msg .= ' 尺码：'.$v['sizes'];
                }
                webApi(400, $msg.' 的商品已经存在,请检查后重新添加');
            }
        }
        unset($data);
        
        // 启动事务
        Db::startTrans();
        try {
            Db::table($this->db.'.vip_goods')->insertAll($result);
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $res = false;
        }
        if($res){
            webApi(200,'ok');
        }else{
            webApi(400,'添加失败');
        }

    }



}