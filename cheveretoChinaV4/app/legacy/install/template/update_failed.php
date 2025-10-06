<?php
// @phpstan-ignore-next-line
// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
} ?>
<h1>更新失败</h1>
<p>更新失败，请检查 MySQL 数据库问题：</p>
<p class="highlight padding-10"><?php echo $error_message ?? ''; ?></p>
<p>如果您从较旧版本进行更新，将您的MyISAM表更新到InnoDB表存储引擎，然后重试。</p>
<p>如果您更改了数据库，则需要手动执行此更新。</p>
