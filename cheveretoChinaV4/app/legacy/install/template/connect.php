<?php
use Chevereto\Legacy\G\Handler;
use function Chevereto\Vars\env;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
}
function echoDatabaseEnv(string $name, string $post, string $default): void
{
    echo 'placeholder="' . $default . '" value="';
    $env = env()[$name] ?? null;
    echo(Handler::var('safe_post')[$post] ?? $env ?? $default) . '"';
}
?>
<h1><i class="fa fa-database"></i> 配置数据库连接</h1>
<p>为了继续安装CheveretoChina，请提供您的 MySQL数据库连接信息。</p>
<?php if ($error ?? false) { ?>
<p class="highlight padding-10"><?php echo $error_message ?? ''; ?></p>
<?php } ?>
<form method="post">
	<div class="p input-label">
		<label for="db_host">数据库主机</label>
		<input type="text" name="db_host" id="db_host" class="width-100p" <?php echoDatabaseEnv('CHEVERETO_DB_HOST', 'db_host', 'localhost'); ?> title="Database server host (default: localhost)" required>
	</div>
    <div class="p input-label">
		<label for="db_port">数据库端口</label>
		<input type="number" min="0" name="db_port" id="db_port" class="width-100p" <?php echoDatabaseEnv('CHEVERETO_DB_PORT', 'db_port', '3306'); ?> title="Database server port (default: 3306)" required>
	</div>
	<div class="p input-label">
		<label for="db_name">数据库名称</label>
		<input type="text" name="db_name" id="db_name" class="width-100p" <?php echoDatabaseEnv('CHEVERETO_DB_NAME', 'db_name', ''); ?> title="Name of the database where you want to install Chevereto" required>
	</div>
	<div class="p input-label">
		<label for="db_user">数据库用户</label>
		<input type="text" name="db_user" id="db_user" class="width-100p" <?php echoDatabaseEnv('CHEVERETO_DB_USER', 'db_user', ''); ?> title="User with access to the above database" required>
	</div>
	<div class="p input-label">
		<label for="db_pass">数据库密码</label>
		<input type="password" name="db_pass" id="db_pass" class="width-100p" <?php echoDatabaseEnv('CHEVERETO_DB_PASS', 'db_pass', ''); ?> title="Password of the above user">
	</div>
	<div class="p input-label">
		<label for="db_tablePrefix">数据库前缀</label>
		<input type="text" name="db_tablePrefix" id="db_tablePrefix" class="width-100p" <?php echoDatabaseEnv('CHEVERETO_DB_TABLE_PREFIX', 'db_tablePrefix', 'chv_'); ?>  title="Database table prefix. Use chv_ if you don't need this">
	</div>
	<div>
		<button class="action radius" type="submit">继续</button>
	</div>
</form>
