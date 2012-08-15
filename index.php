<?php
require_once('lib/bootstrap.php');

$instant = new Instant();
$post = $instant->render();
echo $post;

/**
 * A simple class to handle request
 * get base url => get post file => get layout => render page
 *
 * TODO add cache to make it faster
 * @author Sybil
 **/
class Instant
{
    var $url;
    var $use_cache;
    var $cache_base;
    var $config;
    var $error_page;
    var $post_folder;
    var $layout_folder;

    function __construct()
    {
        $this->config = $this->_get_config();
        $this->use_cache = $this->_get_setting('use_cache', true);
        $this->cache_base = $this->_get_setting('cache_base', './cache');
        $this->error_page = $this->_get_setting('error_page', '404');
        $this->post_folder = $this->_get_setting('post_folder', 'posts');
        $this->layout_folder = $this->_get_setting('layout_folder', 'layouts');

        $this->url = $this->_url();
    }

    /**
     * Render page
     *
     * @return string
     **/
    public function render()
    {
        $posts = $this->_get_posts();
        $this->config['posts'] = $posts;
        $this->config['categories'] = $this->_get_categories($posts);

        $post_name = $this->_get_post_name($this->url);
        if (null == $post_name) {
            $post_name = $this->config['index']; 
        }
        $post_filename = $this->config['posts'][$post_name]['filename'];
        if (null == $post_filename) {
            $post_filename = $this->config['posts'][$this->error_page]['filename'];
        }
        $post = $this->_get_post($post_filename);
        $content = $post['content'];
        unset($post['content']);

        $layout = $this->_get_layout($post['layout']);
        $layout = $this->_render_layout($this->config, $layout);

        $content = $this->_render_syntax($content);
        $content =  Markdown($content);
        $post = $this-> _render_post($this->config, $post, $content, $layout);

        return $post;
    }

    protected function _get_setting($name, $default = null)
    {
        if (isset($this->config[$name])) 
            return $this->config[$name];
        return $default;
    }

    /**
     * Get posts's categories reverse link
     *
     * @return array
     **/
    protected function _get_categories($posts)
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

    /**
     * render page with layout and config
     *
     * @return string
     **/
    protected function _render_post($site_config, $page_config, $content, $layout)
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

    /**
     * Render page layout recursively
     *
     * @return string
     **/
    protected function _render_layout($site_config, $layout)
    {
        $layouts = array();
        while (isset($layout['layout'])) {
            $layouts[] = $layout;
            $layout = $this->_get_layout($layout['layout']);
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

    /**
     * Get the page's url
     *
     * @return string
     **/
    protected function _url()
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

    /**
     * get the post's name by url
     * '/blog/2012/03/08/test' => '2012-03-08-test'
     *
     * @return string
     **/
    protected function _get_post_name($url)
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

    /**
     * Get the site's config
     *
     * @return array
     **/
    protected function _get_config()
    {
        $config = Spyc::YAMLLoad("config.yaml");
        return $config;
    }

    /**
     * Get all the posts info in reverse link
     *
     * @return array
     **/
    protected function _get_posts()
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
                    $tmp = $this->_get_post($entry);
                    $post = array_merge($post, $tmp);
                    $posts[$post_name] = $post;
                }
            }
            closedir($handle);
        }
        return $posts;
    }

    /**
     * Get a post info by filename
     *
     * @return array
     **/
    protected function _get_post($filename)
    {
        $configStr = "";
        $content = "";
        $handle = @fopen("$this->post_folder/$filename", "r");
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

    /**
     * Get a layout info by layout name
     * a layout's filename is layout_name.html
     *
     * @return array
     **/
    protected function _get_layout($layout_name)
    {
        $filename = $layout_name . '.html';
        $content = '';
        $configStr = '';
        $handle = @fopen("$this->layout_folder/$filename", "r");
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

    /**
     * Render highlight code in page
     * a highlight code sniper is like this
     * {% highlight lang %}
     *  ....code ....
     * {% endhighlight %}
     *
     * @return string
     **/
    protected function _render_syntax($content)
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
}
