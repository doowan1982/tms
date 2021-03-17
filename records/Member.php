<?php
namespace app\records;
use Yii;
use yii\behaviors\TimestampBehavior;
class Member extends \app\base\BaseAR{

    const WORKING_STATUS = 1;
    const LEFT_STATUS = 0;

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%member}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['create_time', 'update_time'],
                    self::EVENT_BEFORE_UPDATE => ['update_time'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        return [
            'username' => '用户名',
            'password' => '密码',
            'email' => '邮箱',
            'phone_number' => '手机号码',
            'is_leave' => '是否在职',
            'last_signin_time' => '最后登陆时间',
            'signin_ip' => '登陆IP',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
            'real_name' => '真实姓名',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            [['username', 'email','phone_number', 'is_leave'], 'required'],
            [['username','email','phone_number'], 'unique'],
            [['username', 'email', 'real_name'], 'string','max'=>50],
            [['phone_number'], 'string','max'=>20],
            [['is_leave'], 'integer'],
            [['is_leave'], 'in','range' => [
                self::WORKING_STATUS, 
                self::LEFT_STATUS, 
            ]], 
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields(){
        $fields = parent::fields();
        unset($fields['password']);
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert){
        if($insert){
            $this->password = Yii::$app->get('userService')->generatePasswordHash($this->password);
            $this->is_leave = self::WORKING_STATUS;
        }
        return parent::beforeSave($insert);
    }

    public function getGroups(){
        return $this->hasMany(MemberRoleGroup::class, ['member_id' => 'id'])->joinWith('role');
    }

}