<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Route;

//组织架构前端接口
Route::rule('api/getOrganization','relation/Organization/getOrganization','GET');
Route::rule('api/insertOrganization','relation/Organization/insertOrganization','POST');
Route::rule('api/importOrganization','relation/Organization/importOrganization','POST');
Route::rule('api/updateOrganization','relation/Organization/updateOrganization','POST');
Route::rule('api/deleteOrganization','relation/Organization/deleteOrganization','POST');

return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[hello]'     => [
        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
        ':name' => ['index/hello', ['method' => 'post']],
    ],

];
