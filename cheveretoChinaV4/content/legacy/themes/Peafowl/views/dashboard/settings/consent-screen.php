<?php

use Chevereto\Legacy\Classes\Settings;
use Chevereto\Legacy\G\Handler;
use function Chevereto\Legacy\get_select_options_html;
use function Chevereto\Legacy\get_system_image_url;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
} ?>
<div class="margin-bottom-10"><i class="fas fa-info-circle"></i> <?php _se("Shows a consent screen before accessing the website. Useful for adult content websites where minors shouldn't be allowed."); ?></div>
<?php echo read_the_docs_settings('consent-screen', _s('Consent screen')); ?>
<div class="input-label">
    <label for="enable_consent_screen"><?php _se('Enable consent screen'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="enable_consent_screen" id="enable_consent_screen" class="text-input" data-combo="consent-screen-combo">
            <?php
            echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Handler::var('safe_post') ? Handler::var('safe_post')['enable_consent_screen'] : Settings::get('enable_consent_screen')); ?>
        </select></div>
</div>
<div id="consent-screen-combo">
    <div data-combo-value="1" class="switch-combo <?php if (!(Handler::var('safe_post') ? Handler::var('safe_post')['enable_consent_screen'] : Settings::get('enable_consent_screen'))) {
                echo ' soft-hidden';
            } ?>">
        <div class="input-label">
            <label for="consent_screen_cover_image"><?php _se('Consent screen cover image'); ?></label>
            <div class="transparent-canvas dark margin-bottom-10" style="max-width: 200px;"><img class="display-block" width="100%" src="<?php echo get_system_image_url(Settings::get('consent_screen_cover_image')); ?>"></div>
            <div class="c5 phablet-c1">
                <input id="consent_screen_cover_image" name="consent_screen_cover_image" type="file" accept="image/*">
            </div>
            <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['consent_screen_cover_image'] ?? ''; ?></div>
        </div>
    </div>
</div>
