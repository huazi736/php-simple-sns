/**
 * @author:    zhangbo
 * @created:   2012/3/5
 * @version:   v1.0
 * @desc:      邀请码
 */
 /**
 * @author:    chengting
 * @created:   2012/7/23
 * @version:   v1.0
 * @desc:      把keyup事件改成blur事件
 */
$(document).ready(function() {

	var inviteCode_url = mk_url("main/invitecode/duankou_num"); //发送邀请码
	var checkmobile_url = mk_url("main/invitecode/checkmobile"); //验证手机是否注册
	var addFollow_url = mk_url("main/api/addFollow"); //添加关注
	var unfollow_url = mk_url("main/api/unFollow"); //取消关注
	var addFriend_url = mk_url("main/api/addFriend"); //加好友
	var delFriend_url = mk_url("main/api/delFriend"); //删除好友
	var loadmore_url = mk_url("main/invitecode/get_recommend_loadmore"); //点击加载更多
	var isSend = 0;
	var isSendName =0;
	//保存两个常用的JQ对象
	var $codeContent = $('#codeContent'),
		$codeBlock_form = $codeContent.find('div.codeBlock_form'),
		$condSend = $('#condSend');
	// 实时验证姓名
	var inviteName_function = function(e){
		var _this = $(this);
		var strName = $.trim($codeBlock_form.find('input[name="userName"]').val());
		var strMobile = $.trim($codeBlock_form.find('input[name="userMobile"]').val());
		
		if(strName.length<1){
			$(this).next().html('<span class="red">请输入被邀请人姓名</span>');
			isSendName = 0;
			return false;
		}

		if (!validator.codeName.test(strName) || strName.length < 2 || strName.length > 10) {
			$(this).next().html('<span class="red">2-10个字符，仅限中、英文</span>');
			$condSend.removeClass().addClass('notSend');
			isSendName = 0;
		}
		else {
			$(this).next().html('<span class="code_ok"> </span>');
			if (strMobile == "") {
				$codeBlock_form.find(".errMsg_mobile").html('<span class="c-tipmsg">请输入被邀请人手机号码</span>');
			}
			else {
				if (!validator.mobile.test(strMobile)) {
					$codeBlock_form.find(".errMsg_mobile").html('<span class="red">请输入正确的手机号码</span>');
					isSendName = 0;
				}
				else {
				if (isSendName == 0) {
					$.djax({
						url: checkmobile_url,
						type: 'post',
						dataType: 'json',
						data: {
							userName: strName,
							userMobile: strMobile
						},
						success: function(result){
							isSend = 1;
							if (result.status == 1) {
								$condSend.removeClass().addClass('Send');
								$('.errMsg_mobile').html('<span class="code_ok"> </span>');
							}
							else {
							
								$condSend.removeClass().addClass('notSend');
							
								$('.errMsg_mobile').html('<span class="red">' + result.info + '</span>');
								
							}
							
							_this.bind("keyup",codeBlock_function);
							}
					});
					_this.unbind("keyup", inviteName_function);
				}
				}
			}
		}

	}
	$codeBlock_form.find('input[name="userName"]').blur(inviteName_function );
	// 实时验证手机号
	var codeBlock_function = function(e) {
		var _this=$(this);
		var userName = $.trim($codeBlock_form.find('input[name="userName"]').val());
		var strMobile = $.trim($codeBlock_form.find('input[name="userMobile"]').val());
		
		if(strMobile.length<1){
			$(".errMsg_mobile").html('<span class="red">请输入被邀请人手机号码</span>');
			isSend = 0;
			return false;
		}

		if (!validator.mobile.test(strMobile)) {
			$(".errMsg_mobile").html('<span class="red">请输入正确的手机号码</span>');
			isSend = 0
		}else {
			if (isSend == 0) {
				$.djax({
					url: checkmobile_url,
					type: 'post',
					dataType: 'json',
					data: {
						userName: userName,
						userMobile: strMobile
					},
					success: function(result){
						isSend = 1;
						if (result.status == 1) {
							$condSend.removeClass().addClass('Send');
							$('.errMsg_mobile').html('<span class="code_ok"> </span>');
						}
						else {
						
							$condSend.removeClass().addClass('notSend');
						
							$('.errMsg_mobile').html('<span class="red">' + result.info + '</span>');
							
						}
						
						_this.bind("keyup",codeBlock_function);
						
					}
				});
				_this.unbind("keyup",codeBlock_function);
			}
		}
	}
	$codeBlock_form.find('input[name="userMobile"]').blur(codeBlock_function );



	//发送邀请

			$('b.Send').live('click', function(){
				var _this = $(this);
				
				var userName = $codeBlock_form.find('input[name="userName"]').val();
				var userMobile = $codeBlock_form.find('input[name="userMobile"]').val();
				var code_surplus = $('#code_surplus');
				if(code_surplus.text() == 0){
					$.alert('您没有剩余邀请码，无法发送邀请！', '温馨提示');
					return false;
				}
				if (validator.codeName.test(userName) && userName.length >= 2 && userName.length <= 10 && validator.mobile.test(userMobile)) {
					$condSend.text("发送中...")
					$condSend.removeClass().addClass('notSend');
					$.ajax({
						url: inviteCode_url,
						type: 'post',
						dataType: 'json',
						data: {
							userName: userName,
							userMobile: userMobile
						},
						success: function(result){
							$codeBlock_form.find('input[name="userName"]').val('');
							$codeBlock_form.find('input[name="userMobile"]').val('');
							
							if (result.status == 1) {
								code_surplus.text(result.data.renums);
								$codeBlock_form.find('.errMsg_name').html('<span class="c-tipmsg">请输入被邀请人姓名</span>');
								$codeBlock_form.find('.errMsg_mobile').html('<span class="c-tipmsg">请输入被邀请人手机号码</span>');
								$condSend.text("发送");
								$condSend.removeClass().addClass('Send');
								if(result.data.dk_code){
									$.alert('邀请码发送成功，请尽快通知您的朋友进行注册！</br>你也可以复制邀请码发给朋友“' + userName + '”，邀请码：<span style="color:red">' + result.data.dk_code + "</span>", '发送邀请码');
								}else{
									$.alert('邀请码发送成功，请尽快通知您的朋友" <strong>' + userName + '</strong> "进行注册！', '发送邀请码');
								}
							}  
							else {
								$condSend.removeClass().addClass('notSend');
								$('.errMsg_mobile').html('<span class="red">' + result.info + '</span>');
							}
						}
					});
				}
				else {
					if (userName == "") {
						$codeBlock_form.find(".errMsg_name").html('<span class="red">请输入被邀请人姓名</span>');
					}
					else {
						if (!validator.codeName.test(userName) || userName.length < 2 || userName.length > 10) {
							$(this).next().html('<span class="red">2-10个字符，仅限中、英文</span>');
							
						}
						else {
							$condSend.removeClass().addClass('notSend');
							$(this).next().html('<span class="code_ok"> </span>');
							
						}
					}
					if (userMobile == "") {
						$codeBlock_form.find(".errMsg_mobile").html('<span class="red">请输入被邀请人手机号码</span>');
					}else {
						if (!validator.mobile.test(userMobile)) {
							$codeBlock_form.find(".errMsg_mobile").html('<span class="red">请输入正确的手机号码</span>');
							
						}
					}
					return false;
				}
			});


	//添加关注
	$codeContent.find('div.statusBox').relation();

	//点击更多请求，异步加载 
	pageflag = {
		page: 2,
		setFlag: function(f) {
			this.page = f;
		}
	};
	$codeContent.find("div.codeBottom > a").click(function(){
		//获取总页数
		var maxpage = $(this).attr('pagecount');
		var uid = $(this).attr('id');
		var _this = this;
		if (pageflag.page <= maxpage) {
			$('.is-loading').css("display", "block");
			$.ajax({
				url: loadmore_url,
				type: 'post',
				dataType: 'json',
				data: {
					uid: uid,
					nowpage: pageflag.page
				},
				success: function(result) {
					if (result.status == 1) {
						$('#item_box').append(result.data).find('div.newItem').removeClass('newItem').relation();
						pageflag.setFlag(pageflag.page + 1);
					} else {
						$.alert(result.info);
					}
				}
			});
			if (pageflag.page == maxpage) {
				$(_this).parent().css('display', 'none');
			}
			$('.is-loading').css("display", "none");
		}
	});

});