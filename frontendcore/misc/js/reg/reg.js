/*
 * Created on 2011-12-26
 * @author: Yewang
 * @desc: 登录注册首页功能
 * @depends: validator.reg.js
 */
 
 /*
 *
 * Update on 2012-8-1
 * @author: Chenging
 * @desc: 改版为原生js
 *
 */

function loginReg(){
	this.init();
}
loginReg.prototype = {
	init: function(){
		var self = this;
		var obj = this.cache,
			cacheData = obj.cacheData,
			myArea_home = new initAreaComponent('now_area','1-now_nation,1-now_province,1-now_city,1-now_town',''),
			regSubmit = document.getElementById('submitform'),
			login = document.getElementById('login'),
			username = document.getElementById('username');		//登录用户名
			

		//初始化现居地
		myArea_home.initalize();

		//事件监听的兼容处理
		function addEventHandler(target,type,func){
			if(target.addEventListener){
				target.addEventListener(type,func,false);
			}else if(target.attachEvent){
				target.attachEvent('on' + type,func);
			}
			else target['on' + type] = func;
		}

		//封装事件函数
		function inputfocus(e){//获取焦点事件
			var e = e || window.event;
			var input = e.target || e.srcElement;
			self.event('inputFocus',[input]);
		}
		function checkinput(e){//失去焦点事件
			var e = e || window.event;
			var input = e.target || e.srcElement;
			self.event('checkInput',[input])
		}
		function clicksubmit(e){//提交事件
			self.event('clickSubmit')
		}
		function sexchang(e){//设置性别事件
			var e = e || window.event;
			var input = e.target || e.srcElement;
			self.event('sexChang',[input]);
		}
		function loginsubmit(e){//登录提交事件
			self.event('loginSubmit');
		}
		function loginfocus(e){//登录获得焦点
			var e = e || window.event;
			var input = e.target || e.srcElement;
			self.event('loginFocus',[input]);
		}
		function loginblur(e){//登录失去焦点
			var e = e || window.event;
			var input = e.target || e.srcElement;
			self.event('loginBlur',[input]);
		}

		//焦点事件
		for(var i in cacheData){
			var input = cacheData[i][1];
			addEventHandler(input,'focus',inputfocus);
			addEventHandler(input,'blur',checkinput);
		}

		//页面刷新时初始化性别
		obj._sex.options[0].selected = true;	

		//设置性别
		addEventHandler(obj._sex,'change',sexchang);

		//提交事件
		addEventHandler(regSubmit,'click',clicksubmit);


		//登录焦点事件
		addEventHandler(username,'focus',loginfocus);
		addEventHandler(username,'blur',loginblur);


		//登录点击事件
		addEventHandler(login, 'click', loginsubmit);

	},
	cache: {
		validate : {
			email : /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/,
			number : /^\d+$/,
			name : /^[a-zA-Z\u4e00-\u9fa5]+$/
		},
		cacheData : {
			invatationCode : [false,document.getElementById('invatationCode')],
			name : [false,document.getElementById('name')],
			email : [false,document.getElementById('email')],
			pwd : [false,document.getElementById('password')],
			repwd : [false,document.getElementById('repassword')]
		},//缓存input的check结果
		_sex : document.getElementById('sex'),
		_MD5password : document.getElementById('MD5password'),
		_MD5repassword : document.getElementById('MD5repassword'),
		_regForm : document.getElementById('regForm')
	},
	view: function(method,arg){
		var self = this;
		var _class = {
			toggelClass : function(arg){		//class 的增加和删除
				var toggel = arg[0],
					dom = arg[1],
					toggelClassName = arg[2].split(' '),
					getClassName = dom.className.split(' '),
					toggelLen = toggelClassName.length,
					getLen = getClassName.length,
					classN = '';

				switch(toggel){
					case 'add' :
						if(getLen>0){
							var i,j;
							for(i = 0; i<toggelLen; i++){
								for(j = 0; j<getLen; j++){
									if(toggelClassName[i] == getClassName[j]){
										break;
									}
								}
								if(j>=getLen){
									classN += ' ' + toggelClassName[i]; 
								}
							}
							dom.className = dom.className + classN;
						}else{
							dom.className = dom.className + ' ' + arg[1];
						}
						break;
					case 'remove' :
						if(toggelLen>0){
							var i,j;
							for(i = 0; i<getLen; i++){
								for(j = 0; j<toggelLen; j++){
									if(getClassName[i] == toggelClassName[j]){
										break;
									}
								}
								if(j>=toggelLen){
									classN += ' ' + getClassName[i];
								}
							}
							dom.className = classN;
						}else{
							dom.className = ' ';
						}
						break;
				}
			},
			error : function(arg){
				var obj = self.cache,
					input = arg[0],
					text = arg[1],
					errors = input.nextSibling,
					promptTxt = errors.nextSibling,
					allRight = promptTxt.nextSibling;

				input.setAttribute('error',text);
				self.view('toggelClass',['add',input,'addBorder']);
				errors.innerHTML = text;
				errors.style.display = 'block';
				promptTxt.style.display = 'none';
				allRight.style.display = 'none';
			},
			right : function(arg){
				var obj = self.cache,
					input = arg[0],
					errors = input.nextSibling,
					promptTxt = errors.nextSibling,
					allRight = promptTxt.nextSibling;

				self.view('toggelClass',['remove',input,'addBorder']);
				errors.style.display = 'none';
				promptTxt.style.display = 'none';
				allRight.style.display = 'block';
			}
		};
		return _class[method](arg);
	},
	event: function(method,arg){
		var self = this;
		var _class = {
			inputFocus: function(arg){
				var obj = self.cache,
					input = arg[0],
					errors = input.nextSibling,
					promptTxt = errors.nextSibling,
					allRight = promptTxt.nextSibling;

				self.view('toggelClass',['add',input,'addBorder']);
				errors.style.display = 'none';
				allRight.style.display = 'none';
				promptTxt.style.display = 'block';
			},
			checkInput: function(arg){
				var obj = self.cache,
					input = arg[0],
					typeName = input.getAttribute('typename');

				if(input.value !== ''){
					switch(typeName){
						case '邀请码':
							self.model('code',[input]);
							break;
						case '姓名':
							self.model('name',[input]);
							break;
						case '电子邮箱':
							self.model('email',[input]);
							break;
						case '新设密码':
							self.model('password',[input]);
							break;
						case '确认密码':
							self.model('password',[input,obj.cacheData.pwd[1].value]);
							break;
					}
				}else{
					self.view('error',[input,'请输入' + typeName]);
				}
			},
			sexChang: function(arg){
				var sex = arg[0]
					errors = sex.nextSibling,
					allRight = errors.nextSibling;

				if(this.value < 0 ){
					errors.style.display = 'block';
					allRight.style.display = 'none';
				}else{
					errors.style.display = 'none';
					allRight.style.display = 'block';
				}
			},
			clickSubmit: function(){
				var obj = self.cache,
					cacheData = obj.cacheData,
					now_area = document.getElementById('now_area'),			//现居地
					live_area = document.getElementById('live_area'),		//现居地隐藏域
					select = now_area.getElementsByTagName('select'),		//
					areaData = '',
					sex = obj._sex,
					checkResult = true;

				//获取现居地
				for(var i = 0, len = select.length; i<len; i++){
					var val = select[i].value;
					if(val > 0){
						areaData += val + ',';
					}else{
						areaData = '';
						break;
					}
				}
				live_area.value = areaData;

				//check input框
				for(var i in cacheData){
					var inputObj = cacheData[i];
					if(!inputObj[0]){
						if(inputObj[1].value === ''){
							self.view('error',[inputObj[1],'请输入' + inputObj[1].getAttribute('typename')]);
						}
						checkResult = false;
					}
				}
				//check性别
				if(sex.value < 0){
					sex.nextSibling.style.display = 'block';
					sex.nextSibling.nextSibling.style.display = 'none';
					checkResult = false;
				}
				if(!checkResult)return;
				obj._regForm.submit();
			},
			loginFocus: function(arg){
				var input = arg[0];
				self.view('toggelClass',['remove',input,'defaultColor']);
				if(input.value === input.getAttribute('default')){
					input.value = '';
				}
			},
			loginBlur: function(arg){
				var input = arg[0];
				if(input.value === ''){
					input.value = input.getAttribute('default');
					self.view('toggelClass',['add',input,'defaultColor']);
				}
			},
			loginSubmit: function(){
				var pwd = document.getElementById('pwd'),
					MD5pwd = document.getElementById('MD5pwd'),
					pVal = pwd.value;
				if(pVal.length > 5) {
					MD5pwd.value = MD5(MD5(pVal));
				}
			}
		};
		var arg = arg || '';
		if(arg.length>0){
			return _class[method](arg);
		}else{
			return _class[method]();
		}
	},
	model: function(method,arg){
		var self = this,
			obj = self.cache,
			input = arg[0],
			cacheData = obj.cacheData;

		var _class = {
			ajax : function(arg){
				var req,
					url = arg[0].url,
					input = arg[1];

				if(window.ActiveXObject){
					req = new ActiveXObject('Microsoft.XMLHTTP');
				}else if(window.XMLHttpRequest){
					req = new XMLHttpRequest();
				}
				
				if(input.getAttribute('data') == arg[0].sendData){		//避免重复提交
					if(cacheData[input.name][0]){
						self.view('right',[input]);
					}else{
						self.view('error',[input,input.getAttribute('error')]);
					}
				}else{
					input.setAttribute('data',arg[0].sendData);
					req.open('post',url,true);
					req.onreadystatechange = function(){
						if(req.readyState == 4){
							var data = eval('(' + req.responseText + ')');
							if(data.status === 1){
								self.view('right',[input]);
					 			cacheData[input.name][0] = true;
					 			if(input.getAttribute('typename') == '邀请码'){
					 				if(obj.cacheData.name[1].value.length>=2){
						 				self.model('name',[obj.cacheData.name[1]])
						 			}
					 			}
					 			return true;
							}else{
								self.view('error',[input,data.info]);
								cacheData[input.name][0] = false;
							}
						}
					}
					req.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
					req.send(arg[0].sendData);
				}
			},
			code : function(arg){
				var val = input.value,
					data = {};
				
				data.url = mk_url('front/register/check_dkcode');
				data.sendData = 'invatationCode=' + val;
				if(val.length<6 || !obj.validate.number.test(val)){
					self.view('error',[input,'6~10位数字邀请码']);
					input.setAttribute('data',data.sendData);
					cacheData.invatationCode[0] = false;
					return false;
				}
				self.model('ajax',[data,input]);
			},
			name : function(arg){
				var val = input.value,
					_code = cacheData.invatationCode[1],
					data = {};

				data.url = mk_url('front/register/check_name');
				data.sendData = 'invatationCode=' + _code.value + '&' + 'name=' + input.value;
				
				if(cacheData.invatationCode[0] && val.length>=2 && obj.validate.name.test(val)){
					self.model('ajax',[data,input]);
				}else{
					if(val.length>=2 && obj.validate.name.test(val)){
						self.view('right',[input]);
						cacheData.name[0] = true;
					}else{
						self.view('error',[input,'2~10位，仅限中、英文']);
						cacheData.name[0] = false;
						return false;
					}
				}
			},
			email : function(arg){
				var val = input.value,
					data = {};

				data.url = mk_url('front/register/check_email'),
				data.sendData = 'email=' + input.value;
				if(obj.validate.email.test(val)){
					self.model('ajax',[data,input]);
				}else{
					self.view('error',[input,'邮箱格式不正确']);
					input.setAttribute('data',data.sendData);
					cacheData.email[0] = false;
				}
			},
			password : function(arg){
				var val = input.value,
					pwdVal = '';

				if(val.length<6){
					self.view('error',[input,'密码不得少于6个字符']);
					if(pwdVal){
						cacheData.repwd[0] = false;
					}else{
						cacheData.pwd[0] = false;
					}
					return false;
				}
				if(arg[1]){
					var pwdVal = arg[1];
					if(val !== pwdVal){
						self.view('error',[input,'两次输入的密码不一致']);
						cacheData.repwd[0] = false;
						return false;
					}
					self.view('right',[input]);
					cacheData.repwd[0] = true;
					obj._MD5repassword.value = MD5(MD5(pwdVal));
					return true;
				}
				self.view('right',[input]);
				cacheData.pwd[0] = true;
				obj._MD5password.value = MD5(MD5(val));
			}
		};
		return _class[method](arg);
	}
}
new loginReg();
