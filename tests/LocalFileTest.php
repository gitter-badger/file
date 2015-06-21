<?php
/**
 * Created by PhpStorm.
 * User: ec
 * Date: 05.06.15
 * Time: 0:29
 * Project: file
 * @author: Evgeny Pynykh bpteam22@gmail.com
 */

namespace bpteam\File;

use \PHPUnit_Framework_Testcase;
use \ReflectionClass;

class LocalFileTest extends PHPUnit_Framework_TestCase
{
    public static $fileName1;
    public static $fileName2;
    public static $dir1;

    public static function setUpBeforeClass()
    {
        self::$fileName1 = __DIR__ . '/for_tests/f1.txt';
        self::$fileName2 = __DIR__ . '/for_tests/f2';
        self::$dir1 = __DIR__ . '/for_tests/rm_me';
    }

    /**
     * @param        $name
     * @param string $className
     * @return \ReflectionMethod
     */
    protected static function getMethod($name, $className = 'bpteam\File\LocalFile')
    {
        $class = new ReflectionClass($className);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * @param        $name
     * @param string $className
     * @return \ReflectionProperty
     */
    protected static function getProperty($name, $className = 'bpteam\File\LocalFile')
    {
        $class = new ReflectionClass($className);
        $property = $class->getProperty($name);
        $property->setAccessible(true);
        return $property;
    }

    public function testOpen()
    {
        $file = new LocalFile();
        $file->open($this->fileName1);
        $this->assertFileExists($this->fileName1);
        $file->close();
    }

    public function testClose()
    {
        $file = new LocalFile($this->fileName1);
        $this->assertTrue(is_resource($file->getHandle()));
        $file->close();
        $this->assertFalse(is_resource($file->getHandle()));
    }

    public function testWrite()
    {
        $file = new LocalFile($this->fileName1);
        $result = $file->write('hello world ' . microtime(true) . "\n");
        $this->assertGreaterThanOrEqual(12, $result);
        $file->close();
    }

    public function testRead()
    {
        $file = new LocalFile($this->fileName1);
        $file->write('hello world ' . microtime(true) . "\n");
        $data = $file->read();
        $this->assertRegExp('%hello world%', $data);
        $file->close();
    }

    public function testClear()
    {
        $file = new LocalFile($this->fileName1);
        $file->clear();
        $this->assertEquals('', $file->read());
        $file->close();
    }

    public function testDelete()
    {
        $file = new LocalFile($this->fileName1);
        $this->assertFileExists($this->fileName1);
        $file->delete();
        $this->assertFileNotExists($this->fileName1);
        $file->close();
    }

    public function testDelDir()
    {
        $this->assertFalse(is_dir($this->dir1));
        mkdir($this->dir1);
        $this->assertTrue(is_dir($this->dir1));
        LocalFile::delDir($this->dir1);
        $this->assertFalse(is_dir($this->dir1));
    }

    public function testClearDir()
    {
        mkdir($this->dir1);
        (new LocalFile($this->dir1 . '/f1'))->close();
        (new LocalFile($this->dir1 . '/f2'))->close();
        $this->assertArrayHasKey(0, glob($this->dir1 . "/*"));
        $this->assertArrayHasKey(1, glob($this->dir1 . "/*"));
        $this->assertRegExp('%f1$%', glob($this->dir1 . "/*")[0]);
        $this->assertRegExp('%f2$%', glob($this->dir1 . "/*")[1]);
        LocalFile::clearDir($this->dir1);
        $this->assertCount(0, glob($this->dir1 . "/*"));
        LocalFile::delDir($this->dir1);
        $this->assertFalse(is_dir($this->dir1));
    }

    public function testLock()
    {
        $file = new LocalFile($this->fileName1);
        $this->assertTrue($file->lock());
        $this->assertTrue($file->isOwner());
        $this->assertTrue($file->free());
        $this->assertFalse($file->isOwner());
        $file->close();
    }

    public function testWriteLock()
    {
        $file = new LocalFile($this->fileName1);
        $file->lock();
        $result = $file->write('hello world');
        $this->assertGreaterThanOrEqual(11, $result);
        $file->close();
    }

    public function testReadLock()
    {
        $file = new LocalFile($this->fileName1);
        $file->lock();
        $file->write('hello world');
        $data = $file->read();
        $this->assertRegExp('%hello world%', $data);
        $file->close();
    }

    public function testDeleteLock()
    {
        $file = new LocalFile($this->fileName1);
        $file->lock();
        $file->delete();
        $this->assertFileNotExists($this->fileName1);
    }

    public function testCantWriteLock()
    {
        $fileBlock = new LocalFile($this->fileName1);
        $file = new LocalFile($this->fileName1);
        $file->setWaitWhenFree(false);
        $fileBlock->lock();
        $fileBlock->write('I can write');
        $data = $fileBlock->read();
        $this->assertRegExp('%I can write%', $data);
        $file->write('I cant write');
        $data = $fileBlock->read();
        $this->assertNotRegExp('%I cant write%', $data);

        $fileBlock->close();
        $file->write('I cant write');
        $data = $file->read();
        $this->assertRegExp('%I cant write%', $data);
        $file->close();
    }

    public function testCantDeleteLock()
    {
        $fileBlock = new LocalFile($this->fileName1);
        $file = new LocalFile($this->fileName1);
        $file->setWaitWhenFree(false);
        $fileBlock->lock();
        $file->delete();
        $this->assertFileExists($this->fileName1);
        $fileBlock->close();
        $file->delete();
        $this->assertFileNotExists($this->fileName1);
    }

    public function testCantLockLock()
    {
        $fileBlock = new LocalFile($this->fileName1);
        $file = new LocalFile($this->fileName1);
        $file->setWaitWhenFree(false);
        $fileBlock->lock();
        $this->assertTrue($fileBlock->isOwner());
        $this->assertFalse($file->isOwner());
        $file->delete();
        $this->assertFileExists($this->fileName1);
        $file->lock();
        $this->assertTrue($fileBlock->isOwner());
        $this->assertFalse($file->isOwner());
        $file->delete();
        $this->assertFileExists($this->fileName1);
        $fileBlock->close();
        $file->lock();
        $this->assertTrue($file->isOwner());
        $file->delete();
        $this->assertFileNotExists($this->fileName1);
    }
}