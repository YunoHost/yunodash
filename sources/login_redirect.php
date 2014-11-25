<?php
require('app.php');

// Verify the state matches our stored state
if (!get('state') || $_SESSION['state'] != get('state'))
{
  header('Location: logout.php');
}

// Exchange the auth code for a token
$token = apiRequest($tokenURL, array(
  'client_id' => OAUTH2_CLIENT_ID,
  'client_secret' => OAUTH2_CLIENT_SECRET,
  //'redirect_uri' => $baseURL . $_SERVER['PHP_SELF'],
  'state' => $_SESSION['state'],
  'code' => get('code')
));

// Now we are loggued
$_SESSION['access_token'] = $token->access_token;

header('Location: main.php');
die();

?>