</div>
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
<div>
