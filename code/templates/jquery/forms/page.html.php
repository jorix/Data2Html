<?php die("It's uncooked!"); ?>
<div class="row" ng-controller="$${id}">
    <form id="$${id}" class="simple-form"  novalidate>
        <div class="form-inline">$${body}</div>
    </form>
    <span ng-init="start()" ng-cloak>
        Filtered {{filtered.length}} of {{data.length}}
    </span>
</div>
