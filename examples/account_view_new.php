<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ca" lang="ca">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>title</title>
   
    <link rel="stylesheet" type="text/css"   media="screen" href="css/aixada_main.css" />	
    <link  href="../code/external/jquery/css/jqueryui.css" rel="stylesheet" type="text/css" media="screen" />
    <script src="../code/external/jquery/js/jquery.js"></script>
    <!-- script src="../code/external/jqueryui/js/jqueryui.js"></script -->
    <script src="../code/js/jquery.aixadaJSON2HTML.js" ></script>   
</head>
<body><div id="wrap">
    <?php
    $tpl = 
        '    <div id="d2h_aixada_account" class="ui-widget">
        <div class="ui-widget-content ui-corner-all">
            <h3 class="ui-widget-header ui-corner-all">
                <span style="color:#777">Diners:</span>
                <span id="d2h_aixada_account_h3">{account}</span>
                <span style="float:right; margin-top:-4px;">
                    <img style="display:none" class="loadSpinner" src="img/ajax-loader.gif"/>
                </span>
            </h3>
            <table class="tblListingDefault">
                <thead>
                    <tr>
                        <th>data</th>
                        <th>operat</th>
                        <th>description</th>
                        <th>method</th>
                        <th>quantity</th>
                        <th>balance</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{ts}</td>
                        <td>{operator}</td>
                        <td>{description}</td>
                        <td>{method}</td>
                        <td>{quantity}</td>
                        <td class="textAlignRight formatQty">{balance}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr><td colspan="2"></td></tr>
                </tfoot>
            </table>
        </div>
    </div>
    <script>
        var p_currency_sign = "€",
            p_msg_err_nomovements = "p_msg_err_nomovements";
        $("#d2h_aixada_account table tbody").data2html({
            url:        "account_controller.php",
            loadOnInit: true,
            resultsPerPage : 20,
            paginationNav : "#d2h_aixada_account table tfoot td",
            beforeLoad : function(){
                $("#d2h_aixada_account .loadSpinner").show();
            },
            rowComplete : function (rowIndex, row){
               //$.formatQuantity(row, p_currency_sign);
            },
            complete : function(rowCount){
                $("#d2h_aixada_account .loadSpinner").hide();
                if ($("#d2h_aixada_account table tbody tr").length == 0 &&
                        p_msg_err_nomovements){
                    $.showMsg({
                        msg: p_msg_err_nomovements,
                        type: "warning"
                    });
                } else {
                    $("#d2h_aixada_account table tbody tr:odd").addClass("rowHighlight"); 
                }
            }
        });
    </script>';
    require_once("config_db.php");
    require_once("account__.php");
    $a = new aixada_account($db_driver);
    echo $a->renderHtmlTable($tpl);
    ?>
</div></body>
</html>