<?php 
include_once(Yii::getAlias('@view/common/header.php'));
$parameters = $this->context->parameters
?>
<div class='block-container'>
    <div class='container-content'>
        <div class='container-form' >
            <div class='float-right'>
                <button type="button" id='create'>创建</button>
            </div>
            <div class='float-clear'></div>
        </div>
        <div class='table-container'>
            <table border=0 cellpadding=0 cellspacing=1 class=table-data width='100%'>
                <thead>
                <tr>
                    <td width="60">编号</td>
                    <td width="*">角色名称</td>
                    <td width="50">显示顺序</td>
                    <td width='150'>操作</td>
                </tr>
                </thead>
                <tbody>
                <?php if(count($roles) > 0):?>
                    <?php foreach($roles as $role):?>
                        <tr>
                            <td><?= $role->id ?></td>
                            <td><?= $role->name ?></td>
                            <td><?= $role->show_order ?></td>
                            <td>
                                <a href='/member/edit-role?id=<?=$role->id?>' data-id='<?=$role->id?>' class='editRole'>修改</a>
                                <a href='/member/delete-role' data-id='<?=$role->id?>' class='deleteRole'>删除</a>
                            </td>
                        </tr>
                    <?php endforeach;?>
                <?php else:?>
                    <tr><td colspan="4" align="center">暂无数据</td></tr>
                <?php endif;?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type='text/html' id='roleEditTpl'>
    <div id='editRoleForm'>
            <table cellspacing=5 class=table-data width='100%'>
                <tbody>
                    <tr>
                        <td>角色名称</td>
                        <td><input type='text' name='name' value='{{role.name}}'/></td>
                    </tr>
                    <tr>
                        <td>显示顺序</td>
                        <td><input type='text' name='show_order' value='{{role.show_order}}'/></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</script>

<script type='text/javascript'>
    $("button").button();
    $('.deleteRole').click(function(){
        var that = $(this);
        Dialog.confirm({
            text: '确定删除该角色？',
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
        editRole();
        return false;
    });

    $('.editRole').click(function(){
        editRole($(this).attr('data-id'));
        return false;
    });

    function editRole(id){
        var url = '/member/edit-role';
        if(id){
            url += '?id='+id;
        }
        request(url, function(rep){
            var html = $(template('roleEditTpl', {
                'role' : rep.data
            }))
            Dialog.content(html, {
                title: '编辑角色',
                width: '300px',
                buttons: [{
                    text : '确定',
                    click : function(){
                        //转交数据
                        dialog = $(this);
                        var form = $('#editRoleForm');
                        request('/member/save-role', function(rep){
                            dialog.dialog("close");
                            Dialog.message(rep.message, function(){
                                window.location.reload();
                            });
                        }, {
                            'id' : id,
                            'name' :  form.find('input[name="name"]').val(),
                            'show_order' : form.find('input[name="show_order"]').val()
                        });
                    }
                },{
                    text : '取消',
                    click : function(){
                        $(this).dialog("close");
                    }
                }]
            });
        });
    }

    $(".submit").button();
</script>
<?php 
include_once(Yii::getAlias('@view/common/footer.php'));
?>