<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2019/1/15
 * Time: 9:43
 */

namespace app\menu\model;
use think\Model;

class MenuList extends Model
{
    //自动修改、更新时间戳
    protected $autoWriteTimestamp = true;
    //返回结果集
    protected $resultSetType = 'collection';
}