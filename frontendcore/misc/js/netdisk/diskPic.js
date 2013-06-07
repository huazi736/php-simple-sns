/**
 * Created on  2012-07-31 
 * @author: zhoulang	
 * @desc: 网盘图片
 */
 $(document).ready(function(e) {
	 	//全选
	 $("#select").click(function(){
		if($("#select input").attr("checked")){
			$("#diskPic li").attr({"ischeck":'true',"class":"opacity"});
			$(".look,.check_pic").css("display","none");
			$(".check").css("display","block");
		}else{
			$("#diskPic li").attr({"ischeck":'false',"class":""});	
			$(".look,.check_pic").css("display","block");
			$(".check").css("display","none");
		}
		
	});
	
	//点击更多获取数据
	$('#moreBlog').click(function(){
		$.ajax({
			url:'http://api.com/app/user/pic/list.json',
			dataType:'jsonp',
			success:function(json){
				if(json){
					var oCon=eval(json);
					for(var i=0;i<oCon.length;i++)
					{
						var oLi=$("<li ischeck='false' uId="+oCon[i].id+" status="+oCon[i].status+"><img src="+oCon[i].path+" /><span class='look'>预览</span><span class='check_pic'>选取</span><div class='check'></div></li>");
						$("#diskPic").append(oLi);
					}	
				}	
			}
		})
	});
				//点击选择按钮选择
				$("#diskPic li .check_pic").live("click",function(ev){
					$(this).parent().attr({'ischeck':'true','class':'opacity'});
					$(this).next().css('display','block');
					$(this).css('display','none');
					$(this).prev().css('display','none');
					//阻止事件冒泡到li上
					var oEvent=ev||event;
					if(oEvent.stopPropagation)
					{
						oEvent.stopPropagation();
					}else{
				 		oEvent.cancelBubble=true;
				  	}
				});
				
				
				$(".check").live("click",function(){
					$(this).parent().attr('ischeck','false');
					$(this).css('display','none');	
				})
				
				//鼠标移入显示选择按钮 移出隐藏显示按钮
				$("#diskPic li").live("mouseover",function(){
					if($(this).attr('ischeck')=='false'){
						$(this).children('.select_pic').css('display','block');
					}
				}).live("mouseout",function() {
					$(this).children('.select_pic').css('display','none');
				}).live("click",function(){
					if($(this).attr('ischeck')){
						$(this).children('.check').css('display','none');
						$(this).attr('ischeck','false')
						$(this).children('.look').css('display','block');
						$(this).children('.check_pic').css('display','block');
						$(this).attr('class','');
					}
				});
				$('.look').live("click",function(){
					var oPop=$('<div class="pop"></div>')	
				});
	
	
	//将选中的数据提交
	var data=[];
	$("#showInDate").click(function(){
		$("#diskPic li[ischeck=true][status=0]").each(function(index) {
			var arr=[];
			arr[0]=$(this).attr('uId');
			arr[1]=$(this).children('img').attr('srcImg');
			arr[2]=$(this).children('img').attr('alt');
           data.push(arr);						
        });	
		$("#data").val(data);
		$("#dataForm").submit();
	});
});