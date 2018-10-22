<!DOCTYPE html>
<?php
    require_once '_start-dev.php';

    $render = Data2Html\Handler::createRender();
    $result = $render->render([], ['html' => '$${require selectivity}']);
    $lang= 'ca';
?>
<html lang="ca">
<head>
	<meta charset="UTF-8">
	<title>sBox-selectivity</title>
    
    <script src="../../demo/lang.js.php?lang=<?=$lang?>"></script>
    <?php echo $result->getSource(['base' => '../..', 'lang' => $lang]);?>

</head>
<body>
    <br>
    <div class="container section sec-1">
        <div class="form-horizontal" >
            <div class="form-group">
                <label class="col-sm-2 control-label">$${title}</label>
                <div class="col-sm-2">
                    <input type="text" value="Abcd|"
                        class="form-control">
                </div>
            </div>
            <hr>
                
            <div class="form-group">
                <label class="col-sm-2 control-label">static</label> 
                <div class="col-sm-2">
                    <div class="form-control-static">text</div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Single:
                    <span id="to_example-1"></span>
                </label>
                <div class="col-sm-3">
                    <div id="example-1" class="selectivity-input"></div>
                </div>
                <script>
                $('#example-1').selectivity({
                    allowClear: true,
                    items: [{id:'1', text:'Amsterdam'}, {id:2, text:'Antwerp'}/*, ...*/],
                    placeholder: 'Selectec a city'
                });
                $('#example-1').change(function () {
                    $('#to_example-1').text(
                        JSON.stringify($(this).selectivity('val'))
                    );
                })
                </script>
            </div>
            <hr>
            <div class="form-group">
                <label class="col-sm-2 control-label">Multiple:
                    <span id="to_example-2"></span>
                </label>
                <div class="col-sm-2">
                    <div id="example-2" class="selectivity-input"></div>
                </div>
                <script>
                $('#example-2').selectivity({
                    items: [{id:1, text:'Amsterdam'}, {id:2, text:'Antwerp'}/*, ...*/],
                    multiple: true,
                    placeholder: 'Type to search a city'
                });
                
                $('#example-2').selectivity('val', [1, 2]);
                $('#example-2').change(function () {
                    $('#to_example-2').text(
                        JSON.stringify($(this).selectivity('val'))
                    );
                })
                </script>
            </div>
        </div>
        
		<div class="row"><hr>
        Emails:
            <span id="to_example-5"></span><br>
            <div id="example-5" class="selectivity-input"></div>
            <script>
            $('#example-5').selectivity({
                inputType: 'Email',
                placeholder: 'Type or paste email addresses'
            });
            $('#example-5').change(function () {
                $('#to_example-5').text(
                    JSON.stringify($(this).selectivity('val'))
                );
            })
            </script>
        </div>
		<div class="row"><hr>
        Ajax:
            <span id="to_example-6"></span><br>
            <div id="example-6" class="selectivity-input"></div>
            <script>
            $('#example-6').selectivity({
                ajax: {
                    url: 'https://api.github.com/search/repositories',
                    minimumInputLength: 3,
                    quietMillis: 250,
                    params: function(term, offset) {
                        // GitHub uses 1-based pages with 30 results, by default
                        return { q: term, page: 1 + Math.floor(offset / 30) };
                    },
                    fetch: function(url, init, queryOptions) {
                        return $.ajax(url).then(function(data) {
                            return {
                                results: $.map(data.items, function(item) {
                                    return {
                                        id: item.id,
                                        text: item.name,
                                        description: item.description
                                    };
                                }),
                                more: (data.total_count > queryOptions.offset + data.items.length)
                            };
                        });
                    }
                },
                placeholder: 'Search for a repository',
                multiple: true,
                templates: {
                    resultItem: function(item) {
                        return (
                            '<div class="selectivity-result-item" data-item-id="' + item.id + '">' +
                                '<b>' + escape(item.text) + '</b><br>' +
                                item.description +
                            '</div>'
                        );
                    }
                }
            });
            </script>
        </div>
		<div class="row"><hr>
        d2h_Ajax:
            <span id="to_example-7"></span><br>
            <div id="example-7" class="selectivity-input"></div>
            <script>
            
            var selW = new selectivityWrapper($('#example-7'), {
                url: '/Aixada/Data2Html/demo/_controller/_controller.php?model=aixada_ufs:list',
                filterName: 'name_lk',
                textName: 'uf_name'
            });
            // var aaa = selW.server().then(function(data) {
                // var b = data.rows;
            // });
            
            </script>
        </div>
	</div>

</body>
</html>