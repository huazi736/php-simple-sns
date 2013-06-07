/*
 * Created on 2011-12-26
 * @author: Yewang
 * @desc: 注册各个步骤
 */
 /*
 * update on 2012-3-26
 * @author: Tingting
 * @desc: 注册各个步骤
 */
var reg = {
	init:function() {
		this.confirmEmail();
		this.searchFirends();
		if($(".firendList")[0]){
			this.addAttention();
		}
	},
	confirmEmail:function(){
		//更改邮件地址
		var $nowEmail = $('#nowEmail'), //原邮箱地址
			$check = $("#check");        //
		$('#changEmail').click(function(){
			var text = $nowEmail.text();
			if(text.length > 30) {
				text = text.substring(0,29) + '...';
			}
			$(this).popUp({
				width:470,
				title:'更改邮箱地址',
				content:'<ul class="changEmail"><li><label>现有的电子邮箱：</label><span class="putEmail" title="' + $nowEmail.text() + '">' + text + '</span></li><li><label>新的邮箱：</label><input class="inputTxt" id="newEmail" type="text" maxlength="64" /><span class="error"></span></li></ul>',
				buttons:'<span id="changBtn" class="popBtns blueBtn callbackBtn">改变电子邮箱地址</span><span class="popBtns closeBtn">取消</span>',
				mask:false,
				maskMode:true,
				callback:function(){changBtn();}
			});
		});
		//邮箱验证
		function changBtn(){
			var _this = $(this);
				$newEmail = $('#newEmail'),  //新邮件地址
				val = $.trim($newEmail.val()),
				$error = $(".error");             //错误提示
				
			$error.hide();
			if(val === '') {
				$error.text('请输入邮箱地址').show();
				$newEmail.focus();
				return false;
			}
			if(val.length < 6 || !validator.email.test(val)) {
				$error.text('邮箱格式不正确').show();
				$newEmail.focus();
				return false;
			}
			if($.trim($nowEmail.text()).toUpperCase() === val.toUpperCase()){
				$error.text('与已填邮箱一致，请更换邮箱').show();
				return false;
			}
			$.djax({
				type: 'post',
				dataType: 'json',
				data: {nowEmail:$nowEmail.text(),newEmail:$newEmail.val(),dkcode:$nowEmail.attr('dkcode')},
				url: mk_url('front/register/change_email'),
				success: function(data){
					if(data.status === 1){
						$nowEmail.text($newEmail.val());
						if(data.data === 0){
							$check.hide();
						}else{
							$check.show();
							$check.find('a').attr('href',data.data);
						}
						$("#changBtn").next().click();
						$.alert(data.info);
					}
					if(data.status === 0){
						$error.text(data.info).show();
					}
				}
			})
		}

		//再次发送邮件
		$('#resendEmail').click(function(){
			var str = '';
			$.djax({
				type: 'post',
				data: {nowEmail:$nowEmail.text()},
				dataType: 'json',
				url: mk_url('front/register/reSendEmail'),
				success: function(data){
					if(data.status ===1){
						str = '<p>我们又向' + $nowEmail.text() +'发送了一封电子邮件</p><p>请点击电子邮件中的连接确认您的电子邮件地址。一定要检查您的垃圾邮件文件夹</p>';
					}
					if(data.status === 0){
						str = '<p style="text-align:center;">' + data.info + '</p>';
					}
					$(this).popUp({
						width:470,
						title:'重新发送邮件',
						content:'<div class="resendEmail">' + str + '</div>',
						buttons:'<span id="resendBtn" class="popBtns blueBtn callbackBtn">确定</span><span style="display:none;" class="popBtns closeBtn">取消</span>',
						mask:false,
						maskMode:true,
						callback:function(){
							$('#resendBtn').next().click();
						}
					});
				}
			});
			
		});
	},
	searchFirends:function() {
		var $searchEmail = $('#searchEmail'),
			$searchPwd = $('#searchPwd'),
			em = /^\w+([-+.]\w+)*$/;
		//填写邮箱
		$('#searchFriends').click(function(){
			var $this = $(this),
				emailVal = $.trim($searchEmail.val()),
				pwdVal = $.trim($searchPwd.val());
			$('span.error').text('');
			if(emailVal === '' && pwdVal === ''){
				$searchEmail.next().next().text('请输入邮箱地址').show();
				$searchPwd.next().text('请输入密码').show();
				return false;
			}
			if(emailVal === '') {
				$searchEmail.next().next().text('请输入邮箱地址').show();
				return false;
			}
			if(!em.test(emailVal)){
				$searchEmail.next().next().text('您输入的邮箱格式不正确').show();
				return false;
			}
			if(pwdVal === ''){
				$searchPwd.next().text('请输入密码').show();
				return false;
			}
			$("[name='loginEmail']").get(0).submit();
		});
	},
	//添加关注
	addAttention:function(){
		function addAttention(){
			var $firendList = $('#firendList'),
				$checkAll = $('#checkAll'),
				$input = $firendList.find('li input:checkbox'),
				len = $input.length;
			var	data = [];
			$input.click(function(){
				if(this.checked == true){
					this.value = 1;
				}else{
					this.value = 0;
				}
			});
			$('#checkAll').click(function(){
				if(this.checked ==true){
					for(var i=0; i<len; i++){
						$input.eq(i).val(1);
						$input.eq(i).attr('checked',true);
					}
				}else{
					for(var i=0; i<len; i++){
						$input.eq(i).val(0);
						$input.eq(i).attr('checked',false);
					}
				}
			});
			$('#addAttention').click(function(){
				var $checkInput = $firendList.find('li input:checked');
				if($checkInput.length < 1) {
					alert('未选择任何用户');
					return false;
				}
				$checkInput.each(function(){
					//dada.data.push(this.name);
					data.push(this.name);
				});
				$.djax({
					type: 'post',
					url: mk_url('front/register/follow'),
					data: {data:data.join(",")},
					dataType: 'json',
					success: function(data){
						if(data.state == 1){
							alert('恭喜您关注成功，现在开始端口之旅！');
							window.location.href = webpath;
						}
						if(data.state == 0){
							window.location.href = webpath;
						}
						
					}
				})
			});
		}

		addAttention();
	}
};
$(function() {
	reg.init();
});
