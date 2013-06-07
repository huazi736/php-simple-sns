<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>前端架构库--按钮库</title>
<link type="text/css" rel="stylesheet" href="css/F2E_center.css" />
<link type="text/css" rel="stylesheet" href="/misc/css/common/base.css" />
<link href="http://dev.duankou.com/misc/css/plug-css/comment-easy/comment_easy.css" rel="stylesheet" type="text/css" />
<link href="http://dev.duankou.com/misc/css/ask/ask.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="/misc/js/jquery.min.js"></script>
<script type="text/javascript" src="/misc/js/common/utils.js"></script>
<style>
body{
    background:#fff;
}
.btn_list td,.btn_list th{
    border:1px solid #ccc;
    text-align:left;
    padding:5px 5px 5px 8px;
}
/*<btn_lsit>*/

    /*<.blueBtn>*/
    .blueBtn{

    }




    /*</.blueBtn>*/


/*</btn_lsit>*/
</style>

<script>
$(document).ready(function(){
    var btn_list = [
        [".btnBlue",'<span class="btnBlue"><a>发送</a></span>'],
        [".btnGray",'<span class="btnGray"><i></i><a href="javascript:void(0);" id="btnCreate">创建活动</a></span>'],
        [".uiButton",'<span class="uiButton">发送</span>'],
        [".friendBtns",'<div class="friendBtns"><a class="btn"><i class="followed"></i><span>提问</span></a></div>'],
        [".reply_btn",'<div class="reply_btn" style="display: block; position:static;width:45px"><span>发表</span></div>'],
        [".dropMenu",'<div class="userName"><div class="dropMenu"><div class="triggerBtn" style=""><i></i><span class="fl" time="2012">2012年</span><s></s></div><div class="dropList" style="top: 22px; left: 0pt;"><ul class="dropListul checkedUl"><li class=""><a href="javascript:void(0)" class="itemAnchor"><i></i><span time="1986">出生</span></a></li></ul></div></div></div>'],
        [".dropMenu",'<div tip="公开" class="dropWrap dropMenu tip_up_right_black" uid="" s="1" oid="123" tipid="tip_3" style="z-index: 0;"><input type="hidden" value="1" name="permission"><div class="triggerBtn" style=""><u class="o"></u><span>公开</span><s></s></div><div class="dropList" style="top: 22px; right: 0pt;"><ul class="dropListul checkedUl"><li><a class="itemAnchor" rel="8" href="javascript:void(0);"><i></i><u class="s"></u><span>仅限自己</span></a></li><li><a class="itemAnchor" rel="4" href="javascript:void(0);"><i></i><u class="fr"></u><span>好友</span></a></li><li><a class="itemAnchor" rel="3" href="javascript:void(0);"><i></i><u class="fan"></u><span>粉丝</span></a></li><li class="current"><a class="itemAnchor" rel="1" href="javascript:void(0);"><i></i><u class="o"></u><span>公开</span></a></li><li><a class="itemAnchor" rel="-1" href="javascript:void(0);"><i></i><u class="c"></u><span>自定义</span></a></li></ul></div></div>']
    ]
    function run(data){
        var str = "";
        for(var i=0;i<data.length;i++){
            str += '<tr><td name="name">'+data[i][0]+'</td><td name="result">'+data[i][1]+'</td><td name="html">'+replaceBrackets(data[i][1])+'</td></tr>'
           
        }
        $("#jsbtn_list").children("tbody").html(str);
    }
    run(btn_list);
});
</script>
</head>

<body>

<div class="frame clearfix">
    <!--<div class="from">
        位置：<select><option>首页</option></select>
    </div>
    -->
	<table width="100%" class="btn_list" id="jsbtn_list">
        <thead>
            <tr>
                <th width="200">名称</th>
                <th width="200">效果</th>
                <th>源码</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td name="name"></td>
                <td name="result"></td>
                <td name="html"></td>
            </tr>
        </tbody>
    </table>
</div>
</body>
</html>
