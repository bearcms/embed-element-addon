<?php
/*
 * Embed element addon for Bear CMS
 * https://github.com/bearcms/embed-element-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

use BearFramework\App;
use IvoPetkov\HTML5DOMDocument;

$app = App::get();

$outputType = (string) $component->getAttribute('output-type');
$outputType = isset($outputType[0]) ? $outputType : 'full-html';
$isFullHtmlOutputType = $outputType === 'full-html';

$allowedHosts = [
    'skydrive.live.com',
    'onedrive.live.com',
    'docs.google.com',
    'drive.google.com',
    'slideshare.net',
    'www.slideshare.net',
    'sway.com',
];

$value = (string)$component->value;
$aspectRatio = (string)$component->aspectRatio;
$height = (string)$component->height;

$value = trim($value);
if (strpos($value, '<iframe') !== false) {
    $dom = new HTML5DOMDocument();
    $dom->loadHTML($value, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);
    $iframes = $dom->getElementsByTagName('iframe');
    if ($iframes->length > 0) {
        $url = trim((string) $iframes[0]->getAttribute('src'));
    } else {
        $url = '';
    }
} else {
    $url = $value;
}
if ($url !== '' && strpos($url, '//') === 0) {
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
                    if (!isset($error[0])) {
                        $result = json_decode($result, true);
                        if (is_array($result) && isset($result['html'])) {
                            $dom = new HTML5DOMDocument();
                            $dom->loadHTML($result['html'], HTML5DOMDocument::ALLOW_DUPLICATE_IDS);
                            $iframe = $dom->querySelector('iframe');
                            if ($iframe !== null) {
                                $src = (string)$iframe->getAttribute('src');
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
    $html = '<iframe src="' . htmlentities($url) . '" frameborder="0"' . ($isFullHtmlOutputType ? ' style="position:absolute;width:100%;height:100%;"' : ' style="width:100%;"') . ' allowfullscreen="true"></iframe>';

    if (strlen($aspectRatio) > 0) {
        $aspectRatioParts = explode(':', $aspectRatio);
        $paddingBottom = '75%';
        if (sizeof($aspectRatioParts) === 2 && is_numeric($aspectRatioParts[0]) && is_numeric($aspectRatioParts[1])) {
            $widthRatio = (float) $aspectRatioParts[0];
            $heightRatio = (float) $aspectRatioParts[1];
            if ($widthRatio > 0 && $heightRatio > 0) {
                if ($heightRatio / $widthRatio > 10) { // prevent super tall element
                    $heightRatio = $widthRatio * 10;
                }
                $paddingBottomValue = ($heightRatio / $widthRatio * 100);
                if ($paddingBottomValue >= 0) {
                    $paddingBottom = $paddingBottomValue . '%';
                }
            }
        }
        $containerStyle = 'position:relative;height:0;padding-bottom:' . $paddingBottom . ';';
    } else {
        if (strlen($height) === 0) {
            $height = '420px';
        }
        $containerStyle = 'position:relative;height:' . $height . ';';
    }
    if ($isFullHtmlOutputType) {
        $content = '<div class="bearcms-embed-element" style="' . $containerStyle . 'font-size:0;line-height:0;" data-responsively-lazy-type="html" data-responsively-lazy="' . htmlentities($html) . '"></div>';
    } else {
        $content = $html;
    }
} else {
    if ($app->bearCMS->currentUser->exists()) {
        $content = '<div style="background-color:red;color:#fff;padding:10px 15px 9px 15px;border-radius:4px;line-height:25px;font-size:14px;font-family:Arial,sans-serif;">';
        $content .= 'Invalid embed code or URL!<div style="font-size:11px;">This message is visible to administrators only.</div>';
        $content .= '</div>';
    } else {
        $content = '';
    }
}
echo '<html>';

if ($isFullHtmlOutputType) {
    echo '<head><link rel="client-packages-embed" name="responsivelyLazy"></head>';
}

echo '<body>';
echo $content;
echo '</body>';

echo '</html>';
