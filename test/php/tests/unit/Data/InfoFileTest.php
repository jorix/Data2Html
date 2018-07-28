<?php
use Data2Html\Data\InfoFile;

use Codeception\Scenario;

class InfoFileTest extends \Codeception\Test\Unit
{
    public $infoFolder = 'tests/_data/Data/';
    
    function _ds($path)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    // Tests
    public function testToCleanFilePath()
    {
        $this->assertEquals(
            $this->_ds('aaa/ccc'),
            InfoFile::toCleanFilePath('aaa/bbb/../ccc'),
            'Remove /../ on a path of file'
        );
    }
    
    public function testToCleanFolderPath()
    {
        $this->assertEquals(
            $this->_ds('aaa/ccc/'),
            InfoFile::toCleanFolderPath('aaa/bbb/../ccc'),
            'Remove /../ on a path of folder'
        );
    }

    public function testReadJson()
    {
        $this->assertEquals(
            ['a' => 'b'],
            InfoFile::readJson($this->infoFolder . 'testReadJson-01.json'),
            'Read a json file'
        );
        $this->assertEquals(
            ['a' => 'b-php'],
            InfoFile::readJson($this->infoFolder . 'testReadJson-01.json.php'),
            'Read a wrapped json file'
        );
    }
    
    public function testReadPhp()
    {
        $this->assertEquals(
            ['a' => 'test-ReadPhp'],
            InfoFile::readPhp($this->infoFolder . 'testReadPhp-01.php'),
            'Read a php file as a return'
        );
        try {
            $fail = false;
            InfoFile::readPhp($this->infoFolder . 'testReadPhp-error.php');
        } catch (\Exception $e) { $fail = true; }
        $this->assertTrue($fail, 'Error when parsing a incorrect php file');
    }
}
