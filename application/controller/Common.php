<?php

namespace app\controller;

use think\Controller;

class Common extends Controller
{
    protected $db;
    protected $ssl = 'http://';
    
    public function initialize()
    {
        // 判断http
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $this->ssl = 'https://';
        }
        
        $this->db = session('database.db');
        //$this->db = 'suoke';
    }

}