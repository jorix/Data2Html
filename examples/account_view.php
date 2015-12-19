<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=$language;?>" lang="<?=$language;?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>title</title>
   
    <link rel="stylesheet" type="text/css"   media="screen" href="css/aixada_main.css" />	
    <link  href="../code/external/jquery/css/jqueryui.css" rel="stylesheet" type="text/css" media="screen" />
    <script src="../code/external/jquery/js/jquery.js"></script>
    <!-- script src="../code/external/jqueryui/js/jqueryui.js"></script -->
    <script src="../code/js/jquery.aixadaJSON2HTML.js" ></script>   
	<script type="text/javascript">	
	$(function(){
		$.ajaxSetup({ cache: false });
		//loading animation
		$('.loadSpinner').attr('src', "img/ajax-loader.gif").hide(); 
			
	});
	</script>
</head>
<body>
<div id="wrap">
    <div id="d2h_aixada_account" class="ui-widget">
        <div class="ui-widget-content ui-corner-all">
            <h3 class="ui-widget-header ui-corner-all">
                <span style="color:#777">Diners:</span>
                <span id="d2h_aixada_account_h3">{account}</span>
                <span style="float:right; margin-top:-4px;">
                    <img style="display:none" class="loadSpinner" src="img/ajax-loader.gif"/>
                </span>
            </h3>
            <table id="td2h_aixada_account" class="tblListingDefault">
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
                <tbody xxxstyle="display:none">
                    <tr class="xml2html_tpl">
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
        $("#td2h_aixada_account tbody").data2html("init",{
            url:        "account_controller.php",
            loadOnInit: true,
            resultsPerPage : 20,
            paginationNav : "#td2h_aixada_account tfoot td",
            beforeLoad : function(){
                $("#d2h_aixada_account .loadSpinner").show();
            },
            rowComplete : function (rowIndex, row){
               //$.formatQuantity(row, p_currency_sign);
            },
            complete : function(rowCount){
                $("#d2h_aixada_account .loadSpinner").hide();
                if ($("#td2h_aixada_account tbody tr").length == 0 &&
                        p_msg_err_nomovements){
                    $.showMsg({
                        msg: p_msg_err_nomovements,
                        type: "warning"
                    });
                } else {
                    $("#td2h_aixada_account tbody tr:odd").addClass("rowHighlight"); 
                }
            }
        });
	</script>
</body>
</html>