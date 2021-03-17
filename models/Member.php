<?php
namespace app\models;

use Yii;
use app\base\BaseModel;
use app\records\Member as MemberAR;
use yii\web\IdentityInterface;
class Member extends BaseModel implements IdentityInterface{

    /**
     * 用户
     * @var Mmeber
     */
    public $member;

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id){
        $self = new static();
        $self->member = \Yii::$app->get('userService')->getMember($id);
        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null){
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(){
        if($this->member == null){
            return 0;
        }
        return $this->member->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey(){
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey){
        return false;
    }

    /**
     * 查询指定用户名的账号信息
     * @param  string $username
     * @return Member
     */
    public static function findByUsername($username){
        $self = new static();
        $self->member = Yii::$app->get('userService')->getMemberByUsername($username);
        return $self;
    }

    /**
     * 验证密码
     * @param  string $password
     * @return boolean
     */
    public function validatePassword($password){
        if(!$this->member){
            return false;
        }
        return Yii::$app->security->validatePassword($password, $this->member->password);
    }

}