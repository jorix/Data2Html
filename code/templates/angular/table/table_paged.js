/*
function ContactDirective(){
  return {
    restrict: 'E',
    templateUrl: 'my-contact-template.html',
    controller: 'contactController',
    controllerAs: 'contactCtrl',
    scope: {
      contact: '='
    },
    link:function(scope, elem, attrs, contactCtrl){
      scope.$watch('contact', function(newContact){
        //Still just initializing the contact using
        // the controller
        contactCtrl.setContact(newContact);
      });
    }
  };
}
*/

d2h_App.controller('$${id}', function ($scope, $http) {

    // server
    var _url = function() {
        return 'account_controller.php' +
            '?pageSize=' + $scope.pageSize.value +
            '&pageStart=' + $scope.pageStart;
    };
    $scope.initialPage = function() {
        $scope.pageStart = 1; //current page
        $http.post(_url(), $scope.d2h_filter)
        .success(function(data) {
            $scope.list = data.rows;
        });
    };
    $scope.nextPage = function() {
        $scope.pageStart += $scope.pageSize.value;
        $http.post(_url(), $scope.d2h_filter)
        .success(function(data) {
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