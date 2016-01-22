<!DOCTYPE html>
<html ng-app="myApp" ng-app lang="en">
<head>
    <meta charset="utf-8">
    <link href="../outside/bootstrap-3.3.6-dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../outside/angular-ui/ui-select-0.13.2/dist/select.min.css" rel="stylesheet">
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
    <script src="../outside/angular-1.4.8/angular.min.js"></script>
    <script src="../outside/angular-1.4.8/angular-sanitize.min.js"></script>
    <script src="../outside/angular-1.4.8/i18n/angular-locale_ca.js"></script>
    <script src="../outside/angular-ui/ui-bootstrap-tpls-1.0.3.min.js"></script>
    <script src="../outside/angular-ui/ui-select-0.13.2/dist/select.min.js"></script>
    <script>
        var d2h_App = angular.module('myApp', ['ui.bootstrap', 'ngSanitize', 'ui.select']);
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
