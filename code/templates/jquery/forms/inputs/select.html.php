<?php die("It's uncooked!"); ?>
<select class="form-control" style="padding:0;"
    title="$${title}"
    ng-controller="$${id}"
    ng-model="$${prefix}$${name}"
    ng-change="changed()"
    ng-options="item.value as item.text for item in data"
    $${validations}
></select>
<script>
    d2h_App.controller('$${id}', function($scope, $http) {
        $scope.item = {};
        $http({
            method: 'POST',
            url: '$${url}lang=ca',
            headers: {
                'Content-Type': undefined
            },
            data: {d2h_oper: 'read'}
        }).then(
            function(response) { // Ok
                $scope.data = response.data.rows;
            }, function(response) { // Error
                console.log(response.status);
            }
        );
    });
</script>
