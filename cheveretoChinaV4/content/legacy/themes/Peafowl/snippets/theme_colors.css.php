<?php
use function Chevereto\Legacy\G\is_valid_hex_color;
use function Chevereto\Legacy\getSetting;

// @phpstan-ignore-next-line
if (!defined('ACCESS') || !ACCESS) {
    die('This file cannot be directly accessed.');
} ?>
<?php
    $default_color = '#00A7DA';
    $color = getSetting('theme_main_color');
    if (!is_string($color) || !is_valid_hex_color($color)) {
        $color = $default_color;
    }
?>
<style>
.palette-dark:root{
    --alertAccent: hsl(48, 89%, 50%);
    --alertBackground: hsl(52, 100%, 90%);
    --alertText: hsl(278, 22%, 10%);
    --bodyBackground: hsl(245, 9%, 18%);
    --bodyEmpty: hsl(245, 10%, 22%);
    --bodySeparator: var(--bodyEmpty);
    --bodyText: hsl(0, 0%, 80%);
    --bodyTextSubtle: hsl(245, 5%, 45%);
    --buttonAccentBackground: hsl(245, 10%, 10%);
    --buttonAccentHoverBackground: hsl(245, 10%, 5%);
    --buttonAccentHoverText: var(--bodyText);
    --buttonAccentText: var(--bodyText);
    --buttonDefaultBackground: var(--bodyEmpty);
    --buttonDefaultHoverBackground: hsl(245, 5%, 10%);
    --buttonDefaultHoverText: var(--colorAccent);
    --buttonDefaultText: var(--bodyText);
    --inputBackground:  hsl(245, 10%, 10%);
    --inputPlaceholderText: hsl(245, 5%, 50%);
    --inputText: var(--bodyText);
    --menuBackground: hsla(245, 10%, 8%, 80%);
    --menuItemHoverBackground: var(--colorAccent);
    --menuItemHoverText: #FFF;
    --menuItemText: var(--bodyText);
    --menuSeparator: var(--bodyBackground);
    --menuText: var(--bodyText);
    --modalBackground: var(--bodyBackground);
    --modalText: var(--bodyText);
    --topBarBackground: hsl(245, 9%, 18%);
    --topBarText: var(--bodyText);
    --viewerBackground: hsl(245, 5%, 12%);
}
/*     --topBarBackground: hsl(150, 25%, 70%);
 */
.palette-lush:root{
    --alertAccent: hsl(48, 89%, 50%);
    --alertBackground: hsl(52, 100%, 90%);
    --alertText: hsl(278, 22%, 10%);
    --bodyBackground: hsl(150, 25%, 94%);
    --bodyEmpty: hsl(150, 25%, 85%);
    --bodySeparator: var(--bodyEmpty);
    --bodyText: hsl(150, 25%, 16%);
    --bodyTextDisabled: hsl(150, 25%, 50%);
    --bodyTextSubtle: hsl(189, 6%, 45%);
    --buttonAccentBackground: hsl(150, 25%, 70%);
    --buttonAccentHoverBackground: hsl(150, 25%, 60%);
    --buttonAccentHoverText: var(--buttonAccentText);
    --buttonAccentText: hsl(150, 25%, 20%);
    --buttonDefaultBackground: var(--bodyBackground);
    --buttonDefaultBorder: hsl(150, 25%, 80%);
    --buttonDefaultHoverBackground: hsl(150, 25%, 85%);
    --buttonDefaultHoverBorder: hsl(150, 25%, 80%);
    --buttonDefaultHoverText: hsl(150, 25%, 20%);
    --buttonDefaultText: hsl(150, 25%, 20%);
    --colorAccent: hsl(150, 40%, 50%);
    --colorAccentStrong: hsl(150, 25%, 40%);
    --inputBackground:  hsl(0, 0%, 100%);
    --inputPlaceholderText: hsl(150, 25%, 40%, 0.2);
    --inputText: var(--bodyText);
    --linkText: var(--colorAccent);
    --menuBackground: hsla(150, 25%, 90%, 80%);
    --menuItemHoverBackground: var(--colorAccent);
    --menuItemHoverText: #FFF;
    --menuItemText: var(--bodyText);
    --menuSeparator: hsl(150, 25%, 72%);
    --menuText: var(--bodyText);
    --modalBackground: var(--bodyBackground);
    --modalText: var(--bodyText);
    --topBarBackground: var(--bodyBackground);
    --topBarText: var(--bodyText);
    --listItemText: var(--bodyBackground);
    /* --viewerBackground: hsl(150, 25%, 85%); */
}
.palette-graffiti:root {
    --alertAccent: hsl(48, 89%, 50%);
    --alertBackground: hsl(52, 100%, 90%);
    --alertText: var(--bodyText);
    --bodyBackground: hsl(279, 77%, 95%);
    --bodyEmpty: hsl(278, 80%, 91%);
    --bodySeparator: hsl(278, 80%, 94%);
    --bodyText: hsl(278, 22%, 10%);
    --bodyTextDisabled: hsl(278, 80%, 90%);
    --buttonAccentBackground: var(--colorAccent);
    --buttonAccentHoverBackground: var(--colorAccentStrong);
    --buttonAccentHoverText: var(--buttonAccentText);
    --buttonDefaultBackground: var(--bodyBackground);
    --buttonDefaultBorder: hsl(278, 80%, 90%);
    --buttonDefaultHoverBackground: var(--bodyEmpty);
    --buttonDefaultHoverBorder: hsl(278, 80%, 90%);
    --buttonDefaultHoverText: var(--colorAccentStrong);
    --buttonDefaultText: var(--colorAccent);
    --colorAccent: hsl(278, 33%, 44%);
    --colorAccentStrong: hsl(278, 33%, 40%);
    --inputBackground:  hsl(0, 0%, 100%);
    --inputText: hsl(278, 22%, 10%);
    --linkText: var(--colorAccent);
    --menuBackground: hsla(278, 80%, 90%, 80%);
    --menuItemHoverBackground: var(--colorAccent);
    --menuItemHoverText: #FFF;
    --menuItemText: var(--inputText);
    --menuSeparator: hsl(278, 80%, 79%);
    --menuText: var(--inputText);
    --modalBackground: var(--bodyBackground);
    --modalText: var(--inputText);
    --topBarBackground: var(--bodyBackground);
    --topBarText: var(--bodyText);
    --listItemText: var(--bodyBackground);
}
.palette-abstract:root{
    --alertAccent: hsl(48, 89%, 50%);
    --alertBackground: hsl(52, 100%, 90%);
    --alertText: hsl(278, 22%, 10%);
    --bodyBackground: hsl(15, 73%, 97%);
    --bodyEmpty: hsl(15, 100%, 92%);
    --bodySeparator: var(--bodyEmpty);
    --bodyText: hsl(15, 25%, 16%);
    --bodyTextDisabled: hsl(15, 100%, 90%);
    --bodyTextSubtle: hsl(189, 6%, 45%);
    --buttonAccentBackground: var(--colorAccent);
    --buttonAccentHoverBackground: var(--colorAccentStrong);
    --buttonAccentHoverText: var(--buttonAccentText);
    --buttonAccentText:  hsl(0, 0%, 100%);
    --buttonDefaultBackground: var(--bodyBackground);
    --buttonDefaultBorder: hsl(15, 70%, 90%);
    --buttonDefaultHoverBackground: hsl(15, 100%, 95%);
    --buttonDefaultHoverBorder: hsl(15, 80%, 90%);
    --buttonDefaultHoverText: var(--colorAccentStrong);
    --buttonDefaultText:  var(--colorAccentStrong);
    --colorAccent: hsl(15, 70%, 30%);
    --colorAccentStrong: hsl(15, 70%, 40%);
    --inputBackground: var(--buttonAccentText);
    --inputPlaceholderText: hsl(15, 100%, 20%, 0.3);
    --inputText: var(--bodyText);
    --linkText: var(--colorAccent);
    --menuBackground: hsla(15, 100%, 90%, 80%);
    --menuItemHoverBackground: var(--colorAccent);
    --menuItemHoverText: #FFF;
    --menuItemText: var(--bodyText);
    --menuSeparator: hsl(15, 71%, 74%);
    --menuText: var(--bodyText);
    --modalBackground: var(--bodyBackground);
    --modalText: var(--bodyText);
    --topBarBackground: var(--bodyBackground);
    --topBarText: var(--bodyText);
    --listItemText: var(--bodyBackground);
    /* --viewerBackground: hsl(15, 100%, 96%); */
}
.palette-cheers:root{
    --alertAccent: hsl(48, 89%, 50%);
    --alertBackground: hsl(52, 100%, 90%);
    --alertText: hsl(278, 22%, 10%);
    --bodyBackground: hsl(42, 60%, 91%);
    --bodyEmpty: hsl(42, 80%, 82%);
    --bodySeparator: var(--bodyEmpty);
    --bodyText: hsl(42, 25%, 16%);
    --bodyTextDisabled: hsl(42, 100%, 50%);
    --bodyTextSubtle: hsl(189, 6%, 45%);
    --buttonAccentBackground: hsl(42, 100%, 70%);
    --buttonAccentHoverBackground: hsl(42, 100%, 60%);
    --buttonAccentHoverText: var(--buttonAccentText);
    --buttonAccentText: hsl(42, 80%, 20%);
    --buttonDefaultBackground: var(--bodyBackground);
    --buttonDefaultBorder: hsl(42, 70%, 80%);
    --buttonDefaultHoverBackground: hsl(42, 80%, 85%);
    --buttonDefaultHoverBorder: hsl(42, 80%, 80%);
    --buttonDefaultHoverText: hsl(42, 80%, 20%);
    --buttonDefaultText: hsl(42, 80%, 20%);
    --colorAccent: hsl(42, 100%, 50%);
    --colorAccentStrong: hsl(42, 100%, 40%);
    --inputBackground:  hsl(0, 0%, 100%);
    --inputPlaceholderText: hsl(42, 100%, 40%, 0.2);
    --inputText: var(--bodyText);
    --linkText: var(--colorAccent);
    --menuBackground: hsla(42, 90%, 90%, 80%);
    --menuItemHoverBackground: var(--colorAccent);
    --menuItemHoverText: #FFF;
    --menuItemText: var(--bodyText);
    --menuSeparator: hsl(42, 70%, 67%);
    --menuText: var(--bodyText);
    --modalBackground: var(--bodyBackground);
    --modalText: var(--bodyText);
    --topBarBackground: var(--bodyBackground); /* hsl(42, 100%, 70%) */
    --topBarText: var(--bodyText);
    --listItemText: var(--bodyBackground);
    /* --viewerBackground: hsl(42, 80%, 85%); */
}
.palette-imgur:root {
    --alertAccent: var(--colorAccent);
    --alertBackground: #463979;
    --alertText: var(--bodyText);
    --backgroundDarkAlpha: rgb(0 0 0 / 80%);
    --backgroundLightAlpha: rgba(26, 25, 62, .9);
    --bodyBackground: rgb(39,41,45);
    --bodyEmpty: var(--buttonDefaultBackground);
    --bodySeparator: #585D6A;
    --bodyText: #DADCDF;
    --bodyTextDisabled: #b4b9c2;
    --bodyTextSubtle: #999;
    --buttonAccentHoverText: var(--buttonAccentText);
    --buttonAccentText: #FFF;
    --buttonDefaultBackground: #464b57;
    --buttonDefaultHoverBackground: var(--colorAccent);
    --buttonDefaultHoverText: var(--buttonAccentText);
    --buttonDefaultText: #b4b9c2;
    --colorAccent: #1bb76e;
    --colorAccentStrong: #31be7c;
    --inputBackground: #191919;
    --inputPlaceholderText: #9298a0;
    --inputText: #f2f2f2;
    --linkText: var(--colorAccent);
    --menuBackground: hsl(222deg 6% 35% / 80%);
    --menuItemHoverBackground: hsla(0,0%,80%,.29);
    --menuItemHoverText: var(--buttonAccentText);
    --menuItemText: var(--buttonAccentText);
    --menuSeparator: rgb(11 14 15 / 10%);
    --menuText: var(--buttonAccentText);
    --modalBackground: rgb(60 66 75 / 50%);
    --modalText: var(--buttonAccentText);
    --topBarBackground: linear-gradient(180deg, #171544 0%, rgba(39,41,45,1));
    --topBarText: var(--bodyText);
    --viewerBackground: linear-gradient(180deg, transparent 0%, rgba(0,0,0,.1));
}
.palette-flickr:root {
    --bodyBackground: #f2f5f6;
    --colorAccent: #128fdc;
    --colorAccentStrong: #1c9be9;
    --linkText: #006dac;
    --topBarBackground: rgb(0 0 0 / 90%);
    --topBarText: #FFF;
    --viewerBackground: #212124;
}
.palette-deviantart:root {
    --alertAccent: var(--colorAccent);
    --alertBackground: #9affde;
    --bodyBackground: #06070d;
    --bodyEmpty: var(--bodyBackground);
    --bodyText: #f2f2f2;
    --bodyTextSubtle: #5d5c6c;
    --buttonAccentHoverBackground: var(--colorAccentStrong);
    --buttonAccentHoverText: var(--buttonAccentText);
    --buttonAccentText: #FFF;
    --buttonDefaultBackground: var(--bodyBackground);
    --buttonDefaultHoverBackground:var(--bodyBackground);
    --buttonDefaultHoverText: var(--buttonDefaultText);
    --buttonDefaultText: #f2f2f2;
    --colorAccent: #00e59b;
    --colorAccentStrong: #31be7c;
    --inputBackground: #292f34;
    --inputPlaceholderText: #838592;
    --inputText: #838592;
    --linkText: var(--colorAccent);
    --menuBackground: var(--bodyBackground);
    --menuItemHoverBackground: #282f34;
    --menuItemHoverText: var(--colorAccent);
    --menuItemText: #b1b1b9;
    --menuText: var(--buttonAccentText);
    --modalText: var(--buttonDefaultText);
    --topBarBackground: #06070d;
    --topBarText: #fff;
    --viewerBackground: linear-gradient(180deg, rgba(26,28,35,1) 0%, rgba(11,13,18,1) 100%);
}
.palette-cmyk:root {
    --alertAccent: var(--buttonAccentBackground);
    --alertBackground: #fff5b3;
    --alertText: var(--bodyText);
    --bodyBackground: #000;
    --bodyEmpty: #000e10;
    --bodySeparator: #370f1d;
    --bodyText: #00bcd4;
    --bodyTextSubtle: var(--buttonAccentBackground);
    --buttonAccentBackground: #ec407a;
    --buttonAccentHoverBackground: var(--bodyText);
    --buttonAccentHoverText: #fff;
    --buttonAccentText: var(--buttonAccentHoverText);
    --buttonDefaultBackground: var(--bodyBackground);
    --buttonDefaultHoverBackground: var(--bodyBackground);
    --buttonDefaultHoverText: #ffd54f;
    --buttonDefaultText: var(--buttonAccentBackground);
    --colorAccent: var( --buttonDefaultHoverText);
    --inputBackground: #edcf6e;
    --inputPlaceholderText: var(--bodyBackground);
    --linkText: var(--buttonDefaultHoverText);
    --menuBackground: rgb(0 0 0 / 70%);
    --menuItemHoverBackground: var(--buttonAccentBackground);
    --menuItemHoverText: var(--buttonAccentText);
    --menuItemText: var(--buttonDefaultHoverText);
    --menuSeparator: rgb(11 14 15 / 10%);
    --menuText: var(--buttonAccentHoverText);
    --modalBackground: var(--bodyBackground);
    --modalText: var(--bodyText);
    --topBarBackground: rgb(0 0 0 / 70%);
    --topBarText: var(--bodyText);
    --viewerBackground: linear-gradient(180deg, rgba(26,28,35,1) 0%, rgba(11,13,18,1) 100%);
}
</style>
