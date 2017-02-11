<?php die("It's uncooked!"); ?>
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