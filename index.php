<head>
   <meta http-equiv="content-type" content="text/html; charset=utf-8" />

   <!-- syntax highlighting CSS -->
   <link rel="stylesheet" href="/css/monokai.css" type="text/css" />

   <!-- Homepage CSS -->
   <link rel="stylesheet" href="/css/screen.css" type="text/css" media="screen, projection" />
</head>

<body>
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

$content = file_get_contents("posts/test.md");
//$data = Spyc::YAMLLoad("posts/test.md");
$data = Spyc::YAMLLoadString($content);
var_dump($data);

$post = _get_post('test.md');
var_dump($post);

echo Markdown($post['content']);

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

function _get_post($filename)
{
    $configStr = "";
    $content = "";
    $handle = @fopen("posts/$filename", "r");
    if ($handle) {
        $cnt = 0;
        while (($buffer = fgets($handle, 4096)) !== false) {
            if (false !== strpos($buffer, '---')){
                ++$cnt;
                if ($cnt > 1)
                    break;
            }
            $configStr .= $buffer;
        }

        while (($buffer = fgets($handle, 4096)) !== false) {
            $content .= $buffer;
        }

        if (!feof($handle)) {
            echo "Error: unexpected fgets() fail\n";
        }
        fclose($handle);
    }

    $config = Spyc::YAMLLoadString($configStr);
    $config['content'] = $content;
    return $config;
}
