<?php $this->insert('@system.errors.partials.header') ?>
<h2>The administration panel is currently offline due to technical problems</h2>
<p>Required panel assets were not found. If you are the maintainer of this site, please run <code>cd panel; pnpm install && pnpm build</code></p>
<?php $this->insert('@system.errors.partials.footer') ?>
