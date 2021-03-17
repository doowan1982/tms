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

        $(window).resize(function(){
            setContentBlockHeight();
        });
    </script>
    </body>
</html>