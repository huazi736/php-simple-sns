 /**
 * Created on  2012-07-31 
 * @author: zhoulang	
 * @desc: 网盘文字
 */
$(function(){   
	//全选
	$("#select").click(function(){
		if($("#select input").attr("checked")){
			$("#diskText input").attr("checked","ture");
		}else{
			$("#diskText input").removeAttr("checked");
		}        
	}) 
	//鼠标移过背景颜色变化
	$("#diskText li").mouseover(function(){
		$(this).css("background",'#ebeef5')
	}).mouseout(function(){
		$(this).css('background','')
	});
	//点击更多获取数据
	$('#moreBlog').click(function(){
		$.ajax({
			url:'',
			dataType:'jsonp',
			success:function(json){}
		})
	});
	$.ajax({   
		url:'http://api.com/app/user/info/list.json',
		dataType:"jsonp",
		success:function(json){ 
			var oCon=eval(json);
			for(var i=0;i<oCon.length;i++)
			{
				var oLi=$("<li uId="+oCon[i].id+"><label><input type='checkbox'/><span>"+oCon[i].content+"</span></label></li>");
				$("#diskText").append(oLi);		
			}
		},
		error:function(){
			alert(arguments[1]);
		}
	});
	
	//将选中的提交
	var data=[];
	$("#showInDate").click(function(){
		$("#diskText li[ischeck=true]").each(function(index) {
			var arr=[];
			arr[0]=$(this).attr('uId'); //ID
			arr[1]=$(this).children('label').children('span').text(); //文字内容
            data.push(arr); 
        });
		$("#data").val(data);
		$("#dataForm").submit();
	});
})
