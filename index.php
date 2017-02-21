<?php
define('APP_ROOT', __DIR__);
define('DS', DIRECTORY_SEPARATOR);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    # 这里把上传的操作提交给相对于的操作处理
    # 如果是正式使用，不用判断 直接指定要使用的版本
    $ver = false;
    $path = APP_ROOT . DS . 'src' . DS . 'php' . DS;
    foreach (['v', 'ver', 'version'] as $v) {
        if (isset($_GET[$v])) {
            $ver = $_GET[$v];
            break;
        }
    }
    if (!$ver || !is_dir($path . $ver)) {
        preg_match("/test\/(.+)\.html$/i", $_SERVER['HTTP_REFERER'], $matches);
        if (isset($matches[1])) {
            $ver = $matches[1];
        } else {
            die('无法找到需要请求的版本！');
        }
    }
    require $path . $ver . DS . 'BPUploads.php';
} else {
    $testFiles = get_pathFiles('./test/');
    if (empty($testFiles) || !is_array($testFiles)) {
        die('暂无测试文件，请再test中添加测试文件！');
    } else {
        // $link = '/test/' . reset($testFiles);
        // header('Location:'.$link);
        foreach ($testFiles as $val) {
            $link = "/test/{$val}";
            echo '<p><a href="'.$link.'">'.$val.'</a></p>';
        }
    }
}



/**
 * [读取路径所有文件]
 * @param  string $path   [路径]
 * @return boolean|array  [是否读取成功]
 * @author OJesusO
 */
function get_pathFiles($path='')
{
    if( empty($path) ) return FALSE;

    $tmpPath = '.' . trim($path, '.');
    if( !file_exists($tmpPath) || !$files = scandir($tmpPath) ) return FALSE;

    array_splice($files, 0, 2);
    return $files ? $files : array();
}