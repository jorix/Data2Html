<?php die("It's uncooked!"); ?>
<ui-select
    ng-controller="$${id}"
    ng-model="$${prefix}$${name}" 
    name="$${id}"
    theme="bootstrap"
    ng-change="changed()"
    $${validations}
>   <ui-select-match
        allow-clear="true"
        placeholder="$${description}"
    >{{$select.selected.text}}</ui-select-match>
    <ui-select-choices 
        repeat="item.value as item in data | filter: {text: $select.search}">
        <span ng-bind-html="item.text | highlight: $select.search"></span>
    </ui-select-choices>
</ui-select>
