<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>前端架构库--插件库</title>
<link type="text/css" rel="stylesheet" href="css/F2E_center.css" />
<link type="text/css" rel="stylesheet" href="/misc/css/common/base.css" />
<link href="http://dev.duankou.com/misc/css/plug-css/comment-easy/comment_easy.css" rel="stylesheet" type="text/css" />
<link href="http://dev.duankou.com/misc/css/ask/ask.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="/misc/js/jquery.min.js"></script>
<script type="text/javascript" src="/misc/js/common/utils.js"></script>


<script type="text/javascript" src="/misc/js/plug/calendar/dk_calendar.js"></script>
<link href="http://dev.duankou.com/misc/css/plug-css/calendar/dk_calendar.css" rel="stylesheet" type="text/css" />

<script type="text/javascript" src="/misc/js/plug/dk-tip/dk.tip.js"></script>
<link href="http://dev.duankou.com/misc/css/plug-css/tip/jquery.tip.css" rel="stylesheet" type="text/css" />

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
   
    $(".html_date").calendar({button:false, time:false});
    $(".tip").tip({
        width:"auto",
        showOn:"click",
        hold:true,
        pBox:$("body")
    });
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
                <th width="">名称</th>
                <th width="">效果</th>
                <th width="400">说明</th>
                <th>引用地址</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td name="name">日历插件 dk_calendar</td>
                <td name="result"><input type="text" begin_year=" 1986-3-5" end_year="2012-7-10" value="2012-7-10" now="2012-7-10" name="datetime" class="html_date" id="date_a" autocomplete="off" /></td>
                <td name="memo">
                    可设置：开始日期、结束日期、当前日期、操作按钮、时分秒
                    $(".html_date").calendar({button:false,time:false});
                 
                </td>
                <td name="files"><a tip="<iframe src=http://dev.duankou.com/misc/js/plug/calendar/dk_calendar.js></iframe>" class="tip" target="_blank">http://dev.duankou.com/misc/js/plug/calendar/dk_calendar.js</a><br><a target="_blank" class="tip" tip="<iframe src=http://dev.duankou.com/misc/css/plug-css/calendar/dk_calendar.css></iframe>">http://dev.duankou.com/misc/css/plug-css/calendar/dk_calendar.css</a></td>
            </tr>
            <tr>
                <td name="name">提示 tip</td>
                <td name="result"><span class="tip" tip="这是一个例子">点点看</span></td>
                <td name="memo">两种skin 默认白、全黑，可设置默认展开方向，触发事件<br>
                    $(".tip").tip({width:"auto",showOn:"click",hold:true,pBox:$("body")});
                </td>
                <td name="files"><a tip="<iframe src=http://dev.duankou.com/misc/js/plug/dk-tip/dk.tip.js></iframe>" class="tip" target="_blank">http://dev.duankou.com/misc/js/plug/dk-tip/dk.tip.js</a><br><a target="_blank" class="tip" tip="<iframe src=http://dev.duankou.com/misc/css/plug-css/tip/jquery.tip.css></iframe>">http://dev.duankou.com/misc/css/plug-css/tip/jquery.tip.css</a></td>
            </tr>
        </tbody>
    </table>
</div>
</body>
</html>
