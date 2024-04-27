<?= $this->layout('site') ?>
<?php if ($page->content()) : ?>
    <aside>
        <div class="container">
            <?= $page->content() ?>
        </div>
    </aside>
<?php endif ?>
<main>
    <div class="container">
        <?php foreach ($posts as $post) : ?>
            <article>
                <h1 class="article-title"><a href="<?= $post->uri() ?>"><?= $post->title() ?></a></h1>
                <?php if (!$post->publishDate()->isEmpty()) : ?><div style="font-size: 0.875rem; color: #aaa;"><?= $post->publishDate()->toDuration() ?></div><?php endif ?>
                <?= $this->insert('_tags', ['post' => $post, 'blog' => $page]) ?>
                <?php if (!$post->summary()->isEmpty()) : ?>
                    <?= $post->summary() ?>
                    <a class="read-more" href="<?= $post->uri() ?>" rel="bookmark">Read more &rarr;</a>
                <?php else : ?>
                    <?= $post->content() ?>
                <?php endif ?>
            </article>
        <?php endforeach ?>
    </div>
</main>
<?= $this->insert('_pagination') ?>