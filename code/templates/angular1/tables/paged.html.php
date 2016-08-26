<?php die(basename(__FILE__) . ': It is crude!'); ?>
<div ng-controller="$${id}">
$${filter}
<div class="row">
    <div class="col-md-12" id="$${id}">
        <h3>$${title}</h3>
        <table class="table table-striped table-bordered">
            <thead><tr>
                $${thead}
            </tr></thead>
            <tfoot ng-show="filtered.length > 0" ng-cloak><tr>
                <td colspan="$${colCount}">
                    $${page}
                </td>
            </tr></tfoot>
            <tbody 
                ng-show="filtered.length > 0" ng-cloak><tr 
                    ng-repeat="item in filtered = (data | filter:search | orderBy :predicate :reverse)">
                $${tbody}
            </tr></tbody>
        </table>
        <div class="alert alert-warning" role="alert"
            ng-show="filtered.length == 0"
        ng-cloak>
            No data found
        </div>
    </div>
</div>
<span ng-init="start()"></span>
</div>