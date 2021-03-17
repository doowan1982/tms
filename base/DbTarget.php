<?php
namespace app\base;
use yii\base\InvalidConfigException;
use yii\helpers\VarDumper;
class DbTarget extends \yii\log\DbTarget{

    public function export(){
        if ($this->db->getTransaction()) {
            $this->db = clone $this->db;
        }
        $app = \Yii::$app;
        $actionName = '';
        if($app->controller != null){
            $actionName = $app->controller->getUniqueId();
            if($app->controller->action != null){
                $actionName = $app->controller->action->getUniqueId();
            }
        }

        $userId = 0;
        if(!$app->user->isGuest){
            $userId = $app->user->getIdentity()->id;
        }

        $tableName = $this->db->quoteTableName($this->logTable);
        $sql = "INSERT INTO $tableName ([[level]], [[action_name]], [[category]], [[message]], [[user_id]], [[prefix]], [[create_time]])
                VALUES (:level, :action_name, :category, :message, :user_id, :prefix, :create_time)";
        $command = $this->db->createCommand($sql);
        foreach ($this->messages as $message) {
            list($text, $level, $category, $timestamp) = $message;
            if (!is_string($text)) {
                // exceptions may not be serializable if in the call stack somewhere is a Closure
                if ($text instanceof \Throwable || $text instanceof \Exception) {
                    $text = (string) $text;
                } else {
                    $text = VarDumper::export($text);
                }
            }
            if ($command->bindValues([
                    ':level' => $level,
                    ':action_name' => $actionName,
                    ':category' => $category,
                    ':message' => $text,
                    ':user_id' => $userId,
                    ':prefix' => preg_replace('/^\[(.*?)\].*/m', '$1', $this->getMessagePrefix($message)),
                    ':create_time' => (int)$timestamp,
                ])->execute() > 0) {
                continue;
            }
            throw new \yii\log\LogRuntimeException('Unable to export log through database!');
        }
    }

}