<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>前端架构库</title>
<link type="text/css" rel="stylesheet" href="css/F2E_center.css" />
<script type="text/javascript" src="/misc/js/jquery.min.js"></script>
<script type="text/javascript" src="js/F2E_center.js"></script>
</head>

<body>
<div class="frame clearfix">
	<ul class="frameThumb" id="frameThumb">
		<?php
        $handler = opendir('demo/');
        while(($filename=readdir($handler))!==false){
            if($filename!='.'&&$filename!='..')
            {
				if($filename!=='.svn'){
					echo '<li><iframe scrolling="no" src="demo/'.$filename.'"></iframe><a href="demo/'.$filename.'" target="_blank"></a><span>'.$filename.'</span></li>'.' ';
				}
            }
        }
        ?>
    </ul>
    <ol class="frameSolid">
    	<ul id="frameSolid">
        	<!--<li class="selected">●</li>
            <li>●</li>-->
        </ul>
    </ol>
</div>
</body>
</html>
