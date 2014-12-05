<?php
// https://gist.github.com/aaronpk/3612742
require('app.php');

if ( !$monitor->is_loggued() )
{
?>
   
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>YunoDash</title>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="format-detection" content="telephone=no" />
    <meta name="viewport" content="user-scalable=no, width=device-width, height=device-height" />
    <link rel="icon" href="img/favicon.ico">
    
    <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/custom.css">
    
    <!-- Always define js console -->
    <script type="text/javascript">
      if (typeof console === "undefined" || typeof console.log === "undefined")
      {
        console = {};
        console.log = function () {};
      }
    </script>
<!--
    <script src="bower_components/jquery/jquery.min.js"></script>
    <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
-->
  </head>

  <body>
    <table id="page-table" >
      <tr>
        <td id="page-td">
          <div class="login-github">
            <a title="Please login with GitHub" href="login.php"></a>
          </div>
        </td>
      </tr>
    </table>
  </body>
</html>
    
<?php
}
else
{
?>

<!DOCTYPE html>
<html lang="en" ng-app="appDashboard">
<head>
  <meta charset="utf-8">
  <title>YunoDash</title>
  
  <link rel="icon" href="img/favicon.ico">
  
  <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="bower_components/bootstrap-select/dist/css/bootstrap-select.min.css">
  <link rel="stylesheet" href="css/app.css">
  
  <script src="bower_components/jquery/jquery.min.js"></script>
  <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
  <script src="bower_components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
  <script src="bower_components/angular/angular.min.js"></script>
  <script src="bower_components/angular-bootstrap/ui-bootstrap-tpls.min.js"></script>
  <script src="js/app.js"></script>
  
</head>
<body ng-controller="YnhAppDashboardCtrl">

<nav class="navbar navbar-default" role="navigation">
  <div class="container-fluid">
    <ul class="nav navbar-nav">
      <li>
        <form class="navbar-form navbar-left">
          <button class="btn {{ onlymyapps | myappbtn }}" ng-click="onlymyapps = !onlymyapps">Show only my apps</button>
        </form>
      </li>
      <li>
        <form class="navbar-form navbar-left">
          <label>Sort by :</label>
          <select ng-model="orderProp" class="selectpicker">
            <option value="json.manifest.name">Application Name</option>
            <option value="json.manifest.developer.name">Maintainer</option>
            <option value="json.lastUpdate">Last updated</option>
          </select>
        </form>
      </li>
    </ul>
    <ul>
      <li>
        <form class="navbar-form navbar-right" role="search">
          <input ng-model="query" class="form-control" placeholder="Search...">
          <a class="btn btn-default" title="Log out" href="logout.php"><span class="glyphicon glyphicon-off"></span></a>
        </form>
      </li>

    </ul>
  </div>
  </nav>
  <div class="container">
    <div ng-if="dataLoading">
        <img class="img-responsive center-block" src="img/loading.gif"></img>
    </div>
    
    <accordion close-others="oneAtATime">
      <accordion-group ng-repeat="app in apps | selectmyapps:onlymyapps | filter:query | orderBy:orderProp" class="{{ app | panelclass}}">
        <accordion-heading>
          {{app.json.manifest.name}}
          <div class="pull-right small">
            {{ app | status }}
          </div>
        </accordion-heading>
        
        <p><strong>Description</strong>: {{ app.json.manifest.description.en }}</p>
        <p><strong>Last update</strong>: {{ app.json.lastUpdate + "000" | date:'yyyy-MM-dd HH:mm:ss Z' }}</p>
        <p><strong>Maintainer</strong>: {{ app.json.manifest.developer.name }} <small class="text-muted">({{ app.json.manifest.developer.email }})</small></p>
        <p><strong>Git</strong>: <a href="{{ app.json.git.url }}" target="_blank">{{ app.json.git.url }}</a> 
          <small class="text-muted">({{ app.json.git.branch }})</small>
        </p>
        <p><strong>Published revision</strong>: {{ app.json.git.revision }}</p>
        <p><strong>Latest revision</strong>: {{ app.trunk_rev }}</p>
        <div ng-if="app | not_uptodate">
          <a href="{{ app.diff_url }}" target="_blank" class="btn btn-default">View diff</a>
          <a href="{{ app | validate_url }}" target="_blank" class="btn btn-default">Validate</a>
        </div>
      </accordion-group>
    </accordion>
  </div>
  <script>
    $( document ).ready(function() {
      $('.selectpicker').selectpicker();
    });
  </script>
</body>
</html>
    
<?php
}
?>
