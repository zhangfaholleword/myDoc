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

//标准角色前端接口
Route::rule('api/getRole','relation/Role/getRole','GET');
Route::rule('api/insertRole','relation/Role/insertRole','POST');
Route::rule('api/importRole','relation/Role/importRole','POST');
Route::rule('api/updateRole','relation/Role/updateRole','POST');
Route::rule('api/deleteRole','relation/Role/deleteRole','POST');

//用户管理前端接口
Route::rule('api/getUserInfoInRole','relation/User/getUserInfoInRole','GET');
Route::rule('api/getUserInfoInGroup','relation/User/getUserInfoInGroup','GET');
Route::rule('api/getUserInfo','relation/User/getUserInfo','GET');
Route::rule('api/createUser','relation/User/createUser','POST');
Route::rule('api/deleteUser','relation/User/deleteUser','POST');
Route::rule('api/updateUser','relation/User/updateUser','POST');

//文件夹管理接口
Route::rule('api/getFolder','document/Folder/getFolder','GET');
Route::rule('api/createFolder','document/Folder/createFolder','POST');
Route::rule('api/deleteFolder','document/Folder/deleteFolder','POST');
Route::rule('api/updateFolder','document/Folder/updateFolder','POST');

//文件管理接口
Route::rule('api/getUploadByMe','document/Document/getUploadByMe','GET');
Route::rule('api/getShareForMe','document/Document/getShareForMe','GET');
Route::rule('api/getShareRecord','document/Document/getShareRecord','GET');
Route::rule('api/deleteUploadByMe','document/Document/deleteUploadByMe','POST');
Route::rule('api/deleteShareForMe','document/Document/deleteShareForMe','POST');
Route::rule('api/updateShareRecord','document/Document/updateShareRecord','POST');
Route::rule('api/saveShareDoc','document/Document/saveShareDoc','POST');
Route::rule('api/upload','document/Document/upload','POST');
Route::rule('api/share','document/Document/share','POST');

//权限管理接口
Route::rule('api/createPermission','permission/Permission/createPermission','POST');

return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[hello]'     => [
        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
        ':name' => ['index/hello', ['method' => 'post']],
    ],
];
