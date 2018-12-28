<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2018/12/26
 * Time: 17:26
 */

namespace app\relation\controller;

use app\index\controller\Index;
use app\relation\model\Organization as OrganizationModel;
use think\ResponseStructure;

class Organization extends ResponseStructure
{
    /**
     * @frontAPI
     * 获取所有组织架构列表
     */
    public function getOrganization(){
        if($this->request->isGet()){
            $param = $this->request->get();
            $result = OrganizationModel::where('id','=',27)->select();
            (!is_array($result)) ? $this->res->resToMsg("",$result,200):
                $this->res->clientError(110);
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 插入一条组织架构信息
     */
    public function insertOrganization(){
        //判断接口方式
        if($this->request->isPost()){
            $param = $this->request->post();
            //必填项是否填写
            ($param['formData']['organization_name'] && $param['formData']['organization_code'])
                ? '':$this->res->clientError(105);
            //插入的数据是否已经存在(organization_code)
            $existence = OrganizationModel::where("organization_code",$param['formData']['organization_code'])->find();
            ($existence) ? $this->res->clientError(106):'';
            //对$param进行一定处理（字符类型匹配）
            $organization = new OrganizationModel($param['formData']);
            //formData中组织架构名
            $organization->allowField(true)->save();
            //通过获取新插入数据主键 判断插入是否成功
            ($organization->id) ? $this->res->resToMsg("插入一条组织架构成功",$organization->id):
                $this->res->clientError(104);
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 导入excel 组织架构信息
     */
    public function importOrganization(){
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
        $data_field = ['organization_name','organization_code','pid','description','status'];
        $data = Import::relationUpload($filename,$extension);
        $newArr = array();
        //整理导入格式
        /**
         * (optimization)
         * 需要优化: 导入的时候已经存在的数据不需要再插入
         */
        foreach ($data as $key=>$value){
            $temp = array();
            //'organization_name','organization_code'是必填字段
            if(($value[0] && $value[1])){
                foreach ($value as $index=>$item){
                    $temp[$data_field[$index]] = $item;
                }
            }else
                $this->res->clientError(107);
            array_push($newArr,$temp);
        }
        $organizationModel = new OrganizationModel;
        $res = $organizationModel->saveAll($newArr);
        $this->res->resToMsg("导入组织架构成功",count($res));
    }

    /**
     * @frontAPI
     * 修改一条组织架构信息
     */
    public function updateOrganization(){
        if($this->request->isPost()){
            $param = $this->request->post();
            //必填项是否填写
            ($param['formData']['organization_name'] && $param['formData']['organization_code'])
                ? '':$this->res->clientError(105);
            //对$param进行一定处理（字符类型匹配）
            $organization = new OrganizationModel;
            //formData中组织架构名
            $organization->isUpdate(true)->save($param['formData']);
            //通过获取新插入数据主键 判断插入是否成功
            ($organization->id) ? $this->res->resToMsg("更新一条组织架构成功",$organization->id):
                $this->res->clientError(104);
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 删除一条组织架构信息
     */
    public function deleteOrganization(){
        if($this->request->isPost()){
            $param = $this->request->post();
            //直接使用静态方法
            $result = OrganizationModel::destroy($param['id']);
            ($result) ? $this->res->resToMsg("更新一条组织架构成功",$result):
                $this->res->clientError(109);
        }
        $this->res->clientError(103);
    }
}