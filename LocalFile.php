<?php
/**
 * Created by PhpStorm.
 * User: EC
 * Date: 04.12.15
 * Time: 0:11
 * Project: file
 * @author: Evgeny Pynykh bpteam22@gmail.com
 */

namespace bpteam\File;


/**
 * Class LocalFile
 * Класс для работы с файлами, распределение доступа к файлам, CRUD
 * @package File
 * @author  Evgeny Pynykh <bpteam22@gmail.com>
 */
class LocalFile implements iFile
{

    protected $localPath;
    /**
     * @var resource
     */
    protected $handle = null;
    protected $name;
    protected $owner = false;
    /**
     * Ожидать пока освободиться файл или нет
     * @var bool
     */
    protected $waitWhenFree = true;

    /**
     * @param string $currentPath
     */
    public function setLocalPath($currentPath)
    {
        $this->localPath = $currentPath;
    }

    /**
     * @return string
     */
    public function getLocalPath()
    {
        return $this->localPath;
    }

    /**
     * @param string $val
     */
    public function setHandle($val)
    {
        $this->handle = $val;
    }

    /**
     * @return resource
     */
    public function &getHandle()
    {
        return $this->handle;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $pathInfo = pathinfo($name);
        $this->setLocalPath($pathInfo['dirname']);
        $this->name = $pathInfo['basename'];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getPath()
    {
        return $this->localPath . '/' . $this->name;
    }

    /**
     * @param mixed $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return mixed
     */
    public function isOwner()
    {
        return $this->owner;
    }

    protected function access($function, ...$param)
    {
        if ($this->isOwner()) {
            $res = $function($param[0], $param[1]);
        } elseif ($this->lock()) {
            $res = $function($param[0], $param[1]);
            $this->free();
        } else {
            $res = false;
        }

        return $res;
    }

    /**
     * @param boolean $waitWhenFree
     */
    public function setWaitWhenFree($waitWhenFree)
    {
        $this->waitWhenFree = $waitWhenFree;
    }

    /**
     * @return boolean
     */
    public function hasWaitFree()
    {
        return $this->waitWhenFree;
    }

    function __construct($name = null)
    {
        if ($name) {
            $this->open($name);
        }
    }

    function __destruct()
    {
        $this->close();
    }

    public function open($name)
    {
        if ($this->getHandle()) {
            self::close();
        }
        $this->setName($name);
        $this->setHandle(fopen($this->getPath(), 'a+'));

        return $this->getHandle();
    }

    public function close()
    {
        $this->free();
        return is_resource($this->getHandle()) ? fclose($this->getHandle()) : false;
    }

    /**
     * Блокировка файла от других процессов
     * @return bool
     */
    public function lock()
    {
        if (is_resource($this->getHandle())) {
            if ($this->hasWaitFree()) {
                $this->setOwner(flock($this->getHandle(), LOCK_EX));
            } else {
                $this->setOwner(flock($this->getHandle(), LOCK_EX | LOCK_NB));
            }

            return $this->isOwner();
        } else {
            return false;
        }
    }

    public function free()
    {
        if (is_resource($this->getHandle()) && $this->isOwner()) {
            fflush($this->getHandle());
            $this->setOwner(!flock($this->getHandle(), LOCK_UN));
        }
        return !$this->isOwner();
    }

    public function write($data)
    {
        $result = $this->access('fwrite', $this->getHandle(), $data);
        fflush($this->getHandle());

        return $result;
    }

    /**
     * Читает из файла, чтение производится с учетом блокировки, если файл блокирован вернет false, или файл не доступен
     * @return bool|string
     */
    public function read()
    {
        rewind($this->getHandle());
        clearstatcache(true, $this->getPath());
        $fSize = filesize($this->getPath());
        return $fSize ? $this->access('fread', $this->getHandle(), $fSize) : '';
    }

    public function delete()
    {
        if (file_exists($this->getPath()) && $this->lock()) {
            self::close();
            return unlink($this->getPath());
        }

        return false;
    }

    public static function clearDir($dir)
    {
        $fileList = glob($dir . "/*");
        foreach ($fileList as $fileName) {
            if (is_file($fileName)) {
                unlink($fileName);
            } elseif (is_dir($fileName)) {
                self::delDir($fileName);
            }
        }
    }

    public static function delDir($dir)
    {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                (is_dir("$dir/$file")) ? self::delDir("$dir/$file") : unlink("$dir/$file");
            }
            return rmdir($dir);
        }

        return false;
    }

    public function clear()
    {
        return $this->access('ftruncate', $this->getHandle(), 0);
    }

} 