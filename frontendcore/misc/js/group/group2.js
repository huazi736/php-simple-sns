/**
 * @author:    duxianwei
 * @created:   2012/05/31
 * @version:   v1.0
 * @desc:      群组模块
 */

$(function(){
	//web-box添加鼠标移上去以及点击效果
	var addHover = function(obj){
		obj.bind({
			"mouseover":function(){
				$(this).addClass("hover");
			},
			"mouseout":function(){
				$(this).removeClass("hover");
			},
			"click":function(){
				var sub = $(".sub");
				obj.removeClass("click");
				$(this).addClass("click");
				var value = $(".click p").attr("title");
				var save_value=$("#save_value");
				save_value.val(value);
				sub.click();
			}
		});
	}
	addHover($(".web-box"));

	//第三步如果选取的好友数量少于1个则不让创建群组
	var checkedCount = function(obj){
		obj.die().live({
			"click":function(){
				var $this = $(this);
				if ($this.hasClass("checked")) {
					$this.removeClass("checked").find("input").removeAttr("checked");
				}
				else{
					$this.addClass("checked").find("input").attr("checked","checked");;
				}
				var count = $(".checked").length;
				var start = $("#start input");
				if (count > 0) {
					start.removeAttr("disabled");
					start.removeClass("dis");
				}
				else{
					start.attr("disabled","disabled");
					start.addClass("dis");
				}	
			}
		})
	}
	checkedCount($(".check span"));
	$("#searchList").bind("keyup",function(){
		var start = $("#start input");
		start.attr("disabled","disabled");
		start.addClass("dis");
		checkedCount($(".check span"));
	});
});