<?php header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 500 Internal Server Error'); ?>
<?php include __DIR__ . '/partials/header.php' ?>
<h2>The site is currently offline<br>due to technical problems</h2>
<p>If you are the maintainer of this site, please run <code>composer install</code>. Composer autoloader was not found.</p>
<?php include __DIR__ . '/partials/footer.php' ?>