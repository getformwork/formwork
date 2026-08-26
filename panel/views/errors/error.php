<!DOCTYPE html>
<html class="color-scheme-<?= $panel->colorScheme()->value ?>">

<head>
    <title><?php if (!empty($title)) : ?><?= $this->escape($title) ?> | <?php endif ?>Formwork</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="<?= $this->assets()->get('@panel/images/icon.svg')->uri() ?>">
    <link rel="alternate icon" href="<?= $this->assets()->get('@panel/images/icon.png')->uri() ?>">
    <?php $this->assets()->add('@panel/css/panel.min.css') ?>
    <?php $this->insert('@panel._stylesheets') ?>
</head>

<body>
    <main>
        <div class="container-full">
            <div class="error-container">
                <h1>
                    <span class="error-code"><?= $code ?></span>
                    <span class="error-status"><?= $this->escape($status) ?></span>
                </h1>
                <img class="logo" src="<?= $this->assets()->get('@panel/images/icon.svg')->uri() ?>">
                <h2><?= $this->escape($heading) ?></h2>
                <p><?= $this->escape($description) ?></p>
                <?php if (isset($action)) : ?><a class="action" href="<?= $action['href'] ?>"><?= $this->escape($action['label']) ?></a><?php endif ?>
            </div>
        </div>
        <?php if (isset($throwable)) : ?>
            <div class="container-full">
                <?php while ($throwable): ?>
                    <div class="error-debug-details">
                        <h3>Uncaught <code><?= $throwable::class ?></code>: <?= $this->escape($throwable->getMessage()) ?></h3>
                        <?php foreach ($traceResolver($throwable) as $i => $frame) : ?>
                            <?php if (isset($frame['file'], $frame['line'])): ?>
                                <details <?= $this->attr(['open' => $i === 0]) ?>>
                                    <summary><a class="error-debug-editor-uri" href="<?= Formwork\Utils\Str::interpolate($app->config()->getString('system.debug.editorUri'), ['filename' => $frame['file'], 'line' => $frame['line']]) ?>"><span class="error-debug-filename"><?= preg_replace('/([^\/]+)$/', '<strong>$1</strong>', $frame['file']) ?></span><span class="error-debug-line">:<?= $frame['line'] ?></span></a></summary>
                                    <?php Formwork\Debug\CodeDumper::dumpBacktraceFrame($frame, $app->config()->getInt('system.debug.contextLines', 5)) ?>
                                </details>
                            <?php endif ?>
                        <?php endforeach ?>
                    </div>
                    <?php $throwable = $throwable->getPrevious() ?>
                <?php endwhile ?>
            </div>
        <?php endif ?>
    </main>
    <?php $this->assets()->add('@panel/js/app.min.js', ['module' => true]) ?>
    <?php $this->insert('@panel._scripts') ?>
</body>

</html>
