/*
	电影频道时间线/发表框
	author:liying
*/

var Movies=function(){
	this.moveObj=$("#movieDK");
	this.inputField=this.moveObj.find("input.text");
	
}
Movies.prototype={
	checkPost:function(e){
			var value=$(e).val();
			if(value!==''){
				$(e).prev(".label").text('');
			}else{
				var datar=$(e).attr("require-data");
				$(e).prev(".label").text(datar);
			}
	},
	formsubmit:function(){
		var _self=this;
		$.ajax({
			type:"GET",
			url:"http://liying.duankou.com/www_duankou/interest/movies/test?p_type=submit",
			dataType:"jsonp",
			success:function(data){
				$.djax({
					url:"http://liying.duankou.com/www_duankou/interest/movies/test?p_type=submit",
					dataType:"jsonp",
					async:true,
					data:data.data,
					success:function(data) {
						console.log(data.data);
						if (data.status == 1) {
							var tempA = class_timeline.CLASS_TIMELINE_NAV.addNewYear(data, _self._class,_self.classTimeLine);
							class_postBox.siderClick([tempA, data]);
							alert("发布成功");
						} else {
							alert('发布失败了，请稍后重试！');
						}
					},
					error:function(data) {
						alert("网络错误，请重试！");
					}
				});
			}
		});
	},
	addEvent:function(){
		var _this=this;
		this.inputField.bind("keyup",function(){
			_this.checkPost(this);
		});
		this.inputField.bind("blur",function(){
			_this.checkPost(this);
		});
		$("#distributeButton").click(function(){
			_this.formsubmit();
		});
		
	}
}
var movies = new Movies();


