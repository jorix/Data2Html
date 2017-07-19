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
    
<!-- START "../../code/templates/jquery/forms/formEdit/edit.html.php" [[ -->
<!-- ======================================== -->

<div class="row">
    <form id="d2h_1_form_" class="simple-form"
        data-d2h-form="
            url:    '../_controller/_controller.php?model=aixada_providers&form=default&d2h_keys=2',
            type:   'GET'
        "
        data-d2h-on="change:readPage"
    >
        <div class="form-inline">
<!-- START "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" [[ -->
<!-- ======================================== -->

<div><label class="col-md-1 form-group">id</label>
<!-- START "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" [[ -->
<!-- ======================================== -->

<input type="text" class="form-control"
    placeholder="id"
    name="id"
    
    
>

<!-- END  "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" ]] -->
</div>

<!-- END  "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" ]] -->

<!-- START "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" [[ -->
<!-- ======================================== -->

<div><label class="col-md-1 form-group">$$Provider</label>
<!-- START "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" [[ -->
<!-- ======================================== -->

<input type="text" class="form-control"
    placeholder="$$Provider"
    name="name"
    
    
>

<!-- END  "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" ]] -->
</div>

<!-- END  "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" ]] -->

<!-- START "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" [[ -->
<!-- ======================================== -->

<div><label class="col-md-1 form-group">$$Provider_contact</label>
<!-- START "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" [[ -->
<!-- ======================================== -->

<input type="text" class="form-control"
    placeholder="$$Provider_contact"
    name="contact"
    
    
>

<!-- END  "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" ]] -->
</div>

<!-- END  "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" ]] -->

<!-- START "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" [[ -->
<!-- ======================================== -->

<div><label class="col-md-1 form-group">$$Provider_address</label>
<!-- START "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" [[ -->
<!-- ======================================== -->

<input type="text" class="form-control"
    placeholder="$$Provider_address"
    name="address"
    
    
>

<!-- END  "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" ]] -->
</div>

<!-- END  "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" ]] -->

<!-- START "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" [[ -->
<!-- ======================================== -->

<div><label class="col-md-1 form-group">$$Nif</label>
<!-- START "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" [[ -->
<!-- ======================================== -->

<input type="text" class="form-control"
    placeholder="$$Nif"
    name="nif"
    
    
>

<!-- END  "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" ]] -->
</div>

<!-- END  "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" ]] -->

<!-- START "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" [[ -->
<!-- ======================================== -->

<div><label class="col-md-1 form-group">$$Zip</label>
<!-- START "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" [[ -->
<!-- ======================================== -->

<input type="text" class="form-control"
    placeholder="$$Zip"
    name="zip"
    
    
>

<!-- END  "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" ]] -->
</div>

<!-- END  "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" ]] -->

<!-- START "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" [[ -->
<!-- ======================================== -->

<div><label class="col-md-1 form-group">$$Provider_email</label>
<!-- START "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" [[ -->
<!-- ======================================== -->

<input type="text" class="form-control"
    placeholder="$$Provider_email"
    name="email"
    
    1
>

<!-- END  "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" ]] -->
</div>

<!-- END  "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" ]] -->

<!-- START "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" [[ -->
<!-- ======================================== -->

<div><label class="col-md-1 form-group">text</label>
<!-- START "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" [[ -->
<!-- ======================================== -->

<input type="text" class="form-control"
    placeholder="text"
    name="text"
    
    
>

<!-- END  "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" ]] -->
</div>

<!-- END  "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" ]] -->

<!-- START "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" [[ -->
<!-- ======================================== -->

<div><label class="col-md-1 form-group">active</label>
<!-- START "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" [[ -->
<!-- ======================================== -->

<input type="text" class="form-control"
    placeholder="active"
    name="active"
    
    
>

<!-- END  "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" ]] -->
</div>

<!-- END  "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" ]] -->

<!-- START "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" [[ -->
<!-- ======================================== -->

<div><label class="col-md-1 form-group">$$Order_format</label>
<!-- START "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" [[ -->
<!-- ======================================== -->

<input type="text" class="form-control"
    placeholder="$$Order_format"
    name="order_send_format"
    value="default"
    
>

<!-- END  "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" ]] -->
</div>

<!-- END  "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" ]] -->

<!-- START "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" [[ -->
<!-- ======================================== -->

<div><label class="col-md-1 form-group"></label>
<!-- START "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" [[ -->
<!-- ======================================== -->

<input type="text" class="form-control"
    
    name="fields"
    
    
>

<!-- END  "../../code/templates/jquery/forms/formEdit/../inputs/text.html.php" ]] -->
</div>

<!-- END  "../../code/templates/jquery/forms/formEdit/../layouts/block.html.php" ]] -->
</div>
    </form>
</div>

<!-- END  "../../code/templates/jquery/forms/formEdit/edit.html.php" ]] -->

                
<script>
// START "../../code/templates/jquery/forms/formEdit/edit.js.php" [[
// ========================================

$('[data-d2h-form]').data2html('load');

// END  "../../code/templates/jquery/forms/formEdit/edit.js.php" ]]
</script>    </div>
    <div class="d2h_waiting"></div>
</body>
</html>
