<div class='block-container nav-container panel-bottom'>
    <div class='container-content'>
        <div class='container-content font-14 float-left'>
        <?php
            if($this->params['position'] != null){
                echo '<span>所在位置：&nbsp;&nbsp;</span>'.$this->params['position']->toHtml(); 
                if($this->params['position']->nextPosition != null){
                    echo "&nbsp;<a href='javascript:window.location.reload();' class='current-position ui-icon ui-icon-refresh' title='刷新当前页'></a>&nbsp;|&nbsp;<a href='javascript:window.history.back(-1)' class='ui-icon ui-icon-arrowreturn-1-w' title='返回上一页'></a>";
                }
            } 
        ?>
        </div>
        <ul class='font-14 float-right' style='line-height: 30px;'>
            <li><a href="/my/tasks">我的任务</a></li>
            <li><a href="/project/pending-tasks">待分发任务</a></li>
            <!-- <li><a href="/my/task-detail">工作日志</a></li> -->
            <li><a href="/project">项目管理</a></li>
            <li><a href="/member">成员管理</a></li>
            <li><a href="/stat">统计</a></li>
            <li><a href="/site/setting">设置</a></li>
        </ul>
        <div class='float-clear'></div>
    </div>
</div>