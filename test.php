<?php
    require_once("lib/bootstrap.php");
    $m = new Mustache_Engine();
    $template = "Hello, {{planet}}!";
    echo $m -> render($template, array('planet'=>"world"));

    $data = Spyc::YAMLLoad("posts/test.md");
    var_dump($data);

    $text = file_get_contents("posts/test.md");
    $html = Markdown($text);
    echo $html;
