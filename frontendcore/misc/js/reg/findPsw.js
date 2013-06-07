/*
 * Created on 2011-10-01.
 * @author: tanglijian
 * @desc: 找回密码
 * @depends: validator.js,init.js
 * Update on 2011-12-06.
 */
 /*
 * @author: chengting
 * Update on 2012-03-22.
 *
 *
 * @author: yewang
 * Update on 2012-04-24.
 *
 *
 *@author: tingting
 *@update on 2012-5-15 
 *
 *
 *@author: tingting
 *@update on 2012-7-15 
 *
 */

var findPwd = {
	init: function() {

		if($('#subnewpswbtn')[0]) {

			this.changePwd();

		} else {
			this.focusBorder();
			this.findPwd.init();

		}
	},
	focusBorder: function() {			//鼠标焦点事件

		var $inputs = $('input.inputTxt');

		$inputs.focus(function() {

			$(this).addClass('addBorder').next().hide();

			if($.trim(this.value) === $(this).attr('rel')){
				$(this).val('');
			}

		}).blur(function() {

			$(this).removeClass('addBorder');

			if($.trim(this.value) === ''){
				$(this).val($(this).attr('rel'));
			}
		});
	},
	findPwd: {
		init: function() {
			var self = this;
				obj = self.parameters,
			self.changeWay();
			obj.duankou.blur(function(){
				$(this).data('fouce',true);
				self.checkDk();
			});
			obj.findpsw.click(function(){
				if( obj.regWay.find('li.selected').index() === 0){
					self.fromMail();
				}else if(obj.regWay.find('li.selected').index() === 1){
					if(!obj.duankou.data('fouce')){
						self.checkDk();
					}
					if(obj.duankou.data('checked')){
						self.fromSecurity();
					}
					obj.duankou.data('fouce',false);
				}
			});		
		},
		parameters: {
			error: $('span.error'),					//错误提示框
			regWay: $('#regWay'),					//找回密码方式标题
			email: $('#email'),						//通过邮箱找回，邮箱或端口号输入框
			duankou: $('#duankou'),					//通过密保找回，端口号输入框
			findpsw: $('#findpsw'),					//提交按钮	
			wayContent: $('#regWayContent')			//找回密码的具体执行

		},
		reInput: function(obj){		//重置input框
			obj.each(function(){
				$(this).val($(this).attr('rel'));
			});
		},
		changeWay: function() {		//切换找回密码方式
			var self = this,
				obj = self.parameters,
				ways = obj.regWay.find('li'),
				wayContent = obj.wayContent.find('li.qList'),
				inputs = $("input.inputTxt");

		    self.reInput(inputs);  //重置input

		    //找回密码切换
		    ways.click(function(){
		    	var index = $(this).index();
		    	$(this).addClass('selected').siblings().removeClass('selected');
		    	wayContent.hide().eq(index).show();
		    	self.reInput(inputs); //重置input
		    	obj.error.hide();
		    	if(index === 1){
		    		obj.duankou.parent().nextAll().hide();
		    	}
		    	obj.findpsw.data('value',null);
		    	obj.findpsw.data('dkcode',null)
		    });
		},
		error: function(dom,text){
			dom.addClass('addBorder').data('checked',null).next().text(text).show();
		},
		fromMail: function() {
			var self = this,
				obj = self.parameters,
				email = obj.email,
				getEmail = email.val(),
				text = '请输入正确的邮箱或端口号地址';
			if(!validator.number.test(getEmail) && !validator.email.test(getEmail)) {
				self.error(email,text);
				return false;
			}
			if(getEmail !== obj.findpsw.data('value')){			//避免重复提交
				$.djax({
					url:mk_url('front/login/doforget_pass_checkemail'),
					type:'post',
					dataType:"json",
					data:{email:getEmail},
					success:function(data){
						obj.findpsw.data('value',getEmail);			
						if(data.status === 1){
							email.next().text('').hide();
							email.data('checked',true);
							window.location.href= mk_url('front/login/successEmail');
						}else{
							self.error(email,data.info);
						}
					}
				});
			}else{
				email.next().show();
			}
			return false;
		},
		checkDk: function() {
			var self = this,
				obj = self.parameters,
				duankou = obj.duankou,				//端口号输入框
				questions = obj.wayContent.find('li.confirQuestion span.questionC'),			//问题列表
				duankouVal = $.trim(duankou.val()),	//端口号值
				answers = obj.wayContent.find('li.confirQuestion input'),
				text = '请输入正确的端口号';
			if(duankouVal === "" || duankouVal === duankou.attr("rel") || !validator.number.test(duankouVal)){
				self.error(duankou,text);
				return false;
			}
			if(duankouVal !== obj.findpsw.data('dkcode')){
				$.djax({
					url:mk_url('front/login/checkSecurity'),
					data: {dkcode:duankouVal},
					success: function(data){
						obj.error.hide();
						obj.findpsw.data('dkcode',duankouVal);
						if(data.status === 0){
							duankou.data('checked',false);
							duankou.parent().nextAll().hide();
							self.error(duankou,data.info);
							return false;
						}else if(data.status === 1){
							var j = 0;
							for(var i in data.data){
								questions.eq(j).attr('qid',i).text(data.data[i]);
								j++;
							}
							answers.val('');
							duankou.parent().nextAll().show();
							duankou.next().text('').hide();
							duankou.data('checked',true);
							if(!duankou.data('fouce')){
								self.fromSecurity();
							}
						}else{
							self.error(duankou,'服务器响应失败');
						}
					}
				});
			}else if(!duankou.data('checked')){
				self.error(duankou,'您输入的端口号未设置密保问题');
			}
		},
		fromSecurity: function() {
			var self = this,
				postData = {},
				obj = self.parameters,
				questions = obj.wayContent.find('li.confirQuestion span.questionC'),
				answers = obj.wayContent.find('li.confirQuestion input'),
				len = answers.length;

			postData.questions = [];
 			postData.answers = [];
			for(var i=0; i<3; i++){
				var val = $.trim(answers[i].value);
				if(val.length<1){
					answers.eq(i).next().show();
					return false;
				};
				answers.eq(i).next().hide();
				postData.questions.push(questions.eq(i).attr('qid'));
				postData.answers.push(val);
			}
			postData.dkcode = obj.duankou.val();
			if(postData !== obj.findpsw.data('data')){
				$.djax({
					url:mk_url('front/login/doforget_pass_checkquestion'),
					data:postData,
					success:function(data){
						if(data.status === 1){
							obj.findpsw.data('data',postData);
							window.location.href= mk_url('front/login/reset_pass');
						}else{
							$.alert(data.info);
						}
					}
				});
			}
		}
	},
	changePwd: function() {
		var save = $('#subnewpswbtn'),
			_new = $('#password'),
			_confirm = $('#repassword'),
			MD5new = $('#MD5password'),
			MD5confirm = $('#MD5repassword');

			function newVal(val) {
				if(val.length < 1) {
					_new.next().text('请输入密码').show();
					return false;
				} else if(val.length < 6) {
					_new.next().text('密码不得少于6个字符').show();
					return false;
				}
				return true;
			}
			function conVal(val) {
				if(val.length < 1) {
					_confirm.next().text('请输入确认密码').show();
					return false;
				} else if(val.length < 6) {
					_confirm.next().text('确认密码不得少于6个字符').show();
					return false;
				} else if(val !== _new.val()) {
					_confirm.next().text('两次输入的密码不一致').show();
					return false;
				}
				return true;
			}
		var form = $('#findpwd');
		save.click(function() {
			var new_val = _new.val(),
				con_val = _confirm.val();
			_new.next().text('').hide();
			_confirm.next().text('').hide();
			if(!newVal(new_val) || !conVal(con_val))return false;
			//MD5加密
			MD5new.val(MD5(MD5(new_val)));
			MD5confirm.val(MD5(MD5(con_val)));
			form.submit();
		});
	}
	
};

$(function(){
	findPwd.init();	   
});