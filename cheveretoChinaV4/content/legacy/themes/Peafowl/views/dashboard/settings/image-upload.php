<?php

use Chevereto\Legacy\Classes\Image;
use Chevereto\Legacy\Classes\Settings;
use Chevereto\Legacy\Classes\Upload;
use function Chevereto\Legacy\G\bytes_to_mb;
use function Chevereto\Legacy\G\format_bytes;
use Chevereto\Legacy\G\Handler;
use function Chevereto\Legacy\G\random_string;
use function Chevereto\Legacy\get_checkbox_html;
use function Chevereto\Legacy\get_select_options_html;
use function Chevereto\Legacy\get_system_image_url;
use function Chevereto\Legacy\getSetting;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
}
echo read_the_docs_settings('image-upload', _s('Image upload')); ?>
<div class="input-label">
    <label><?php _se('Enabled image formats'); ?></label>
    <div class="checkbox-label">
        <ul class="c20 phablet-c1">
        <?php
            foreach (Upload::getAvailableImageFormats() as $k) {
                $isFailing = in_array($k, IMAGE_FORMATS_FAILING);
                echo strtr('<li class="c5 display-inline-block margin-right-10"><label class="display-block" for="image_format_enable[%k]" %tip> <input type="checkbox" name="image_format_enable[]" id="image_format_enable[%k]" value="%k" %checked %disabled>%K</label></li>', [
                    '%k' => $k,
                    '%K' => strtoupper($k),
                    '%checked' => (in_array($k, Upload::getEnabledImageFormats()) ? 'checked' : ''),
                    '%disabled' => $isFailing ? 'disabled' : '',
                    '%tip' => $isFailing ? 'title="' . _s('Unsupported in your server') . '" rel="tooltip"' : ''                                            ]);
            } ?>
        </ul>
        <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['upload_enabled_image_formats'] ?? ''; ?></div>
        <p class="margin-top-20"><i class="fas fa-check-square"></i> <?php _se("Only checked image formats will be allowed to be uploaded."); ?></p>
    </div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="enable_uploads"><?php _se('Enable uploads'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="enable_uploads" id="enable_uploads" class="text-input">
            <?php
            echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Settings::get('enable_uploads')); ?>
        </select></div>
    <div class="input-below"><?php _se("Enable this if you want to allow image uploads. This setting doesn't affect administrators."); ?></div>
</div>
<div class="input-label">
    <label for="enable_uploads_url"><?php _se('Enable uploads'); ?> (URL)</label>
    <div class="c5 phablet-c1">
        <select type="text" name="enable_uploads_url" id="enable_uploads_url" class="text-input">
            <?php
            echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Settings::get('enable_uploads_url')); ?>
        </select></div>
        <div class="input-below"><?php _se("Enable this if you want to allow image upload from URLs."); ?></div>
        <div class="input-below"><span class="highlight padding-5 display-inline-block"><i class="fas fa-exclamation-triangle"></i> <?php _se("Note that enabling this will expose your server IP."); ?> <?php _se("This feature is available only for administrators."); ?></span></div>
</div>
<div class="input-label">
    <label for="upload_gui"><?php _se('Upload user interface'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="upload_gui" id="upload_gui" class="text-input">
            <?php
            echo get_select_options_html(['js' => _s('On-page container'), 'page' => '/upload ' . _s('route')], Settings::get('upload_gui')); ?>
        </select></div>
</div>
<div class="input-label">
    <label for="guest_uploads"><?php _se('Guest uploads'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="guest_uploads" id="guest_uploads" class="text-input" <?php if (getSetting('website_mode') == 'personal') {
                echo ' disabled';
            } ?>>
            <?php
            echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Settings::get('guest_uploads')); ?>
        </select></div>
    <div class="input-below"><?php _se('Enable this if you want to allow non registered users to upload.'); ?></div>
    <?php personal_mode_warning(); ?>
</div>
<div class="input-label">
    <label for="moderate_uploads"><?php _se('Moderate uploads'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="moderate_uploads" id="moderate_uploads" class="text-input" <?php if (getSetting('website_mode') == 'personal') {
                echo ' disabled';
            } ?>>
            <?php
            echo get_select_options_html([
                    '' => _s('Disabled'),
                    'guest' => _s('Guest'),
                    'all' => _s('All')
                ], Settings::get('moderate_uploads')); ?>
        </select></div>
    <div class="input-below"><?php _se('Enable this to moderate incoming uploads. Target content will require moderation for approval.'); ?></div>
    <?php personal_mode_warning(); ?>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="theme_show_embed_uploader"><?php _se('Enable embed codes (uploader)'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="theme_show_embed_uploader" id="theme_show_embed_uploader" class="text-input">
            <?php
            echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Settings::get('theme_show_embed_uploader')); ?>
        </select></div>
    <div class="input-below"><?php _se('Enable this if you want to show embed codes when upload gets completed.'); ?></div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="upload_threads"><?php _se('Upload threads'); ?></label>
    <div class="c2"><input type="number" min="1" max="8" pattern="\d+" name="upload_threads" id="upload_threads" class="text-input" value="<?php echo Settings::get('upload_threads'); ?>" placeholder="2" required></div>
    <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['upload_threads'] ?? ''; ?></div>
    <div class="input-below"><?php _se('Number of simultaneous upload threads (parallel uploads)'); ?></div>
</div>
<div class="input-label">
    <label for="enable_redirect_single_upload"><?php _se('Redirect on single upload'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="enable_redirect_single_upload" id="enable_redirect_single_upload" class="text-input">
            <?php
            echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Settings::get('enable_redirect_single_upload')); ?>
        </select></div>
    <div class="input-below"><?php _se('Enable this if you want to redirect to image page on single upload.'); ?></div>
</div>
<div class="input-label">
    <label for="enable_duplicate_uploads"><?php _se('Enable duplicate uploads'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="enable_duplicate_uploads" id="enable_duplicate_uploads" class="text-input">
            <?php
            echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Settings::get('enable_duplicate_uploads')); ?>
        </select></div>
    <div class="input-below"><?php _se("Enable this if you want to allow duplicate uploads from the same IP within 24hrs. This setting doesn't affect administrators."); ?></div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="enable_expirable_uploads"><?php _se('Enable expirable uploads'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="enable_expirable_uploads" id="enable_expirable_uploads" class="text-input">
            <?php
            echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Settings::get('enable_expirable_uploads')); ?>
        </select></div>
    <div class="input-below"><?php _se('Enable this if you want to allow uploads with an automatic delete option.'); ?></div>
</div>
<div class="input-label">
    <label for="auto_delete_guest_uploads"><?php _se('Auto delete guest uploads'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="auto_delete_guest_uploads" id="auto_delete_guest_uploads" class="text-input">
            <?php
            echo get_select_options_html(Image::getAvailableExpirations(), Settings::get('auto_delete_guest_uploads')); ?>
        </select></div>
    <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['auto_delete_guest_uploads'] ?? ''; ?></div>
    <div class="input-below"><?php _se('Enable this if you want to force guest uploads to be auto deleted after certain time.'); ?></div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="upload_max_image_width" class="display-block-forced"><?php _se('Maximum image size'); ?></label>
    <div class="c5 overflow-auto clear-both">
        <div class="c2 float-left">
            <input type="number" min="0" pattern="\d+" name="upload_max_image_width" id="upload_max_image_width" class="text-input" value="<?php echo Handler::var('safe_post')['upload_max_image_width'] ?? Settings::get('upload_max_image_width'); ?>" placeholder="<?php echo  Settings::getDefault('upload_max_image_width'); ?>" rel="tooltip" data-tiptip="top" title="<?php _se('Width'); ?>" required>
        </div>
        <div class="c2 float-left margin-left-10">
            <input type="number" min="0" pattern="\d+" name="upload_max_image_height" id="upload_max_image_height" class="text-input" value="<?php echo Handler::var('safe_post')['upload_max_image_height'] ?? Settings::get('upload_max_image_height'); ?>" placeholder="<?php echo  Settings::getDefault('upload_max_image_height'); ?>" rel="tooltip" data-tiptip="top" title="<?php _se('Height'); ?>" required>
        </div>
    </div>
    <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['upload_max_image_width'] ?? ''; ?></div>
    <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['upload_max_image_height'] ?? ''; ?></div>
    <div class="input-below"><?php _se("Images greater than this size will get automatically downsized. Use zero (0) to don't set a limit."); ?></div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="upload_image_exif"><?php _se('Image Exif data'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="upload_image_exif" id="upload_image_exif" class="text-input">
            <?php
            echo get_select_options_html([
                1 => _s('Keep'),
                0 => _s('Remove'),
            ], Settings::get('upload_image_exif')); ?>
        </select></div>
    <div class="input-below"><?php _se('Select the default setting for image <a %s>Exif data</a> on upload.', 'rel="external" href="https://en.wikipedia.org/wiki/Exchangeable_image_file_format" target="_blank"'); ?></div>
</div>
<div class="input-label">
    <label for="upload_image_exif_user_setting"><?php _se('Image Exif data (user setting)'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="upload_image_exif_user_setting" id="upload_image_exif_user_setting" class="text-input">
            <?php
            echo get_select_options_html([
                1 => _s('Enabled'),
                0 => _s('Disabled'),
            ], Settings::get('upload_image_exif_user_setting')); ?>
        </select></div>
    <div class="input-below"><?php _se('Enable this if you want to allow each user to configure how image Exif data will be handled.'); ?></div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="upload_max_filesize_mb"><?php _se('Maximum upload file size'); ?> [MB]</label>
    <div class="c2"><input type="number" min="0.1" step="0.1" max="<?php echo bytes_to_mb(Settings::get('true_upload_max_filesize')); ?>" pattern="\d+" name="upload_max_filesize_mb" id="upload_max_filesize_mb" class="text-input" value="<?php echo Handler::var('safe_post')['upload_max_filesize_mb'] ?? Settings::get('upload_max_filesize_mb'); ?>" placeholder="MB" required></div>
    <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['upload_max_filesize_mb'] ?? ''; ?></div>
    <div class="input-below"><?php _se('Maximum size allowed by server is %s. This limit is capped by %u and %p (%f values).', ['%s' => format_bytes(Settings::get('true_upload_max_filesize')), '%u' => '<code>upload_max_filesize = ' . ini_get('upload_max_filesize') . '</code>', '%p' => '<code>post_max_size = ' . ini_get('post_max_size') . '</code>', '%f' => 'php.ini']); ?></div>
</div>
<div class="input-label">
    <label for="upload_max_filesize_mb_guest"><?php _se('Maximum upload file size'); ?> (<?php _se('guests'); ?>)</label>
    <div class="c2"><input type="number" min="0.1" step="0.1" max="<?php echo bytes_to_mb(Settings::get('true_upload_max_filesize')); ?>" pattern="\d+" name="upload_max_filesize_mb_guest" id="upload_max_filesize_mb_guest" class="text-input" value="<?php echo Handler::var('safe_post')['upload_max_filesize_mb_guest'] ?? Settings::get('upload_max_filesize_mb_guest'); ?>" placeholder="MB" required></div>
    <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['upload_max_filesize_mb_guest'] ?? ''; ?></div>
    <div class="input-below"><?php _se('Same as "%s" but for guests.', _s('Maximum upload file size')); ?></div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="upload_image_path"><?php _se('Image path'); ?></label>
    <div class="c9 phablet-c1"><input type="text" name="upload_image_path" id="upload_image_path" class="text-input" value="<?php echo Handler::var('safe_post')['upload_image_path'] ?? Settings::get('upload_image_path'); ?>" placeholder="<?php _se('Relative to Chevereto root'); ?>" required></div>
    <span class="input-warning red-warning"><?php echo Handler::var('input_errors')['upload_image_path'] ?? ''; ?></span>
    <div class="input-below"><?php _se('Where to store the images? Relative to Chevereto root.'); ?></div>
</div>
<div class="input-label">
    <label for="upload_storage_mode"><?php _se('Storage mode'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="upload_storage_mode" id="upload_storage_mode" class="text-input">
            <?php
            echo get_select_options_html(['datefolder' => _s('Datefolders'), 'direct' => _s('Direct')], Settings::get('upload_storage_mode')); ?>
        </select></div>
    <div class="input-below"><?php _se('Datefolders creates %s structure', date('/Y/m/d/')); ?></div>
</div>
<div class="input-label">
    <label for="upload_filenaming"><?php _se('File naming method'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="upload_filenaming" id="upload_filenaming" class="text-input">
            <?php
            echo get_select_options_html(['original' => _s('Original'), 'random' => _s('Random'), 'mixed' => _s('Mix original + random'), 'id' => 'ID'], Settings::get('upload_filenaming')); ?>
        </select></div>
    <div class="input-below"><?php _se('"Original" will try to keep the image source name while "Random" will generate a random name. "ID" will name the image just like the image ID.'); ?></div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="upload_thumb_width" class="display-block-forced"><?php _se('Thumb size'); ?></label>
    <div class="c5 overflow-auto clear-both">
        <div class="c2 float-left">
            <input type="number" min="16" pattern="\d+" name="upload_thumb_width" id="upload_thumb_width" class="text-input" value="<?php echo Handler::var('safe_post')['upload_thumb_width'] ?? Settings::get('upload_thumb_width'); ?>" placeholder="<?php echo  Settings::getDefault('upload_thumb_width'); ?>" rel="tooltip" data-tiptip="top" title="<?php _se('Width'); ?>" required>
        </div>
        <div class="c2 float-left margin-left-10">
            <input type="number" min="16" pattern="\d+" name="upload_thumb_height" id="upload_thumb_height" class="text-input" value="<?php echo Handler::var('safe_post')['upload_thumb_height'] ?? Settings::get('upload_thumb_height'); ?>" placeholder="<?php echo  Settings::getDefault('upload_thumb_height'); ?>" rel="tooltip" data-tiptip="top" title="<?php _se('Height'); ?>" required>
        </div>
    </div>
    <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['upload_thumb_width'] ?? ''; ?></div>
    <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['upload_thumb_height'] ?? ''; ?></div>
    <div class="input-below"><?php _se('Thumbnails will be fixed to this size.'); ?></div>
</div>
<div class="input-label">
    <label for="upload_medium_fixed_dimension"><?php _se('Medium image fixed dimension'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="upload_medium_fixed_dimension" id="upload_medium_fixed_dimension" class="text-input">
            <?php
            echo get_select_options_html(['width' => _s('Width'), 'height' => _s('Height')], Settings::get('upload_medium_fixed_dimension')); ?>
        </select></div>
    <div class="input-below"><?php _se('Medium sized images will be fixed to this dimension. For example, if you select "width" that dimension will be fixed and image height will be automatically calculated.'); ?></div>
</div>
<div class="input-label">
    <label for="upload_medium_size"><?php _se('Medium image fixed size'); ?></label>
    <div class="c2">
        <input type="number" min="16" pattern="\d+" name="upload_medium_size" id="upload_medium_size" class="text-input" value="<?php echo Handler::var('safe_post')['upload_medium_size'] ?? Settings::get('upload_medium_size'); ?>" placeholder="<?php echo Settings::getDefault('upload_medium_size'); ?>" required>
    </div>
    <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['upload_medium_size'] ?? ''; ?></div>
    <div class="input-below"><?php _se('Width or height will be automatically calculated.'); ?></div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="watermark_enable"><?php _se('Watermarks'); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="watermark_enable" id="watermark_enable" class="text-input" data-combo="watermark-combo">
            <?php
            echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Handler::var('safe_post') ? Handler::var('safe_post')['watermark_enable'] : Settings::get('watermark_enable')); ?>
        </select></div>
    <div class="input-below input-warning red-warning clear-both"><?php echo Handler::var('input_errors')['watermark_enable'] ?? ''; ?></div>
    <div class="input-below"><?php _se('Enable this to put a logo or anything you want in image uploads.'); ?></div>
</div>
<div id="watermark-combo">
    <div data-combo-value="1" class="switch-combo phablet-c1<?php if ((Handler::var('safe_post') ? Handler::var('safe_post')['watermark_enable'] : Settings::get('watermark_enable')) != 1) {
                echo ' soft-hidden';
            } ?>">
        <div class="input-label">
            <label for="watermark_checkboxes"><?php _se('Watermark user toggles'); ?></label>
            <?php echo get_checkbox_html([
                'name' => 'watermark_enable_guest',
                'label' => _s('Enable watermark on guest uploads'),
                'checked' => ((bool) (Handler::var('safe_post') ? Handler::var('safe_post')['watermark_enable_guest'] : Settings::get('watermark_enable_guest'))),
            ]); ?>
            <?php echo get_checkbox_html([
                'name' => 'watermark_enable_user',
                'label' => _s('Enable watermark on user uploads'),
                'checked' => ((bool) (Handler::var('safe_post') ? Handler::var('safe_post')['watermark_enable_user'] : Settings::get('watermark_enable_user'))),
            ]); ?>
            <?php echo get_checkbox_html([
                'name' => 'watermark_enable_admin',
                'label' => _s('Enable watermark on admin uploads'),
                'checked' => ((bool) (Handler::var('safe_post') ? Handler::var('safe_post')['watermark_enable_admin'] : Settings::get('watermark_enable_admin'))),
            ]); ?>
        </div>
        <div class="input-label">
            <label for="watermark_checkboxes"><?php _se('Watermark file toggles'); ?></label>
            <?php echo get_checkbox_html([
                'name' => 'watermark_enable_file_gif',
                'label' => _s('Enable watermark on GIF image uploads'),
                'checked' => ((bool) (Handler::var('safe_post') ? Handler::var('safe_post')['watermark_enable_file_gif'] : Settings::get('watermark_enable_file_gif'))),
            ]); ?>
            <p class="highlight padding-5 display-inline-block"><i class="fas fa-exclamation-triangle"></i> <?php _se("Animated images won't be watermarked."); ?></p>
        </div>
        <div class="input-label">
            <label for="watermark_target_min_width" class="display-block-forced"><?php _se('Minimum image size needed to apply watermark'); ?></label>
            <div class="c5 overflow-auto clear-both">
                <div class="c2 float-left">
                    <input type="number" min="0" pattern="\d+" name="watermark_target_min_width" id="watermark_target_min_width" class="text-input" value="<?php echo Handler::var('safe_post')['watermark_target_min_width'] ?? Settings::get('watermark_target_min_width'); ?>" placeholder="<?php echo  Settings::getDefault('watermark_target_min_width'); ?>" rel="tooltip" data-tiptip="top" title="<?php _se('Width'); ?>" required>
                </div>
                <div class="c2 float-left margin-left-10">
                    <input type="number" min="0" pattern="\d+" name="watermark_target_min_height" id="watermark_target_min_height" class="text-input" value="<?php echo Handler::var('safe_post')['watermark_target_min_height'] ?? Settings::get('watermark_target_min_height'); ?>" placeholder="<?php echo  Settings::getDefault('watermark_target_min_height'); ?>" rel="tooltip" data-tiptip="top" title="<?php _se('Height'); ?>" required>
                </div>
            </div>
            <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['watermark_target_min_width'] ?? ''; ?></div>
            <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['watermark_target_min_height'] ?? ''; ?></div>
            <div class="input-below"><?php _se("Images smaller than this won't be watermarked. Use zero (0) to don't set a minimum image size limit."); ?></div>
        </div>
        <div class="input-label">
            <?php Image::watermarkFromDb(); ?>
            <label for="watermark_image"><?php _se('Watermark image'); ?></label>
            <div class="transparent-canvas dark margin-bottom-10" style="max-width: 200px;"><img class="display-block" width="100%" src="<?php echo get_system_image_url(Settings::get('watermark_image')) . '?' . random_string(8); ?>"></div>
            <div class="c5 phablet-c1">
                <input id="watermark_image" name="watermark_image" type="file" accept="image/png">
            </div>
            <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['watermark_image'] ?? ''; ?></div>
            <div class="input-below"><?php _se('You will get best results with plain logos with drop shadow. You can use a large image if the file size is not that big (recommended max. is 16KB). Must be a PNG.'); ?></div>
        </div>
        <div class="input-label">
            <label for="watermark_position"><?php _se('Watermark position'); ?></label>
            <div class="c5 phablet-c1"><select type="text" name="watermark_position" id="watermark_position" class="text-input">
                    <?php
                    echo get_select_options_html(
                [
                            'left top' => _s('left top'),
                            'left center' => _s('left center'),
                            'left bottom' => _s('left bottom'),
                            'center top' => _s('center top'),
                            'center center' => _s('center center'),
                            'center bottom' => _s('center bottom'),
                            'right top' => _s('right top'),
                            'right center' => _s('right center'),
                            'right bottom' => _s('right bottom'),
                        ],
                Handler::var('safe_post')['watermark_position']
                            ?? Settings::get('watermark_position')
            ); ?>
                </select></div>
            <div class="input-below input-warning red-warning clear-both"><?php echo Handler::var('input_errors')['watermark_position'] ?? ''; ?></div>
            <div class="input-below"><?php _se('Relative position of the watermark image. First horizontal align then vertical align.'); ?></div>
        </div>
        <div class="input-label">
            <label for="watermark_percentage"><?php _se('Watermark percentage'); ?></label>
            <div class="c2">
                <input type="number" min="1" max="100" pattern="\d+" name="watermark_percentage" id="watermark_percentage" class="text-input" value="<?php echo Handler::var('safe_post')['watermark_percentage'] ?? Settings::get('watermark_percentage'); ?>" placeholder="<?php echo Settings::getDefault('watermark_percentage'); ?>" required>
            </div>
            <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['watermark_percentage'] ?? ''; ?></div>
            <div class="input-below"><?php _se('Watermark percentual size relative to the target image area. Values 1 to 100.'); ?></div>
        </div>
        <div class="input-label">
            <label for="watermark_margin"><?php _se('Watermark margin'); ?></label>
            <div class="c2">
                <input type="number" min="0" pattern="\d+" name="watermark_margin" id="watermark_margin" class="text-input" value="<?php echo Handler::var('safe_post')['watermark_margin'] ?? Settings::get('watermark_margin'); ?>" placeholder="<?php echo Settings::getDefault('watermark_margin'); ?>" required>
            </div>
            <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['watermark_margin'] ?? ''; ?></div>
            <div class="input-below"><?php _se('Margin from the border of the image to the watermark image.'); ?></div>
        </div>
        <div class="input-label">
            <label for="watermark_opacity"><?php _se('Watermark opacity'); ?></label>
            <div class="c2">
                <input type="number" min="1" max="100" pattern="\d+" name="watermark_opacity" id="watermark_opacity" class="text-input" value="<?php echo Handler::var('safe_post')['watermark_opacity'] ?? Settings::get('watermark_opacity'); ?>" placeholder="<?php echo Settings::getDefault('watermark_opacity'); ?>" required>
            </div>
            <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['watermark_opacity'] ?? ''; ?></div>
            <div class="input-below"><?php _se('Opacity of the watermark in the final watermarked image. Values 0 to 100.'); ?></div>
        </div>
    </div>
</div>
