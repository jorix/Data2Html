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
<div ng-controller="customersCrtl">
<div class="container">
<br/>
<blockquote><h4><a href="http://angularcode.com/angularjs-datagrid-paging-sorting-filter-using-php-and-mysql/">Simple Datagrid with search, sort and paging using AngularJS + PHP + MySQL</a></h4></blockquote>
<br/>
    <div class="row">
        <div class="col-md-2">PageSize:
            <select ng-model="entryLimit" class="form-control">
                <option>5</option>
                <option>10</option>
                <option>20</option>
                <option>50</option>
                <option>100</option>
            </select>
        </div>
        <div class="col-md-3">Filter:
            <input type="text" ng-model="search" ng-change="filter()" placeholder="Filter" class="form-control" />
        </div>
        <div class="col-md-4">
            <h5>Filtered {{ filtered.length }} of {{ totalItems}} total customers</h5>
        </div>
    </div>
    <br/>
    <div class="row">
<?php
    require_once("config_db.php");
    require_once("account__.php");
    $a = new aixada_account($db_driver);
    echo $a->renderAngularTable("templates/angular");
?>
        <div class="col-md-12" ng-show="filteredItems > 0">    
            <div pagination="" page="pageNumber" on-select-page="setPage(page)" boundary-links="true" total-items="filteredItems" items-per-page="entryLimit" class="pagination-small" previous-text="&laquo;" next-text="&raquo;"></div>
        </div>
    </div>
</div>
</div>
<script src="angular/js/angular-1.4.8.min.js"></script>
<script src="angular/js/i18n/angular-locale_ca.js"></script>       
<script src="angular/js/ui-bootstrap-tpls-0.10.0.min.js"></script>
<script src="templates/tableAngular.js"></script>
</body>
</html>
