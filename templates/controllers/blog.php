<?php

    $posts = $page->children()->filter('published');

    if ($params->has('tagName')) {
        $posts = $posts->filter('tags', $params->get('tagName'), 'Formwork\Utils\Str::slug');
    }

    $posts = $posts->reverse()->paginate($page->get('posts-per-page', 5));

    if ($posts->isEmpty()) {
        $site->errorPage(true);
    }

    return [
        'posts'      => $posts,
        'pagination' => $posts->pagination()
    ];
