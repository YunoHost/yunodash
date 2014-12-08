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
  var $is_mine = False;
  var $pull_requests = array();
  var $issues = array();
  
  public function __construct($appjson, $logged_user)
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
    
    $this->is_mine = ($this->github_username == $logged_user);
  }
  
  public function set_pull_requests($pr_array)
  {
    foreach($pr_array as $pr)
    {
      $this->pull_requests[] = array(
        "number" => $pr->number,
        "html_url" => $pr->html_url,
        "title" => $pr->title,
        "created_at" => $pr->created_at
        );
    }
  }
  
  public function set_issues($issues_array)
  {
    foreach($issues_array as $issue)
    {
      if ($issue->pull_request == NULL)
      {
        $this->issues[] = array(
          "number" => $issue->number,
          "html_url" => $issue->html_url,
          "title" => $issue->title,
          "created_at" => $issue->created_at
          );
      }
    }
  }

}


class YunohostAppMonitor
{
  private $curl_multi = NULL;
  private $app_info_arr = array();
  private $loggued_user_data = NULL;

  public function __construct()
  {
    $this->curl_multi = new Curl_Multi();
    
    if ( $this->is_loggued() )
    {
      $this->loggued_user_data = $this->load_user_data();
    }
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
  
  public function load_user_data()
  {
    $ch = $this->makeApiRequestHandle('https://api.github.com/user');
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response);
  }
  
  public function get_user()
  {
    return $this->loggued_user_data->login;
  }
  
  public function loadAppData()
  {
    $applist = json_decode(
      file_get_contents( 'https://app.yunohost.org/list.json' )
    );
    
    foreach($applist as $app)
    {
      $this->app_info_arr[$app->manifest->id] = new AppInfo($app, $this->get_user());
     
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

      $pull_request_apiurl =
        str_replace( array("{user}", "{repo}"),
                     array($github_username, $github_repo),
                     "https://api.github.com/repos/{user}/{repo}/pulls?state=open" );

      $this->curl_multi->addHandle(
        $this->makeApiRequestHandle($pull_request_apiurl),
        array($this, "store_pull_requests"),
        array(
          "app" => $app->manifest->id
          )
        );

      $issues_request_apiurl =
        str_replace( array("{user}", "{repo}"),
                     array($github_username, $github_repo),
                     "https://api.github.com/repos/{user}/{repo}/issues?open" );

      $this->curl_multi->addHandle(
        $this->makeApiRequestHandle($issues_request_apiurl),
        array($this, "store_issues"),
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
  
  public function store_pull_requests($curl_info, $curl_data, $callback_data)
  {
    $app_info = $this->app_info_arr[ $callback_data["app"] ];
    $app_info->set_pull_requests(json_decode($curl_data));
  }
  
  public function store_issues($curl_info, $curl_data, $callback_data)
  {
    $app_info = $this->app_info_arr[ $callback_data["app"] ];
    $app_info->set_issues(json_decode($curl_data));
  }

  public function get_apps_info()
  {
     return $this->app_info_arr;
  }
  
  public function get_apps_array()
  {
    // turn associative array to regular list
    
    $a = array();
    foreach($this->app_info_arr as $appid => $appinfo)
    {
      $a[] = $appinfo;
    } 
    return $a;
  }
  
}

$monitor = new YunohostAppMonitor();

?>
