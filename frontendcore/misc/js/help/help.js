/**
*@author:    唐小剑
*@created:   2011/11/24
*@desc:      帮助页面
*@version:    v1.0
**/
$(function(){
	$("p.tt").click(function(){
		if($(this).next().is(":hidden")){
			$(this).addClass("changeBg").next().show();
		}else{
			$(this).removeClass("changeBg").next().hide();
		}
		
	});
});