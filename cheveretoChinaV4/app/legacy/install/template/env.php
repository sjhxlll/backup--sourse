<?php
use function Chevereto\Legacy\G\get_base_url;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
} ?>
<h1>发现 App/Env.php 文件权限问题</h1>
<p>数据库详细信息是正确的，但是系统无法将配置写入 <code>app/env.php</code> 文件。</p>
<p>您需要检查 <code><?php echo PATH_APP . 'env.php'; ?></code> 文件权限，确保文件可写，并点击下方重试！</p>
<code class="display-block" data-click="select-all"><pre><?php echo htmlentities($envDotPhpContents ?? ''); ?></pre></code>
<div>
    <a href="<?php echo get_base_url(); ?>" class="action button radius">重试</a>
</div>
