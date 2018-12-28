<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2018/12/27
 * Time: 10:12
 */

namespace app\index\behavior;
use think\Response;

class CORS
{
    public function appInit(&$params)
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: token,Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: POST,GET');
        if(request()->isOptions()){
            exit();
        }
    }
}