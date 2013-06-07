/**
 * Created on 2012-02-10
 * @author: chengting
 * @version:  2.0
 * @desc: 系统设置
 **/

 /**
 * update on 2012-06-12
 * @author: chengting
 **/
 var settingUserinfo = {
 	init : function(){
 		var self = this;
 		self.editPanel();			//显示编辑面板
 		self.cancel_save();			//取消编辑
 		self.psw.init();			//修改密码
 		//self.email.init();			//修改邮箱
 		self.security.init();		//密保问题
 	},
 	ele : {
 		errors : $('span.errors'),				//错误提示对象
 		accountEdit : $('#accountEdit')			
 	},
 	errors : function(obj,text){				
 		obj.next().text(text).show();
 	},
 	editPanel : function(){						//显示编辑面板
 		var self = this,
 			editItem = self.ele.accountEdit.find('div.editItem');
 		editItem.click(function(){
 			editItem.show().next().hide();
 			$(this).hide().next().show().find('input').val('');
 			self.ele.errors.hide();
 		});
 	},
 	cancel_save : function(){					//取消编辑
 		var self = this,
 			cancel_s = $('span.cancel'),
 			editContent = self.ele.accountEdit.find('div.editContent'),
 			editItem = self.ele.accountEdit.find('div.editItem');
 		cancel_s.click(function(){
 			var parent = $(this).closest('div.hide,table.hide');
 			editContent.hide();
 			editItem.show();
 			parent.find('input').val('').next().hide();
 		});
 	},
 	psw: {										//修改密码
 		init: function(){
 			var self = this;
 			self.pswEdit();
 		},
 		pswEle: {
 		},
 		pswCheck: function(obj, val, text, cVal){
 			var parent = settingUserinfo;
 			if(val === ''){
 				parent.errors(obj, text+'不能为空');
 				return false;
 			}
 			if(val.length<6){
 				parent.errors(obj, text+'不能小于6位');
 				return false;
 			}
 			if(cVal){
 				if(val !== cVal){
 					parent.errors(obj, text + '与新密码不一致');
 					return false;
 				}
 			}
 			return true;
 		},
 		pswEdit: function(){
 			var parent = settingUserinfo,					//父对象
 				self = this,								//当前对象
	 			psw_save = $('#save_pwd'),					//确认按钮
	 			oldPsw = $('input[name=pwd_old]'),			//旧密码
	 			newPsw = $('input[name=pwd_new]'),			//新密码
	 			confirmPsw = $('input[name=pwd_confirm]'), //确认密码
	 			o,n,c;
	 		psw_save.click(function(){
	 			parent.ele.errors.hide();
	 			o = self.pswCheck(oldPsw, oldPsw.val(), '旧密码');
	 			n = self.pswCheck(newPsw, newPsw.val(), '新密码');
	 			c = self.pswCheck(confirmPsw, confirmPsw.val(), '确认密码', newPsw.val());
	 			if(o && n && c){
	 				$.djax({
						url:mk_url('main/setting/resetPasswd'),
				 		data:{old_pwd:MD5(MD5(oldPsw.val())), new_pwd:MD5(MD5(newPsw.val()))}, //MD5加密
				 		success:function(data) {
				 			if(data.status === '1') {
				 				$.alert('恭喜! 密码修改成功!');
				 				$('#alertWindow').find('span.popBtns').click(function() {
				 					window.location.reload();
				 				});
							} else {
								alert(data.info);
							}
						}
					});
	 			}
	 		});
 		}
 	},
 	// email: {									//修改邮箱
 	// 	init: function(){
 	// 		this.emailEdit();
 	// 	},
 	// 	emailEle: {
 	// 		emailPanel: $('#emailPanel'),		//邮箱编辑面板
 	// 		emailEdit: $('#emailEdit'),
 	// 		email: /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/
 	// 	},
 	// 	emailEdit: function(){
 	// 		var self = this,
 	// 			parent = settingUserinfo,
 	// 			emailPanel = self.emailEle.emailPanel,
 	// 			waitEmail =	emailPanel.find('div.waitEmail'),		//等待确认邮箱
 	// 			resendEmail = waitEmail.find('#resendEmail'),		//重新发送邮件
 	// 			cancelEmail = waitEmail.find('#cancelEmail'),		//取消该邮箱
 	// 			save_email = emailPanel.find('#save_email'),
 	// 			email = emailPanel.find('input[name="newEmail"]'),
 	// 			psw = emailPanel.find('input[name="emailPsw"]'),
		// 		confirEmail = $('#confirEmail'),
 	// 			emailReg = self.emailEle.email;
 	// 		self.emailEle.emailEdit.click(function(){
 	// 			$.djax({
 	// 				url: mk_url('main/setting/isModover'),
 	// 				success: function(data){
 	// 					if(data.status === '1'){
 	// 						confirEmail.text(data.data.updateemail);
 	// 						waitEmail.show().next().hide();
 	// 						save_email.addClass('cancel_save');
 	// 						resendEmail.text('重发确认邮件').css('color','#3B5998');
 	// 					}
 	// 					if(data.status === '0'){
 	// 						waitEmail.hide().next().show();
 	// 						save_email.removeClass('cancel_save');
 	// 					}
 	// 				}
 	// 			});
 	// 		});
 	// 		//重新发送电子邮件
 	// 		resendEmail.click(function(){
		// 		var _this = $(this);
		// 		$.djax({
		// 			url: mk_url('main/setting/anewEmail'),
		// 			success: function(data){
		// 				if(data.status === '1'){
		// 					_this.text('已经重发确认电子邮件').css('color','#333');
		// 				}
		// 			}
		// 		});
		// 	});
		// 	//取消修改电子邮件
		// 	cancelEmail.click(function(){
		// 		$.djax({
		// 			url: mk_url('main/setting/cancelEmail'),
		// 			success: function(data){
		// 				if(data.status  === '1'){
		// 					waitEmail.hide().nextAll().show()
		// 					save_email.removeClass('cancel_save');
		// 					waitEmail.next().find('input').val('');
		// 				}
		// 				if(data.status === '0'){
		// 					$.alert('取消失败');
		// 				}
		// 			}
		// 		});
		// 	});
		// 	//修改电子邮件
		// 	save_email.click(function(){
		// 		var _this = $(this),
		// 			emailVal = $.trim(email.val()),
		// 			pswVal = psw.val(),
		// 			p;

		// 		parent.ele.errors.text('').hide();
		// 		p = parent.psw.pswCheck(psw, pswVal, '密码');
		// 		if(emailVal.length<1){
		// 			email.next().text('新邮箱不能为空').show();
		// 			return false;
		// 		}
		// 		if(emailReg.test(emailVal) && !$(this).hasClass('cancel_save')){
		// 			if(p){
		// 				$.djax({
		// 					url: mk_url('main/setting/modEmail'),
		// 					data: {email:emailVal,psd:MD5(MD5(psw.val()))},
		// 					success: function(data){
		// 						if(data.status === '3'){
		// 							email.next().text('邮箱已存在，请重设').show();
		// 						}
		// 						if(data.status === '2'){
		// 							psw.next().text('密码错误').show();
		// 						}
		// 						if(data.status === '1'){
		// 							confirEmail.text(emailVal);
		// 							waitEmail.show().next().hide();
		// 							_this.addClass('cancel_save');
		// 							resendEmail.text('重发确认邮件').css('color','#3B5998');
		// 						}
		// 						if(data.status === '0'){
		// 							alert('请求失败');
		// 						}
		// 					}
		// 				});
		// 			}
		// 		}else{
		// 			email.next().text('新邮箱格式不正确').show();
		// 		}
		// 	});
 	// 	}
 	// },
 	security : {
 		init: function(){
 			this.securityEdit();
 		},
 		securityEle: {
 			sectItem: $('#sectItem'),
 			securityForm: $('#securityForm')
 		},
 		securityEdit: function(){
 			var self = this,
 				parent =settingUserinfo,
 				securityForm = self.securityEle.securityForm,
 				securityEditor = securityForm.find('div.settingEditPanel'),

 				confirAnswer = $('#confirAnswer'),								//确认密保问题
 				confirQuestionLis = securityForm.find('li.confirQuestion'),
 				confirQuestions = confirQuestionLis.find('span.questionC'),
 				confirAnswers  = confirQuestionLis.find('input'),

 				setSecurity = $('#setSecurity'),									//设置密保问题
 				questionLis = securityForm.find('li.question'),
 				questions = questionLis.find('select'),
 				answers = questionLis.find('input'),

 				options = questions.eq(0).find('option'),
 				optionsLen = options.length;

 			//密保去除重复问题处理
 			questions.each(function(){
 				var _this = $(this),
	 				len = questions.length;

 				_this.change(function(){
 					var val = [];

	 				for(var i=0; i<len; i++){
	 					if(questions[i].value !== -1){
	 						val.push(questions[i].value);
	 					}
	 				}

	 				for(var i=0; i<len; i++){
	 					var vali = questions[i].value,
	 						select = questions.eq(i),
	 						thisOption = select.find('option'),
	 						thisOptionLen = thisOption.length;

 						for(var k = 0; k<thisOptionLen; k++){
	 						if(thisOption[k].value != vali && thisOption[k].value!= -1){
	 							thisOption.eq(k).remove();
	 						}
	 					}

 						for(var k = 1; k<optionsLen; k++){
 							if(options[k].value == vali){continue;}
 							options.eq(k).clone(true).appendTo(select);
 						}

	 					for(var j=0, vLen = val.length; j<vLen; j++){
	 						if(vali !== val[j]){
	 							select.find('option[value="' + val[j] + '"]').remove();
	 						}
	 					}
	 				}
	 			});
 			});
			
 			//显示编辑面板
 			self.securityEle.sectItem.click(function(){
 				$.djax({
 					url: mk_url('main/setting/getSecurity'),
 					success: function(data){
 						if(data.status === '1'){
 							securityEditor.eq(0).show().next().hide();
 							for(var i=0, len=confirQuestions.length; i<len; i++){
 								confirQuestions.eq(i).text(data.data[i].text).attr('qid',data.data[i].qid);
 							}
 						}
 						if(data.status === '0'){
 							securityEditor.eq(1).show().prev().hide();
 						}
 					}
 				});
 			});
 			//确认密保问题
 			confirAnswer.click(function(){
 				var _this = $(this),
 					data = {};
 					data.questions = [];
 					data.answers = [];

 				for(var i=0; i<3; i++){
 					var val = $.trim(confirAnswers[i].value);
 					if(val.length<1){
 						confirAnswers.eq(i).next().show();
 						return false;
 					}
 					confirAnswers.eq(i).next().hide();
 					data.questions.push(confirQuestions.eq(i).attr('qid'));
 					data.answers.push(val);
 				}
 				$.djax({
 					url: mk_url('main/setting/replySecurity'),
 					data: data,
 					success: function(data){
 						if(data.status === '1'){
 							_this.closest('div.settingEditPanel').hide().next().show();
 						}
 						if(data.status === '0'){
 							$.alert('您的密保问题与答案不一致，请重新填写！');
 						}
 					}
 				});
 			});
 			//设置密保问题
 			setSecurity.click(function(){
 				var data = {};
 				data.questions = [];
 				data.answers = [];

 				for(var i=0, len = questions.length; i<len; i++){
 					var aVal = $.trim(answers[i].value),
 						qVal = questions[i].value;
 					if(qVal<1){
 						questions.eq(i).next().show();
 						return false;
 					}
 					questions.eq(i).next().hide();

 					if(aVal.length<2){
 						answers.eq(i).next().show();
 						return false;
 					}
 					answers.eq(i).next().hide();
 					
 					data.questions.push(qVal);
 					data.answers.push(aVal);
 				}
 				for(var i= 0, len = data.answers.length; i<len; i++){
 					for(var j=i+1, jlen = data.answers.length; j<jlen; j++){
 						if(data.answers[i] == data.answers[j]){
 							$.alert('三个问题的答案不能一样，请您重新设置！');
 							return false;
 						}
 					}
 				}
 				$.djax({
 					url: mk_url('main/setting/modSecurity'),
 					data: data,
 					success: function(data){
 						if(data.status=== '1'){
 							securityForm.hide().prev().show();
 						}
 						if(data.status === '0'){
 							$.alert('密保问题设置失败，请稍后重设');
 						}
 					}
 				});
 			});
 		}
 	}
 };

$(function(){
	settingUserinfo.init();
});