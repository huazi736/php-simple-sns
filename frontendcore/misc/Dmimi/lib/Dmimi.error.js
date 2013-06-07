/*
	@错误处理
	
	desc 
		1.友好的出错提示
		2.修改建议
*/

DMIMI.error = {
	error:"错误",
	undefined:function(text){
		return text+" 找不到"
	},
	notFunction:function(text){
		return text+"不是一个方法"
	}
}