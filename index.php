<?php
require_once('lib/bootstrap.php');
$url = _url();

$use_cache = false;
$cache_base = './cache';
$config_cache = "$cache_base/config";
if ($use_cache && file_exists($config_cache)){
    $config = Spyc::YAMLLoad($config_cache);
}else {
    $posts = _get_posts();
    $config = _get_config();
    $config['posts'] = $posts;
    $config['categories'] = _get_categories($posts);
    $cache_data = Spyc::YAMLDump($config);

    if ($use_cache) {
        if (false === file_exists($cache_base)) {
            mkdir($cache_base);
        }
        file_put_contents($config_cache, $cache_data);
    }
}

$post_name = _get_post_name($url);
if (null == $post_name) {
    $post_name = $config['index']; 
}
$post_filename = $config['posts'][$post_name]['filename'];
if (null == $post_filename) {
    die("Can't find post");
}

$post = _get_post($post_filename);
$content = $post['content'];
unset($post['content']);

$content = _render_syntax($content);
$content =  Markdown($content);

$layout = _get_layout($post['layout']);
$layout = _render_layout($config, $layout);

$post = _render_post($config, $post, $content, $layout);
echo $post;

function _get_categories($posts)
{
    $categories = array();
    foreach ($posts as $post) {
        if (false === isset($post['categories'])) {
            continue;
        }
        foreach ($post['categories'] as $category) {
            $categories[$category][] = $post;
        }
    }
    return $categories;
}

function _render_post($site_config, $page_config, $content, $layout)
{
    $mustache = new Mustache_Engine(array(
        'partials_loader' => new Mustache_Loader_ArrayLoader(array('content' => $content))
    ));
    $render_config = array(
        'site' => $site_config,
        'page' => $page_config
    );
    $layout = preg_replace('/{{\s+content\s+}}/', '{{> content }}', $layout);
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
    if (1 == $len) {
        return $layout['content'];
    }
    $mustache = new Mustache_Engine();
    for ($i = $len-1; $i > 0; $i--) {
        $template = $layouts[$i]['content'];
        $template = preg_replace('/{{\s+content\s+}}/', '{{{ content }}}', $template);
        $render_config = array(
            'site' => $site_config,
            'content' => $layouts[$i-1]['content']
        );
        $layout = $mustache->render($template, $render_config);
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
        return null;
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
                $post = array();
                $post['filename'] = $entry;
                $post['name'] = $post_name;
                $url = "/posts";
                $words = explode('-', $post_name);
                foreach ($words as $word) {
                    $url .= '/';
                    $url .= $word;
                }
                $post['url'] = $url;
                $post['title'] = $words[count($words) - 1];
                $tmp = _get_post($entry);
                $post = array_merge($post, $tmp);
                $posts[$post_name] = $post;
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
