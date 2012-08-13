---
layout: default
title: Archives
---

<div id="home">
  <h1>Blog Posts</h1>
  <ul class="posts">
    {{#site.categories.test}}
      <li><span>{{date}}</span> &raquo; <a href="{{url}}">{{title}}</a></li>
    {{/site.categories.test}}
  </ul>
</div>
