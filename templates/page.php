<?= $this->layout('site') ?>
<?= $this->insert('_cover-image') ?>
    <main>
        <div class="container">
            <?= $page->get('summary') . $page->content() ?>
        </div>
    </main>
