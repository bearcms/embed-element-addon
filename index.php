<?php

/*
 * Embed element addon for Bear CMS
 * https://github.com/bearcms/embed-element-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

use BearFramework\App;

$app = App::get();

$app->bearCMS->addons
        ->announce('bearcms/embed-element-addon', function(\BearCMS\Addons\Addon $addon) use ($app) {
            $addon->initialize = function() use ($app) {
                $context = $app->context->get(__FILE__);

                $context->assets->addDir('assets');

                $app->localization
                ->addDictionary('en', function() use ($context) {
                    return include $context->dir . '/locales/en.php';
                })
                ->addDictionary('bg', function() use ($context) {
                    return include $context->dir . '/locales/bg.php';
                });

                \BearCMS\Internal\ElementsTypes::add('embed', [
                    'componentSrc' => 'bearcms-embed-element',
                    'componentFilename' => $context->dir . '/components/embedElement.php',
                    'fields' => [
                        [
                            'id' => 'value',
                            'type' => 'textbox'
                        ],
                        [
                            'id' => 'aspectRatio',
                            'type' => 'textbox'
                        ],
                        [
                            'id' => 'height',
                            'type' => 'textbox'
                        ]
                    ]
                ]);

                \BearCMS\Internal\Themes::$elementsOptions['embed'] = function($context, $idPrefix, $parentSelector) {
                    $group = $context->addGroup(__('bearcms.themes.options.Embed'));
                    $group->addOption($idPrefix . "EmbedCSS", "css", '', [
                        "cssTypes" => ["cssBorder", "cssRadius", "cssShadow"],
                        "cssOutput" => [
                            ["rule", $parentSelector . " .bearcms-embed-element", "overflow:hidden;"],
                            ["selector", $parentSelector . " .bearcms-embed-element"]
                        ]
                    ]);
                };
            };
        });
