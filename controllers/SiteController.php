<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\records\Member;
use app\models\Constants;
use app\records\Setting;
use app\models\SettingSerialize;

class SiteController extends BaseController
{

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['access'] = Yii::$app->params['accessControl'];
        $behaviors['access']['rules'][] = [
            'actions' => ['index', 'login', 'logout', 'error', 'reset-password'],
            'roles' => ['?', '@'],
            'allow' => true,
            'matchCallback' => function($rule, $action){
                return true;
            }
        ];
        return $behaviors;
    }

    public function beforeAction($action){
        return Controller::beforeAction($action);
    }

    public function actionIndex(){
        return $this->redirect('/my/index');
    }

    public function actionError(){
        $message = '未知错误';
        $exception = Yii::$app->errorHandler->exception;
        if($exception != null){
            $message = $exception->getMessage();
        }
        return $this->error($message);
    }

    public function actionLogin(){
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $username = $this->post('username', null);
        $password = $this->post('password', null);

        if(!$username && !$password){
            return $this->render('login');
        }

        $model = new LoginForm();
        $model->username = $username;
        $model->password = $password;
        if (!$model->login()) {
            return $this->error($model->getErrorString());
        }

        $member = $model->getUser()->member;
        $member->last_signin_time = time();
        $member->signin_ip = ip2long(Yii::$app->request->getUserIP());
        Yii::$app->get('userService')->save($member);
        self::i("登录了系统");
        return $this->success('success');
    }

    public function actionLogout(){
        Yii::$app->user->logout();
        return $this->goHome();
    }

    public function actionResetPassword(){
        $service = Yii::$app->get('userService');
        $hash = $this->get('token', null);
        if($hash){
            $member = $service->getMemberByHash($hash);
            if($member == null){
                return $this->error('无效的token');
            }
            $password = $this->post('password');
            $member->password = $service->generatePasswordHash($password);
            if(!$password){
                return $this->render('/site/resetPassword');
            }
            if($service->save($member) === false){
                return $this->error('密码重置失败');
            }
            $ip = Yii::$app->request->getUserIP();
            self::i("完成重置密码操作【IP地址：{$ip}】");
            $service->removeAllPasswordRecover($member);
            return $this->success('重置成功');
        }   
        //验证输入信息 步骤一
        $member = new Member();
        $member->setAttributes($this->post());
        if(!$member->username || !$member->email || !$member->phone_number){
            return $this->render('resetPassword');
        }
        
        $member = $service->verifiedBeforeResetPassword($member);
        if($member === false){
            return $this->error($service->getErrorString());
        }

        $token = $service->createPasswordRecover($member);
        if(!$token){
            return $this->error($service->getErrorString());
        }
        return $this->redirect(Constants::RESET_PASSWORD.'?token='.$token->id);
    }

    public function actionSetting(){
        return $this->render('/site/setting', [
            'setting' => $this->toolService->getSetting()
        ]);
    }

    public function actionUpgradeSettingCache(){
        $this->toolService->upgradeSettingCache();
        self::i("更新缓存数据");
        return $this->success('缓存已更新');
    }

    public function actionGetSetting(){
        $setting = $this->toolService->getSettingById($this->get('id'));
        if($setting == null){
            return $this->error('数据不存在');
        }
        $setting->value = unserialize($setting->value)->getValue();
        return $this->success('success', $setting);
    }

    public function actionSaveSetting(){
        $id = $this->post('id');
        $name = $this->post('name');
        $service = $this->toolService;
        $value = $this->post('value', '');
        if($value === ''){
            return $this->error('参数值不能为空');
        }
        $setting = $service->getSettingById($id);
        if($setting == null){
            if($service->getSettingByName($name) != null){
                return $this->error('参数名已存在');
            }
            $setting = new Setting();
        }
        $setting->name = $name;
        $setting->comment = $this->post('comment', '');
        $setting->value = serialize(new SettingSerialize($value));
        if(!$service->save($setting)){
            return $this->error($setting->getErrorString());
        }
        self::i("修改名称为【{$name}】的设置");
        return $this->success('保存成功', $setting->id);
    }

    public function actionDeleteSetting(){
        $id = $this->post('id');
        $setting = $this->toolService->getSettingById($id);
        if($setting == null){
            return $this->error('数据不存在');
        }
        if(!$this->toolService->deleteSetting($setting)){
            return $this->error('删除失败');
        }
        self::i("删除名称为【{$setting->name}】的设置");
        return $this->success('删除成功');
    }

}