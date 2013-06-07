/**
 * Created on 2012-02-10
 * @author: chengting
 * @version:  2.0
 * @desc: 系统设置
 **/
$(function(){
	
	var $accountEdit = $("#accountEdit");

	//显示一般设置的编辑模块
	$accountEdit.find("div.editItem").click(function(){
		$accountEdit.find("div.editItem").show();
		//$accountEdit.find("div.editContent").hide();
		$(this).hide().next().show();
	});
	
	//密码设置
	//显示密码编辑模块(判断是否设置密保问题)
	$accountEdit.find("div.editItemPrw").click(function(){
		var $this = $(this);
		$.ajax({
			type: "post",
			url: mk_url('main/setting/checkSecurity'),
			dataType: "json",
			success: function(result){
				if(result === true){
					$this.hide().next().show();
				}else{
					$this.hide().next().next().show();
				}
			}
		});
	});
	
	//验证密保问题
	$("#confirm_answer").click(function(){
		var $this = $(this);
		if($.trim($("#answer").val())!==''){
			$.ajax({
				type:"post",
				url:mk_url('main/setting/verifySecurity'),
				data:{question:$("#Question").val(),answer:$("#answer").val()},
				dataType:"json",
				success:function(result){
					if(result === true){
						$this.parents("div.editContent").hide().next().show();
					}else{
						$("#answerError").text("答案错误").show();
						return false;
					}
				}
			});
		}else{
			$("#answerError").text("请输入答案").show();
			return false;
		}
	});
	//修改密码
	var $pwdForm = $("#pwdForm");
	var pwdOld = '' ;//缓存本地旧密码
	$("#save_pwd").click(function(){
		var _old = $pwdForm.find("input[name=pwd_old]").val(); //旧密码
		var _new = $pwdForm.find("input[name=pwd_new]").val(); //新密码
		var _confirm = $pwdForm.find("input[name=pwd_confirm]").val(); //确认密码
		var $textOld = $("#pwd_old"); 
		
		$pwdForm.find("span.errors").hide();
		if(_old === '' || _old.length<6){
			$textOld.show();
			return false;
		}
		
		//判断新密码合法性
		function pwd(){
			var $textNew = $("#pwd_new");
			if(_new === ''){
				$textNew.text("新密码不能为空").show();
				return false;
			}else if(_new.length < 6){
				$textNew.text("新密码不能小于6位").show();
				return false;
			}
			var $textConfirm = $("#pwd_confirm");
			if(_confirm === ''){
				$textConfirm.text("确认密码不能为空").show();
				return false;
			}else if(_confirm.length < 6){
				$textConfirm.text("确认密码不能小于6位").show();
				return false;
			}else if(_confirm !== _new){
				$textConfirm.text("确认密码与新密码不一致").show();
				return false;
			}
			// $pwdForm.children("form").submit();
			$.djax({
				url:mk_url('main/setting/resetPasswd'),
				data:{old_pwd:MD5(MD5(_old)), new_pwd:MD5(MD5(_new))}, //MD5加密
				success:function(data) {
					if(data.state === 1) {
						$.alert('恭喜! 密码修改成功!');
						$('#alertWindow').find('span.popBtns').click(function() {
							window.location.reload();
						});
					} else {
						alert(data.msg);
					}
				}
			});
		}
		
		//后台获取旧密码
		if(pwdOld === ''){
			$.djax({
				url:mk_url('main/setting/verifyOldPasswd'),
				data:{old:MD5(MD5(_old))},
				success:function(result){
					if(result.state === 1){
						pwdOld = _old;
						pwd();
					}else{
						$textOld.show();
					}
				}
			});
		}else if(_old !== pwdOld){
			$textOld.show();
			return false;
		}else{
			pwd();
		}
		return false;
	});



	//语言设置
	var $lagForm = $("#lagForm");
	var $lag = $lagForm.find("select").val();
	$("#save_lag").click(function(){
		if($lagForm.find("select").val() != $lag){
			$lagForm.children("form").submit();
		}
		else{
			$(this).next().click();
		}
	});
	
	//安全设置
	//显示安全编辑模块
	var $chooseQuestion = $("#chooseQuestion"),
		$securityForm = $("#securityForm"), 
		currVal = '',
		ansOld ;
	$accountEdit.find("div.editItemSect").click(function(){
		var $this = $(this);
		currVal = $chooseQuestion.val();
		$.ajax({
			type : "post",
			data : {question:currVal},
			url : mk_url('main/setting/isExistsSecurity'),
			dataType : "json",
			success : function(result){
				$this.hide().next().show();
				if(result === true){
					$this.next().find("li.ans").hide().nextAll().show();
					$("#answerInput").removeClass("it");
				}else{
					$this.next().find("li.ans").show().nextAll().hide();
					$("#answerInput").addClass("it");
				}
			}
		});
	});

	//selec值判断
	$chooseQuestion.change(function(){
		var $this = $(this);
		currVal = $chooseQuestion.val();
		$.ajax({
			type : "post",
			data : {question:currVal},
			url : mk_url('main/setting/isExistsSecurity'),
			dataType : "json",
			success : function(result){
				if(result === true){
					$this.parent("li").next().hide().nextAll().show();
					$this.parent("li").next().find("input").removeClass("it");
				}else{
					$this.parent("li").next().show().nextAll().hide();
					$this.parent("li").next().find("input").addClass("it");
				}
			}
		});
	});

	function securityForm(q, a, a_old, a_new) {
		$.djax({
			url: mk_url('main/setting/setSecurity'),
			data: {question: q, answer: a,oldanswer: a_old, newanswer: a_new},
			success: function(data) {
				if(data.state === 1) {
					$.alert('恭喜! 密保问题设置成功!');
					$('#alertWindow').find('span.popBtns').click(function() {
						window.location.reload();
					});
				} else {
					$("#oldAnswerError").text("答案错误").show();
				}
			}
		});
	}

	//判断原始答案正确性
	$("#save_security").click(function(){
		var $this = $(this),
			q = $('#chooseQuestion').val(),
			a = $.trim($("#answerInput").val()),
			a_old = $.trim($("#oldAnswer").val()),
			a_new = $.trim($("#newAnswer").val());
		if($("#answerInput").hasClass("it")){
			if(a === ""){
				$("#answerError").text("答案不能为空").show();
				return false;
			}
			securityForm(q, a, a_old, a_new);
			return false;
		}
		if(a_old === ""){
			$("#oldAnswerError").text("答案不能为空").show();
			return false;
		}
		if(a_new === ""){
			$("#newAnswerError").text("答案不能为空").show();
			return false;
		}
		securityForm(q, a, a_old, a_new);
	});

	//判断新答案合法性


	//通知设置
	//显示设置面板
	var $noticelist = $("#noticelist");
	$noticelist.find("div.allNoticeList").click(function(){
		$noticelist.find("div.allNoticeList").show();
		$noticelist.find("table.noticeItemContent").hide();
		$(this).hide().next().show().find("span.save").addClass("save_init").next().addClass("cancel_init");
		var name = $(this).next().find("span.save").attr('name').split(',');
		$(this).next().find('input:checkbox').attr('checked', false);
		for(var i = 0, l = name.length; i < l; i++) {
			$(this).next().find('input[name='+name[i]+']').attr('checked', true);
		}
	});
	//设置checkbox的value值
	$noticelist.find("input:checkbox").click(function(){
		$(this).parents("table.noticeItemContent").find("span.save").removeClass("save_init").next().removeClass("cancel_init");
		if(this.checked == true){
			this.value = 1;
		}else{
			this.value = 0;
		}
	});
	$noticelist.find('span.save').each(function(){
		var parent = $(this).parents('table.noticeItemContent'),
			c_inputs = parent.find('input:checked'),
			name = '',
			c_l = c_inputs.length;
		for(var i = 0; i < c_l; i++) {
			name += c_inputs[i].name + ',';
		}
		$(this).attr('name', name).click(function() {
			var $this = $(this),
				inputs = parent.find('input:checkbox').not(':checked'),
				data = {},
				checked_inputs = parent.find('input:checked'),
				l = checked_inputs.length;
			if($(this).hasClass('save_init')){
				cancel_save($this);
				return false;
			}
			if($(this).hasClass('operating')){
				return false;
			}
			$(this).addClass('operating');
			
			data.type = parent.prev().attr('name');
			data.data = [];
			inputs.each(function(){
				data.data.push(this.name);
			});
			$.ajax({
				type:'POST',
				url:mk_url('main/notice/noticeeditsetting'),
				data:{data:data},
				dataType:'json',
				success:function(response){
					var response = response.data;
					if(response.state == 1){
						var _name = '';
						for(var j = 0; j < l; j++) {
							_name += checked_inputs[j].name + ',';
						}
						parent.prev().find('span.noticeItemNum span').text(l);
						$this.attr('name', _name).removeClass('operating');
						cancel_save($this);
					}else{
						alert(response.message);
					}
				}
			});
		});
		
	});
	var $noticeMoreList = $("#noticeMoreList")
	var $noticeMoreListLi = $noticeMoreList.find("li");
	var $noticeMore = $("#noticeMore");
	if($noticeMoreListLi.length>4){
		$noticeMoreList.css({"height":5 * 32,"overflow":"hidden"});
		$noticeMore.show().click(function(){
			$noticeMoreList.height($noticeMoreListLi.length * 32);
			$(this).hide();
		});
	}else{
		$noticeMore.hide();
		$noticeMoreList.height($noticeMoreListLi.length * 32);
	}

	function cancel_save(span) {
		span.parents(".hide").hide().prevAll().not(".hide").show();
		span.parents(".hide").find('input').val('').next().hide();
	}

	if($noticelist[0]) {
		$noticelist.find('span.cancel').click(function(){
			var $this = $(this);
			if(!$this.hasClass('save_init')){
				var parent = $(this).parents('table.noticeItemContent'),
					inputs = parent.find('input:checked');
				parent.find('input:checked').attr('checked', false);
				var name = $this.prev().attr('name').split(',');
				for(var i = 0, l = name.length; i < l; i++) {
					parent.find('input[name='+name[i]+']').attr('checked', true);
				}

			}
			cancel_save($this);
		});
		return;
	}

	//取消编辑模块
	$("span.cancel").click(function() {
		var $this = $(this);
		cancel_save($this);
		return false;
	});
	
});
