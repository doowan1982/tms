<?php
namespace app\models;

use Yii;
use app\base\BaseModel;
use yii\web\UploadedFile;
class UploadForm extends BaseModel
{
    /**
     * @var UploadedFile file attribute
     */
    public $file;

    /**
     * 文件名
     * @var string
     */
    public $fileName;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['file'], 'file', 'checkExtensionByMimeType' => false, 'extensions' => 'jpeg, jpg, png, docx, doc, xls, xlsx'],
        ];
    }

    /**
     * 上传表单
     * @author doowan
     * @date   2020-03-26
     * @param  string     $name      上传文件参数名称
     * @param  string     $filename  保存的文件名称，默认为hash
     * @param  string     $path      保存路径
     * @param  string     $uploadDir 上传目录
     * @return UploadForm
     */
    public static function upload($name, $filename='', $path='', $uploadDir=''){
        $model = new static();
        $model->file = UploadedFile::getInstanceByName($name);
        if(!$model->file){
            return $model;
        }
        if(!$uploadDir){
            $uploadDir = Yii::$app->params['uploadDir'];
        }
        $extension = $model->file->extension;
        if(in_array($extension, ['jpeg', 'jpg', 'png'])){
            $uploadDir .= Yii::$app->params['picPath'];
        }else if(in_array($extension, ['doc', 'docx', 'xls', 'xlsx'])){
            $uploadDir .= Yii::$app->params['filePath'];
        }
        if(!$path){
            $path = date('y/m/d', time());
        }
        $uploadDir .= "/{$path}";
        if(!is_dir($uploadDir)){
            @mkdir($uploadDir, 0777, true);
        }
        if(!$filename){
            $filename = md5(time().rand(1,10000));
        }
        if ($model->file && $model->validate()) {
            $model->fileName = "{$uploadDir}/{$filename}.{$extension}";
            $model->file->saveAs($model->fileName);
        }
        return $model;
    }

    /**
     * 返回文件路径加名称
     * @author doowan
     * @date   2020-03-26
     * @return string
     */
    public function getFileName(){
        return str_replace(Yii::getAlias('@webroot'), '', $this->fileName);
    }

}