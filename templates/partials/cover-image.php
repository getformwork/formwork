<?php if ($page->images()->has($page->get('image'))): ?>
    <div class="cover-image" style="background-image:url(<?= $page->images()->get($page->image())->uri() ?>);"></div>
<?php endif; ?>
