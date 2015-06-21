<?php
/**
 * Created by PhpStorm.
 * User: ec
 * Date: 04.06.15
 * Time: 20:12
 * Project: file
 * @author: Evgeny Pynykh bpteam22@gmail.com
 */

namespace bpteam\File;

interface iFile
{
    public function open($name);
    public function close();
    public function write($data);
    public function read();
    public function delete();
    public function setHandle($val);
    public function &getHandle();
    public function setLocalPath($path);
    public function getLocalPath();
}