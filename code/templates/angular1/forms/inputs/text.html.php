<?php die(basename(__FILE__) . ': It is crude!'); ?>
<input type="text" class="form-control" 
    ng-model="$${prefix}$${name}"
    ng-change="changed()"
    ng-model-options="{ debounce: 500 }"
    placeholder="$${description}"
    name="$${name}"
    $${validations}
>
