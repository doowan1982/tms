<?php 
use yii\widgets\LinkPager;

include_once(Yii::getAlias('@view/common/header.php'));
$parameters = $this->context->parameters;
?>
<div class='block-container'>
    <div class='container-content'>
        <div class='table-container'>
            <table border=0 cellpadding=0 cellspacing=1 class=table-data width='100%'>
                <thead>
                <tr>
                    <td width="*">节点简述</td>
                    <td width="80">涉及成员</td>
                    <td width="150">实施时间</td>
                </tr>
                </thead>
                <tbody>
                <?php if(count($lifecycle) > 0):?>
                    <?php foreach($lifecycle as $value):?>
                        <tr data-id='<?= $value['task_id'] ?>'>
                            <td><?= $value['message'] ?></td>
                            <td><?= $value['member']['real_name'] ?>
                            <td><?= date('Y-m-d H:i:s', $value['create_time']) ?></td>
                        </tr>
                    <?php endforeach;?>
                <?php else:?>
                    <tr><td colspan="3" align="center">任务暂未开始</td></tr>
                <?php endif;?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type='text/html' id='changeLogTpl'>
    <div id='searchProject'>
        <div class='table-container'>
            <table border=0 cellpadding=0 cellspacing=1 class=table-data width='100%'>
                <thead>
                <tr>
                    <td width="*">名称</td>
                    <td width="60">优先级</td>
                    <td width="100">类型</td>
                    <td width='50'>难度</td>
                    <td width='100'>状态</td>
                    <td width='150'>发布时间<br>期望完成时间</td>
                    <td width='80'>实施人</td>
                    <td width='150'>接收时间<br>实际完成时间</td>
                    <td width='70'>变更时间</td>
                </tr>
                </thead>
                <tbody>
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
                    </tr>
                {{/each}}
                </tbody>
            </table>
        </div>
    </div>
</script>
<script type="text/javascript">
    $('.taskChangeLog').click(function(){
        var taskId = $(this).parents('tr').attr('data-id');
        request('/task-change-log/index?task_id='+taskId, function(rep){
            var html = $(template('changeLogTpl', {
                'list' : getChangeLog(rep.data), 
                'name': name
            }));
            Dialog.content(html, {
                title: '变更记录',
                width : '90%',
            });
        });
        return false;
    });
</script>
<?php 
include_once(Yii::getAlias('@view/common/footer.php'));
?>