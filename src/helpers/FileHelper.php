<?php
/**
 * Created by PhpStorm.
 * User: Choate
 * Date: 2018/3/13
 * Time: 15:49
 */

namespace choate\yii2\components\helpers;


use yii\helpers\FileHelper AS YiiFileHelper;
use Yii;

class FileHelper extends YiiFileHelper
{
    public static $extensionMagicTypeFile = __DIR__ . DIRECTORY_SEPARATOR .'extensionHashTypes.php';

    private static $_hashTypes = [];

    public static function getExtensionByHashType($hashType, $magicFile = null)
    {
        $hashTypes = static::loadHashTypes($magicFile);

        return array_search(mb_strtolower($hashType, 'UTF-8'), $hashTypes, true);
    }

    public static function getHashTypeByExtension($extension, $magicFile = null)
    {
        $hashTypes = static::loadHashTypes($magicFile);

        $ext = strtolower($extension);
        if (isset($hashTypes[$ext])) {
            return $hashTypes[$ext];
        }

        return '000';
    }

    public static function buildFileId($file, $ext)
    {
        $hash = md5_file($file);

        return $hash . static::getHashTypeByExtension($ext);
    }

    public static function buildPath($fileId, $ext)
    {
        $split = str_split($fileId, 2);

        return '/' . $split[0] . '/' . $split[1] . '/' . $split[2] . '/' . $fileId . '.' . $ext;
    }

    public static function buildPathByFileId($fileId)
    {
        if ($fileId) {
            $hashType = substr($fileId, 32);
            $ext = static::getExtensionByHashType($hashType);

            return static::buildPath($fileId, $ext);
        }

        return null;
    }

    protected static function loadHashTypes($magicFile)
    {
        if ($magicFile === null) {
            $magicFile = static::$extensionMagicTypeFile;
        }
        $magicFile = Yii::getAlias($magicFile);
        if (!isset(self::$_hashTypes[$magicFile])) {
            self::$_hashTypes[$magicFile] = require($magicFile);
        }

        return self::$_hashTypes[$magicFile];
    }
}