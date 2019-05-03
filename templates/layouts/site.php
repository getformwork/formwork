<!DOCTYPE html>
<html lang="<?= $site->lang() ?>">
<head>
    <meta charset="utf-8">
    <title><?= $page->title() ?> | <?= $site->title() ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="<?= $this->assets()->uri('css/style.min.css') ?>">
    <script src="<?= $this->assets()->uri('js/script.min.js') ?>"></script>
</head>
<body>
<?= $this->insert('_menu') ?>
<?= $this->insert('_cover-image') ?>
<?= $this->content() ?>
    <footer>
        <div class="container small">
            &copy; 2017-2019 &mdash; Made with <a href="http://github.com/giuscris/formwork">Formwork</a>
        </div>
    </footer>
</body>
</html>
