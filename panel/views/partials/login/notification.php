<?php if ($notification = $panel->notifications()) : ?>
    <?php if (($type = $notification[0]['type']) === 'error') : ?>
        <div class="login-modal-danger"><?= $this->icon($notification[0]['icon']) ?> <?= $notification[0]['text'] ?></div>
    <?php else : ?>
        <div class="login-modal-<?= $type ?>"><?= $this->icon($notification[0]['icon']) ?> <?= $notification[0]['text'] ?></div>
    <?php endif ?>
<?php endif ?>