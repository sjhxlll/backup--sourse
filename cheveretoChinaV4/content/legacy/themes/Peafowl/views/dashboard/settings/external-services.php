<?php

use Chevereto\Legacy\Classes\Settings;
use Chevereto\Legacy\G\Handler;
use function Chevereto\Legacy\get_select_options_html;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
}
echo read_the_docs_settings('external-services', _s('External services')); ?>
<div class="input-label">
    <label for="akismet"><?php _se('%s spam protection', 'Akismet'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="akismet" id="akismet" class="text-input" data-combo="akismet-combo">
        <?php
                echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Handler::var('safe_post') ? Handler::var('safe_post')['akismet'] : Settings::get('akismet')); ?>
    </select></div>
    <div class="input-below"><?php _se('Enable this to use %l to block spam on %c.', [
                                                            '%l' => '<a rel="external" href="https://akismet.com/" target="_blank">Akismet</a>',
                                                            '%c' => _s('user generated content')
                                                        ]); ?></div>
</div>
<div id="akismet-combo" class="c9 phablet-c1">
    <div data-combo-value="1" class="switch-combo<?php if (!(Handler::var('safe_post') ? Handler::var('safe_post')['akismet'] : Settings::get('akismet'))) {
                                                            echo ' soft-hidden';
                                                        } ?>">
        <div class="input-label">
            <label for="akismet_api_key"><?php _se('%s API key', 'Akismet'); ?></label>
            <input type="text" name="akismet_api_key" id="akismet_api_key" class="text-input" value="<?php echo Handler::var('safe_post')['akismet_api_key'] ?? Settings::get('akismet_api_key'); ?>" >
            <div class="input-warning red-warning"><?php echo Handler::var('input_errors')['akismet_api_key'] ?? ''; ?></div>
        </div>
    </div>
</div>
<div class="input-label">
    <label for="stopforumspam"><?php _se('%s spam protection', 'StopForumSpam'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="stopforumspam" id="stopforumspam" class="text-input">
        <?php
                echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Handler::var('safe_post') ? Handler::var('safe_post')['stopforumspam'] : Settings::get('stopforumspam')); ?>
    </select></div>
    <div class="input-below"><?php _se('Enable this to use %l to block spam on %c.', [
                                                            '%l' => '<a rel="external" href="https://stopforumspam.com/" target="_blank">StopForumSpam</a>',
                                                            '%c' => _s('user signup')
                                                        ]); ?></div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="cdn">CDN</label>
    <div class="c5 phablet-c1"><select type="text" name="cdn" id="cdn" class="text-input" data-combo="cdn-combo">
        <?php
                echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Handler::var('safe_post') ? Handler::var('safe_post')['cdn'] : Settings::get('cdn')); ?>
    </select></div>
</div>
<div id="cdn-combo" class="c9 phablet-c1">
    <div data-combo-value="1" class="switch-combo<?php if (!(Handler::var('safe_post') ? Handler::var('safe_post')['cdn'] : Settings::get('cdn'))) {
                    echo ' soft-hidden';
                } ?>">
        <div class="input-label">
            <label for="cdn_url">CDN URL</label>
            <input type="text" name="cdn_url" id="cdn_url" class="text-input" value="<?php echo Handler::var('safe_post')['cdn_url'] ?? Settings::get('cdn_url'); ?>" placeholder="http://a.cdn.url.com/">
            <div class="input-warning red-warning"><?php echo Handler::var('input_errors')['cdn_url'] ?? ''; ?></div>
        </div>
    </div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="recaptcha">reCAPTCHA</label>
    <div class="c5 phablet-c1"><select type="text" name="recaptcha" id="recaptcha" class="text-input" data-combo="recaptcha-combo">
        <?php
                echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Handler::var('safe_post') ? Handler::var('safe_post')['recaptcha'] : Settings::get('recaptcha')); ?>
    </select></div>
    <div class="input-below"><?php _se('You need a %s for this.', '<a rel="external" href="https://www.google.com/recaptcha/intro/index.html" target="_blank">' . _s('%s key', 'reCAPTCHA') . '</a>'); ?></div>
</div>
<div id="recaptcha-combo">
    <div data-combo-value="1" class="switch-combo<?php if (!(Handler::var('safe_post') ? Handler::var('safe_post')['recaptcha'] : Settings::get('recaptcha'))) {
                    echo ' soft-hidden';
                } ?>">
        <div class="input-label">
            <label for="recaptcha_version">reCAPTCHA version</label>
            <div class="c2 phablet-c1"><select type="text" name="recaptcha_version" id="recaptcha_version" class="text-input">
                <?php
                echo get_select_options_html([2 => '2', 3 => '3'], Handler::var('safe_post') ? Handler::var('safe_post')['recaptcha_version'] : Settings::get('recaptcha_version')); ?>
            </select></div>
            <div class="input-below"><?php _se("Please note that each reCAPTCHA version require its own set of keys. Don't forget to update the keys if you change versions."); ?></div>
        </div>
        <div class="c9 phablet-c1">
            <div class="input-label">
                <label for="recaptcha_public_key"><?php _se('%s site key', 'reCAPTCHA'); ?></label>
                <input type="text" name="recaptcha_public_key" id="recaptcha_public_key" class="text-input" value="<?php echo Handler::var('safe_post')['recaptcha_public_key'] ?? Settings::get('recaptcha_public_key'); ?>">
                <div class="input-warning red-warning"><?php echo Handler::var('input_errors')['recaptcha_public_key'] ?? ''; ?></div>
            </div>
            <div class="input-label">
                <label for="recaptcha_private_key"><?php _se('%s secret key', 'reCAPTCHA'); ?></label>
                <input type="text" name="recaptcha_private_key" id="recaptcha_private_key" class="text-input" value="<?php echo Handler::var('safe_post')['recaptcha_private_key'] ?? Settings::get('recaptcha_private_key'); ?>">
                <div class="input-warning red-warning"><?php echo Handler::var('input_errors')['recaptcha_private_key'] ?? ''; ?></div>
            </div>
        </div>
        <div class="input-label">
            <div class="c9 phablet-c1">
                <label for="recaptcha_threshold"><?php _se('reCAPTCHA threshold'); ?></label>
                <div class="c2">
                    <input type="number" min="0" name="recaptcha_threshold" id="recaptcha_threshold" class="text-input" value="<?php echo Handler::var('safe_post')['recaptcha_threshold'] ?? Settings::get('recaptcha_threshold'); ?>">
                </div>
            </div>
            <div class="input-below"><?php _se('How many failed attempts are needed to ask for reCAPTCHA? Use zero (0) to always show reCAPTCHA.'); ?></div>
        </div>
        <div class="input-label">
            <label for="force_recaptcha_contact_page"><?php _se('Force %s on contact page', 'reCAPTCHA'); ?></label>
            <div class="c5 phablet-c1"><select type="text" name="force_recaptcha_contact_page" id="force_recaptcha_contact_page" class="text-input">
                <?php
                echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Handler::var('safe_post') ? Handler::var('safe_post')['force_recaptcha_contact_page'] : Settings::get('force_recaptcha_contact_page')); ?>
            </select></div>
            <div class="input-below"><?php _se('Enable this to always show %s on contact page.', 'reCAPTCHA'); ?></div>
        </div>
    </div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="comments_api"><?php _se('Comments API'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="comments_api" id="comments_api" class="text-input" data-combo="comments_api-combo">
        <?php
                echo get_select_options_html([
                    'disqus' => 'Disqus',
                    'js' => 'JavaScript/HTML',
                ], Handler::var('safe_post') ? Handler::var('safe_post')['comments_api'] : Settings::get('comments_api')); ?>
    </select></div>
    <div class="input-below"><?php _se('Disqus API works with %s.', '<a rel="external" href="https://help.disqus.com/customer/portal/articles/236206" target="_blank">Single Sign-On</a> (SSO)'); ?></div>
</div>
<div id="comments_api-combo">
    <div data-combo-value="disqus" class="switch-combo<?php if ((Handler::var('safe_post') ? Handler::var('safe_post')['comments_api'] : Settings::get('comments_api')) !== 'disqus') {
                    echo ' soft-hidden';
                } ?>">
        <div class="c9 phablet-c1">
            <div class="input-label">
                <label for="disqus_shortname"><?php _se('Disqus shortname'); ?></label>
                <input type="text" name="disqus_shortname" id="disqus_shortname" class="text-input" value="<?php echo Handler::var('safe_post')['disqus_shortname'] ?? Settings::get('disqus_shortname'); ?>">
                <div class="input-warning red-warning"><?php echo Handler::var('input_errors')['disqus_shortname'] ?? ''; ?></div>
            </div>
            <div class="input-label">
                <label for="disqus_secret_key"><?php _se('%s secret key', 'Disqus'); ?></label>
                <input type="text" name="disqus_secret_key" id="disqus_secret_key" class="text-input" value="<?php echo Handler::var('safe_post')['disqus_secret_key'] ?? Settings::get('disqus_secret_key'); ?>">
                <div class="input-warning red-warning"><?php echo Handler::var('input_errors')['disqus_secret_key'] ?? ''; ?></div>
            </div>
            <div class="input-label">
                <label for="disqus_public_key"><?php _se('%s public key', 'Disqus'); ?></label>
                <input type="text" name="disqus_public_key" id="disqus_public_key" class="text-input" value="<?php echo Handler::var('safe_post')['disqus_public_key'] ?? Settings::get('disqus_public_key'); ?>">
                <div class="input-warning red-warning"><?php echo Handler::var('input_errors')['disqus_public_key'] ?? ''; ?></div>
            </div>
        </div>
    </div>
    <div data-combo-value="js" class="switch-combo<?php if ((Handler::var('safe_post') ? Handler::var('safe_post')['comments_api'] : Settings::get('comments_api')) !== 'js') {
                    echo ' soft-hidden';
                } ?>">
        <div class="input-label">
            <label for="comment_code"><?php _se('Comment code'); ?></label>
            <div class="c12 phablet-c1"><textarea type="text" name="comment_code" id="comment_code" class="text-input r4" value="" placeholder="<?php _se('Disqus, Facebook or anything you want. It will be used in image view.'); ?>"><?php echo Settings::get('comment_code'); ?></textarea></div>
        </div>
    </div>
</div>
<hr class="line-separator"></hr>
<div class="input-label">
    <label for="arachnid">Project Arachnid</label>
    <div class="c5 phablet-c1"><select type="text" name="arachnid" id="arachnid" class="text-input" data-combo="arachnid-combo">
        <?php
            echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Handler::var('safe_post') ? Handler::var('safe_post')['arachnid'] : Settings::get('arachnid')); ?>
    </select></div>
    <div class="input-below"><?php _se('Use %s to combat child sexual abuse material (CSAM).', '<a rel="external" href="https://projectarachnid.ca/" target="_blank">Project Arachnid</a>'); ?></div>
</div>
<div id="arachnid-combo" class="c12 phablet-c1">
    <div data-combo-value="1" class="switch-combo<?php if ((Handler::var('safe_post') ? Handler::var('safe_post')['arachnid'] : Settings::get('arachnid')) == 0) {
                echo ' soft-hidden';
            } ?>">
        <div class="input-label">
            <label for="arachnid_key">Arachnid API Key</label>
            <input type="text" name="arachnid_key" id="arachnid_key" class="text-input" value="<?php echo Handler::var('safe_post')['arachnid_key'] ?? Settings::get('arachnid_key'); ?>" placeholder="">
            <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['arachnid_key'] ?? ''; ?></div>
        </div>
    </div>
</div>
<hr class="line-separator"></hr>
<div class="input-label">
    <label for="moderatecontent">ModerateContent</label>
    <div class="c5 phablet-c1"><select type="text" name="moderatecontent" id="moderatecontent" class="text-input" data-combo="moderatecontent-combo">
        <?php
            echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Handler::var('safe_post') ? Handler::var('safe_post')['moderatecontent'] : Settings::get('moderatecontent')); ?>
    </select></div>
    <div class="input-below"><?php _se('Automatically moderate the content using the %s service.', '<a rel="external" href="https://www.moderatecontent.com/" target="_blank">ModerateContent</a>'); ?></div>
</div>
<div id="moderatecontent-combo" class="c12 phablet-c1">
    <div data-combo-value="1" class="switch-combo<?php if ((Handler::var('safe_post') ? Handler::var('safe_post')['moderatecontent'] : Settings::get('moderatecontent')) == 0) {
                echo ' soft-hidden';
            } ?>">
        <div class="input-label">
            <label for="moderatecontent_key">ModerateContent API Key</label>
            <input type="text" name="moderatecontent_key" id="moderatecontent_key" class="text-input" value="<?php echo Handler::var('safe_post')['moderatecontent_key'] ?? Settings::get('moderatecontent_key'); ?>" placeholder="">
            <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['moderatecontent_key'] ?? ''; ?></div>
        </div>
        <div class="input-label">
            <label for="moderatecontent_auto_approve"><?php _se('Automatic approve'); ?></label>
            <div class="c5 phablet-c1"><select type="text" name="moderatecontent_auto_approve" id="moderatecontent_auto_approve" class="text-input">
            <?php echo get_select_options_html([0 => _s('Disabled'), 1 => _s('Enabled')], Handler::var('safe_post') ? Handler::var('safe_post')['moderatecontent_auto_approve'] : Settings::get('moderatecontent_auto_approve')); ?>
            </select></div>
            <div class="input-below"><?php _se('Enable this to automatically approve content moderated by this service.'); ?></div>
        </div>
        <div class="input-label">
            <label for="moderatecontent_block_rating"><?php _se('Block content'); ?></label>
            <div class="c5 phablet-c1"><select type="text" name="moderatecontent_block_rating" id="moderatecontent_block_rating" class="text-input">
            <?php echo get_select_options_html(['' => _s('Disabled'), 'a' => _s('Adult'), 't' => _s('Teen and adult')], Handler::var('safe_post') ? Handler::var('safe_post')['moderatecontent_block_rating'] : Settings::get('moderatecontent_block_rating')); ?>
            </select></div>
        </div>
        <div class="input-label">
            <label for="moderatecontent_flag_nsfw"><?php _se('Flag NSFW'); ?></label>
            <div class="c5 phablet-c1"><select type="text" name="moderatecontent_flag_nsfw" id="moderatecontent_flag_nsfw" class="text-input">
            <?php echo get_select_options_html([0 => _s('Disabled'), 'a' => _s('Adult'), 't' => _s('Teen and adult')], Handler::var('safe_post') ? Handler::var('safe_post')['moderatecontent_flag_nsfw'] : Settings::get('moderatecontent_flag_nsfw')); ?>
            </select></div>
        </div>
    </div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="analytics_code"><?php _se('Analytics code'); ?></label>
    <div class="c12 phablet-c1"><textarea type="text" name="analytics_code" id="analytics_code" class="text-input r4" value="" placeholder="<?php _se('Google Analytics or anything you want. It will be added to the theme footer.'); ?>"><?php echo Settings::get('analytics_code'); ?></textarea></div>
</div>
