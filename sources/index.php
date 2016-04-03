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
    <link rel="stylesheet" type="text/css" href="css/app.css">
    
  </head>

  <body>

    <table id="page-table">
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
  <link rel="stylesheet" href="css/app.css">
  
  <script src="bower_components/angular/angular.min.js"></script>
  <script src="bower_components/angular-bootstrap/ui-bootstrap-tpls.min.js"></script>
  <script src="js/app.js"></script>
</head>
<body ng-controller="YnhAppDashboardCtrl">

<nav class="navbar navbar-default" role="navigation">
  <div class="container-fluid">
    <ul class="nav navbar-nav">
      <li>
        <form class="navbar-form">
          <button class="btn {{ onlymyapps | myappbtn }}" ng-click="onlymyapps = !onlymyapps">My apps</button>
        </form>
      </li>
      <li>
        <form class="navbar-form">
          <button class="btn {{ onlylateapps | lateapp_button_style }}" ng-click="onlylateapps = !onlylateapps">Outdated apps</button>
        </form>
      </li>
      <li>
        <form class="navbar-form">
          <label>Sort by :</label>
          <select ng-model="orderProp" class="form-control">
            <option value="json.manifest.name">Application Name</option>
            <option value="json.manifest.developer.name">Maintainer</option>
            <option value="json.lastUpdate">Last updated</option>
          </select>
        </form>
      </li>
    </ul>
    <ul class="nav navbar-nav navbar-right">
      <li>
        <form class="navbar-form" role="search">
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
      <accordion-group ng-repeat="app in apps | selectmyapps:onlymyapps | selectlateapps:onlylateapps | filter:query | orderBy:orderProp" class="{{ app | panelclass}}">
        <accordion-heading>
          <img width=30 height=30 ng-src="{{ app.maintainer.avatar_url }}&s=40"></img>
          {{ app.json.manifest.name }}
          <div class="pull-right small">
            {{ app | status }}
          </div>
        </accordion-heading>
        <table class="table table-condensed">
            <tr>
              <td style="width:20%"><strong>Description</strong></td>
              <td>{{ app.json.manifest.description.en }}</td>
            </tr>
            <tr>
              <td><strong>Last update</strong></td>
              <td>{{ app.json.lastUpdate + "000" | date:'yyyy-MM-dd HH:mm:ss Z' }}</td>
            </tr>
            <tr>
              <td><strong>Maintainer</strong></td>
              <td>{{ app.json.manifest.maintainer.name }} <small class="text-muted">({{ app.json.manifest.maintainer.email }})</td>
            </tr>
            <tr>
              <td><strong>Git</strong></td>
              <td><a href="{{ app.json.git.url }}" target="_blank">{{ app.json.git.url }}</a></td>
            </tr>
            <tr>
              <td><strong>Manual update</strong></td>
              <td>python add_or_update.py official.json {{ app.json.git.url }}</td>
            </tr>
            <tr>
              <td><strong>Published revision</strong></td>
              <td><a href="{{ app | revision_url:app.json.git.revision }}" target="_blank">{{ app.json.git.revision }}</a></td>
            </tr>
            <tr>
              <td><strong>Latest revision</strong></td>
              <td><a href="{{ app | revision_url:app.trunk_rev }}" target="_blank">{{ app.trunk_rev }}</a></td>
            </tr>
        </table>
        
        <div ng-if="app | not_uptodate">
          <br>
          <div class="panel panel-default">
            <div class="panel-heading">
              <a href="{{ app.diff_url }}" target="_blank">Commits behind</a>
            </div>
            <table class="table table-condensed table-hover">
                <tr ng-repeat="commit in app.diff_commits">
                  <td style="width:5%;text-align:center"><a href="{{ commit.author_url }}" target="_blank"><img width=20 height=20 ng-src="{{ commit.author_gravatar_url }}&s=40" alt="{{ commit.author_login }}"></img></a></td>
                  <td style="width:10%"><a href="{{ commit.url }}" target="_blank">{{ commit.short_sha }}</a></td>
                  <td>{{ commit.message }}</td>
                  <td style="width:10%;text-align:right">{{ commit.date | date:'yyyy-MM-dd' }}</td>
                </tr>
            </table>
          </div>
        </div>

        <div ng-if="app.pull_requests">
          <br>
          <div class="panel panel-default">
            <div class="panel-heading">
              <a href="{{ app.json.git.url }}/pulls" target="_blank">Open Pull Requests</a>
            </div>
            <table class="table table-condensed table-hover">
                <tr ng-repeat="pr in app.pull_requests">
                  <td style="width:5%;text-align:center"><img width=20 height=20 ng-src="{{ pr.reporter.avatar_url }}&s=40" alt="{{ pr.reporter.login }}"></img></td>
                  <td style="width:5%"><a href="{{ pr.html_url }}" target="_blank">#{{ pr.number }}</a></td>
                  <td>{{ pr.title }}</td>
                </tr>
            </table>
          </div>
        </div>
        
        <div ng-if="app.issues">
          <br>
          <div class="panel panel-default">
            <div class="panel-heading">
              <a href="{{ app.json.git.url }}/issues" target="_blank">Open Issues</a>
            </div>
            <table class="table table-condensed table-hover">
                <tr ng-repeat="issue in app.issues">
                  <td style="width:5%;text-align:center"><img width=20 height=20 ng-src="{{ issue.reporter.avatar_url }}&s=40" alt="{{ issue.reporter.login }}"></img></td>
                  <td style="width:5%"><a href="{{ issue.html_url }}" target="_blank">#{{ issue.number }}</a></td>
                  <td>{{ issue.title }}</td>
                </tr>
            </table>
          </div>
        </div>
        
        <div ng-if="app.tests">
          <br>
          <div class="row">
            <div class="col-xs-6">
              <div class="panel panel-default">
                <div class="panel-heading">
                  <a href="{{ app | tests_url }}" target="_blank">Last tests results</a>
                </div>
                <table class="table table-condensed table-hover">
                    <tr ng-repeat="test in app.tests.child">
                      <td style="width:7%;text-align:right"><i class="glyphicon {{ test | test_glyph_class }}" style="{{ test | test_glyph_style }}"></i></td>
                      <td><a href="{{ app | single_test_url:test }}" target="_blank">{{ test.name }}</a></td>
                      <td style="width:20%;text-align:right">{{ test.duration | number:2 }} sec</td>
                    </tr>
                </table>
              </div>
            </div>
            <div class="col-xs-6">
              <div class="panel panel-default">
                <div class="panel-heading">
                  <a href="{{ app | tests_url }}" target="_blank">Last tests output</a>
                </div>
                <table class="table table-condensed table-hover">
                  <tr ng-repeat="testoutput in app.tests_attachments">
                     <td style="width:7%;text-align:right"><i class="glyphicon glyphicon-file"></i></td>
                     <td><a href="{{ testoutput | test_output_url:app }}" target="_blank">{{ testoutput }}</a></td>
                  </tr>
                </table>
              </div>
            </div>
          </div>
        </div>
        
        <div ng-if="app | not_uptodate">
          <br>
          <a href="{{ app | validate_url }}" target="_blank" class="btn btn-default">Validate</a>
        </div>
        
      </accordion-group>
    </accordion>
  </div>
</body>
</html>
    
<?php
}
?>
