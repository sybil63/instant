---
published: true
layout: post
title: "Hello World"
date: 2012-03-09 00:33
comments: false
categories: ['test']
---

## 测试img

![Sakula](http://sybil-blog.b0.upaiyun.com/sakula.jpg!blog4dc)

testetste

## test highlight 
{% highlight php %}
function preg_strip($expression) {
    $regex = '/^(.)(.*)\\\\1([imsxeADSUXJu]*)$/s';
    if (preg_match($regex, $expression, $matches) !== 1)
        return false;
    $delim = $matches[1];
    $sub_expr = $matches[2];
    if ($delim !== '/') {
        // Replace occurrences by the escaped delimiter by its unescaped
        // version and escape new delimiter.
        
        $sub_expr = str_replace("\\\\$delim", $delim, $sub_expr);
        $sub_expr = str_replace('/', '\\\\/', $sub_expr);
    }
    
    $modifiers = $matches[3] === '\' ?
        array() : str_split(trim($matches[3]));
    return array($sub_expr, $modifiers);
}
{% endhighlight %}

##test highlight line on
<div class="highlight"><pre><code class="javascript"><span class="lineno">1</span> <span class="kd">var</span> <span class="nx">arr1</span> <span class="o">=</span> <span class="k">new</span> <span class="nb">Array</span><span class="p">(</span><span class="nx">arrayLength</span><span class="p">);</span>
<span class="lineno">2</span> <span class="kd">var</span> <span class="nx">arr2</span> <span class="o">=</span> <span class="k">new</span> <span class="nb">Array</span><span class="p">(</span><span class="nx">element0</span><span class="p">,</span> <span class="nx">element1</span><span class="p">,</span> <span class="p">...,</span> <span class="nx">elementN</span><span class="p">);</span>
</code></pre>
</div>
