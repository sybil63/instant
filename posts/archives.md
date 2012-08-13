---
layout: default
title: Sybil63
---

<div id="home">
  <h1>Blog Posts</h1>
  <ul class="posts">
    {{#site.posts}}
      <li><span>{{date}}</span> &raquo; <a href="{{url}}">{{title}}</a></li>
    {{/site.posts}}
  </ul>
</div>
