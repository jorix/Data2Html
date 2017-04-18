<!DOCTYPE html>
<html lang="ca">
<head>
	<meta charset="UTF-8">
	<title>title</title>
   
    <script src="../../external/jquery-2.1.0/jquery.js" ></script>
    <link  href="../../external/bootstrap-3.3.6-dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../external/bootstrap-3.3.6-dist/js/bootstrap.min.js" ></script>
    <script src="../../code/js/jQuery-Data2Html.js" ></script>
    <style>
    .d2h_waiting {
        position: fixed; left: 50%; top: 50%;
        display: none;
        border: 12px dotted #aaa;
        border-bottom: 8px solid #aaa;
        border-radius: 50%;
        width: 100px;
        height: 100px;
        -webkit-animation: spin 2s linear infinite;
        animation: spin 2s linear infinite;
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
    <div class="container">à
        <ul>
            <li><a href="#" onclick="$('#tbl_providers').data2html('load', {url: '../_controller/_controller.php?model=aixada_providers'}); return false;">Providers</a></li>
            <li><a href="#" onclick="$('#tbl_providers').data2html('load'); return false;">Ufs</a></li>
            <li><a href="#" onclick="$('#tbl_xxx').data2html('load'); return false;">xxx Providers</a></li>
        </ul>
        <div class="row" 
            id="tbl_providers"
            data-d2h="
                url:'../_controller/_controller.php?model=aixada_ufs',
                repeat:  'table tbody tr.d2h_r',
                filter:'#d2h_1_filter',
                // page:  '#d2h_1_page',
                sort:  '#d2h_1_sort'">
        <form id="d2h_1_filter" class="simple-form">
        <div>
            <label class="col-md-3 form-group">ddddddww wewew
                <input class="form-control" name="name" type="text">
            </label>
            <label class="col-md-3 form-group">ddddddww wewew
                <input class="form-control" name="d2h_fff5" type="text">
            </label>
            <span class="col-md-1 form-group">
                <br>
                <button class="btn btn-default" title="$$Clear filter" onclick="clear()">
                    <span class="glyphicon glyphicon-remove"></span>
                </button>
            </span>
        </div>
        </form>
        
        <table class="table table-striped table-bordered">
            <thead><tr>
            <th>&nbsp;<input type="hidden" value="name" class="d2h_sort"></th>
            <th>id 
                <a title="ordre creixent" ><span class="glyphicon glyphicon-arrow-up"></span></a>
                <a title="ordre decreixent" ><span class="glyphicon glyphicon-arrow-down"></span></a>
                <a title="ordenar" ><span class="glyphicon glyphicon-sort"></span></a>
            </th>
            <th>nom</th>
            </tr></thead>
            <tbody>
                <tr><td>previ<div class="d2h_waiting"></div></td></tr>
                <tr class="d2h_r" providerId="{id}" responsibleUfId="{responsible_uf_id}" >
                    <td><input type="checkbox" name="providerBulkAction"/></td>
                    <td class="textAlignRight">{id}</td>
                    <td title="<?php echo 'click_to_list'; ?>">{name}</td>
                </tr>
                <tr><td>post</td></tr>              
            </tbody>
        </table>
        <table id="tbl_xxx" class="table table-striped table-bordered">
            <tbody><tr class="d2h_repeat" providerId="{id}" responsibleUfId="{responsible_uf_id}" >
                    <td><input type="checkbox" name="providerBulkAction"/></td>
                    <td class="textAlignRight">{id}</td>
                    <td title="<?php echo 'click_to_list'; ?>">{name}</td></tr>
                <tr><td>post<div class="d2h_waiting"></div></td></tr>              
            </tbody>
            </table>
        <span class="d2h_repeat333"></span>
        </div>
    </div>
    
    <script "type"="text/javascript">
    
    </script>
    
    <script "type"="text/javascript">
    //$('#tbl_providers').data2html('load');
    </script>
    <script "type"="text/javascript">
    $('#tbl_xxx').data2html({
        url: '../_controller/_controller.php?model=aixada_providers'
    });
    </script>
</body>
</html>