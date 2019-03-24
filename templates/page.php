<?= $this->layout('site') ?>
<?= $this->insert('_cover-image') ?>
    <main>
        <div class="container">
            <?= $page->content() ?>
        </div>
    </main>
