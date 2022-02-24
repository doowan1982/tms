<?php 
use yii\widgets\LinkPager;

include_once(Yii::getAlias('@view/common/header.php'));
$parameters = $this->context->parameters;
?>
<div class='block-container'>
    <div class='container-content'>
        <div class='container-form' >
            <form action="/project/pending-tasks" id='searchForm' method="get" class='float-left'>
                <input type="text" name='name' placeholder='任务描述' class='input-100' value='<?= isset($parameters['name']) ? $parameters['name'] : ''?>'>
                <input type="text" name='start_time' placeholder='发布起始时间' class='datepicker' style='width:120px;' value='<?= isset($parameters['start_time']) ? $parameters['start_time'] : ''?>'>
                <input type="text" name='end_time' placeholder='发布截至时间' class='datepicker' style='width:120px;' value='<?= isset($parameters['end_time']) ? $parameters['end_time'] : ''?>'>
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
                <input type='hidden' name='project_id' value="<?=isset($this->context->parameters['project_id']) ? $this->context->parameters['project_id'] : ''?>"/>
                <button type="submit" class='submit'>查询</button>
            </form>

            <div class='float-right'>
                <button type="button" id='batchReceive'>领取</button>
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
                    <td width="*">项目名称<br>任务名称</td>
                    <td width="50">优先级</td>
                    <td width="50">难度</td>
                    <td width="90">任务类型</td>
                    <td width="150">发布人</td>
                    <td width="150">发布时间</td>
                    <td width="150">预期完成时间</td>
                    <td width='100'>操作</td>
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
                            <td><label><input type='checkbox' class='checkbox independentCheckbox' data-status='<?= $task->status?>' value='<?=$task->id?>'/></label></td>
                            <td><a href='/project/tasks?project_id=<?=$task->project_id?>&task_id=<?=$task->id?>' title='管理该任务' target='_blank'><?=$task->id?></a></td>
                            <td><a href='#' form-search-id='<?=$task->project_id?>' class='shortcutSearch' form-search-name='project_id' title='查看该项目任务'><?=$task->project->name?></a><br><a href='/project/task-detail?id=<?=$task['id']?>' class='detail' title='查看详情'><?= $task['name'] ?></td>
                            <td><?= $priorities[$task['priority']] ?></td>
                            <td><?= $task['difficulty'] ?></td>
                            <td><?= $task->category->name ?></td>
                            <td><a href='#' title='查看该成员发布的任务' class='shortcutSearch' form-search-name='publisher_id' form-search-id='<?=$publisher->id?>'><?= $publisher->real_name ?></a></td>
                            <td><?= $array['publish_time'] ?></td>
                            <td><?= $array['expected_finish_time'] ?></td>
                            <td>
                                <?php if($this->context->member->id === $task->publisher->id):?>
                                    <!-- 编辑修改自己创建的任务 -->
                                    <a href="/project/edit-task?id=<?=$task['id']?>&project_id=<?=$task['project_id']?>">修改</a>
                                    <a href='/project/delete-task' data-id='<?=$task['id']?>' class='delete'>删除</a>
                                <?php endif;?>
                                <a href="/project/receive-task" data-task-id='<?=$task->id?>' class='receiveTask'>领取</a>
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
<script type='text/javascript'>
    $("button").button();

    $("select" ).selectmenu({
        'width' : 110,
        'height' : 20
    });

    var checkboxGroup = new CheckboxGroup();


    $('#batchReceive').click(function(){
        var idArray = checkboxGroup.getValues(function(checkbox){
            return parseInt(checkbox.attr('data-status')) === 1; //仅获取待领取的任务
        });
        if(idArray.length === 0){
             Dialog.message('请选择需要领取的任务');
             return false;
        }
        for(var i in idArray){
            idArray[i] = idArray[i].val();
        }

        Dialog.confirm({
            minWidth: 300,
            minHeight: 100,
            text : '<h3>确认领取所选任务？</h3>',
            yes : function(){
                request('/project/batch-receive-tasks', function(rep){
                    Dialog.message(rep.message, function(){
                        window.location.reload();
                    });
                }, {
                    'task_id' : idArray
                });
            }
        })
        return false;
    });

    $('.shortcutSearch').click(function(){
        functions.shortcutSearch.call(this, $('#searchForm'));
    });

    $('.receiveTask').click(function(){
        var that = $(this);
        Dialog.confirm({
            minWidth: 300,
            minHeight: 100,
            text : '<h3>确认领取？</h3>',
            yes : function(){
                request(that.attr('href'), function(rep){
                    Dialog.message(rep.message);
                    that.parents('tr').remove();
                }, {
                    'id' : that.attr('data-task-id')
                })
            }
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