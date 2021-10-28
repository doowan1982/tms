<?php 
use yii\widgets\LinkPager;
use app\records\Task;
include_once(Yii::getAlias('@view/common/header.php'));
$parameters = $this->context->parameters;
?>
<div class='block-container'>
    <div class='container-content'>
        <div class='container-form' >
            <form action="/project/tasks" method="get" class='float-left' id='searchForm'>
                <input type='hidden' name='project_id' value='<?= $project->id?>' require>
                <input type="text" name='name' placeholder='任务描述' class='input-100' value='<?= isset($parameters['name']) ? $parameters['name'] : ''?>'>
                <input type="text" id='name' name='username' placeholder='用户名/手机' class='input-100' value='<?= isset($parameters['username']) ? $parameters['username'] : ''?>'>
                <input type="text" name='start_time' placeholder='实施起始时间' class='datepicker' style='width:120px;' value='<?= isset($parameters['start_time']) ? $parameters['start_time'] : ''?>'>
                <input type="text" name='end_time' placeholder='实施截至时间' class='datepicker' style='width:120px;' value='<?= isset($parameters['end_time']) ? $parameters['end_time'] : ''?>'>
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
                <input type='hidden' name='publisher_id' value="<?=isset($this->context->parameters['publisher_id']) ? $this->context->parameters['publisher_id'] : ''?>"/>
                <input type='hidden' name='receive_user_id' value="<?=isset($this->context->parameters['receive_user_id']) ? $this->context->parameters['receive_user_id'] : ''?>"/>
                <button type="submit" class='submit'>查询</button>
                <button type="button" class='reset'>清空</button>
            </form>

            <div class='float-right'>
                <button type="button" data-project-id='<?=$project->id?>' id='allocateTask' title='仅针对待领取任务'>分发</button>
                <!-- <button type="button" data-project-id='<?=$project->id?>' id='allocateTask'>删除</button> -->
                <button type="button" data-project-id='<?=$project->id?>' id='createTask'>创建</button>
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
                    <td width='40'><label><input type='checkbox' class='checkbox contextCheckbox'/></label></td>
                    <td width="80">编号</td>
                    <td width="*">所在项目<br>任务名称</td>
                    <td width="50">优先级</td>
                    <td width="50">难度</td>
                    <td width="90">任务类型</td>
                    <td width="50">是否有效</td>
                    <td width="80">子任务<br>总数/活跃</td>
                    <td width="70">状态</td>
                    <td width="120">发布时间<br/>发布人</td>
                    <td width="120">接收时间<br/>实施人</td>
                    <td width="120">预期完成时间<br/>实际完成时间</td>
                    <td width='120'>操作</td>
                </tr>
                </thead>
                <tbody>
                <?php if($tasks->totalCount > 0):?>
                    <?php foreach($tasks->getModels() as $task):?>
                        <?php 
                            $publisher = $task->publisher;
                            if($this->context->member->id == $publisher->id){
                                $publisher->real_name = '我自己';
                            }
                            $receiver = '暂无';
                            if($task->receive_user_id){
                                $receiver = "<a href='#' title='查看该成员实施的任务'  data-project-id='{$task->project_id}' form-search-id='{$task->receiver->id}' class='shortcutSearch' form-search-name='receive_user_id'>{$task->receiver->real_name}</a>";
                            }
                            $array = $task->toArray();

                            $mainTask = '';
                            if($task->mainTask){
                                $mainTask = "&nbsp;[<a href='/project/tasks?project_id={$task->mainTask->project_id}&task_id={$task->mainTask->id}' title='主任务：{$task->mainTask->name}'>{$task->task_id}</a>]";
                            }
                        ?>
                        <tr>
                            <td><label><input type='checkbox' class='checkbox independentCheckbox' data-status='<?= $task->status?>' value='<?=$task->id?>'/></label></td>
                            <td><a href='/project/tasks?project_id=<?=$task->project_id?>&task_id=<?=$task->id?>'><?=$task->id?></a></td>
                            <td><?= $task->project->name ?><br><a href='/project/task-detail?id=<?=$task['id']?>' class='detail' title='查看详情'><?= $task['name'] ?></a><?=$mainTask?></td>
                            <td><?= $priorities[$task['priority']] ?></td>
                            <td><?= $task['difficulty'] ?></td>
                            <td><?= $task->category->name ?></td>
                            <td>
                                <?php if($task->is_valid): ?>
                                    <a href='/project/terminate-task' data-id='<?=$task->id?>' title='终止任务' class='terminateTask'>是</a>
                                <?php else:?>
                                    <a href='/project/restart-task' data-id='<?=$task->id?>' title='继续任务' class='restartTask'>否</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($task['fork_task_count'] > 0):?><a href='#' class='forkTaskTree' data-id='<?=$task->id?>' title='直接间接子任务'>任务树</a><br><a href='/project/tasks?main_task_id=<?= $task->id ?>' target='_blank' title='直接子任务'><?= $task['fork_task_count'] ?></a><?php else:?>0<?php endif;?>&nbsp;/&nbsp;<?php if($task['fork_activity_count'] > 0):?><a href='/project/tasks?main_task_id=<?= $task->id ?>&task_active=0' target='_blank' title='直接活跃子任务'><?= $task['fork_activity_count'] ?></a><?php else:?><?= $task['fork_activity_count'] ?><?php endif;?>
                            </td>
                            <td><?= $status[$task['status']] ?></td>
                            <td><?= $array['publish_time'] ?><br/><a href="#" title='查看该成员发布的任务' form-search-name='publisher_id' form-search-id='<?=$publisher->id?>' class='shortcutSearch'><?= $publisher->real_name ?></a></td>
                            <td><?= $array['receive_time'] ?><br><?= $receiver ?></td>
                            <td><?= $array['expected_finish_time'] ?><br><?= $array['real_finish_time'] ?></td>
                            <td>
                                <?php if($task->status == Task::COMPLETE_STATUS):?>
                                    <a href="/project/finish-info?id=<?=$task['id']?>" title='任务完成 信息' class='finishInfo'>信息</a>
                                <?php endif;?>
                                <a href="/project/edit-task?id=<?=$task['id']?>&project_id=<?=$task['project_id']?>">修改</a>
                                <a href='/project/delete-task' data-id='<?=$task['id']?>' class='delete'>删除</a>
                                <br>
                                <a href='/project/task-lifecycle?task_id=<?=$task['id']?>' data-id='<?=$task['id']?>' class='taskLifecycle' title='该项目的进程周期'>进度详情</a>
                                <br>
                                <a href='/project/task-change-logs?task_id=<?=$task['id']?>' data-id='<?=$task['id']?>' class='taskChangeLog'>变更记录</a>
                                <br>
                                <?php if($task->fork_task_count > 0):?>
                                    <a href='#' data-id='<?=$task['id']?>' class='directStat' title='所有子任务'>任务统计</a>
                                    <br>
                                <?php endif; ?>
                                <a href='/project/edit-task?project_id=<?=$task->project_id?>&task_id=<?=$task->id?>' data-id='<?=$task['id']?>' class='taskLifecycle' title='该项目的进程周期'>新建子任务</a>
                            </td>
                        </tr>
                    <?php endforeach;?>
                <?php else:?>
                    <tr><td colspan="13" align="center">暂无数据</td></tr>
                <?php endif;?>
                </tbody>
            </table>
            <div class='paginattion-container'><?= $pagination ?></div>
        </div>
    </div>
</div>

<script type='text/html' id='changeLogTpl'>
    <div id='searchProject'>
        <div class='table-container'>
            <table border=0 cellpadding=0 cellspacing=1 class=table-data width='100%'>
                <thead>
                <tr>
                    <td width='*'>名称</td>
                    <td width='60'>优先级</td>
                    <td width='100'>类型</td>
                    <td width='50'>难度</td>
                    <td width='100'>状态</td>
                    <td width='150'>发布时间<br>期望完成时间</td>
                    <td width='80'>实施人</td>
                    <td width='150'>接收时间<br>实际完成时间</td>
                    <td width='70'>变更时间</td>
                    <td width='70'>内容</td>
                </tr>
                </thead>
                <tbody>
                {{if $data.list.length > 0}}
                    {{each $data.list}}
                    <tr>
                        <td><%- $value['name']%></td>
                        <td><%-$value['priority'] %></td>
                        <td><%-$value['type'] %></td>
                        <td><%-$value['difficulty'] %></td>
                        <td><%- $value['status']%></td>
                        <td><%-$value['publish_time'] %><br><%-$value['expected_finish_time'] %></td>
                        <td><%-$value['receiver'] %></td>
                        <td><%-$value['receive_time'] %><br><%-$value['real_finish_time'] %></td>
                        <td><%-$value['log_time'] %></td>
                        <td>
                        {{if $value['log_id']}}
                        <a href='#' class='content' data-id='{{$value['log_id']}}'>查看</td>
                        {{else}}-{{/if}}
                    </tr>
                    {{/each}}
                {{else}}
                    <tr><td colspan='10' align='center'>暂无数据</td></tr>
                {{/if}}
                </tbody>
            </table>
        </div>
    </div>
</script>

<script type='text/html' id='allocateTpl'>
    <div>
        <div class='container-content'>
            <form action='/project/search' method='get'>
            <input type="text" name='name' id='reciver' placeholder='请选择接收人' class='input-200'>
            <input type="hidden" id='reciverId'/>
            </form>
        </div>
    </div>
</script>

<script type='text/javascript' src='/js/echarts.min.js'></script>
<script type='text/html' id='statTpl'>
    <div>
        <div class='container-content'>
            <button type="button" id='tableStat'>表格</button>
            <button type="button" id='graphicsStat'>图形</button>
        </div>
        <div class='table-container'>
            <table border=0 cellpadding=0 cellspacing=1 class=table-data width='100%'>
                <thead>
                <tr>
                    <td width="*">真实姓名</td>
                    <td width="120">待实施</td>
                    <td width="120">实施中</td>
                    <td width='120'>已完成/终止</td>
                </tr>
                </thead>
                <tbody>
                {{if $data.list.length > 0 || undoTasksCount > 0}}
                    {{each $data.list}}
                        <tr>
                            <td>{{$value['receiver']['real_name']}}</td>
                            <td>{{$value['awaitTasksCount']}}</td>
                            <td>{{$value['activedTasksCount']}}</td>
                            <td>{{$value['terminateTasksCount']}}</td>
                        </tr>
                    {{/each}}
                    <tr>
                        <td>{{$data.total[0]}}</td>
                        <td>{{$data.total[1]}}</td>
                        <td>{{$data.total[2]}}</td>
                        <td>{{$data.total[3]}}</td>
                    </tr>
                    {{if $data.undoTasksCount > 0}}
                        <tr>
                            <td style='background-color: #F8F8FF'>待分发任务</td>
                            <td style='background-color: #F8F8FF' colspan="3">{{$data.undoTasksCount}}</td>
                        </tr>
                    {{/if}}
                {{else}}
                    <tr><td colspan="4" align="center">暂无数据</td></tr>
                {{/if}}
                </tbody>
            </table>
        </div>
    </div>
</script>
<script type="text/html" id='statSearchTpl'>
    <div id='chartPanel'>
        <input type='text' id='receiver' class='input-200' style="width:150px;" placeholder="实施人">
        <input type='hidden' name='reciver_id' id='reciverId' class='input-100' placeholder="实施人">&nbsp;
        <input type="text" name="stat_start_time" placeholder="起始日期" id="statStartTime" style="width:100px;" title="按任务创建时间"/>&nbsp;
        <input type="text" id="statEndTime" name="stat_end_time"  style="width:100px;"  placeholder="截止日期" title="按任务创建时间"/>&nbsp;
        <button class="statSearch">查询</button>
        [chart/]
    </div>
</script>
<script type='text/javascript'>

    $('.forkTaskTree').click(function(){
        loadForkTaskTree($(this).attr('data-id'));
        return false;
    });

    $("button").button();
    $('#createTask').click(function(){
        window.location.href = '/project/edit-task?project_id='+$(this).attr('data-project-id');
    });

    $('body').on('click', '.terminateTask', function(){
        var that = $(this);
        Dialog.confirm({
            text: '确定终止该任务？',
            yes: function(){
                request(that.attr('href'), function(rep){
                    that.html('否').attr({
                        title: '继续任务',
                        href : '/project/restart-task' 
                    }).removeClass('terminateTask').addClass('restartTask')
                    Dialog.message(rep.message);
                }, {
                    'id' : that.attr('data-id')
                });
            }
        });
        return false;
    });

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

    $('body').on('click', '.restartTask', function(){
        var that = $(this);
        Dialog.confirm({
            text: '确定重新启动该任务？',
            yes: function(){
                request(that.attr('href'), function(rep){
                    that.html('是').attr({
                        title: '继续任务',
                        href : '/project/terminate-task' 
                    }).removeClass('restartTask').addClass('terminateTask')
                    Dialog.message(rep.message);
                }, {
                    'id' : that.attr('data-id')
                });
            }
        });
        return false;
    });

    $('.shortcutSearch').click(function(){
        var that = $(this);
        var form = $('#searchForm');
        var name = that.attr('form-search-name');
        var value = that.attr('form-search-id');
        form.append("<input type='hidden' name='"+name+"' value='"+value+"'>");
        form.submit();
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
                            'member_id' :  $('#reciverId').val(),
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
                    $(this).find('#reciver').autocomplete({
                        source : data, 
                        select : function(event, ui){
                            //匹配id
                            var id = ui.item.label.replace(/.*\[(\d+)\]$/, '$1');
                            $('#reciverId').val(id);
                        }
                    });
                }
            });
        });

    });

    $("select" ).selectmenu({
        'width' : 110,
        'height' : 20
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
    });

    $('.directStat').click(function(){
        statTask('/task/stat', $(this).attr('data-id'), 'graphics');
        return false;
    });

    $('.reset').click(function(){
        $('form').find('input').each(function(){
            var that = $(this);
            if(that[0].hasAttribute('require')){
                return;
            }
            that.val('');
        });
        $("select[name='status']" ).val('').selectmenu('refresh');
        $("select[name='type']" ).val('').selectmenu('refresh');
        $("select[name='priority']" ).val('').selectmenu('refresh');
    });

    $("#name").autocomplete({
        delay: 500,
        source : function(req, rep){
            request('/member/list?name='+req.term, function(reponse){
                var data = [];
                for(var i in reponse.data){
                    data.push(reponse.data[i].username);
                }
                rep($.grep(data, function(item){
                    return true;
                }))
            })
        }
    });

    $('.taskChangeLog').click(function(){
        request('/task-change-log/index?task_id='+$(this).attr('data-id'), function(rep){
            
            var html = $(template('changeLogTpl', {
                'list' : getChangeLog(rep.data), 
                'name': name
            }));
            Dialog.content(html, {
                title: '变更记录',
                width : '90%',
                create : function(event, ui){
                    $(event.target).find('.content').click(function(){
                        var that = $(this);
                        request('/task-change-log/content?id='+that.attr('data-id'), function(rep){
                            var html = createDialogWarpperHtml('detailDialog', rep.data, 0.7, 0.8);
                            if(!rep.data){
                                rep.data = '';
                            }
                            Dialog.content(html, {
                                title: '任务内容'
                            });
                        })
                    });
                }
            });
        });
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
    
    $(".submit").button();
</script>
<?php 
include_once(Yii::getAlias('@view/common/footer.php'));
?>