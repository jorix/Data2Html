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
    <script src="angular/js/angular-1.4.8.min.js"></script>
    <script src="angular/js/i18n/angular-locale_ca.js"></script>       
    <script src="angular/js/ui-bootstrap-tpls-0.10.0.min.js"></script>
    <script>
        var d2h_App = angular.module('myApp', ['ui.bootstrap']);
    </script>
    <div ng-controller="d2h_aixada_account" class="container">
    <?php
        require_once("account__.php");
        $a = new aixada_account();
        $render = new Data2Html_Render("../code/templates/angular/table_paged.ini");
        echo $render->filterForm($a);
        echo $render->table($a);
    ?>
    </div>

</body>
</html>
