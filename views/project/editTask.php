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
                    <td class='red'>所在项目</td>
                    <td>
                        <span><a href='#' id='modifyTheProject' title='点击修改所在项目' class='projectName'><?= $project->name ?></a></span>
                    </td>
                </tr>
                <tr>
                    <td class='red'>任务名称</td>
                    <td>
                        <input type="text" name='name' style='width:70%' value='<?= isset($task['name']) ? $task['name'] : ''?>' placeholder='请简要填写便于搜索，详情可补充于任务内容中'>
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
                    <td>所在主任务</td>
                    <td>
                        <?php 
                            $parentTaskId = 0;
                            $parentTaskName = '暂无';
                            if($parentTask != null){
                                $parentTaskId = $parentTask->id;
                                $parentTaskName = $parentTask->name;
                            }
                        ?>
                        <span><a href='#' title='<?= $parentTaskId > 0 ? '修改主任务' : '设置主任务'?>' id='changeParentTask'><?= $parentTaskName?></a></span>&nbsp;&nbsp;<span id='removeMainTask'></span>
                    </td>
                </tr>
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
<!-- 载入项目搜索 -->
<?php
include_once(Yii::getAlias('@view/jstpl/projectSearch.php'));
?>

<script type='text/html' id='tasksTpl'>
    <div id='searchTasks'>
        <div class='container-content'>
            <form action='/project/tasks-json' method='get'>
            <input type="text" name='name' value="{{$data.name}}" placeholder='任务名称' class='input-100'>
            <button type="button" id='searchTasksButton'>查询</button>
            </form>
        </div>
        <div class='taskList table-container'>
        [data/]
        </div>
    </div>
</script>

<script type="text/html"  id='taskListTpl'>
    <table border=0 cellpadding=0 cellspacing=1 class=table-data width='100%'>
        <thead>
        <tr>
            <td width="80">编号</td>
            <td width="*">任务名称</td>
            <td width="120">发布人/发布时间</td>
            <td width="130">接收人/接收时间</td>
            <td width='80'>操作</td>
        </tr>
        </thead>
        <tbody>
        {{if $data.list.length > 0}}
            {{each $data.list}}
                <tr>
                    <td>{{$value['id']}}</td>
                    <td>{{$value['name']}}</td>
                    <td>{{$value.publisher.real_name}}<br>{{$value.publish_time}}</td>
                    <td>{{$value.receiver.real_name}}<br>{{$value.receive_time}}</td>
                    <td>{{if $value.unselected}}-{{else}}<a href='#' class='chooseToParentTask' data-id='{{$value.id}}' data-name='{{$value.name}}'>选择</a>{{/if}}</td>
                </tr>
            {{/each}}
        {{else}}
            <tr><td colspan="5" align="center">暂无数据</td></tr>
        {{/if}}
        </tbody>
    </table>
</script>>

<script type="text/javascript" src="/js/kindeditor/kindeditor-all-min.js"></script>
<script type='text/javascript'>
    function toggleRemoveMainTaskButton(){
        var obj = $('#removeMainTask');
        if($('input[name=task_id]').val() > 0){
            obj.html("<a href='#' title='移除'>X</a>");
        }else{
            obj.html('');
        }
    }
    toggleRemoveMainTaskButton();
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

    $('body').on('click', '.chooseTheProject', function(){
        var project = $('input[name=project_id]');
        var that = $(this);
        project.val(that.attr('data-id'));
        $('.projectName').html(that.attr('data-name'));
        Dialog.getDialogContainer('dialog').dialog("close");
    })

    $('#modifyTheProject').click(function(){
        var projectId = $('input[name=project_id]').val();
        searchProject('', {
            'actions' : function(value){
                if(value['id'] == projectId){
                    return '-';
                }
                return '<a href="#" class="chooseTheProject" data-id="'+value['id']+'" data-name='+value['name']+'>选择</a>';
            },
            'complete' : function(data){
                
            }
        });
        return false;
    });

    $('#removeMainTask').on('click', 'a', function(){
        $('input[name=task_id]').val(0);
        $('#changeParentTask').html('暂无','设置主任务');
        $(this).remove();
    });

    $('#changeParentTask').click(function(){
        var that = $(this);
        var projectId = $('input[name=project_id]').val();
        loadTaskDialogContent(projectId);
        return false;
    });

    //加载搜索的对话框
    function loadTaskDialogContent(id, name){
        var that = $('#changeParentTask');
        name = name || '';
        var taskId = $('input[name=id]').val();
        var url = '/project/get-main-task-candidates?project_id='+id+'&task_id='+taskId;
        if(name){
            url += "&name="+name;
        }
        request(url, function(rep){
            for(var i in rep.data){
                var data = rep.data[i];
                data.receiver = data.receiver || {
                    'real_name' : '暂无',
                }
            }
            var html = template('taskListTpl', {
                    'list' : rep.data
                });
            var searchTasks = $('#searchTasks');
            if(searchTasks.length == 0){
                var searchBlock = template('tasksTpl', {
                    'name' : name
                });
                html = searchBlock.replace('[data/]', html);
            }else{
                searchTasks.find('.taskList').html(html);
                return;              
            }

            Dialog.content(html, {
                title: '选择主任务【仅限于所在项目】',
                width : '70%'
            });
        });
    }

    $('body').on('click', '#searchTasksButton', function(){
        var id = $('input[name=project_id]').val();
        var name = $('#searchTasks').find('input[name=name]').val();
        loadTaskDialogContent(id, name);
    })

    $('body').on('click', '.chooseToParentTask', function(){
        var task = $(this);
        $('input[name=task_id]').val(task.attr('data-id'));
        $('#changeParentTask').html(task.attr('data-name')).attr('title','修改主任务');
        $('#searchTasks').remove();
        toggleRemoveMainTaskButton();
        Dialog.getDialogContainer('dialog').dialog("close");
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