<?php

require "config.php";
require "config-secret.php";
require "php-curl-multi/Curl/Multi.php";

ini_set("session.use_cookies",1);
session_start();

class AppInfo
{
  var $json;
  var $github_username;
  var $maintainer;
  var $github_repo;
  var $trunk_rev;
  var $diff_url;
  var $commits_behind = 0;
  var $diff_commits = array();
  var $is_mine = False;
  var $pull_requests = array();
  var $issues = array();
  var $tests = array();
  var $tests_attachments = array();
  
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
  
  public function set_diff_commits($diff_commits)
  {
    $diff = array();
    foreach($diff_commits->commits as $commit)
    {
      $commit_data = array(
        "author_login" => $commit->author->login,
        "author_url" => $commit->author->html_url,
        "author_gravatar_url" => $commit->author->avatar_url,
        "message" => $commit->commit->message,
        "date" => $commit->commit->author->date,
        "url" => $commit->html_url,
        "sha" => $commit->sha,
        "short_sha" => substr($commit->sha,0,7)
      );
      $diff[] = $commit_data;
    }
    $this->diff_commits = $diff;
  }

  public function set_pull_requests($pr_array)
  {
    foreach($pr_array as $pr)
    {
      $this->pull_requests[] = array(
        "number" => $pr->number,
        "html_url" => $pr->html_url,
        "title" => $pr->title,
        "created_at" => $pr->created_at,
        "reporter" => array(
          "login" => $pr->user->login,
          "avatar_url" => $pr->user->avatar_url
          )
        );
    }
  }
  
  public function set_issues($issues_array)
  {
    foreach($issues_array as $issue)
    {
      if ( !property_exists($issue, 'pull_request') )
      {
        $this->issues[] = array(
          "number" => $issue->number,
          "html_url" => $issue->html_url,
          "title" => $issue->title,
          "created_at" => $issue->created_at,
          "reporter" => array(
            "login" => $issue->user->login,
            "avatar_url" => $issue->user->avatar_url
            )
          );
      }
    }
  }

  public function set_maintainer($maintainer_array)
  {
    $this->maintainer = array(
      "login" => $maintainer_array->login,
      "avatar_url" => $maintainer_array->avatar_url
      );
  }

  public function set_tests($tests_array)
  {
    $this->tests = $tests_array;
  }

  public function set_tests_attachments($tests_attachments_array)
  {
    $this->tests_attachments = $tests_attachments_array;
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

  public function makeJenkinsRequestHandle($url)
  {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $headers[] = 'Accept: application/json';
    $headers[] = 'User-Agent: Yunohost App Status';
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
      file_get_contents( 'https://yunohost.org/official.json' )
    );
 
    foreach($applist as $app)
    {
      $this->app_info_arr[$app->manifest->id] = new AppInfo($app, $this->get_user());
     
      $url = $app->git->url;

      if ( preg_match("/https:\/\/github.com\/(.+)\/(.+)/", $app->git->url, $matches) != 1 )
      {
        // TODO handle error
        continue;
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
      
      $maintainer_apiurl =
        str_replace( array("{user}"),
                     array($github_username),
                     "https://api.github.com/users/{user}" );

      $this->curl_multi->addHandle(
        $this->makeApiRequestHandle($maintainer_apiurl),
        array($this, "store_maintainer"),
        array(
          "app" => $app->manifest->id
          )
        );
/*
 * Jenkins instance is offline...
 *
      $tests_url =
        str_replace( array("{appid}"),
                     array($app->manifest->id),
                     "https://moonlight.nohost.me/jenkins/job/yunotest/lastCompletedBuild/testReport/apps_tests/{appid}/api/json" );

      $this->curl_multi->addHandle(
        $this->makeJenkinsRequestHandle($tests_url),
        array($this, "store_tests"),
        array(
          "app" => $app->manifest->id
          )
        );

      $tests_attachments_url =
        str_replace( array("{appid}"),
                     array($app->manifest->id),
                     "https://moonlight.nohost.me/jenkins/job/yunotest/lastCompletedBuild/testReport/apps_tests/{appid}/" );

      $this->curl_multi->addHandle(
        $this->makeJenkinsRequestHandle($tests_attachments_url),
        array($this, "store_tests_attachments"),
        array(
          "app" => $app->manifest->id
          )
        );
 */
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
    $app_info->set_diff_commits(json_decode($curl_data));
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

  public function store_maintainer($curl_info, $curl_data, $callback_data)
  {
    $app_info = $this->app_info_arr[ $callback_data["app"] ];
    $app_info->set_maintainer(json_decode($curl_data));
  }

  public function store_tests($curl_info, $curl_data, $callback_data)
  {
    $app_info = $this->app_info_arr[ $callback_data["app"] ];
    $app_info->set_tests(json_decode($curl_data));
  }

  public function store_tests_attachments($curl_info, $curl_data, $callback_data)
  {
/*
    error_log(print_r($curl_info, True));
    error_log(print_r($curl_data, True));
    error_log(print_r($callback_data, True));
*/
    /*
     *  scrape the test result page to extract attachments, as they are not
     *  provided through the API
     * 
     *  http://www.bradino.com/php/screen-scraping/
     */
     
    $header_size = $curl_info["header_size"];
    $body = substr($curl_data, $header_size);
     
    $newlines = array("\t","\n","\r","\x20\x20","\0","\x0B");
    $content = str_replace($newlines, "", html_entity_decode($body));
    
    // extract the attachments table
    $start = strpos($content,'<table class="pane" id="attachments">');
    $end   = strpos($content,'</table>', $start) + 8;
    $table = substr($content,$start,$end-$start);
    
    //error_log(print_r($table));
    
    // extract all <a> elements
    $attachments = array();
    preg_match_all('|title=\"(.+)\"|U', $table, $match_results);
    
    $app_info = $this->app_info_arr[ $callback_data["app"] ];
    $app_info->set_tests_attachments($match_results[1]);
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
