<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2018/12/26
 * Time: 17:25
 */

namespace app\relation\controller;

use app\relation\model\User as UserModel;
use think\ResponseStructure;
use think\Db;

class User extends ResponseStructure
{
    /**
     * @frontAPI
     * 获取用户信息列表 根据组织架构
     */
    public function getUserInfoInGroup(){
        //1.判断接口方式
        if($this->request->isGet()){
            $param = $this->request->get();
            //直接使用静态方法
            $result = Db::table('user_organization')->where('organization_id',$param['ouid'])->field('user_id')->select();
            $uidArr = array_column($result,'user_id');
            $userInfo = Db::table('user_role')->alias('r')->join('user u','u.uid = r.user_id','RIGHT')
                ->where('user_id','in',$uidArr)
                ->select();
            ($userInfo) ? $this->res->resToMsg("",$userInfo,200):
                $this->res->clientError(110);
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 获取用户信息列表 根据标准角色
     */
    public function getUserInfoInRole(){
        //1.判断接口方式
        if($this->request->isGet()){
            $param = $this->request->get();
            //直接使用静态方法
            $result = Db::table('user_role')->where('role_id',$param['role'])->field('user_id')->select();
            $organizationArr = array_column($result,'user_id');
            $userInfo = Db::table('user_organization')->alias('o')->join('user u','u.uid = o.user_id','RIGHT')
                ->where('user_id','in',$organizationArr)
                ->select();
            ($userInfo) ? $this->res->resToMsg("",$userInfo,200):
                $this->res->clientError(110);
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 获取用户信息列表 根据用户id
     */
    public function getUserInfo(){
        //1.判断接口方式
        if($this->request->isGet()){
            //user表信息加上用户的用户角色集合、用户组织架构集合
            $param = $this->request->get();
            //事务操作
            Db::startTrans();
            try{
                $userInfo = UserModel::get($param['uid']);
                //判断是否存在此用户
                ($userInfo) ? '':$this->res->clientError(110);
                $userRole = Db::table('user_role')->where('user_id',$param['uid'])->field('role_id')->select();
                $userOrganization = Db::table('user_organization')->where('user_id',$param['uid'])->field('organization_id')->select();
                $userInfo['role'] = implode(',',array_column($userRole, 'role_id'));
                $userInfo['ouid'] = implode(',',array_column($userOrganization, 'organization_id'));
                $this->res->resToMsg("",$userInfo,200);
            }catch (\Exception $e){
                // 回滚事务
                dump($e);
                Db::rollback();
            }
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 修改用户信息
     */
    public function updateUser(){
        //判断接口方式
        if($this->request->isPost()){
            $param = $this->request->post();
            //获取的$param分为三个部分处理 User表、用户角色集合、用户组织架构集合
            ($param['formData']['username'] && $param['formData']['password'] && $param['formData']['nickname'])
                ? '':$this->res->clientError(105);
            //密码需要调用md5方法
            $param['formData']['password'] = md5($param['formData']['password']);
            // 启动事务
            Db::startTrans();
            try{
                //1.User表
                $userInfo['uid'] = $param['formData']['uid'];
                $userInfo['username'] = $param['formData']['username'];
                $userInfo['nickname'] = $param['formData']['nickname'];
                $userInfo['password'] = $param['formData']['password'];
                $userInfo['status'] = $param['formData']['status'];
                $user = new UserModel;
                //formData中标准角色名
                $user->isUpdate(true)->save($userInfo);
                //通过获取新插入数据主键 判断插入是否成功($user->uid)

                //2.用户角色集合更新
                $roleArr = explode(",",$param['formData']['role_id']);
                //字符串数组转int类型
                $roleArr = array_map('intval', $roleArr);
                $this->isUpdateRole($param['formData']['uid'],$roleArr);

                //3.用户组织架构更新
                $organizationArr = explode(",",$param['formData']['organization_id']);
                $this->isUpdateOrganization($param['formData']['uid'],$organizationArr);
                // 提交事务
                Db::commit();
                $this->res->resToMsg("用户更新成功");
            } catch (\Exception $e) {
                // 回滚事务
                dump($e);
                Db::rollback();
            }
        }
        $this->res->clientError(103);
    }

    /**
     * 接口updateUser里的方法
     * @param $uid
     * @param $array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function isUpdateRole($uid,$array){
        $roleArr = Db::table('user_role')->field('role_id')->where('user_id',$uid)->select();
        $roleArr = array_column($roleArr, 'role_id');
        //找到两个数组的交集
        $intersect = array_intersect($roleArr,$array);
        //增加的角色集合 计算差集
        $addRole = array_diff($array,$intersect);
        if($addRole) {
            $insertRole = array();
            foreach ($addRole as $key=>$value){
                array_push($insertRole,['user_id' => $uid , 'role_id' => $value]);
            }
            Db::table('user_role')->insertAll($insertRole);
        }
        //删除的角色集合 计算差集
        $deleteRole = array_diff($roleArr,$intersect);
        if($deleteRole) {
            Db::table('user_role')
                ->where('user_id',$uid)
                ->where('role_id','in',$deleteRole)
                ->delete();
        }
    }

    /**
     * 接口updateUser里的方法
     * @param $uid
     * @param $array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function isUpdateOrganization($uid,$array){
        $organizationArr = Db::table('user_organization')->field('organization_id')->where('user_id',$uid)->select();
        $organizationArr = array_column($organizationArr, 'organization_id');
        //找到两个数组的交集
        $intersect = array_intersect($organizationArr,$array);
        //增加的组织架构集合 计算差集
        $addRole = array_diff($array,$intersect);
        if($addRole) {
            $insertRole = array();
            foreach ($addRole as $key=>$value){
                array_push($insertRole,['user_id' => $uid , 'organization_id' => $value]);
            }
            Db::table('user_organization')->insertAll($insertRole);
        }
        //删除的组织架构集合 计算差集
        $deleteRole = array_diff($organizationArr,$intersect);
        if($deleteRole) {
            Db::table('user_organization')
                ->where('user_id',$uid)
                ->where('organization_id','in',$deleteRole)
                ->delete();
        }
    }

    /**
     * @frontAPI
     * 创建一个新用户
     */
    public function createUser(){
        //插入成功后返回id 用于关系表（用户-角色）、（用户-组织架构）
        if($this->request->isPost()){
            $param = $this->request->post(); //获取$param['formData'];
            //检查formData中数据是否填写（username、nickname、password）'
            ($param['formData']['username'] && $param['formData']['password'] && $param['formData']['nickname'])
                ? '':$this->res->clientError(105);
            //密码需要调用md5方法
            $param['formData']['password'] = md5($param['formData']['password']);

            //整理用户信息
            $userInfo['username'] = $param['formData']['username'];
            $userInfo['nickname'] = $param['formData']['nickname'];
            $userInfo['password'] = $param['formData']['password'];
            $userInfo['status'] = $param['formData']['status'];

            //角色、组织架构与用户均为一对多关系，获取的数据为字符串如"1,2,3",需对数据进行整理
            $roleArr = explode(",",$param['formData']['role_id']);
            $organizationArr = explode(",",$param['formData']['organization_id']);

            //事务操作
            Db::startTrans();
            try{
                $user = new UserModel($userInfo);
                $user->save();
                $user_id = $user->uid;
                //根据$roleArr，$organizationArr整理批量插入数据
                $insertRole = array();$insertOrganization = array();
                foreach ($roleArr as $key=>$value){
                    array_push($insertRole,['user_id' => $user_id , 'role_id' => $value]);
                }
                foreach ($organizationArr as $key=>$value){
                    array_push($insertOrganization,['user_id' => $user_id , 'organization_id' => $value]);
                }
                Db::table('user_role')->insertAll($insertRole);
                Db::table('user_organization')->insertAll($insertOrganization);
                // 提交事务
                Db::commit();
                $this->res->resToMsg("新建用户成功");
            } catch (\Exception $e) {
                // 回滚事务
                dump($e);
                Db::rollback();
                $this->res->clientError(104);
            }
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 删除用户
     */
    public function deleteUser(){
        //数据库设计-外键约束删除时和更新时 CASCADE
        //只需要删除用户表中数据,关联表会自动删除
        //1.判断接口方式
        if($this->request->isPost()){
            $param = $this->request->post();
            //直接使用静态方法
            $result = UserModel::destroy($param['id']);
            ($result) ? $this->res->resToMsg("用户删除成功",$result):
                $this->res->clientError(109);
        }
        $this->res->clientError(103);
    }
}