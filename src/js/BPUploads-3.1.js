/**
 * [断点续传类]
 * @param {String} uploadURL [上传地址]
 * @param {String} config    [配置]
 * @author OJesusO 
 */
function BPUploads(uploadURL, config)
{
    var _OBJ = this,
    URL = false,
    CONF = {
        dragUpload: {
            dragBoxId: '',
        },
        packetSize: 2097152,  //2097152b -> 2M
        uploadAuth: 'ad9a1c9a1f9df2ae0def76ae3b2319d6',
        fileLimit: {
            size: 52428800,   //52428800b -> 50M  上传文件大小限制 最小可限制10b 不需要限制可设为false
            suffix: [],
        },
        upTogetherNum: 5,
    },
    FileList = {};
    UploadNum = 0;
    _OBJ.msg = 0;
    _OBJ.errorMsg = true;
    _OBJ.successMsg = false;
    var uploadsBefore = function(obj, file){ /*上传开始……*/ };
    var sendDoing = function(obj, loadedSize, allSize){ /*上传中……*/ };
    var sendDoEnd = function(obj, data){ /*上传完……*/ };
    var completeAfter = function(){ /*操作完成后|队列文件上传完后*/ };
    var getId = function(len){ return generateMixed(len); };



    /*上传类*/
    function Uploads(){
        var _this = this,
        Uploading = false,
        Packet = [],
        LoadedSize = 0,
        PacketTotal = 0,
        NowFile = {};
        _this.ID = '';

        function OBJ_before(file){
            if( !file ) return false;
            if( CONF.fileLimit ) {
                var tmp = file.name; tmp = tmp.split('.').pop(); tmp = tmp.toLowerCase();
                if( CONF.fileLimit.size>10 && CONF.fileLimit.size<file.size ) return sysMsg('文件大小异常！['+file.name+']', false, true);
                if( CONF.fileLimit.suffix && CONF.fileLimit.suffix.length>0 && !in_array(tmp, CONF.fileLimit.suffix) ) return sysMsg('文件后缀异常！['+file.name+']', false, true);
            }

            Packet = cutFile(file, CONF.packetSize, 0);
            if( Packet.length <= 0 ) return sysMsg('文件处理出错！['+file.name+']', false, true);

            _this.ID = getId();
            NowFile = file;
            var msg = uploadsBefore(_this, file);
            return msg===false ? false : true;
        };
        function cutFile(file, length, start){
            var tmp = [];
            var end = 0;
            var size = file.size;

            start = parseInt(start);
            start = start ? start : 0;
            while(size > 0)
            {
                length = (length>size) ? size : length;
                end = parseInt(start) + parseInt(length);

                tmp.push( file.slice(start, end) );
                start += length;
                size -= length;
            }

            return tmp;
        };
        function Send(num, url){
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.onload = function(e){
                var data = eval( '('+ xhr.responseText +')' );
                var state = data.status;
                if( state == 1 )
                {
                    if( data.Packet )
                    {
                        num = data.Packet;
                        LoadedSize = PacketTotal*num;
                    }
                    else
                    {
                        LoadedSize += PacketTotal;
                        num++;
                    }
                    if( num < Packet.length ) Send(num, URL+data.url);
                    else SendEnd(data);
                }
                else
                {
                    Msg(2, '上传失败！请刷新后重试！');
                    SendEnd();
                }
            };
            xhr.upload.onprogress = function(e){
                /*e.loaded:该片文件上传了多少 / e.total:该片文件的总共大小*/
                PacketTotal = e.total;
                var loadedSize = LoadedSize+e.loaded;
                sendDoing(_this, loadedSize, NowFile.size);
            };
            xhr.send(Packet[num]);
        };
        function SendEnd(data){
            if( typeof(data)!='undefined' && data.complete==1 )
            {
                Msg(1, '上传完成！');
                sendDoEnd(_this, data, NowFile);
            }
            LoadedSize = 0;
            PacketTotal = 0;
            NowFile = {};
            delete FileList[ _this.ID ]; UploadNum--;
            if( Object.keys(FileList).length > 0 ){
                for(var i in FileList) { FileList[i].upContinue(); break;}
            } else {
                return completeAfter();
            }
        };
        function uploads(){
            UploadNum++;
            var suffix = NowFile.name.split('.').pop();
            Send(0, URL+'?packet=0&auth='+CONF.uploadAuth+'&num='+Packet.length+'&suffix='+suffix);
        };
        _this.uploads = function(file){
            if( OBJ_before(file) !== true ) return false;

            FileList[ _this.ID ] = _this;
            if( UploadNum < CONF.upTogetherNum ) uploads();
        };
        _this.upContinue = function(){
            uploads();
        };
    };

    /*文件拖拽上传*/
    function dragDropUpload(){
        if( typeof(CONF.dragUpload)!='object' || !CONF.dragUpload.dragBoxId ) return;
        var obj = document.getElementById(CONF.dragUpload.dragBoxId);
        if( obj == null ) return sysMsg('拖拽容器获取出错！', false);

        //文件拖拽到指定区域时候执行
        obj.addEventListener('dragover', function(evt){
            evt.stopPropagation();
            evt.preventDefault();
            evt.dataTransfer.dropEffect = 'copy';
        }, false);
        //文件被释放到置顶区域时候执行
        obj.addEventListener('drop', function(evt){
            evt.stopPropagation();
            evt.preventDefault();
            _OBJ.uploads( evt.dataTransfer.files );
        }, false);
    };

    /*改变配置*/
    function changeConf(){
        config = config||{};
        for(var i in CONF) if( typeof(CONF[i])=='object' && typeof(config[i])=='object' ) {
            config[i] = $.extend({}, CONF[i], config[i]);
        }
        CONF = $.extend({}, CONF, config||{});
        uploadURL && (URL = uploadURL);
    };

    /*模拟构造*/
    (function(){
        changeConf();
        dragDropUpload();
    })();



    /*上传动作*/
    _OBJ.uploads = function(files){
        if( !files ) return false;
        if( CONF.upTogetherNum > 0 ){} else return sysMsg('可连续上传数量异常！', false);
        for(var i=0; i<files.length; i++) new Uploads().uploads(files[i]);
    };
    _OBJ.beforeDo = function(fun){
        if( typeof(fun) == 'function' ) uploadsBefore = fun;
        return _OBJ;
    };
    _OBJ.sendingDo = function(fun){
        if( typeof(fun) == 'function' ) sendDoing = fun;
        return _OBJ;
    };
    _OBJ.sendendDo = function(fun){
        if( typeof(fun) == 'function' ) sendDoEnd = fun;
        return _OBJ;
    };
    _OBJ.completeDo = function(fun){
        if( typeof(fun) == 'function' ) completeAfter = fun;
        return _OBJ;
    };
    _OBJ.setIdFun = function(fun){
        if( typeof(fun) == 'function' ) getId = fun;
        return _OBJ;
    };



    /*辅助函数*/
    function Msg(state, info, fun){
        if( _OBJ.msg === false ){

        } else if( typeof(layer) == 'undefined' ) {
            alert(info);
            if( typeof(fun) != 'undefined' ) fun();
        } else if( _OBJ.msg ) {
            layer.msg(info, {'icon':state, 'time':1500}, function(){
                if( typeof(fun) != 'undefined' ) fun();
            });
        } else if( state==1 && _OBJ.successMsg ) {
            layer.msg(info, {'icon':1, 'time':1500}, function(){
                if( typeof(fun) != 'undefined' ) fun();
            });
        } else if( state==2 && _OBJ.errorMsg ) {
            layer.msg(info, {'icon':2, 'time':1500}, function(){
                if( typeof(fun) != 'undefined' ) fun();
            });
        }
        return (state==1) ? true : false;
    };
    function sysMsg(info, state, msg){
        msg || (msg = false);
        msg && Msg(2, info);
        console.log('submitData：'+info); return state;
    };
    function generateMixed(n) {
        n || (n = 8);
        var res = "";
        var chars = ['0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
        for(var i = 0; i < n ; i ++) {
            var id = Math.ceil(Math.random()*35);
            res += chars[id];
        }
        return res;
    }
}