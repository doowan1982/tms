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
        var current = $.extend({}, logs[i]);
        if(previous){
            for(var j in previous){
                if(j == 'log_time' || j == 'log_id'){
                    continue;
                }
                if(previous[j] !== current[j]){
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
            width: size['width'],
            height: size['height'],
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
            width: '300px',
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
