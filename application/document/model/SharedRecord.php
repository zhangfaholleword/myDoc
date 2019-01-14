<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2019/1/9
 * Time: 16:37
 */

namespace app\document\model;
use think\Model;

class SharedRecord extends Model
{
    //自动修改、更新时间戳
    protected $autoWriteTimestamp = true;
    //返回结果集
    protected $resultSetType = 'collection';
}