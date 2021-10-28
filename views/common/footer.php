        </div>
    <div id='dialog'></div>
    <div id='loading'></div>
    <script type="text/javascript">
        function setContentBlockHeight(){
            var content = $('#contentBlock');
            var windowHeight = $(window).height();
            var blockHeight = $('.nav-container').height()+$('.header-container').height();
            content.height(windowHeight-blockHeight);
        }

        setContentBlockHeight();         
        $('.datepicker').datetimepicker({
            language: 'zh-CN', 
            timeText: '时分',
            controlType: 'select',
            oneLine: true,
            dateFormat: 'yy-mm-dd',
            timeFormat : 'HH:mm',
        });

        $('cancel').click(function(){
            $(this).parents('form').find();
        });

        $('.table-container tbody tr').hover(function(){
            $(this).find('td').css('background-color', '#F5F5F5');
        }, function(){
            $(this).find('td').css('background-color', '');
        });

        

        evtListener.addEvt(new Evt('closeDialog', function(event){
            var _target = event.target;
            let nonEffectTag = ['input', 'button', 'a', '#ui-datepicker-div', '.ui-dialog']; //该标签的不触发事件
            while(typeof(_target) != 'undefined'){
                if(evtListener.isIncludeElement(nonEffectTag, _target)){
                    break;
                }
                if(_target.tagName.toLocaleLowerCase() == 'body'){
                    let dialog = Dialog.getDialogContainer('dialog');
                    if(dialog.html() != ''){
                        dialog.dialog('close');
                    }
                    break;
                }
                _target = _target.parentNode;
            }
        }))

        $(document).click(function(event){
            evtListener.trigger(event);
        });

        $(window).resize(function(){
            setContentBlockHeight();
        });
    </script>
    </body>
</html>