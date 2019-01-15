<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2019/1/4
 * Time: 15:05
 */

namespace app\document\controller;

use app\document\model\Document as DocumentModel;
use app\document\model\SharedRecord as SharedRecordModel;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\ResponseStructure;
use think\Db;

class Document extends ResponseStructure
{
    /**
     * @frontAPI
     * 文件上传接口（*****）
     */
    public function upload(){
        //判断接口方式
        if($this->request->isPost()) {
            //上传文件获取
            $files = request()->file('files');
            //文件长传文件夹
            $folder = $this->request->post()['folder'];
            //上传人
            $uid = $this->request->post()['uid'];
            //分享方式
            $sharing_method = $this->request->post()['sharing_method'];
            //分享key集合
            $sharing_arr = $this->request->post()['sharing_arr'];
            //分享所分配权限
            $permission_arr = explode(',',$this->request->post()['permission_arr']);
            //分享时期
            $sharing_deadline = $this->request->post()['sharing_deadline'];
            //自定义上传验证(验证同级文件夹下是否存在完全相同内容或者名字)
            try {
                $this->uploadValidation($files, $folder);
            } catch (DataNotFoundException $e) {
                dump($e);
            } catch (ModelNotFoundException $e) {
                dump($e);
            } catch (DbException $e) {
                dump($e);
            }
            //验证成功，存储文件
            $all_doc = $this->uploadSave($files,$folder);
            // 分享记录整合
            $sharing_record = array();
            $sharing_record['sharing_method'] = $sharing_method;
            $sharing_record['sharing_arr'] = $sharing_arr;
            $sharing_record['sharing_uid'] = $uid;
            $sharing_record['sharing_deadline'] = $sharing_deadline;
            // 启动事务 复杂的上传
            Db::startTrans();
            try{
                //1.批量插入
                $user = new DocumentModel;
                $insertArr = $user->saveAll($all_doc);
                //2.获取文件上传成功的id的集合 $idArr用于添加用户-文件关系表
                $idArr = array();
                foreach ($insertArr as $key=>$value){
                    array_push($idArr,(int)$value['id']);
                }
                $user_doc = array();
                foreach ($idArr as $value){
                    array_push($user_doc,["doc_id"=>$value,"user_id"=>$uid]);
                }
                Db::table("document_user")->insertAll($user_doc);
                //3.添加分享记录
                $sharedModel = new SharedRecordModel;
                $sharedModel->data($sharing_record);
                $sharedModel->save(); //获取分享记录的key
                $shard_id = $sharedModel->id;
                //4.分享记录和权限的关联
                $sharing_permission = array();
                foreach ($permission_arr as $value){
                    array_push($sharing_permission,["sharing_id" => $shard_id,"permission_id" => $value]);
                }
                Db::table("sharing_permission")->insertAll($sharing_permission);
                //5.分享记录和文件的关联
                $sharing_doc = array();
                foreach ($idArr as $value){
                    array_push($sharing_doc,["sharing_id" => $shard_id,"doc_id" => $value]);
                }
                Db::table("document_shared")->insertAll($sharing_doc);
                //6.分享记录和用户的关联(分享方式、所分享的id集合)
                $this->uploadSharing($shard_id,$sharing_method,$sharing_arr);
                // 提交事务
                Db::commit();
                $this->res->resToMsg("上传文件成功",'');
            } catch (\Exception $e) {
                // 回滚事务
                dump($e);
                Db::rollback();
            }
        }
    }

    /**
     * @frontAPI
     * 获取用户自身上传的文件
     */
    public function getUploadByMe(){
        if($this->request->isGet()){
            $param = $this->request->get();
            try {
                $result = array();
                //获取指定文件夹下文件
                if (isset($param['uid']) && isset($param["folder"])) {
                    $uid = $param['uid'];$folder = $param["folder"];
                    $result = Db::table('document_user')->alias('a')->join('document b', 'a.doc_id = b.id', 'RIGHT')
                        ->where('user_id', $uid)
                        ->where('doc_folder', $folder)
                        ->select();
                }
                //获取所有上传文件
                if (isset($param['uid']) && !isset($param["folder"])) {
                    $uid = $param['uid'];
                    $result = Db::table('document_user')->alias('a')->join('document b', 'a.doc_id = b.id', 'RIGHT')
                        ->where('user_id', $uid)
                        ->select();
                }
                if($result) $this->res->resToMsg("",$result,200);
                $this->res->clientError(113);
            } catch (DataNotFoundException $e) {
                dump($e);
            } catch (ModelNotFoundException $e) {
                dump($e);
            } catch (DbException $e) {
                dump($e);
            }
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 获取其他用户分享的文件
     */
    public function getShareForMe(){
        if($this->request->isGet()){
            $param = $this->request->get();
            $uid = $param['uid'];
            try {
                $shareArr = Db::table('shared_user')
                    ->where('user_id', $uid)
                    ->field('sharing_id')
                    ->select();
                $result = array();
                if(isset($uid) && isset( $param["folder"])){
                    $folder = $param["folder"];
                    $result = Db::table('document_shared')->alias('a')->join('document b', 'a.doc_id = b.id', 'RIGHT')
                        ->where('sharing_id', 'in', array_column($shareArr, 'sharing_id'))
                        ->where('doc_folder', $folder)
                        ->select();
                }
                if(isset($uid) && !isset( $param["folder"])){
                    $result = Db::table('document_shared')->alias('a')->join('document b', 'a.doc_id = b.id', 'RIGHT')
                        ->where('sharing_id', 'in', array_column($shareArr, 'sharing_id'))
                        ->select();
                }
//                $result = $this->array_unique_key($result,'id');
                if($result) $this->res->resToMsg("",$result,200);
                $this->res->clientError(113);
            } catch (DataNotFoundException $e) {
                dump($e);
            } catch (ModelNotFoundException $e) {
                dump($e);
            } catch (DbException $e) {
                dump($e);
            }
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 获取指定用户的分享记录
     */
    public function getShareRecord(){
        if($this->request->isGet()){
            $param = $this->request->get();
            $uid = $param['uid'];
            $recordModel = new SharedRecordModel();
            $result = $recordModel->where('sharing_uid',$uid)->select()->toArray();
            ($result) ? $this->res->resToMsg("",$result,200):
            $this->res->clientError(113);
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 修改分享权限\有效时间
     */
    public function updateShareRecord(){
        if($this->request->isPost()){
            $param = $this->request->post();
            $sharingId = $param['sharingId'];
            $sharingDeadline = $param['sharingDeadline'];
            $permissionId = $param['permissionId'];

            $oldPermission = array();
            //查找现有权限
            try {
                $result = Db::table('sharing_permission')
                    ->where('sharing_id', $sharingId)
                    ->field('permission_id')
                    ->select();
                ($result) ? $oldPermission = array_column($result,'permission_id'):
                    $this->res->clientError(100,200,'没有权限');
            } catch (DataNotFoundException $e) {dump($e);
            } catch (ModelNotFoundException $e) {dump($e);
            } catch (DbException $e) {dump($e);
            }

            //整合权限更新
            $newPermission = explode(',',$permissionId);
            $intersect = array_intersect($oldPermission,$newPermission);
            $addRole = array_diff($newPermission,$intersect);
            $deleteRole = array_diff($oldPermission,$intersect);
            // 启动事务
            Db::startTrans();
            try{
                $recordModel = new SharedRecordModel;
                $recordModel->where('id', $sharingId)
                    ->update(['sharing_deadline' => $sharingDeadline]);
                if($addRole){
                    $insertRole = array();
                    foreach ($addRole as $key=>$value){
                        array_push($insertRole,['sharing_id' => $sharingId , 'permission_id' => $value]);
                    }
                    Db::table('sharing_permission')->insertAll($insertRole);
                }
                if($deleteRole){
                    Db::table('sharing_permission')
                        ->where('sharing_id',$sharingId)
                        ->where('permission_id','in',$deleteRole)
                        ->delete();
                }
                // 提交事务
                Db::commit();
                $this->res->resToMsg('分享信息修改成功',200);
            } catch (\Exception $e) {
                // 回滚事务
                dump($e);
                Db::rollback();
            }
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 删除自身上传文件
     */
    public function deleteUploadByMe(){
        if($this->request->isPost()){
            $param = $this->request->post();
            $docId = $param['docId'];
            try {
                //删除系统中的存储文件
                $res = Db::table('doc_document')
                    ->where("id", 'in', explode(',',$docId))
                    ->field('doc_url')
                    ->select();
                $deleteArr = array_column($res,'doc_url');
                foreach ($deleteArr as $item){
                    //todo 删掉前需要判断文件状态，是否正在被打开中
                    unlink(ROOT_PATH . 'public/uploads/'.$item);
                }
                $result = Db::table('doc_document')
                    ->where("id", 'in', explode(',',$docId))
                    ->delete();
                if($result){
                    $this->res->resToMsg("",$result,200);
                }$this->res->clientError(109);
            } catch (PDOException $e) {
                dump($e);
            } catch (Exception $e) {
                dump($e);
            }
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 删除拥有的分享文件
     * @throws \think\Exception
     */
    public function deleteShareForMe(){
//        if($this->request->isPost()){
//            $param = $this->request->post();
//            $uid = $param['uid'];$docId = $param['docId'];
//            try {
//                //查找
//                $result = Db::table('shared_user')
//                    ->alias('a')
//                    ->join(['document_shared' => 'b'], 'a.sharing_id = b.sharing_id')
//                    ->where("user_id", $uid)
//                    ->where("doc_id", $docId)
//                    ->select();
//                //删除关联
//                //todo(循环连接数据库，优化：确保同一文件分享给同一人只一份儿)
//                $arr = [
//                    "sharing_id" => $result[0]['sharing_id'],
//                    "doc_id" => $result[0]['doc_id'],
//                ];
//                $res = Db::table("document_shared")->where($arr)->delete();
//                if($res) $this->res->resToMsg("",$res,200);
//                $this->res->clientError(109);
//            } catch (DataNotFoundException $e) {
//                dump($e);
//            } catch (ModelNotFoundException $e) {
//                dump($e);
//            } catch (DbException $e) {
//                dump($e);
//            }
//        }
//        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 分享文件(*****)
     */
    public function share(){
        if($this->request->isPost()){
            $param = $this->request->post();
            $uid = $param['uid'];
            $docId = explode(',',$param['docId']);
            $sharing_method = $param['sharing_method'];
            $sharing_arr = explode(',',$param['sharing_arr']);
            $sharing_deadline = $param['sharing_deadline'];
            $permission_arr = explode(',',$param['permission_arr']);

            // 分享记录整合
            $sharing_record = array();
            $sharing_record['sharing_method'] = $sharing_method;
            $sharing_record['sharing_arr'] = $param['sharing_arr'];
            $sharing_record['sharing_uid'] = $uid;
            $sharing_record['sharing_deadline'] = $sharing_deadline;
            Db::startTrans();
            try{
                //1.添加分享记录
                $sharedModel = new SharedRecordModel;
                $sharedModel->data($sharing_record);
                $sharedModel->save(); //获取分享记录的key
                $shard_id = $sharedModel->id;
                //2.分享记录和权限的关联
                $sharing_permission = array();
                foreach ($permission_arr as $value){
                    array_push($sharing_permission,["sharing_id" => $shard_id,"permission_id" => $value]);
                }
                Db::table("sharing_permission")->insertAll($sharing_permission);
                //3.分享记录和文件的关联
                $sharing_doc = array();
                foreach ($docId as $value){
                    array_push($sharing_doc,["sharing_id" => $shard_id,"doc_id" => $value]);
                }
                Db::table("document_shared")->insertAll($sharing_doc);
                //4.分享记录和用户的关联(分享方式、所分享的id集合)
                $this->uploadSharing($shard_id,$sharing_method,$param['sharing_arr']);
                // 提交事务
                Db::commit();
                $this->res->resToMsg("文件分享成功",'');
            } catch (\Exception $e) {
                // 回滚事务
                dump($e);
                Db::rollback();
            }
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 保存分享文件(*****)
     */
    public function saveShareDoc(){
        if($this->request->isPost()){
            $param = $this->request->post();
            $uid = $param['uid'];$docId = $param['docId'];$permissionId = $param['permissionId'];
            //1.判断是否有保存文件的权限
            //todo 根据配置文件判断保存权限
            $permissionArr = explode(',' , $permissionId);
            $canSave = '';
            (in_array(4,$permissionArr)) ? '' : $canSave='不存在保存文件权限';

            //2.判断是否已经保存此文件
            if($canSave){
                $this->res->clientError(114,200,$canSave);
            }else{
                try {
                    $result = Db::table('document_user')
                        ->where('user_id', $uid)
                        ->where('doc_id', $docId)
                        ->select();
                    ($result) ? $canSave="文件已经保存" : "";
                } catch (DataNotFoundException $e) {dump($e);
                } catch (ModelNotFoundException $e) {dump($e);
                } catch (DbException $e) {dump($e);
                }
            }

            //插入文档-用户关联表
            if($canSave){
                $this->res->clientError(114,200,$canSave);
            }else{
                $res = Db::table('document_user')
                    ->insert(array('user_id' => $uid,'uploader' => 0,'doc_id' => $docId));
                ($res) ? $this->res->resToMsg("",$res,200):
                    $this->res->clientError(104);
            }
        }
        $this->res->clientError(103);
    }

    /**
     * @method
     * 二维数组内部的一维数组因某一个键值不能相同，删除重复项
     * @param $arr
     * @param $key
     * @return array
     */
    public function array_unique_key($arr, $key)
    {
        $tmp_arr = array();
        foreach ($arr as $k => $v) {
            if (in_array($v[$key], $tmp_arr))   //搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
            {
                unset($arr[$k]); //销毁一个变量  如果$tmp_arr中已存在相同的值就删除该值
            } else {
                $tmp_arr[$k] = $v[$key];  //将不同的值放在该数组中保存
            }
        }
        ksort($arr);
        return $arr;
    }

    /**
     * @method
     * 上传文件验证合格后存入服务器
     * @param $files
     * @param $folder
     * @return mixed
     */
    public function uploadSave($files,$folder){
        $all_doc = array();
        foreach ($files as $file) {
            // 移动到框架应用根目录/public/uploads/目录下
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if ($info) {
                $doc_info = array();
                //文件唯一编码 文件版本管理
                $doc_info['doc_unique_code'] = md5_file(ROOT_PATH . 'public' . DS
                    . 'uploads' . DS . $info->getSaveName());
                //获取的文件名称
                $doc_info['doc_name'] = date("Y-m-d").' '.$file->getInfo()['name'];
                //获取文件的类型
                $doc_info['doc_type'] = $info->getExtension();
                //获取文件的存储路径
                $doc_info['doc_url'] = $info->getSaveName();
                //获取文件的大小
                $doc_info['doc_size'] = $info->getSize();
                //所属文件夹
                $doc_info['doc_folder'] = $folder;
                //装入$all_doc
                array_push($all_doc,$doc_info);
            } else {
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
        return $all_doc;
    }

    /**
     * @method
     * 验证上传接口传入数据
     * @param $files
     * @param $folder
     * @throws DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function uploadValidation($files,$folder){
        $validation_doc = array();
        foreach ($files as $file){
            $info = $file->getInfo();
            if($info) {
                $doc_info = array();
                //文件唯一编码 文件版本管理
                $doc_info['doc_unique_code'] = md5_file($info['tmp_name']); //临时文件
                $isExist = $this->isExistInFolder($folder,$doc_info['doc_unique_code']);
                ($isExist) ? $this->res->clientError(111,200, $info['name'].
                    '在此文件夹下已存在，与'.$isExist.'内容完全相同'):'';
                //获取的文件名称
                $doc_info['doc_name'] = date("Y-m-d").' '.$info['name'];
                $isSame = $this->isSameInFolder($folder,$doc_info['doc_name']);
                ($isSame) ? $this->res->clientError(111,200, '本文件夹下文件名称为"'.$info['name'].'"已存在'):'';
                array_push($validation_doc,$doc_info);
            }
        }
        //验证$all_doc中是否存在完全相同内容的文件
        $unique_code = $this->getRepeatedValues($validation_doc,'doc_unique_code');
        ($unique_code) ? $this->printIllegalName($validation_doc,$unique_code):""; //打印不合法信息
    }

    /**
     * @method
     * @param $method
     * 验证上传接口信息
     * @param $shard_id
     * @param $arr
     * @throws DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function uploadSharing($shard_id,$method, $arr){
        //$method = 1: 分享至组织架构、$method = 2: 分享至标准角色、$method = 3: 分享至用户
        $arr = explode(',',$arr);
        switch ((int)$method){
            case 1:
                //获取$arr下所有用户id 再插入表
                $result = Db::table('user_organization')
                    ->where('organization_id','in',$arr)
                    ->distinct(true)
                    ->field('user_id')
                    ->select();
                $res = array_column($result,'user_id');
                $organization_shared = array();
                foreach ($res as $value){
                    array_push($organization_shared,["sharing_id" => $shard_id,"user_id" => $value]);
                }
                Db::table("shared_user")->insertAll($organization_shared);
                break;
            case 2:
                //获取$arr下所有用户id 再插入表
                $result = Db::table('user_role')
                    ->where('role_id','in',$arr)
                    ->distinct(true)
                    ->field('user_id')
                    ->select();
                $res = array_column($result,'user_id');
                $role_shared = array();
                foreach ($res as $value){
                    array_push($role_shared,["sharing_id" => $shard_id,"user_id" => $value]);
                }
                Db::table("shared_user")->insertAll($role_shared);
                break;
            case 3:
                //插入分享记录-用户关联表
                $user_shared = array();
                foreach ($arr as $value){
                    array_push($user_shared,["sharing_id" => $shard_id,"user_id" => $value]);
                }
                Db::table("shared_user")->insertAll($user_shared);
                break;
            default: break;
        }
    }

    /**
     * @method
     * @param $folder 文件夹id
     * @param $unique_code 文件唯一编码
     * 判断在此文件夹下是否存在该文件夹
     * @return string
     * @throws DataNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function isExistInFolder($folder,$unique_code){
        $back = '';
        $documentModel = new DocumentModel();
        $result = $documentModel->where('doc_folder',$folder)
                ->where('doc_unique_code',$unique_code)
                ->select();
        //是否存在
        (isset($result[0])) ? $back=$result[0]['doc_name']:'';
        return $back;
    }

    /**
     * @method
     * 判断在此文件夹下是否存在相同文件夹名称
     * @param $folder 文件夹id
     * @param $doc_name 文件名称
     * @return string
     * @throws DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isSameInFolder($folder,$doc_name){
        $back = '';
        $documentModel = new DocumentModel();
        $result = $documentModel->where('doc_folder',$folder)
            ->where('doc_name',$doc_name)
            ->select();
        //是否存在
        (isset($result[0])) ? $back=$result[0]['doc_name']:'';
        return $back;
    }

    /**
     * @method
     * 获取二维数组中指定Key的重复Value
     * @param array $arrInput 二维数组
     * @param string $strKey 键名
     * @return bool|string 重复的键值（以逗号分隔）
     */
    function getRepeatedValues($arrInput, $strKey){
        //参数校验
        if (!is_array($arrInput) || empty($arrInput) || empty($strKey)) {
            return false;
        }
        //获取数组中所有指定Key的值，如果为空则表示键不存在
        $arrValues = array_column($arrInput, $strKey);
        if (empty($arrValues)) {
            return false;
        }
        $arrUniqueValues = array_unique($arrValues);
        $arrRepeatedValues = array_unique(array_diff_assoc($arrValues, $arrUniqueValues));
        return implode($arrRepeatedValues, ',');
    }

    /**
     * @method
     * 打印有相同上传文件的不合法信息
     * @param $all_doc 所有文件集合
     * @param $unique_code 文件唯一标识
     */
    public function printIllegalName($all_doc, $unique_code){
        //字符串转数组
        $unique_code_arr = explode(",",$unique_code);
        $all_str = "";
        foreach ($unique_code_arr as $value){
            $str = "";
            foreach ($all_doc as $item){
                ($item['doc_unique_code'] == $value) ? $str = $str.'"'.substr($item['doc_name'],11).'"':"";
            }
            $str = $str.'的内容完全相同 ';
            $all_str = $all_str.$str;
        }
        $this->res->clientError(112,200, $all_str);
    }
}