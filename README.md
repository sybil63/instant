# What's instant
* 这个项目是我在公司内为了写readme而弄出来的小玩具
* 一个用markdown写blog的应用
* php版本的jekyll
* 由于写readme实在太恶心了，这个小东西可以比较方便的使用公司内AppEngine的IDE写readme，或者用vim。
实在受不了公司的wiki编辑器，TMD是裸textarea，看得眼花啊有木有。

# How to use
## 使用的开源代码
* 使用了mustache作为模板引擎，所以像jekyll的liquid一样的一些高级功能不支持，比如自定义变量，filter。但是以我使用jekyll的经验看来，这些高级功能用到的地方也不多。
* 为了达到渲染代码的作用，使用了hyperlight。自定义了一个highlight标签

        ｛％ highlight lang ％｝
            ...code...
        ｛％ endhighlight ％｝

* 使用了spyc来解析yaml，只是为了方便。
* 使用了一个php-markdown来渲染markdown

## 目录说明
 * lib: 放置了一些使用到的外置库
 * layouts: 放置模板，跟jekyll一样，也支持多层模板。目前默认模板都以.html结尾
 * posts: 放置markdown文档,访问post的url是按照post的名称声称，比如

        2012-08-13-test.md => /posts/2012/08/13/test

  post的头里也可以定义一些变量，使用page.XX来访问。
  目前对本应用有意义的只有layout变量和categories变量

        page.date
        page.title
        ...

 * config.yaml: 定义一些常用变量，模板里可以使用site.XX来访问，目前唯一特殊的变量是categories，这是一个按照category排列的post倒排索引
    
        site.title
        site.author
        site.categories.CATEGORY
        ...

 to be continue...

#Contact
* Email: xichen0603@gmail.com

