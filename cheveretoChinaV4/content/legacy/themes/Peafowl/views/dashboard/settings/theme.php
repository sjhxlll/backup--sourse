<?php

use Chevereto\Legacy\Classes\Palettes;
use Chevereto\Legacy\Classes\Settings;
use Chevereto\Legacy\G\Handler;
use function Chevereto\Legacy\G\random_string;
use function Chevereto\Legacy\get_select_options_html;
use function Chevereto\Legacy\get_system_image_url;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
}
echo read_the_docs_settings('theme', _s('Theme')); ?>
<div class="input-label">
    <label for="theme"><?php _se('Theme'); ?></label>
    <?php
    $themes = [];
foreach (scandir(PATH_PUBLIC_CONTENT_LEGACY_THEMES) as $v) {
    if (is_dir(PATH_PUBLIC_CONTENT_LEGACY_THEMES . DIRECTORY_SEPARATOR . $v) and !in_array($v, ['.', '..'])) {
        $themes[$v] = $v;
    }
} ?>
    <div class="c5 phablet-c1">
        <select type="text" name="theme" id="theme" class="text-input">
            <?php
            echo get_select_options_html($themes, Settings::get('theme')); ?>
        </select>
    </div>
</div>
<div class="input-label">
<?php
/** @var Palettes $palettes */
$palettes = Handler::var('palettes');
$palettesOptions = [];
foreach (array_keys($palettes->get()) as $id) {
    $palettesOptions[strval($id)] = $palettes->getName($id);
}
?>
    <label for="theme_palette"><?php _se('Default %s', _s('palette')); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="theme_palette" id="theme_palette" class="text-input">
            <?php
            echo get_select_options_html($palettesOptions, Handler::var('safe_post') ? Handler::var('safe_post')['theme_palette'] : Settings::get('theme_palette')); ?>
        </select></div>
    <div class="input-below input-warning red-warning clear-both"><?php echo Handler::var('input_errors')['theme_palette'] ?? ''; ?></div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="logo_type"><?php _se('Logo'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="logo_type" id="logo_type" class="text-input" data-combo="logo-combo">
            <?php
            echo get_select_options_html(
    ['vector' => _s('Vector'), 'image' => _s('Image'), 'text' => _s('Text')],
    Handler::var('safe_post') ? Handler::var('safe_post')['logo_type'] : Settings::get('logo_type')
); ?>
        </select></div>
    <div class="input-below input-warning red-warning clear-both"><?php echo Handler::var('input_errors')['logo_type'] ?? ''; ?></div>
    <div class="input-below clear-both"><?php _se('Text option uses the website name as logo.'); ?></div>
</div>
<div id="logo-combo">
<?php
$logoType = Handler::var('safe_post')
    ? Handler::var('safe_post')['logo_type']
    : Settings::get('logo_type');
$logoComboVisibility = function (string ...$try) use ($logoType): string {
    return !in_array($logoType, $try)
        ? ' soft-hidden'
        : '';
}
?>
    <div data-combo-value="vector" class="input-label switch-combo<?php echo $logoComboVisibility('vector'); ?>">
        <label for="logo_vector"><?php _se('Logo vector'); ?></label>
        <div class="transparent-canvas dark margin-bottom-10" style="max-width: 200px;"><img class="display-block" width="100%" src="<?php echo get_system_image_url(Settings::get('logo_vector')) . '?' . random_string(8); ?>"></div>
        <div class="c5 phablet-c1">
            <input id="logo_vector" name="logo_vector" type="file" accept="image/svg">
        </div>
        <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['logo_vector'] ?? ''; ?></div>
        <div class="input-below"><?php _se('Vector version or your website logo in SVG format.'); ?></div>
    </div>
    <div data-combo-value="image" class="input-label switch-combo<?php echo $logoComboVisibility('image'); ?>">
        <label for="logo_image"><?php _se('Logo image'); ?></label>
        <div class="transparent-canvas dark margin-bottom-10" style="max-width: 200px;"><img class="display-block" width="100%" src="<?php echo get_system_image_url(Settings::get('logo_image')) . '?' . random_string(8); ?>"></div>
        <div class="c5 phablet-c1">
            <input id="logo_image" name="logo_image" type="file" accept="image/*">
        </div>
        <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['logo_image'] ?? ''; ?></div>
        <div class="input-below"><?php _se('Bitmap version or your website logo. PNG format is recommended.'); ?></div>
    </div>
    <div data-combo-value="vector image" class="input-label switch-combo<?php echo $logoComboVisibility('vector', 'image'); ?>">
        <label for="theme_logo_height"><?php _se('Logo height'); ?></label>
        <div class="c4"><input type="number" min="0" pattern="\d+" name="theme_logo_height" id="theme_logo_height" class="text-input" value="<?php echo Settings::get('theme_logo_height'); ?>" placeholder="<?php _se('No value'); ?>"></div>
        <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['theme_logo_height'] ?? ''; ?></div>
        <div class="input-below"><?php _se('Use this to set the logo height if needed.'); ?></div>
    </div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="favicon_image"><?php _se('Favicon image'); ?></label>
    <div class="transparent-canvas dark margin-bottom-10" style="max-width: 100px;"><img class="display-block" width="100%" src="<?php echo get_system_image_url(Settings::get('favicon_image')) . '?' . random_string(8); ?>"></div>
    <div class="c5 phablet-c1">
        <input id="favicon_image" name="favicon_image" type="file" accept="image/*">
    </div>
    <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['favicon_image'] ?? ''; ?></div>
    <div class="input-below"><?php _se('Favicon image. Image must have same width and height.'); ?></div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="image_load_max_filesize_mb"><?php _se('Image load max. filesize'); ?> (MB)</label>
    <div class="c2"><input type="number" min="0.1" step="0.1" pattern="\d+" name="image_load_max_filesize_mb" id="image_load_max_filesize_mb" class="text-input" value="<?php echo Handler::var('safe_post')['image_load_max_filesize_mb'] ?? Settings::get('image_load_max_filesize_mb'); ?>" placeholder="MB"></div>
    <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['image_load_max_filesize_mb'] ?? ''; ?></div>
    <div class="input-below"><?php _se('Images greater than this size will show a button to load full resolution image.'); ?></div>
</div>
<div class="input-label">
    <label for="theme_download_button"><?php _se('Enable download button'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="theme_download_button" id="theme_download_button" class="text-input">
            <?php
            echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Settings::get('theme_download_button')); ?>
        </select></div>
    <div class="input-below"><?php _se('Enable this if you want to show the image download button.'); ?></div>
</div>
<div class="input-label">
    <label for="theme_image_right_click"><?php _se('Enable right click on image'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="theme_image_right_click" id="theme_image_right_click" class="text-input">
            <?php
            echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Settings::get('theme_image_right_click')); ?>
        </select></div>
    <div class="input-below"><?php _se('Enable this if you want to allow right click on image viewer page.'); ?></div>
</div>
<div class="input-label">
    <label for="theme_show_exif_data"><?php _se('Enable show Exif data'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="theme_show_exif_data" id="theme_show_exif_data" class="text-input">
            <?php
            echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Settings::get('theme_show_exif_data')); ?>
        </select></div>
    <div class="input-below"><?php _se('Enable this if you want to show image Exif data.'); ?></div>
</div>
<div class="input-label">
    <label for="image_first_tab"><?php _se('%s first tab', _s('Image')); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="image_first_tab" id="image_first_tab" class="text-input">
            <?php
            echo get_select_options_html(
    [
                    'embeds' => _s('Embed codes'),
                    'about' => _s('About'),
                    'info' => _s('Info'),
                ],
    Settings::get('image_first_tab')
);
            ?>
        </select></div>
    <div class="input-below"><?php _se('Determine the first tab on %s page.', _s('image')); ?></div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="theme_show_social_share"><?php _se('Enable social share'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="theme_show_social_share" id="theme_show_social_share" class="text-input">
            <?php
            echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Settings::get('theme_show_social_share')); ?>
        </select></div>
    <div class="input-below"><?php _se('Enable this if you want to show social network buttons to share content.'); ?></div>
</div>
<div class="input-label">
    <label for="theme_show_embed_content_for"><?php _se('Enable embed codes (content)'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="theme_show_embed_content_for" id="theme_show_embed_content_for" class="text-input">
            <?php
            echo get_select_options_html([
                'all' => _s('Everybody'),
                'users' => _s('Users only'),
                'none' => _s('Disabled')
                ], Settings::get('theme_show_embed_content_for')); ?>
        </select></div>
    <div class="input-below"><?php _se('Enable this if you want to show embed codes for the content.'); ?></div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="theme_nsfw_upload_checkbox"><?php _se('Not safe content checkbox in uploader'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="theme_nsfw_upload_checkbox" id="theme_nsfw_upload_checkbox" class="text-input">
            <?php
            echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Settings::get('theme_nsfw_upload_checkbox')); ?>
        </select></div>
    <div class="input-below"><?php _se('Enable this if you want to show a checkbox to indicate not safe content upload.'); ?></div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="theme_custom_css_code"><?php _se('Custom CSS code'); ?></label>
    <div class="c12 phablet-c1"><textarea type="text" name="theme_custom_css_code" id="theme_custom_css_code" class="text-input r4" placeholder="<?php _se('Put your custom CSS code here. It will be placed as <style> just before the closing </head> tag.'); ?>"><?php echo Settings::get('theme_custom_css_code'); ?></textarea></div>
</div>
<div class="input-label">
    <label for="theme_custom_js_code"><?php _se('Custom JS code'); ?></label>
    <div class="c12 phablet-c1"><textarea type="text" name="theme_custom_js_code" id="theme_custom_js_code" class="text-input r4" placeholder="<?php _se('Put your custom JS code here. It will be placed as <script> just before the closing </head> tag.'); ?>"><?php echo Settings::get('theme_custom_js_code'); ?></textarea></div>
    <div class="input-below"><?php _se('Do not use %s markup here. This is for plain JS code, not for HTML script tags. If you use script tags here you will break your website.', '&lt;script&gt;'); ?></div>
</div>
