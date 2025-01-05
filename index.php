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
    ->register('bearcms/embed-element-addon', function (\BearCMS\Addons\Addon $addon) use ($app): void {
        $addon->initialize = function () use ($app): void {
            $context = $app->contexts->get(__DIR__);

            $context->assets->addDir('assets');

            $app->localization
                ->addDictionary('en', function () use ($context) {
                    return include $context->dir . '/locales/en.php';
                })
                ->addDictionary('bg', function () use ($context) {
                    return include $context->dir . '/locales/bg.php';
                });

            $type = new \BearCMS\Internal\ElementType('embed', 'bearcms-embed-element', $context->dir . '/components/embedElement.php');
            $type->properties = [
                [
                    'id' => 'value',
                    'type' => 'string'
                ],
                [
                    'id' => 'aspectRatio',
                    'type' => 'string'
                ],
                [
                    'id' => 'height',
                    'type' => 'string'
                ]
            ];
            \BearCMS\Internal\ElementsTypes::add($type);

            \BearCMS\Internal\Themes::$elementsOptions['embed'] = function ($options, $idPrefix, $parentSelector, $context, $details): void {
                $group = $options->addGroup(__('bearcms.themes.options.Embed'));
                $group->addOption($idPrefix . "EmbedCSS", "css", '', [
                    "cssTypes" => ["cssBorder", "cssRadius", "cssShadow"],
                    "cssOptions" => isset($details['cssOptions']) ? $details['cssOptions'] : [],
                    "cssOutput" => [
                        ["rule", $parentSelector . " .bearcms-embed-element", "overflow:hidden;"],
                        ["selector", $parentSelector . " .bearcms-embed-element"]
                    ]
                ]);
            };
        };
    });
