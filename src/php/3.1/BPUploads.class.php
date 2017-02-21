<?php
/**
 * 文件断点续传模型
 * @author OJesusO
 */
class BPUploads {

    private $is_complete = FALSE;
    private $packet, $first, $auth, $suffix, $num, $dir;
    protected $autoCheckFields = false;
    public function __construct($dir='/TMP/tmpPacket/')
    {
        $this->dir = $dir;
        $this->get_param();
        $this->delete_oldfiles($this->dir);
    }


    /**
     * [获取参数]
     * @author OJesusO
     */
    private function get_param()
    {
        $this->packet = isset($_GET['packet']) ? $_GET['packet'] : null;
        $this->first = isset($_GET['first']) ? $_GET['first'] : null;
        $this->auth = isset($_GET['auth']) ? $_GET['auth'] : null;
        $this->suffix = isset($_GET['suffix']) ? $_GET['suffix'] : null;
        $this->num = isset($_GET['num']) ? $_GET['num'] : null;
    }

    /**
     * [获取验证]
     * @return string [返回特定验证规则的字符串]
     * @author OJesusO
     */
    private function auth()
    {
        //第一次的授权为 ad9a1c9a1f9df2ae0def76ae3b2319d6; 请填写至js类中
        return md5("packet={$this->packet}&first={$this->first}");
    }

    /**
     * [获取文件存放路径]
     * @return string [返回组装好的地址]
     * @author OJesusO
     */
    private function path()
    {
        return $this->dir.$this->first;
    }



/*=======================================================================================================*/
/*                                                                                                      |*/
    /**
     * [断点续传操作方法]
     * @return array [上传信息数组]
     * @author OJesusO
     */
    public function uploads()
    {
        if( $this->auth != $this->auth() ) ajaxReturn( Msg(-2) );

        // 第一块文件上传操作
        if( $this->packet == 0 )
        {
            //可以修改为前端中获取文件hash后传入，这里简单的加密第一块的值取得，如果要区分用户的可以加上个用户ID
            //Tip：最好是前端获取文件hash，一起请求上传，并且增加查询数据库是否有该文件，如果有的话直接完成（如果上传文件要区分用户的话，可以复制一份到指定的目录），
            //     如果没有的话，查询文件块的目录是否有合并了未移动的，有的话复制移动，如果还没有 则进行断点上传操作。
            $this->first = md5($GLOBALS['HTTP_RAW_POST_DATA']);
            $tmp = $this->is_uploaded();
            if($tmp != 0)
            {
                $this->packet = $tmp;
                return $this->returnMsg();
            }
        }

        // 存放文件操作
        $this->packet_save();

        // 最后一块文件上传操作
        if( $this->packet == ($this->num-1) ) $this->packet_merge();
    
        // 返回信息
        $this->packet++;
        return $this->returnMsg();
    }
    
    /**
     * [对外接口 用于文件完整上传后返回文件路径]
     * @return string [文件路径]
     */
    public function get_files_path()
    {
        if( $this->is_complete !== TRUE ) return FALSE;
        return $this->path().'.'.$this->suffix;
    }
/*                                                                                                      |*/
/*=======================================================================================================*/



    /**
     * [是否已经上传过该文件，断点续传准备]
     * @return int|str [该值如果大于0 则返回给前台应该从第几块文件开始传]
     * @author OJesusO
     */
    private function is_uploaded()
    {
        // 第一块文件开始
        $tmp = 0;
        while( 1 )
        {
            $tmpdir = $this->path().'/'.$tmp;
            if( !is_file($tmpdir) ) break;

            $tmphandle = fopen($tmpdir, 'rb');
            $tmpread = fread($tmphandle, filesize($tmpdir));
            fclose($tmphandle);
            if( $tmpread ) $tmp++;
            else break;
        }
        return $tmp;
    }

    /**
     * [块文件存放]
     * @author OJesusO
     */
    private function packet_save()
    {
        $path = $this->path();
        create_dir($path);
        $handle = fopen($path.'/'.$this->packet, 'wb');
        fwrite($handle, $GLOBALS['HTTP_RAW_POST_DATA']);
        fclose($handle);
    }

    /**
     * [块文件合并]
     * @author OJesusO
     */
    private function packet_merge()
    {
        $path = $this->path();
        $count = fopen($path.'.'.$this->suffix, 'wb');
        for($i=0; $i<$this->num; $i++)
        {
            $handle = fopen($path.'/'.$i, 'rb');
            $content = fread($handle, filesize($path.'/'.$i));
            fwrite($count, $content);
            fclose($handle);
        }
        fclose($count);
        remove_directory($path);
        $this->is_complete = TRUE;
    }

    /**
     * [文件断点续传信息返回]
     * @return array [信息数组]
     * @author OJesusO
     */
    private function returnMsg()
    {
        if( $this->is_complete !== TRUE )
        {
            $auth = $this->auth();
            $url = "?packet={$this->packet}&first={$this->first}&auth={$auth}&num={$this->num}&suffix={$this->suffix}";
            return Msg(1, '上传成功！', array('url'=>$url, 'packet'=>$this->packet));
        }
        else
        {
            return Msg(1, '上传完成！', array('fileURL'=>trim($this->path().'.'.$this->suffix, '.'), 'complete'=>1));
        }
    }


    /**
     * [删除过期的文件夹]
     * @param  string $dir [需要删除的路径，默认读取$this->dir]
     * @return boolean     [是否删除成功]
     * @author OJesusO
     */
    public function delete_oldfiles($dir)
    {
        $dir || $dir = $this->dir;

        if($handle = opendir($dir))
        {
            $time = time() - 604800; //7*24*60*60;
            while(false !== ($file = readdir($handle)))
            {
                $tmp = $dir.'/'.$file;
                if( $file!='.' && $file!='..' && is_dir($tmp) )
                {
                    $file = $tmp . '/0';
                    if( !is_file($file) || filemtime($file)<$time ) remove_directory($tmp);
                }
            }
            closedir($handle);
            return TRUE;
        }
        else return FALSE;
    }

}



if (!function_exists('create_dir')) {
    /**
     * [创建目录(不存在则创建)]
     * @param string $path [目录路径]
     * @return boolean [是否创建成功]
     * @author OJesusO
     */
    function create_dir($path)
    {
        if( is_dir($path) ) return TRUE;

        $res = mkdir(iconv('UTF-8', 'GBK', $path), 0777, TRUE); 
        return $res ? TRUE : FALSE;
    }
}

if (!function_exists('remove_directory')) {
    /**
     * [删除目录]
     * @param  string  $dir   [目录路径]
     * @param  boolean $clear [是否只清空目录]
     * @return array       [是否删除成功]
     * @author OJesusO
     */
    function remove_directory($dir, $clear=FALSE)
    {
        if( !is_dir($dir) ) return FALSE;
        if ($handle = opendir("$dir")) {
            $msg = array();
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..") {
                    if (is_dir("$dir/$item")) {
                        remove_directory("$dir/$item");
                    } else {
                        if( !is_file("$dir/$item") ) continue;
                        unlink("$dir/$item");
                        $msg[] = " removing $dir/$item<br>\n";
                    }
                }
            }
            closedir($handle);
            if($clear === FALSE) {
                rmdir($dir); $msg[] = "removing $dir<br>\n";
            }
            return $msg;
        }
    }
}

if (!function_exists('copy_file')) {
    /**
     * [复制文件，当delete为真时可当做移动文件]
     * @param  string  $old    [源文件路径]
     * @param  string  $new    [新文件路径]
     * @param  boolean $delete [是否需要删除源文件]
     * @return boolean         [是否操作成功]
     * @author OJesusO
     */
    function copy_file($old='', $new='', $delete=FALSE)
    {
        if( empty($old) ) return FALSE;
        if( empty($new) )
        {
            // $tmp = explode('.', $old);
            // $suffix = array_pop($tmp);
            // $new = implode('.', $tmp) . time() . '_copy_.' . $suffix;
            extract( pathinfo($old) );
            $new = $dirname . '/' . $filename . time() . '_copy_.' . $extension;
        }

        // 获取信息创建路径
        extract( pathinfo($new) );
        create_dir( $dirname );

        // 判断是否复制成功
        if( !copy($old, $new) ) return FALSE;

        // 是否能读取文件
        // $tmphandle = fopen($new, 'rb');
        // $tmpread = fread($tmphandle, filesize($new));
        // fclose($tmphandle);
        $tmpread = is_file($new);
        if( !$tmpread ) return FALSE;

        // 是否需要删除原文件
        return $delete ? unlink($old) : TRUE;
    }
}

if (!function_exists('get_pathFiles')) {
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
}

if (!function_exists('Msg')) {
    /**
     * 消息组装
     * @param str|int $status 消息代码
     * @param string  $info   消息信息
     * @author OJesusO
     */
    function Msg($status=null, $info=null, $param=null)
    {
        $msg = compact('status', 'info');

        if( is_array($param) )
            return array_merge($msg, $param);
        elseif( !empty($param) )
            return array_merge($msg, ['url'=>$param]);
        else
            return $msg;
    }
}

if (!function_exists('ajaxReturn')) {
    function ajaxReturn($data=null) {
       echo json_encode($data); exit;
    }
}