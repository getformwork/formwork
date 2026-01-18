<!DOCTYPE html>
<html lang="<?= $site->languages()->current() ?>">

<head>
    <title><?= $page->title() ?> | <?= $site->title() ?></title>
    <?= $this->insert('_meta') ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🚧</text></svg>">
    <link rel="stylesheet" type="text/css" href="<?= $this->assets()->get('css/style.css')->uri(includeVersion: true) ?>">
    <script src="<?= $this->assets()->get('js/script.js')->uri(includeVersion: true) ?>"></script>
</head>

<body>
    <header class="header">
        <div class="container">
            <?= $this->insert('_menu') ?>
        </div>
    </header>
    <?= $this->insert('_cover-image') ?>
    <?= $this->content() ?>
    <footer class="footer">
        <div class="container">
            &copy; 2017-2026 &mdash; Made with <a href="https://github.com/getformwork/formwork">Formwork</a>
        </div>
    </footer>
</body>

</html>
