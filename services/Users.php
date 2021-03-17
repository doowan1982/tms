<?php
namespace app\services;

use \Yii;
use app\records\Member;
use app\records\MemberRole;
use app\records\MemberConfig;
use app\records\MemberPasswordRecover;
use app\records\MemberRoleGroup;
use app\models\Constants;
use yii\web\UnauthorizedHttpException;
use yii\helpers\ArrayHelper;
class Users extends \app\base\Service{
    
    /**
     * 返回用户信息，如果$id为0，则返回当前用户的信息
     * @param  integer $id
     * @return Member
     */
    public function getMember($id){
        return Member::find()
                    ->andWhere([
                        'id' => $id,
                        'is_leave' => Member::WORKING_STATUS,
                    ])
                    ->one();
    }

    /**
     * 返回指定邮箱的成员
     * @param  string $username
     * @return Member
     */
    public function getMemberByEmail($email){
        return Member::find()
                    ->andWhere([
                        'email' => $email,
                        'is_leave' => Member::WORKING_STATUS
                    ])
                    ->one();
    }

    /**
     * 返回指定手机号码的成员
     * @param  string $username
     * @return Member
     */
    public function getMemberByPhone($phone){
        return Member::find()
                    ->andWhere([
                        'phone_number' => $phone,
                        'is_leave' => Member::WORKING_STATUS
                    ])
                    ->one();
    }

    /**
     * 返回指定用户名的成员
     * @param  string $username
     * @return Member
     */
    public function getMemberByUsername($username){
        return Member::find()
                    ->andWhere([
                        'username' => $username,
                        'is_leave' => Member::WORKING_STATUS
                    ])
                    ->one();
    }

    /**
     * 返回指定条件的用户数据
     * @param  array  $conditions 
     * @return array
     */
    public function getMembers($conditions=[]){
        $query = Member::find();
        $fields = ['id', 'username', 'email', 'phone_number', 'real_name'];
        if(isset($conditions['name'])){
            $range = ['username', 'email', 'phone_number', 'real_name'];
            foreach ($range as $key=>$field) {
                $query->orWhere(['like', $field, $conditions['name']]);
            }
            unset($conditions['name']);
        }
        $conditions['is_leave'] = Member::WORKING_STATUS;
        return $query->andFilterWhere($conditions)->orderBy(['real_name'=>SORT_ASC])->all();
    }

    /**
     * 分配角色
     * @param  array $roles MemberRole
     * @return boolean
     */
    public function assignRoles(Member $member, array $roles){
        if(empty($roles)){
            return $this->setError('未指定角色');
        }
        MemberRoleGroup::deleteAll([
            'member_id' => $member->id
        ]);
        foreach($roles as $role){
            $group = new MemberRoleGroup();
            $group->member_id = $member->id;
            $group->role_id = $role->id;
            if(!$group->insert()){
                return $this->setError($group->getErrorString());
            }
        }
        return true;
    }

    /**
     * 返回除$member的用户数据
     * @author doowan
     * @date   2020-11-10
     * @param  Member     $member
     * @param  array     $conditions
     * @return array
     */
    public function getExceptMembers(Member $member, $conditions = []){
        $query = Member::find();
        $fields = ['id', 'username', 'email', 'phone_number', 
        'real_name'];
        $conditions['is_leave'] = Member::WORKING_STATUS;
        return $query->select($fields)
                    ->andFilterWhere($conditions)
                    ->andWhere(['<>', 'id', $member->id])
                    ->orderBy(['username' => SORT_ASC])
                    ->all();
    }

    /**
     * 重置密码
     * @param  Member $member
     * @return Member
     */
    public function verifiedBeforeResetPassword(Member $member){
        $member = Member::find()->where([
            'username' => $member->username,
            'email' => $member->email,
            'phone_number' => $member->phone_number,
        ])->one();
        if($member == null){
            return $this->setError('未找到匹配的成员数据');
        }
        return $member;
    }

    /**
     * 返回角色列表数据
     * @return array
     */
    public function getRoles($ids = null){
        $conditions = ['id' => $ids];
        return MemberRole::find()->andFilterWhere($conditions)
                ->orderBy(['show_order' => SORT_DESC, 'id' => SORT_DESC])
                ->all();
    }

    /**
     * 返回$roles角色所存在的用户
     * @param  array $roles 角色对象或者角色编号数组
     * @return array
     */
    public function getMembersByRoles($roles = []){
        return Member::find()->joinWith([
                'groups' => function($query) use ($roles){
                    foreach($roles as $key=>$role){
                        if($role instanceof MemberRole){
                            $roles[$key] = $role->id;
                        }
                    }
                    $query->where(['role_id' => $roles]);
                }
            ])->all();
    }

    /**
     * 返回指定编号的角色
     * @param  integer $id
     * @return MemberRole
     */
    public function getRoleById($id){
        return MemberRole::findOne($id);
    }

    /**
     * 删除角色
     * @param  Role $role
     * @return boolean
     */
    public function deleteRole(MemberRole $role){
        $group = MemberRoleGroup::findOne(['role_id' => $role->id]);
        if($group != null){
            return $this->setError('角色存在关联数据');
        }
        return $role->delete() !== false;
    }

    /**
     * 根据给定的$member创建密码找回的token
     * @param  Member $member
     * @return MemberPasswordRecover
     */
    public function createPasswordRecover($member){
        $recover = new MemberPasswordRecover();
        $recover->id = md5($member->username.$member->email.$member->phone_number.time());
        $recover->expired_time = time() + \Yii::$app->get("toolService")->getCacheSetting('findPasswordExpiredTime')->getValue();
        $recover->member_id = $member->id;
        if($recover->insert() !== false){
            return $recover;
        }
        $this->setError($recover->getErrorString());
        return null;
    }


    /**
     * 删除指定$member的token数据
     * @return void
     */
    public function removeAllPasswordRecover(Member $member){
        MemberPasswordRecover::deleteAll(['member_id' => $member->id]);
    }

    /**
     * 找回密码时使用，
     * @param  string $hash
     * @return Member
     */
    public function getMemberByHash($hash){
        $recover = MemberPasswordRecover::find()->where([
                        'id' => $hash,
                    ])
                    ->andWhere(['>=', 'expired_time', time()])
                    ->one();
        if($recover == null){
            return null;
        }
        return Member::findOne($recover->member_id);
    }

    /**
     * 密码hash
     * @param  string $password
     * @return string
     */
    public function generatePasswordHash($password){
        return Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * 返回配置信息
     * @param  Member $member
     * @param  string $name
     * @return MemberConfig
     */
    public function getMemberConfig(Member $member, $name){
        $where = [
            'member_id' => $member->id,
            'name' => $name
        ];
        return MemberConfig::find()->where($where)->one();
    }


    /**
     * 返回配置信息
     * @param  Member $member
     * @return array
     */
    public function getMemberAllConfig(Member $member){
        return MemberConfig::find()->where(['member_id' => $member->id])->orderBy(['name' => SORT_ASC])->all();
    }

    /**
     * 设置用户的配置信息
     * @param MemberConfig $config
     * @return boolean
     */
    public function setMemberConfig(MemberConfig $config){
        if($config->name == Constants::SEND_TO_EMAIL){
            
        }
        return $this->save($config);
    }

    /**
     * 根据用户的关注配置，获取参与的项目，如果未设置（0），则返回空数组
     * @param  Member $member
     * @param  array $definedParams 如果具有项目编号查询参数，则检查是否在参与的项目之内
     * @return array 有效的项目编号数组
     */
    public function getFocusProjects(Member $member, $definedParams = []){
        $definedParams = (array)$definedParams;
        $config = $this->getMemberConfig($member, Constants::FOCUS_PROJECT);
        if($config == null || $config->value < 1){
            return [];
        }
        $joinProjects = Yii::$app->get('projectService')->getMemberJoinProjects($member);
        if(count($joinProjects) > 0 && count($definedParams) > 0){
            $intersect = array_intersect(ArrayHelper::getColumn($joinProjects, 'id'), $definedParams);
            foreach($joinProjects as $key=>$project){
                if(!in_array($project->id, $intersect)){
                    unset($joinProjects[$key]);
                }
            }
        }
        return $joinProjects;
    }
}