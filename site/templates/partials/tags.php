<?php if ($post->has('tags')): ?>
<div class="tags">
    <?php foreach ($post->tags() as $tag): ?>
        <a class="tag" rel="tag" href="<?= $blog->uri('/tag/' . $this->slug($tag) . '/') ?>"><?= $tag ?></a>
    <?php endforeach ?>
</div>
<?php endif ?>
