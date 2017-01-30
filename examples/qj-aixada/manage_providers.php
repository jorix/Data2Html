<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ca" lang="ca">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>title</title>
   
    <script src="../../external/jquery-2.1.0/jquery.js" "type"="text/javascript"></script>
    <link  href="../../external/bootstrap-3.3.6-dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../external/bootstrap-3.3.6-dist/js/bootstrap.min.js" "type"="text/javascript" ></script>
    <script src="../../code/js/jQuery-Data2Html.js" "type"="text/javascript" ></script>
</head>
<body>
    <div class="container">
        <div class="row" id="tbl_providers" >
        <table class="table table-striped table-bordered">
            <tbody>
                <tr providerId="{id}" responsibleUfId="{responsible_uf_id}" >
                    <td><input type="checkbox" name="providerBulkAction"/></td>
                    <td class="textAlignRight">{id}</td>
                    <td title="<?php echo 'click_to_list'; ?>">{name}</td>
                    <td>{phone1} / {phone2}</td>
                    <td>{email}</td>
                    <td>{active}</td>
                    <td><?php echo 'uf_short';?>{responsible_uf_id} {responsible_uf_name}</td>
                    <td><a href="javascript:void(null)" class="btn_edit_provider"><?php echo 'edit'; ?></a> | 
                    <a href="javascript:void(null)" class="btn_del_provider"><?php echo 'btn_del'; ?></a></td>
                </tr>						
            </tbody>
        </table>
        </div>
    </div>
    <script "type"="text/javascript">
    $('#tbl_providers').data2html({
        url: '../_controller.php',
        params : 'model=aixada_providers',
        beforeLoad: function(){
            $('.loadSpinner').show();
        },
        rowComplete: function (rowIndex, row){

        },
        complete: function(rowCount){
            $('.loadSpinner').hide();
            //$('tr:even', this).addClass('rowHighlight');
            $('p.providerActiveStatus').each(function(){
                if ($(this).text() == "1"){
                    $(this).html('<span class="ui-icon ui-icon-check"></span>').addClass('aix-style-ok-green ui-corner-all')
                } else {
                    $(this).html('<span class="ui-icon ui-icon-cancel"></span>').addClass("noRed ui-corner-all");
                }
            });
            $("#tbl_providers").trigger("update"); 
        }
    });
    $('#tbl_providers').data2html("load");
    </script>
</body>
</html>