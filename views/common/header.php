<?php
$count = "<font color='red' id='unreadCount' style='font-weight:bold;'>&nbsp;0&nbsp;</font>";
?>
<div class='block-container header-container panel-bottom'>
    <div class='container-content'>
        <div class='float-left font-18'><a href='/' style='text-decoration: none; font-size:16px; font-weight: bold; color:#8B2500;'><?= $this->context->toolService->getCacheSetting('systemTitle')->getValue() ?></a></div>
        <div class='float-right font-14' >
            <font>你好，<strong><a href='/my/info' title='个人信息'><?= $this->context->getMember()->real_name?></a></strong>&nbsp;&nbsp;&nbsp;[<span><a href="/my/messages" id='messageTip'><?=$count?>条未读消息</a></span><span id='realTimeShow' style='cursor: pointer;' class="ui-icon  ui-icon-radio-off" title='开启实时消息'></span>]</font>&nbsp;&nbsp;&nbsp;|<a href='#' id='globalFontSizeZoomIn' class="ui-icon   ui-icon-arrowthick-1-nw" title='放大'></a><span id='fontSize' title="当前字号"></span><a href='#' id='globalFontSizeZoomOut' class="ui-icon ui-icon-arrowthick-1-se" title='缩小'></a>|&nbsp;&nbsp;&nbsp;<a href="/site/logout">退出</a>
        </div>
        <div class='float-clear'></div>
    </div>
</div>
<script type='text/html' id='messageTpl'>
    <div id='messages'>
        <div class='table-container'>
            <table border=0 cellpadding=0 cellspacing=0 class=table-data width='100%'>
                <tbody>
                {{if $data.list.length > 0}}
                    {{each $data.list}}
                        <tr>
                            <td  class='panel-bottom' style="text-align:left; ">
                                <div class='float-left' style='width:68%;'>
                                {{if !$value['receiver_id']}}
                                【全局消息】
                                {{/if}}
                                <p>{{$value['content']}}&nbsp;&nbsp;
                                {{if $value['url']}}
                                    <a href='{{$value['url']}}'>查看详情</a>
                                {{/if}}</p>
                                </div>
                                <div class='float-right text-align-right' style='width:26%; height: 100%; border-left:1px solid #EECBAD;'>
                                {{if $value['sender_id'] > 0}}
                                {{$value['sender_real_name']}}
                                {{else}}
                                系统消息
                                {{/if}}&nbsp;
                                发送于：{{$value['send_time']}}之前
                                {{if $value['receiver_id'] > 0}}
                                &nbsp;&nbsp;
                                <a href="/my/read?message_id={{$value['id']}}" class="read">标记已读</a>
                                {{/if}}
                                </div>
                            </td>
                        </tr>
                    {{/each}}
                {{else}}
                    <tr><td>暂无消息</td></tr>
                {{/if}}
                </tbody>
            </table>
        </div>
    </div>
</script>
<script type="text/javascript">
    $('#messageTip').click(function(){
        var that = $(this);
        var url = that.attr('href');
        request(url, function(rep){
            var messages = $('#messages');
            if(messages.length > 0){
                messages.remove();
            }
            var messages = template('messageTpl', {
                'list' : rep.data, 
                'name': name 
            });
            Dialog.content(messages, {
                title: '未读消息列表',
                width : '70%'
            });
        });
        return false;
    });

    $('body').on('click', '.read', function(){
        var that = $(this);
        var unreadCount = $('#unreadCount');
        request(that.attr('href'), function(rep){
            var _p = that.parents('tbody');
            that.parents('tr').remove();
            if(_p.find('tr').length == 0){
                $('#messages').dialog('close');
            }
            var unreadCount = $('#unreadCount');
            var count = parseInt(unreadCount.html().replace(/[^\d]/g, ''));
            if(count > 0){
                updateMessageCount(--count);
            }
        })
        return false;
    })

    $('#realTimeShow').click(function(){
        var that = $(this);
        var realtimeMessage = $.cookie('realtime_message') > 0;
        if(realtimeMessage){
            clearInterval(interval);
            realtimeMessage = 0;
            setRealTimeStyle(that, 0);
        }else{
            realtimeMessage = 1;
            receiveRealTimeMessage();
        }
        $.removeCookie('realtime_message');
        $.cookie('realtime_message', realtimeMessage, {
            'expires' : 30,
            'path' : '/'
        });
        return false;
    });


    function getMessageCount(){
        request('/my/get-message-count', function(rep){
            updateMessageCount(rep.message);
        }, false)
    }

    function updateMessageCount(count){
        $('#unreadCount').html('&nbsp;' + count + '&nbsp;');
    }

    function setRealTimeStyle(obj, on){
        if(on){
            obj.addClass('ui-icon-radio-on')
                .removeClass('ui-icon-radio-off')
                .attr('title', '关闭实时消息');
        }else{
            obj.addClass('ui-icon-radio-off')
                .removeClass('ui-icon-radio-on')
                .attr('title', '开启实时消息');
        }
    }

    var interval = 0;

    function receiveRealTimeMessage(){
        setRealTimeStyle($('#realTimeShow'), 1);
        getMessageCount();
        if(interval){
            clearInterval(interval);
        }
        interval = setInterval(getMessageCount, 3000);
    }

    if($.cookie('realtime_message') > 0){
        receiveRealTimeMessage();
    }else{
        getMessageCount();
    }

</script>
<?php
include_once(Yii::getAlias('@view/common/nav.php'));
?>
<div id='contentBlock' style='overflow: auto;'>