<?php
// https://gist.github.com/aaronpk/3612742
require('app.php');


if ( !$monitor->is_loggued() )
{
  echo $twig->render("index.tpl",
    array(
      "timezone_set" => $monitor->session('time'),
      "baseurl" => $baseURL
      )
    );
}
else
{
  $monitor->loadAppData();

  echo json_encode($monitor->get_apps_info());
}

?>
