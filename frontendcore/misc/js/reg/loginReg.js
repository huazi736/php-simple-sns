/*
 * Created on 2011-12-26
 * @author: Yewang
 * @desc: 登录注册首页功能
 * @depends: validator.reg.js
 */
 
var loginReg = {
	init: function() {
		this.login();
		if($('#now_area')[0])this.reg.init();
	},
	//登录 function
	login: function() {
		var username = $('#username');
		//用户名input默认文本切换
		function switchTxt(input, txt) {
			input.focus(function() {
				if($.trim(this.value) === txt) {
					this.value = '';
					$(this).removeClass('defaultColor');
				}
			}).blur(function() {
				if($.trim(this.value) === '') {
					this.value = txt;
					$(this).addClass('defaultColor');
				}
			});
		}

		var relogin = $('#reLogin'),
			login = $('#login');
		if(relogin[0]) {
			username = $('#reloginUsername');
			switchTxt(username, username.attr('default'));

			var errorLi = $('li.error'),
				_pwd = $('#reloginPwd'),
				_MD5pwd = $('#MD5reloginPwd');

			$('input.inputTxt').focus(function() {
				errorLi.text('');
				$(this).next().text('');
			}).blur(function() {
				var $this = $(this);
				if(this.id === 'reloginUsername') {
					dk_user($this);
				} else {
					dk_pwd($this);
				}
			});

			$('#reLogin').click(function() {
				errorLi.text('');
				dk_user(username);
				dk_pwd(_pwd);
				if(!dk_user(username) || !dk_pwd(_pwd)) return false;
				var pVal = _pwd[0].value;
				_MD5pwd.val(MD5(MD5(pVal)));
			});


			function dk_user($input) {
				var val = $.trim($input.val());
				if(val === '' || val === '电子邮件/端口号') {
					$input.next().text('请输入邮箱地址或端口号');
					return false;
				} else if(val.length < 6){
					$input.next().text('该邮箱地址或端口号不存在，请重新输入');
					return false;
				}
				return true;
			}

			function dk_pwd($input) {
				var val = $input.val();
				if(val.length < 1) {
					$input.next().text('请输入密码');
					return false;
				}
				if(val.length < 6) {
					$input.next().text('密码错误，请重新输入');
					return false;
				}
				return true;
			}
		} else {
			switchTxt(username, username.attr('default'));
			login.click(function(){
				if($.trim(username.val()) === username.attr('default')){
					username.val('');
				}
				var pwd = $('#pwd'),
					MD5pwd = $('#MD5pwd'),
					pVal = pwd[0].value;
				if(pVal.length > 5) {
					MD5pwd.val(MD5(MD5(pVal)));
				}
			});
		}
		username.focus();
	},
	//注册
	reg: {
		//初始化
		init: function() {
			var _this = this,
				myArea_home = new initAreaComponent('now_area','1-now_nation,1-now_province,1-now_city,1-now_town','');
			
			//生成地区
			myArea_home.initalize();

			//inputs聚焦判断
			this.focusBorder();

			//点击提交验证
			$('#submitform').click(function() {
				_this.validate();
			});
		},
		//缓存对象
		cache: {
			inputs: $('input.inputTxt'),
			invatationCode: $('#invatationCode'),
			password: $('#password'),
			sex: $('#sex'),
			clause: $('#comfirmClause'),
			validate: {
				email : /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/,
				number : /^\d+$/,
				name : /^[a-zA-Z\u4e00-\u9fa5]+$/
			}
		},
		focusBorder: function() {
			var self = this,
				inputs = this.cache.inputs,
				validator = this.cache.validate;
			inputs.focus(function() {
				$(this).addClass('addBorder').next().addClass('hide').next().removeClass('hide').next().addClass('hide');
			});

			for(var i = 0, l = inputs.length; i < l; i++) {
				$(inputs[i]).blur(function() {
					var input = $(this),
						val = input.val(),
						typeName = input.attr('typeName');
					self.valiFun.inputsAction(val, input, typeName);
				});
				
			}
			self.cache.sex.change(function() {
				if($(this).val() !== '-1') {
					$(this).removeClass('addBorder').next().addClass('hide').next().removeClass('hide');
				} else {
					$(this).addClass('addBorder').next().removeClass('hide').next().addClass('hide');
				}
			});
		},
		error: function(dom, text) {
			try{
				if(text){
					dom.addClass('addBorder').data('checked', null).next().text(text)
					.removeClass('hide').next().addClass('hide').next().addClass('hide');
				}
			}catch(e){
				alert(e);
			}
		},
		valiFun: {
			//验证通过
			allRight: function(_input) {
				_input.removeClass('addBorder').next().addClass('hide').next().addClass('hide').next().removeClass('hide');
			},
			//邀请码验证
			code: function(val, input) {
				var self = loginReg.reg,
					validator = self.cache.validate,
					cache = input.data('cache'),
					errorTxt = input.data('error'),
					_this = this;
				if(cache !== val) {
					input.data('cache', val);
					if(val.length < 6 || !validator.number.test(val)) {
						errorTxt = '6~10位数字邀请码';
						self.error(input, errorTxt);
						input.data('error', errorTxt);
						return '请先填写邀请码';
					}
					$.ajax({
						type: 'POST',
						url: mk_url('front/register/check_dkcode'),
						dataType: 'JSON',
						data: {invatationCode: val},
						success: function(data) {
							if(data.status !== 1) {
								self.error(input, data.info);
								input.data('error', data.info);
							} else {
								_this.allRight(input);
								input.data('checked', true);

								var name = $('#name'),
									nameTxt = $.trim(name.val());
								name.data('cache', null);
								name.data('checked', false);

								if(nameTxt !== '') {
									_this.name(nameTxt, name);
								}
								
							}
						}
					});
				} else if(input.data('checked') === true) {
					_this.allRight(input);
				} else {
					self.error(input, errorTxt);
				}
			},
			//姓名验证
			name: function(val, input, first) {
				var self = loginReg.reg,
					validator = self.cache.validate,
					cache = input.data('cache'),
					errorTxt = input.data('error'),
					_this = this;
				if(first)cache = null;
				if(cache !== val) {
					input.data('cache', val);
					if(val.length < 2 || !validator.name.test(val)) {
						errorTxt = '2~10位，仅限中、英文';
						self.error(input, errorTxt);
						input.data('error', errorTxt);
						return;
					}
					if(self.cache.invatationCode.data('checked') === true) {
						$.ajax({
							type: 'POST',
							url: mk_url('front/register/check_name'),
							dataType: 'JSON',
							data: {invatationCode: $.trim(self.cache.invatationCode.val()), name:val},
							success: function(data) {
								if(data.status !== 1) {
									self.error(input, data.info);
									input.data('error', data.info);
								} else {
									_this.allRight(input);
									input.data('checked', true);
								}
							}
						});
					} else {
						input.data('cache', null);
						_this.allRight(input);
					}
					
				} else if(input.data('checked') === true) {
					_this.allRight(input);
				} else {
					self.error(input, errorTxt);
				}
			},
			//电子邮箱验证
			mail: function(val, input) {
				var self = loginReg.reg,
					validator = self.cache.validate,
					cache = input.data('cache'),
					errorTxt = input.data('error'),
					_this = this;
				/*add by lss*/	
				var order = _this.code(self.cache.invatationCode.val(),self.cache.invatationCode);//先进行邀请码验证
				if(typeof(order)=='string'){
					self.error(input, order);
					input.data('error', order);
					return;
				}
				/*end add*/ 	
				if(cache !== val) {
					input.data('cache', val);
					if(val.length < 6 ||!validator.email.test(val)) {
						errorTxt = '邮箱格式不正确'
						self.error(input, errorTxt);
						input.data('error', errorTxt);
						return;
					}
					$.ajax({
						type: 'POST',
						url: mk_url('front/register/check_email'),
						dataType: 'JSON',
						data: {email: val,invatationCode:$.trim($('#invatationCode').val())},
						success: function(data) {
							if(data.status !== 1) {
								self.error(input, data.info);
								input.data('error', data.info);
							} else {
								_this.allRight(input);
								input.data('checked', true);
							}
						}
					});
				} else if(input.data('checked') === true) {
					_this.allRight(input);
				} else {
					self.error(input, errorTxt);
				}
			},
			//验证密码
			pwd: function(val, input, pwd) {
				var self = loginReg.reg,
					_this = this,
					lessTxt = '密码不得少于6个字符',
					different = '两次输入的密码不一致';
				if(val.length < 1) {
					self.error(input, '请输入' + input.attr('typename'));
					return;
				}
				if(val.length < 6) {
					self.error(input, lessTxt);
					return;
				}
				if(pwd) {
					if(val !== pwd) {
						self.error(input, different);
						return;
					}
				} else {
					var repwd = $('#repassword'),
						reVal = repwd.val();
					_this.allRight(input);
					input.data('checked', true);
					if(reVal !== '' && val !== repwd.val()) {
						var text = different;
						if(reVal.length < 6) text = lessTxt;
						self.error(repwd, text);
						return;
					}
				}
				_this.allRight(input);
				input.data('checked', true);
			},
			inputsAction: function(val, input, typeName) {
				var self = this;
				if(typeName === '新设密码') {
					
					self.pwd(val, input);
				} else if(typeName === '确认密码') {
					self.pwd(val, input, loginReg.reg.cache.password.val());
				} else {
					val = $.trim(val);
					if(val !== '') {
						switch(typeName) {
							case '邀请码':
								self.code(val, input);
								break;
							case '姓名':
								self.name(val, input);
								break;
							case '电子邮箱':
								self.mail(val, input);
								break;
						}

					} else {
						loginReg.reg.error(input, '请输入' + typeName);
					}
				}
				
			}
		},
		//点击提交验证function
		validate: function() {
		
			var obj = this.cache,
				self = this,
				inputsL = obj.inputs.length;
			
			//判断输入框为空
			for(var i = 0; i < inputsL; i++) {
				var input = $(obj.inputs[i]),
					val = input.val(),
					typeName = input.attr('typeName');
				self.valiFun.inputsAction(val, input, typeName);
				
			}
			
			//选择性别
			if(obj.sex[0].value < 0) {
				var sex = $(obj.sex[0]);
				self.error(sex, '请选择性别');
				return;
			}
			for(var i = 0; i < inputsL; i++) {
				var input = $(obj.inputs[i]);
				if(input.data('checked') !== true) {
					return false;
				}
			}
			var areaData = '';
			$('#now_area').find('select').each(function() {
				if($(this).val() !== '-1' && $(this).val() !== '0') {
					var tmp = $(this).find('option:selected').text();
					areaData += tmp + ',';
				}
				
			});
			var pwd = $('#password'),
				repwd = $('#repassword'),
				MD5pwd = $('#MD5password'),
				MD5repwd = $('#MD5repassword');
			MD5pwd.val(MD5(MD5(pwd[0].value)));
			MD5repwd.val(MD5(MD5(repwd[0].value)));
			$('#live_area').val(areaData);
			$('#regForm').submit();
			//同意服务条款
			// if(obj.clause[0].checked === false) {
			// 	errorAlert('请先阅读端口网的服务条款');
			// 	return;
			// }
			
		}
	}
};
 
$(function() {
	loginReg.init();
});