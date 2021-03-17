<?php 
include_once(Yii::getAlias('@view/common/header.php'));
?>
<div class='block-container'>
    <div class='container-content'>
        <div class='container-form' >
            <div class='float-right'>
                <button type='button' id='add'>新增参数</button></td>
                <button type='button' id='upgradeCache'>更新缓存</button></td>
            </div>
            <div class='float-clear'></div>
        </div>
        <div class='table-container'>
            <table border=0 cellpadding=0 cellspacing=1 class=table-data width='100%'>
                <thead>
                <tr>
                    <td width="100">参数名</td>
                    <td width="*">参数值</td>
                    <td width="400">参数用途</td>
                    <td width="120">操作</td>
                </tr>
                </thead>
                <tbody>
                    <?php foreach($setting as $obj):?>
                        <tr>
                            <td><?= $obj->name ?></td>
                            <td><?= unserialize($obj->value)->toString() ?></td>
                            <td><?= $obj->comment ?></td>
                            <td><button type='button' class='editConfig' data-id='<?= $obj->id ?>'>修改</button>&nbsp;<button type='button' class='deleteConfig' data-id='<?= $obj->id ?>'>删除</button></td>
                        </tr>
                    <?php endforeach;?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script type='text/html' id='editTpl'>
<tr>
    <td><input name='name' value='{{setting.name}}' class='configName'/></td>
    <td><textarea style='width:98%;height:51px;' name='value' class='configValue'>{{setting.value}}</textarea></td>
    <td><textarea style='width:98%;height:51px;' name='comment' class='configComment'>{{setting.comment}}</textarea></td>
    <td><button type='button' class='saveConfig' data-id='{{setting.id}}'>保存</button><button type='button' class='cancelConfig' data-id='{{setting.id}}'>取消</button></td>
</tr>
</script>

<script type='text/html' id='recoverTpl'>
<tr>
    <td>{{setting.name}}</td>
    <td>{{setting.value}}</td>
    <td>{{setting.comment}}</td>
    <td><button type='button' class='editConfig' data-id='{{setting.id}}'>修改</button>&nbsp;<button type='button' class='deleteConfig' data-id='{{setting.id}}'>删除</button></td>
</tr>
</script>
<script type='text/javascript'>
    $("button").button();
    $('#add').click(function(){
        $('table tbody').prepend(fill('editTpl'));
    });

    $('#upgradeCache').click(function(){
        Dialog.confirm({
            minWidth: 300,
            minHeight: 100,
            text : '<h3>确认更新缓存？</h3>',
            yes : function(){
                request('/site/upgrade-setting-cache', function(rep){
                    Dialog.message(rep.message);
                });
            }
        })
        return false;
    });

    $('body').on('click', '.editConfig', function(){
        var that = $(this);
        var _p = that.parents('tr');
        request('/site/get-setting?id='+that.attr('data-id'), function(rep){
            _p.replaceWith(fill('editTpl', { setting : rep.data}));
        });
        return false;
    });

    $('body').on('click', '.deleteConfig', function(){
        var that = $(this);
        Dialog.confirm({
            minWidth: 300,
            minHeight: 100,
            text : '<h3>确认删除设置？</h3>',
            yes : function(){
                request('/site/delete-setting', function(rep){
                    Dialog.message(rep.message);
                    that.parents('tr').remove();
                }, {
                    'id' : that.attr('data-id')
                });
            }
        })
        return false;
    });

    $('body').on('click', '.cancelConfig', function(){
        var that = $(this);
        var _p = that.parents('tr');
        var id = that.attr('data-id');
        if(!id){
            _p.remove();
        }else{
            request('/site/get-setting?id='+id, function(rep){
                var tr = fill('recoverTpl', {
                    setting : rep.data
                });
                _p.replaceWith(tr);
            });
        }
    });

    $('body').on('click', '.saveConfig', function(){
        var that = $(this);
        var _p = that.parents('tr');
        var id = that.attr('data-id');
        var name = _p.find('.configName').val();
        var value = _p.find('.configValue').val();
        var comment = _p.find('.configComment').val();
        if(!name || value === ''){
            Dialog.message('参数名与参数值不能为空');
            return false;
        }
        request('/site/save-setting', function(rep){
            Dialog.message(rep.message, function(){
                $(this).dialog('close');
                var tr = fill('recoverTpl', {
                    setting : {
                        'name' : name,
                        'value' : value,
                        'comment' : comment,
                        'id' : rep.data
                    }
                });
                _p.replaceWith(tr);
            });
        }, {
            'id' : id,
            'name' : name,
            'value' : value,
            'comment' : comment
        });
    })

    function fill(tpl, setting){
        setting = setting || {
            setting : {
                name : '',
                value : '',
                comment : '',
                id : ''
            }
        }
        var html = $(template(tpl, setting));
        html.find('button').button();
        return html;
    }
</script>
<?php 
include_once(Yii::getAlias('@view/common/footer.php'));
?>