<?php

require('app.php');


if (! is_loggued() )
{
  header('Location: index.php');
  die();
}

require('header.php');

?>

<div class="container">

<?php

$user = apiRequest($apiURLBase . 'user');

$applist = json_decode(file_get_contents('https://app.yunohost.org/list.json'));
$app_data = array();
foreach($applist as $app)
{
  $url = $app->git->url;

  if ( preg_match("/https:\/\/github.com\/(.+)\/(.+)/", $app->git->url, $matches) != 1 )
  {
    // TODO handle error
  }
  $github_username = $matches[1];
  $github_repo = $matches[2];
  if ( substr($github_repo, -4) == ".git" )
  {
    $github_repo = substr_replace($github_repo, '', -4);
  }

  $latest_commit_apiurl =
    str_replace( array("{user}", "{repo}", "{branch}"),
                 array($github_username, $github_repo, $app->git->branch),
                 "https://api.github.com/repos/{user}/{repo}/git/refs/heads/{branch}" );
  
  $latest_commit_result = apiRequest($latest_commit_apiurl);
  
  $latest_rev = $latest_commit_result->object->sha;
  $published_rev = $app->git->revision;
  
  $diff_url = 
    str_replace( array("{user}", "{repo}", "{first}", "{second}"),
                 array($github_username, $github_repo, $published_rev, $latest_rev),
                 "https://github.com/{user}/{repo}/compare/{first}...{second}" );

  $diff_api_url = 
    str_replace( array("{user}", "{repo}", "{first}", "{second}"),
                 array($github_username, $github_repo, $published_rev, $latest_rev),
                 "https://api.github.com/repos/{user}/{repo}/compare/{first}...{second}" );

  $diff_api_url_result = apiRequest($diff_api_url);
  $diff_api_url_result->total_commits;
  
  $app_data[] = 
    array(
      "id" => $app->manifest->id,
      "name" => $app->manifest->name,
      "up_to_date" => $published_rev == $latest_rev,
      "status" => '' . $diff_api_url_result->total_commits . ' behind',
      "desc" => $app->manifest->description->en,
      "update" => $app->lastUpdate,
      "maintainer" => $app->manifest->developer->name,
      "maintainer_mail" => $app->manifest->developer->email,
      "git" => $github_repo,
      "branch" => $app->git->branch,
      "published_rev" => $published_rev,
      "trunk_rev" => $latest_rev,
      "diff_url" => $diff_url
      );
}

echo '<h3>Logged in as </h3>';
echo '<h4>' . $user->name . '</h4>';
echo '<p><a href="' . $baseURL . '/logout.php">Log out</a></p>';

// $mustache = new Mustache_Engine;
// echo $mustache->render( file_get_contents('main.mustache'), array( "apps" => $app_data ) );

$loader = new Twig_Loader_Filesystem( "." );
$twig = new Twig_Environment($loader, array());
echo $twig->render("main.tpl", array("apps" => $app_data));

?>

</div>

<?php
require('footer.php');
?>