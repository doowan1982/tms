<?php
use yii\widgets\LinkPager;
use app\records\Project;
?>
<?php 
include_once(Yii::getAlias('@view/common/header.php'));
?>
<div class='block-container'>
    <div class='container-content'>
        <div class='container-form' >
            <form action="/project" method="get" class='float-left'>
                <input type="text" name='name' placeholder='项目名称' class='input-100' value='<?= isset($this->context->parameters['name']) ? $this->context->parameters['name'] : ''?>'>
                <input type="text" name='start_time' id='startTime' placeholder='起始时间' class='datepicker' value='<?= isset($this->context->parameters['start_time']) ? $this->context->parameters['start_time'] : ''?>'>
                <input type="text" name='end_time'  id='endTime' placeholder='截至时间' class='datepicker' value='<?= isset($this->context->parameters['end_time']) ? $this->context->parameters['end_time'] : ''?>'>
                <select name='status'>
                    <option value=''>--全部状态--</option>
                    <?php foreach($status as $key=>$name):?>
                        <?php
                            $selected = '';
                            $this->context->parameters = $this->context->parameters;
                            if(isset($this->context->parameters['status']) && $this->context->parameters['status'] == $key){
                                $selected = 'selected=true';
                            }
                        ?>
                        <?= "<option value='{$key}'{$selected}>{$name}</option>" ?>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class='submit'>查询</button>
                <button type="button" class='reset'>清空</button>
            </form>

            <div class='float-right'>
                <button type="button" id='createProject'>新增项目</button>
            </div>
            <div class='float-clear'></div>

        </div>
        <?php
            $pagination = app\helpers\Helper::getPaginationHtml($data);
        ?>
        <div class='table-container'>
            <div class='paginattion-container'><?= $pagination ?></div>
            <table border=0 cellpadding=0 cellspacing=1 class=table-data width='100%'>
                <thead>
                <tr>
                    <td width="80">编号</td>
                    <td width="*">名称</td>
                    <td width="100">状态</td>
                    <td width="100">开发版本</td>
                    <td width="80">文档</td>
                    <td width="150">预期开始时间</td>
                    <td width="150">预期截止时间</td>
                    <td width="150">创建时间</td>
                    <td width='150'>操作</td>
                </tr>
                </thead>
                <tbody>
                <?php if($data->totalCount > 0):?>
                    <?php foreach($data->getModels() as $project):?>
                        <?php $project = $project->toArray(); ?>
                        <tr>
                            <td><?= $project['id'] ?></td>
                            <td><a href='/project/detail?id=<?=$project['id']?>' class='detail' title='查看详情'><?= $project['name'] ?></td>
                            <td><?= Project::getStatusMap()[$project['status']] ?></td>
                            <td><?= $project['version_number'] ?></td>
                            <td>
                            <?php if($project['project_doc_attachement']):?>
                                <a href='/project/download-attachement?id=<?=$project['id']?>' title='下载'>下载</a>
                            <?php else:?>无
                            <?php endif;?></td>
                            <td><?= $project['expected_start_time'] ?></td>
                            <td><?= $project['expected_end_time'] ?></td>
                            <td><?= $project['create_time'] ?></td>
                            <td>
                                <a href="/project/edit-task?project_id=<?=$project['id']?>">新增任务</a><br>
                                <a href='/project/stat?id=<?= $project['id']?>' data-id='<?=$project['id']?>' class='stat'>统计</a>
                                <a href='/project/tasks?project_id=<?= $project['id']?>'>任务</a>
                                <a href='/project/create?id=<?=$project['id']?>'>修改</a>
                                <a href='/project/delete' data-id='<?=$project['id']?>' class='delete'>删除</a>
                            </td>
                        </tr>
                    <?php endforeach;?>
                <?php else:?>
                    <tr><td colspan="9" align="center">暂无数据</td></tr>
                <?php endif;?>
                </tbody>
            </table>
            <div class='paginattion-container'><?= $pagination ?></div>
        </div>
    </div>
</div>
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
<script type='text/javascript'>
    $("select[name='status']" ).selectmenu({
        'width' : 150,
        'height' : 20
    });
    $('.stat').click(function(){
        statTask('/project/stat', $(this).attr('data-id'), 'graphics');
        return false;
    });

    $("input[name='name']").autocomplete();
    $('.detail').click(function(){
        var that = $(this);
        request(that.attr('href'), function(rep){
            var dialog = $('#dialog').html(rep.data);
            Dialog.content(dialog, {
                title: '['+that.html()+']详情'
            });
        });
        return false;
    })
    $('.reset').click(function(){
        $('form').find('input').each(function(){
            $(this).val('');
        });
        $("select[name='status']" ).val('').selectmenu('refresh');
    });
    $("button").button();
    $('.delete').click(function(){
        var that = $(this);
        Dialog.confirm({
            text: '确定删除？',
            yes: function() {
                var data = {id : that.attr('data-id')};
                request('/project/delete', function(rep){
                    Dialog.message(rep.message);
                    that.parents('tr').remove();
                }, data);
                return true;
            }
        });
        return false;
    });
    $('#createProject').click(function(){
        window.location.href = '/project/create';
    });
</script>
<?php 
include_once(Yii::getAlias('@view/common/footer.php'));
?>
