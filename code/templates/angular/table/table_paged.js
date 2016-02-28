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
    $scope.d2h_page={};
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
        $scope.d2h_page.pageStart += $scope.d2h_page.pageSize;
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
        $scope.sortBy('id', true);
    };
});