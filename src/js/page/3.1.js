/**
 * 3.0.html 页面JS
 */
var layer,laytpl;
layui.use(['layer', 'laytpl'], function(){
    layer = layui.layer;
    laytpl = layui.laytpl;
});


/*单文件上传*/
    var myUploads = new BPUploads("/index.php");
    myUploads.beforeDo(function(o){
        var tmp = "0%";
        $("#progressBar").css("width", tmp).find("num").text(tmp);
    }).sendingDo(function(o, now, count){
        var tmp = parseInt( (now/count)*100 ) + '%';
        $("#progressBar").css("width", tmp).find("num").text(tmp);
    }).sendendDo(function(o, data, nowFile){
        data.filename = nowFile.name;
        $("#progressBar").find("num").text("上传成功！");
        $.post("/index.php?Complete=1", data, function(data){
            $("#strshow").html(data['str_show']);
        }, 'json');
    });
    myUploads.resetendDo = function(){
        var tmp = "0%";
        $("#strshow").html('&nbsp;');
        $("#progressBar").css("width", tmp).find("num").text(tmp);
    };
    myUploads.msg = true;


/*多文件上传*/
    var myUploads2 = new BPUploads("/index.php", {
        dragUpload: {
            dragBoxId: 'thumbnail',
        },
    });
    myUploads2.beforeDo(function(o, file){
        laytpl(thumbTpl.innerHTML).render({id:o.ID, file:file}, function(html){
            $("#thumbList").append(html).next().html('');
        });
    }).sendingDo(function(o, now, count){
        var tmp = parseInt( (now/count)*100 ) + '%';
        $('#'+o.ID).find(".progress-bar").css("width", tmp).find("num").text(tmp);
    }).sendendDo(function(o, data, nowFile){
        data.filename = nowFile.name;
        $('#'+o.ID).find(".progress").children().removeClass("progress-bar-warning").addClass("progress-bar-success");
        $.post("/index.php?Complete=1", data, function(data){}, 'json');
    });
    myUploads2.msg = false;
