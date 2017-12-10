<!DOCTYPE html>
<html lang="ca">
<head>
	<meta charset="UTF-8">
	<title>title</title>
   
    <script src="../../external/jquery-2.1.0/jquery.js" ></script>
    <link  href="../../external/bootstrap-3.3.6-dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../external/bootstrap-3.3.6-dist/js/bootstrap.min.js" ></script>
    
    <script src="../../external/date_time/moment-2.18.1/min/moment-with-locales.js" ></script>
    <script src="../../external/date_time/moment-timezone-0.5.13/builds/moment-timezone-with-data.min.js" ></script>
    <link  href="../../external/date_time/eonasdan/bootstrap-datetimepicker-4.17.47/build/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
    <script src="../../external/date_time/eonasdan/bootstrap-datetimepicker-4.17.47/build/js/bootstrap-datetimepicker.min.js"></script>
    
    <script src="../../code/js/jQuery-Data2Html.js" ></script>
    <script src="../../code/js/d2h_switchTo.js" ></script>
    <script src="../../code/js/d2h_sortBy.js" ></script>
    <script src="../../code/js/d2h_values.js" ></script>
    
    <style>    
    .d2h_sortBy_no .d2h_sort_no {color: #777}
    .d2h_sortBy_asc .d2h_sort_asc,
    .d2h_sortBy_desc .d2h_sort_desc,
    .d2h_sortBy_no .d2h_sort_no {display: inline;}
    .d2h_sortBy_asc .d2h_sort_desc,
    .d2h_sortBy_asc .d2h_sort_no, 
    .d2h_sortBy_desc .d2h_sort_asc,
    .d2h_sortBy_desc .d2h_sort_no,
    .d2h_sortBy_no .d2h_sort_asc,
    .d2h_sortBy_no .d2h_sort_desc {display: none;}
    
    .clickable {cursor:pointer;}
    
    /* waiting */
    .d2h_waiting {
        position: fixed; left: 50%; top: 50%;
        display: none;
        border: 6px dotted #a88;
        border-bottom: 4px solid #aaa;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        -webkit-animation: spin 2s linear infinite;
        animation: spin 2s linear infinite;
    }
    .d2h_formChanged, 
    .d2h_formChanged input{
        background-color: red
    }
    @-webkit-keyframes spin {
        0% { -webkit-transform: rotate(0deg); }
        100% { -webkit-transform: rotate(360deg); }
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    </style>
</head>
<body>
    <div class="container">
    <?php
        require_once("../../code/php/Data2Html/Autoload.php");
        Data2Html_Autoload::start('../_config');
    
        $model = Data2Html_Handler::createModel($_REQUEST['model']);
        $render = Data2Html_Handler::createRender('../../code/templates/jquery/grid_paged.json.php');
    
    // Grid    
        $result = Data2Html_Handler::renderGrid($render, $model, 'main');
        $idGrid = $result['id'];
        echo "{$result['html']} \n<script>{$result['js']}</script>";
        
    echo '<hr>';
    // Form edit
        $result = Data2Html_Handler::renderForm($render, $model, 'main');
        $idEdit = $result['id'];
        echo "{$result['html']} \n<script>{$result['js']}</script>";
        
    ?>
    <script>
        (function() {
            d2h_switchTo.create('#<?=$idGrid?>', 'grid')
                .add('#<?=$idEdit?>', 'edit')
                .go('grid');
        })();
    </script>
    </div>
    <div class="d2h_waiting"></div>
</body>
</html>
