<?php
use Data2Html\Data\InfoFile;

use Codeception\Scenario;

class InfoFileTest extends \Codeception\Test\Unit
{
    use \Codeception\Specify;
    
    public $infoFolder = "tests/_data/Data/";
    
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    function _ds($path)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    // Tests
    public function testToCleanFilePath()
    {
        $this->assertEquals(
            $this->_ds('aaa/ccc'),
            InfoFile::toCleanFilePath('aaa/bbb/../ccc')
        );
    }
    
    public function testToCleanFolderPath()
    {
        $this->assertEquals(
            $this->_ds('aaa/ccc/'),
            InfoFile::toCleanFolderPath('aaa/bbb/../ccc')
        );
    }

    public function testReadJson()
    {
        $this->specify("read files", function() {
            $this->assertEquals(
                '{"a":"b"}',
                json_encode( InfoFile::readJson($this->infoFolder . 'testReadJson-01.json'))
            );
            $this->assertEquals(
                '{"a":"b-php"}',
                json_encode( InfoFile::readJson($this->infoFolder . 'testReadJson-01.json.php'))
            );
        });
    }
    
    public function testReadPhp()
    {
        $this->assertEquals(
            '{"a":"test-ReadPhp"}',
            json_encode( InfoFile::readPhp($this->infoFolder . 'testReadPhp-01.php'))
        );
        try {
            $fail = false;
            InfoFile::readPhp($this->infoFolder . 'testReadPhp-error.php');
        } catch (\Exception $e) { $fail = true; }
        $this->assertTrue($fail, "Error when parsing a incorrect php file");
    }
}
