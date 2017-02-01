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
        <ul>
            <li><a href="#" onclick="$('#tbl_providers').data2html('load', {params: 'model=aixada_ufs'}); return false;">Ufs</a></li>
            <li><a href="#" onclick="$('#tbl_providers').data2html('load', {params: 'model=aixada_providers'}); return false;">Proveidors</a></li>
            <li><a href="#" onclick="$('#tbl_xxx').data2html('load', {params: 'model=aixada_ufs'}); return false;">xxx Ufs</a></li>
        </ul>
        <div class="row" id="tbl_providers" >
        <table class="table table-striped table-bordered">
            <tbody>
                <tr><td>previ</td></tr>
                <tr class="d2h_repeat" providerId="{id}" responsibleUfId="{responsible_uf_id}" >
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
                <tr><td>post</td></tr>              
            </tbody>
        </table>
        <table id="tbl_xxx" class="table table-striped table-bordered">
            <tbody><tr class="d2h_repeat2" providerId="{id}" responsibleUfId="{responsible_uf_id}" >
                    <td><input type="checkbox" name="providerBulkAction"/></td>
                    <td class="textAlignRight">{id}</td>
                    <td title="<?php echo 'click_to_list'; ?>">{name}</td></tr>
                <tr><td>post</td></tr>              
            </tbody>
            </table>
        <span class="d2h_repeat333"></span>
        </div>
    </div>
    <script "type"="text/javascript">
    $('#tbl_providers').data2html({
        url: '../_controller.php',
        params: 'model=aixada_providers'
    });
    $('#tbl_providers').data2html("load");
    </script>
    <script "type"="text/javascript">
    $('#tbl_xxx').data2html({
        classRepeat: 'd2h_repeat2',
        url: '../_controller.php',
        params: 'model=aixada_providers'
    });
    </script>
</body>
</html>