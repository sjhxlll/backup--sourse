<?php

use Chevereto\Config\Config;
use Chevereto\Legacy\Classes\Page;
use function Chevereto\Legacy\G\absolute_to_relative;
use function Chevereto\Legacy\G\get_base_url;
use Chevereto\Legacy\G\Handler;
use function Chevereto\Legacy\G\is_writable;
use function Chevereto\Legacy\G\str_replace_last;
use function Chevereto\Legacy\G\truncate;
use function Chevereto\Legacy\get_select_options_html;
use function Chevereto\Vars\post;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
}
switch (Handler::var('settings_pages')['doing']) {
    case 'display':
        break;
    case 'add':
        $page = [];
        $page_db = [];

        break;
    case 'edit':
        $page = Handler::var('page');
        global $page_db;
        $page_db = [];
        foreach ($page as $k => $v) {
            $page_db['page_' . $k] = $v;
        }

        break;
}
function get_page_val($key, $from = 'POST')
{
    global $page_db;
    if (empty($key)) {
        return null;
    }
    if ($from == 'POST' and post() !== []) {
        return Handler::var('safe_post')[$key];
    } else {
        switch (Handler::var('settings_pages')['doing']) {
            case 'add':
                return null;
            case 'edit':
                $return = $page_db[$key] ?? null;
                if (in_array($key, ['page_file_path_absolute', 'page_file_path'])) {
                    if (!Config::enabled()->phpPages()) {
                        $return = str_replace_last('.php', '.html', $return ?? '');
                    }
                }

                return $return;
        }
    }
} ?>
<?php if (isset(Handler::var('settings_pages')['title'])) {
    ?>
    <h3><?php echo Handler::var('settings_pages')['title']; ?></h3>
<?php
} ?>
<?php if (Handler::var('settings_pages')['doing'] !== 'listing') {
        ?>
    <div class="input-label">
        <label for="page_title"><?php _se('Title'); ?></label>
        <div class="c9 phablet-c1"><input type="text" name="page_title" id="page_title" class="text-input" value="<?php echo get_page_val('page_title'); ?>" required placeholder="<?php _se('Page title'); ?>"></div>
        <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['page_title'] ?? ''; ?></div>
    </div>
    <div class="input-label">
        <label for="page_is_active"><?php _se('Page status'); ?></label>
        <div class="c5 phablet-c1"><select type="text" name="page_is_active" id="page_is_active" class="text-input">
                <?php
                echo get_select_options_html([1 => _s('Active page'), 0 => _s('Inactive page (%s)', '404')], (int) get_page_val('page_is_active')); ?>
            </select></div>
        <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['page_is_active'] ?? ''; ?></div>
        <div class="input-below"><?php _se('Only active pages will be accessible.'); ?></div>
    </div>
    <div class="input-label">
        <label for="page_type"><?php _se('Type'); ?></label>
        <div class="c5 phablet-c1"><select type="text" name="page_type" id="page_type" class="text-input" data-combo="page-type-combo">
                <?php
                echo get_select_options_html(['internal' => _s('Internal'), 'link' => _s('Link')], get_page_val('page_type')); ?>
            </select></div>
        <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['page_type'] ?? ''; ?></div>
    </div>
    <div id="page-type-combo">
        <?php
        $pagesAll = Page::getAll();
        $internals = [
            'tos' => _s('Terms of service'),
            'privacy' => _s('Privacy'),
            'contact' => _s('Contact'),
        ];
        $printInternals = [
            null => _s('Extra page'),
        ];
        $takenInternals = [];
        if ($pagesAll) {
            foreach ($pagesAll as $k => $v) {
                if ($v['internal'] && $internals[$v['internal']]) {
                    $takenInternals[] = $v['internal'];
                    if (Handler::hasVar('page') && Handler::var('page')['id'] == $v['id']) {
                        $printInternals[$v['internal']] = $internals[$v['internal']];
                    }
                }
            }
        }
        foreach ($internals as $k => $v) {
            if (in_array($k, $takenInternals) == false) {
                $printInternals[$k] = $v;
            }
        }
        $page_internal_combo_visible = Handler::var('settings_pages')['doing'] == 'edit' ? (get_page_val('page_type') == 'internal') : true; ?>
        <div data-combo-value="internal" class="switch-combo phablet-c1<?php if (!$page_internal_combo_visible) {
            echo ' soft-hidden';
        } ?>">
            <div class="input-label">
                <label for="page_internal"><?php _se('Internal page type'); ?></label>
                <div class="c5 phablet-c1"><select type="text" name="page_internal" id="page_internal" class="text-input">
                        <?php
                        echo get_select_options_html($printInternals, get_page_val('page_internal') ?: null); ?>
                    </select></div>
                <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['page_internal'] ?? ''; ?></div>
                <div class="input-below"><?php _se('You can have multiple extra pages, but only one of the other special internal types.'); ?></div>
            </div>
            <div class="input-label">
                <label for="page_is_link_visible"><?php _se('Page visibility'); ?></label>
                <div class="c5 phablet-c1"><select type="text" name="page_is_link_visible" id="page_is_link_visible" class="text-input" <?php echo $page_internal_combo_visible ? 'required' : 'data-required'; ?>>
                        <?php
                        echo get_select_options_html([1 => _s('Visible page'), 0 => _s('Hidden page')], (int) get_page_val('page_is_link_visible')); ?>
                    </select></div>
                <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['page_is_link_visible'] ?? ''; ?></div>
                <div class="input-below"><?php _se("Hidden pages won't be show in system menus, but anyone can access to it with the link."); ?></div>
            </div>
            <div class="input-label">
                <label for="page_url_key"><?php _se('URL key'); ?></label>
                <div class="c9 phablet-c1"><input type="text" name="page_url_key" id="page_url_key" class="text-input" value="<?php echo get_page_val('page_url_key'); ?>" pattern="^[\w]([\w-]*[\w])?(\/[\w]([\w-]*[\w])?)*$" rel="tooltip" data-tiptip="right" placeholder="url-key" title="<?php _se('Only alphanumerics, hyphens and forward slash'); ?>" <?php echo $page_internal_combo_visible ? 'required' : 'data-required'; ?>></div>
                <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['page_url_key'] ?? ''; ?></div>
                <div class="input-below"><?php echo get_base_url('pages/url-key'); ?></div>
            </div>
            <div class="input-label">
                <label for="page_file_path"><?php _se('File path'); ?></label>
                <div class="c9 phablet-c1"><input type="text" name="page_file_path" id="page_file_path" class="text-input" value="<?php echo get_page_val('page_file_path'); ?>" pattern="^[\w]([\w-]*[\w])?(\/[\w]([\w-]*[\w])?)*\.<?php echo !Config::enabled()->phpPages() ? 'html' : '(php|html)'; ?>$" <?php echo $page_internal_combo_visible ? 'required' : 'data-required'; ?> placeholder="page.<?php echo !Config::enabled()->phpPages() ? 'html' : '(php|html)'; ?>"></div>
                <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['page_file_path'] ?? ''; ?></div>
                <div class="input-below"><?php
                $pages_visible_path = absolute_to_relative(PATH_PUBLIC_CONTENT_PAGES);
        _se('A %f file relative to %s', ['%f' => !Config::enabled()->phpPages() ? 'HTML' : 'PHP', '%s' => $pages_visible_path]); ?></div>
            </div>
            <div class="input-label">
                <label for="page_keywords"><?php _se('Meta keywords'); ?> <span class="optional"><?php _se('optional'); ?></span></label>
                <div class="c9 phablet-c1"><input type="text" name="page_keywords" id="page_keywords" class="text-input" value="<?php echo get_page_val('page_keywords'); ?>"></div>
                <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['page_keywords'] ?? ''; ?></div>
            </div>
            <div class="input-label">
                <label for="page_description"><?php _se('Meta description'); ?> <span class="optional"><?php _se('optional'); ?></span></label>
                <div class="c9 phablet-c1"><textarea type="text" name="page_description" id="page_description" class="text-input resize-vertical r2"><?php echo get_page_val('page_description'); ?></textarea></div>
                <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['page_description'] ?? ''; ?></div>
            </div>
            <div class="input-label">
                <label for="page_code"><?php _se('Source code'); ?></label>
                <?php
                if (Handler::var('settings_pages')['doing'] == 'add') {
                    $page_write_path = Page::getPath();
                    $no_write_msg = _s('No write permission in %s path you will need to add this file using an external editor.', $page_write_path);
                } else { // edit
                    $page_write_path = get_page_val('page_file_path_absolute');
                    if (!get_page_val('page_file_path', 'DB') or !file_exists($page_write_path)) {
                        $page_write_path = Page::getPath();
                    }
                    $no_write_msg = _s('No write permission in %s you will need to edit the contents of this file using an external editor.', $page_write_path);
                }
        $is_page_writable = is_writable($page_write_path);
        $page_path = get_page_val('page_file_path', 'DB');
        $page_path_absolute = get_page_val('page_file_path_absolute', 'DB'); ?>
                <?php if ($page_path_absolute) {
            ?>
                    <p class="margin-bottom-10"><?php echo absolute_to_relative($page_path ? $page_path_absolute : _s('Taken from: %s', $page_path_absolute)); ?></p>
                <?php
        } ?>
                <?php if (!$is_page_writable) {
            ?>
                    <p class="highlight margin-bottom-10 padding-5"><?php echo $no_write_msg; ?></p>
                <?php
        } ?>
                <textarea type="text" name="page_code" id="page_code" class="text-input resize-vertical r14" <?php if (!$is_page_writable) {
            echo ' readonly';
        } ?>><?php echo (isset($page_path_absolute) && is_readable($page_path_absolute)) ? htmlspecialchars(file_get_contents($page_path_absolute)) : ''; ?></textarea>
                <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['page_code'] ?? ''; ?></div>
            </div>
        </div>
        <div data-combo-value="link" class="switch-combo phablet-c1<?php if (get_page_val('page_type') !== 'link') {
            echo ' soft-hidden';
        } ?>">
            <div class="input-label">
                <label for="page_link_url"><?php _se('Link URL'); ?></label>
                <div class="c9 phablet-c1"><input type="url" name="page_link_url" id="page_link_url" class="text-input" value="<?php echo get_page_val('page_link_url'); ?>" <?php echo get_page_val('page_type') == 'link' ? 'required' : 'data-required'; ?>></div>
                <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['page_link_url'] ?? ''; ?></div>
            </div>
        </div>
    </div>
    <div class="input-label">
        <label for="page_attr_target"><?php _se('Link target attribute'); ?></label>
        <div class="c5 phablet-c1"><select type="text" name="page_attr_target" id="page_attr_target" class="text-input">
                <?php
                echo get_select_options_html(['_self' => '_self', '_blank' => '_blank'], get_page_val('page_attr_target')); ?>
            </select></div>
        <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['page_attr_target'] ?? ''; ?></div>
        <div class="input-below"><?php _se('Select %s to open the page or link in a new window.', '"_blank"'); ?></div>
    </div>
    <div class="input-label">
        <label for="page_attr_rel"><?php _se('Link rel attribute'); ?> <span class="optional"><?php _se('optional'); ?></span></label>
        <div class="c9 phablet-c1"><input type="text" name="page_attr_rel" id="page_attr_rel" class="text-input" pattern="[\w\s\-]+" value="<?php echo get_page_val('page_attr_rel'); ?>" rel="tooltip" data-tiptip="right" title="<?php _se('Only alphanumerics, hyphens and whitespaces'); ?>"></div>
        <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['page_attr_rel'] ?? ''; ?></div>
        <div class="input-below"><?php _se('HTML %s attribute', '<a rel="external" href="http://www.w3schools.com/tags/att_a_rel.asp" target="_blank">rel</a>'); ?></div>
    </div>
    <div class="input-label">
        <label for="page_icon"><?php _se('Link icon'); ?> <span class="optional"><?php _se('optional'); ?></span></label>
        <div class="c9 phablet-c1"><input type="text" name="page_icon" id="page_icon" class="text-input" pattern="[\w\s\-]+" value="<?php echo get_page_val('page_icon'); ?>" rel="tooltip" data-tiptip="right" title="<?php _se('Only alphanumerics, hyphens and whitespaces'); ?>"></div>
        <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['page_icon'] ?? ''; ?></div>
        <div class="input-below"><?php _se('Check the <a %s>icon reference</a> for the complete list of supported icons.', 'rel="external" href="https://fontawesome.com/icons?d=gallery&p=2&m=free" target="_blank"'); ?></div>
    </div>
    <div class="input-label">
        <label for="page_sort_display"><?php _se('Sort order display'); ?> <span class="optional"><?php _se('optional'); ?></span></label>
        <div class="c3 phablet-c1"><input type="number" min="1" name="page_sort_display" id="page_sort_display" class="text-input" value="<?php echo get_page_val('page_sort_display'); ?>"></div>
        <div class="input-below input-warning red-warning"><?php echo Handler::var('input_errors')['page_sort_display'] ?? ''; ?></div>
        <div class="input-below"><?php _se('Page sort order display for menus and listings. Use "1" for top priority.'); ?></div>
    </div>
    <?php
    } else { // display
        if (Handler::var('pages') and count(Handler::var('pages')) > 0) {
            $auth_token = Handler::getAuthToken(); ?>
        <?php echo read_the_docs_settings('pages', _s('Pages')); ?>
        <ul data-content="dashboard-categories-list" class="tabbed-content-list table-li-hover table-li margin-top-20 margin-bottom-20">
            <li class="table-li-header phone-hide">
                <span class="c2 display-table-cell padding-right-10"><?php echo 'ID'; ?></span>
                <span class="c3 display-table-cell"><?php _se('Type'); ?></span>
                <span class="c5 display-table-cell padding-right-10"><?php _se('URL key'); ?></span>
                <span class="c13 display-table-cell padding-right-10"><?php _se('Title'); ?></span>
            </li>
            <?php
            foreach (Handler::var('pages') as $k => $v) {
                ?>
                <li>
                    <span class="c2 display-table-cell padding-right-10"><?php echo $v['id']; ?></span>
                    <span class="c3 display-table-cell padding-right-10 phone-hide"><?php echo $v['type_tr']; ?></span>
                    <span class="c5 display-table-cell padding-right-10"><?php echo $v['url_key'] ? truncate($v['url_key'], 25) : '--'; ?></span>
                    <span class="c13 display-table-cell padding-right-10"><a href="<?php echo get_base_url('dashboard/settings/pages/edit/' . $v['id']); ?>"><?php echo truncate($v['title'], 64); ?></a></span>
                    <span class="display-table-cell"><a class="btn btn-small default" href="<?php echo get_base_url('dashboard/settings/pages/delete/' . $v['id'] . '/?auth_token=' . $auth_token); ?>" data-confirm="<?php _se("Do you really want to delete the page ID %s? This can't be undone.", $v['id']); ?>"><i class="fas fa-trash-alt margin-right-5"></i><?php _se('Delete'); ?></a></span>
                </li>
            <?php
            } // for each page
            ?>
        </ul>
    <?php
        } else { // no pages
    ?>
        <div class="content-empty">
            <span class="icon fas fa-inbox"></span>
            <h2><?php _se("There's nothing to show here."); ?></h2>
        </div>
    <?php
    } ?>
<?php
    } // display
?>
