<?php

use Chevereto\Legacy\G\Handler;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
} ?>
<?php
if (Handler::cond('captcha_needed') && Handler::hasVar('recaptcha_html')) {
    ?>
<div class="content-section content-section--recaptchaFix">
    <?php echo Handler::var('recaptcha_html'); ?>
</div>
<?php
} ?>
