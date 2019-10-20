<?= $this->layout('site') ?>
<main>
    <div class="container">
        <article>
            <h1 class="article-title"><a href="<?= $page->uri() ?>"><?= $page->title() ?></a></h1>
            <?= $this->insert('_tags', array('post' => $page, 'blog' => $page->parent())) ?>
            <?= $page->content() ?>
        </article>
    </div>
</main>
