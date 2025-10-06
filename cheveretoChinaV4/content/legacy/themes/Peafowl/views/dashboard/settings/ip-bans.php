<?php

use Chevereto\Legacy\Classes\IpBan;
use function Chevereto\Legacy\G\get_base_url;
use function Chevereto\Legacy\G\include_theme_file;
use function Safe\json_encode;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
}
$ip_bans = IpBan::getAll(); ?>
<script>
    $(document).ready(function() {
        CHV.obj.ip_bans = <?php echo json_encode($ip_bans); ?>;
    });
</script>
<?php echo read_the_docs_settings('ip-bans', _s('IP bans')); ?>
<ul data-content="dashboard-ip_bans-list" class="tabbed-content-list table-li table-li-hover margin-top-20 margin-bottom-20">
    <li class="table-li-header phone-hide">
        <span class="c6 display-table-cell padding-right-10">IP</span>
        <span class="c5 display-table-cell padding-right-10 phone-hide phablet-hide"><?php _se('Expires'); ?></span>
        <span class="c13 display-table-cell phone-hide"><?php _se('Message'); ?></span>
    </li>
    <?php
    $li_template = '<li data-content="ip_ban" data-ip_ban-id="%ID%" class="word-break-break-all">
<span class="c6 display-table-cell padding-right-10"><a data-modal="edit" data-target="form-modal" data-ip_ban-id="%ID%" data-content="ip_ban-ip">%IP%</a></span>
<span class="c5 display-table-cell padding-right-10 phone-hide phablet-hide" data-content="ip_ban-expires">%EXPIRES%</span>
<span class="c14 display-table-cell padding-right-10 phone-display-block" data-content="ip_ban-message">%MESSAGE%</span>
<span class="display-table-cell"><a class="btn btn-small default" data-ip_ban-id="%ID%" data-args="%ID%" data-confirm="' . _s("Do you really want to remove the ban to the IP %s? This can't be undone.") . '" data-submit-fn="CHV.fn.ip_ban.delete.submit" data-before-fn="CHV.fn.ip_ban.delete.before" data-ajax-deferred="CHV.fn.ip_ban.delete.complete"><i class="fas fa-trash-alt margin-right-5"></i>' . _s('Delete') . '</a></span>
</li>';
foreach ($ip_bans as $ip_ban) {
    $replaces = [];
    foreach ($ip_ban as $k => $v) {
        $replaces['%' . strtoupper($k) . '%'] = $v;
    }
    echo strtr($li_template, $replaces);
} ?>
</ul>
<div class="hidden" data-content="ip_ban-dashboard-template">
    <?php echo $li_template; ?>
</div>
<p><i class="fas fa-info-circle"></i> <?php _se('Banned IP address will be forbidden to use the entire website.'); ?></p>
<div data-modal="form-modal" class="hidden" data-submit-fn="CHV.fn.ip_ban.edit.submit" data-before-fn="CHV.fn.ip_ban.edit.before" data-ajax-deferred="CHV.fn.ip_ban.edit.complete" data-ajax-url="<?php echo get_base_url('json'); ?>">
    <span class="modal-box-title"><i class="fas fa-edit"></i> <?php _se('Edit IP ban'); ?></span>
    <div class="modal-form">
        <input type="hidden" name="form-ip_ban-id">
        <?php include_theme_file('snippets/form_ip_ban_edit'); ?>
    </div>
</div>
