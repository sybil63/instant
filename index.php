<?php
require_once('lib/bootstrap.php');
$url = _url();
$post_name = _get_post_name($url);
//echo $url;
//echo $post_name;

$posts = _get_posts();
$config = _get_config();
$config['posts'] = $posts;
//var_dump($posts);
//var_dump($config);

//$post_name = 'test.md';
$post_filename = $posts[$post_name]['filename'];
$post = _get_post($post_filename);
//var_dump($post);

$content = $post['content'];
unset($post['content']);
//var_dump($post);

$content = _render_syntax($content);
$content =  Markdown($content);
//echo $content;

$layout = _get_layout($post['layout']);
//echo $layout['content'];
$layout = _render_layout($config, $layout);

$post = _render_post($config, $post, $content, $layout);
echo $post;

function _render_post($site_config, $page_config, $content, $layout)
{
    $mustache = new Mustache_Engine();
    $render_config = array(
        'site' => $site_config,
        'page' => $page_config,
        'content' => $content
    );
    $layout = preg_replace('/{{\s+content\s+}}/', '{{{ content }}}', $layout);
    $post = $mustache->render($layout, $render_config);
    return $post;
}

function _render_layout($site_config, $layout)
{
    $layouts = array();
    while (isset($layout['layout'])) {
        $layouts[] = $layout;
        $layout = _get_layout($layout['layout']);
    }
    $layouts[] = $layout;
    $len = count($layouts);
    $mustache = new Mustache_Engine();
    for ($i = $len-1; $i > 0; $i--) {
        $template = $layouts[$i]['content'];
        $template = preg_replace('/{{\s+content\s+}}/', '{{{ content }}}', $template);
        $render_config = array(
            'site' => $site_config,
            'content' => $layouts[$i-1]['content']
        );
        $layout = $mustache->render($template, $render_config);
        $layouts[$i-1]['content'] = $layout;
    }
    return $layout;
}

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
    if ($len > 2) {
        for ($i = 2; $i < $len; $i++) {
            if ($i > 2)
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
                $post_name = $file[0];
                $posts[$post_name]['filename'] = $entry;
                $url = "/posts";
                $words = explode('-', $filename);
                foreach ($words as $word) {
                    $url .= '/';
                    $url .= $word;
                }
                $posts[$post_name]['url'] = $url;
                $posts[$post_name]['title'] = $words[count($words) - 1];
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
    $post_folder = 'posts';
    $handle = @fopen("$post_folder/$filename", "r");
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

function _get_layout($layout_name)
{
    $filename = $layout_name . '.html';
    $layout_folder = 'layouts';
    $content = '';
    $configStr = '';
    $handle = @fopen("$layout_folder/$filename", "r");
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

    if ($content == "") {
        $content = $configStr;
        $configStr = "";
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
            $ret .= "\n";
            $ret .= '<div class="highlight"><p>';
            $ret .= hyperlight($str, $lang, array("code"));
            $ret .= '</p></div>';
            $ret .= "\n";
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
