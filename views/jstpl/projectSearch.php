<script type='text/html' id='searchTpl'>
    <div id='searchProject'>
        <div class='container-content'>
            <form action='/project/search' method='get'>
            <input type="text" name='name' value="{{$data.name}}" placeholder='项目名称' class='input-100'>
            <button type="button" id='searchProjectButton'>查询</button>
            </form>
        </div>
        <div class='table-container'>
            <table border=0 cellpadding=0 cellspacing=1 class=table-data width='100%'>
                <thead>
                <tr>
                    <td width="80">编号</td>
                    <td width="*">项目名称</td>
                    <td width="100">项目版本</td>
                    <td width='150'>操作</td>
                </tr>
                </thead>
                <tbody>
                {{if $data.list.length > 0}}
                    {{each $data.list}}
                        <tr>
                            <td>{{$value['id']}}</td>
                            <td>{{$value['name']}}</td>
                            <td>{{$value['version_number']}}</td>
                            <td>{{@$value['actions']}}</td>
                        </tr>
                    {{/each}}
                {{else}}
                    <tr><td colspan="4" align="center">暂无数据</td></tr>
                {{/if}}
                </tbody>
            </table>
        </div>
    </div>
</script>

<script type="text/javascript">
    //搜索项目
    function searchProject(name, config){
        config = config || {
            'actions' : function(value){
                return '<a href="/project/edit-task?project_id='+value['id']+'">新增任务</a>';
            },
            'complete' : function(rep){

            }
        }
        name = name || '';
        var url = '/project/search';
        if(name !== ''){
            url += '?name=' + name;            
        }else{
            name = '';
        }
        request(url, function(rep){
            var searchProject = $('#searchProject');
            if(searchProject.length > 0){
                searchProject.remove();
            }
            for(var i in rep.data){
                rep.data[i]['actions'] = config.actions(rep.data[i]);
            }
            var searchProject = template('searchTpl', {
                'list' : rep.data, 
                'name': name 
            });
            // searchProject.find('button').button();
            Dialog.content(searchProject, {
                title: '选择项目',
                width : '60%',
                open : function(event, ui){
                    config.complete(rep.data)
                }
            });
        });
    }
</script>