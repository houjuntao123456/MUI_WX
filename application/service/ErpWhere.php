<?php

namespace app\service;

use think\Db;

class ErpWhere
{
    private $db;
    private $orgCodes = '';
    private $orgs = '';
    private $store = '';

    public function __construct($db, $orgs)
    {
        $this->db = $db;
        $this->orgCodes = $orgs;
    }

    public function get()
    {
        if ($this->store == '') {
            return $this->orgs;
        } else {
            return $this->store;
        }
    }

    /**
     * 获取自己管理的组织机构及子机构
     */
    public function org()
    {
        $db = $this->db;
        // 为空就是自己
        if ($this->orgCodes == '') {
            $orgs = session('info.admin_org');
        } else {
            $orgs = $this->orgCodes;
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
            $orgs = implode(',', array_unique($codes));
        }
        $this->orgs = $orgs;
        return $this;
    }

    /**
     * 查询该组织机构下的门店并返回
     */ 
    public function store()
    {
        $store = Db::table($this->db . '.vip_store')->field('code')->where('org_code', 'in', $this->orgs)->where('status', 0)->select();
        if (!empty($store)) {
            $store = implode(',', array_column($store, 'code'));
        } else {
            $store = '';
        }
        $this->store = $store;
        return $this;
    }

    /**
     * 取中文字符长度
     */
    public function abslength($str)
    {
        if (empty($str)) {
            return 0;
        }
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, 'utf-8');
        } else {
            preg_match_all("/./u", $str, $ar);
            return count($ar[0]);
        }
    }

    /**
     * 发送短信消息
     * @param array $phone 用户手机号  一维数组
     * @param array $content 短信内容
     */
    public function shortMessage($phone, $content)
    {
        $phoneStr = implode(',', $phone);
        $url = 'https://wxauth.suokeduo.com/user/sendSmsML?phone=' . $phoneStr . '&content=' . urlencode($content) . '&code=suoke';//.$this->db;// . session('database.db');
        $curl = file_get_contents($url);
        $data = json_decode($curl, true);
        if ($data['status'] == 1) {
            foreach ($phone as $v) {
                $user = Db::table($this->db . '.vip_viplist')->field('username,code')->where('phone', $v)->find();
                $staff = Db::table($this->db . '.vip_staff')->field('name,code')->where('code', session('info.staff'))->find();
                if ($user) {
                    $visit_data = [
                        'user_name' => $user['username'],
                        'user_code' => $user['code'],
                        'visit_operator' => session('info.staff'),
                        'visit_mode' => '短信回访',
                        'content' => $content,
                        'time' => time()
                    ];

                    $message = [
                        'code' => 'MESS' . str_replace('.', '', microtime(1)),
                        'vip_name' => $user['username'],
                        'vip_code' => $user['code'],  
                        'executor_code' => session('info.staff'),
                        'executor_name' => $staff['name'],
                        'content' => $content,
                        'create_time' => time()
                    ];

                    Db::table($this->db . '.vip_returnvisit_record')->limit(100)->insert($visit_data);                      // 回访记录
                    Db::table($this->db . '.vip_inmessage_record')->limit(100)->insert($message);                           // 短信记录
                    Db::table($this->db . '.vip_viplist')->where('phone', $v)->setField('return_visit', time()); // 修改最后回访时间
                }
            }
            return 1;
        } else {
            return '发送失败！';
        }
    }

    /**
     * 微信发送消息
     */
    public function wxGroupSendOut($code, $content)
    {
        foreach ($code as $v) {
            $openId = Db::table($this->db . '.vip_viplist')->field('code, openid, username')->where('phone', $v)->where('openid', '<>', '')->find();
            if (!empty($openId)) {
                $url = 'https://wxauth.suokeduo.com/modules/sendMsg?openid=' . $openId['openid'] . '&content=' . urlencode($content) . '&company=' . $this->db;
                $curl = file_get_contents($url);
                $data = json_decode($curl, true);
                if ($data['status'] == true) {
                    $dece = [
                        'return_visit' => time(),
                        'customer_service' => session('info.staff')
                    ];
                    Db::table($this->db . '.vip_viplist')->where('code', $openId['code'])->setField($dece);
                    $visit_data = [
                        'user_name' => $openId['username'],
                        'user_code' => $openId['code'],
                        'visit_operator' => session('info.staff'),
                        'visit_mode' => '微信回访',
                        'content' => $content,
                        'time' => time()
                    ];
                    Db::table($this->db . '.vip_returnvisit_record')->insert($visit_data);
                }
            }
        }
    }

    /**
     * 新微信群发
     */
    public function wxG($code, $content, $type)
    {   
        $whereCode = implode(',', $code);
        dump($whereCode);
        $openId = Db::table($this->db . '.vip_viplist')
                ->field('openid, code, username')
                ->where('code', 'in', $whereCode)
                ->where('openid', '<>', '')
                ->select();
        dump($openId);
        exit;
        $openidWhere = array_column($openId, 'openid');
        $openidW = implode(',', $openidWhere);
        if ($type == 'image') {
            $baseImg = $this->imgToBase64($content);
            $cont = $baseImg[0];
            $imageType = $baseImg[1];
        } else if ($type == 'text') {
            $cont = $content;
            $imageType = '';
        }
        $company_substr = substr($this->db,-4);
        if ($this->db == 'ic' || is_numeric($company_substr)) {
            $url = 'https://wechat.suokeduo.com/api/msg/sendMassMsg';
        } else {
            $url = 'https://wxauth.suokeduo.com/coupon/sendMsg';
        }
        $ser = [
            'company' => $this->db,
            'msgType' => $type,
            'openids' => $openidW,
            'content' => $cont,
            'imageType' => $imageType
        ];
        $Data = json_decode(doHttpPost($url, $ser), true);
        if ($Data['errorCode'] == 0) {
            foreach ($code as $v) {
                $vip_viplist = Db::table($this->db . '.vip_viplist')
                        ->field('code, openid, username')
                        ->where('code', $v)
                        ->where('openid', '<>', '')
                        ->find();
                if (!empty($vip_viplist)) {
                    $dece = [
                        'return_visit' => time(),
                        'customer_service' => session('info.staff')
                    ];
                    Db::table($this->db . '.vip_viplist')->where('code', $vip_viplist['code'])->setField($dece);
                    $visit_data = [
                        'user_name' => $vip_viplist['username'],
                        'user_code' => $vip_viplist['code'],
                        'visit_operator' => session('info.staff'),
                        'visit_mode' => '微信回访',
                        'content' => $content,
                        'time' => time()
                    ];
                    Db::table($this->db . '.vip_returnvisit_record')->insert($visit_data);
                }
            }
            return '发送成功';
        } else {
            return '发送失败';
        }
    }

    /**
     * 客户端推送卡劵消息
     * $openId : 会员的openid
     * $title : 您好，【'.$username.'】，您有一张卡券兑换成功
     * $coupon ： 卡劵信息
     * $type    ： 1 领取卡劵  其他赠送卡劵
     */
    public function pushCoupon($openId, $title, $coupon, $staff_code = "") 
    {
        if ($coupon['coupon_type'] == 0) {
            $time = '领取后计算有效期';
        } else {
            if ($coupon['xz'] == 1) {
                $start_time = time() + (86400 * $coupon['takeEffect']);
                $time = date('Y-m-d H:i:s', $start_time). ' - ' .date('Y-m-d H:i:s', $start_time + (86400 * $coupon['effective']));
            } else {
                $time = date('Y-m-d H:i:s', $coupon['start_time']). ' - ' .date('Y-m-d H:i:s', $coupon['end_time']);
            }
        }

        $couponUrl = 'https://wx.suokeduo.com/member/members/card_details.html?company=' . $this->db . '_' . $coupon['code']. '_' . $coupon['coupon_type'] . '_' . $staff_code;
        $company_substr = substr($this->db,-4);
        if ($this->db == 'ic' || is_numeric($company_substr)) {
            $data = [
                'first' => $title,
                'keyword1' => $coupon['name'],
                'keyword2' => $coupon['coupon_name'],
                'keyword3' => '1',
                'keyword4' => $time,
                'keyword5' => '请查看详情',
                'remark' => $coupon['remark']
            ];
        } else {
            $data = [
                'first' => $title,
                'ecoupon' => $coupon['name'],
                'product' => $coupon['coupon_name'],
                'number' => '1',
                'valid' => $time,
                'location' => '请查看详情',
                'remark' => $coupon['remark']
            ];
        }
       
        switch ($this->db) {    
            case "suoke":
                $templateId = 'VxcWkYVTe1t9Rutk5IFLv3DRjLT8CsqC9mt8wT-oB9Y';
                break;
            case "suokeduo2020":
                $templateId = 'BB7hk6qfrviD-1Oj1JE0Aq7o-c0FmYir1aWQFVvWwfY';
                break;
            case "jingshunxing":
                $templateId = 'EVAFXjKnHRxT5MINY0eHX2QVpVbdWgz4yAkl9BPmiiA';
                break;
            case "dujia":
                $templateId = '6y4gEpzU4O64MwhxKKa-uAaVz56SXIUD9EEjQc2SMm4';
                break;
            case "youjin":
                $templateId = 'jbCYBznWKen3HH54juVmdSrZW_Hr7BhxQ2VjPOMPvfE';
                break;
            case "hongqingting":
                $templateId = 'qmpZuWR1hggWMdlbsgvsf0y1URka5GlcFxStoQ-Gy4g';
                break;
            case "weishuai11":
                $templateId = '9d1sGdOohbE46Ez7OysTskkLWJjzXSP1mVHOkdju3zE';
                break;
            case "ic":
                $templateId = 'vVCgERfjr-gMDfLYzepU9pTFIfUoMNncc9yU28xW71M';
                break;
            case "zhimeiyu":
                $templateId = 'k8nwqsUbuVy5H3Tbi4YL9L5uvvAgvpdot8Q5nIXDalQ';
                break;
            default:
                $templateId = '';
        }
        $vdata = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if ($this->db == 'ic' || is_numeric($company_substr)) {
            $url = 'https://wechat.suokeduo.com/api/msg/sendTemplate';
            $map = [
                'company' => $this->db,
                'openid' => $openId,
                'url' => $couponUrl,
                'data' => $vdata,
                'businessCode ' => 'template_dzpz'
            ];
            doHttpPost($url, $map);
        } else {
            $url = 'https://wxauth.suokeduo.com/template/send?company=' . $this->db . '&openId=' . $openId . '&url=' . $couponUrl . '&data=' . urlencode($vdata) . '&templateId='.$templateId;
            file_get_contents($url);
        }
    }

    /**
     * 富信
     */
    public function fuXin($phone, $title, $imgcache)
    {
        $url = 'https://wxauth.suokeduo.com/modules/getMsgId';
        
        $map_c = "";
        if ($imgcache) {
            if (count($imgcache) == 1) {
                $map_c .= "3,".$imgcache[0]['img_type']."|".$imgcache[0]['baseImg'].",txt|".$imgcache[0]['img_txt'];
            } else {
                foreach ($imgcache as $k=>$v) {
                    $map_c .= "3,".$v['img_type']."|".$v['baseImg'].",txt|".$v['img_txt'].';';
                }
                $map_c = rtrim($map_c, ";");
            }
            
        }
        $map = [
            "content" => $map_c
        ];
        $data = json_decode(doHttpPost($url, $map), true);
        $phoneStr = implode(',', $phone);
        if ($data['Result'] == 0 ) {
            $mmsID = 'https://wxauth.suokeduo.com//modules/SendWithMmsID';
            $ser = [
                'phone' => $phoneStr,
                'msgId' => $data['ResultMsg'],
                'title' => $title,
                'sendtime' => date('Y-m-d h:i:s', time() + 300)
            ];
            $mmsData = json_decode(doHttpPost($mmsID, $ser), true);
            
            if ($mmsData['Result'] == 0) {
                foreach ($phone as $v) {
                $user = Db::table($this->db . '.vip_viplist')->field('username,code,phone')->where('phone', $v)->find();
                $staff = Db::table($this->db . '.vip_staff')->field('name,code')->where('code', session('info.staff'))->find();
                if ($user) {
                    $visit_data = [
                        'user_name' => $user['username'],
                        'user_code' => $user['code'],
                        'visit_operator' => session('info.staff'),
                        'visit_mode' => '视图回访',
                        'content' => $title,
                        'time' => time()
                    ];

                    $message = [
                        'code' => 'MMS' . str_replace('.', '', microtime(1)),
                        'vip_name' => $user['username'],
                        'vip_code' => $user['code'],
                        'vip_phone' => $user['phone'],
                        'executor_code' => session('info.staff'),
                        'executor_name' => $staff['name'],
                        'theme' => $title,
                        'content' => '',
                        'create_time' => time()
                    ];

                    Db::table($this->db . '.vip_returnvisit_record')->limit(100)->insert($visit_data);                      // 回访记录
                    Db::table($this->db . '.vip_mms_record')->limit(100)->insert($message);                                 // 彩信记录
                    Db::table($this->db . '.vip_viplist')->where('phone', $v)->setField('return_visit', time()); // 修改最后回访时间
                }
            }
                return 200;
            } else {
                return 400;
            }
        } else {
            return 400;
        }
    }

    /**
     * 图转base64编码
     * @param img 图片网址
    **/
    public function imgToBase64($img = '')
    {
        if (!$img) {
            return false;
        }
        $info = getimagesize($img);
        $suffix = false;  //获取图片类型 
        if($mime = $info['mime']){
            $suffix = explode('/',$mime)[1];
        }
        if ($suffix == 'jpeg') {
            $suffix = 'jpg';
        }

        $base64 = chunk_split(base64_encode(file_get_contents($img))); // 对图片进行编码
        return [$base64, $suffix];
    }

    /**
     * 各种有礼赠送卡劵
     */
    public function couponAdd($type, $coupon_code, $vip_code, $remark)
    {
        //查询当前登入人
        $operate = Db::table($this->db . '.vip_staff')->where('code', session('info.staff'))->find();
        //判断门店
        if ($operate['store_code'] == "") {
            $store_name = "无门店";
        } else {
            $store_name = Db::table($this->db . '.vip_store')->where('code', $operate['store_code'])->find()['name'];
        }
        //接受获取的号码
        // [$type, $coupon_code, $vip_code] = [input('card_type'), input('coupon'), input('card_number')];
        if ($type == null) {
            webApi(0, 'error', 0, '卡券类型错误!');
        }
        //定义卡券金额, 折扣, 礼品
        $two = "";
        $three = "";
        //判断类型并返回数据
        if ($type == '优惠劵') {
            $one = Db::table($this->db . '.vip_cash_coupon')->where('code', $coupon_code)->find(); // 优惠券
            $two = $one['card_money'];
            $three = $one['money'];
            $one['coupon_name'] = $one['card_money'] . '元，优惠劵';
            $coupon_tpye = 0;
        } else if ($type == '折扣劵') {
            $one = Db::table($this->db . '.vip_coupon')->where('code', $coupon_code)->find(); // 折扣券
            $two = $one['card_discount'];
            $three = $one['money'];
            $one['coupon_name'] = $one['card_discount'] . '折，折扣劵';
            $coupon_tpye = 1;
        } else if ($type == '礼品劵') {
            $one = Db::table($this->db . '.vip_coupon_gift')->where('code', $coupon_code)->find(); // 礼品券
            $two = $one['gift_code'];
            $three = 0;
            $one['coupon_name'] = $one['gift_code'] . '礼品劵';
            $coupon_tpye = 2;
        }
        if ($one['xz'] == 1) {
            $start_time = time() + (86400 * $one['takeEffect']);
            $end_time = $start_time + (86400 * $one['effective']);
            
        } else {
            $start_time = $one['start_time'];
            $end_time = $one['end_time'];
        }
        if (!empty($operate)) {  // 因为会员注册时查不到赠送人
            $data = [
                'code' => 'CADD' . str_replace('.', '', microtime(1)),
                'vip_code' => $vip_code,
                'card_type' => $coupon_tpye,
                'card_name' => $one['name'],
                'coupon_type' => $one['coupon_type'],
                'card_many' => $two,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'card_code' => $coupon_code,
                'remark' => $remark,
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
                'g_store_name' => $store_name
            ];
        } else {
            $data = [
                'code' => 'CADD' . str_replace('.', '', microtime(1)),
                'vip_code' => $vip_code,
                'card_type' => $coupon_tpye,
                'card_name' => $one['name'],
                'coupon_type' => $one['coupon_type'],
                'card_many' => $two,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'card_code' => $coupon_code,
                'remark' => $remark,
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
                'off_store_name' => $one['off_store_name']
            ];
        }
        
        //备注替换
        $one['remark'] = $data['remark'];
        //查询会员
        $vip = Db::table($this->db . '.vip_viplist')->where('code', $vip_code)->find();

        $coupon_record = Db::table($this->db . '.vip_coupon_record')
                        ->where('vip_code', $vip_code)
                        ->where('card_code', $coupon_code)
                        ->where('status', 0)
                        ->count();
        if ($one['receive_limit'] > 0) {
            if ($coupon_record >= $one['receive_limit']) {
                return '会员卡劵数量超过限制领取张数'; 
            }
        }

        //判断普通卡券与赠送卡券
        if ($one['coupon_type'] == 0) {
            if (empty($vip['openid'])) {
                return '会员未绑定微信, 无法赠送普通卡券';
            }
            $title = '您好，【' . $vip['username'] . '】您有卡券待领取！';
            $this->pushCoupon($vip['openid'], $title, $one, session('info.staff'));
            return true;
        } else {
            //执行赠送
            $res = Db::table($this->db . '.vip_coupon_record')->insert($data);
            $title = '您好，【' . $vip['username'] . '】您收到一张卡券，请到会员中心查看或使用！';
            //清除变量
            unset($data);
            //判断返回数据
            if ($res) {
                if ($type == 0) {
                    Db::table($this->db . '.vip_cash_coupon')->where('code', $coupon_code)->setInc('receive', 1); // 优惠券
                } else if ($type == 1) {
                    Db::table($this->db . '.vip_coupon')->where('code', $coupon_code)->setInc('receive', 1); // 折扣券
                } else if ($type == 2) {
                    Db::table($this->db . '.vip_coupon_gift')->where('code', $coupon_code)->setInc('receive', 1); // 礼品券
                }
                if (empty($vip['openid'])) {
                    return '赠送成功, 会员未绑定微信不会发送微信消息';
                } else {
                    $this->pushCoupon($vip['openid'], $title, $one);
                    return true;
                }
            } else {
                return false;
            }
        }
    }

    /**
     * 总积分晋升
     */
    public function integral_promotes($integral, $vipMember)
    {
        $db = $this->db;
        $memLv = Db::table($db .'.vip_viplevel')->field('uid')->where('code', $vipMember['level_code'])->find();
        if (!empty($memLv)) {
            $nowLv = $memLv['uid'];
        } else {
            $nowLv = -1;
        }
        // 级别晋升 查询该会员当前的级别折扣力度 查询比他力度更大的第一条 比较所有的晋升条件 如果有一条符合 晋升
        $promotes = Db::table($db .'.vip_vippromote')
                    ->alias('a')
                    ->leftJoin($db .'.vip_viplevel l', 'l.code = a.levelname')
                    ->where('l.uid', '>', $nowLv)
                    // ->where('a.state', 1)
                    ->select();
        
        if (!empty($promotes)) {
            foreach ($promotes as $k=>$v) {
                $promotes[$k]['js'] = false;
            }
            foreach ($promotes as $k=>$v) {
                // 判断累计总积分
                $getIntegral = Db::table($db .'.vip_flow_integral')->field('sum(integral) as integral')->where('vip_code', $vipMember['code'])->where('create_time', '>=', time() - ($v['total_integral_time'] * 86400))->find();
                if ( $getIntegral['integral'] + $integral  >= $v['total_integral'] ) {
                    $promotes[$k]['js'] = true;
                }
                unset($getIntegral);
            }
            foreach ($promotes as $k=>$v) {
                if ($v['js'] == false) {
                    unset($promotes[$k]);
                }
            }
            if (!empty($promotes)) {
                foreach ($promotes as $k=>$v) {
                    $promotes[$k]['l'] =
                        !empty( Db::table($db .'.vip_viplevel')->field('uid')->where('code', $v['levelname'])->find() )
                            ?
                        Db::table($db .'.vip_viplevel')->field('uid')->where('code', $v['levelname'])->find()['uid']
                            : '';
                }
                rsort($promotes);
                $memberLevel = Db::table($db .'.vip_viplevel')->where('code',  $promotes[0]['levelname'])->find();
            }
        }
        unset($promotes);
        if (isset($memberLevel) && !empty($memberLevel)) {
            $promote_insert = [
                'vip_code' => $vipMember['code'],
                'vip_name' => $vipMember['username'],
                'before_level' => $vipMember['level_code'],
                'after_level' => $memberLevel['code'],
                'reason' => '自动晋升',
                'create_time' => time()
            ];
            Db::table($db . '.vip_promote')->insert($promote_insert);
            Db::table($db . '.vip_viplist')->where('code', $vipMember['code'])->update(['level_code' => $memberLevel['code']]);
        }
    }

    /**
     * 储值晋升
     */
    public function value_promotes($value, $vipMember)
    {
        $db = $this->db;
        $memLv = Db::table($db . '.vip_viplevel')->field('uid')->where('code', $vipMember['level_code'])->find();
        if (!empty($memLv)) {
            $nowLv = $memLv['uid'];
        } else {
            $nowLv = -1;
        }
        // 级别晋升 查询该会员当前的级别折扣力度 查询比他力度更大的第一条 比较所有的晋升条件 如果有一条符合 晋升
        $promotes = Db::table($db . '.vip_vippromote')
            ->alias('a')
            ->leftJoin($db . '.vip_viplevel l', 'l.code = a.levelname')
            ->where('l.uid', '>', $nowLv)
            // ->where('a.state', 1)
            ->select();

        if (!empty($promotes)) {
            foreach ($promotes as $k => $v) {
                $promotes[$k]['js'] = false;
            }
            foreach ($promotes as $k => $v) {
                if ($vipMember['total_value'] + $value  >= $v['cumulative']) {
                    $promotes[$k]['js'] = true;
                }
            }
            foreach ($promotes as $k => $v) {
                if ($v['js'] == false) {
                    unset($promotes[$k]);
                }
            }
            if (!empty($promotes)) {
                foreach ($promotes as $k => $v) {
                    $promotes[$k]['l'] =
                        !empty(Db::table($db . '.vip_viplevel')->field('uid')->where('code', $v['levelname'])->find())
                        ?
                        Db::table($db . '.vip_viplevel')->field('uid')->where('code', $v['levelname'])->find()['uid']
                        : '';
                }
                rsort($promotes);
                $memberLevel = Db::table($db . '.vip_viplevel')->where('code',  $promotes[0]['levelname'])->find();
            }
        }
        unset($promotes);
        if (isset($memberLevel) && !empty($memberLevel)) {
            $promote_insert = [
                'vip_code' => $vipMember['code'],
                'vip_name' => $vipMember['username'],
                'before_level' => $vipMember['level_code'],
                'after_level' => $memberLevel['code'],
                'reason' => '自动晋升',
                'create_time' => time()
            ];
            Db::table($db . '.vip_promote')->insert($promote_insert);
            Db::table($db . '.vip_viplist')->where('code', $vipMember['code'])->update(['level_code' => $memberLevel['code']]);
        }
    }

    /**
     * 商品查询会员
     */
    public function goodsVip($barCode)
    {
        $db = $this->db;
        //商品
        $goods = Db::table($db . '.vip_goods')->field('bar_code, color, sizes')->where('bar_code', $barCode)->find();
        //商品单号
        $lable = Db::table($db . '.vip_goods_labels')->where('goods_code', $goods['bar_code'])->select();
        //条件
        $where = '1=1';
        //判断
        if (empty($goods) && empty($lable)) {
            return "";
        } else {
            if (!empty($goods['color'])) {
                $where .= ' and ' . 'goods.color =' . ' "' . $goods['color'] . '"';
            }
            if (!empty($goods['sizes'])) {
                $where .= ' and ' . 'goods.sizes =' . ' "' . $goods['sizes'] . '"';
            }
            $goods_code = [];
            if (!empty($lable)) {
                foreach ($lable as $k => $v) {
                    $lave = Db::table($db . '.vip_goods_labels')->where('label_code', $v['label_code'])->where('label_id', $v['label_id'])->select();
                    foreach ($lave as $val) {
                        if (!in_array($val['goods_code'], $goods_code)) {
                            array_push($goods_code, $val['goods_code']);
                        }
                    }
                }
                $vv = '';
                foreach ($goods_code as $av) {
                    $vv .= "'" . $av . "',";
                }
                $aaa = trim($vv, ','); // trim 去掉字符串两边符号
                $where .= " and goods.bar_code in (" . $aaa . ")";
            }
            //查询符合的会员code
            $order_vip = Db::table($db . '.vip_goods_order')
                ->alias('v')
                ->leftJoin($db . '.vip_goods_order_info info', 'v.code = info.order_code')
                ->leftJoin($db . '.vip_goods goods', 'goods.bar_code = info.goods_code')
                ->field('v.vip_code code')
                ->where($where)
                ->where('v.vip_code', '<>', '非会员')
                ->group('v.vip_code')
                ->select();
            //格式化code数据
            // $vipCode = implode(',', array_column($order_vip, 'code'));
            $vipCode = array_column($order_vip, 'code');
            // dump($vipCode);exit;
            return $vipCode;
        }
    }
    
    /**
     * 二维数组分页方法
     */
    public static function arrPage($arr, $p, $count)
    {

        if (empty($p)) {

            $p = 1;
        }

        if (empty($count)) {

            $count = 10;
        }

        $start = ($p - 1) * $count;

        $new_arr = [];

        for ($i = $start; $i < $start + $count; $i++) {

            if (!empty($arr[$i])) {
                $new_arr[] = $arr[$i];
            }
        }

        return $new_arr;
    }
}
