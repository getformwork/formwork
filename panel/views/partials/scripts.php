<?php foreach ($this->assets()->scripts() as $script): ?>
    <script <?= $this->attr(['src' => $script->uri(includeVersion: true), 'integrity' => $script->integrityHash(), 'type' => $script->getMeta('module') ? 'module' : null]) ?>></script>
<?php endforeach ?>

<?php if ($this->assets()->has('js/app.min.js')): ?>
    <script type="module">
        import { app } from "<?= $this->assets()->get('js/app.min.js')->uri(includeVersion: true) ?>";
        app.load(<?= Formwork\Parsers\Json::encode($panel->getAppConfig()) ?>);
    </script>
<?php endif ?>
