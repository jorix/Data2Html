var app = angular.module('myApp', ['ui.bootstrap']);

app.filter('startFrom', function() {
    return function(input, start) {
        if(input) {
            start = +start; //parse to int
            return input.slice(start);
        }
        return [];
    }
});
app.controller('customersCrtl', function ($scope, $http, $timeout) {
    $http.get('account_controller.php').success(function(data){
        $scope.list = data.rows;
        $scope.pageNumber = 1; //current page
        $scope.pageSize = 5; //max no of items to display in a page
        $scope.filteredItems = $scope.list.length; //Initially for no filter  
        $scope.totalItems = $scope.list.length;
    });
    $scope.setPage = function(pageNumber) {
        $scope.pageNumber = pageNumber;
    };
    $scope.filter = function() {
        $timeout(function() { 
            $scope.filteredItems = $scope.filtered.length;
        }, 10);
    };
    $scope.sortBy = function(predicate, reverse) {
        $scope.predicate = predicate;
        $scope.reverse = reverse;
    };
    $scope.sortBy('id', true);
});
