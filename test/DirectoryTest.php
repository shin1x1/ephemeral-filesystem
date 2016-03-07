<?php
namespace Shin1x1\EphemeralFilesystem\Test;

use Shin1x1\EphemeralFilesystem\Directory;

class DirectoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function hello()
    {
        $sut = new Directory('path');
        $this->assertInstanceOf(Directory::class, $sut);
    }
}
