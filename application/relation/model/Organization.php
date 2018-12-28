<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2018/12/26
 * Time: 17:21
 */

namespace app\relation\model;
use think\Model;

class Organization extends Model
{
    //自动修改、更新时间戳
    protected $autoWriteTimestamp = true;
    //返回结果集
    protected $resultSetType = 'collection';
}