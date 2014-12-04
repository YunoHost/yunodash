<?php

require "config.php";
require "php-curl-multi/Curl/Multi.php";

ini_set("session.use_cookies",1);
session_start();

require('Twig/lib/Twig/Autoloader.php');
Twig_Autoloader::register();
$loader = new Twig_Loader_Filesystem( "./templates" );
$twig = new Twig_Environment($loader, array());



class AppInfo
{
  var $json;
  var $github_username;
  var $github_repo;
  var $trunk_rev;
  var $diff_url;
  var $commits_behind = 0;

  public function __construct($appjson)
  {
    $this->json = $appjson;

    if ( preg_match("/https:\/\/github.com\/(.+)\/(.+)/", $this->json->git->url, $matches) != 1 )
    {
      // TODO handle error
    }
    $github_username = $matches[1];
    $github_repo = $matches[2];
    
    if ( substr($github_repo, -4) == ".git" )
    {
      $github_repo = substr_replace($github_repo, '', -4);
    }
    $this->github_username = $github_username;
    $this->github_repo = $github_repo;
  }
  
  public function id()
  {
    return $this->json->manifest->id;
  }
  
  public function up_to_date()
  {
    return $this->commits_behind == 0;
  }
  
  public function name()
  {
    return $this->json->manifest->name;
  }
  
  public function status()
  {
    if( $this->up_to_date() )
    {
      return "";
    }
    else
    {
      return $this->commits_behind . " commits behind";
    }
  }
  
  public function desc()
  {
    return $this->json->manifest->description->en;
  }
  
  public function last_update()
  {
    return date(DATE_COOKIE, $this->json->lastUpdate);
  }
  
  public function maintainer()
  {
    return $this->json->manifest->developer->name;
  }
  
  public function maintainer_mail()
  {
    return $this->json->manifest->developer->email;
  }
  
  public function git()
  {
    return $this->json->git->url;
  }

  public function branch()
  {
    return $this->json->git->branch;
  }

  public function published_rev()
  {
    return $this->json->git->revision;
  }
  
}


class YunohostAppMonitor
{
  private $curl_multi = NULL;
  private $app_info_arr = array();

  public function __construct()
  {
    $this->curl_multi = new Curl_Multi();
  
  }
  
  public function __destruct()
  {
    $this->curl_multi->finish();
  }

  public function apiRequest($url, $post = FALSE, $headers = array())
  {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if ($post)
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    $headers[] = 'Accept: application/json';
    $headers[] = 'User-Agent: Yunohost App Status';
    if ($this->session('access_token'))
      $headers[] = 'Authorization: Bearer ' . $this->session('access_token');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response);
  }

  public function makeApiRequestHandle($url)
  {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $headers[] = 'Accept: application/json';
    $headers[] = 'User-Agent: Yunohost App Status';
    if ($this->session('access_token'))
      $headers[] = 'Authorization: Bearer ' . $this->session('access_token');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    return $ch;
  }

  public function get($key, $default = NULL)
  {
    return array_key_exists($key, $_GET) ? $_GET[$key] : $default;
  }

  public function session($key, $default = NULL)
  {
    return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
  }

  public function is_loggued()
  {
    return $this->session('access_token') != NULL;
  }
  
  public function get_user()
  {
    $ch = $this->makeApiRequestHandle('https://api.github.com/user');
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response);
  }
  
  public function loadAppData()
  {
    if ( $this->session("timezone") )
    {
      date_default_timezone_set( $this->session("timezone") );
    }
    
    $applist = json_decode(
      file_get_contents( 'https://app.yunohost.org/list.json' )
    );
    
    foreach($applist as $app)
    {
      $this->app_info_arr[$app->manifest->id] = new AppInfo($app);
     
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

      $this->curl_multi->addHandle(
        $this->makeApiRequestHandle($latest_commit_apiurl),
        array($this, "store_trunk_rev"),
        array(
          "app" => $app->manifest->id
          )
        );
    }
    $this->curl_multi->finish();
  }
  
  public function store_trunk_rev($curl_info, $curl_data, $callback_data)
  {
    $app_info = $this->app_info_arr[ $callback_data["app"] ];
    $app_info->trunk_rev = json_decode($curl_data)->object->sha;

    $app_info->diff_url = str_replace( array("{user}", "{repo}", "{first}", "{second}"),
       array($app_info->github_username, $app_info->github_repo, $app_info->json->git->revision, $app_info->trunk_rev),
       "https://github.com/{user}/{repo}/compare/{first}...{second}" );

    $diff_api_url = str_replace( array("{user}", "{repo}", "{first}", "{second}"),
       array($app_info->github_username, $app_info->github_repo, $app_info->json->git->revision, $app_info->trunk_rev),
       "https://api.github.com/repos/{user}/{repo}/compare/{first}...{second}" );

    $this->curl_multi->addHandle(
      $this->makeApiRequestHandle($diff_api_url),
      array($this, "store_diff_data"),
      array(
        "app" => $app_info->json->manifest->id
        )
      );
  }
  
  public function store_diff_data($curl_info, $curl_data, $callback_data)
  {
    $app_info = $this->app_info_arr[ $callback_data["app"] ];
    $app_info->commits_behind = json_decode($curl_data)->total_commits;
  }
  
  public function get_apps_info()
  {
     return $this->app_info_arr;
  }
  
}

$monitor = new YunohostAppMonitor();

?>
