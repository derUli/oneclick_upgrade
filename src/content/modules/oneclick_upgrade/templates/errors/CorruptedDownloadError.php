<?php

defined('ULICMS_ROOT') || exit('No direct script access allowed');

?>
<div class="alert alert-danger">
    <strong><?php translate('error'); ?>!</strong> <?php translate('corrupted_download'); ?>
</div>
<form action="../?sClass=CoreUpgradeController&sMethod=runUpgrade"
        method="post">
            <?php csrf_token_html(); ?>
    <p>
        <button type="submit" class="btn btn-danger"><?php translate('retry'); ?></button>
    </p>
</form>
