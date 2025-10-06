<?php
use function Chevereto\Legacy\G\get_base_url;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
} ?>
<h1><i class="far fa-check-circle"></i> 已安装</h1>
<p>CheveretoChina 已经安装并更新。</p>
<div>
    <a href="<?php echo get_base_url('dashboard'); ?>" class="action button radius"><span class="btn-icon fa-btn-icon fas fa-tachometer-alt"></span> 仪表盘</a>
    <a href="<?php echo get_base_url(); ?>" class="button radius"><span class="btn-icon fa-btn-icon fas fa-globe"></span> 站点首页</a>
</div>
