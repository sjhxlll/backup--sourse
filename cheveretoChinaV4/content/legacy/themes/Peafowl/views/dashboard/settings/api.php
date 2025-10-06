<?php

use Chevereto\Legacy\Classes\Settings;
use Chevereto\Legacy\G\Handler;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
}
echo read_the_docs_settings('api', _s('API')); ?>
<div class="input-label">
    <label for="api_v1_key"><?php _se('Public API key'); ?></label>
    <div class="c12 phablet-c1 position-relative">
        <input type="text" name="api_v1_key" id="api_v1_key" class="text-input" value="<?php echo Settings::get('api_v1_key'); ?>">
        <button type="button" class="input-action" data-action="copy" data-action-target="#api_v1_key"><i class="far fa-copy"></i> <?php _se('copy'); ?></button>
    </div>
    <div class="input-warning red-warning"><?php echo Handler::var('input_errors')['api_v1_key'] ?? ''; ?></div>
    <div class="input-below"><?php _se('This key is for guest usage.'); ?> <?php _se('Check the %s documentation.', '<a rel="external" href="https://v4-docs.chevereto.com/developer/api/api-v1.html" target="_blank">API V1</a>'); ?></div>
</div>
