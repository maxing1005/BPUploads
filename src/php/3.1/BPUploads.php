<?php
include 'BPUploads.class.php';


function fileUploadComplete($data) {
    $name = isset($data['filename'])&&!empty($data['filename']) ? $data['filename'] : basename($data['fileURL']);
    $old = '.'.$data['fileURL'];
    $new = './src/php/TMP/'.$name;
    $msg = copy_file($old, iconv("UTF-8","gb2312",$new), TRUE);

    $data['fileURL'] = $new;
    $data['str_show'] = <<<EOF
      COMPLETE: {$data['complete']}
      STATUS: {$data['status']}
      INFO: {$data['info']}
      FILEURL: {$data['fileURL']}
EOF;
    return $data;
}


/* 普通写法（比较兼容不同的环境），前台请求成功后，再次请求成功回调 */
    if( isset($_GET['Complete']) && $_GET['Complete'] == 1 ) {
        //模拟上传完成后 回调操作，正是使用的时候可以不与上传操作放一起
        //以ThinkPHP的用法，例如把上传放到工具模块中|第三方类库中，把回调放到对应的操作中（例如Goods模块的Tool控制器的bpuCallback操作）
        ajaxReturn( fileUploadComplete($_POST) );
    } else {
        // 上传操作
        $BPUploads = new BPUploads('./src/php/TMP/tmpPacket/');
        $msg = $BPUploads->uploads();
        ajaxReturn($msg);
    }
/*-------------------------------*/



/* 闭包写法（使用在只有一处上传的环境|写死都回调哪个函数，或者直接修改BPUploads的uploads,传入闭包函数 成功后执行），判断上传结果，如果上传完成后调用具体函数 （前端对应地方需要删除，不需要再次请求后台进行回调操作） */
#    // 上传操作
#    $BPUploads = new BPUploads(__DIR__ . '/../TMP/tmpPacket/');
#    $msg = $BPUploads->uploads();
#    if (isset($msg['complete'])) {
#
#    }
#    ajaxReturn($msg);
/*-------------------------------*/
 








