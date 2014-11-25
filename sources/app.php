<?php

require('config.php');

ini_set("session.use_cookies",1);
session_start();

//require('Mustache/Autoloader.php');
//Mustache_Autoloader::register();

require('Twig/lib/Twig/Autoloader.php');
Twig_Autoloader::register();
$loader = new Twig_Loader_Filesystem( "./templates" );
$twig = new Twig_Environment($loader, array());


function apiRequest($url, $post = FALSE, $headers = array())
{
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  if ($post)
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
  $headers[] = 'Accept: application/json';
  $headers[] = 'User-Agent: Yunohost App Status';
  if (session('access_token'))
    $headers[] = 'Authorization: Bearer ' . session('access_token');
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $response = curl_exec($ch);
  curl_close($ch);
  return json_decode($response);
}

function get($key, $default = NULL)
{
  return array_key_exists($key, $_GET) ? $_GET[$key] : $default;
}

function session($key, $default = NULL)
{
  return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
}

function is_loggued()
{
  return session('access_token') != NULL;
}

?>
