<?php
$GLOBALS['SRC_LIB_DIR'] = dirname(__FILE__);

//Init mustache
require_once(dirname(__FILE__) . '/Mustache/Autoloader.php');
Mustache_Autoloader::register();

//Init Spyc, tools for handle yaml
require_once(dirname(__FILE__) . '/Spyc/Spyc.php');

require_once(dirname(__FILE__) . '/php-markdown/markdown.php');
require_once(dirname(__FILE__) . '/tools.php');
require_once(dirname(__FILE__) . '/Hyperlight/hyperlight.php');
