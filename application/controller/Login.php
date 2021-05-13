<?php

namespace app\controller;

use think\Db;
use think\Controller;

/**
 * Author lhp
 * Date 2019/06/21
 * Description 登录
 */
class Login extends Controller
{
    // protected $ssl = 'http://';
    // protected function initialize()
    // {
    //     if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
    //         $this->ssl = 'https://';
    //     }
    // }
    /**
     * 获取数据库信息
     */
    public function database()
    {
        session('info', null);
        session('database', null);
        $database = input('database');
        if (!empty($database)) {
            $session = [
                'db' => $database
            ];
            session('database', $session);
            $data = Db::table('company.vip_business')->field('x_status')->where('code', $database)->find();
            webApi(200, 'ok', $data);
        } else {
            webApi(400, 'no');
        }
        
    }

    /**
     * 登录
     */
    public function login()
    {
        if (input('username') == '' || input('password') == '') {
            webApi(400, '缺少参数');
        }

        $user = Db::table(session('database.db'). '.vip_staff')->where('code', input('username'))->find();

        if (empty($user)) {
            $user = Db::table(session('database.db') .'.vip_staff')->where('phone', input('username'))->find();
        }

        if ($user['status'] != 0) {
            webApi(400, '该用户属于离职状态');
        }
       
        if (!$user || $user['password'] != md5(input('password'))) {
            webApi(400, '用户名或密码错误');
        }

        if ($user[ 'm_status'] != 1) {
            webApi(400, '该用户手机端登录权限未开启');
        }

        if ($user['exp_time'] <= time()) {
            webApi(400, '该用户账号已到期');
        }

        $loginData = [
            // 'access_token' => md5(microtime(1) . $user['code']),
            // 'ua' => $_SERVER['HTTP_USER_AGENT'],
            'invalid_time' => time() + 604800,
            'openid' => input('openId'),
            'is_auto_renew' => 1
        ];
        $res = Db::table(session('database.db'). '.vip_staff')->where('id', $user['id'])->update($loginData);
        if (!$res) {
            webApi(400, '登录失败,请重试');
        }

        $sessionInfo = [
            'staff' => $user['code'],  // 员工工号、账号
            'store' => $user['store_code'], // 所属门店编号
            'org' => $user['org_code'], // 所属机构编号
            'admin_org' => $user['admin_org_code'] // 管理机构编号（多个以逗号隔开）
        ];
        session('info', $sessionInfo);

        webApi(200, 'yes');
    }

    /**
     * 切换账号
     */
    public function logout()
    {
        Db::table(session('database.db'). '.vip_staff')->where('code', session('info.staff'))->setField('openid', '');
        if (!empty(session('info'))) {
            session('info', null);
            session('database', null);
        }
        webApi(0);
    }

    // 修改密码
    public function setMyselfPass()
    {
        $info = Db::table( session('database.db'). '.vip_staff')->where('code', session('info.staff'))->find();
        if ($info['password'] != md5(input('oldPass'))) {
            webApi(400, 'no', '原密码不正确');
        }
        unset($info);
        // 启动事务
        Db::startTrans();
        try {
            Db::table(session('database.db') . '.vip_staff')->where('code', session('info.staff'))->setField('password', md5(input('password')));
            Db::table(session('database.db') . '.vip_staff')->where('code', session('info.staff'))->update(['is_auto_renew' => 0]);
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $res = false;
        }
        if ($res !== false) {
            webApi(200, 'ok', '修改成功');
        } else {
            webApi(400, 'no', '修改失败');
        }
    }

}
