<?php

use Formwork\Http\ResponseStatus;
use Formwork\Http\Utils\Header;
use Formwork\Utils\Str;

// Posts are the published children of the blog page
$posts = $page->children()->published();

// If the route has the param `{tagName}`
if ($router->params()->has('tagName')) {
    $posts = $posts->filterBy(
        'tags',             // Filter posts by tags...
        fn ($tags) => $tags
            ->map(fn ($tag) => Str::slug($tag)) // where the collection of their slugs...
            ->contains($router->params()->get('tagName'))   // contains the value of the `tagName` param.
    );
}

// Get the param `{paginationPage}` from the route and cast its value to integer
$paginationPage = (int) $router->params()->get('paginationPage', 1);

// Reverse the order and paginate the posts
$posts = $posts->reverse()->paginate($page->postsPerPage(), $paginationPage);

// Permanently redirect to the URI of the first page (without the `/page/{paginationPage}/`)
// if the `paginationPage` param is given and equals `1`
if ($router->params()->has('paginationPage') && $paginationPage === 1) {
    Header::redirect($posts->pagination()->firstPageUri(), ResponseStatus::MovedPermanently);
}

// If we have no posts or the `paginationPage` params refer to an nonexistent page
// go to the error page
if ($posts->isEmpty() || !$posts->pagination()->has($paginationPage)) {
    $site->setCurrentPage($site->errorPage());
}

return [
    'posts'      => $posts,
    'pagination' => $posts->pagination()
];
