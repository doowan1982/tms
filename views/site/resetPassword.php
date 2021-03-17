<?php
    $token = '';
    if(isset($this->context->parameters['token'])){
        $token = $this->context->parameters['token'];
    }
?>
<div class='block-container' style='padding:50px 20px;'>
    <form action='' method='post'>
        <input type='hidden' name='hash' id='<?= $token ?>'/>
        <table cellpadding="20" cellspacing="10">
            <tr>
                <td colspan="2">
                <h2 style='font-size: 18px;'>项目管理系统-密码找回</h2>
                </td>
            </tr>
            <?php if($token):?>
                <tr>
                    <td>输入新密码</td>
                    <td><input type='password' name='password' id='password'/>&nbsp;<span class='red'>请在五分钟之内完成该操作</span></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <button type='button' id='setPassword'>提交</button>
                    </td>
                </tr>
            <?php else:?>
                <tr>
                    <td>用户名</td>
                    <td><input type='text' name='username' id='username'/></td>
                </tr>
                <tr>
                    <td>邮箱</td>
                    <td><input type='text' name='email' id='email' /></td>
                </tr>
                <tr>
                    <td>手机号码</td>
                    <td><input type='text' name='phone_number' id='phone_number' /></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <button type='button' id='resetPassword'>重置密码</button>&nbsp;
                        <button type='reset' onclick='window.location.href="/site/login"'>返回</button>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
    </form>
</div>
<script type="text/javascript">

    function verify(){
        var username = $('#username'); 
        var email = $('#email');
        var phone = $('#phone_number');
        if(!username.val()){
            username.css('border', '1px solid red');
            return false;
        }else{
            username.css('border', '');
        }
        if(!email.val()){
            email.css('border', '1px solid red');
            return false;
        }else{
            email.css('border', '');
        }

        if(!phone.val()){
            phone.css('border', '1px solid red');
            return false;
        }else{
            phone.css('border', '');
        }
        return {
            'username' : username.val(),
            'email' : email.val(),
            'phone_number' : phone.val()
        };
    }

    var step = 1; //验证步骤，验证输入信息

    $("button").button();

    $('#username').focus();

    $('#resetPassword').click(function(){
        var data = verify();
        if(!data){
            return false;
        }
        var form = $('form');
        createCsrfBeforeSubmit(form);
        form.submit();
    });

    $('#setPassword').click(function(){
        var password = $('#password');
        if(!password.val()){
            password.css('border', '1px solid red');
            return false;
        }else{
            password.css('border', '');
        }
        request(window.location.href, function(rep){
            Dialog.message(rep.message, function(){
                window.location.href='/';
            });
        }, {
            'password' : password.val()
        });
    });
</script>

<?php 
include_once(Yii::getAlias('@view/common/footer.php'));
?>
