<?php
use app\models\Constants;
include_once(Yii::getAlias('@view/common/header.php'));
?>
<div class='block-container'>
    <div class='container-content'>
        <div id="tabs">
          <ul>
            <li><a href="#info"><span>基本信息</span></a></li>
            <li><a href="#setting"><span>设置</span></a></li>
            <li><a href="#logs"><span>操作日志</span></a></li>
          </ul>
          <div id="info">
              <form action="/my/saveInfo" id='editForm' method="post">
                <input type='hidden' id='id' value='<?=$member['id'] ?>'/>
                <table border=0 cellspacing="10" width="100%">
                    <col width='130' style='text-align:rigth;' align="right"/>
                    <col width='*' align="left"/>
                    <tr>
                        <td>真实姓名</td>
                        <td>
                            <input type="text" name='real_name' id='real_name' class='input-200' value='<?= $member['real_name']?>'>
                        </td>
                    </tr>
                    <tr>
                        <td>用户名</td>
                        <td>
                            <input type="text" name='username' id='username' class='input-200' value='<?= isset($member['username']) ? $member['username'] : ''?>'>
                        </td>
                    </tr>
                    <tr>
                        <td>手机号码</td>
                        <td>
                            <input type="text" name='phone_number' id='phone_number' class='input-200' value='<?= $member['phone_number'] ?>' placeholder=''>
                        </td>
                    </tr>
                    <tr>
                        <td>邮箱</td>
                        <td>
                            <input type="text" name='email' id='email' class='input-200' value='<?= $member['email'] ?>'/>
                        </td>
                    </tr>
                    <tr>
                        <td>密码</td>
                        <td>
                            <input type="password" name='password' id='password' class='input-200' value='' placeholder="密码不为空时，将覆盖原始密码" />
                        </td>
                    </tr>
                    <tr>
                        <td>所在角色</td>
                        <td>
                            <?php foreach($member->groups as $group):?>
                                <span style='background-color:#6495ED; padding:2px; color:#fff;'><?= $group->role->name?></span>
                            <?php endforeach;?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" align="left">
                            <button type="submit" class='submit'>修改</button>
                            <button type="reset">重置</button>
                        </td>
                    </tr>
                </table>
            </form>
          </div>
          <div id='setting'>
                <div class='container-content'>
                    <div><h3>消息发送</h3></div>
                    <div class='container-content'>
                        将涉及我的优先级为
                        <?php foreach($priorities as $id=>$priority):?>
                            <?php 
                                $checked = '';
                                if(isset($config[Constants::SEND_TO_EMAIL]) && in_array($id, $config[Constants::SEND_TO_EMAIL])){
                                    $checked = 'checked';
                                }
                            ?>
                            <label><input type='checkbox' name='<?=Constants::SEND_TO_EMAIL?>' class='priorities' value="<?=$id?>" <?=$checked?>/><?=$priority?></label>
                        <?php endforeach;?>
                        的任务提醒信息发送至邮箱（未选中的将使用系统消息）
                    </div>
                    <div><h3>项目数据</h3></div>
                    <?php
                        $checked = '';
                        if(isset($config[Constants::FOCUS_PROJECT]) && $config[Constants::FOCUS_PROJECT] > 0){
                            $checked = "checked=true";
                        }else{
                            $config[Constants::FOCUS_PROJECT] = 0;
                        }
                    ?>
                    <div class='container-content'>
                        <label>仅关注已参与项目<input type="checkbox" name='<?=Constants::FOCUS_PROJECT?>' class='focusProject' value='<?=$config[Constants::FOCUS_PROJECT]?>' <?=$checked?>/></label>（仅用于部分项目以及任务列表，未选择将显示所有）
                    </div>
                </div>
          </div>
          <div id="logs">
                <div id='searchProject'>
                    <div class='container-content'>
                        <form action='/my/log' id='logSearchForm'>
                            <input type='hidden' name='page' value="1"/>
                            <input type="text" name='start_time' id='startTime' placeholder='起始时间' class='input-100'>
                            <input type="text" name='end_time' id='endTime' placeholder='截至时间' class='input-100'>
                            <button type="submit">查询</button>
                        </form>
                    </div>
                    <div class='table-container'>
                        <table border=0 cellpadding=0 cellspacing=1 class=table-data width='100%' id='logTable'>
                            <thead>
                            <tr>
                                <td width="*">描述</td>
                                <td width="120">时间</td>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
          </div>
        </div>
    </div>
    <div class='float-clear'></div>
</div>

<script type='text/html' id='logTpl'>
{{if $data.list.length > 0}}
    {{each $data.list}}
        <tr>
            <td>{{$value['message']}}</td>
            <td>{{$value['create_time']}}</td>
        </tr>
    {{/each}}
    {{if $data.currentPage == $data.pageCount}}
        <tr><td colspan="2" align="center">已加载完</td></tr>
    {{else}}
        <tr><td colspan="2" align="center" id='more'><a href='#'>更多</a></td></tr>
    {{/if}}
{{else if($data.currentPage == 1)}}
    <tr><td colspan="2" align="center">无数据</td></tr>
{{/if}}
</script>

<script type="text/javascript" src="/js/kindeditor/kindeditor-all-min.js"></script>
<script type='text/javascript'>
    function loadLogs(appendTo, params){
        params = params || '';
        if(params){
            params = '?' + params;
        }
        request('/my/logs'+params, function(rep, status, xhr){
            var data = [];
            var headers = new Pagination(xhr);
            var html = (template('logTpl', {
                list : rep.data,
                currentPage : headers.currentPage,
                pageCount : headers.pageCount
            }));
            appendTo.append(html);
            $(html).hover();
        });
    }

    $('#password').keydown(function(){
        var that = $(this);
        var val = that.val();
        var oldPassword = $('#oldPassword');
        var tr = that.parents('tr');
        if(oldPassword.length == 0){
            var newTr = tr.clone();
            newTr.find('input[name=password]').attr({
                'name': 'oldPassword',
                'id' : 'oldPassword',
                'placeholder' : '更新密码时需验证旧密码'
            }).val('');
            newTr.find('td').eq(0).html('旧密码');
            tr.after(newTr);
        }else if(val.length === 1 || val === ''){
            oldPassword.parents('tr').remove();
        }
    });

    $('body').on('click', '#more', function(){
        var that = $(this);
        var form = $('#logSearchForm');
        var page = form.find('input[name=page]');
        var pageNumber = parseInt(page.val());
        page.val(pageNumber+1);
        loadLogs(that.parents('tbody'), form.serialize());
        that.parents('tr').remove();
    })

    $("button").button();

    $('.priorities').checkboxradio();
    $('.focusProject').checkboxradio();

    $('.focusProject').click(function(){
        var that = $(this);
        request('/member/save-config', function(rep){

        }, {
            'name' : '<?=Constants::FOCUS_PROJECT?>',
            'value' : that.prop('checked') ? 1 : 0
        })
    });

    $('.priorities').click(function(){
        var priorities = [];
        $('.priorities').each(function(){
            var that = $(this);
            if(that.prop('checked')){
                priorities.push(that.val());
            }
        });
        request('/member/save-config', function(rep){

        }, {
            'name' : '<?=Constants::SEND_TO_EMAIL?>',
            'value' : priorities.join(',')
        })
    });

    $("#startTime").datepicker({
        altField: "#startTime",
        altFormat: "yy-mm-dd"
    });
    $("#endTime").datepicker({
        altField: "#endTime",
        altFormat: "yy-mm-dd"
    });


    $("#tabs").tabs({
        activate: function(event, ui) {
            if(ui.newPanel.attr('id') == 'logs'){
                var table = ui.newPanel.find('table');
                var logPanel = table.find('tbody');
                if(logPanel.length == 0){
                    logPanel = $('<tbody></tbody>');
                    table.append(logPanel);
                    loadLogs(logPanel, $('#logSearchForm').serialize());
                }
            }
        }
    });

    $("select" ).selectmenu({
        'width' : 110,
        'height' : 20
    });

    $('#logSearchForm').submit(function(){
        var that = $(this);
        var tbody = $('#info tbody');
        tbody.find('tr').remove();
        loadLogs(tbody, that.serialize());
        return false;
    });

    $('#logTable').on('mouseover mouseout', 'tr', function(){
        if(event.type == "mouseover"){
            $(this).find('td').css('background-color', '#F5F5F5');
        }else if(event.type == "mouseout"){
            $(this).find('td').css('background-color', '');
        }
    });

    $('#editForm').submit(function(){
        var password = $('#password').val();
        var oldPassword = $('#oldPassword');
        if(password && !oldPassword.val()){
            oldPassword.focus();
            Dialog.message('更新密码时需验证旧密码');
            return false;
        }
        if(oldPassword.length > 0){
            oldPassword = oldPassword.val();
        }else{
            oldPassword = '';
        }
        request('/my/save-info', function(rep){
            Dialog.message(rep.message);
        }, {
            'id' : $('#id').val(),
            'real_name' : $('#real_name').val(),
            'username' : $('#username').val(),
            'phone_number' : $('#phone_number').val(),
            'email' : $('#email').val(),
            'password' : password,
            'old_password' : oldPassword,
        });
        return false;
    });
</script>
<?php 
include_once(Yii::getAlias('@view/common/footer.php'));
?>