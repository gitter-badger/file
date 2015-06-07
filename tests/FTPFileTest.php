<?php
/**
 * Created by PhpStorm.
 * User: ec
 * Date: 07.06.15
 * Time: 13:46
 * Project: file
 * @author: Evgeny Pynykh bpteam22@gmail.com
 */

namespace bpteam\File;

use \PHPUnit_Framework_Testcase;
use \ReflectionClass;

class FTPFileTest extends PHPUnit_Framework_TestCase
{

    public $fileName1 = '/public_html/test3.html';

    public $host = '31.170.165.140';
    public $login = 'u773241224';
    public $password = 'Wy14FQ37sHlm';
    public $port = 21;

    public static function setUpBeforeClass()
    {
        if (!is_dir('/tmp/public_html')) {
            mkdir('/tmp/public_html', 777);
        }
    }

    /**
     * @param        $name
     * @param string $className
     * @return \ReflectionMethod
     */
    protected static function getMethod($name, $className = 'bpteam\File\FTPFile')
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
    protected static function getProperty($name, $className = 'bpteam\File\FTPFile')
    {
        $class = new ReflectionClass($className);
        $property = $class->getProperty($name);
        $property->setAccessible(true);
        return $property;
    }

    public function testConnection()
    {
        $ftp = new FTPFile($this->host, $this->login, $this->password, $this->port);
        $this->assertTrue(is_resource($ftp->getFtpHandle()));
        $this->assertTrue($ftp->connection($this->host, $this->login, $this->password, $this->port));
        $ftp->close();
    }

    public function testOpen()
    {
        $ftp = new FTPFile($this->host, $this->login, $this->password, $this->port);
        $ftp->open($this->fileName1);
        $this->assertFileExists($ftp->getPath());
        $ftp->close();
    }

    public function testClose()
    {
        $ftp = new FTPFile($this->host, $this->login, $this->password, $this->port);
        $ftp->open($this->fileName1);
        $this->assertTrue(is_resource($ftp->getHandle()));
        $this->assertTrue(is_resource($ftp->getFtpHandle()));
        $ftp->close();
        $this->assertFalse(is_resource($ftp->getHandle()));
        $this->assertFalse(is_resource($ftp->getFtpHandle()));
    }

    public function testWrite()
    {
        $ftp = new FTPFile($this->host, $this->login, $this->password, $this->port);
        $ftp->open($this->fileName1);
        $result = $ftp->write('hello world ' . microtime(true) . "\n");
        $this->assertTrue($result);
        $this->assertRegExp('%hello world%', $ftp->read());
        $ftp->close();
    }

    public function testRead()
    {
        $ftp = new FTPFile($this->host, $this->login, $this->password, $this->port);
        $ftp->open($this->fileName1);
        $result = $ftp->write('hello world ' . microtime(true) . "\n");
        $this->assertTrue($result);
        $data = $ftp->read();
        $this->assertRegExp('%hello world%', $data);
        $ftp->close();
    }

    public function testDelete()
    {
        $ftp = new FTPFile($this->host, $this->login, $this->password, $this->port);
        $ftp->open($this->fileName1);
        $this->assertFileExists($ftp->getPath());
        $this->assertTrue($ftp->fileExist($this->fileName1));
        $ftp->delete();
        $this->assertFileNotExists($ftp->getPath());
        $this->assertFalse($ftp->fileExist($this->fileName1));
        $ftp->close();
    }
}