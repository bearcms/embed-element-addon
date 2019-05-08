<?php

/*
 * Embed element addon for Bear CMS
 * https://github.com/bearcms/embed-element-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

BearFramework\Addons::register('bearcms/embed-element-addon', __DIR__, [
    'require' => [
        'bearcms/bearframework-addon',
        'bearframework/localization-addon',
        'ivopetkov/client-shortcuts-bearframework-addon'
    ]
]);
