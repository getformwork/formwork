<?php $this->layout('@panel.panel') ?>

<?php $this->modals()->add('clearLog') ?>

<div class="header">
    <div class="header-icon"><?= $this->icon('toolbox') ?></div>
    <div class="header-title"><?= $this->translate('panel.tools.tools') ?></div>
</div>

<?php $this->insert('@panel._navigation.tabs', ['items' => $panel->navigation()->get('tools')->children(), 'current' => 'logs']) ?>

<?php
$levelBadges = [
    'emergency' => 'badge-red',
    'alert'     => 'badge-red',
    'critical'  => 'badge-red',
    'error'     => 'badge-red',
    'warning'   => 'badge-amber',
    'notice'    => 'badge-green',
    'info'      => 'badge-blue',
    'debug'     => 'badge-purple',
];
?>

<div data-view="logs">
    <section class="section">
        <div class="flex flex-wrap gap-x-4">
            <div class="flex-grow-1">
                <div class="form-input-wrap">
                    <span class="form-input-icon"><?= $this->icon('search') ?></span>
                    <input class="form-input logs-search" id="logs-search" type="search" placeholder="<?= $this->translate('panel.tools.logs.search') ?>" aria-label="<?= $this->translate('panel.tools.logs.search') ?>">
                </div>
            </div>
            <form method="get" action="<?= $panel->uri('/tools/logs/') ?>" data-form="logs-form" data-auto-submit="true" class="flex flex-grow-1 gap-x-4">
                <div class="flex-grow-1">
                    <select class="form-select" id="logs-channel" name="channel">
                        <?php foreach ($channels as $name) : ?>
                            <option <?= $this->attr(['value' => $name, 'selected' => $name === $channel]) ?>><?= $this->escape(ucfirst($name)) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div>
                    <select class="form-select" id="logs-level" name="level">
                        <option value=""><?= $this->translate('panel.tools.logs.allLevels') ?></option>
                        <?php foreach ($levels as $value) : ?>
                            <option <?= $this->attr(['value' => $value, 'selected' => $value === $level]) ?>><?= $this->escape(ucfirst($value)) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div>
                    <select class="form-select" id="logs-limit" name="limit">
                        <?php foreach ($limits as $value) : ?>
                            <option <?= $this->attr(['value' => $value, 'selected' => $value === $limit]) ?>><?= $value ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="button button-secondary"><?= $this->icon('arrows-rotate-clockwise') ?> <?= $this->translate('panel.tools.logs.refresh') ?></button>
                </div>
                <?php if ($panel->user()->permissions()->has('panel.tools.logs.clear')) : ?>
                    <div>
                        <button type="button" class="button button-danger" data-modal="clearLogModal" data-modal-action="<?= $panel->uri('/tools/logs/' . rawurlencode($channel) . '/clear/') ?>"><?= $this->icon('trash') ?> <?= $this->translate('panel.tools.logs.clear') ?></button>
                    </div>
                <?php endif ?>
            </form>
        </div>
        <?php if ($entries === []) : ?>
            <p class="text-size-sm"><?= $this->translate('panel.tools.logs.empty') ?></p>
        <?php else : ?>
            <table class="table table-bordered table-hoverable text-size-sm">
                <thead>
                    <tr>
                        <th class="table-header truncate" style="width: 20%"><?= $this->translate('panel.tools.logs.date') ?></th>
                        <th class="table-header truncate" style="width: 10%"><?= $this->translate('panel.tools.logs.level') ?></th>
                        <th class="table-header truncate" style="width: 70%"><?= $this->translate('panel.tools.logs.message') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry) : ?>
                        <tr>
                            <td class="table-cell truncate"><?= $this->datetime($entry['timestamp']) ?></td>
                            <td class="table-cell truncate"><span class="badge text-uppercase truncate <?= $levelBadges[$entry['level']] ?? '' ?>" style="max-width: 100%; vertical-align: middle"><?= $this->escape(ucfirst($entry['level'])) ?></span></td>
                            <td class="table-cell log-message"><?= $this->escape($entry['message']) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </section>
</div>