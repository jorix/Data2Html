<!DOCTYPE html>
<?php
    require_once("../code/php/Autoload.php");
    Data2Html_Autoload::start(__DIR__, '_config/d2h_config.ini');

    $render = Data2Html_Handler::createRender();
    $htmlCode = '';
    $jsCode = '';
    
    $mMembers = Data2Html_Handler::getModel('aixada_members');
// Members Grid    
    $result = $render->renderGrid($mMembers, 'uf_members', 'edit-grid-nopaged');
    $idMemberGrid = $result['id'];
    $htmlCode .= $result['html'];
    $jsCode .= $result['js'];
// Edit Member
    $result = $render->renderElement($mMembers, 'main', 'edit-form');
    $idMemberEdit = $result['id'];
    $htmlCode .= $result['html'];
    $jsCode .= $result['js'];
    
    $mUfs = Data2Html_Handler::getModel('aixada_ufs');
// Ufs Grid    
    $result = $render->renderGrid($mUfs, 'main', 'edit-grid-paged');
    $idUfGrid = $result['id'];
    $htmlCode .= $result['html'];
    $jsCode .= $result['js'];
// Edit uf
    $result = $render->renderElement($mUfs, 'main', 'edit-form');
    $idUfEdit = $result['id'];
    $htmlCode .= $result['html'];
    $jsCode .= $result['js'];
    
?>
<html lang="ca">
<head>
	<meta charset="UTF-8">
	<title>manage_ufs</title>

    <script><?php echo Data2Html_Lang::jsCode('ca'); ?></script>
    <script src="../external/js/jquery-2.1.0/jquery.js" ></script>
    <link  href="../external/js/bootstrap-3.3.6-dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="../external/js/bootstrap-3.3.6-dist/js/bootstrap.min.js" ></script>
    
    <script src="../external/js-date_time/moment-2.18.1/min/moment-with-locales.js" ></script>
    <script src="../external/js-date_time/moment-timezone-0.5.13/builds/moment-timezone-with-data.min.js" ></script>
    <link  href="../external/js-date_time/eonasdan/bootstrap-datetimepicker-4.17.47/build/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
    <script src="../external/js-date_time/eonasdan/bootstrap-datetimepicker-4.17.47/build/js/bootstrap-datetimepicker.min.js"></script>
    
    <script src="../code/js/d2h_server.js"></script>
    <script src="../code/js/d2h_display.js"></script>
    <script src="../code/js/d2h_messages.js"></script>
    <script src="../code/js/d2h_sort.js"></script>
    <script src="../code/js/d2h_values.js"></script>
    
    <style>    
    .d2h_sort_no .d2h_sortIco_no {color: #777}
    .d2h_sort_asc .d2h_sortIco_asc,
    .d2h_sort_desc .d2h_sortIco_desc,
    .d2h_sort_no .d2h_sortIco_no {display: inline;}
    .d2h_sort_asc .d2h_sortIco_desc,
    .d2h_sort_asc .d2h_sortIco_no, 
    .d2h_sort_desc .d2h_sortIco_asc,
    .d2h_sort_desc .d2h_sortIco_no,
    .d2h_sort_no .d2h_sortIco_asc,
    .d2h_sort_no .d2h_sortIco_desc {display: none;}
    
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
    
    /**
     * d2h_message
     */
    .d2h_message { position: absolute; left: 15px; bottom: 30px; opacity: 0.9; }
    .d2h_message .alert { margin-bottom: 0; }
    th .d2h_message { font-weight: normal; }
    th { position: relative;}
    </style>
</head>
<body>
    <div class="container">
        <?=$htmlCode?>
    </div>
    <div class="d2h_waiting"></div>
    <script>
        <?=$jsCode?>

        d2h_display({
            branch: '#<?=$idUfGrid?>',
            items: {
                grid: {selector: '#<?=$idMemberGrid?>', leafKeys: ['uf_id_eq']},
                block: {selector: '#<?=$idMemberEdit?>', leafKeys: ['uf_id']}
            }
        });
        
        d2h_display({
            auto: 'loadGrid',
            items: {
                grid: '#<?=$idUfGrid?>',
                block: '#<?=$idUfEdit?>'
            }
        });
    </script>
</body>
</html>
