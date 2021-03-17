<?php 

include_once(Yii::getAlias('@view/common/header.php')); 
$array = $task->toArray();
?>
<div class='block-container'>
    <div class='container-content'>
        <form action="/project/save-task" name='UploadForm' method="post">
            <input type='hidden' name='id' value='<?=$task['id'] ?>'/>
            <input type='hidden' name='project_id' value='<?=$project->id ?>'/>
            <input type='hidden' name='task_id' value='<?=$task->task_id ?>'/>
            <table border=0 cellspacing="10" width="100%">
                <col width='130' style='text-align:rigth;' align="right"/>
                <col width='*' align="left"/>
                <tr>
                    <td class='red'>任务名称</td>
                    <td>
                        <input type="text" name='name' style='width:70%' value='<?= isset($task['name']) ? $task['name'] : ''?>'>
                        <?php if($parentTask != null):?>
                            <span>【<?= $parentTask->name ?>】子任务</span>
                        <?php endif;?>
                    </td>
                </tr>
                <tr>
                    <td class='red'>任务优先级</td>
                    <td>
                         <select name='priority'>
                            <option value=''>--请选择优先级--</option>
                            <?php foreach($priorities as $key=>$name):?>
                                <?php
                                    $selected = '';
                                    if($task->priority == $key){
                                        $selected = 'selected=true';
                                    }
                                ?>
                                <?= "<option value='{$key}'{$selected}>{$name}</option>" ?>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class='red'>任务类型</td>
                    <td>
                        <select name='type'>
                            <option value=''>--请选择任务类型--</option>
                            <?php foreach($types as $category):?>
                                <?php
                                    $selected = '';
                                    if($task->type == $category->id){
                                        $selected = 'selected=true';
                                    }
                                ?>
                                <?= "<option value='{$category->id}'{$selected}>{$category->name}</option>" ?>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class='red'>发布时间</td>
                    <td>
                        <input type="text" name='publish_time' id='publish_time' value='<?= $array['publish_time'] ?>' class='datepicker' placeholder='任务推送时间' readonly>
                    </td>
                </tr>
                <tr>
                    <td>预期完成时间</td>
                    <td>
                        <input type="text" name='expected_finish_time' id='expected_finish_time' value='<?= $array['expected_finish_time'] ?>'  class='datepicker' placeholder='可在任务实施时填写' readonly>
                    </td>
                </tr>
                <tr>
                    <td>任务难度</td>
                    <td>
                        <span id='slider' style="width:200px; display: inline-block; "></span>
                        <input type="text" name='difficulty' value='<?= $task['difficulty'] ?>' readonly style='width:120px;margin-left: 20px;' placeholder='可在任务实施时填写'>
                    </td>
                </tr>
                <!-- 仅新项目和为分配的项目可以指定接收人 -->
                <?php if($task['id'] <= 0 || $receiver == null):?>
                <tr>
                    <td>实施人</td>
                    <td>
                        <input type="text" id='receiver' value='<?= $receiver != null ? $receiver->real_name : '' ?>' placeholder='未指定则为领取任务' >
                        <input type="hidden" name='receive_user_id' id='receiverId'value='<?= $array['receive_user_id'] ?>'>
                    </td>
                </tr>
                <?php endif;?>
                <tr>
                    <td class='red'>任务内容</td>
                    <td>
                        <textarea name='description' id='description' rows="20" cols="40">
                            <?= $task['description'] ?>
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
    $("select[name='type']" ).selectmenu({
        'width' : 200,
        'height' : 20
    });
    $("select[name='priority']" ).selectmenu({
        'width' : 200,
        'height' : 20
    });
    $("button").button();
    var difficulty = $('input[name="difficulty"]');
    var value = difficulty.val();
    if(!value){
        value = 0;
    }
    $("#slider").slider({
        value: value * 100,
        slide: function(event, ui) {
            difficulty.val(ui.value / 100);
        }
    });

    $('#receiver').keyup(function(){
        if($(this).val() === ''){
            $('#receiverId').val('');
        }
    });


    $('#receiver').click(function(){
        var that = $(this);
        request('/member/list', function(rep){
            var data = rep.data;
            for(var i in data){
                data[i] = {
                    label : data[i].username + ' ' + data[i].real_name + ' [' + data[i]['id'] + ']',
                    value : data[i].real_name
                };
            }
            that.autocomplete({
                source : data, 
                select : function(event, ui){
                    //匹配id
                    var id = ui.item.label.replace(/.*\[(\d+)\]$/, '$1');
                    $('#receiverId').val(id);
                }
            });
        }, false);
        
    });

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