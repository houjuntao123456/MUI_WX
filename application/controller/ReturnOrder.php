<?php

namespace app\controller;

use think\Db;
use think\Controller;

/**
 * Author lhp
 * Date 2019/12/17
 * Description 返单计划
 */
class ReturnOrder extends Common
{
    public function index()
    {
        [$staff, $store, $startTime, $endTime, $summary] = [input('staff'), input('store'), input('startTime'), input('endTime'), input('summary')];
        cache('middle_'.session('info.staff'), null);
        $where = '1=1';
        if (!empty($staff)) {
            $ff = explode(",",$staff);
            $vv = '';
            foreach ($ff as $av) {
                $vv .= "'" . $av . "',";
            }
            $aaa = trim($vv, ','); // trim 去掉字符串两边符号
            $where .= " and shopping_code in (" . $aaa . ")";
        } else if (!empty($store)) {
            $ff = explode(",",$store);
            $vv = '';
            foreach ($ff as $av) {
                $vv .= "'" . $av . "',";
            }
            $bbb = trim($vv, ','); // trim 去掉字符串两边符号
            $where .= " and store_code in (" . $bbb . ")";
        } 
        $where .= ' and execution_time > '. strtotime($startTime);
        $where .= ' and execution_time < '. strtotime($endTime);
        if (!empty($summary)) {
            $where .= ' and summary = ' . $summary;
        }
        $data = Db::table($this->db.'.vip_retuen_order')
                ->field('id, code, execution_time, shopping_code, summary, remarks, shopping_name, creation_time, modification_time, vip_name, vip_code, store_code')
                ->where($where)
                // ->fetchSql(true)
                ->order('execution_time', 'desc')  // 安照执行时间降序（从大到小）
                ->page(input('page'), input('limit'))
                ->select();
        if (!empty($data)) {
            foreach ($data as $k=>$v) {
                $data[$k]['execution_time'] = date('Y-m-d h:i:s', $v['execution_time']);
                $data[$k]['creation_time'] = date('Y-m-d h:i:s', $v['creation_time']);
                $data[$k]['modification_time'] = date('Y-m-d h:i:s', $v['modification_time']);
                $Number = Db::table($this->db.'.vip_return_order_middle')->where('order_code', $v['code'])->count();
                $money = Db::table($this->db.'.vip_return_order_middle')->where('order_code', $v['code'])->sum('price');
                $order = Db::table($this->db.'.vip_goods_order')->where('vip_code', $v['vip_code'])->where('create_time','>', $v['execution_time'])->select();
                $DealMoney = 0;
                $DealNumber = 0;
                if (!empty($order)) {
                    foreach ($order as $v) {
                        $DealMoney += $v['real_pay'];
                        $DealNumber += $v['number'];
                    }
                }
                
                $data[$k]['number'] = $Number;  // 搭配件数
                $data[$k]['money'] = number_format($money, 2);  //搭配金额
                $data[$k]['DealMoney'] = number_format($DealMoney, 2); //成交金额
                $data[$k]['DealNumber'] = $DealNumber;   // 件数百分比
                if ($DealNumber == 0 || $Number == 0) {  //金额百分比
                    $data[$k]['countNumber'] = 0.00 .'%';
                } else {
                    $data[$k]['countNumber'] = number_format(($DealNumber / $Number) * 100, 2) .'%';
                }
                if ($DealMoney == 0 || $money == 0) {  //成交件数
                    $data[$k]['countMoney'] = 0 .'%';
                } else {
                    $data[$k]['countMoney'] = number_format(($DealMoney / $money) * 100, 2) .'%';
                }
            }
        }
        
        $count = Db::table($this->db.'.vip_retuen_order')
                ->where($where)
                ->count();
        $summary = Db::table($this->db.'.vip_retuen_order')
                ->where($where)
                ->where('summary', 2)
                ->count();
        if (empty($count) || empty($summary)) {
            $vcount = '0%';
        } else {
            $vcount = number_format(($summary / $count ) * 100, 2) .'%';
        }
        $data = [
            'data' => $data,
            'count' => $count,
            'proportion' => $vcount,
            'orturn_sum' => $summary
        ];
        webApi(200, 'ok', $data);
    }

    /**
     * 将商品先加入到缓存中
     */
    public function addCache()
    {
        $cache = [
            'delete_id' => 'del' . str_replace('.', '', microtime(1)),
            'goods_name' => input('goods_name'), 
            'goods_code' => input('goods_code'),
            'programme' => input('programme'),
            'goods_img' => input('goods_img'),
            'price' =>  input('price')
        ];

        if (cache('middle_'.session('info.staff'))) {
            $data = cache('middle_'.session('info.staff'));
        } else {
            $data = [];
        }
        array_push($data, $cache);
                
        $res = cache('middle_'.session('info.staff'), $data, 3600);

        if ($res) {
            webApi(200, 'ok', $cache);
        } else {
            webApi(400, '失败');
        }
    }

    /**
     * 新建返单赋值商品用
     */
    public function addOrderCache()
    {
        $data = cache('middle_'.session('info.staff'));
        if ($data != false) {
            webApi(200, 'ok', $data);
        } else {
            webApi(200, 'ok', []);
        }
    }

    public function ClearCache()
    {
        cache('middle_'.session('info.staff'), null);
        webApi(200, 'ok');
    }

    /**
     * 删除缓存中的商品
     */
    public function delCache()
    {
        $id = input('id') ?? null;
        $datacache = cache('middle_'.session('info.staff'));
        if ($datacache == false) {
            webApi(400, '失败');
        } else{
            if (count($datacache) > 1) {
                foreach ($datacache as $k => $v) {
                    if ($id == $v['delete_id']) {
                        unset($datacache[$k]);
                    }
                }
                sort($datacache);
                cache('middle_'.session('info.staff'), $datacache, 3600);
            } else {
                cache('middle_'.session('info.staff'), null);
            }
            unset($id);
            webApi(200, '删除成功!',$datacache);
        }
        
    }

    /**
     * 新建返单
     */
    public function addOrder()
    {
        
        $dataCache = cache('middle_'.session('info.staff'));
        if (empty(input('shopping_code'))) {
            webApi(400, '未选择导购');
        }
        $store = Db::table($this->db.'.vip_staff')->field('store_code')->where('code', input('shopping_code'))->find();
        if ($store['store_code']) {
            $storeName = Db::table($this->db. '.vip_store')->field('name')->where('code', $store['store_code'])->find();
        } else {
            $storeName['name'] = '';
        }
        $data = [  //vip_retuen_order
            'code' => 'Order' . str_replace('.', '', microtime(1)),
            'vip_code' => input('vip_code'),
            'vip_name' => input('vip_name'),
            'execution_time' => strtotime(input('execution_time')),
            'shopping_code' => input('shopping_code'),  //导购
            'shopping_name' => input('shopping_name'),
            'store_code' => $store['store_code'],
            'store_name' => $storeName['name'],
            // 'core' => input('core'),            //核心卖点
            // 'collocation' => input('collocation'),      //整体搭配
            // 'top' => input('top'),      
            // 'problem' => input('problem'),      //问题
            // 'talking_skill' => input('talking_skill'),      //话术
            'creation_time' => time()
        ];
        
        $programme = input('programme_arr');
        
        if (isset($programme)) {
            foreach ($programme as $k=>$v) {
                $programme[$k]['retuen_code'] = $data['code'];
                $programme[$k]['programme'] = $v['id'];
                unset($programme[$k]['name'], $programme[$k]['img'],$programme[$k]['id']);
            }
            Db::table($this->db.'.vip_retuen_order_vice')->insertAll($programme);
        }
        unset($programme);
        $res = Db::table($this->db.'.vip_retuen_order')->insert($data);
        if ($res) {
            if ($dataCache != false) {
                foreach ($dataCache as $v) {
                    $middleData = [  //vip_return_order_middle
                        'order_code' => $data['code'],
                        'goods_name' => $v['goods_name'],
                        'programme' => $v['programme'],
                        'goods_code' => $v['goods_code'],
                        'goods_img' => $v['goods_img'],
                        'price' => $v['price']
                    ];
                    Db::table($this->db.'.vip_return_order_middle')->insert($middleData);
                }
            }
            cache('middle_'.session('info.staff'), null);
            webApi(200, '成功');
        } else {
            webApi(400, '失败');
        }
    }

    /**
     * 查看返单中的商品
     */
    public function SeeOrder()
    { 
        $order_code = Db::table($this->db. '.vip_retuen_order')->where('code', input('code'))->find();
        $order = Db::table($this->db.'.vip_return_order_middle')->where('order_code', input('code'))->select();
        $retuen = Db::table($this->db.'.vip_retuen_order_vice')->where('retuen_code', input('code'))->select();
        if (empty($order_code) && empty($order) && empty($retuen)) {
            $data = [
                'order' => [],
                'goods' => [],
                'retuen' => []
            ];
            webApi(200, 'ok', $data);
        } else {
            if (!empty($order)) {
                foreach ($order as $k=>$v) {
                    $cache = [
                        'delete_id' => 'del' . str_replace('.', '', microtime(1)),
                        'goods_name' => $v['goods_name'], 
                        'goods_code' => $v['goods_code'],
                        'programme' => $v['programme'],
                        'goods_img' => $v['goods_img'],
                        'price' =>  $v['price']
                    ];
                    if (cache('middle_'.session('info.staff'))) {
                        $data = cache('middle_'.session('info.staff'));
                    } else {
                        $data = [];
                    }
                    array_push($data, $cache);
                        
                    cache('middle_'.session('info.staff'), $data, 3600);
                }
                
            }
            if (!empty($order_code)) {
                $order_code['execution_time'] = date('Y-m-d h:i:s', $order_code['execution_time']);
            }
            if (!empty($retuen)) {
                foreach ($retuen as $k=>$v) {
                    $retuen[$k]['img'] = [];
                }
            }
            if (cache('middle_'.session('info.staff')) == false) {
                $cacheS = [];
            } else {
                $cacheS = cache('middle_'.session('info.staff'));
            }
            $data = [
                'order' => $order_code,
                'goods' => $cacheS,
                'retuen' => $retuen
            ];
            webApi(200, 'ok', $data);
        }
    }

    /**
     * 编辑返单
     */
    public function editOrder()
    {
        
        Db::table($this->db.'.vip_return_order_middle')->where('order_code', input('code'))->delete();  

        $dataCache = cache('middle_'.session('info.staff'));
        $store = Db::table($this->db.'.vip_staff')->field('store_code')->where('code', input('shopping_code'))->find();
        if ($store['store_code']) {
            $storeName = Db::table($this->db. '.vip_store')->field('name')->where('code', $store['store_code'])->find();
        } else {
            $storeName['name'] = '';
        }
        $data = [  //vip_retuen_order
            'id' => input('id'),
            'vip_code' => input('vip_code'),
            'vip_name' => input('vip_name'),
            'execution_time' => strtotime(input('execution_time')),  //执行时间
            'shopping_code' => input('shopping_code'),  //导购
            'shopping_name' => input('shopping_name'),
            'store_code' => $store['store_code'],
            'store_name' => $storeName['name'],
            // 'core' => input('core'),            //核心卖点  
            // 'problem' => input('problem'),      //问题
            // 'collocation' => input('collocation'),      //整体搭配
            // 'top' => input('top'),  
            // 'talking_skill' => input('talking_skill'),      //话术
            'modification_time' => time()//修改时间
        ];

        $programme = input('programme_arr');
        if (isset($programme)) {
            Db::table($this->db.'.vip_retuen_order_vice')->where('retuen_code', input('code'))->delete();
            foreach ($programme as $k=>$v) {
                $programme[$k]['retuen_code'] = input('code');
                $programme[$k]['programme'] = $v['id'];
                unset($programme[$k]['name'], $programme[$k]['img'],$programme[$k]['id']);
            }
            Db::table($this->db.'.vip_retuen_order_vice')->insertAll($programme);
        }
        unset($programme);
        $res = Db::table($this->db.'.vip_retuen_order')->update($data);
        if ($res) {
            if ($dataCache != false) {
                foreach ($dataCache as $v) {
                    $middleData = [  //vip_return_order_middle
                        'order_code' => input('code'),
                        'goods_name' => $v['goods_name'],
                        'goods_code' => $v['goods_code'],
                        'programme' => $v['programme'],
                        'goods_img' => $v['goods_img'],
                        'price' => $v['price']
                    ];
                    Db::table($this->db.'.vip_return_order_middle')->insert($middleData);
                }
            }
            cache('middle_'.session('info.staff'), null);
            webApi(200, '成功');
        } else {
            webApi(400, '失败');
        }
    }

    /**
     * 商品搜索
     */
    public function search()
    {
        $data = Db::table($this->db.'.vip_goods')
            ->field('code, name, price, img, color, sizes, bar_code')
            ->where('code', 'like', input('where').'%')
            ->page(input('page'), input('limit'))
            ->select();
        $count = Db::table($this->db.'.vip_goods')
            ->field('code, name, price, img, bar_code')
            ->where('code', 'like', input('where').'%')
            ->count();
        $data = [
            'data' => $data,
            'count' => $count
        ];
        webApi(200, 'ok', $data);
    }

    /**
     * 模糊查询会员
     */
    public function likeVip()
    {
        $data = Db::table($this->db.'.vip_viplist')
            ->field('code, username, phone, img, vvip')
            ->where('code|username|phone', 'like', input('where').'%')
            ->page(input('page'), input('limit'))
            ->select();
        $count = Db::table($this->db.'.vip_viplist')
            ->field('code, username')
            ->where('code|username|phone', 'like', input('where').'%')
            ->count();
        $data = [
            'data' => $data,
            'count' => $count
        ];
        webApi(200, 'ok', $data);
    }

    /**
     * 总结
     */
    public function summary()
    {
        $data = [  
            'id' => input('id'),
            'summary' => input('summary'),
            'remarks' => input('remarks')

        ];
        $res = Db::table($this->db.'.vip_retuen_order')->update($data);

        if ($res) {
            
            webApi(200, '成功');
        } else {
            webApi(400, '失败');
        }
    }

}