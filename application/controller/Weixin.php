<?php

namespace app\controller;

use think\Db;
use think\Controller;
use app\service\ErpWhere;
use image\Image;
use think\facade\Env;

class Weixin extends Common
{

    /**
     * openid查询
     */
    public function issetOpenid()
    {
        $openId = input('openId');
        $list = [
            'img' => input('img')
        ];
        if ($openId == 'undefined' || empty($openId)) {
            webApi(400, 'no');
        } else {
            $data = Db::table($this->db . '.vip_viplist')->where('openid', $openId)->find();
            if ($data) {
                Db::table($this->db . '.vip_viplist')->where('openid', $openId)->setField($list);
                webApi(200, 'ok');
            } else {
                webApi(400, 'no');  
            }
        }
    }

    /**
     * 老会员绑卡  username
     */
    public function tyingCard()
    {
        $openId = input('openId');

        if (input('username') == '' || input('phone') == '') {
            webApi(400, '缺少姓名,或手机号');
        }

        $user = Db::table($this->db. '.vip_viplist')->where('username', input('username'))->where('phone', input('phone'))->find();

        // if (!empty($user['openid'])) {
        //     webApi(400, '该用户已被绑定');
        // }

        if (empty($user)) { 
            webApi(400, '查无此人,请核对手机号码和姓名！');
        }

        if (!empty($openId)) {
            $isOpenId = Db::table($this->db . '.vip_viplist')->where('openid', $openId)->find();
            if ($isOpenId) {
                webApi(400, '用户已经绑定');
            }
            unset($isOpenId);
            $list = [
                'openid' => $openId,
                'vvip' => 1,
                'img' => input('img'),
                'vvip_time' => time()
            ];
            $data = Db::table($this->db . '.vip_viplist')->where('phone', input('phone'))->where('username', input('username'))->setField($list);
            if ($data) {
                if (empty($user['vvip_time'])) {
                    $write_off = Db::table($this->db.'.vip_activity_courtesy')
                    ->where('activity_type', '绑卡有礼')
                    ->where('start_time', '<=', time())
                    ->where('end_time', '>=', time())
                    ->where('status', 0)
                    ->find();  
                    if (!empty($write_off)) {
                        Db::startTrans();
                        try {
                            $ew = new ErpWhere($this->db, '');
                            if (!empty($write_off['integral'])) {
                                Db::table($this->db . '.vip_viplist')->where('code', $user['code'])->setInc('residual_integral', $write_off['integral']); // 用户的剩余总积分加
                                Db::table($this->db . '.vip_viplist')->where('code', $user['code'])->setInc('total_integral', $write_off['integral']); // 用户的总积分加
                                $kaika = Db::table($this->db . '.vip_viplist')->where('code', $user['code'])->find();
                                $integral_jilu = [
                                    'documents' => 'JFLS' . str_replace('.', '', microtime(1)),
                                    'vip_code' => $user['code'],
                                    'vip_name' => $user['username'],
                                    'integral' => $write_off['integral'],
                                    'road' => '绑卡有礼赠送',
                                    'create_time' => time(),
                                ];
                                Db::table($this->db . '.vip_flow_integral')->insert($integral_jilu);
                                $ew->integral_promotes($write_off['integral'], $kaika);
                                unset($integral_jilu);
                            }
                            if (!empty($write_off['stored_value'])) {
                                Db::table($this->db . '.vip_viplist')->where('code', $user['code'])->setInc('residual_value', $write_off['stored_value']); // 用户的剩余储值加
                                Db::table($this->db . '.vip_viplist')->where('code', $user['code'])->setInc('total_value', $write_off['stored_value']); // 用户的总储值加
                                $value_jilu_introducer = [
                                    'documents' => 'CZLS' . str_replace('.', '', microtime(1)),
                                    'vip_code' => $user['code'],
                                    'vip_name' => $user['username'],
                                    'stored_value' => $write_off['stored_value'],
                                    'road' => '绑卡有礼赠送',
                                    'create_time' => time(),
                                ];
                                Db::table($this->db . '.vip_stored_value')->insert($value_jilu_introducer);
                                $ew->value_promotes($write_off['stored_value'], $kaika);
                                unset($value_jilu_introducer);
                            }
                            if (!empty($write_off['coupon_code'])) {
                                $poupon = Db::table($this->db . '.vip_agive_coupon')->where('code', $write_off['coupon_code'])->select();
                                if (!empty($poupon)) {
                                    foreach ($poupon as $pon) {
                                        for ($i = 0; $i < $pon['coupon_number']; $i++) {
                                            $ew->couponAdd($pon['card_type'], $pon['coupon_code'], $user['code'], '绑卡有礼赠送');
                                        }
                                    }
                                }
                                unset($poupon);
                            }
                            Db::commit();
                            $res = true;
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                            $res = false;
                        }
                    } else {
                        $res = true;
                    }
                } else {
                    $res = true;
                }
                if ($res) {
                    webApi(200, 'ok');
                } else {
                    webApi(400, '登录失败');
                }
                
            } else {
                webApi(400, '登录失败');
            }
        } else {
            webApi(400, '没有获得到openid');
        }
    }

    /**
     * openID 员工登录入口
     */
    public function openid()
    {
        $openId = input('openId');
        if ($openId) {
            $staff = Db::table(input('company') . '.vip_staff')->where('openid',  $openId)->order('invalid_time', 'desc')->select();
            if (!empty($staff[0]) && $staff[0]['status'] != 0 && $staff[0]['is_auto_renew'] == 0) {
                webApi(400, 'no');
            }
            if (!empty($staff)) {
                if ($staff[0]) {
                    $loginData = [
                        // 'access_token' => md5(microtime(1) . $staff['code']),
                        // 'ua' => $_SERVER['HTTP_USER_AGENT'],
                        'invalid_time' => time() + 604800
                    ];
                    $res = Db::table(input('company') . '.vip_staff')->where('id', $staff[0]['id'])->update($loginData);
                    if (!$res) {
                        webApi(400, 'no');
                    }

                    $sessionInfo = [
                        'staff' => $staff[0]['code'],  // 员工工号、账号
                        'store' => $staff[0]['store_code'], // 所属门店编号
                        'org' => $staff[0]['org_code'], // 所属机构编号
                        'admin_org' => $staff[0]['admin_org_code'] // 管理机构编号（多个以逗号隔开）
                    ];
                    session('info', $sessionInfo);
                    unset($loginData, $res, $sessionInfo);
                    webApi(200, 'ok');
                } else {
                    webApi(400, 'no');
                }
            } else {
                webApi(400, 'no');
            }
        } else {
            webApi(400, 'no');
        }
    }

    /**
     * 微信聊天记录
     */
    public function wxChatRecord()
    {
                                          
        $code = input('code');
        $createtime = input('createtime');
        $db = input('company');
        $openId = Db::table($db . '.vip_viplist')->where('code', $code)->find()['openid'];
        if (empty($openId)) {
            $openId = 'null';
        }
        $company_substr = substr($db,-4);
        try {
            if ($db == 'ic' || is_numeric($company_substr)) {
                $url = 'https://wechat.suokeduo.com/api/msg/findMsg?fromUser=' . $openId . '&createTime=' . urlencode($createtime) . '&company=' . $db;
                $curl = file_get_contents($url);
                $data = json_decode($curl, true);
                foreach ($data as $k=>$v) { 
                    if ($v['msgType'] == 'image') {
                        $imgUrl = 'https://wechat.suokeduo.com/api/msg/downMedia?company='. $db .'&media_id=' . $v['mediaId'];
                        $imgCurl = file_get_contents($imgUrl);
                        $data[$k]['img'] = $imgCurl;
                    } else if ($v['msgType'] == 'voice') {
                        $voiceUrl = 'https://wechat.suokeduo.com/api/msg/downMedia?company='. $db .'&media_id=' . $v['mediaId'];
                        $voiceCurl = file_get_contents($voiceUrl);
                        $data[$k]['voice'] = $voiceCurl;
                    }
                }
            } else {
                $url = 'https://wxauth.suokeduo.com/modules/findMsg?openId=' . $openId . '&createtime=' . urlencode($createtime) . '&company=' . $db;
                // $url = 'https://wxauth.suokeduo.com/modules/findMsg?openId=ogx1nwV_uCStUY2a_HXB0rJpSLbQ&createtime=1574754253&company=suoke';
                $curl = file_get_contents($url);
                $data = json_decode($curl, true);
                foreach ($data as $k=>$v) { 
                    if ($v['msgType'] == 'image') {
                        $imgUrl = 'https://wxauth.suokeduo.com/card/downMedia?company='. $db .'&media_id=' . $v['mediaId'];
                        $imgCurl = file_get_contents($imgUrl);
                        $data[$k]['img'] = 'https://wxauth.suokeduo.com'.$imgCurl;
                    } else if ($v['msgType'] == 'voice') {
                        $voiceUrl = 'https://wxauth.suokeduo.com/card/downMedia?company='. $db .'&media_id=' . $v['mediaId'];
                        $voiceCurl = file_get_contents($voiceUrl);
                        $data[$k]['voice'] = 'https://wxauth.suokeduo.com'.$voiceCurl;
                    }
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        webApi(200, 'ok', $data);
    }

    /**
     * 微信群发
     */
    public function groupSending()
    {
        // $code = ['13773033340'];
        // $es = new ErpWhere($this->db, "");
        // $es->wxG($code, 'http://c.hiphotos.baidu.com/zhidao/pic/item/d009b3de9c82d1587e249850820a19d8bd3e42a9.jpg', 'image');
        // $es->wxG($code, '这是群发', 'text');

        $code = input('code');
        $content = input('content');
        $es = new ErpWhere($this->db, "");
        $es->wxG($code, $content, input('type')); 
        webApi(200, '发送成功');
    }

    /**
     * 微信发送文字消息
     */
    public function wxSendOutTxt()
    {
        $code = input('code');
        if (empty($code)) {
            webApi(400, '参数错误');
        }
        $openId = Db::table($this->db . '.vip_viplist')->field('openid,username')->where('code', $code)->find();
        if (empty($openId)) {
            webApi(400, '该会员没有关注公众号');
        }
        $content = input('content');
          
         
        $company_substr = substr($this->db,-4);

        if ($this->db == 'ic' || is_numeric($company_substr)) {
            $url = 'https://wechat.suokeduo.com/api/msg/sendKefuMsg';
        } else {
            $url = 'https://wxauth.suokeduo.com/coupon/sendKefuMsg';
        }
        unset($company_substr);        
        $map = [
            'company' => $this->db,
            'msgType' => 'txt',
            'imageType' => '',
            'openid' => $openId['openid'],
            'content' => $content
        ];
        $res = json_decode(doHttpPost($url, $map), true);
        unset($map, $url);
        if ($res['status'] == true) {
            $dece = [
            'return_visit' => time(),
            'customer_service' => session('info.staff')
            ];
            $visit_data = [
                'user_name' => $openId['username'],
                'user_code' => $code,
                'visit_operator' => session('info.staff'),
                'visit_mode' => '微信回访',
                'content' => $content,
                'time' => time()
            ];
            Db::table($this->db . '.vip_viplist')->where('code', $code)->setField($dece);
            Db::table($this->db . '.vip_returnvisit_record')->insert($visit_data);
            unset($res, $visit_data, $dece);
            webApi(200, 'ok');
        } else {
            webApi(400, '该会员没有和你说过话无法和他聊天');
        }
    }

    /**
     * 微信发送图片消息
     */
    public function wxSendOutImage()
    {
        $code = input('code');
        if (empty($code)) {
            webApi(400, '参数错误');
        }
        $openId = Db::table($this->db . '.vip_viplist')->field('openid,username')->where('code', $code)->find();
        if (empty($openId)) {
            webApi(400, '该会员没有关注公众号');
        }
        // 上传所需参数
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
            $url = $this->ssl . 'company.suokeduo.com/index.php/v1/webVipApiOss/';
            // 请求总后台上传接口
            $res = json_decode(doHttpPost($url, [
                'bus_account' => $this->db, 'type' => 'upload', 'file' => $path,
                'model' => input('model'), 'old_img' => strVal(input('old_img')), 'md5_file' => $md5File
            ]), true);
            if ($res['code'] == 200) {
                unset($info);
                is_file($path) && unlink($path);
                $img_url = $res['data'];
            } else {
                unset($info);
                is_file($path) && unlink($path);
                webApi(400, $res['msg']);
            }
        } else {
            $msg = '上传失败！' . $file->getError();
            webApi(400, $msg);
        }
        // 图片处理
        $ew = new ErpWhere($this->db, '');
        $baseImg = $ew->imgToBase64($img_url);

        $company_substr = substr($this->db,-4);

        if ( $this->db == 'ic' || is_numeric($company_substr)) {
            $url = 'https://wechat.suokeduo.com/api/msg/sendKefuMsg';
        } else {
            $url = 'https://wxauth.suokeduo.com/coupon/sendKefuMsg';
        }

        $map = [
            'company' => $this->db,
            'msgType' => 'image',
            'imageType' => $baseImg[1],
            'openid' => $openId['openid'],
            'content' => $baseImg[0]
        ];
        unset($baseImg, $ew);
        $res = json_decode(doHttpPost($url, $map), true);
        if ($res['status'] == true) {
            $dece = [
            'return_visit' => time(),
            'customer_service' => session('info.staff')
            ];
            $visit_data = [
                'user_name' => $openId['username'],
                'user_code' => $code,
                'visit_operator' => session('info.staff'),
                'visit_mode' => '微信回访',
                'content' => $img_url,
                'time' => time()
            ];
            Db::table($this->db . '.vip_viplist')->where('code', $code)->setField($dece);
            Db::table($this->db . '.vip_returnvisit_record')->insert($visit_data);
            unset($res, $dece, $openId, $img_url, $code, $visit_data);
            webApi(200, 'ok');
        } else {
            webApi(400, '该会员没有和你说过话无法和他聊天');
        }
    }

    /**
     * 短信群发
     */
    public function groupSmg()
    {
        $phone = input('phone');
        $count = input('content');
        $ew = new ErpWhere($this->db, '');
        $message_count = count($phone) * ceil($ew->abslength($count) / 64);
        $msg = Db::table('company.vip_business')->field('code, usable_msg')->where('code', $this->db)->find();
        $sms = Db::table('company.vip_sms_autograph')->field('sms_autograph, business_code')->where('business_code', $this->db)->find();
        if (empty($sms)) {
            webApi(400, '短信签名未配置，请联系管理员进行配置');
        }
        //判断短信条数
        if ($msg['usable_msg'] < $message_count) {
            webApi(400, '短信条数不足，可用短信为' . $msg['usable_msg'] . '条' . '，当前发送短信为' . $message_count . '条');
        }
        $data = $ew->shortMessage($phone, '【'.$sms['sms_autograph'].'】'.$count);
        unset($phone, $count, $ew, $msg, $sms);
        if ($data == 1) {
            Db::table('company.vip_business')->where('code', $this->db)->setDec('usable_msg', $message_count);
            webApi(200, '发送成功');
        } else {
            webApi(400, $data);
        }
    }

}