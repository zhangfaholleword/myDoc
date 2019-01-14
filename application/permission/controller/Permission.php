<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2019/1/9
 * Time: 16:16
 */

namespace app\permission\controller;
use app\permission\model\Permission as PermissionModel;
use think\ResponseStructure;
use think\Db;

class Permission extends ResponseStructure
{
    /**
     * @frontAPI
     * 新建权限
     */
    public function createPermission(){
        //判断接口方式
        if($this->request->isPost()){
            $param = $this->request->post();
            //必填项是否填写
            ($param['formData']['permission_name'])
                ? '':$this->res->clientError(105);
            //插入的数据是否已经存在(permission_name)
            $existence = PermissionModel::where("permission_name",$param['formData']['permission_name'])->find();
            ($existence) ? $this->res->clientError(106):'';
            //对$param进行一定处理（字符类型匹配）
            $permission = new PermissionModel($param['formData']);
            //formData中标准角色名
            $permission->allowField(true)->save();
            //通过获取新插入数据主键 判断插入是否成功
            ($permission->id) ? $this->res->resToMsg("新增权限名称成功",$permission->id):
                $this->res->clientError(104);
        }
        $this->res->clientError(103);
    }
}