<?php 
include_once(Yii::getAlias('@view/common/header.php'));
$parameters = $this->context->parameters
?>
<div class='block-container'>
    <div class='container-content'>
        <div class='container-form' >
            <form action="/member/index" method="get" class='float-left'>
                <input type="text" name='name' placeholder='用户名/手机/邮箱' class='input-100' value='<?= isset($parameters['name']) ? $parameters['name'] : ''?>'>
                <button type="submit" class='submit'>查询</button>
            </form>
            <div class='float-right'>
                <button type="button" id='create'>创建</button>
                <button type="button" id='roleManage'>角色管理</button>
            </div>

            <div class='float-clear'></div>
        </div>
        <div class='table-container'>
            <table border=0 cellpadding=0 cellspacing=1 class=table-data width='100%'>
                <thead>
                <tr>
                    <td width="60">编号</td>
                    <td width="*">真实姓名</td>
                    <td width="50">用户名</td>
                    <td width="50">手机号码</td>
                    <td width="90">邮箱</td>
                    <td width="80">角色</td>
                    <td width="140">创建时间</td>
                    <td width="140">更新时间</td>
                    <td width='150'>操作</td>
                </tr>
                </thead>
                <tbody>
                <?php if(count($members) > 0):?>
                    <?php foreach($members as $member):?>
                        <tr>
                            <td><?= $member->id ?></td>
                            <td><?= $member->real_name ?></td>
                            <td><?= $member->username ?></td>
                            <td><?= $member->phone_number ?></td>
                            <td><?= $member->email ?></td>
                            <td>
                                <?php foreach($member->groups as $group):?>
                                    <span><?= $group->role->name?></span><br>
                                <?php endforeach;?>
                            </td>
                            <td><?= date('Y-m-d H:s:i', $member->create_time) ?></td>
                            <td><?= date('Y-m-d H:s:i', $member->update_time) ?></td>
                            <td>
                                <a href='/member/edit?id=<?=$member->id?>'>修改</a>
                                <a href='/member/disable' data-id='<?=$member->id?>' class='disable' title='禁用该成员'>禁用</a>
                            </td>
                        </tr>
                    <?php endforeach;?>
                <?php else:?>
                    <tr><td colspan="9" align="center">暂无数据</td></tr>
                <?php endif;?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type='text/javascript'>

    $("button").button();

    $("select" ).selectmenu({
        'width' : 110,
        'height' : 20
    });

    $('#roleManage').click(function(){
        window.location.href = '/member/roles';
    });

    $('.disable').click(function(){
        var that = $(this);
        Dialog.confirm({
            text: '确定禁用该成员？',
            yes: function() {
                request(that.attr("href"), function(rep){
                    that.parents('tr').remove();
                }, {
                    'id' : that.attr('data-id')
                });
                return true;
            }
        });
        return false;
    });
    $('#create').click(function(){
        window.location.href = '/member/edit';
    });
    $(".submit").button();
</script>
<?php 
include_once(Yii::getAlias('@view/common/footer.php'));
?>