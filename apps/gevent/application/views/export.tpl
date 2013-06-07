<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
	<title>端口网</title>
	<style type="text/css">
	html,body,div,p,h1{margin:0; padding:0;}
	body{font-family: Tahoma,Verdana,Arial,sans-serif;}
	#wrap{width:80%; margin:30px auto; border:1px solid #ccc; border-bottom:2px solid #3B5998; background:#fff;}
	h1{background: #3B5998; border-bottom: 1px solid #133783; box-shadow: 0 0 2px rgba(0, 0, 0, 0.52); color:#fff; font-size:24px; padding:5px 20px;}
	.con{padding:10px 20px 20px 20px;}
	.con div{padding:0 0 20px 38px; line-height:26px;}
	.con div p{padding-top:5px;}
	</style>
</head>
<body>
<div id="wrap">
	<h1>Duankou</h1>
	<div class="con">
		<h3>嗨，<!--{$user.username|escape:'html'}-->你好,</h3>
		<div>
			您在<b>Duankou</b>有以下活动<br />
			<p>
				<b><!--{$event.name|escape:'html'}--></b><br />
				活动时间：<!--{$event.starttime}-->开始　<!--{$event.endtime}-->结束<br />
				活动地点：<!--{$event.address|escape:'html'}-->&nbsp;<!--{$event.city|escape:'html'}-->&nbsp;<!--{$event.street|escape:'html'}--><br />
				详细信息：<!--{$event.detail|escape:'html'}-->
			</p>
		</div>
		<b>欢迎来到Duankou!</b><br /><br />
		<b>Duankou团队</b>
	</div>
</div>
</body>
</html>
