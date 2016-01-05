<!DOCTYPE html>
<html ng-app="myApp" ng-app lang="en">
<head>
    <meta charset="utf-8">
    <link href="angular/css/bootstrap.min.css" rel="stylesheet">
    <style type="text/css">
    a {cursor: pointer;}
    span.glyphicon.d2h_sortActivated {color:red}
    /* 
    [ng-cloak] is used to prevent show templates, see:
        https://docs.angularjs.org/api/ng/directive/ngCloak
    */
    [ng-cloak] {  display: none !important; }
    .red {color:red}
    </style>
    <title>Simple Datagrid with search, sort and paging using AngularJS, PHP, MySQL</title>
</head>
<body>
<div ng-controller="customersCrtl" class="container">
    <div class="row">
        <div class="col-md-3">Filter:
            <input type="text" placeholder="Filter" class="form-control" 
                ng-model="search"
            />
        </div>
    </div>
<?php
    require_once("config_db.php");
    require_once("account__.php");
    $a = new aixada_account();
    $render = new Data2Html_Render("templates/angular");
    echo $render->table($a);
?>
</div>

<script src="angular/js/angular-1.4.8.min.js"></script>
<script src="angular/js/i18n/angular-locale_ca.js"></script>       
<script src="angular/js/ui-bootstrap-tpls-0.10.0.min.js"></script>
<script src="templates/tableAngular.js"></script>
</body>
</html>
