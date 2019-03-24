<?php if ($pagination->hasPages()): ?>
<nav class="pagination">
    <?php if ($pagination->hasPreviousPage()): ?>
    <a class="pagination-previous" href="<?= $pagination->previousPageUri() ?>">&larr; Previous</a>
    <?php else: ?>
    <a class="pagination-previous disabled">&larr; Previous</a>
    <?php endif; ?>
    <?php if ($pagination->hasNextPage()): ?>
    <a class="pagination-next" href="<?= $pagination->nextPageUri() ?>">Next &rarr;</a>
    <?php else: ?>
    <a class="pagination-next disabled">Next &rarr;</a>
    <?php endif; ?>
</nav>
<?php endif; ?>
