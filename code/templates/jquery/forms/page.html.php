<?php die(basename(__FILE__) . ': It is crude!'); ?>
<div class="row" ng-controller="$${id}">
    <form name="$${id}" class="simple-form"  novalidate>
        <div class="form-inline">$${body}</div>
    </form>
    <span ng-init="start()" ng-cloak>
        Filtered {{filtered.length}} of {{data.length}}
    </span>
</div>
