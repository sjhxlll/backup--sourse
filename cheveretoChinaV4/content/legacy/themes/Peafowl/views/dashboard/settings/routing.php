<?php

use Chevereto\Legacy\Classes\Settings;
use function Chevereto\Legacy\G\get_base_url;
use Chevereto\Legacy\G\Handler;
use function Chevereto\Legacy\get_select_options_html;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
}
echo read_the_docs_settings('routing', _s('Routing')); ?>
<div class="input-label">
    <div class="margin-bottom-20"><i class="fas fa-info-circle"></i> <?php _se('Routing allows you to customize default route binds on the fly. Only alphanumeric, hyphen and underscore characters are allowed.'); ?></div>
    <label for="route_user"><?php _se('%s routing', _s('User')); ?></label>
    <div class="c9 phablet-c1">
        <input type="text" name="route_user" id="route_user" class="text-input" value="<?php echo Settings::get('route_user'); ?>" required pattern="^[a-z0-9]+(?:-[a-z0-9]+)*$" placeholder="user">
    </div>
    <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['route_user'] ?? ''; ?></div>
    <div class="input-below"><?php _se('Routing for %s', get_base_url('user/&lt;id&gt;')); ?></div>
</div>
<div class="input-label">
    <label for="route_image"><?php _se('%s routing', _s('Image')); ?></label>
    <div class="c9 phablet-c1">
        <input type="text" name="route_image" id="route_image" class="text-input" value="<?php echo Settings::get('route_image'); ?>" required pattern="^[a-z0-9]+(?:-[a-z0-9]+)*$" placeholder="image">
    </div>
    <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['route_image'] ?? ''; ?></div>
    <div class="input-below"><?php _se('Routing for %s', get_base_url('image/&lt;id&gt;')); ?></div>
</div>
<div class="input-label">
    <label for="route_album"><?php _se('%s routing', _s('Album')); ?></label>
    <div class="c9 phablet-c1">
        <input type="text" name="route_album" id="route_album" class="text-input" value="<?php echo Settings::get('route_album'); ?>" required pattern="^[a-z0-9]+(?:-[a-z0-9]+)*$" placeholder="album">
    </div>
    <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['route_album'] ?? ''; ?></div>
    <div class="input-below"><?php _se('Routing for %s', get_base_url('album/&lt;id&gt;')); ?></div>
</div>
<hr class="line-separator">
<div class="input-label">
    <label for="root_route"><?php _se('%s routing', _s('Root')); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="root_route" id="root_route" class="text-input">
        <?php
                foreach ([
                    'user' => _s('User'),
                    'album' => _s('Album'),
                    'image' => _s('Image'),
                ] as $k => $v) {
                    $sel_root_route = $k == Settings::get('root_route')
                        ? ' selected' : '';
                    echo '<option value="' . $k . '"' . $sel_root_route . '>' . $v . '</option>' . "\n";
                } ?>
    </select></div>
    <div class="input-below"><?php _se('Determine which content to resolve on root route.'); ?></div>
</div>
<hr class="line-separator">
<?php foreach (['image', 'album'] as $v) {
                    ?>
<div class="input-label">
    <label for="seo_<?php echo $v; ?>_urls"><?php _se('SEO %s URLs', _s($v)); ?></label>
    <div class="c5 phablet-c1"><select type="text" name="seo_<?php echo $v; ?>_urls" id="seo_<?php echo $v; ?>_urls" class="text-input">
        <?php
                    echo get_select_options_html([1 => _s('Enabled'), 0 => _s('Disabled')], Settings::get('seo_' . $v . '_urls')); ?>
    </select></div>
    <div class="input-below"><?php _se('Enable this if you want to use SEO %s URLs.', _s($v)); ?></div>
</div>
<?php
                } ?>
