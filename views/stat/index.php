<?php
use yii\widgets\LinkPager;
use app\records\Project;
?>
<?php 
include_once(Yii::getAlias('@view/common/header.php'));
?>
<style type="text/css">
    #formTable table td{
        padding-bottom:10px;
    }
    #formTable table td .ui-button{
        padding:4px;
    }
</style>
<div class='block-container'>
    <div class='container-content'>
        <div class='container-form'>
            <form action="/project" method="get" class='float-left' id='formTable'>
                <table>
                    <tr>
                        <td>统计方式</td>
                        <td>
                            <select name='stat_type'>
                                <option value='letter'>按文字</option>
                                <option value='graphics'>按图形</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>项目状态</td>
                        <td>
                            <label>全部<input class='checkbox allValues' type='checkbox' value='0'></label>
                            <?php foreach($projectStatus as $key=>$name):?>
                                <?= "<label>{$name}<input name='project_status[]' type='checkbox' class='checkbox applyCheckbox' value='{$key}'></label>" ?>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <col width="80"/>
                        <col width="*"/>
                        <td>任务时区</td>
                        <td>
                            <select name='date_type'>
                                <option value='create_timestamp_range'>按创建时间</option>
                                <option value='timestamp_range'>按接收时间</option>
                                <option value='completed_timestamp_range'>按完成时间</option>
                            </select>&nbsp;-&gt&nbsp;
                            <input type="text" name='start_time' id='startTime' style="width:120px;" placeholder='起始时间' class='datepicker' value='<?= isset($this->context->parameters['start_time']) ? $this->context->parameters['start_time'] : ''?>'>&nbsp;-&nbsp;
                            <input type="text" name='end_time'  id='endTime' style="width:120px;" placeholder='截至时间' class='datepicker' value='<?= isset($this->context->parameters['end_time']) ? $this->context->parameters['end_time'] : ''?>'>
                        </td>
                    </tr>
                    
                    <tr>
                        <td>任务状态</td>
                        <td>
                            <label>全部<input class='checkbox allValues' type='checkbox' value='0'></label>
                            <?php foreach($taskStatus as $key=>$name):?>
                                <?= "<label>{$name}<input name='task_status[]' class='checkbox applyCheckbox' type='checkbox' value='{$key}'></label>" ?>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>任务优先级</td>
                        <td>
                            <label>全部<input class='checkbox allValues' type='checkbox' value='0'></label>
                            <?php foreach($priorities as $key=>$name):?>
                                <?= "<label>{$name}<input name='priority[]' class='checkbox applyCheckbox' type='checkbox' value='{$key}'></label>" ?>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>任务类型</td>
                        <td>
                            <label>全部<input class='checkbox allValues' type='checkbox' value='0'></label>
                            <?php foreach($taskTypes as $type):?>
                                <?php
                                    $checked = '';
                                    $taskTypeParams = [];
                                    if(isset($this->context->parameters['task_type'])){
                                        $taskTypeParams = $this->context->parameters['task_type'];
                                    }
                                    if(in_array($key, $taskTypeParams)){
                                        $checked = 'checked=true';
                                    }
                                ?>
                                <?= "<label>{$type->name}<input name='task_type[]' class='checkbox applyCheckbox' type='checkbox' value='{$type->id}' {$checked}></label>" ?>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>成员角色</td>
                        <td>
                            <label>全部<input class='checkbox allValues' type='checkbox' value='0'></label>
                            <?php foreach($roles as $role):?>
                                <?= "<label>{$role->name}<input name='role[]' class='checkbox applyCheckbox queryMemberCondition' type='checkbox' value='{$role->id}'></label>" ?>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr id='members' style='display:none;'>
                        <td>成员</td>
                        <td id='membersCheckbox'>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" align='left'>
                            <button type="submit" class='submit'>查询</button>
                            <button type="button" class='reset'>清空</button>
                        </td>
                    </tr>
                </table>
            </form>
            <div class='float-clear'></div>
        </div>

        <div class='panel-top'>
            <div class='container-content' id='statPanel'>
            统计信息展示区
            </div>
        </div>
    </div>
</div>
<script type='text/javascript' src='/js/echarts.min.js'></script>

<script type="text/html" id='memberTpl'>
    <label>全部<input class='checkbox allValues' type='checkbox' value='0'></label>
    {{each $data.list}}
        <label>{{$value['real_name']}}<input name='members[]' class='checkbox' type='checkbox' value='{{$value['id']}}'></label>
    {{/each}}
</script>

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
                                    <dt>- {{@ task.name}}</dt>
                                {{/each}}
                            </dl>
                        </div>
                    </dd>
                </dl>
            {{/each}}
        {{/if}}
    </div>
</script>
<script type='text/javascript'>
    function loadTaskLetterStat(responseData){
        var allTotal = {};
        var data = responseData.list;
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
                }else if(task.status == 1){
                    status = '待领取';
                }
                for(var t in responseData.taskTypes){
                    var type = responseData.taskTypes[t];
                    if(task.type == type.id){
                        task.type = type.name;
                    }
                }
                task.priority = responseData.priorities[task.priority];
                task.name = task.name+"（<span class='red'><span title='类型'>" + task.type + "</span>&nbsp;|&nbsp;<span title='优先级'>" + task.priority + "</span>&nbsp;|&nbsp;<span title='状态'>" + status+"</span></span>）";
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
            'time_range' : timeRange,
            'priorities' : responseData.priorities,
            'taskStatus' : responseData.taskStatus,
            'taskTypes' : responseData.taskTypes,
        }));
    }

    var day = 60*60*24;
    function calculateUsedTime(plan, real){
        var usedTime = Math.ceil((plan - real) / 1000);
        return Math.round(usedTime / day * 100) / 100;
    }

    function loadTimeStat(data){
        var completedTasks = [];
        var overtimeTasks = [];
        var normal = [];
        for(var i in data){
            for(var j in data[i]['tasks']){
                var task = data[i]['tasks'][j];
                //统计完成的任务
                if(task.status === 40){
                    var usedTime = calculateUsedTime(Date.parse(task.expected_finish_time), Date.parse(task.real_finish_time));
                    if(usedTime < 0){
                        completedTasks.push({
                            value: [task.real_finish_time, usedTime, task],
                            name: task.name
                        });
                    }else{
                        normal.push({
                            value: [task.real_finish_time, usedTime, task],
                            name: task.name
                        });
                    }
                }else{
                    var plan =  Date.parse(task.expected_finish_time);
                    if(isNaN(plan)){
                        continue;
                    }
                    var current = Date.parse(new Date());
                    if(current <= task.expected_finish_time){
                        continue;
                    }
                    var usedTime =  calculateUsedTime(plan, current);
                    if(usedTime < 0){
                        overtimeTasks.push({
                            value: [task.expected_finish_time, Math.abs(usedTime), task],
                            name: task.name
                        });
                    }
                }
            }
        }

        var statPanel = $('#statPanel');
        if(completedTasks.length > 0 || normal.length > 0){
            var option = {
                title: {
                    text: '任务完成情况统计'
                },
                grid: {
                    left: '3%',
                    right: '7%',
                    bottom: '3%',
                    containLabel: true
                },
                tooltip: {
                    // trigger: 'axis',
                    showDelay: 0,
                    formatter: function (params) {
                        var val = '';
                        if(params.value[1] < 0){
                            val = '超时：'+params.value[1];
                        }else{
                            val = '用时：'+params.value[1];
                        }
                        return params.name + ' <br/>' + val + '  完成时间：' + params.value[0];
                    },
                    axisPointer: {
                        show: true,
                        type: 'cross',
                        lineStyle: {
                            type: 'dashed',
                            width: 1
                        }
                    },
                },
                toolbox: {
                    feature: {
                        dataZoom: {},
                        brush: {
                            type: ['rect', 'polygon', 'clear']
                        }
                    }
                },
                brush: {
                },
                legend: {
                    data: ['正常/提前', '超时'],
                    left: 'center'
                },
                xAxis: [
                    {
                        type: 'time',
                        scale: true,
                        axisLabel: {
                            formatter: '{value}'
                        }
                    }
                ],
                yAxis: [
                    {
                        type: 'value',
                        scale: true,
                        axisLabel: {
                            formatter: '{value}/自然日'
                        }
                    }
                ],
                series: [
                    {
                        name: '正常/提前',
                        type: 'scatter',
                        data: normal
                    },
                    {
                        name: '超时',
                        type: 'scatter',
                        data: completedTasks
                    }
                ]
            };
            var chart = $('#timeChart');
            if(chart.length > 0){
                chart.remove();
            }
            chart = $("<div id='timeChart' class='container-content' style='width:700px;height:500px;'></div>");
            statPanel.append(chart);
            var myChart = echarts.init(chart[0]);
            myChart.setOption(option);
            myChart.on('click', function(params){
                var task = params.value[2];
                window.open('/project/tasks?project_id=' + task.project_id + '&task_id='+task.id,  '_blank');
            });
        }
        if(overtimeTasks.length > 0){
            var option = {
                title: {
                    text: '超时未完成任务统计'
                },
                tooltip: {
                    // trigger: 'axis',
                    showDelay: 0,
                    formatter: function (params) {
                        var val = '超时：'+params.value[1];
                        return params.name + ' <br/>' + val + '  完成时间：' + params.value[0];
                    },
                    axisPointer: {
                        show: true,
                        type: 'cross',
                        lineStyle: {
                            type: 'dashed',
                            width: 1
                        }
                    },
                },
                xAxis: {
                    type: 'time',
                    scale: true,
                    axisLabel: {
                        formatter: '预期{value}'
                    }
                },
                yAxis: {
                    scale: true,
                    axisLabel: {
                        formatter: '超时{value}天'
                    }
                },
                series: [{
                    type: 'effectScatter',
                    symbolSize: 20
                }, {
                    type: 'scatter',
                    data: overtimeTasks
                }]
            };
            var chart = $('#overtimeChart');
            if(chart.length > 0){
                chart.remove();
            }
            chart = $("<div id='overtimeChart' class='container-content' style='width:700px;height:500px;'></div>");
            statPanel.append(chart);
            var myChart = echarts.init(chart[0]);
            myChart.setOption(option);
            myChart.on('click', function(params){
                var task = params.value[2];
                window.open('/project/tasks?project_id=' + task.project_id + '&task_id='+task.id,  '_blank');
            });
        }
        
    }

    function loadTaskStat(data){
        var names = [];
        var innerData = [];
        var outerData = [];

        for(var i in data){
            var project = data[i];
            var inner = {
                value : [0, project.id],
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
                }else if(task.status == 1){
                    status = '待领取';
                }

                if(!outer[project.id][task.status]){
                    outer[project.id][task.status] = {
                        value: [0, project.id, task.status],
                        name: status,
                    };
                    names.push(status);
                }
                outer[project.id][task.status].value[0]++;
                inner.value[0]++;
            }
            if(inner.value[0] < 1){
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
                formatter: function(params){
                    return '共' + params.value[0] + '个任务，占比'+ params.percent +'%';
                }
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
                        position: 'inner',
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
                        formatter: function(params){
                            return '{a|'+params.seriesName+'}{abg|}\n{hr|}\n  {b|'+params.name+'：}'+params.value[0]+'个   {per|'+ params.percent +'%}  '
                        },
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
        myChart.on('click', function(params){
            var values = params.value;
            if(!values[2]){
                return;
            }
            var url = '/project/tasks?project_id=' + values[1] + '&status='+values[2];;

            window.open(url,  '_blank');
        });
    }

    function taskTypeToggleCallback(values){
        if(values === null){
            return;
        }
        var membersObj = $('#members');
        var membersCheckboxObj = $('#membersCheckbox');
        if(!values.length){
            membersObj.hide();
            membersCheckboxObj.html('');
            $('statPanel').html('统计信息展示区');
            return;
        }
        values = '?'+values.join('&');
        request('/stat/members'+values, function(rep){
            if(rep.data.length < 1){
                membersObj.hide();
                return;
            }
            membersObj.show();
            membersCheckboxObj.html(template('memberTpl', {
                'list' : rep.data
            }));
            membersCheckboxObj.find('.checkbox').checkboxradio();
        });
    }

    function setTaskType(){
        var values = [];
        return function(checkbox){
            var name = checkbox.attr('name').replace(/\[\]$/, '');
            if(!checkbox.hasClass('queryMemberCondition')){
                return null;
            }
            $('input[name="'+ name +'[]"]').each(function(){
                var current = $(this);
                var value = name + '[]=' + current.val();
                if(current.prop('checked')){
                    for(var i in values){
                        if(values[i] === value){
                            return null;
                        }   
                    }
                    values.push(value);
                }else{
                    for(var i = values.length; i>=0; i--){
                        if(values[i] === value){
                            values.splice(i, 1);
                        }   
                    }
                }
           });
           return values;
        }
    }

    function changeCheckboxStatus(that, value, isReverse){
        isReverse = isReverse || false;
        if(isReverse){
            value = !value;
        }
        that.prop('checked', value);
        that.checkboxradio('refresh');
    }

    var fun = setTaskType();

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

    $('.checkbox').checkboxradio();

    $("select[name='member']" ).selectmenu({
        'width' : 180,
        'height' : 20
    });

    $("select[name='date_type']" ).selectmenu({
        'width' : 110,
        'height' : 20
    });

    $("select[name='stat_type']" ).selectmenu({
        'width' : 100,
        'height' : 20,
        change : function(){
            $('#formTable').submit();
        }
    });

    $('#formTable').submit(function(){
        var that = $(this);
        var params = that.serialize();
        request('stat/result?'+params, function(rep){
            if(rep.data.list.length == 0){
                Dialog.message('未统计到数据');
            }else{
                $('#statPanel').html('统计信息展示区');
                var selected = that.find('select[name=stat_type]').val();
                if(selected == 'graphics'){
                    loadTaskStat(rep.data.list);
                    loadTimeStat(rep.data.list);
                }else{
                    loadTaskLetterStat(rep.data);
                }
            }
        });
        return false;
    });

    $('.applyCheckbox').click(function(){
        taskTypeToggleCallback(fun($(this)));
    });

    $('body').on('click', '.allValues', function(){
        var that = $(this);
        var values = [];
        that.parent().nextAll('label').each(function(){
            var checkbox = $(this).find('input');
            var checked = that.prop('checked');
            changeCheckboxStatus(checkbox, checked);
            values = fun(checkbox);
        });
        taskTypeToggleCallback(values);
    });

    $('.reset').click(function(){
        $('#formTable').find('input[type=checkbox]').each(function(){
            var that = $(this);
            that.prop('checked', false).checkboxradio('refresh');
        });
        $('input[type=text]').val('');
        $("select[name='member']" ).val('').selectmenu('refresh');
        taskTypeToggleCallback([]);
    });
    $("button").button();
    
</script>
<?php 
include_once(Yii::getAlias('@view/common/footer.php'));
?>
