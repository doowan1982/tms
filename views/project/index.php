<?php
use yii\widgets\LinkPager;
use app\records\Project;
?>
<?php 
include_once(Yii::getAlias('@view/common/header.php'));
?>
<div class='block-container'>
    <div class='container-content'>
        <div class='container-form' >
            <form action="/project" method="get" class='float-left'>
                <input type="text" name='name' placeholder='项目名称' class='input-100' value='<?= isset($this->context->parameters['name']) ? $this->context->parameters['name'] : ''?>'>
                <input type="text" name='start_time' id='startTime' placeholder='起始时间' class='datepicker' value='<?= isset($this->context->parameters['start_time']) ? $this->context->parameters['start_time'] : ''?>'>
                <input type="text" name='end_time'  id='endTime' placeholder='截至时间' class='datepicker' value='<?= isset($this->context->parameters['end_time']) ? $this->context->parameters['end_time'] : ''?>'>
                <select name='status'>
                    <option value=''>--全部状态--</option>
                    <?php foreach($status as $key=>$name):?>
                        <?php
                            $selected = '';
                            $this->context->parameters = $this->context->parameters;
                            if(isset($this->context->parameters['status']) && $this->context->parameters['status'] == $key){
                                $selected = 'selected=true';
                            }
                        ?>
                        <?= "<option value='{$key}'{$selected}>{$name}</option>" ?>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class='submit'>查询</button>
                <button type="button" class='reset'>清空</button>
            </form>

            <div class='float-right'>
                <button type="button" id='createProject'>新增项目</button>
            </div>
            <div class='float-clear'></div>

        </div>
        <?php
            $pagination = app\helpers\Helper::getPaginationHtml($data);
        ?>
        <div class='table-container'>
            <div class='paginattion-container'><?= $pagination ?></div>
            <table border=0 cellpadding=0 cellspacing=1 class=table-data width='100%'>
                <thead>
                <tr>
                    <td width="80">编号</td>
                    <td width="*">名称</td>
                    <td width="100">状态</td>
                    <td width="100">开发版本</td>
                    <td width="80">文档</td>
                    <td width="150">预期开始时间</td>
                    <td width="150">预期截止时间</td>
                    <td width="150">创建时间</td>
                    <td width='150'>操作</td>
                </tr>
                </thead>
                <tbody>
                <?php if($data->totalCount > 0):?>
                    <?php foreach($data->getModels() as $project):?>
                        <?php $project = $project->toArray(); ?>
                        <tr>
                            <td><?= $project['id'] ?></td>
                            <td><a href='/project/detail?id=<?=$project['id']?>' class='detail' title='查看详情'><?= $project['name'] ?></td>
                            <td><?= Project::getStatusMap()[$project['status']] ?></td>
                            <td><?= $project['version_number'] ?></td>
                            <td>
                            <?php if($project['project_doc_attachement']):?>
                                <a href='/project/download-attachement?id=<?=$project['id']?>' title='下载'>下载</a>
                            <?php else:?>无
                            <?php endif;?></td>
                            <td><?= $project['expected_start_time'] ?></td>
                            <td><?= $project['expected_end_time'] ?></td>
                            <td><?= $project['create_time'] ?></td>
                            <td>
                                <a href="/project/edit-task?project_id=<?=$project['id']?>">新增任务</a><br>
                                <a href='/project/stat?id=<?= $project['id']?>' data-id='<?=$project['id']?>' class='stat'>统计</a>
                                <a href='/project/tasks?project_id=<?= $project['id']?>'>任务</a>
                                <a href='/project/create?id=<?=$project['id']?>'>修改</a>
                                <a href='/project/delete' data-id='<?=$project['id']?>' class='delete'>删除</a>
                            </td>
                        </tr>
                    <?php endforeach;?>
                <?php else:?>
                    <tr><td colspan="9" align="center">暂无数据</td></tr>
                <?php endif;?>
                </tbody>
            </table>
            <div class='paginattion-container'><?= $pagination ?></div>
        </div>
    </div>
</div>
<script type='text/javascript' src='/js/echarts.min.js'></script>
<script type='text/html' id='statTpl'>
    <div>
        <div class='container-content'>
            <button type="button" id='tableStat'>表格</button>
            <button type="button" id='graphicsStat'>图形</button>
        </div>
        <div class='table-container'>
            <table border=0 cellpadding=0 cellspacing=1 class=table-data width='100%'>
                <thead>
                <tr>
                    <td width="*">真实姓名</td>
                    <td width="120">待实施</td>
                    <td width="120">实施中</td>
                    <td width='120'>已完成/终止</td>
                </tr>
                </thead>
                <tbody>
                {{if $data.list.length > 0 || undoTasksCount > 0}}
                    {{each $data.list}}
                        <tr>
                            <td>{{$value['receiver']['real_name']}}</td>
                            <td>{{$value['awaitTasksCount']}}</td>
                            <td>{{$value['activedTasksCount']}}</td>
                            <td>{{$value['terminateTasksCount']}}</td>
                        </tr>
                    {{/each}}
                    <tr>
                        <td>{{$data.total[0]}}</td>
                        <td>{{$data.total[1]}}</td>
                        <td>{{$data.total[2]}}</td>
                        <td>{{$data.total[3]}}</td>
                    </tr>
                    {{if $data.undoTasksCount > 0}}
                        <tr>
                            <td style='background-color: #F8F8FF'>待分发任务</td>
                            <td style='background-color: #F8F8FF' colspan="3">{{$data.undoTasksCount}}</td>
                        </tr>
                    {{/if}}
                {{else}}
                    <tr><td colspan="4" align="center">暂无数据</td></tr>
                {{/if}}
                </tbody>
            </table>
        </div>
    </div>
</script>
<script type="text/html" id='statSearchTpl'>
    <div id='chartPanel'>
        <input type='text' id='receiver' class='input-200' style="width:150px;" placeholder="实施人">
        <input type='hidden' name='reciver_id' id='reciverId' class='input-100' placeholder="实施人">&nbsp;
        <input type="text" name="stat_start_time" placeholder="起始日期" id="statStartTime" style="width:100px;" title="按任务创建时间"/>&nbsp;
        <input type="text" id="statEndTime" name="stat_end_time"  style="width:100px;"  placeholder="截止日期" title="按任务创建时间"/>&nbsp;
        <button class="statSearch">查询</button>
        [chart/]
    </div>
</script>
<script type='text/javascript'>
    function graphicsStat(myChart, list, undoCount){
        var status = ['待分发', '待实施', '实施中', '已完成/终止'];
        var members = [];
        var data = [];
        var total = {
            receiver: {
                real_name: '全部'
            },
            awaitReceiveTasksCount:0,
            awaitTasksCount:0,
            activedTasksCount:0,
            terminateTasksCount:0
        }
        for(var i in list){
            // total['awaitReceiveTasksCount'] += list[i]['awaitReceiveTasksCount'];
            total['awaitTasksCount'] += list[i]['awaitTasksCount'];
            total['activedTasksCount'] += list[i]['activedTasksCount'];
            total['terminateTasksCount'] += list[i]['terminateTasksCount'];
            list[i]['total'] = list[i]['awaitTasksCount'] +
                          list[i]['activedTasksCount'] +
                          list[i]['terminateTasksCount'];
        }
        //未接受任务无实施人所以仅在整体中体现
        total['awaitReceiveTasksCount'] = undoCount;
        total['total'] = total['awaitTasksCount'] +
                          total['activedTasksCount'] +
                          total['terminateTasksCount'] +
                          total['awaitReceiveTasksCount'];
        list.push(total);
        list.sort(function(a, b){
            return a['total'] - b['total'];
        });
        for(var i in status){
            data.push({
                name: status[i],
                type: 'bar',
                stack: '数量',
                label: {
                    show: true,
                    position: 'insideRight',
                    formatter: function(params){
                        if(params.value > 0){
                            return params.value;
                        }
                        return '';
                    }
                },
                data: []
            });
        }

        for(var i in list){
            members.push(list[i]['receiver']['real_name']);
            for(var j in data){
                j = parseInt(j);
                //仅整体时才进行统计
                if(j === 0){
                    data[j]['data'].push(list[i]['awaitReceiveTasksCount']);
                }else if(j == 1){
                    data[j]['data'].push(list[i]['awaitTasksCount']);
                }else if(j == 2){
                    data[j]['data'].push(list[i]['activedTasksCount']);
                }else if(j == 3){
                    data[j]['data'].push(list[i]['terminateTasksCount']);
                }
            }
        }
        var option = {
            tooltip: {
                trigger: 'axis',
                axisPointer: {            // 坐标轴指示器，坐标轴触发有效
                    type: 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
                },
                formatter : function(params){
                    var sum = 0;
                    for(var i in params){
                        var param = params[i];
                        if(typeof(param.value) === 'undefined'){
                            params[i].value = 0;
                        }
                        sum += param.value;
                    }
                    var html = "";
                    for(var i in params){
                        var val = parseInt(params[i].value);
                        if(val < 1){
                            continue;
                        }
                        html += params[i].marker + params[i].seriesName + ' ' + val + '个 [&nbsp;' + (Math.ceil(Math.round(val / sum * 10000)) / 100) + '%&nbsp;]<br>';                            
                    }
                    return html;
                }
            },
            legend: {
                data: status
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: {
                type: 'value'
            },
            yAxis: {
                type: 'category',
                data: members,
                nameTextStyle: {
                    padding: 2
                },
                axisTick : {
                    show : false,
                    interval : 2
                }
            },
            series: data
        };

        myChart.setOption(option);
    }

    function statTask(id, statType, params){
        statType = statType || 'graphics';
        params = params || {};
        params['id'] = id;
        var str = [];
        for(var i in params){
            //仅作为函数参数
            if(i == 'receiver'){
                continue;
            }
            str.push(i+"="+params[i]);
        }
        request('/project/stat?'+str.join('&'), function(rep){
            var list = rep.data.list;
            if(statType != 'graphics'){
                var total = ['项目整体进度(已分发)', 0, 0, 0];
                for(var i in list){
                    total[1] += list[i]['awaitReceiveTasksCount'];
                    total[2] += list[i]['awaitTasksCount'];
                    total[3] += list[i]['activedTasksCount'];
                    total[4] += list[i]['terminateTasksCount'];
                }
                var html = $(template('statTpl', {
                    'list' : list,
                    'total' : total,
                    'undoTasksCount' : rep.data.undo_count
                }));
                html.find('button').button();
                Dialog.content(html, {
                    title: '任务统计'
                });
            }else{
                html = template('statSearchTpl', {}).replace('[chart/]', createDialogWarpperHtml('chart', '', 0.8, 0.7));
                var size = Dialog.getSize(0.9, 0.95);
                Dialog.content($(html), {
                    title: '任务统计',
                    width: '90%',
                    height: size['height'],
                    create : function(event, ui){
                        var myChart = echarts.init(document.getElementById('chart'));
                        graphicsStat(myChart, list, rep.data.undo_count);
                        var target = $(event.target);

                        //实施人
                        var receiver = target.find('#receiver').val(params['receiver'] || '');
                        request('/member/list', function(rep){
                            var data = rep.data;
                            for(var i in data){
                                data[i] = data[i].real_name + ' ' + data[i].username + '[' + data[i]['id'] + ']';
                            }
                            receiver.autocomplete({
                                source : data, 
                                select : function(event, ui){
                                    //匹配id
                                    var id = ui.item.label.replace(/.*\[(\d+)\]$/, '$1');
                                    target.find('#reciverId').val(id);
                                }
                            });
                            receiver.focus();
                        });
                        var receiverId = target.find('#reciverId');
                        receiverId.val(params['receive_user_id'] || '');

                        //起始时间
                        var startTime = target.find('#statStartTime');
                        startTime.datepicker({
                            altField: "#statStartTime",
                            altFormat: "yy-mm-dd"
                        });
                        startTime.val(params['start_time'] || '');
                        //截止时间
                        var endTime = target.find('#statEndTime');
                        endTime.datepicker({
                            altField: "#statEndTime",
                            altFormat: "yy-mm-dd"
                        });
                        endTime.val(params['end_time'] || '');

                        target.find('.statSearch').click(function(){
                            target.dialog('close');
                            statTask(id, statType, {
                                'start_time' : startTime.val(),
                                'end_time' : endTime.val(),
                                'receive_user_id' : receiverId.val(),
                                'receiver' : receiver.val()
                            });
                        }).button().focus();

                    },
                    close : function(event, ui){
                        $('#chartPanel').remove();
                    }
                });
            }
        });
    }

    $("select[name='status']" ).selectmenu({
        'width' : 150,
        'height' : 20
    });
    $('.stat').click(function(){
        statTask($(this).attr('data-id'), 'graphics');
        return false;
    });

    $("input[name='name']").autocomplete();
    $('.detail').click(function(){
        var that = $(this);
        request(that.attr('href'), function(rep){
            var dialog = $('#dialog').html(rep.data);
            Dialog.content(dialog, {
                title: '['+that.html()+']详情'
            });
        });
        return false;
    })
    $('.reset').click(function(){
        $('form').find('input').each(function(){
            $(this).val('');
        });
        $("select[name='status']" ).val('').selectmenu('refresh');
    });
    $("button").button();
    $('.delete').click(function(){
        var that = $(this);
        Dialog.confirm({
            text: '确定删除？',
            yes: function() {
                var data = {id : that.attr('data-id')};
                request('/project/delete', function(rep){
                    Dialog.message(rep.message);
                    that.parents('tr').remove();
                }, data);
                return true;
            }
        });
        return false;
    });
    $('#createProject').click(function(){
        window.location.href = '/project/create';
    });
</script>
<?php 
include_once(Yii::getAlias('@view/common/footer.php'));
?>
