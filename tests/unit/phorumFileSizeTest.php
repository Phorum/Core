<?php

require_once __DIR__ . '/../../include/format_functions.php';

class formatFunctionsTest extends \PHPUnit_Framework_TestCase
{
    public function getFilesizeData()
    {
        return array(
            [1, '1&nbsp;byte'],
            [3, '3&nbsp;bytes'],
            [1023, '1023&nbsp;bytes'],
            [1024, '1&nbsp;KB'],
            [3 * 1024, '3&nbsp;KB'],
            [1024 * 1024, '1&nbsp;MB'],
            [2 * 1024 * 1024, '2&nbsp;MB'],
            [3.14 * 1024 * 1024, '3.14&nbsp;MB'],
            [3.145 * 1024 * 1024, '3.15&nbsp;MB'],
        );
    }

    /**
     * @dataProvider getFilesizeData
     */
    public function testPhorumFilesize($bytes, $expected)
    {
        $result = phorum_filesize($bytes);
        $this->assertSame($expected, $result);
    }
}
