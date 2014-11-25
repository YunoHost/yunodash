<?php
require('app.php');

/* Generate a random hash and store in the session for security */
$_SESSION['state'] = hash('sha256', microtime(TRUE) . rand() . $_SERVER['REMOTE_ADDR']);
unset($_SESSION['access_token']);

$params = array(
  'client_id' => OAUTH2_CLIENT_ID,
  'redirect_uri' => $baseURL . '/login_redirect.php',
  'scope' => '',
  'state' => $_SESSION['state']
);

// Redirect the user to Github's authorization page
header('Location: ' . $authorizeURL . '?' . http_build_query($params));
die();

?>