<?php

use Chevereto\Legacy\G\Handler;
use function Chevereto\Legacy\G\include_theme_file;
use function Chevereto\Legacy\G\is_route;
use function Chevereto\Legacy\getSetting;
use function Chevereto\Legacy\include_peafowl_foot;
use function Chevereto\Legacy\show_theme_inline_code;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
}
if (!Handler::cond('maintenance')) {
    include_theme_file('snippets/embed_tpl');
}
if (Handler::cond('upload_allowed') && (getSetting('upload_gui') == 'js' || is_route('upload'))) {
    include_theme_file('snippets/anywhere_upload');
}
if (getSetting('theme_show_social_share')) {
    include_theme_file("snippets/modal_share");
}
include_theme_file('custom_hooks/footer');
include_peafowl_foot();
show_theme_inline_code('snippets/footer.js');
echo getSetting('analytics_code');
?>
</body>
</html>
