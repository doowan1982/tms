<?php 
use yii\widgets\LinkPager;
use app\records\Task;
include_once(Yii::getAlias('@view/common/header.php'));
$parameters = $this->context->parameters;
?>
<div class='block-container'>
    <div class='container-content'>
        <div class='container-form' >
            <form action="/my/tasks" method="get" class='float-left'>
                <input type="text" name='name' placeholder='任务描述' class='input-100' value='<?= isset($parameters['name']) ? $parameters['name'] : ''?>'>
                <input type="text" name='start_time' id='startTime' placeholder='接收起始时间' class='input-100' value='<?= isset($parameters['start_time']) ? $parameters['start_time'] : ''?>'>
                <input type="text" name='end_time' id='endTime' placeholder='接收截至时间' class='input-100' value='<?= isset($parameters['end_time']) ? $parameters['end_time'] : ''?>'>
                <select name='status'>
                    <option value=''>--状态--</option>
                    <?php foreach($status as $key=>$name):?>
                        <?php
                            $selected = '';
                            $parameters = $this->context->parameters;
                            if(isset($parameters['status']) && $parameters['status'] == $key){
                                $selected = 'selected=true';
                            }
                        ?>
                        <?= "<option value='{$key}'{$selected}>{$name}</option>" ?>
                    <?php endforeach; ?>
                </select>
                <select name='priority'>
                    <option value=''>--优先级--</option>
                    <?php foreach($priorities as $key=>$name):?>
                        <?php
                            $selected = '';
                            $parameters = $this->context->parameters;
                            if(isset($parameters['priority']) && $parameters['priority'] == $key){
                                $selected = 'selected=true';
                            }
                        ?>
                        <?= "<option value='{$key}'{$selected}>{$name}</option>" ?>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class='submit'>查询</button>
            </form>

            <div class='float-right'>
                <button type="button" id='createTask'>创建</button>
                <button type="button" id='myPublishedTasks'>我发布的</button>
            </div>

            <div class='float-clear'></div>
        </div>
        <?php
            $pagination = app\helpers\Helper::getPaginationHtml($tasks);
        ?>
        <div class='table-container'>
            <div class='paginattion-container'><?= $pagination ?></div>
            <table border=0 cellpadding=0 cellspacing=1 class=table-data width='100%'>
                <thead>
                <tr>
                    <td width="60">编号</td>
                    <td width="*">项目名称<br>任务名称</td>
                    <td width="50">优先级</td>
                    <td width="50">难度</td>
                    <td width="90">任务类型</td>
                    <td width="80">活跃子任务</td>
                    <td width="80">状态</td>
                    <td width="100">发布人</td>
                    <td width="120">发布时间<br>接收时间<br>预期完成时间<br>实际完成时间</td>
                    <td width="120">最后更新时间</td>
                    <td width='150'>操作</td>
                </tr>
                </thead>
                <tbody>
                <?php if($tasks->totalCount > 0):?>
                    <?php foreach($tasks->getModels() as $task):?>
                        <?php 
                            $publisher = $task->publisher;
                            if($publisher->id == $this->context->member->id){
                                $publisher->real_name = '我自己';
                            }
                            $receiver = '暂无';
                            if($task->receive_user_id){
                                $receiver = $task->receiver->username;
                            }
                            $array = $task->toArray();
                        ?>
                        <tr>
                            <td><?=$task->id?></td>
                            <td><a href='/my/tasks?project_id=<?=$task->project_id?>' title='查看该项目任务'><span class='projectName'><?=$task->project->name?></span></a><br><a href='/project/task-detail?id=<?=$task['id']?>' class='detail' title='查看详情'><?= $task['name'] ?></td>
                            <td><?= $priorities[$task['priority']] ?></td>
                            <td><?= $task['difficulty'] ?></td>
                            <td><?= $task->category->name ?></td>
                            <td>
                                <?php if($task['fork_activity_count'] > 0):?>
                            <a href='/my/tasks?project_id=<?= $task->project_id?>&main_task_id=<?= $task->id ?>'><?= $task['fork_activity_count'] ?></a>
                                <?php else:?>
                            <?= $task['fork_activity_count'] ?>
                                <?php endif;?>
                            </td>
                            <td><?= $status[$task['status']] ?></td>
                            <td><?= $publisher->real_name ?></td>
                            <td>
                            <?= $array['publish_time'] ?><br>
                            <?= $array['receive_time'] ?><br>
                            <?= $array['expected_finish_time'] ?><br>
                            <?= $array['real_finish_time'] ?>
                            </td>
                            <td><?= $array['update_time'] ?></td>
                            <td>
                                <a href='/project/edit-task?project_id=<?=$task['project_id']?>' title='为当前项目新建任务'>新建任务</a>
                                <br>
                                <a href='/project/edit-task?project_id=<?=$task['project_id']?>&task_id=<?= $task['id']?>' title='为当前任务建立子任务'>新建子任务</a><br>
                                <?php if($this->context->member->id === $task->publisher->id && $task->status != Task::COMPLETE_STATUS):?>
                                    <!-- 编辑修改自己创建的任务 -->
                                    <a href="/project/edit-task?id=<?=$task['id']?>&project_id=<?=$task['project_id']?>">修改</a>
                                    <a href='/project/delete-task' data-id='<?=$task['id']?>' class='delete'>删除</a>
                                <?php endif;?>
                                <?php if($task->status == Task::WAITTING_ADVANCE_STATUS):?>
                                    <a href='/my/processing-task' data-id='<?=$task['id']?>' class='processing'>实施</a>
                                    <a href='/my/transfer?task_id=<?=$task['id']?>'  data-id='<?=$task['id']?>'  class='transfer'>转交</a>
                                <?php elseif($task->status == Task::ADVANCE_STATUS):?>
                                    <a href='/my/transfer?task_id=<?=$task['id']?>'  data-id='<?=$task['id']?>'  class='transfer'>转交</a>
                                    <a href='/my/code-fragment?task_id=<?=$task['id']?>' data-id='<?=$task['id']?>' class='terminate'>完成</a>
                                <?php endif;?>
                            </td>
                        </tr>
                    <?php endforeach;?>
                <?php else:?>
                    <tr><td colspan="12" align="center">暂无数据</td></tr>
                <?php endif;?>
                </tbody>
            </table>
            <div class='paginattion-container'><?= $pagination ?></div>
        </div>
    </div>
</div>
<script type='text/html' id='searchTpl'>
    <div id='searchProject'>
        <div class='container-content'>
            <form action='/project/search' method='get'>
            <input type="text" name='name' value="{{$data.name}}" placeholder='项目名称' class='input-100'>
            <button type="button" id='searchProjectButton'>查询</button>
            </form>
        </div>
        <div class='table-container'>
            <table border=0 cellpadding=0 cellspacing=1 class=table-data width='100%'>
                <thead>
                <tr>
                    <td width="80">编号</td>
                    <td width="*">项目名称</td>
                    <td width="100">项目版本</td>
                    <td width='150'>操作</td>
                </tr>
                </thead>
                <tbody>
                {{if $data.list.length > 0}}
                    {{each $data.list}}
                        <tr>
                            <td>{{$value['id']}}</td>
                            <td>{{$value['name']}}</td>
                            <td>{{$value['version_number']}}</td>
                            <td>
                                <a href="/project/edit-task?project_id={{$value['id']}}">新增任务</a>
                            </td>
                        </tr>
                    {{/each}}
                {{else}}
                    <tr><td colspan="4" align="center">暂无数据</td></tr>
                {{/if}}
                </tbody>
            </table>
        </div>
    </div>
</script>
<!-- 转交人 -->
<script type='text/html' id='transferTpl'>
    <div style='width:200px;'>
        <div class='container-content'>
            <form action='/project/search' method='get'>
            <input type="text" name='name' id='transferPepole' placeholder='请选择转交人' class='input-200'>
            <input type="hidden" id='transferPepoleId'/>
            </form>
        </div>
    </div>
</script>

<script type='text/html' id='setTaskTpl'>
    <div class='container-content' style='width:400px; height:100px'>
        <table width='100%'>
            <tr>
                <td>预期完成时间</td>
                <td><input type="text" name='expected_finish_time' value="{{$data.expected_finish_time}}" id='expectedFinishTime' placeholder='预计完成时间' class='input-200'></td>
            </tr>
            <tr>
                <td>任务难度</td>
                <td style="padding:5px 0px"><span id='slider' style="width:200px; display: inline-block; "></span>
                <input type="text" id='difficulty' name='difficulty' value='{{$data.difficulty}}' readonly style='width:50px;margin-left: 20px;' placeholder='任务难度'></td>
            </tr>
        </table>
    </div>
</script>
<!-- 载入项目搜索 -->
<?php
include_once(Yii::getAlias('@view/jstpl/projectSearch.php'));
?>
<script type='text/javascript'>

    $("button").button();

    var checkboxGroup = new CheckboxGroup();

    $('#reciveTask').click(function(){
        alert(checkboxGroup.idArray);
    });

    $('#createTask').click(function(){
        searchProject();
    });

    $('#myPublishedTasks').click(function(){
        window.location.href = '/my/published-tasks';
    });

    $('body').on('click', '#searchProjectButton', function(){
        var name = $('#searchProject').find('input[name="name"]').val();
        searchProject(name);
        return false;
    });
    
    $('.delete').click(function(){
        var that = $(this);
        Dialog.confirm({
            text: '确定删除该任务？',
            yes: function() {
                var data = {id : that.attr('data-id')};
                request(that.attr('href'), function(rep){
                    Dialog.message(rep.message);
                    that.parents('tr').remove();
                }, data);
                return true;
            }
        });
        return false;
    });

    $('.processing').click(function(){
        var that = $(this);
        var id = that.attr('data-id');
        request('/my/set-task-before-process?id='+id, function(rep){
            var data = rep.data;
            var html = template('setTaskTpl', rep.data);
            Dialog.content(html, {
                title: '设置任务完成时间及难度',
                width: 'auto',
                height: 'auto',
                buttons: [{
                    text : '确定',
                    click : function(){
                        var expectedFinishTime = $('#expectedFinishTime');
                        var difficulty = $('#difficulty');
                        if(!expectedFinishTime.val()){
                            Dialog.message('请设置预期完成时间');
                            return;
                        }
                        request(that.attr("href"), function(rep){
                            window.location.reload();
                        }, {
                            'id' : that.attr('data-id'),
                            'expected_finish_time' : expectedFinishTime.val(),
                            'difficulty': difficulty.val()
                        });
                    }
                },{
                    text : '取消',
                    click : function(){
                        $(this).dialog("close");
                    }
                }],

                open: function(event, ui){
                    var difficulty = $('#difficulty');
                    $("#slider").slider({
                        value: parseInt(difficulty.val()) * 100,
                        slide: function(event, ui) {
                            difficulty.val(ui.value / 100);
                        }
                    });
                }
            });
        });
        return false;
    });

    $('body').delegate('#expectedFinishTime', 'focus', function(){
        $(this).datetimepicker({
            language: 'zh-CN', 
            timeText: '时分',
            controlType: 'select',
            oneLine: true,
            dateFormat: 'yy-mm-dd',
            timeFormat : 'HH:mm',
        })
    });

    $('.transfer').click(function(){
        var that = $(this);
        request(that.attr("href"), function(rep){
            var title = '转交任务';
            var html = $(template('transferTpl', {
                'taskId' : that.attr('data-id')
            }));
            Dialog.content(html, {
                title: title,
                minWidth: '100px',
                width: 'auto',
                buttons: [{
                    text : '确定',
                    click : function(){
                        //转交数据
                        dialog = $(this);
                        request('/my/transfer-member', function(rep){
                            dialog.dialog("close");
                            Dialog.message(rep.message);
                            that.parents('tr').remove();
                        }, {
                            'member_id' : $('#transferPepoleId').val(),
                            'task_id' : that.attr('data-id'),
                        })
                    }
                },{
                    text : '取消',
                    click : function(){
                        $(this).dialog("close");
                    }
                }],
                open : function(){
                    var data = rep.data;
                    for(var i in data){
                        data[i] = data[i].username + ' ' + data[i].real_name + '[' + data[i]['id'] + ']';
                    }
                    $(this).find('#transferPepole').autocomplete({
                        source : data, 
                        select : function(event, ui){
                            //匹配id
                            var id = ui.item.label.replace(/.*\[(\d+)\]$/, '$1');
                            $('#transferPepoleId').val(id);
                        }
                    });
                }
            });
        });
        return false;
    });

    $('.detail').click(function(){
        var that = $(this);
        var html = createDialogIframeWarpperHtml('detailDialog', that.attr('href'), 0.7, 0.8);
        Dialog.content(html, {
            title: '['+that.html()+']详情',
            open : function(event, ui){
                imgsAutoWidthInsideDialog($(event.target));
            }
        });
        return false;
    })

    $("select" ).selectmenu({
        'width' : 110,
        'height' : 20
    });
    $("input[name='name']").autocomplete();
    $.datepicker.formatDate('yy-mm-dd');
    $("#startTime").datepicker({
        altField: "#startTime",
        altFormat: "yy-mm-dd"
    });
    $("#endTime").datepicker({
        altField: "#endTime",
        altFormat: "yy-mm-dd"
    });
    $(".submit").button();
</script>
<?php 
include_once(Yii::getAlias('@view/common/footer.php'));
?>