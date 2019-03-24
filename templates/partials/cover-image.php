<?php if ($page->images()->has($page->get('image'))): ?>
    <div class="cover-image" style="background-image:url(<?= $page->uri($page->image()) ?>);"></div>
<?php endif; ?>
