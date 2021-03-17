<?php
use yii\helpers\Html;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
    <head>
        <meta charset="<?= Yii::$app->charset ?>">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?= Html::csrfMetaTags() ?>
        <title><?= Html::encode($this->title) ?></title>
        <?php $this->head() ?>
        <script type="text/javascript">
            function setFontSize(fontSize){
                var html = 'body, body *{font-size:'+fontSize+'px !important;}';
                $.cookie('globalFontSize', fontSize, {
                    path:'/'
                });
                return html;
            }

            function showFontSize(){
                $('#fontSize').html(globalFontSize);
            }

            var globalFontSize = parseInt($.cookie('globalFontSize'));
            if(isNaN(globalFontSize) || !globalFontSize){
                globalFontSize = 13;
            }
            document.write('<style id="globalFontSize">');
            document.write(setFontSize(globalFontSize));
            document.write('</style>');

            $(document).ready(function(){
                showFontSize();
                $('#globalFontSizeZoomIn').click(function(){
                    $('#globalFontSize').html(setFontSize(++globalFontSize));
                    showFontSize(globalFontSize);
                    return false;
                });

                $('#globalFontSizeZoomOut').click(function(){
                    $('#globalFontSize').html(setFontSize(--globalFontSize));
                    showFontSize(globalFontSize);
                    return false;
                });
            });
            
        </script>
    </head>
    <body style="overflow-y:hidden">
    <?= $content ?>
<?php $this->endPage() ?>
