<?= $this->layout('site') ?>
<main>
    <div class="container">
        <h1><?= $this->escape($page->title()) ?></h1>
        <?= $page->content() ?>
    </div>
</main>