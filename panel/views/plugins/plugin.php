<?php $this->layout('@panel.panel') ?>
<form method="post" enctype="multipart/form-data" data-form="plugin-form">
    <div class="header">
        <div class="header-icon"><?= $this->icon('puzzle-piece') ?></div>
        <div class="header-title"><?= $this->translate('plugin.plugin') ?></div>
        <div>
            <a class="<?= $this->classes(['button', 'button-link', 'show-from-md', 'disabled' => !$previousPlugin]) ?>" role="button" <?php if ($previousPlugin) : ?>href="<?= $panel->uri("/plugins/{$previousPlugin->id()}/") ?>" <?php endif ?> title="<?= $this->translate('panel.plugins.previousPlugin') ?>" aria-label="<?= $this->translate('panel.plugins.previousPlugin') ?>"><?= $this->icon('chevron-left') ?></a>
            <a class="<?= $this->classes(['button', 'button-link', 'show-from-md', 'disabled' => !$nextPlugin]) ?>" role="button" <?php if ($nextPlugin) : ?>href="<?= $panel->uri("/plugins/{$nextPlugin->id()}/") ?>" <?php endif ?> title="<?= $this->translate('panel.plugins.nextPlugin') ?>" aria-label="<?= $this->translate('panel.plugins.nextPlugin') ?>"><?= $this->icon('chevron-right') ?></a>
            <button type="submit" class="button button-accent" data-command="save"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.save') ?></button>
        </div>
    </div>

    <section class="section">
        <div>
            <span class="h3"><?= $this->escape($plugin->manifest()->title() ?? $plugin->name()) ?></span>
            <?php if ($plugin->manifest()->version()): ?><span><?= $this->escape($plugin->manifest()->version()) ?></span><?php endif ?>
        </div>
        <?php if ($plugin->manifest()->description()) : ?>
            <p><?= $this->escape($plugin->manifest()->description()) ?></p>
        <?php endif ?>
        <?php if ($plugin->manifest()->author()) : ?>
            <div class="text-size-sm"><strong><?= $this->translate('plugin.author') ?>:</strong> <?= $this->escape($plugin->manifest()->author()) ?></div>
        <?php endif ?>
        <?php if ($plugin->manifest()->homepage()) : ?>
            <div class="text-size-sm"><strong><?= $this->translate('plugin.homepage') ?>:</strong> <a target="_blank" rel="noreferrer noopener" href="<?= $this->escapeAttr($plugin->manifest()->homepage()) ?>"><?= $this->escape($plugin->manifest()->homepage()) ?></a></div>
        <?php endif ?>
        <?php if ($plugin->manifest()->license()) : ?>
            <div class="text-size-sm"><strong><?= $this->translate('plugin.license') ?>:</strong> <?= $this->escape($plugin->manifest()->license()) ?></div>
        <?php endif ?>
        <fieldset class="form-togglegroup mt-8 mb-0">
            <label class="form-label">
                <input class="form-input plugin-status-toggle" type="radio" name="enabled" value="1" <?= $plugin->isEnabled() ? 'checked' : '' ?> data-form-ignore="true" data-action="<?= $panel->uri("/plugins/{$plugin->id()}/enable/") ?>"><span><?= $this->translate('plugin.status.enabled') ?></span>
            </label>
            <label class="form-label">
                <input class="form-input plugin-status-toggle" type="radio" name="enabled" value="0" <?= !$plugin->isEnabled() ? 'checked' : '' ?> data-form-ignore="true" data-action="<?= $panel->uri("/plugins/{$plugin->id()}/disable/") ?>"><span><?= $this->translate('plugin.status.disabled') ?></span>
            </label>
        </fieldset>
    </section>

    <?php if (!$fields->isEmpty()): ?>
        <?php $this->insert('@panel.fields', ['fields' => $fields]) ?>
    <?php endif ?>
    <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
</form>