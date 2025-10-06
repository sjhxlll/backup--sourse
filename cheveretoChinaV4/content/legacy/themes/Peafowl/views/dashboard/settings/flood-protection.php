<?php

use Chevereto\Legacy\Classes\Settings;
use Chevereto\Legacy\G\Handler;
use function Chevereto\Legacy\get_select_options_html;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
} ?>
<div class="margin-bottom-10"><i class="fas fa-info-circle"></i> <?php _se("Block image uploads by IP if the system notice a flood behavior based on the number of uploads per time period. This setting doesn't affect administrators."); ?></div>
<?php echo read_the_docs_settings('flood-protection', _s('Flood protection')); ?>
<div class="input-label">
    <label for="flood_uploads_protection"><?php _se('Flood protection'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="flood_uploads_protection" id="flood_uploads_protection" class="text-input" data-combo="flood-protection-combo">
            <?php
            echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Handler::var('safe_post') ? Handler::var('safe_post')['flood_uploads_protection'] : Settings::get('flood_uploads_protection')); ?>
        </select></div>
</div>
<div id="flood-protection-combo">
    <div data-combo-value="1" class="switch-combo <?php if (!(Handler::var('safe_post') ? Handler::var('safe_post')['flood_uploads_protection'] : Settings::get('flood_uploads_protection'))) {
                echo ' soft-hidden';
            } ?>">
        <div class="input-label">
            <label for="flood_uploads_notify"><?php _se('Notify to email'); ?></label>
            <div class="c5 phablet-c1"><select type="text" name="flood_uploads_notify" id="flood_uploads_notify" class="text-input">
                    <?php
                    echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Handler::var('safe_post') ? Handler::var('safe_post')['flood_uploads_notify'] : Settings::get('flood_uploads_notify')); ?>
                </select></div>
            <div class="input-below"><?php _se('If enabled the system will send an email on flood incidents.'); ?></div>
        </div>
        <div class="input-label">
            <label for="flood_uploads_minute"><?php _se('Minute limit'); ?></label>
            <div class="c3"><input type="number" min="0" name="flood_uploads_minute" id="flood_uploads_minute" class="text-input" value="<?php echo Handler::var('safe_post')['flood_uploads_minute'] ?? Settings::get('flood_uploads_minute'); ?>" placeholder="<?php echo Settings::getDefault('flood_uploads_minute'); ?>"></div>
            <div class="input-warning red-warning"><?php echo Handler::var('input_errors')['flood_uploads_minute'] ?? ''; ?></div>
        </div>
        <div class="input-label">
            <label for="flood_uploads_hour"><?php _se('Hourly limit'); ?></label>
            <div class="c3"><input type="number" min="0" name="flood_uploads_hour" id="flood_uploads_hour" class="text-input" value="<?php echo Handler::var('safe_post')['flood_uploads_hour'] ?? Settings::get('flood_uploads_hour'); ?>" placeholder="<?php echo Settings::getDefault('flood_uploads_hour'); ?>"></div>
            <div class="input-warning red-warning"><?php echo Handler::var('input_errors')['flood_uploads_hour'] ?? ''; ?></div>
        </div>
        <div class="input-label">
            <label for="flood_uploads_day"><?php _se('Daily limit'); ?></label>
            <div class="c3"><input type="number" min="0" name="flood_uploads_day" id="flood_uploads_day" class="text-input" value="<?php echo Handler::var('safe_post')['flood_uploads_day'] ?? Settings::get('flood_uploads_day'); ?>" placeholder="<?php echo Settings::getDefault('flood_uploads_day'); ?>"></div>
            <div class="input-warning red-warning"><?php echo Handler::var('input_errors')['flood_uploads_day'] ?? ''; ?></div>
        </div>
        <div class="input-label">
            <label for="flood_uploads_week"><?php _se('Weekly limit'); ?></label>
            <div class="c3"><input type="number" min="0" name="flood_uploads_week" id="flood_uploads_week" class="text-input" value="<?php echo Handler::var('safe_post')['flood_uploads_week'] ?? Settings::get('flood_uploads_week'); ?>" placeholder="<?php echo Settings::getDefault('flood_uploads_week'); ?>"></div>
            <div class="input-warning red-warning"><?php echo Handler::var('input_errors')['flood_uploads_week'] ?? ''; ?></div>
        </div>
        <div class="input-label">
            <label for="flood_uploads_month"><?php _se('Monthly limit'); ?></label>
            <div class="c3"><input type="number" min="0" name="flood_uploads_month" id="flood_uploads_month" class="text-input" value="<?php echo Handler::var('safe_post')['flood_uploads_month'] ?? Settings::get('flood_uploads_month'); ?>" placeholder="<?php echo Settings::getDefault('flood_uploads_month'); ?>"></div>
            <div class="input-warning red-warning"><?php echo Handler::var('input_errors')['flood_uploads_month'] ?? ''; ?></div>
        </div>
    </div>
</div>
