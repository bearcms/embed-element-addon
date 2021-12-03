<?php

/*
 * Embed element addon for Bear CMS
 * https://github.com/bearcms/embed-element-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

/**
 * @runTestsInSeparateProcesses
 */
class EmbedElementTest extends BearCMS\AddonTests\PHPUnitTestCase
{
    /**
     * 
     */
    public function testOutput()
    {
        $app = $this->getApp();

        $html = '<bearcms-embed-element value="https://www.slideshare.net/GrahamMcInnes1/22-immutable-laws-of-marketing"/>';
        $result = $app->components->process($html);

        $this->assertTrue(strpos($result, '<div class="bearcms-embed-element"') !== false);
        $this->assertTrue(strpos($result, 'https://www.slideshare.net/slideshow/embed_code/') !== false);
    }
}
