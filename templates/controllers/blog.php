<?php

    $posts = $page->children()->filter('published')->reverse()->paginate($page->get('posts-per-page', 5));

    return array(
        'posts'      => $posts,
        'pagination' => $posts->pagination()
    );
