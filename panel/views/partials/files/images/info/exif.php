<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="page-file-info-entry-title"><?= $this->translate('panel.pages.file.info.image.exif.creationDateAndTime') ?>:</div>
    <?= $exif->dateTimeOriginal() ?? '-' ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="page-file-info-entry-title"><?= $this->translate('panel.pages.file.info.image.exif.camera') ?>:</div>
    <?= $exif->makeAndModel() ?? '–' ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="page-file-info-entry-title"><?= $this->translate('panel.pages.file.info.image.exif.lensModel') ?>:</div>
    <?= $exif->lensModel() ?? '–'  ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="page-file-info-entry-title"><?= $this->translate('panel.pages.file.info.image.exif.focalLength') ?>:</div>
    <?= $exif->focalLength() ?? '–'  ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="page-file-info-entry-title"><?= $this->translate('panel.pages.file.info.image.exif.exposureTime') ?>:</div>
    <?= $exif->exposureTime() ?? '–'  ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="page-file-info-entry-title"><?= $this->translate('panel.pages.file.info.image.exif.aperture') ?>:</div>
    <?= $exif->aperture() ?? '–'  ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="page-file-info-entry-title"><?= $this->translate('panel.pages.file.info.image.exif.sensitivity') ?>:</div>
    <?= $exif->photographicSensitivity() ?? '–'  ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="page-file-info-entry-title"><?= $this->translate('panel.pages.file.info.image.exif.exposureCompensation') ?>:</div>
    <?= $exif->exposureCompensation() ?? '–'  ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="page-file-info-entry-title"><?= $this->translate('panel.pages.file.info.image.exif.exposureProgram') ?>:</div>
    <?= $exif->exposureProgram() ?? '–'  ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="page-file-info-entry-title"><?= $this->translate('panel.pages.file.info.image.exif.whiteBalance') ?>:</div>
    <?php if ($exif->hasAutoWhiteBalance() === true) : ?>AWB<?php else : ?>–<?php endif ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="page-file-info-entry-title"><?= $this->translate('panel.pages.file.info.image.exif.flash') ?>:</div>
    <?php if ($exif->hasFlashFired() !== null) : ?> <?= $exif->hasFlashFired() ? $this->icon('camera-flash') : $this->icon('camera-no-flash') ?><?php else : ?>–<?php endif ?>
</div>
<div class="col-sm-1-2 col-md-1-4 mb-4">
    <div class="page-file-info-entry-title"><?= $this->translate('panel.pages.file.info.image.exif.meteringMode') ?>:</div>
    <?php if ($exif->meteringMode() !== null) : ?><?= $this->icon('camera-metering-' . $exif->meteringMode()) ?><?php else : ?>–<?php endif ?>
</div>