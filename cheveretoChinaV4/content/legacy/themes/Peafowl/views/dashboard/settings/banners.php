<?php

use Chevereto\Legacy\G\Handler;
use function Chevereto\Legacy\get_banner_code;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
}
echo read_the_docs_settings('banners', _s('Banners'));
$banners = Handler::var('banners');
$banner_keys = array_keys($banners);
$last_banner = end($banner_keys);
foreach ($banners as $k => $group) { ?>
    <h3 class="margin-top-20"><?php echo $group['label']; ?></h3>
    <?php
        foreach ($group['placements'] as $id => $banner) { ?>
        <div class="input-label">
            <label for="<?php echo $id; ?>"><?php echo $banner['label']; ?></label>
            <div class="c12 phablet-c1"><textarea type="text" id="<?php echo $id; ?>" name="<?php echo $id; ?>" class="text-input r3" placeholder="<?php echo $banner['placeholder'] ?? ''; ?>" </textarea><?php echo get_banner_code($id); ?> </textarea> </div> <?php if (isset($banner['hint'])) { ?> <div class="input-below"><?php echo $banner['hint']; ?></div>
<?php } ?>
</div>
<?php
         }
    if ($k !== $last_banner) { ?>
<hr class="line-separator">
<?php
    } ?>
<?php
} ?>
