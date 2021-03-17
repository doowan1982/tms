<?php 
include_once(Yii::getAlias('@view/common/header.php'));
if(!$project->getIsNewRecord()){
    $project = $project->toArray();
}
?>
<div class='block-container'>
    <div class='container-content'>
        <form action="/project/save" name='UploadForm' method="post"  enctype="multipart/form-data">
            <input type='hidden' name='id' value='<?=$project['id'] ?>'/>
            <table border=0 cellspacing="10" width="100%">
                <col width='130' style='text-align:rigth;' align="right"/>
                <col width='*' align="left"/>
                <tr>
                    <td>项目名称</td>
                    <td>
                        <input type="text" name='name' style='width:80%' value='<?= isset($project['name']) ? $project['name'] : ''?>'>
                    </td>
                </tr>
                <tr>
                    <td>项目版本号</td>
                    <td>
                        <input type="text" name='version_number' value='<?= $project['version_number'] ?>' placeholder='版本号格式：1.1.xx'>
                    </td>
                </tr>
                <tr>
                    <td>版本控制</td>
                    <td>
                        <input type="text" name='vcs_host' class='input-400' value='<?= empty($project['vcs_host']) ? "https://git.tesoon.com" : $project['vcs_host'] ?>' placeholder='版本控制服务器主机或者域名'/>
                    </td>
                </tr>
                <tr>
                    <td>预期开始时间</td>
                    <td>
                        <input type="text" name='expected_start_time' id='expectedStartTime' value='<?= $project['expected_start_time'] ?>' class='datepicker' readonly>
                    </td>
                </tr>
                <tr>
                    <td>预期完成时间</td>
                    <td>
                        <input type="text" name='expected_end_time' id='expectedEndTime' value='<?= $project['expected_end_time'] ?>' class='datepicker' readonly>
                    </td>
                </tr>
                <tr>
                    <td>项目文档</td>
                    <td>
                        <input type="file" name='project_doc_attachement' id='projectDocAttachement'><a href='<?= $project['project_doc_attachement'] ?>' title='下载'><?= $project['project_doc_attachement'] ?></a>
                    </td>
                </tr>
                
                <tr>
                    <td>项目简述</td>
                    <td>
                        <textarea name='description' id='description' rows="20" cols="40">
                            <?= $project['description'] ?>
                        </textarea> 
                    </td>
                </tr>
                <tr>
                    <td colspan="2" align="left">
                        <button type="submit" class='submit'>提交</button>
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
    $("input[name='name']").autocomplete();
    $("button").button();

    var editor;
    KindEditor.ready(function(K) {
        editor = K.create('textarea[name="description"]', {
            allowFileManager : true,
            uploadJson : '/project/upload',
            filePostName: 'file',
            width : '80%',
            height: '500px',
            extraFileUploadParams: csrf(),
        });
    });

    $('form').submit(function(){
        $('#description').text(editor.html());
        createCsrfBeforeSubmit($(this));
        return true;
    });
</script>
<?php 
include_once(Yii::getAlias('@view/common/footer.php'));
?>