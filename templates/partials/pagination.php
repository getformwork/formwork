<?php
            if ($pagination->hasPages()):
?>
            <nav class="pagination">
<?php
                if ($pagination->hasPreviousPage()):
?>
                <a class="pagination-previous" href="<?= $pagination->previousPageUri() ?>">&larr; Precedente</a>
<?php
                else:
?>
                <a class="pagination-previous disabled">&larr; Precedente</a>
<?php
                endif;
?>
<?php
                if ($pagination->hasNextPage()):
?>
                <a class="pagination-next" href="<?= $pagination->nextPageUri() ?>">Successiva &rarr;</a>
<?php
                else:
?>
                <a class="pagination-next disabled">Successiva &rarr;</a>
<?php
                endif;
?>
            </nav>
<?php
            endif;
?>
