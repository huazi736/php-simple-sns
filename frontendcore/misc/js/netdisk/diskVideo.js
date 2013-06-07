 /**
 * Created on  2012-07-31 
 * @author: zhoulang	
 * @desc: 网盘视频
 */
 $(document).ready(function() {
	 //全选
	 $("#select").click(function(){
		if($("#select input").attr('checked')){
			$("#diskVideo li").attr({'ischeck':'true',"class":'opacity'});
			$(".look,.check_pic").css("display","none");
			$(".check").css("display",'block');
		}else{
			$("#diskVideo li").attr({'ischeck':'false',"class":""});
			$(".look,.check_pic").css("display","block");	
			$(".look,.check_pic").css("display","block");
			$(".check").css("display","none");
		}	 
	});
	
	//点击ajax获取更多数据
	$('#moreBlog').click(function(){
		$.ajax({
			url:'http://api.com/app/user/video/list.json',
			dataType:'jsonp',
			success:function(json){
				if(json){
					var oCon=eval(json);
					for(var i=0;i<oCon.length;i++)
					{ 
						var oLi=$("<li ischeck='false' uId="+oCon[i].id+" src="+oCon[i].path+"><img src="+oCon[i].picture+" /><span class='look'>预览</span><span class='check_pic'>选取</span><div class='check'></div></li>");
						$("#diskVideo").append(oLi);	
					}	
				}	
			}
		})
	});
				
				$("#diskVideo li .check_pic").live("click",function(ev){
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
				$("#diskVideo li").live("mouseover",function(){
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
	
	//数据提交
	var data=[];
	$("#showInDate").click(function(){
		$("#diskVideo li[ischeck=true]").each(function(index) {
			var arr=[];
			arr[0]=$(this).attr('uId');  //ID
			arr[1]=$(this).children('img').attr('src'); //视频图片地址
			arr[2]=$(this).children('img').attr('alt'); //视频图片提示
			arr[3]=$(this).attr('src');  //视频地址
            data.push(arr);
        });	
		$("#data").val(data);
		$("#dataForm").submit();      
	});
});