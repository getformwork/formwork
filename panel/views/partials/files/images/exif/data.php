<table class="table table-bordered table-striped table-hoverable text-size-sm">
    <?php foreach ($exif->parsedTags() as $key => $value) : ?>
        <tr>
            <td class="table-cell truncate page-file-info-entry-title" style="width: 25%;"><?= $key ?></td>
            <td class="table-cell"><?= $this->escape(is_array($value) ? implode(', ', $value) : (string) $value) ?></td>
        </tr>
    <?php endforeach ?>
</table>