<head>
   <meta http-equiv="content-type" content="text/html; charset=utf-8" />

   <!-- syntax highlighting CSS -->
   <link rel="stylesheet" href="/css/vibrant.css" type="text/css" />

   <!-- Homepage CSS -->
   <link rel="stylesheet" href="/css/screen.css" type="text/css" media="screen, projection" />
</head>

<body>
    <div class="site">
<?php
require_once('lib/bootstrap.php');
$url = _url();
echo $url;

$post_name = _get_post_name($url);
echo $post_name;

$posts = _get_posts();
var_dump($posts);

$config = _get_config();
$config['posts'] = $posts;
var_dump($config);

$post = _get_post('test.md');
//var_dump($post);

$content =  Markdown($post['content']);
$content = _render_syntax($content);
echo $content;

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

function _get_post_name($url)
{
    $tmp = explode("/", $url);
    $len = count($tmp);
    $name = "";
    if ($len > 1) {
        for ($i = 1; $i < $len; $i++) {
            if ($i > 1)
                $name .= '-';
            $name .= $tmp[$i];
        }
    } else {
        $name = 'index';
    }
    return $name;
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

function _render_syntax($content)
{
    $lines = explode("\n", $content);
    $ret = "";
    $lang = false;
    $str = "";
    foreach ($lines as $line) {
        if (preg_match('/{%\s+highlight\s+(\w+)\s+%}/', $line, $mathces)) {
            $lang = $mathces[1];
            $str = "";
        }else if (preg_match('/{%\s+endhighlight\s+%}/', $line, $mathces)) {
            $ret .= '<div class="highlight"><p><pre>';
            $ret .= hyperlight($str, $lang, $tag = array("code"));
            $ret .= '</pre></p></div>';
            $lang = false;
        } else if (false === $lang) {
            $ret .= $line;
            $ret .= "\n";
        } else {
            $str .= $line;
            $str .= "\n";
        }
    }
    return $ret;
}
