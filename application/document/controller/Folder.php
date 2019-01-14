<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2019/1/4
 * Time: 15:05
 */

namespace app\document\controller;

use app\document\model\Folder as FolderModel;
use think\ResponseStructure;

class Folder extends ResponseStructure
{
    /**
     * @frontAPI
     * 获取所有文件夹列表
     */
    public function getFolder(){
        if($this->request->isGet()){
            $param = $this->request->get();
            $pidArr = explode(",",$param['pids']);
            //String数组转成Int数组
            $newPid = array();
            foreach ($pidArr as $key=>$value){
                array_push($newPid,(int)$value);
            }
            if(count($pidArr) == 1 && $pidArr[0] == 0) {
                $result = FolderModel::where('pid','=',0)->select();
            }else if(count($pidArr) == 1 && $pidArr[0] != 0){
                $result = FolderModel::where('pid','=',$newPid[0])->select();
            }else{
                $result = FolderModel::where('pid','in',$newPid)->select();
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
     * 新建文件夹
     */
    public function createFolder(){
        //判断接口方式
        if($this->request->isPost()){
            $param = $this->request->post();
            //必填项是否填写
            ($param['formData']['folder_name'])
                ? '':$this->res->clientError(105);
            //插入的数据是否已经存在(folder_name)
            $existence = FolderModel::where("folder_name",$param['formData']['folder_name'])->find();
            ($existence) ? $this->res->clientError(106):'';
            //对$param进行一定处理（字符类型匹配）
            $folder = new FolderModel($param['formData']);
            //formData中标准角色名
            $folder->allowField(true)->save();
            //通过获取新插入数据主键 判断插入是否成功
            ($folder->id) ? $this->res->resToMsg("插入一条标准角色成功",$folder->id):
                $this->res->clientError(104);
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 修改文件夹信息
     */
    public function updateFolder(){
        if($this->request->isPost()){
            $param = $this->request->post();
            //必填项是否填写
            ($param['formData']['folder_name'])
                ? '':$this->res->clientError(105);
            //对$param进行一定处理（字符类型匹配）
            $folder = new FolderModel;
            //formData中文件夹名称
            $folder->isUpdate(true)->save($param['formData']);
            //通过获取新插入数据主键 判断插入是否成功
            ($folder->id) ? $this->res->resToMsg("修改文件夹信息成功",$folder->id):
                $this->res->clientError(104);
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 删除文件夹
     */
    public function deleteFolder(){
        if($this->request->isPost()){
            $param = $this->request->post();
            //直接使用静态方法
            $result = FolderModel::destroy($param['id']);
            ($result) ? $this->res->resToMsg("删除文件夹成功",$result):
                $this->res->clientError(109);
        }
        $this->res->clientError(103);
    }

}