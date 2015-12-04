<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=$language;?>" lang="<?=$language;?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>title</title>
   
    <link  href="../code/external/jquery/css/jqueryui.css" rel="stylesheet" type="text/css" media="screen" />
    <script src="../code/external/jquery/js/jquery.js"></script>
    <script src="../code/external/jqueryui/js/jqueryui.js"></script>   	 
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

	<!-- end of headwrap -->
	
	
	<div id="stagewrap">
	
		
		<!---------------- -->
        <div id="account_listing" class="ui-widget">
		<div class="ui-widget-content ui-corner-all">
			<h3 class="ui-widget-header ui-corner-all"><span
				style="color:#777"><?='latest_movements';?>:</span> <span
				class="account_id"></span> <span
				style="float:right; margin-top:-4px;"><img
				class="loadSpinner hidden" src="img/ajax-loader.gif"/></span></h3>
			<table id="list_account" class="tblListingDefault">
			<thead>
				<tr>
					<th><?php echo 'date';?></th>
					<th><?php echo 'operator'; ?></th>
					<th><?php echo 'description'; ?></th>
					<th>Type</th>
					<th class="textAlignRight"><?php 
						echo 'mon_amount'; ?></th>
					<th class="textAlignRight"><?php 
						echo 'mon_balance'; ?></th>
				</tr>
			</thead>
			<tbody>
				<tr class="xml2html_tpl">
					<td>{ts}</td>
					<td>{operator}</td>
					<td>{description}</td>
					<td>{method}</td>
					<td class="textAlignRight formatQty">{quantity}</td>
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
		/**
		 * 	account extract
		 */
		function load_write_list_account(p_currency_sign, p_msg_err_nomovements) {
			$('#list_account tbody').data2html('init',{
                url:'data/data_account.json',
				XXurl		: 'php/ctrl/Account.php',
         		XXparams: 'oper=accountExtract&account_id=1014&filter=pastYear',
				loadOnInit: true,
				resultsPerPage : 20,
				paginationNav : '#list_account tfoot td',
				beforeLoad : function(){
					$('#account_listing .loadSpinner').show();
				},
				rowComplete : function (rowIndex, row){
					//$.formatQuantity(row, p_currency_sign);
				},
				complete : function(rowCount){
					$('#account_listing .loadSpinner').hide();
					if ($('#list_account tbody tr').length == 0 &&
							p_msg_err_nomovements){
						$.showMsg({
							msg: p_msg_err_nomovements,
							type: 'warning'
						});
					} else {
						$('#list_account tbody tr:odd').addClass('rowHighlight'); 
					}
				}
			});
		}
	</script>
	<script>
		load_write_list_account("€", "p_msg_err_nomovements");
	</script>
        <!---------------- -->
	</div>
	<!-- end of stage wrap -->
</div>
<!-- end of wrap -->

<!-- / END -->
</body>
</html>