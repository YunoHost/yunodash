'use strict';

var appDashboard = angular.module('appDashboard', ['ui.bootstrap']);
//var appDashboard = angular.module('appDashboard', ['ui.bootstrap', 'ui.grid', 'ui.grid.autoResize']);

appDashboard
.controller('YnhAppDashboardCtrl', ['$scope', '$http', function($scope, $http) {
  $scope.dataLoading = true;
  
  $http.get('../data.json.php').success(function(data) {
    $scope.apps = data;
    $scope.dataLoading = false;
  }).error(function () {
    $scope.dataLoading = false;
  });

  $scope.orderProp = 'json.manifest.name';
  $scope.onlymyapps = false;
  $scope.onlylateapps = false;
  
  
}])
.filter('panelclass', function() {
  return function(app) {
    return (app.commits_behind == 0 ? "panel-success" : "panel-danger") +
      (app.is_mine ? " myapp" : "");
  }
})
.filter('status', function() {
  return function(app) {
    var status_array = new Array();
    
    if (app.pull_requests.length > 0)
    {
      status_array[status_array.length] = app.pull_requests.length + " pull request" + (app.pull_requests.length > 1 ? "s" : "");
    }
    if (app.issues.length > 0)
    {
      status_array[status_array.length] = app.issues.length + " issue" + (app.issues.length > 1 ? "s" : "");
    }
    if (app.commits_behind > 0)
    {
      status_array[status_array.length] = app.commits_behind + " commit" + (app.commits_behind > 1 ? "s" : "") + " behind";
    }
    return status_array.join(", ");
  }
})
.filter('myappbtn', function() {
  return function(val) {
    return val ? "btn-success" : "btn-default";
  }
})
.filter('selectmyapps', function() {
  return function(items, onlymyapps) {
    return onlymyapps ? items.filter( function(item) { return item.is_mine } ) : items;
  }
})
.filter('lateapp_button_style', function() {
  return function(val) {
    return val ? "btn-success" : "btn-default";
  }
})
.filter('selectlateapps', function() {
  return function(items, onlylateapps) {
    return onlylateapps ? items.filter( function(item) { return item.commits_behind > 0 } ) : items;
  }
})
.filter('not_uptodate', function() {
  return function(app) {
    return app.commits_behind != 0;
  }
})
.filter('validate_url', function() {
  return function(app) {
    return "https://app.yunohost.org/validate.php?url="+app.json.git.url+"&branch="+app.json.git.branch+"&rev="+app.trunk_rev+"&email="+app.json.manifest.developer.email;
  }
})
;
