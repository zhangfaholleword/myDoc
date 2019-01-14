<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2018/12/26
 * Time: 17:26
 */

namespace app\relation\controller;

use app\relation\model\Role as RoleModel;
use think\ResponseStructure;

class Role extends ResponseStructure
{
    /**
     * @frontAPI
     * 获取所有标准角色列表
     */
    public function getRole(){
        if($this->request->isGet()){
            $param = $this->request->get();
            $pidArr = explode(",",$param['pids']);
            //String数组转成Int数组
            $newPid = array();
            foreach ($pidArr as $key=>$value){
                array_push($newPid,(int)$value);
            }
            if(count($pidArr) == 1 && $pidArr[0] == 0) {
                $result = RoleModel::where('pid','=',0)->select();
            }else if(count($pidArr) == 1 && $pidArr[0] != 0){
                $result = RoleModel::where('pid','=',$newPid[0])->select();
            }else{
                $result = RoleModel::where('pid','in',$newPid)->select();
            }
            $resultArr = json_decode(json_encode($result));
            if(count($resultArr) > 0)  $this->checkIfEnd($resultArr);
            (count($resultArr) > 0) ? $this->res->resToMsg("",$result,200):
                $this->res->clientError(110);
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 插入一条标准角色信息
     */
    public function insertRole(){
        //判断接口方式
        if($this->request->isPost()){
            $param = $this->request->post();
            //必填项是否填写
            ($param['formData']['role_name'] && $param['formData']['role_code'])
                ? '':$this->res->clientError(105);
            //插入的数据是否已经存在(role_code)
            $existence = RoleModel::where("role_code",$param['formData']['role_code'])->find();
            ($existence) ? $this->res->clientError(106):'';
            //对$param进行一定处理（字符类型匹配）
            $role = new RoleModel($param['formData']);
            //formData中标准角色名
            $role->allowField(true)->save();
            //通过获取新插入数据主键 判断插入是否成功
            ($role->id) ? $this->res->resToMsg("插入一条标准角色成功",$role->id):
                $this->res->clientError(104);
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 导入excel 标准角色信息
     */
    public function importRole(){
        //获取表单上传文件
        $file = request()->file('excel');
        $info = $file->validate(['ext' => 'xlsx,xls'])->move(ROOT_PATH . 'public' . DS . 'upload' . DS . 'Import');
        (!$info) ? $this->res->clientError(108):'';
        //数据为空返回错误
        if(empty($info)){
            $output['status'] = false;
            $output['info'] = '导入数据失败~';
            $this->ajaxReturn($output);
        }
        //获取文件名
        $exclePath = $info->getSaveName();
        //上传文件的地址
        $filename = ROOT_PATH . 'public' . DS . 'upload' . DS . 'Import'. DS . $exclePath;
        //判断截取文件
        $extension = strtolower( pathinfo($filename, PATHINFO_EXTENSION) );
        //导入文件数据整理
        $data_field = ['role_name','role_code','pid','description','status'];
        $data = Import::relationUpload($filename,$extension);
        $newArr = array();
        //整理导入格式
        /**
         * (optimization)
         * 需要优化: 导入的时候已经存在的数据不需要再插入
         */
        foreach ($data as $key=>$value){
            $temp = array();
            //'role_name','role_code'是必填字段
            if(($value[0] && $value[1])){
                foreach ($value as $index=>$item){
                    $temp[$data_field[$index]] = $item;
                }
            }else
                $this->res->clientError(107);
            array_push($newArr,$temp);
        }
        $roleModel = new RoleModel;
        $res = $roleModel->saveAll($newArr);
        $this->res->resToMsg("导入标准角色成功",count($res));
    }

    /**
     * @frontAPI
     * 修改一条标准角色信息
     */
    public function updateRole(){
        if($this->request->isPost()){
            $param = $this->request->post();
            //必填项是否填写
            ($param['formData']['role_name'] && $param['formData']['role_code'])
                ? '':$this->res->clientError(105);
            //对$param进行一定处理（字符类型匹配）
            $role = new RoleModel;
            //formData中标准角色名
            $role->isUpdate(true)->save($param['formData']);
            //通过获取新插入数据主键 判断插入是否成功
            ($role->id) ? $this->res->resToMsg("更新一条标准角色成功",$role->id):
                $this->res->clientError(104);
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 删除一条标准角色信息
     */
    public function deleteRole(){
        if($this->request->isPost()){
            $param = $this->request->post();
            //直接使用静态方法
            $result = RoleModel::destroy($param['id']);
            ($result) ? $this->res->resToMsg("删除一条标准角色成功",$result):
                $this->res->clientError(109);
        }
        $this->res->clientError(103);
    }
}