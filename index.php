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
        ->register('bearcms/embed-element-addon', function(\BearCMS\Addons\Addon $addon) use ($app) {
            $addon->initialize = function() use ($app) {
                $context = $app->contexts->get(__DIR__);

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

                $app->clientPackages
                ->add('-bearcms-embed-element-responsively-lazy', function(IvoPetkov\BearFrameworkAddons\ClientPackage $package) use ($context) {
                    $package->addJSFile($context->assets->getURL('assets/responsivelyLazy.min.js', ['cacheMaxAge' => 999999999, 'version' => 2]), ['async' => true]);
                    $package->addCSSCode('.responsively-lazy:not(img){position:relative;height:0;}.responsively-lazy:not(img)>img{position:absolute;top:0;left:0;width:100%;height:100%}img.responsively-lazy{width:100%;}');
                });
            };
        });
