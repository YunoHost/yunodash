<?php
// https://gist.github.com/aaronpk/3612742
require('app.php');

if ( $monitor->is_loggued() )
{
  $monitor->loadAppData();
  
  header('Cache-Control: no-cache, must-revalidate');
  header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');
  header('Content-type: application/json');

  echo json_encode($monitor->get_apps_array());
}

?>
