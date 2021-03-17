<div class='block-container' style='padding:50px 20px;'>
    <form>
        <table cellpadding="20" cellspacing="10">
            <tr>
                <td colspan="2">
                <h2 style='font-size: 18px;'>项目管理系统</h2>
                </td>
            </tr>
            <tr>
                <td>用户名</td>
                <td><input type='text' name='username' id='username'/></td>
            </tr>
            <tr>
                <td>密码</td>
                <td><input type='password' name='password' id='password'/>&nbsp;&nbsp;<a href="/site/reset-password">忘记密码</a></td>
            </tr>
            <tr>
                <td colspan="2">
                    <button type='submit'>登录</button>&nbsp;
                    <button type='reset'>重置</button>
                </td>
            </tr>
        </table>
    </form>
</div>
<script type="text/javascript">
    $("button").button();
    $('#username').focus();
    $('form').submit(function(){
        var username = $('#username'); 
        var password = $('#password');
        if(!username.val()){
            username.css('border', '1px solid red');
            return false;
        }else{
            username.css('border', '');
        }
        if(!password.val()){
            password.css('border', '1px solid red');
            return false;
        }else{
            password.css('border', '');
        }
        request('/site/login', function(rep){
            window.location.href = '/';
        }, {
            'username' : username.val(),
            'password' : password.val()
        });
        return false;
    });
</script>

<?php 
include_once(Yii::getAlias('@view/common/footer.php'));
?>
