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

<script type="text/javascript">
    $('.taskChangeLog').click(function(){
        var taskId = $(this).parents('tr').attr('data-id');
        request('/task-change-log/index?task_id='+taskId, function(rep){
            Dialog.content(getChangeLogHtml(rep.data), {
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