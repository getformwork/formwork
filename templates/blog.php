<?= $this->insert('_header') ?>
<?= $this->insert('_cover-image') ?>
<?php
    if ($page->content()):
?>
                <aside>
                    <div class="container">
                        <?= $page->content() ?>
                    </div>
                </aside>
<?php
    endif;
?>
                <main>
                    <div class="container">
<?php
    foreach ($posts as $post):
?>
                    <article>
                        <h1 class="article-title"><a href="<?= $post->uri() ?>"><?= $post->title() ?></a></h1>
<?php
        if ($post->get('summary')):
?>
                        <p><?= $post->get('summary') ?></p>
<?php
        else:
?>
                        <?= $post->content(); ?>
<?php
        endif;
?>
                    </article>
<?php
    endforeach;
?>
<?= $this->insert('_pagination') ?>
                </div>
            </main>
<?= $this->insert('_footer') ?>
