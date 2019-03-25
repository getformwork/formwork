<?= $this->layout('site') ?>
<?php if ($page->content()): ?>
<aside>
    <div class="container">
        <?= $page->content() ?>
    </div>
</aside>
<?php endif; ?>
<main>
    <div class="container">
    <?php foreach ($posts as $post): ?>
        <article>
            <h1 class="article-title"><a href="<?= $post->uri() ?>"><?= $post->title() ?></a></h1>
            <?= $this->insert('_tags', array('post' => $post, 'blog' => $page)) ?>
            <?php if ($post->summary()): ?>
                <?= $post->summary() ?>
            <?php else: ?>
                <?= $post->content() ?>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    </div>
</main>
<?= $this->insert('_pagination') ?>
