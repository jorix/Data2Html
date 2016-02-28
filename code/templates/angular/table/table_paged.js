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
    $scope.d2h_filter={};
    // server
    var _req = function() {
        return {
            method: 'POST',
            url: '$${serviceUrl}lang=ca',
            headers: {
                'Content-Type': undefined
            },
            data: {
                d2h_oper: 'read',
                d2h_filter: $scope.d2h_filter,
                d2h_page: {
                    pageSize: $scope.pageSize,
                    pageStart: $scope.pageStart
                }
            }
        };
    };
    
    $scope.readPage = function() {
        $scope.pageStart = 1; //current page
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
        $scope.pageStart += $scope.pageSize;
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
    
    // local
    var pageSizes = [10, 20, -4, -3, 50, 100];
    $scope.pageSizeOp = [];
    for (i = 0, len = pageSizes.length; i < len; i++) {
        $scope.pageSizeOp.push({
            value: pageSizes[i],
            'text': pageSizes[i]+''
        });
    }
    $scope.sortBy = function(predicate, reverse) {
        $scope.predicate = predicate;
        $scope.reverse = reverse;
    };
    $scope.start = function() {
        $scope.readPage();
        $scope.sortBy('id', true);
    };
});