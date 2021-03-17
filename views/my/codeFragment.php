<?php 
use app\helpers\Helper;
include_once(Yii::getAlias('@view/common/header.php')); 
$array = $task->toArray();
?>
<div class='block-container'>
    <div class='container-content'>
        <form action="/my/finish" name='finishTask' method="post">
            <input type='hidden' name='task_id' id='taskId' value='<?=$task['id'] ?>'/>
            <table border=0 cellspacing="10" width="100%">
                <col width='60' style='text-align:rigth;' align="right"/>
                <col width='*' align="left"/>
                <tr>
                    <td>任务名称</td>
                    <td>
                        <?= $task['name'] ?>
                    </td>
                </tr>
                <tr>
                    <td>完成时间</td>
                    <td>
                         <input type="text" name='real_finish_time'class='datepicker' id='realFinishTime' placeholder='任务实际完成时间' readonly>
                    </td>
                </tr>
                <tr>
                    <td>内容</td>
                    <td>
                        <textarea name='fragment' id='fragment' rows="15" cols="40">
                        </textarea><span>主要为具体内容/代码段</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" align="left">
                        <button type="submit" class='submit'>完成</button>
                        <button type="reset">重置</button>
                    </td>
                </tr>
            </table>
        </form>
    </div>
    <div class='float-clear'></div>
</div>
<script type="text/javascript" src="/js/kindeditor/kindeditor-all-min.js"></script>
<script type='text/javascript'>
    $("button").button();
    var editor;
    KindEditor.ready(function(K) {
        editor = K.create('textarea[name="fragment"]', {
            allowFileManager : true,
            uploadJson : '/project/upload',
            filePostName: 'file',
            width : '80%',
            height: '500px',
            items: ['undo', 'redo', '|', 'code', 'image', '|', 'forecolor', 'fontsize','bold', 'underline', 'link'],
            extraFileUploadParams: csrf(),
        });
    });

    $('form').submit(function(){
        request('/my/finish', function(rep){
            Dialog.message(rep.message, function(){
                window.location.href = '/my/tasks';
            });
        }, {
            'fragment' : editor.html(),
            'task_id' : $('#taskId').val(),
            'real_finish_time' : $('#realFinishTime').val()
        });
        return false;
    });
</script>
<?php
include_once(Yii::getAlias('@view/common/footer.php'));
?>