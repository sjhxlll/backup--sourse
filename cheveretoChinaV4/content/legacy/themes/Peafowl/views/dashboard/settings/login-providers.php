<?php

use Chevereto\Legacy\Classes\Login;
use Chevereto\Legacy\Classes\Settings;
use Chevereto\Legacy\G\Handler;
use function Chevereto\Legacy\get_select_options_html;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
}
read_the_docs_settings('login-providers', _s('Login providers'));
$tpl = <<<HTML
<div class="input-label">
    <label for="%name%"><span class="fab fa-%name%"></span> %label%</label>
    <div class="c5 phablet-c1"><select type="text" name="%name%" id="%name%" class="text-input" data-combo="%name%-combo">
        %options%
    </select></div>
    <div class="input-warning red-warning">%optionError%</div>
</div>
<div id="%name%-combo">
    <div data-combo-value="1" class="switch-combo c9 phablet-c1%hidden%">
        <div class="input-label">
            <label for="%name%_id"><span class="fab fa-%name%"></span> %appId%</label>
            <input type="text" name="%name%_id" id="%name%_id" class="text-input" value="%appIdValue%" placeholder="%label% %appId%" data-required>
            <div class="input-warning red-warning">%appIdError%</div>
        </div>
        <div class="input-label">
            <label for="%name%_secret"><span class="fab fa-%name%"></span> %appSecret%</label>
            <input type="text" name="%name%_secret" id="%name%_secret" class="text-input" value="%appSecretValue%" placeholder="%label% %appSecret%" data-required>
            <div class="input-warning red-warning">%appSecretError%</div>
        </div>
    </div>
</div>
<hr class="line-separator">
HTML;
foreach (Login::getProviders('all') as $name => $provider) {
    if ($name === 'apple') {
        continue;
    }
    echo strtr($tpl, [
        '%name%' => $name,
        '%label%' => $provider['label'],
        '%options%' => get_select_options_html(
            [
                1 => _s('Enabled'),
                0 => _s('Disabled')
            ],
            Handler::var('safe_post')
                ? Handler::var('safe_post')[$name]
                : $provider['is_enabled']
        ),
        '%optionError%' => Handler::var('input_errors')[$name] ?? '',
        '%hidden%' => (!(Handler::var('safe_post') ? Handler::var('safe_post')[$name] : $provider['is_enabled']))
            ? ' soft-hidden'
            : '',
        '%appId%' => _s('Application id'),
        '%appIdValue%' => Handler::var('safe_post')['%name%_id'] ?? $provider['key_id'],
        '%appIdError%' => Handler::var('input_errors')['%name%_id'] ?? '',
        '%appSecret%' => _s('Application secret'),
        '%appSecretValue%' => Handler::var('safe_post')[$name . '_secret'] ?? $provider['key_secret'],
        '%appSecretError%' => Handler::var('input_errors')[$name . '_secret'] ?? '',
    ]);
} ?>
<div class="input-label">
    <label for="twitter_account">MOVER<?php _se('Twitter account'); ?></label>
    <div class="c5 phablet-c1">
        <input type="text" name="twitter_account" id="twitter_account" class="text-input" placeholder="chevereto" value="<?php echo Handler::var('safe_post')['twitter_account'] ?? Settings::get('twitter_account'); ?>">
    </div>
    <div class="input-warning red-warning"><?php echo Handler::var('input_errors')['twitter_account'] ?? ''; ?></div>
</div>
