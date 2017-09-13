<?php

require_once __DIR__ . '/../../include/api/format.php';

class formatFunctionsTest extends \PHPUnit_Framework_TestCase
{
    public function getFileSizeData()
    {
        return array(
            [2 * 1024 * 1024, '2&nbsp;MB'],
            [1024 * 1024, '1&nbsp;MB'],
            [3.14 * 1024 * 1024, '3.14&nbsp;MB'],
            [3.145 * 1024 * 1024, '3.15&nbsp;MB'],
            [3 * 1024, '3&nbsp;KB'],
            [3, '3&nbsp;bytes'],
        );
    }

    /**
     * @dataProvider getFileSizeData
     */
    public function testPhorumFilesize($bytes, $expected)
    {
        $result = phorum_filesize($bytes);
        $this->assertSame($expected, $result);
    }
}
