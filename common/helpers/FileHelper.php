<?php
namespace common\helpers;

/**
 * 文件帮助类
 *
 * Class FileHelper
 * @package common\helpers
 */
class FileHelper
{
    /**
     * 检测目录并循环创建目录
     *
     * @param $catalogue
     */
    public static function mkdirs($catalogue)
    {
        if (!file_exists($catalogue))
        {
            self::mkdirs(dirname($catalogue));
            mkdir($catalogue, 0777);
        }

        return true;
    }

    /**
     * 写入日志
     *
     * @param $path
     * @param $content
     * @return bool|int
     */
     public static function writeLog($path, $content)
     {
//<<<<<<< HEAD
//         $dir = pathinfo($path);
         self::mkdirs(dirname($path));
         return file_put_contents($path, "\r\n" . $content, FILE_APPEND);
//=======
//         return file_put_contents(self::mkdirs(dirname($path)), "\r\n" . $content, FILE_APPEND);
//>>>>>>> upstream/master
     }

    /**
     * 获取文件夹大小
     *
     * @param string $dir 根文件夹路径
     * @return int
     */
    public static function getDirSize($dir)
    {
        $handle = opendir($dir);
        $sizeResult = 0;
        while (false !== ($FolderOrFile = readdir($handle)))
        {
            if ($FolderOrFile != "." && $FolderOrFile != "..")
            {
                if (is_dir("$dir/$FolderOrFile"))
                {
                    $sizeResult += self::getDirSize("$dir/$FolderOrFile");
                }
                else
                {
                    $sizeResult += filesize("$dir/$FolderOrFile");
                }
            }
        }

        closedir($handle);
        return $sizeResult;
    }

    /**
     * 基于数组创建目录
     *
     * @param $files
     */
    public static function createDirOrFiles($files)
    {
        foreach ($files as $key => $value)
        {
            if (substr($value, -1) == '/')
            {
                mkdir($value);
            }
            else
            {
                file_put_contents($value, '');
            }
        }
    }
}