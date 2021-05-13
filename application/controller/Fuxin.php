<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;
use image\Image;
use think\facade\Env;

class Fuxin extends Common
{

    /**
     * 消息提醒
     */
    public function xiaoxi()
    {
        $company_substr = substr($this->db,-4);
        if ($this->db == 'ic' || is_numeric($company_substr)) { 
            $url = 'https://wechat.suokeduo.com/api/msg/findMsg?company='.$this->db.'&createTime=1&isRead=0';
            $json = file_get_contents($url);
            $json_decode = json_decode($json,true);
            $data_from = $this->assoc_unique($json_decode, 'fromUser');
        } else {
            $url = 'https://wxauth.suokeduo.com/modules/findNoRead?company='.$this->db;
            $json = file_get_contents($url);
            $data_from = json_decode($json,true);
        }
        if (empty($data_from)) {
            $data = [];
            webApi(200, 'ok', $data);
        }
        $result = array_column($data_from,'fromUser');
        $sz = implode(',', $result);
        unset($json, $result, $company_substr);
        
        $whereStaff = [];
        //基本配置
        $sysCon = Db::table($this->db . '.vip_sys_con')->find()['fen_is_org'] ;
        
        if ($sysCon== "on") { 
            $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
            //门店限制
            if ($operate['code'] == 'boss') {
                
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
                    $aa = [];
                    webApi(200, 'ok', $aa);
                }
            } else {
                $whereStaff[] = ['consultant_code', '=', $operate['code']];
            }
        } 
        $data = Db::table($this->db.'.vip_viplist')
            ->field('code, username, birthday, consultant_code, total_consumption, img, consumption_times, final_purchases, return_visit, phone, consumption_number, openid')
            ->where($whereStaff)   
            ->where('openid', 'in', $sz)
            ->select();
        if (!empty($data)) {
            foreach($data as $k=>$v) {
                foreach ($data_from as $val) {
                    if ($v['openid'] == $val['fromUser']) {
                        $data[$k]['noReadCount'] = $val['noReadCount'];
                    }
                }
                $data[$k]['name'] = $v['username'];
                if (!empty($v['birthday'])) {
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
        } else {
            $data = [];
        }
        
        webApi(200, 'ok', $data);
    }

    function assoc_unique($arr, $key) {
 
        $tmp_arr = array();

        $arr_one=array_column($arr,'fromUser');//把值提取出来转成一维数组
        $arr_two=array_count_values($arr_one);//数组的值作为键名，该值在数组中出现的次数作为值

        foreach ($arr as $k => $v) {
            $arr[$k]['noReadCount'] = $arr_two[$v['fromUser']];
            if (in_array($v[$key], $tmp_arr)) {//搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
                unset($arr[$k]);
            } else {
                $tmp_arr[] = $v[$key];
            }
        }
        sort($arr); //sort函数对数组进行排序
        
        return $arr;
        
    }

    /**
     * 添加一帧加入缓存一次
     */
    public function imgcache()
    {
        // dump(cache('getMsgId_'.session('info.staff')));exit;
        $file = request()->file('file');
        $info = $file->move('../web/upload');
        if ($info) {
            $filepath = '/upload/' . $info->getSaveName();
            $path = Env::get('root_path') . 'web' . $filepath;
            //  压缩文件 extend  下引入类
            // $image = new Image();
            // $image->open($path);
            // $image->thumb(480, 480);
            // $image->save($path);
            $img_url = 'https://wx.suokeduo.com'.$filepath;
        }else{
            webApi(400, '图片上传失败');
        }
       
        $ew = new ErpWhere($this->db, '');
        $baseImg = $ew->imgToBase64($img_url);
        // $baseImg = $ew->imgToBase64(input('img_url'));
        $cache = [
            'delete_id' => 'del' . str_replace('.', '', microtime(1)),
            'baseImg' => $baseImg[0],  //对图片进行base64编码
            'img_txt' => base64_encode(iconv('UTF-8', 'GB2312', input('img_txt'))),    //对文字进行base64编码
            'img_type' => $baseImg[1]
        ];

        if (cache('getMsgId_'.session('info.staff'))) {
            $data = cache('getMsgId_'.session('info.staff'));
        } else {
            $data = [];
        }
        array_push($data, $cache);
                
        $res = cache('getMsgId_'.session('info.staff'), $data, 3600);

        if ($res) {
            webApi(200, 'ok', $cache);
        } else {
            webApi(400, '失败');
        }
    }

    /**
     * 清空缓存
     */
    public function emptyCache()
    {
        cache('getMsgId_'.session('info.staff'), null);
        webApi(200, '成功');
    }

    /**
     * 删除缓存中的帧
     */
    public function delCache()
    {
        $id = input('id') ?? null;
        $datacache = cache('getMsgId_'.session('info.staff'));
        if (count($datacache) > 1) {
            foreach ($datacache as $k => $v) {
                if ($id == $v['delete_id']) {
                    unset($datacache[$k]);
                }
            }
            sort($datacache);
            cache('getMsgId_'.session('info.staff'), $datacache, 3600);
        } else {
            cache('getMsgId_'.session('info.staff'), null);
        }
        unset($id);
        webApi(200, '删除成功!');
    }

    /**
     * 发送视图
     */
    public function SendWithMmsID() {
        
        $imgcache = cache('getMsgId_'.session('info.staff'));
        if ($imgcache == false) {
            webApi(400, '没有添加图文');
        }
        $msg = Db::table('company.vip_business')->field('code, fuxin_msg')->where('code', $this->db)->find();
        //判断短信条数
        if ($msg['fuxin_msg'] < count(input('phone'))) {
            webApi(400, '视图条数不足，可用视图为' . $msg['fuxin_msg'] . '条' . '，当前发送视图为'.count(input('phone')).'条');
        }
        $ew = new ErpWhere($this->db, '');
        $data = $ew->fuXin(input('phone'), input('title'), $imgcache);
        // $data = $ew->fuXin($phone, input('title'), $imgcache);
        if ($data == 200) {
            Db::table('company.vip_business')->where('code', $this->db)->setDec('fuxin_msg', count(input('phone')));
            cache('getMsgId_'.session('info.staff'), null);
            webApi(200, '发送成功');
        } else {
            webApi(400, '失败');
        }
     
    }
}