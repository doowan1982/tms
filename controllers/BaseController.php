<?php
namespace app\controllers;

use app\base\CommonTrait;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\helpers\ArrayHelper;
use app\models\UploadForm;
use app\models\Error;
use yii\web\View;
use app\models\Success;
use app\models\PaginationSuccess;
use yii\web\Response;
use app\models\Position;
use app\models\Constants;
use app\services\Tools;
class BaseController extends \yii\web\Controller{

    use CommonTrait;

    private $position = null;

    public $parameters = [];

    public $toolService;

    /**
     * {@inheritdoc}
     */
    public function init(){
        parent::init();
        $this->toolService = Yii::$app->get('toolService');
        //自动更新缓存数据
        if(!$this->toolService->getCacheSetting('accessControlRoute')){
            $this->toolService->upgradeSettingCache();
        }

        $this->parameters = (\Yii::$app->request->get() + \Yii::$app->request->post());
        \Yii::setAlias('@view', \Yii::getAlias('@app').'/views/');
        $this->view->cssFiles = Yii::$app->params['cssFiles'];
        foreach(Yii::$app->params['jsFiles'] as $value){
            list($file, $params) = $value;
            $this->view->registerJsFile($file, $params);
        }
        $this->view->title = $this->toolService->getCacheSetting('systemTitle')->getValue() ;
        $this->view->params['csrfParam'] = \Yii::$app->request->csrfParam;
        $this->view->params['csrfToken'] = \Yii::$app->request->getCsrfToken();

        $this->setPosition(new Position(['name' => '首页', 'jumpUrl' => '/']));
    }
    

    /**
     * {@inheritdoc}
     */
    public function actions(){
        return [
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['access'] = Yii::$app->params['accessControl'];
        $behaviors['rateLimiter'] = [
              'class' => \yii\filters\RateLimiter::className(),
              'errorMessage' => '访问过于频繁',
        ];
        return $behaviors;
    }
    
    /**
     * {@inheritdoc}
     */
    public function beforeAction($action){
        if(Yii::$app->user->getIsGuest()){
            $this->redirect(Constants::LOGIN);
            return false;
        }
        return parent::beforeAction($action);
    }

    //返回get参数
    public function get($name=null, $def='', $isBlank = false){
        $request = \Yii::$app->request;
        if($name){
            $value = $request->get($name, $def);
            if($value === '' && !$isBlank){
                $value = $def;
            }
            return $value;
        }
        return $request->get();
    }

    //返回post参数
    public function post($name=null, $def='', $isBlank = false){
        $request = \Yii::$app->request;
        if($name){
            $value = $request->post($name, $def);
            if($value === '' && !$isBlank){
                $value = $def;
            }
            return $value;
        }
        $data = $request->post();
        unset($data[$request->csrfParam]);
        return $data;
    }

    //文件上传
    public function actionUpload(){
        $root = \Yii::getAlias("@webroot");
        $model = UploadForm::upload(
            'file', 
            '', 
            date('y/m/d'), 
            $root.\Yii::$app->params['uploadDir']
        );
        if($model->hasErrors()){
            exit(json_encode([
                'error' => 1,
                'message' => $model->getErrorString()
            ]));
        }
        exit(json_encode([
            'error' => 0,
            'url' => str_replace($root, '', $model->getFileName())
        ]));
    }

    //设置面包屑位置
    public function setPosition($position){
        if($this->position == null){
            $this->position = $position;
        }else{
            $this->position->setNextPosition($position);
        }
    }

    public function getMember(){
        return Yii::$app->user->getIdentity()->member;
    } 

    /**
     * 权限控制
     * @param  Action $action
     * @return boolean
     */
    public function canAccess($action){
        $id = $action->controller->id;
        $allowAccessRoutes = Yii::$app->get("toolService")->getCacheSetting('accessControlRoute')->getValue();
        if(!isset($allowAccessRoutes->$id)){
            $rules = false;
        }else{
            $rules = $allowAccessRoutes->$id;
            if(is_string($rules)){
                $rules = $rules === '*';
            }else if(is_array($rules)){
                $rules = in_array($action->id, $rules);
            }
        }
        return $this->getMember() != null && ($this->isAdmin() || $rules);
    }

    /**
     * 是否为管理员
     * @return boolean
     */
    public function isAdmin(){
        $admin = ArrayHelper::merge(Yii::$app->params['admin'], $this->toolService->getAdminList());
        return in_array($this->getMember()->username, $admin);
    }
    

    public function pagination($message, $data = []){
        $data = new PaginationSuccess([
            'message' => $message,
            'data' => $data,
        ]);
        return $this->success($message, $data);
    }

    public function success($message, $data = []){
        if(!$data instanceof Success){
            $data = new Success([
                    'message' => $message,
                    'data' => $data
                ]);
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $data->data();
    }
    
    //返回错误信息
    public function error($info){
        if(!$info instanceof Error){
            if(!is_array($info)){
                $info = [
                    'message' => $info
                ];
            }
            $info = new Error($info);
        }
        if(Yii::$app->request->getIsAjax()){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $info->getMessage();
            return $info->toArray();
        }
        return $this->render(
            '/common/error', 
            [
                'error' => $info
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function render($view, $params = []){
        $this->view->params['position'] = $this->position;
        $result = parent::render($view, $params);
        return $result;
    }

    /**
     * 返回未读消息数量
     * @return integer
     */
    public function getUnreadMessages(){
        return $this->toolService->getUnreadMessageCount($this->getMember());
    }

    private function unauthor($message){
        throw new \yii\web\UnauthorizedHttpException($message);
    }

}