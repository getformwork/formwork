<?php if ($post->has('taxonomy.tag')) : ?>
    <div class="tags">
        <?php foreach ($post->get('taxonomy.tag') as $tag) : ?>
            <a class="tag" rel="tag" href="<?= $blog->uri("/tag/{$this->slug($tag)}/") ?>"><?= $this->escape($tag) ?></a>
        <?php endforeach ?>
    </div>
<?php endif ?>