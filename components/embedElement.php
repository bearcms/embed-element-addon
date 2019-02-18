<?php
/*
 * Embed element addon for Bear CMS
 * https://github.com/bearcms/embed-element-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

use BearFramework\App;

$app = App::get();
$context = $app->contexts->get(__FILE__);

$allowedHosts = [
    'skydrive.live.com',
    'onedrive.live.com',
    'docs.google.com',
    'drive.google.com',
    'slideshare.net',
    'www.slideshare.net',
    'sway.com',
];

$value = $component->value;
$aspectRatio = $component->aspectRatio;
$height = $component->height;

$value = trim($value);
if (strpos($value, '<iframe') !== false) {
    $dom = new IvoPetkov\HTML5DOMDocument();
    $dom->loadHTML($value);
    $iframes = $dom->getElementsByTagName('iframe');
    if ($iframes->length > 0) {
        $url = trim((string) $iframes[0]->getAttribute('src'));
    }
} else {
    $url = $value;
}
if (strpos($url, '//') === 0) {
    $url = 'https:' . $url;
}
if (filter_var($url, FILTER_VALIDATE_URL) === false) {
    $url = '';
}
if (strlen($url) > 0) {
    $urlParts = parse_url($url);
    if (!isset($urlParts['host']) || array_search($urlParts['host'], $allowedHosts) === false) {
        $url = '';
    }
    if ($url !== '') {
        $updated = false;

        if (!$updated) {
            $matches = [];
            if (preg_match('/\/\/drive\.google\.com\/file\/d\/(.*?)\/view/', $url, $matches)) {
                $url = str_replace($matches[0], '//drive.google.com/file/d/' . $matches[1] . '/preview', $url);
                $updated = true;
            }
        }
        if (!$updated) {
            if (isset($urlParts['host'], $urlParts['path'], $urlParts['query']) && $urlParts['host'] === 'drive.google.com' && $urlParts['path'] === '/open') {
                $query = [];
                parse_str($urlParts['query'], $query);
                if (isset($query['id'])) {
                    $url = 'https://drive.google.com/file/d/' . $query['id'] . '/preview';
                    $updated = true;
                }
            }
        }
        if (!$updated) {
            if (array_search($urlParts['host'], ['slideshare.net', 'www.slideshare.net']) !== false) {
                $cacheKey = 'slideshare-oembed-' . $url;
                $result = $app->cache->getValue($cacheKey);
                if ($result === null) {
                    $ttl = 86400;
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, 'https://www.slideshare.net/api/oembed/2?url=' . $url . '&format=json');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    $result = curl_exec($ch);
                    $error = curl_error($ch);
                    curl_close($ch);
                    if (!isset($error{0})) {
                        $result = json_decode($result, true);
                        if (is_array($result) && isset($result['html'])) {
                            $dom = new IvoPetkov\HTML5DOMDocument();
                            $dom->loadHTML($result['html']);
                            $iframe = $dom->querySelector('iframe');
                            if ($iframe !== null) {
                                $src = $iframe->getAttribute('src');
                                if (strlen($src) > 0) {
                                    $url = $src;
                                    $ttl = 0;
                                }
                            }
                        }
                    }
                    $cacheItem = $app->cache->make($cacheKey, $url);
                    if ($ttl > 0) {
                        $cacheItem->ttl = $ttl;
                    }
                    $app->cache->set($cacheItem);
                } else {
                    $url = $result;
                }
            }
        }
    }
}
if (strlen($url) > 0) {
    $html = '<iframe src="' . htmlentities($url) . '" frameborder="0" style="position:absolute;width:100%;height:100%;" allowfullscreen="true"></iframe>';

    if (strlen($aspectRatio) > 0) {
        $aspectRatioParts = explode(':', $aspectRatio);
        $paddingBottom = '75%';
        if (sizeof($aspectRatioParts) === 2 && is_numeric($aspectRatioParts[0]) && is_numeric($aspectRatioParts[1])) {
            $paddingBottom = ((float) $aspectRatioParts[1] / (float) $aspectRatioParts[0] * 100) . '%';
        }
        $containerStyle = 'padding-bottom:' . $paddingBottom . ';';
    } else {
        if (strlen($height) === 0) {
            $height = '420px';
        }
        $containerStyle = 'height:' . $height . ';';
    }
    $content = '<div class="bearcms-embed-element responsively-lazy" style="' . $containerStyle . 'font-size:0;line-height:0;" data-lazycontent="' . htmlentities($html) . '"></div>';
} else {
    if ($app->bearCMS->currentUser->exists()) {
        $content = '<div style="background-color:red;color:#fff;padding:10px 15px 9px 15px;border-radius:4px;line-height:25px;font-size:14px;font-family:Arial,sans-serif;">';
        $content .= 'Invalid embed code or URL!<div style="font-size:11px;">This message is visible to administrators only.</div>';
        $content .= '</div>';
    } else {
        $content = '';
    }
}
?><html>
    <head>
        <style id="responsively-lazy-style">.responsively-lazy:not(img){position:relative;height:0;}.responsively-lazy:not(img)>img{position:absolute;top:0;left:0;width:100%;height:100%}img.responsively-lazy{width:100%;}</style>
        <script id="responsively-lazy-script" src="<?= $context->assets->getURL('assets/responsivelyLazy.min.js', ['cacheMaxAge' => 999999999, 'version' => 2]) ?>" async/>
    </head>
    <body><?= $content ?></body>
</html>