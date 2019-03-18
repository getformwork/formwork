<?= $this->layout('site') ?>
<?= $this->insert('_cover-image') ?>
    <main>
        <div class="container">
            <article>
                <h1 class="article-title"><a href="<?= $page->uri() ?>"><?= $page->title() ?></a></h1>
                <?= $page->get('summary') . $page->content() ?>
            </article>
        </div>
    </main>
