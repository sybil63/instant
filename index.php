<pre>
<?php
require_once('lib/bootstrap.php');
$url = _url();
echo $url;

$posts = _get_posts();
var_dump($posts);

$config = _get_config();
$config['posts'] = $posts;
var_dump($config);

echo Spyc::YAMLDump($config);

function _url()
{
    if (!empty($_SERVER['PATH_INFO'])) {
        return $_SERVER['PATH_INFO'];
    }else if (!empty($_SERVER['REQUEST_URI'])) {
        return $_SERVER['REQUEST_URI'];
    }elseif (isset($_SERVER['PHP_SELF']) && isset($_SERVER['SCRIPT_NAME'])) {
            $uri = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['PHP_SELF']);
    } elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $uri = $_SERVER['HTTP_X_REWRITE_URL'];
    } elseif ($var = env('argv')) {
            $uri = $var[0];
    }
    return $uri;
}


function _get_config()
{
    $data = Spyc::YAMLLoad("config.yaml");
    return $data;
}

function _get_posts()
{
    $posts = array();
    if ($handle = opendir('posts')) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                $file = explode(".", $entry);
                $posts[$file[0]] = $entry;
            }
        }
        closedir($handle);
    }
    return $posts;
}
