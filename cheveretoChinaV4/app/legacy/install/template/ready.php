<?php
use function Chevereto\Legacy\getSetting;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
} ?>
<h1><i class="fa fa-box-open"></i> 准备安装</h1>
<p>配置管理员信息并继续</p>
<p>You can change this account later on.</p>
<?php if ($error ?? false) { ?>
<p class="highlight padding-10"><?php echo $error_message ?? ''; ?></p>
<?php } ?>
<form method="post">
	<div>
        <div class="p input-label">
            <label for="username">管理员用户名</label>
            <input type="text" name="username" id="username" class="width-100p" value="<?php echo $safe_post['username'] ?? ''; ?>" placeholder="Admin username" pattern="<?php echo getSetting('username_pattern'); ?>" title='<?php echo strtr('%i to %f characters<br>Letters, numbers and "_"', ['%i' => getSetting('username_min_length'), '%f' => getSetting('username_max_length')]); ?>' maxlength="<?php echo getSetting('username_max_length'); ?>" required>
            <span class="input-warning red-warning"><?php echo $input_errors['username'] ?? ''; ?></span>
        </div>
        <div class="p input-label">
            <label for="email">管理员邮箱</label>
            <input type="email" name="email" id="email" class="width-100p" value="<?php echo $safe_post['email'] ?? ''; ?>" placeholder="Admin email" title="Valid email address for your admin account" required>
            <span class="input-warning red-warning"><?php echo $input_errors['email'] ?? ''; ?></span>
        </div>
        <div class="p input-label input-password">
            <label for="password">管理员密码</label>
            <input type="password" name="password" id="password" class="width-100p" value="" placeholder="Admin password" title="Password to login" pattern="<?php echo getSetting('user_password_pattern'); ?>" required>
            <div class="input-password-strength"><span style="width: 0%" data-content="password-meter-bar"></span></div>
            <div class="input-warning red-warning" data-text="password-meter-message"><?php echo $input_errors['password'] ?? ''; ?></div>
        </div>
    </div>
	<?php
        if ($is_2X ?? false) {
            ?>
    <div>
        <div class="p input-label">
            <label for="crypt_salt">图床加密密钥</label>
            <input type="text" name="crypt_salt" id="crypt_salt" class="width-100p" value="<?php echo $safe_post['crypt_salt'] ?? ''; ?>" placeholder="Example: changeme" title="As defined in includes/definitions.php" required>
            <span class="input-below highlight">用于 define("__CHV_CRYPT_SALT__", "changeme");</span>
            <span class="input-warning red-warning"><?php echo $input_errors['crypt_salt'] ?? ''; ?></span>
        </div>
    </div>
	<?php
        }
    ?>
	<div>
		<button class="action radius" type="submit">安装</button>
	</div>
</form>
