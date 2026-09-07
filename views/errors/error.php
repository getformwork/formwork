<?php $this->insert('@system.errors.partials.header') ?>
<h2>Oops, something went wrong!</h2>
<p>Formwork encountered an error while serving your request.<br>If you are the maintainer of this site, please check Formwork configuration or the server log for errors.</p>
<p><a href="https://github.com/getformwork/formwork/issues" target="_blank">Report an issue to GitHub</a></p>
<?php if (isset($throwable)) : ?>
    <?php $this->insert('@system.errors.partials.debug') ?>
<?php endif ?>
<?php $this->insert('@system.errors.partials.footer') ?>
