<?php
use function Chevereto\Legacy\G\get_base_url;
use function Chevereto\Legacy\get_chevereto_version;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
} ?>
<h1><i class="far fa-check-circle"></i> 安装完成</h1>
<p><?php echo strtr('CheveretoChina 已成功安装。 您现在可以进入<a href="%u">仪表盘</a>配置您的图床。', ['%s' => get_chevereto_version(true), '%u' => get_base_url('dashboard')]); ?></p>
<div>
    <a href="<?php echo get_base_url('dashboard'); ?>" class="action button radius"> 仪表盘</a>
    <a href="<?php echo get_base_url(); ?>" class="button radius"> 站点首页</a>
</div>
