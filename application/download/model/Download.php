<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2019/1/14
 * Time: 17:11
 */

namespace app\download\model;
use think\Model;

class Download extends Model
{
    //自动修改、更新时间戳
    protected $autoWriteTimestamp = true;
    //返回结果集
    protected $resultSetType = 'collection';
}