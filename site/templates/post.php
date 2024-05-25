<?= $this->layout('site') ?>
<main>
    <div class="container">
        <article>
            <h1 class="article-title"><a href="<?= $page->uri() ?>"><?= $this->escape($page->title()) ?></a></h1>
            <?= $this->insert('_tags', ['post' => $page, 'blog' => $page->parent()]) ?>
            <?= $page->summary() ?><?= $page->content() ?>
        </article>
    </div>
</main>