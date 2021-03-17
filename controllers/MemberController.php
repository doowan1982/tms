<?php
namespace app\controllers;

use \Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use app\records\Member;
use app\records\MemberRole;
use app\records\MemberConfig;
use app\models\Position;
use app\models\Constants;
class MemberController extends BaseController{

    private $userService;

    /**
     * {@inheritdoc}
     */
    public function init(){
        parent::init();
        $this->userService = Yii::$app->get('userService');
    }

    public function actionList(){
        $params['name'] = $this->get('name');
        $params['username'] = $this->get('username');
        return $this->success('ok', $this->userService->getMembers($params));
    }

    public function actionIndex(){
        $this->setPosition(new Position(['name' => '成员管理']));
        $params['name'] = $this->get('name', null);
        return $this->render('/member/index', [
                'members' => $this->userService->getMembers($params)
            ]);
    }

    public function actionEdit(){
        $this->setPosition(new Position([
            'name' => '成员管理',
            'jumpUrl' => Constants::MEMBER_INDEX,
        ]));
        $id = $this->get('id');
        $member = null;
        if($id){
            $member = $this->userService->getMember($id);
        }
        $name = '修改成员信息';
        if($member == null){
            $member = new Member();
            $member->username = '';
            $member->real_name = '';
            $member->phone_number = '';
            $member->email = '';
            $name = '新增成员信息';
        }
        $this->setPosition(new Position(['name' => $name]));
        return $this->render('/member/edit', [
            'member' => $member,
            'roles' => $this->userService->getRoles(),
        ]);
    }

    public function actionSave(){
        $data = $this->post();
        $id = $this->post('id');
        $member = $this->userService->getMember($id);
        if($member == null){
            $member = new Member();
            $member->is_leave = Member::WORKING_STATUS;
            $member->password = $this->post('password');
        }

        $roleId = $this->post('role_id');
        $roles = $this->userService->getRoles($roleId);
        if(empty($roles)){
            return $this->error('请选择角色');
        }
        $member->setAttributes($data);
        if(!$this->userService->save($member)){
            return $this->error($this->userService->getErrorString());
        }
        if(!$this->userService->assignRoles($member, $roles)){
            return $this->error($this->userService->getErrorString());
        }
        self::i("操作成员【{$member->real_name}】的数据");
        return $this->success('保存成功');
    }

    public function actionDisable(){
        $id = $this->post('id');
        $member = $this->userService->getMember($id);
        if($member == null){
            return $this->error('成员不存在');            
        }
        $member->is_leave = Member::LEFT_STATUS;
        if(!$this->userService->save($member)){
            return $this->error('操作失败');
        }
        self::i("禁用了【{$member->real_name}】账号");
        return $this->success('操作成功');
    }

    public function actionRoles(){
        $this->setPosition(new Position(['name' => '成员管理', 'jumpUrl' =>  Constants::MEMBER_INDEX]));
        $this->setPosition(new Position(['name' => '角色管理']));
        return $this->render('/member/roles', [
            'roles' => $this->userService->getRoles(),
        ]);
    }

    public function actionEditRole(){
        $roleId = $this->get('id');
        $role = $this->userService->getRoleById($roleId);
        if($role == null){
            $role = new MemberRole();
        }
        return $this->success('success', $role);
    }

    public function actionSaveRole(){
        $roleId = $this->post('id');
        $role = $this->userService->getRoleById($roleId);
        $message = '修改';
        if($role == null){
            $role = new MemberRole();
            $message = '新增';
        }
        $role->setAttributes($this->post());
        if(!$this->userService->save($role)){
            return $this->error($this->userService->getErrorString());
        }
        self::i("{$message}【{$role->name}】的数据");
        return $this->success('保存成功');
    }

    public function actionDeleteRole(){
        $roleId = $this->post('id');
        $role = $this->userService->getRoleById($roleId);
        if($role == null){
            return $this->error('角色不存在');
        }
        if(!$this->userService->deleteRole($role)){
            return $this->error($this->userService->getErrorString());
        }
        self::i("删除角色【{$role->name}】");
        return $this->success('删除成功');
    }

    public function actionSaveConfig(){
        $member = $this->getMember();
        $name = $this->post('name', '');
        if(!$name){
            return $this->error('未指定配置名称');
        }
        $memberConfig = $this->userService->getMemberConfig($member, $name);
        if($memberConfig == null){
            $memberConfig = new MemberConfig();
            $memberConfig->member_id = $member->id;
        }
        $memberConfig->name = $name;
        $memberConfig->value = $this->post('value', '');
        if(!$this->userService->setMemberConfig($memberConfig)){
            return $this->error($this->userService->getErrorString());
        }
        self::i("更新【{$name}】配置信息");
        return $this->success('success');
    }

}