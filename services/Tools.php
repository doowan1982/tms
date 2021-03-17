<?php
namespace app\services;
use app\records\Message;
use app\records\Setting;
use app\records\Member;
use app\records\Log;
use app\records\MemberConfig;
use app\models\sender\DbSender;
use app\models\sender\EmailSender;
use app\models\Constants;
use Yii;
class Tools extends \app\base\Service{

    /**
     * 根据类型返回消息发送者
     * @param  array $config
     * @param  string $type Constants 发送类型参数
     * @return MessageSender
     */
    public function getMessageSender($config, $type = Constants::DB_MESSAGE){
        $sender = null;
        if($type == Constants::DB_MESSAGE){
            $sender = Yii::createObject([
                'class' => DbSender::class,
            ] + $config);
            $sender = $this->getMailSender($sender);        
        }else if($type == Constants::EMAIL_MESSAGE){
            $sender = Yii::createObject([
                'class' => EmailSender::class,
            ] + $config);  
        }
        return $sender;
    }

    /**
     * 返回一条消息记录
     * @param  integer $id
     * @return Message
     */
    public function getMessageById($id){
        return Message::findOne($id);
    }

    /**
     * 将消息标记为已读
     * @param  Message $message
     * @return boolean
     */
    public function read(Message $message){
        $message->status = Message::READED;
        return $message->update() !== false;
    }

    /**
     * 消息发送并报错数据库
     * @author doowan
     * @date   2020-05-19
     * @param  Member     $member 接收人
     * @param  string     $content 消息内容
     * @param  string     $url 连接地址
     * @param  Member     $sender 发送人
     * @return boolean
     */
    public function sendMessage(Member $member, $content, $url='', Member $sender = null){
        $message = new Message([
                'status' => Message::UNREAD,
                'content' => $content,
                'receiver_id' => $member->id,
                'url' => $url,
                'sender_id' => $sender == null ? 0 : $sender->id,
            ]);
        if(!$message->validate()){
            return $this->setError($message->getErrorString());
        }
        return $message->insert() !== false;
    }

    /**
     * 发送邮箱
     * @param  Member $member
     * @param  string $title
     * @param  string $content
     * @return boolean
     */
    public function sendEmailMessage($email, $title, $content){
        $mail = Yii::$app->mailer->compose();
        $mail->setTo($email);
        $mail->setSubject($title);
        $mail->setHtmlBody($content);
        return $mail->send();
    }

    /**
     * 返回指定成员$member未读消息数量
     * @param  Member $member 
     * @return integer
     */
    public function getUnreadMessageCount(Member $member){
        return $this->getMessageQuery($member)->count();
    }

    /**
     * 返回个人消息
     * @param  Member $member
     * @param  integer $pageSize
     * @return \yii\data\DataProviderInterface
     */
    public function getMessages(Member $member, $pageSize = 30){
        return $this->getDataProvider($this->getMessageQuery($member), $pageSize);
    }

    /**
     * 返回设置信息
     * @param  string $name
     * @return array
     */
    public function getSetting($name = null){
        return Setting::find()->andFilterWhere(['name' => $name])->all();
    }


    /**
     * 返回设置信息
     * @param  string $name
     * @return Setting
     */
    public function getSettingById($id){
        return Setting::findOne($id);
    }


    /**
     * 返回设置信息
     * @param  string $name
     * @return Setting
     */
    public function getSettingByName($name){
        return Setting::findOne(['name' => $name]);
    }   

    const SETTING_CACHE_NAME = 'setting';

    /**
     * 更新设置缓存
     * @return void
     */
    public function upgradeSettingCache(){
        $data = [];
        foreach($this->getSetting() as $setting){
            $data[$setting->name] = unserialize($setting->value);
        }
        $cache = Yii::$app->cache;
        if(!$cache->exists(self::SETTING_CACHE_NAME)){
            $cache->set(self::SETTING_CACHE_NAME, []);
        }
        $cache->set(self::SETTING_CACHE_NAME, $data);
    }

    /**
     * 从缓存中提取设置参数
     * @param  array $name
     * @return SettingSerialize
     */
    public function getCacheSetting($name){
        $cache = Yii::$app->cache;
        if(!$cache->exists(self::SETTING_CACHE_NAME)){
            $cache->set(self::SETTING_CACHE_NAME, []);
        }
        $cacheData = $cache->get(self::SETTING_CACHE_NAME);
        if(isset($cacheData[$name])){
            return $cacheData[$name];
        }
        $setting = $this->getSettingByName($name);
        if($setting == null){
            return null;
        }
        $cacheData[$name] = unserialize($setting->value);
        $cache->set(self::SETTING_CACHE_NAME, $cacheData);
        return $setting->value;
    }

    /**
     * 删除
     * @param  Setting $setting
     * @return boolean
     */
    public function deleteSetting(Setting $setting){
        return $setting->delete() !== false;
    }

    /**
     * 返回管理员的用户名
     * @return array
     */
    public function getAdminList(){
        return $this->getCacheSetting('admin')->getValue();
    }

    /**
     * 查询指定用户的日志数据
     * @param  Member  $member
     * @param  array   $conditions 查询条件
     * @param  integer $pageSize   分页
     * @return DataProvider
     */
    public function getLogsByMember(Member $member, $conditions = [], $pageSize = 20){
        $conditions['user_id'] = $member->id;
        $conditions['category'] = Constants::INFO_LOG;
        $query = Log::find()->select(['message', 'create_time'])
                            ->orderBy(['id' => SORT_DESC]);
        $this->conditionFilter($query, $conditions, ['create_time', 'create_time', self::RANGE]);
        $query->andWhere($conditions);
        return $this->getDataProvider($query, $pageSize);
    }

    //当满足用户设置的邮箱消息时，
    private function getMailSender($sender){
        if($sender->member == null || $sender->task == null){
            return $sender;
        }
        $config = Yii::$app->get('userService')->getMemberConfig($sender->member, Constants::SEND_TO_EMAIL);
        if($config == null){
            return $sender;
        }
        $config = explode(',', $config->value);
        //存在设置
        if(in_array($sender->task->priority, $config)){
            return $this->getMessageSender($sender->getAttributes(), Constants::EMAIL_MESSAGE);
        }
        return $sender;
    }

    private function getMessageQuery(Member $member){
        return Message::find()->orderBy([
                'send_time' => SORT_DESC,
            ])
            ->where([
                'receiver_id' => [$member->id, 0], //指定用户和所有人的信息
                'status' => Message::UNREAD,
            ])
            ->andWhere(['>', 'expire_time', time()]);
    }

}