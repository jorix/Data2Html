<!DOCTYPE html> 
<html lang="ca">
<head>
	<meta charset="UTF-8">
	<title>sBox-selectivity</title>
   
    <script src="../../external/js/jquery-2.1.0/jquery.js" ></script>
    <link  href="../../external/js/bootstrap-3.3.6-dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../external/js/bootstrap-3.3.6-dist/js/bootstrap.min.js" ></script>

    <link  href="../../external/js/selectivity-3.1.0/selectivity-jquery.css" rel="stylesheet">
    <link  href="../../external/js/font-awesome_v4.2.0/css/font-awesome.min.css" rel="stylesheet">
    <script src="../../external/js/selectivity-3.1.0/selectivity-jquery.js" ></script>
    <script>
    // $.Selectivity.Locale.noResults =  'No results fouuuuund';
    // $.Selectivity.Locale.noResultsForTerm = function(term) {
        // return 'No resultttttts for <b>' + escape(term) + '</b>';
    // };
    var Selectivity_Locale = {
        loading: 'Loading...',
        loadMore: 'Load more...',
        noResults: 'No results found',
        ajaxError: function(term) {
            if (term) {
                return 'Failed to fetch results for <b>' + escape(term) + '</b>';
            } else {
                return 'Failed to fetch results';
            }
        },

        needMoreCharacters: function(numCharacters) {
            return 'Enter ' + numCharacters + ' more characters to search';
        },

        noResultsForTerm: function(term) {
            return 'No results forzzzz <b>' + escape(term) + '</b>';
        }
    };
    $.extend($.Selectivity.Locale, Selectivity_Locale);
    
    </script>
    <style>
    .selectivity-input {
        width: 100%;
    }
    .selectivity-single-select {
        min-height: 20px;
        border-radius: 3px;
        padding: 6px 5px;
        border: 1px solid #ccc;
        background: #f9f9f9;
    }
    .selectivity-caret,
    .selectivity-single-result-container {
        top: 6px;
    }
    input.selectivity-single-select-input {
        box-sizing: content-box; 
        height: 20px;
        padding: 0;
        border-width: 0;
    }
    
    .selectivity-multiple-input-container {
        min-height: 18px;
        border-radius: 3px;
        padding: 2px 5px;
        border: 1px solid #ccc;
        background: #f9f9f9;
    }
    .selectivity-multiple-selected-item {
        background: #ddd;
        color: #555;
        margin: 1px 2px;
        padding: 0 2px;
        line-height: 26px;
    }
    
    .selectivity-single-selected-item-remove,
    .selectivity-multiple-selected-item-remove {
        color: #888;
        padding: 4px 5px;
    }
    .selectivity-multiple-result-container {
        top: 6px;
    }
    input[type='text'].selectivity-multiple-input {
        box-sizing: content-box; 
        height: 22px;
        padding: 3px 0;
        border-width: 0;
    }
    </style>
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
	</div>

</body>
</html>