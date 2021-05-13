<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/**
 * @param int $code 状态码 成功200 失败400 参数错误406
 * @param string $msg 返回信息|提示信息
 * @param int $count 数据长度|数据条数
 * @param array $data 返回数据
 */
function webApi($code = 200, $msg = '', $data = []) {
    $return = [
        'code' => $code,
        'msg' => $msg,
        'data' => $data,
    ];
    echo json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * [http_curl description]
 * @param  [type] $url  [description]
 * @param  string $type [description]
 * @param  string $res  [description]
 * @param  string $arr  [description]
 * @return [type]       [description]
 */
function http_curl($url, $type = 'get', $res = 'json', $arr = '') {
    // 1.初始化curl
    $ch = curl_init();
    // 2.设置curl参数
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($type == 'post') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $arr);
    }
    // 3.采集
    $output = curl_exec($ch);
    // 4.关闭
    curl_close($ch);
    if ($res == 'json') {
        return json_decode($output, true);
    }
}

/**
 * http post 请求
 */
function doHttpPost($url, $params) {
    $curl = curl_init();
    $response = false;
    do{
        // 1. 设置HTTP URL (API地址)
        curl_setopt($curl, CURLOPT_URL, $url);
        // 2. 设置HTTP HEADER (表单POST)
        $head = array(
            'Content-Type: application/x-www-form-urlencoded'
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $head);
        // 3. 设置HTTP BODY (URL键值对)
        $body = http_build_query($params);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        // 4. 调用API，获取响应结果
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_NOBODY, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        if ($response === false){
            $response = false;
            break;
        }
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($code != 200){
            $response = false;
            break;
        }
    } while (0);
    curl_close($curl);
    return $response;
}

/**
 * @param array $list 数据 必须传值
 * @param string $pk 主键 默认主键为id,可自行传入
 * @param string $pid 父键/父类标识 默认父类标识为pid,可自行传入
 * @param string $child 子类下标 children, 可自行传入
 * @return array 返回处理好的父子递进数据
 * @Description 回调函数
 */
function getTree($items, $pk = 'id', $pid = 'pid', $child = 'children') {

    [$map, $tree] = [[], []];
    foreach ($items as &$it) { 
        $map[$it[$pk]] = &$it; //数据的ID名生成新的引用索引树
    }

    foreach ($items as &$at){
        $parent = &$map[$at[$pid]];
        if($parent) {
            $parent[$child][] = &$at;
        }else{
            $tree[] = &$at;
        }
    }
    return $tree;
}