function request(url, fn, data, callback, error, loading){
    var loading = loading || true;
    if(typeof(data) === 'boolean'){
        loading = data;
        data = null;
    }
    if(typeof(callback) === 'boolean'){
        loading = data;
        callback = null;
    }
    if(typeof(error) === 'boolean'){
        loading = data;
        error = null;
    }
    if(loading){
        loading = Dialog.loading();
    }

    var fun = function(rep, status, xhr){
        if(loading){
            loading.dialog('close');
        }
        if(rep.status != 200){
            Dialog.message(rep.message);
            return;
        }
        var callback = callback || function(rep, status, xhr){
            fn(rep, status, xhr);
        };
        callback(rep, status, xhr);
    };

    error = error || function(rep){
        var message = '未知错误';
        if(typeof(rep) === 'string'){
            message = rep;
        }else if(typeof(rep.responseJSON) !== 'undefined'){
            message = rep.responseJSON.message;
        }else if(typeof(rep.message) !== 'undefined'){
            message = rep.message;
        }else if(typeof(rep.responseText) !== 'undefined'){
            message = rep.responseText;
        }
        Dialog.message(message, false, {
            minWidth:'500px',
            minHeight: '400px'
        });
        if(loading){
            loading.dialog('close');
        }
    }

    data = data || null;
    if(data == null){
        $.get(url, fun).fail(error);
        return;
    }
    data = $.extend(data, csrf());
    $.post(url, data, fun).fail(error);
}

function getHeaders(xhr, name){
    var headers = xhr.getAllResponseHeaders();
    headers = headers.replace(/\s/, '').split(/\r\n|\r|\n/);
    var array = {};
    for(var i in headers){
        var pos = headers[i].indexOf(':');
        var key = headers[i].substr(0, pos).replace(/\s/, '').toLowerCase();
        var value = headers[i].substr(pos+1).replace(/\s/, '');
        array[key] = value;
    }
    if(typeof(name) === 'string'){
        name = [name];
    }
    headers = {};
    for(var i in array){
        for(var j in name){
            if(name[j].toLowerCase() === i){
                headers[i] = array[i];
                break;
            }
        }
    }
    return headers;
}

function Pagination(xhr){
    var headers = getHeaders(xhr, [
        'X-Pagination-Current-Page',
        'X-Pagination-Page-Count',
        'X-Pagination-Per-Page',
        'X-Pagination-Total-Count'
    ]);
    this.currentPage = headers['x-pagination-current-page'];
    this.pageCount = headers['x-pagination-page-count'];
    this.pageSize = headers['x-pagination-per-page'];
    this.totalCount = headers['x-pagination-total-count'];
}

function createCsrfBeforeSubmit(form){
    var data = csrf();
    var name = '';
    for(var i in data){
        name = i;
        break;
    }
    var param = $('input[name="'+name+'"]');
    if(!param.length){
        param = $("<input type='hidden' name='"+name+"' value='"+ data[name] +"'/>");
        form.append(param);
    }
}

var changeLogTpl = "<div id='searchProject'>" +
    "<div class='table-container'>" +
        "<table border=0 cellpadding=0 cellspacing=1 class=table-data width='100%'>" +
            "<thead>" +
            "<tr>" +
                "<td width='*'>所在项目<br>主任务<br>名称</td>" +
                "<td width='60'>优先级<br>难度</td>" +
                "<td width='100'>类型<br>状态</td>" +
                "<td width='150'>发布时间<br>期望完成时间</td>" +
                "<td width='80'>实施人</td>" +
                "<td width='150'>接收时间<br>实际完成时间</td>" +
                "<td width='70'>变更时间</td>" +
                "<td width='70'>内容</td>" +
            "</tr>" +
            "</thead>" +
            "<tbody>" +
            "{{if $data.list.length > 0}}" +
                "{{each $data.list}}" +
                "<tr>" +
                    "<td>" +
                        "<%- $value['project']%><br>" +
                        "{{if $value['task_id'] > 0}}<%- $value['task']%>{{else}}-{{/if}}<br>" +
                        "<%- $value['name']%>" +
                    "</td>" +
                    "<td><%-$value['priority'] %><br><%-$value['difficulty'] %></td>" +
                    "<td><%-$value['type'] %><br><%- $value['status']%></td>" +
                    "<td><%-$value['publish_time'] %><br><%-$value['expected_finish_time'] %></td>" +
                    "<td><%-$value['receiver'] %></td>" +
                    "<td><%-$value['receive_time'] %><br><%-$value['real_finish_time'] %></td>" +
                    "<td><%-$value['log_time'] %></td>" +
                    "<td>" +
                    "{{if $value['log_id']}}" +
                    "<a href='#' class='content' data-id='{{$value['log_id']}}'>查看</td>" +
                    "{{else}}-{{/if}}" +
                "</tr>" +
                "{{/each}}" +
            "{{else}}" +
                "<tr><td colspan='8' align='center'>暂无数据</td></tr>" +
            "{{/if}}" +
            "</tbody>" +
        "</table>" +
    "</div>" +
"</div>";

function getChangeLogHtml(data){
    return template.render(changeLogTpl, {
        'list' : getChangeLog(data)
    });
}

//比对出修改数据
function getChangeLog(logs){
    var previous = null;
    var change = "<span class='change-point'>[[value]]</span>";
    for(var i in logs){
        if(!logs[i]['receiver']){
            logs[i]['receiver'] = {
                'real_name' : '-'
            };
        }
        logs[i]['receiver'] = logs[i]['receiver']['real_name'];
        logs[i]['project'] = logs[i]['project']['name'];
        logs[i]['task'] = logs[i]['mainTask'] ? logs[i]['mainTask']['name'] : '-';
        var current = $.extend({}, logs[i]);
        if(previous){
            for(var j in previous){
                if(typeof(previous[j]) === 'object' || j == 'log_time' || j == 'log_id' || j == 'task' || j == 'project'){
                    continue;
                }
                if(previous[j] !== current[j]){
                    if(j == 'project_id'){
                        j = 'project'; 
                    }else if(j == 'task_id'){
                        j = 'task'; 
                    }
                    logs[i][j] = change.replace('[[value]]', logs[i][j]);
                }
            }
        }
        previous = $.extend({}, current);
    }
    return logs;
}

function csrf(){
    var key = $('meta[name="csrf-param"]').attr('content');
    var value = $('meta[name="csrf-token"]').attr('content');
    var obj = {};
    obj[key] = value;
    return obj;
}


function createDialogWarpperHtml(id, html, widthPercent, heightPercent){
    var size = Dialog.getSize(widthPercent, heightPercent);
    return '<div id="'+id+'" style="width:'+size['width']+'px; height:'+ size['height'] +'px;">'+html+'</div>';
}

function createDialogIframeWarpperHtml(id, src, widthPercent, heightPercent){
    var size = Dialog.getSize(widthPercent, heightPercent);
    return '<iframe frameborder=0 src="'+src+'" id="'+id+'" width="'+size['width']+'" height="'+ size['height'] +'"></iframe>'
}

function round(value, times){
    times = times || 2;
    times = Math.pow(10, times);
    return Math.round(value * times) / times;
}

var Dialog = {

    loading : function(message, options){
        message = message || '加载中....';
        return this.getDialogContainer('loading', '<div id="loadingDialog">'+message+'</div>').dialog({
            modal: true,
            closeOnEscape : false,
            title : '',
            buttons: [],
            minHeight: 'auto',
            open : function(event,ui){
                $(event.target).parents('.ui-dialog').find(".ui-dialog-titlebar").hide();
            }
        });
    },

    getSize : function(widthPercent, heightPercent){
        widthPercent = widthPercent || 0.8;
        heightPercent = heightPercent || 0.8;
        var w = $(window);
        return {
            width : w.width() * widthPercent,
            height: w.height() * heightPercent
        };
    },

    content : function(html, opts){
        var size = this.getSize();
        opts = $.extend({
            modal: true,
            closeOnEscape: true,
            width: opts['width'] || size['width'],
            height: opts['height'] || size['height'],
            buttons: [{
                text : '确定',
                click : function(){
                    $(this).dialog("close");
                }
            }]
        }, opts);
        if(typeof(html) === 'object'){
            html.dialog(opts);
        }else if(typeof(html) === 'string'){
            this.getDialogContainer('dialog', html).dialog(opts);
        }
    },

    message : function(message, clickEvent, opts){
        clickEvent = clickEvent || function(){
            $(this).dialog("close");
        }
        opts = $.extend({
            title : '消息',
            modal: true,
            width: 300,
            height: 200,
            minHeight: 100,
            closeOnEscape: true,
            buttons: [
                {
                    text : '确定',
                    click : clickEvent
                }
            ]
        }, opts || {});
        return this.getDialogContainer('dialog', message).dialog(opts);
    },

    customDialog : function(text, buttons, opts){
        opts = $.extend({
            buttons : buttons
        }, opts);
        return this.getDialogContainer('dialog', text).dialog(opts);
    },
    
    confirm : function(opts){
        var options = $.extend({
            title : '确认',
            modal: true,
            closeOnEscape: true,
            buttons: [
                { 
                    text : '确定',
                    click: function(){
                        if(options.yes()){
                            $(this).dialog('close');
                        }
                    }
                },
                {
                    text : '取消',
                    click : function(){
                        $(this).dialog("close");
                    }
                }
            ],
            width: 300,
            height: 200
        }, opts);
        return this.getDialogContainer('dialog', options.text).dialog(options);
    },
    
    getDialogContainer : function(id, text){
        var dialog = $('#'+id);
        dialog.html(text);
        return dialog;
    }

}

function imgsAutoWidthInsideDialog(dialog, lose){
    lose = lose || 20;
    var w = dialog.width();
    dialog.find('img').each(function(){
        var that = $(this);
        that[0].onload = function(){
            if(this.width > w){
                that.css('width', w - lose);
            }
        }
    });
}

function CheckboxGroup(selector, contextCheckbox, independentCheckbox){
    var checkedArray = []; //选中的值
    this.selector = selector || '.checkbox';
    this.independentCheckbox = independentCheckbox || '.independentCheckbox';
    this.contextCheckbox = contextCheckbox || '.contextCheckbox';
    this.init = function(){
        obj = this;
        $(this.selector).checkboxradio();
        $(this.contextCheckbox).click(function(){
            obj.click($(this));
        });
        $(this.independentCheckbox).click(function(){
            var that = $(this);
            var value = that.val();
            var checked = that.prop('checked');
            if(checked){
                checkedArray.push(that);
            }else{
                for(var i in checkedArray){
                    if(checkedArray[i].val() == value){
                        checkedArray.splice(i, 1);
                    }
                }
                that.prop('checked', false);
            }
            that.checkboxradio('refresh');
        });
    }

    this.click = function(contextCheckbox){
        var checked = contextCheckbox.prop('checked');
        checkedArray = [];
        $(this.independentCheckbox).each(function(){
            var that = $(this);
            that.prop('checked', checked).checkboxradio('refresh');
            if(checked){
                checkedArray.push(that);
            }else{
                that.prop('checked', false);
            }
        })
    }

    this.getValues = function(verify){
        verify = verify || function(obj){
            return true;
        }
        var array = [];
        for(var i in checkedArray){
            if(!verify(checkedArray[i])){
                continue;
            }
            array.push(checkedArray[i]);
        }
        return array;
    }

    this.init();
}

CheckboxGroup.prototype.verify = function(checkbox){
    return true;
}

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

var tasksInProjectStatTpl = "<div id='chartPanel'>"+
                    "<input type='text' id='receiver' class='input-200' style='width:150px;' placeholder='实施人'>"+
                    "<input type='hidden' name='reciver_id' id='reciverId' class='input-100' placeholder='实施人'>&nbsp;"+
                    "<input type='text' name='stat_start_time' placeholder='起始日期' id='statStartTime' style='width:100px;' title='按任务创建时间'/>&nbsp;"+
                    "<input type='text' id='statEndTime' name='stat_end_time'  style='width:100px;'  placeholder='截止日期' title='按任务创建时间'/>&nbsp;"+
                    "<button class='statSearch'>查询</button>"+
                    "<button class='reset'>清空</button>"+
                    "[chart/]"+
                "</div>";
function statTask(url, id, statType, params){
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
    request(url+'?'+str.join('&'), function(rep){
        var list = rep.data.list;
        if(statType != 'graphics'){
            var total = ['整体进度(已分发)', 0, 0, 0];
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
            html = template.render(tasksInProjectStatTpl, {}).replace('[chart/]', createDialogWarpperHtml('chart', '', 0.8, 0.7));
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

                    target.find('.reset').click(function(){
                        $(this).parent().find('input').each(function(){
                            $(this).val('');
                        });
                    }).button();

                    target.find('.statSearch').click(function(){
                        target.dialog('close');
                        statTask(url, id, statType, {
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

//统计列表模板
var tpl = "<div style='border-top: 1px solid #ccf;line-height:40px;' class='forkTaskTreeDiv'>"+
    "<span>{{blank}}&nbsp;</span>"+
    "<span>"+
        "<a href='/project/tasks?project_id={{projectId}}&task_id={{id}}&main_task_id={{mainTaskId}}' target='_blank'>{{name}}</a>"+
        "<span>【{{projectName}}】</span>"+
    "</span>"+
    "<span class='tags action-tags statusTag' data-status='{{status}}' title='两次点击可恢复列表'  style='{{style}}'>{{statusName}}<span class='selected-tag-icon'></span></span>"+
    "<span class='tags action-tags typeTag' title='两次点击可恢复列表' style='{{typeStyle}}' data-type='{{type}}'>{{typeName}}<span class='selected-tag-icon'></span></span>"+
"</div>";
//统计模板
var statTpl = "<div style='line-height:40px;' id='forkTaskStat'>"+
    "<font style='font-weight:bold'>共计{{taskCount}}个任务&nbsp;&nbsp;"+
        "<font class='tags' data-status='1' style='{{awaitReceiveTasksStyle}}'>待领取{{awaitReceiveTasksCount}}个{{awaitReceiveTasksPercent}}%</font>&nbsp;&nbsp;"+
        "<font class='tags' data-status='10' style='{{awaitTasksStyle}}'>待实施{{awaitTasksCount}}个&nbsp;{{awaitTasksPercent}}%</font>&nbsp;&nbsp;"+
        "<font class='tags' data-status='20' style='{{activedTasksStyle}}'>实施中{{activedTasksCount}}个&nbsp;{{activedTasksPercent}}%</font>&nbsp;&nbsp;"+
        "<font class='tags' data-status='40' style='{{completeTaskStyle}}'>已完成{{completeTaskCount}}个&nbsp;{{completeTaskPercent}}%</font>&nbsp;&nbsp;"+
        "<font class='tags' data-status='50' style='{{terminateTasksStyle}}'>终止{{terminateTasksCount}}个&nbsp;{{terminateTasksPercent}}%</font>"+
    "</font>"+
"</div>";
function loadForkTaskTree(id){
    var fn = function(node, mainTaskId, level, stat){
        mainTaskId = mainTaskId || 0;
        level = level || 0;
        var html = '';
        if(level > 0){
            var blank = ['|- '];
            if(node.nodes)
            for(let i=0; i<level; i++){
                blank.push(' - - -');
            }
            node['blank'] = blank.join('');
            node['mainTaskId'] = mainTaskId;
            node['typeStyle'] = getCategoriesColour(node['type']);
            var style = '';
            stat.taskCount++;
            switch(node['status']){
                case 10:{
                    style = getColour(node['status']);
                    stat.awaitTasksCount++;
                }
                break;
                case 20:{
                    style = getColour(node['status']);
                    stat.activedTasksCount++;
                }
                break;
                case 30:{
                    style = getColour(node['status']);
                }
                break;
                case 40:{
                    style = getColour(node['status']);
                    stat.completeTaskCount++;
                }
                break;
                case 50:{
                    style = getColour(node['status']);
                    stat.terminateTasksCount++;
                }
                break;
                default:{
                    stat.awaitReceiveTasksCount++;
                    style = getColour(node['status']);
                }
            }
            node['style'] = style;
            html = template.render(tpl, node);
        }
        level++;
        for(var i in node.nodes){
            html += fn(node.nodes[i], node.id, level, stat);
        }
        return html;
    }

    var stat = {
        'taskCount' : 0,
        'awaitReceiveTasksCount': 0, //待领取
        'awaitTasksCount': 0, //待实施
        'activedTasksCount': 0, //实施中
        'completeTaskCount': 0, //已完成
        'terminateTasksCount': 0 //终止
    };

    function forkTaskStat(stat){
        stat['awaitReceiveTasksPercent'] = round(stat.awaitReceiveTasksCount / stat.taskCount * 100, 0);
        stat['awaitTasksPercent'] = round(stat.awaitTasksCount / stat.taskCount * 100, 0);
        stat['activedTasksPercent'] = round(stat.activedTasksCount / stat.taskCount * 100, 0);
        stat['completeTaskPercent'] = round(stat.completeTaskCount / stat.taskCount * 100, 0);
        stat['terminateTasksPercent'] = round(stat.terminateTasksCount / stat.taskCount * 100, 0);
        stat['awaitReceiveTasksStyle'] = getColour(1);
        stat['awaitTasksStyle'] = getColour(10);
        stat['activedTasksStyle'] = getColour(20);
        stat['completeTaskStyle'] = getColour(40);
        stat['terminateTasksStyle'] = getColour(50);
        return template.render(statTpl, stat);
    }

    function reloadStatHtml(targets, all){
        var data = {
            taskCount : 0,
            awaitReceiveTasksCount : 0,
            awaitTasksCount : 0,
            activedTasksCount : 0,
            completeTaskCount : 0,
            terminateTasksCount : 0
        };
        targets.each(function(){
            var that = $(this);
            var tags = that.find('.selectedTag');
            if(tags.length === 0 && !all){
                return;
            }
            var status = that.find('.statusTag').attr('data-status');
            status = parseInt(status);
            data.taskCount++;
            switch(status){
                case 1 : {
                    data.awaitReceiveTasksCount++;
                } break;
                case 10 : {
                    data.awaitTasksCount++;
                } break;
                case 20 : {
                    data.activedTasksCount++;
                } break;
                case 40 : {
                    data.completeTaskCount++;
                } break;
                case 50 : {
                    data.terminateTasksCount++;
                };
            }
        });
        $(forkTaskStat(data)).replaceAll($('#forkTaskStat'));
    }

    request('/task/get-fork-tasks?id='+id, function(rep){
        var html = fn(rep.data, 0, 0, stat);
        html = forkTaskStat(stat) + html;
        Dialog.content(html, {
            title: '【'+rep.data.name+'】任务树',
            create: function(){
                var that = $(this);
                var search = {
                    name: 0,
                    value: 0,
                };

                //此处可绑定多个搜索项
                var searchItems = [
                    {
                        name: 'data-type',
                        value: 0
                    },
                    {
                        name: 'data-status',
                        value: 0
                    },
                ];
                that.on('click', '.action-tags', function(){
                    var tag = $(this);
                    var revert = tag.hasClass('selectedTag');
                    
                    for(var i in searchItems){
                        var name = searchItems[i].name;
                        if(!tag.attr(name)){
                            continue;
                        }
                        if(!revert){
                            searchItems[i].value = parseInt(tag.attr(name));
                        }else if(revert){
                            searchItems[i].value = 0;
                        }
                    }
                    tag.parents('.forkTaskTreeDiv').siblings('.forkTaskTreeDiv').each(function(index){
                        var div = $(this);
                        if(searchTags(searchItems, div.find('.action-tags'))){
                            div.show();
                        }else{
                            div.hide().find('.selectedTag').removeClass('selectedTag');
                        }
                    });
                    if(!revert){
                        tag.addClass('selectedTag');
                    }else{
                        tag.removeClass('selectedTag');
                    }

                    reloadStatHtml($('.forkTaskTreeDiv'), $('.selectedTag').length === 0);
                });
            }
        });
    });
}

/**
 * 通过给定的value来匹配同一组的多个tags
 * @return boolean
 */
function searchTags(search, tags){
    var matchedCount = 0;
    var expectedCount = 0;
    for(var i in search){
        if(search[i].value > 0){
            expectedCount++;
        }
    }
    if(expectedCount <= 0){
        tags.removeClass('selectedTag');
        return true;
    }
    matchedCount = 0;
    tags.each(function(){
        var tag = $(this);
        if(matchTag(search, tag)){
            matchedCount++;
            tag.addClass('selectedTag');
        }else{
            tag.removeClass("selectedTag");
        }
    });
    return matchedCount === expectedCount;
}

function matchTag(search, obj){
    for(var i in search){
        var val = search[i].value;
        if(val && val == parseInt(obj.attr(search[i].name))){
            return true;
        }
    }
    return false;
}

function getCategoriesColour(type){
    var config = {
        1: 'background-color: #ced671; cursor: pointer;',
        2: 'background-color: #a355af; color:#fff; cursor: pointer;',
        3: 'background-color: #55a5af; color:#fff; cursor: pointer;',
        4: 'background-color: #af555f; color:#fff; cursor: pointer;',
        5: 'background-color: #af7355; color:#fff; cursor: pointer;',
        6: 'background-color: #dad3d5; cursor: pointer;',
        7: 'background-color: #5011b0; color:#fff; cursor: pointer;',
        8: 'background-color: #0a1352; color:#fff; cursor: pointer;',
        9: 'background-color: #0d520a; color:#fff; cursor: pointer;',
    };
    return config[type];
}

function getColour(status){
    var config = {
        1:'background-color: #FFFF00; cursor: pointer;',
        10:'background-color: #FFF8DC; cursor: pointer;',
        20:'color:#fff; background-color: #4169E1; cursor: pointer;',
        30:'color:#fff; background-color: #87CEFA; cursor: pointer;',
        40:'color:#fff; background-color: #32CD32; cursor: pointer;',
        50:'background-color: #D3D3D3; cursor: pointer;'
    };
    return config[status] || config[1];
}

function createSelectPlugin(elements, options){
    elements.selectmenu({
        'width' : 110,
        'height' : 20
    }, options);
}

function createMultiSelectPlugin(elements, options){
    if(typeof(elements) !== 'object'){
        return;
    }
    options = $.extend({
        buttonWidth: 450,
        selectedList: 8,
        header:false,
        noneSelectedText: '--请选择参与人--',
        menuHeight: 100,
        menuWidth: 450,
        wrapText: ['button', 'options'],
        selectedText: '已选 # 个，共 # 个',
        classes: 'multiselect',
        groupColumns: true,
        resizableMenu: true,
    }, options);
    elements.multiselect(options);
}