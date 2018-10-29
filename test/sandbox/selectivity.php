<!DOCTYPE html>
<?php
    require_once '_start-dev.php';

    $render = Data2Html\Handler::createRender();
    $required = $render->render([], ['html' => '$${require selectivity-wrapper}']);
    $lang= 'ca';
    
    $template = ['html' =>
        '<div class="form-group row">
            <label for="$${example}" class="col-2  col-form-label">$${title}</label>
            <div class="col-7">
                <div id="$${example}" class="selectivity-input"></div>
            </div>
            <div class="col-3">
                <input type="text" id="val-$${example}" class="form-control" placeholder="(result)" value="">
            </div>
            <script>
            $("#$${example}").change(function () {
                $("#val-$${example}").val(JSON.stringify($(this).selectivity("val")));
            })
            </script>
        </div>
        <hr>'
    ];
?>
<html lang="<?=$lang?>">
<head>
	<meta charset="UTF-8">
	<title>sBox: selectivity</title>
    
    <script src="../../demo/lang.js.php?lang=<?=$lang?>"></script>
    <?php echo $required->getSource(['base' => '../..', 'lang' => $lang]);?>

</head>
<body>
    <br>
    <div class="row">
        <div class="col-4 form-horizontal">
            <?php 
                echo $render->render(['example' => 'ex-1', 'title' => 'Single'], $template)->get();
            ?>
            <script>
                $('#ex-1').selectivity({
                    items: [{id:'1', text:'Amsterdam'}, {id:2, text:'Antwerp'}/*, ...*/],
                    allowClear: true,
                    placeholder: 'Selectec a city'
                });
            </script>

            <?php 
                echo $render->render(['example' => 'ex-2', 'title' => 'Multiple'], $template)->get();
            ?>
            <script>
                $('#ex-2').selectivity({
                    items: [{id:1, text:'Amsterdam'}, {id:2, text:'Antwerp'}/*, ...*/],
                    allowClear: true,
                    multiple: true,
                    placeholder: 'Selectec a city'
                });
                $('#ex-2').selectivity('val', [1, 2]);
            </script>
            
            <?php 
                echo $render->render(['example' => 'ex-3', 'title' => 'emails'], $template)->get();
            ?>
            <script>
                $('#ex-3').selectivity({
                    inputType: 'Email',
                    placeholder: 'Emails list'
                });
            </script>
        
            <?php 
                echo $render->render(['example' => 'ex-4', 'title' => 'Ajax'], $template)->get();
            ?>
            <script>
                $('#ex-4').selectivity({
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
        <div class="col-4 form-horizontal">
            <?php
                echo $render
                    ->render(['example' => 'exd-1', 'title' => 'd2h-Ajax'], $template)
                    ->get('html');
            ?>
            <script>(function(){
                new selectivityWrapper($('#exd-1'), {
                    url: '../../demo/_controller/_controller.php?model=empl_employees:list',
                    filterName: 'last_name_lk'
                });
            })();</script>
            
            <?php
                echo $render
                    ->render(['example' => 'exd-2', 'title' => 'd2h-Ajax'], $template)
                    ->get('html');
            ?>
            <script>(function(){
                new selectivityWrapper($('#exd-2'), {
                    url: '../../demo/_controller/_controller.php?model=empl_departments:list'
                });
            })();</script>
            
        </div>
    </div>
</body>
</html>