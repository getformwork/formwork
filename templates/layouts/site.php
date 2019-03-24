<!DOCTYPE html>
<html lang="<?= $site->lang() ?>">
<head>
    <title><?php if (isset($page)): ?><?= $page->title() ?> | <?php endif; ?><?= $site->title() ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="<?= $site->uri('/templates/assets/css/style.min.css') ?>">
    <script src="<?= $site->uri('/templates/assets/js/script.min.js') ?>"></script>
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
