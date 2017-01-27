<?php die(basename(__FILE__) . ': It is crude!'); ?>
<div class="row" ng-controller="$${id}">
    <form name="$${id}" class="simple-form" xx-class="form-horizontal"  novalidate>
    <div>
        $${body}
        <span class="col-md-1 form-group">
            <br>
            <button class="btn btn-default"
                title="$$Clear filter"
                ng-click="clear()"
            >
                <span class="glyphicon glyphicon-remove"
                    aria-hidden="true"></span>
                <!-- span class="hidden-xs hidden-sm">$$Clear filter</span -->
            </button>
        </span>
    </div>
    </form>
    <div>$$Vallid {{$${id}.$valid}}</div>
</div>
