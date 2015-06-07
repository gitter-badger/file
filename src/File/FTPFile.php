<?php
/**
 * Created by PhpStorm.
 * User: ec
 * Date: 05.06.15
 * Time: 13:21
 * Project: file
 * @author: Evgeny Pynykh bpteam22@gmail.com
 */

namespace bpteam\File;


class FTPFile extends LocalFile implements iFile {

    protected $host;
    protected $login;
    protected $password;
    protected $port = 21;
    protected $ftpPath;
    protected $localPathPrefix = '/tmp';
    protected $ftpHandle;
    protected $timeout = 600;

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    public function &getFtpHandle()
    {
        return $this->ftpHandle;
    }

    public function setFtpPath($path)
    {
        $this->ftpPath = $path;
    }

    public function getFtpPath()
    {
        return ($this->ftpPath=='/'? $this->ftpPath : $this->ftpPath . '/') . $this->name;
    }

    protected function upload()
    {
        return ftp_put($this->getFtpHandle(), $this->getFtpPath(), $this->getPath(), FTP_ASCII);
    }

    public function setName($name)
    {
        $pathInfo = pathinfo($name);
        $this->setFtpPath($pathInfo['dirname']);
        $this->name = $pathInfo['basename'];
        parent::setName($this->localPathPrefix . $this->getFtpPath());
    }

    function __construct($host, $login, $password, $port = 21)
    {
        $this->connection($host, $login, $password, $port);
    }

    public function connection($host, $login, $password, $port = 21)
    {
        $this->host = $host;
        $this->login = $login;
        $this->password = $password;
        $this->port = $port;
        $this->ftpHandle = ftp_connect($host, $port, 999);
        return ftp_login($this->ftpHandle, $login, $password);
    }

    public function open($name)
    {
        parent::open($name);
        if ($this->fileExist($this->getFtpPath())) {
            $result = ftp_fget($this->ftpHandle, $this->handle, $this->getFtpPath(), FTP_ASCII);
        } elseif ($this->upload()) {
            $result = $this->open($name);
        } else {
            $result = false;
        }
        return $result;
    }

    public function close()
    {
        is_resource($this->getFtpHandle()) ? ftp_close($this->getFtpHandle()) : false;
        parent::delete();
        parent::close();
    }

    public function write($data)
    {
        return parent::write($data) && $this->upload();
    }

    public function read()
    {
        $this->open($this->getFtpPath());
        return parent::read();
    }

    public function delete()
    {
        ftp_delete($this->ftpHandle, $this->name);
        parent::delete();
    }

    public function setHandle($val)
    {
        parent::setHandle($val);
    }

    public function &getHandle()
    {
        return parent::getHandle();
    }

    public function setLocalPath($path)
    {
        parent::setLocalPath($path);
    }

    public function getLocalPath()
    {
        return parent::getLocalPath();
    }

    public function fileExist($path)
    {
        return ftp_size($this->ftpHandle, $path) != -1;
    }
}