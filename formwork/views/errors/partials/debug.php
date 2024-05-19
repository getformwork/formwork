</div>
<div class="error-debug-details">
    <h3>Uncaught <code><?= $throwable::class ?></code>: <?= $throwable->getMessage() ?></h3>
    <details open>
        <summary><a class="error-debug-editor-uri" href="<?= Formwork\Utils\Str::interpolate($app->config()->get('system.debug.editorUri'), ['filename' => $throwable->getFile(), 'line' => $throwable->getLine()]) ?>"><span class="error-debug-filename"><?= preg_replace('/([^\/]+)$/', '<strong>$1</strong>', $throwable->getFile()) ?></span><span class="error-debug-line">:<?= $throwable->getLine() ?></span></a></summary>
        <?= Formwork\Utils\CodeDumper::dumpLine($throwable->getFile(), $throwable->getLine(), $app->config()->get('system.debug.contextLines', 5)) ?>
    </details>
    <?php foreach ($throwable->getTrace() as $trace) : ?>
        <?php if (isset($trace['file'], $trace['line']) && $trace['file'] !== $throwable->getFile() && $trace['line'] !== $throwable->getLine()) : ?>
            <details>
                <summary><a class="error-debug-editor-uri" href="<?= Formwork\Utils\Str::interpolate($app->config()->get('system.debug.editorUri'), ['filename' => $trace['file'], 'line' => $trace['line']]) ?>"><span class="error-debug-filename"><?= preg_replace('/([^\/]+)$/', '<strong>$1</strong>', $trace['file']) ?></span><span class="error-debug-line">:<?= $trace['line'] ?></span></a></summary>
                <?= Formwork\Utils\CodeDumper::dumpLine($trace['file'], $trace['line'], $app->config()->get('system.debug.contextLines', 5)) ?>
            </details>
        <?php endif ?>
    <?php endforeach ?>