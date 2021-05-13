<?php

namespace app\controller;

use think\Db;
use think\Controller;

class Wechat
{
    protected $appid = 'wx8b0191530fb8df65';
    protected $secret = '10fecaa2792e5ead9e447fca689ad5ea';

    public function index()
	{
		// 1.获得参数 signature nonce token timestamp echostr
		$token = 'sorks';
		$nonce = input('nonce');
		$timestamp = input('timestamp');
		$signature = input('signature');
		$echostr = input('echostr');
		// 2.字典序排序并加密
		$array = [];
		$array = [$nonce, $timestamp, $token];
		sort($array);
		$str = sha1(implode('', $array));
		if ($str == $signature) {
			echo $echostr;
			exit;
		} else {
			$this->responseMsg();
		}
    }
    
    /**
	 * [responseMsg 关注事件/扫码事件]
	 */
	public function responseMsg()
    {
    	// 获取数据
		$postArr = file_get_contents('php://input');
		libxml_disable_entity_loader(true);
    	// 处理消息类型
    	$postObj = simplexml_load_string($postArr, 'SimpleXMLElement', LIBXML_NOCDATA);
		// 回复用户消息
		$toUser = $postObj->FromUserName;
		$fromUser = $postObj->toUserName;
		$time = time();
		$msgType = 'text';
		$template = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[%s]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					</xml>";
    	if (strtolower($postObj->MsgType) == 'event') {
    		// 如果是关注事件
    		if (strtolower($postObj->Event) == 'subscribe') {
                // 如果EventKey存在 说明是扫码进来的
                if (!empty($postObj->EventKey)) {
                	// 获取识别信息
              		$identify = substr($postObj->EventKey, 8);
                }
                $content = '欢迎关注liuliu测试号';
    			// 消息模板 请求消息的用户 公众号 时间戳 消息类型 内容
                $info = sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
                echo $info;	
    		}
    	}
    }

    /**
	 * [setItem 设置微信菜单]
	 */
	public function setItem()
	{
		$access_token = $this->getWxAccessToken();
		$url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
		$json = '{
					"button":[
					{
						"type":"view",
						"name":"首页",
						"url":"http://wx.sorks.cn/"
					},
					{
						"name":"菜单",
						"sub_button":[
						{
							"type":"view",
							"name":"菜单一",
							"url":"http://wx.sorks.cn/"
						},
						{
							"type":"view",
							"name":"菜单二",
							"url":"http://wx.sorks.cn/"
						}]
					},
					{
						"type":"view",
						"name":"我",
						"url":"http://wx.sorks.cn/"
					}]
				}';
        http_curl($url, 'post', 'json', $json);
	}

    /**
	 * [getWxAccessToken 获取access_token]
	 */
	public function getWxAccessToken()
	{
		if (cache('?wx_access_token') == true) {
			$access_token = cache('wx_access_token');
		} else {
			// 1.请求url地址
			$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->secret;
			$res = http_curl($url, 'get', 'json');
			$access_token = $res['access_token'];
			cache('wx_access_token', $access_token, 7100);
		}
		return $access_token;
	}
}