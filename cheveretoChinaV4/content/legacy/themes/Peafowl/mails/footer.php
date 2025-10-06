<?php
use function Chevereto\Legacy\G\get_base_url;
use function Chevereto\Legacy\getSetting;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
} ?>
<br><br>--<br>
<?php _se('This email was sent from %w %u', ['%w' => getSetting('website_name'), '%u' => get_base_url()]); ?>
</body>
</html>
