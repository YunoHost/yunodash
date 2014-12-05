'use strict';

var appDashboard = angular.module('appDashboard', ['ui.bootstrap']);

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
}])
.filter('panelclass', function() {
  return function(app) {
    return (app.commits_behind == 0 ? "panel-success" : "panel-danger") +
      (app.is_mine ? " myapp" : "");
  }
})
.filter('status', function() {
  return function(app) {
    return (app.commits_behind == 0 ? "" : app.commits_behind + " commits behind");
  }
})
.filter('myappbtn', function() {
  return function(val) {
    return val ? "btn-success" : "btn-default";
  }
})
.filter('selectmyapps', function() {
  return function(items, onlymyapps) {
    return onlymyapps ? items.filter( function(item) { return item.is_mine }) : items;
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
