<?php 
use app\records\Task;
use \Yii as Yii;
include_once(Yii::getAlias('@view/common/header.php'));
?>
<div class='block-container'>
    <div class='container-content'>
        <div class='container-form' >
            <form action="/my/published-tasks" method="get" class='float-left'>
                <input type="text" name='name' placeholder='任务描述' class='input-100' value='<?= isset($parameters['name']) ? $parameters['name'] : ''?>'>
                <input type="text" name='start_time' id='startTime' placeholder='起始时间' class='input-100' value='<?= isset($parameters['start_time']) ? $parameters['start_time'] : ''?>'>
                <input type="text" name='end_time' id='endTime' placeholder='截至时间' class='input-100' value='<?= isset($parameters['end_time']) ? $parameters['end_time'] : ''?>'>
                <select name='type'>
                    <option value=''>--类型--</option>
                    <?php foreach($types as $category):?>
                        <?php
                            $selected = '';
                            $parameters = $this->context->parameters;
                            if(isset($parameters['type']) && $parameters['type'] == $category->id){
                                $selected = 'selected=true';
                            }
                        ?>
                        <?= "<option value='{$category->id}'{$selected}>{$category->name}</option>" ?>
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
                <button type="submit" class='submit'>查询</button>
            </form>
            <div class='float-right'>
                <button type="button" id='allocateTask' title='仅针对待领取任务'>分发</button>
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
                    <td width="40"><label><input type='checkbox' class='checkbox contextCheckbox'/></label></td>
                    <td width="60">编号</td>
                    <td width="*">项目名称<br>任务名称</td>
                    <td width="50">优先级</td>
                    <td width="50">难度</td>
                    <td width="90">任务类型</td>
                    <td width="80">状态</td>
                    <td width="150">发布时间<br>最后修改时间</td>
                    <td width="150">实施时间<br>实施人</td>
                    <td width="150">预期完成时间<br>实际完成时间</td>
                    <td width='150'>操作</td>
                </tr>
                </thead>
                <tbody>
                <?php if($tasks->totalCount > 0):?>
                    <?php foreach($tasks->getModels() as $task):?>
                        <?php
                            $array = $task->toArray();
                            $receiver = '虚位以待';
                            if($task->receive_user_id){
                                $receiver = "{$array['receive_time']}<br><a href='/my/published-tasks?receive_user_id={$task->receiver->id}' title='查看该用户的记录'>{$task->receiver->real_name}</a>";
                            }
                            if($task->receive_user_id == $this->context->getMember()->id){
                                $receiver = "{$array['receive_time']}<br>自问自答";
                            }
                        ?>
                        <tr>
                            <td><label><input type='checkbox' class='checkbox independentCheckbox' data-status='<?= $task->status?>' value='<?=$task->id?>'/></label></td>
                            <td><a href='/project/tasks?project_id=<?=$task->project_id?>&task_id=<?=$task->id?>'><?=$task->id?></a></td>
                            <td><a href='/my/published-tasks?project_id=<?=$task->project_id?>' title='查看该项目任务'><?=$task->project->name?></a><br><a href='/project/task-detail?id=<?=$task['id']?>' class='detail' title='查看详情'><?= $task['name'] ?></td>
                            <td><?= $priorities[$task['priority']] ?></td>
                            <td><?= $task['difficulty'] ?></td>
                            <td><?= $task->category->name ?></td>
                            <td><?= $status[$task['status']] ?></td>
                            <td><?= $array['publish_time'] ?><br><?= $array['update_time'] ?></td>
                            <td><?= $receiver ?></td>
                            <td><?= $array['expected_finish_time'] ?><br><?= $array['real_finish_time'] ?></td>
                            <td>
                                <?php if($task->status == Task::COMPLETE_STATUS):?>
                                    <a href='/project/edit-task?project_id=<?=$task['project_id']?>&task_id=<?= $task['id']?>' title='为当前任务建立子任务'>新建子任务</a><br>
                                    <a href="/project/finish-info?id=<?=$task['id']?>" title='任务完成 信息' class='finishInfo'>信息</a>
                                <?php endif;?>
                                <?php if($task->status != Task::COMPLETE_STATUS):?>
                                    <a href="/project/edit-task?id=<?=$task['id']?>&project_id=<?=$task['project_id']?>">修改</a>
                                    <a href='/project/delete-task' data-id='<?=$task['id']?>' class='delete'>删除</a>
                                <?php endif;?>
                                <a href='/project/task-lifecycle?task_id=<?=$task['id']?>' data-id='<?=$task['id']?>' class='taskLifecycle' title='该项目的进程周期'>进度</a>
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

<script type='text/html' id='allocateTpl'>
    <div>
        <div class='container-content'>
            <form action='/project/search' method='get'>
            <input type="text" name='name' id='receiver' placeholder='请选择接收人' class='input-200'>
            <input type="hidden" id='receiverId'/>
            </form>
        </div>
    </div>
</script>
<script type='text/javascript'>
    
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
    
    $('.finishInfo').click(function(){
        var that = $(this);
        request(that.attr('href'), function(rep){
            var html = createDialogWarpperHtml('detailDialog', rep.data, 0.7, 0.8);
            Dialog.content(html, {
                title: '查看完成信息'
            });
        });
        return false;
    });

    var checkboxGroup = new CheckboxGroup();

    $('#allocateTask').click(function(){
        var idArray = checkboxGroup.getValues(function(checkbox){
            return parseInt(checkbox.attr('data-status')) === 1; //仅获取待领取的任务
        });
        if(idArray.length === 0){
             Dialog.message('请选择需要分发的任务');
             return false;
        }
        for(var i in idArray){
            idArray[i] = idArray[i].val();
        }
        request('/member/list', function(rep){
            var title = '批量分发';
            var html = $(template('allocateTpl',{}));
            Dialog.content(html, {
                title: title,
                width: '300px',
                buttons: [{
                    text : '确定',
                    click : function(){
                        //转交数据
                        dialog = $(this);
                        request('/project/batch-allocate-task', function(rep){
                            dialog.dialog("close");
                            Dialog.message(rep.message);
                        }, {
                            'member_id' :  $('#receiverId').val(),
                            'task_id' : idArray
                        });
                    }
                },{
                    text : '取消',
                    click : function(){
                        $(this).dialog("close");
                    }
                }],
                create : function(){
                    var data = rep.data;
                    for(var i in data){
                        data[i] = data[i].username + ' ' + data[i].real_name + '[' + data[i]['id'] + ']';
                    }
                    $(this).find('#receiver').autocomplete({
                        source : data, 
                        select : function(event, ui){
                            //匹配id
                            var id = ui.item.label.replace(/.*\[(\d+)\]$/, '$1');
                            $('#receiverId').val(id);
                        }
                    });
                }
            });
        });

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

    $("button").button();
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