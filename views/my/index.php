<?php
include_once(Yii::getAlias('@view/common/header.php'));
?>

<style type="text/css">
    .projectList{
        cursor: pointer;
    }
</style>
<div class='block-container'>
    <div class='container-content'>
        <div id="tabs">
            <ul>
                <li><a href="#fragment-1"><span>最近参与</span></a></li>
                <li><a href="#fragment-2"><span>任务进度</span></a></li>
                <li><a href="#fragment-3"><span>任务统计</span></a></li>
            </ul>
            <div id="fragment-1">
                <div class='table-container'>
                    <table border=0 cellpadding=0 cellspacing=1 class=table-data width='100%'>
                        <thead>
                        <tr>
                            <td width="30%">任务名称</td>
                            <td width="*">动态</td>
                            <td width="150">时间</td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if(count($recentTouchTasks) > 0):?>
                            <?php foreach($recentTouchTasks as $touchTask):?>
                                <?php 
                                    if(!$touchTask->task){
                                        continue;
                                    } 
                                ?>
                                <tr>
                                    <td><a href='/project/tasks?project_id=<?= $touchTask->task->project_id ?>&task_id=<?=$touchTask->task_id?>' title='查看任务'><?= $touchTask->task->name ?></a></td>
                                    <td><?= preg_replace('/<a.*[^>]>/', '', $touchTask->message) ?></td>
                                    <td>
                                        <?= date('Y-m-d H:i:s', $touchTask->create_time) ?>
                                    </td>
                                </tr>
                            <?php endforeach;?>
                        <?php else:?>
                            <tr><td colspan="6" align="center">暂无任务</td></tr>
                        <?php endif;?>
                        </tbody>
                    </table>
                </div>  
              
          </div>
          <div id="fragment-2">
                <div class='table-container'>
                    <table border=0 cellpadding=0 cellspacing=1 class=table-data width='100%'>
                        <thead>
                        <tr>
                            <td width="70">编号</td>
                            <td width="40%">任务名称</td>
                            <td width="*">进度</td>
                            <td width="80">剩余时长[天]</td>
                            <td width="150">开始时间<br>预期时长</td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if(count($progresses) > 0):?>
                            <?php foreach($progresses as $progress):?>
                                <?php 
                                    $task = $progress->task;
                                    $id = "bar{$task->id}";
                                ?>
                                <tr>
                                    <td><?= $task->id ?></td>
                                    <td>【<?=$task->project->name?>】<?= $task->name ?></td>
                                    <td>
                                      <div id='<?= $id ?>'></div>
                                      <script type="text/javascript">
                                          $('#<?= $id ?>').progressbar({
                                            max : <?= $progress->getTotalTime() ?>,
                                            value: <?= $progress->getUsedTime() ?>
                                          });
                                      </script>
                                    </td>
                                    <td><?= $progress->getRemainTime() ?></td>
                                    <td>
                                        <?= date('Y-m-d H:i:s', $progress->startTime) ?>
                                        <br>
                                        <?= date('Y-m-d H:i:s', $progress->task->expected_finish_time) ?>
                                    </td>
                                </tr>
                            <?php endforeach;?>
                        <?php else:?>
                            <tr><td colspan="6" align="center">暂无任务</td></tr>
                        <?php endif;?>
                        </tbody>
                    </table>
                </div>
          </div>
          <div id="fragment-3">
            <div class='container-form' >
                <form action="/my/task-stat" id='statForm' method="get" class='float-left'>
                    <input type="text" name='start_time' id='startTime' placeholder='接收起始时间' class='input-100'>
                    <input type="text" name='end_time'  id='endTime' placeholder='接收截至时间' class='input-100'>
                    <select name='stat_type'>
                        <option value='letter'>按文字</option>
                        <option value='graphics'>按图形</option>
                    </select>
                    <button type="submit" class='submit'>查询</button>
                </form>
                <div class='float-clear'></div>
            </div>
            <div id='statPanel'>
            
            </div>
          </div>
        </div>
    </div>
</div>
<script type='text/html' id='statTpl'>
    <div class='table-container list'>
        {{if $data.project_count > 0}}
            <dl>
                <dt>
                    <span style='font-weight: bold;'>{{$data.time_range}}共参与<span class='red'>{{$data.project_count}}</span>个项目中的<span class='red'>{{$data.task_count}}</span>个任务（&nbsp;|&nbsp;{{each $data.all_total count statusName}}{{statusName}}<span class='red'>{{count}}</span>个&nbsp;|&nbsp;{{/each}}）</sapn>
                </dt>
            </dl>
            {{each $data.list}}
                <dl>
                    <dt class='panel-top'><a class='projectList' href='#' title='展示'><span style='font-weight: bold;'>{{$value.name}}</span></a>&nbsp;&nbsp;项目任务统计：{{each $value.project_total count statusName}}{{statusName}}<span class='red'>{{count}}</span>个&nbsp;&nbsp;{{/each}}</dt>
                    <dd>
                        <div class='taskList' style='display: none;'>
                            <dl>
                                {{each $value.tasks task taskIndex}}
                                    <dt>- {{task.name}}&nbsp;&nbsp;（<span class='red'>{{task.status}}</span>）</dt>
                                {{/each}}
                            </dl>
                        </div>
                    </dd>
                </dl>
            {{/each}}
        {{/if}}
    </div>
</script>
<script type='text/javascript' src='/js/echarts.min.js'></script>
<script type="text/javascript">
    function loadTaskLetterStat(data){
        var allTotal = {};
        for(var i in data){
            var total = {};
            var tasks = data[i]['tasks']
            for(var j in tasks){
                var task = tasks[j];
                if(task.status == 40){
                    status = '完成';
                }else if(task.status == 20){
                    status = '实施中';
                }else if(task.status == 10){
                    status = '待实施';
                }else if(task.status == 50){
                    status = '终止';
                }
                if(typeof(total[status]) == 'undefined'){
                    total[status] = 0;
                }
                if(typeof(allTotal[status]) == 'undefined'){
                    allTotal[status] = 0;
                }
                total[status]++;
                allTotal[status]++;
                data[i]['tasks'][j]['status'] = status;
            }
            data[i]['project_total'] = total;
        }
        var taskTotal = 0;
        for(var i in allTotal){
            taskTotal = taskTotal + allTotal[i];
        }
        var startTime = $('#startTime').val();
        var endTime = $('#endTime').val();
        var timeRange = '';
        if(!startTime){
            timeRange = '截止到';
        }else{
            timeRange = '从'+startTime+'到'
        }
        if(!endTime){
            timeRange += '当前，';
        }else{
            timeRange += endTime + '，';
        }
        $('#statPanel').html(template('statTpl', {
            'list' : data,
            'project_count' : data.length,
            'task_count' : taskTotal,
            'all_total' : allTotal,
            'time_range' : timeRange
        }));
    }

    function loadTaskStat(data){
        var names = [];
        var innerData = [];
        var outerData = [];

        for(var i in data){
            var project = data[i];
            var inner = {
                value : 0,
                name : project.name
            };
            if(i < 1){
                inner['selected'] = true;
            }
            var outer = {};
            for(var j in project.tasks){
                var task = project.tasks[j];
                if(!outer[project.id]){
                    outer[project.id] = {};
                }
                var status = '';
                if(task.status == 40){
                    status = '完成';
                }else if(task.status == 20){
                    status = '实施中';
                }else if(task.status == 10){
                    status = '待实施';
                }else if(task.status == 50){
                    status = '终止';
                }

                if(!outer[project.id][task.status]){
                    outer[project.id][task.status] = {
                        value: 0,
                        name: status,
                    };
                    names.push(status);
                }
                outer[project.id][task.status].value++;
                inner.value++;
            }
            if(inner.value < 1){
                continue;
            }
            innerData.push(inner);
            for(var j in outer[project.id]){
                var value = outer[project.id];
                if(value[j].value < 1){
                    continue;
                }
                outerData.push(value[j]);
            }
        }

        var option = {
            tooltip: {
                trigger: 'item',
                formatter: '{a} <br/>{b}: {c} ({d}%)'
            },
            legend: {
                orient: 'vertical',
                left: 10,
                data: names
            },
            series: [
                {
                    name: '项目',
                    type: 'pie',
                    selectedMode: 'single',
                    radius: [0, '30%'],

                    label: {
                        position: 'inner'
                    },
                    labelLine: {
                        show: false
                    },
                    data: innerData
                },
                {
                    name: '任务状态统计',
                    type: 'pie',
                    radius: ['40%', '55%'],
                    label: {
                        formatter: '{a|{a}}{abg|}\n{hr|}\n  {b|{b}：}{c}  {per|{d}%}  ',
                        backgroundColor: '#eee',
                        borderColor: '#aaa',
                        borderWidth: 1,
                        borderRadius: 4,
                        shadowBlur:3,
                        shadowOffsetX: 2,
                        shadowOffsetY: 2,
                        shadowColor: '#999',
                        padding: [0, 7],
                        rich: {
                            a: {
                                color: '#999',
                                lineHeight: 22,
                                align: 'center'
                            },
                            abg: {
                                backgroundColor: '#333',
                                width: '100%',
                                align: 'right',
                                height: 22,
                                borderRadius: [4, 4, 0, 0]
                            },
                            hr: {
                                borderColor: '#aaa',
                                width: '100%',
                                borderWidth: 0.5,
                                height: 0
                            },
                            b: {
                                fontSize: 16,
                                lineHeight: 33
                            },
                            per: {
                                color: '#eee',
                                backgroundColor: '#334455',
                                padding: [2, 4],
                                borderRadius: 2
                            }
                        }
                    },
                    data: outerData
                }
            ]
        };
        var chart = $('#chart');
        if(chart.length === 0){
            chart = $("<div id='chart' style='width:700px;height:500px;'></div>");
            $('#statPanel').html(chart);
        }
        var myChart = echarts.init(chart[0]);
        myChart.setOption(option);
    }

    $('#statForm').submit(function(){
        var that = $(this);
        var params = that.serialize();
        request('/my/task-stat?'+params, function(rep){
            if(rep.data.length == 0){
                Dialog.message('未统计到数据');
            }else{
                var selected = that.find('select[name=stat_type]').val();
                if(selected == 'graphics'){
                    loadTaskStat(rep.data);
                }else{
                    loadTaskLetterStat(rep.data);
                }
            }
        });
        return false;
    });
    
     $('body').on('click', '.projectList', function(){
        var that = $(this);
        that.parent().next().find('.taskList').toggle(300, function(){
            if($(this).css('display') == 'none'){
                that.attr('title', '展开');
                return;
            }
            that.attr('title', '隐藏');
        });
        return false;
    });

    $("select[name='stat_type']" ).selectmenu({
        'width' : 100,
        'height' : 20
    });

    $("button").button();
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
            var current = $(ui.newPanel);
            if(current.id == 'fragment-1'){

            }else if(current.id == 'fragment-2'){
                
            }
        },
        load: function(event, ui){
          alert(1)
        }
    });
</script>
<?php
include_once(Yii::getAlias('@view/common/footer.php'));
?>