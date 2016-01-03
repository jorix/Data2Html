var app = angular.module('myApp', ['ui.bootstrap']);

app.controller('customersCrtl', function ($scope, $http) {

    // server
    var _url = function() {
        return 'account_controller.php' +
            '?pageSize=' + $scope.pageSize.value +
            '&pageStart=' + $scope.pageStart;
    };
    $scope.initialPage = function() {
        $scope.pageStart = 1; //current page
        $http.get(_url()).success(function(data) {
            $scope.list = data.rows;
        });
    };
    $scope.nextPage = function() {
        $scope.pageStart += $scope.pageSize.value;
        $http.get(_url()).success(function(data) {
            Array.prototype.push.apply($scope.list, data.rows);
        });
    };
    
    // local
    var pageSizes = [10, 20, 50, 100];
    $scope.pageSizeOp = [];
    for (i = 0, len = pageSizes.length; i < len; i++) {
        $scope.pageSizeOp.push({
            value: pageSizes[i],
            label: pageSizes[i]+''
        });
    }
    $scope.sortBy = function(predicate, reverse) {
        $scope.predicate = predicate;
        $scope.reverse = reverse;
    };
    $scope.start = function() {
        $scope.initialPage();
        $scope.sortBy('id', true);
    };
});