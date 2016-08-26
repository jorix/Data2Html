<!DOCTYPE html>
<html ng-app="d2h_App" ng-app lang="ca">
<head>
    <meta charset="utf-8">

    <link href="../outside/bootstrap-3.3.6-dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../outside/angular-ui/ui-select-0.13.2/dist/select.min.css" rel="stylesheet">
    <style type="text/css">
    a {cursor: pointer;}
    span.glyphicon.d2h_sortActivated {color:red}
    label {font-weight:	normal;} /* remove font-weight:bold form Bootstrap */
    /**
     * [ng-cloak] is used to prevent show templates, see:
     * https://docs.angularjs.org/api/ng/directive/ngCloak
     */
    [ng-cloak] {  display: none !important; }
    .red {color:red}
    
    /**
     * Validations colors
    .ng-invalid.ng-dirty {
        border-color: red
    }
    .ng-valid.ng-dirty {
        border-color: green
    }
     */
    
    </style>
    <script src="../outside/angular-1.4.8/angular.min.js"></script>
    <script src="../outside/angular-1.4.8/angular-sanitize.min.js"></script>
    <script src="../outside/angular-1.4.8/i18n/angular-locale_ca.js"></script>
    <script src="../outside/angular-1.2.16/angular-route.min.js"></script>
    <script src="../outside/angular-ui/ui-bootstrap-tpls-1.0.3.min.js"></script>
    <script src="../outside/angular-ui/ui-select-0.13.2/dist/select.min.js"></script>
    <script>

var d2h_App = angular.module('d2h_App', ['ui.bootstrap', 'ngSanitize', 'ui.select', 'ngRoute']);
d2h_App.factory('services', ['$scope', '$http', function($scope, $http) {
    var _vRender_urlRequest = '_controller.php?lang=ca&model=aixada_ufs:list';
    var _serv = {};
    _serv.getPage = function(d2h_filter, d2h_sort, d2h_page){
        $http({
            method: 'POST',
            url: _vRender_urlRequest,
            headers: {
                'Content-Type': undefined
            },
            data: {
                d2h_oper: 'read',
                d2h_filter: d2h_filter,
                d2h_sort: d2h_sort,
                d2h_page: d2h_page
            }
        });
    };
    return _serv;
}]);
d2h_App.controller('d2h_1', [
    '$scope', '$http', 'pageDefaults', 'filterDefaults',
    function($scope, $http, pageDefaults, filterDefaults) {
    var vRender_urlRequest = '_controller.php?lang=ca&model=aixada_ufs:list';
    $scope.d2h_filter = filterDefaults();
    $scope.d2h_sort = 'account_id';
    $scope.d2h_page = pageDefaults();
    
    // server
    var _req = function() {
        return {
            method: 'POST',
            url: vRender_urlRequest,
            headers: {
                'Content-Type': undefined
            },
            data: {
                d2h_oper: 'read',
                d2h_filter: $scope.d2h_filter,
                d2h_sort: $scope.d2h_sort,
                d2h_page: $scope.d2h_page
            }
        };
    };
    
    $scope.readPage = function() {
        $scope.d2h_page.pageStart = 1; //current page
        $http(_req()).then(
            function(response) { // Ok
                $scope.data = response.data.rows;
                // $scope.status = response.status;
            }, function(response) { // Error
                // $scope.data = response.data || "Request failed";
                // $scope.status = response.status;
                console.log("IRROR");
            }
        );
    };
    $scope.nextPage = function() {
        $scope.d2h_page.pageStart = 
            parseInt($scope.d2h_page.pageStart, 10) +
            parseInt($scope.d2h_page.pageSize, 10);
        $http(_req()).then(
            function(response) { // Ok
                Array.prototype.push.apply(
                    $scope.data,
                    response.data.rows
                );
            }, function(response) { // Error
                console.log("IRROR");
            }
        );
    };
    $scope.sortBy = function(predicate, reverse) {
        $scope.predicate = predicate;
        $scope.reverse = reverse;
    };
    $scope.start = function() {
        $scope.readPage();
       //$scope.sortBy('id', true);
    };
}]);


d2h_App.config(['$routeProvider', function($routeProvider) {
    $routeProvider
      .when('/', {
        templateUrl: '_htmlRender.php?lang=ca&model=aixada_ufs:list',
        controller: 'd2h_1'
      })
      .otherwise({
        redirectTo: '/'
      });
}]);

d2h_App.controller('d2h_2', ['$scope', function ($scope) {
    $scope.changed = function() {
        $scope.readPage();
    };
}]);

d2h_App.controller('d2h_6', ['$scope', function($scope) {
    $scope.clear = function() {
        $scope.d2h_filter={};
    };
    $scope.changed = function() {
        $scope.readPage();
    };
}]);

d2h_App.factory('pageDefaults', [function() {
        return function() {
            return {"pageSize":10};
        };
}]);
d2h_App.factory('filterDefaults', [function() {
    return function() {
        return {"active":true};
    };
}]);
    </script>

    <title>Simple Datagrid with search, sort and paging using AngularJS, PHP, MySQL</title>
</head>
<body>
    <div class="container">
    <ng-view></ng-view>
    </div>
</body>
</html>
