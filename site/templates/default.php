<?= $this->layout('site') ?>
<main>
    <div class="container">
        <h1><?= $page->title() ?></h1>
        <?= $page->content() ?>
    </div>
</main>
