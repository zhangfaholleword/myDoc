<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2019/1/14
 * Time: 17:12
 */

namespace app\download\controller;
use app\download\model\Download as DownloadModel;
use think\ResponseStructure;

class Download extends ResponseStructure
{
    /**
     * @frontAPI
     * 新建权限
     */
    public function createDownload(){
        //判断接口方式
        if($this->request->isPost()){
            $param = $this->request->post();
            //必填项是否填写
            ($param['formData']['download_id'])
                ? '':$this->res->clientError(105);
            //对$param进行一定处理（字符类型匹配）
            $download = new DownloadModel($param['formData']);
            //formData中下载名
            $download->allowField(true)->save();
            //通过获取新插入数据主键 判断插入是否成功
            ($download->id) ? $this->res->resToMsg("新增一条下载数据成功",$download->id):
                $this->res->clientError(104);
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 删除一条权限
     */
    public function deleteDownload(){
        if($this->request->isPost()){
            $param = $this->request->post();
            //直接使用静态方法
            $result = DownloadModel::destroy($param['id']);
            ($result) ? $this->res->resToMsg("删除下载数据成功",$result):
                $this->res->clientError(109);
        }
        $this->res->clientError(103);
    }
}