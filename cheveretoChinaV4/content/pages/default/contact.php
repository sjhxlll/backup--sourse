<?php

use Chevereto\Legacy\Classes\Login;
use function Chevereto\Legacy\G\get_base_url;
use function Chevereto\Legacy\G\get_client_ip;
use function Chevereto\Legacy\G\get_input_auth_token;
use Chevereto\Legacy\G\Handler;
use function Chevereto\Legacy\G\include_theme_footer;
use function Chevereto\Legacy\G\include_theme_header;
use function Chevereto\Legacy\G\redirect;
use function Chevereto\Legacy\get_recaptcha_component;
use function Chevereto\Legacy\getSetting;
use function Chevereto\Legacy\getSettings;
use function Chevereto\Legacy\recaptcha_check;
use function Chevereto\Legacy\send_mail;
use function Chevereto\Vars\get;
use function Chevereto\Vars\post;
use function Chevereto\Vars\request;
use function Chevereto\Vars\server;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
} ?>
<?php
$is_error = false;
$input_errors = [];
$is_sent = isset(get()['sent']);
$allowed_subjects = [
    'general' => _s('General questions/comments'),
    'dmca' => _s('DMCA complaint'),
];
if (Handler::cond('is_captcha_needed') || (getSetting('recaptcha') && getSetting('force_recaptcha_contact_page'))) {
    Handler::setCond('captcha_show', true);
    Handler::setVar(...get_recaptcha_component());
}
if (post() !== [] && !$is_sent) {
    if (!Handler::checkAuthToken(request()['auth_token'])) {
        die(_s("Request denied"));
    }
    if (strlen(post()['name']) == 0) {
        $input_errors['name'] = _s('Invalid name');
    }
    if (strlen(post()['message']) == 0) {
        $input_errors['message'] = _s('Invalid message');
    }
    if (!array_key_exists(post()['subject'], $allowed_subjects)) {
        $input_errors['subject'] = _s('Invalid subject');
    }
    if (!filter_var(post()['email'], FILTER_VALIDATE_EMAIL)) {
        $input_errors['email'] = _s('Invalid email');
    }
    if (Handler::cond('captcha_show')) {
        $captcha = recaptcha_check();
        if (!$captcha->is_valid) {
            $input_errors['recaptcha'] = _s('%s says you are a robot', 'reCAPTCHA');
        }
    }
    if (count($input_errors) > 0) {
        $is_error = true;
    } else {
        $email = trim(post()['email']);
        $subject = getSettings()['website_name'] . ' contact form';
        $name = post()['name'];
        $send_mail = [
            'to' => getSettings()['email_incoming_email'],
            'from' => [getSettings()['email_from_email'], $name . ' (' . getSettings()['website_name'] . ' contact form)'],
            'reply-to' => [$email]
        ];
        $body_arr = [ // Mail body array (easier to edit)
            'Name' => $name,
            'E-mail' => $email,
            'User' => (Login::isLoggedUser() ? Login::getUser()['url'] : 'not user'),
            'Subject' => post()['subject'] . "\n",
            'Message' => strip_tags(post()['message']) . "\n",
            'IP' => get_client_ip(),
            'Browser' => server()['HTTP_USER_AGENT'] ?? 'n/a',
            'URL' => get_base_url() . "\n"
        ];
        $body = '';
        foreach ($body_arr as $k => $v) {
            $body .= $k . ': ' . $v . "\n";
        }
        send_mail($send_mail, $subject, $body);
        redirect(get_base_url('page/contact/?sent=1'));
    }
}
include_theme_header(); ?>
<div class="content-width">
	<div class="c24 center-box margin-top-20">
		<div class="header default-margin-bottom">
			<h1 class="header-title"><?php echo $is_sent
                ? '<span class="fas fa-check-circle color-success margin-right-5"></span>' . _s('Message sent')
                : '<span class="fas fa-at margin-right-5"></span>' . _s('Contact'); ?></h1>
		</div>
        <p><?php echo $is_sent ? _s('Message sent. We will get in contact soon.') : _s('If you want to send a message fill the form below.'); ?></p>
        <?php if (!$is_sent) { ?>
		<form method="post" class="form-content">
			<?php echo get_input_auth_token(); ?>
			<div class="input-label c8">
				<label for="name"><?php _se('Name'); ?></label>
				<input type="text" name="name" id="name" class="text-input" placeholder="<?php _se('Your name'); ?>" value="<?php if ($is_error) {
                    echo Handler::var('safe_post')['name'];
                } ?>" required>
				<div class="input-warning red-warning"><?php echo $input_errors['name'] ?? ''; ?></div>
			</div>
			<div class="input-label c8">
				<label for="email"><?php _se('Email address'); ?></label>
				<input type="email" name="email" id="email" class="text-input" placeholder="<?php _se('Your email address'); ?>" value="<?php if ($is_error) {
                    echo Handler::var('safe_post')['email'];
                } ?>" required>
				<div class="input-warning red-warning"><?php echo $input_errors['email'] ?? ''; ?></div>
			</div>
			<div class="input-label c8">
				<label for="subject"><?php _se('Subject'); ?></label>
				<select type="text" name="subject" id="subject" class="text-input">
					<?php
                        $ask_for = Handler::var('safe_post') ? Handler::var('safe_post')['subject'] : '';
                        foreach ($allowed_subjects as $k => $v) {
                            ?>
					<option value="<?php echo $k; ?>"<?php if ($ask_for == $k) {
                                ?> selected<?php
                            } ?>><?php echo $v; ?></option>
					<?php
                        }
                    ?>
				</select>
				<div class="input-warning red-warning"><?php echo $input_errors['subject'] ?? ''; ?></div>
			</div>
			<div class="input-label c12">
				<label for="message"><?php _se('Message'); ?></label>
				<textarea name="message" id="message" class="text-input r3" required><?php if ($is_error) {
                        echo Handler::var('safe_post')['message'];
                    } ?></textarea>
				<div class="input-warning red-warning"><?php echo $input_errors['message'] ?? ''; ?></div>
			</div>
			<?php if (Handler::cond('captcha_show')) {
                        ?>
            <?php if (Handler::var('recaptcha_html')) {
                            ?>
			<div class="input-label">
				<label for="recaptcha_response_field">reCAPTCHA</label>
				<?php echo Handler::var('recaptcha_html'); ?>
			</div>
            <?php
                        }//recaptcha_html?>
            <div class="input-below red-warning"><?php echo $input_errors['recaptcha'] ?? ''; ?></div>
			<?php
                    }//captcha_show?>
            <?php if (!Login::isLoggedUser()) { ?>
            <div class="checkbox-label">
                <label for="accept-tos"><input type="checkbox" name="accept-tos" id="accept-tos" class="margin-right-5" value="1" required><?php echo Handler::var('tos_privacy_agreement'); ?></label>
            </div>
            <?php } ?>
			<div class="btn-container">
				<button class="btn btn-input default" type="submit"><span class="btn-icon fas fa-check-circle"></span><span class="btn-text"><?php _se('Send'); ?></span></button></span>
			</div>
		</form>
        <?php } ?>
	</div>
</div>
<?php if (post() !== [] && $is_error) {
                        ?>
<script>
$(function() {
	PF.fn.growl.call("<?php _se('Check the errors in the form to continue.'); ?>");
});
</script>
<?php
                    } ?>
<?php include_theme_footer(); ?>
