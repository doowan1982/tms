<?php 
include_once(Yii::getAlias('@view/common/header.php'));
?>
<div class='block-container'>
    <div class='container-content'>
        <form action="/member/save" name='UploadForm' method="post"  enctype="multipart/form-data">
            <input type='hidden' name='id' value='<?=$member['id'] ?>'/>
            <table border=0 cellspacing="10" width="100%">
                <col width='130' style='text-align:rigth;' align="right"/>
                <col width='*' align="left"/>
                <tr>
                    <td>真实姓名<span class="red">*</span></td>
                    <td>
                        <input type="text" name='real_name' class='input-200' value='<?= $member['real_name']?>'>
                    </td>
                </tr>
                <tr>
                    <td>用户名<span class="red">*</span></td>
                    <td>
                        <input type="text" name='username' class='input-200' value='<?= isset($member['username']) ? $member['username'] : ''?>'>
                    </td>
                </tr>
                <tr>
                    <td>手机号码<span class="red">*</span></td>
                    <td>
                        <input type="text" name='phone_number' class='input-200' value='<?= $member['phone_number'] ?>' placeholder=''>
                    </td>
                </tr>
                <tr>
                    <td>邮箱<span class="red">*</span></td>
                    <td>
                        <input type="text" name='email' class='input-200' value='<?= $member['email'] ?>'/>
                    </td>
                </tr>
                <?php if($member->getIsNewRecord()):?>
                <tr>
                    <td>密码<span class="red">*</span></td>
                    <td>
                        <input type="text" name='password' class='input-200' value=''/>
                    </td>
                </tr>
                <?php endif;?>
                <tr>
                    <td>所在角色<span class="red">*</span></td>
                    <td>
                        <?php foreach($roles as $role):?>
                            <?php
                                $flag = false;
                                if($member->groups){
                                    foreach($member->groups as $group){
                                        if($group->role_id === $role->id){
                                            $flag = true;
                                            break;
                                        }
                                    }
                                }
                            ?>
                            <label><input type='checkbox' name='role_id[]' class='checkbox' value='<?=$role->id?>' <?= $flag ? 'checked=true' : ''?>/><?= $role->name?></label>
                        <?php endforeach;?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" align="left">
                        <button type="submit" class='submit'>保存</button>
                        <button type="reset">重置</button>
                    </td>
                </tr>
            </table>
        </form>
    </div>
    <div class='float-clear'></div>
</div>
<script type="text/javascript" src="/js/kindeditor/kindeditor-all-min.js"></script>
<script type='text/javascript'>
    $("input[name='name']").autocomplete();
    $("button").button();
    $('.checkbox').checkboxradio();
    var editor;
    KindEditor.ready(function(K) {
        editor = K.create('textarea[name="description"]', {
            allowFileManager : true,
            uploadJson : '/project/upload',
            filePostName: 'file',
            width : '80%',
            height: '500px',
            extraFileUploadParams: csrf(),
        });
    });

    $('form').submit(function(){
        createCsrfBeforeSubmit($(this));
        var flag = true;
        if(!$('input[name=username]').val()){
            $('input[name=username]').css('border', '1px solid red');
            flag = false;
        }
        if(!$('input[name=real_name]').val()){
            $('input[name=real_name]').css('border', '1px solid red');
            flag = false;
        }
        if(!$('input[name=phone_number]').val()){
            $('input[name=phone_number]').css('border', '1px solid red');
            flag = false;
        }
        if(!$('input[name=real_name]').val()){
            $('input[name=real_name]').css('border', '1px solid red');
            flag = false;
        }
        if(!$('input[name=email]').val()){
            $('input[name=email]').css('border', '1px solid red');
            flag = false;
        }
        var password = $('input[name=password]');
        if(password.length > 0 && !password.val()){
            password.css('border', '1px solid red');
            flag = false;
        }
        if(!flag){
            return false;
        }
        request('/member/save', function(rep){
            Dialog.message(rep.message);
        }, $(this).serializeArray());
        return false;
    });
</script>
<?php 
include_once(Yii::getAlias('@view/common/footer.php'));
?>