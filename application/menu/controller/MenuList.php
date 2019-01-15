<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2019/1/15
 * Time: 9:42
 */

namespace app\menu\controller;
use app\menu\model\MenuList as MenuListModel;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\ResponseStructure;
use think\Db;

class MenuList extends ResponseStructure
{
    /**
     * @frontAPI
     * 获取指定用户的菜单列表
     */
    public function getMenuListByUser(){
        //判断接口方式
        if($this->request->isGet()){
            $param = $this->request->get();
            $uid = $param['uid'];
            try {
                //找到用户所有角色id集合
                $roleId = array_column(Db::table('user_role')
                    ->where('user_id', $uid)
                    ->field('role_id')
                    ->select(), 'role_id');

                $menu_id = array_column(Db::table("role_menu")
                    ->where('role_id','in',$roleId)
                    ->distinct(true)
                    ->field('menu_id')
                    ->select(),'menu_id');

                $result = Db::table('doc_menu_list')
                    ->where('id', 'in', $menu_id)
                    ->select();
                ($result) ? $this->res->resToMsg("",$result,200) :
                $this->res->clientError(104);
            } catch (DataNotFoundException $e) {dump($e);
            } catch (ModelNotFoundException $e) {dump($e);
            } catch (DbException $e) {dump($e);
            }
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 修改指定角色的菜单列表
     */
    public function getMenuIdByRole(){
        //判断接口方式
        if($this->request->isGet()){
            $param = $this->request->get();
            $role = $param['role'];
            $menuList =  new MenuListModel();
            try {
                $oldMenu = array_column($menuList
                    ->where('role_id', $role)
                    ->field('menu_id')
                    ->select(), 'menu_id');
                ($oldMenu) ? $this->res->resToMsg('',$oldMenu,200):
                    $this->res->clientError(113);;
            } catch (DataNotFoundException $e) {dump($e);
            } catch (ModelNotFoundException $e) {dump($e);
            } catch (DbException $e) {dump($e);
            }
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 修改指定角色的菜单列表
     */
    public function updateMenuIdByRole(){
        //判断接口方式
        if($this->request->isPost()){
            $param = $this->request->post();
            $newMenu = explode(',',$param['menuId']);
            $role = $param['role'];
            $menuList =  new MenuListModel();
            try {
                $oldMenu = array_column(
                    Db::table('role_menu')
                    ->where('role_id', $role)
                    ->field('menu_id')
                    ->select(), 'menu_id');
                //公共域
                $intersect = array_intersect($oldMenu,$newMenu);
                $addRole = array_diff($newMenu,$intersect);
                $deleteRole = array_diff($oldMenu,$intersect);
                // 启动事务
                Db::startTrans();
                try{
                    if($addRole){
                        $insertRole = array();
                        foreach ($addRole as $key=>$value){
                            array_push($insertRole,['role_id' => $role , 'menu_id' => $value]);
                        }
                        Db::table('role_menu')->insertAll($insertRole);
                    }
                    if($deleteRole){
                        Db::table('role_menu')
                            ->where('role_id',$role)
                            ->where('menu_id','in',$deleteRole)
                            ->delete();
                    }
                    // 提交事务
                    Db::commit();
                    $this->res->resToMsg('菜单修改成功',200);
                } catch (\Exception $e) {
                    // 回滚事务
                    dump($e);
                    Db::rollback();
                }
            } catch (DataNotFoundException $e) {dump($e);
            } catch (ModelNotFoundException $e) {dump($e);
            } catch (DbException $e) {dump($e);
            }
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 新建菜单
     */
    public function createMenu(){
        //判断接口方式
        if($this->request->isPost()){
            $param = $this->request->post();
            //必填项是否填写
            ($param['formData']['menu_name'] && $param['formData']['menu_url'])
                ? '':$this->res->clientError(105);
            //对$param进行一定处理（字符类型匹配）
            $menu = new MenuListModel($param['formData']);
            //formData中下载名
            $menu->allowField(true)->save();
            //通过获取新插入数据主键 判断插入是否成功
            ($menu->id) ? $this->res->resToMsg("新增一项菜单成功",$menu->id):
                $this->res->clientError(104);
        }
        $this->res->clientError(103);
    }

    /**
     * @frontAPI
     * 删除一条菜单
     */
    public function deleteMenu(){
        if($this->request->isPost()){
            $param = $this->request->post();
            //直接使用静态方法
            $result = MenuListModel::destroy($param['id']);
            ($result) ? $this->res->resToMsg("删除菜单成功",$result):
                $this->res->clientError(109);
        }
        $this->res->clientError(103);
    }
}