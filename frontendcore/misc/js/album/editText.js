/**
 * @author:    lishijun(928990115@qq.com)
 * @created:   2012/4/11
 * @version:   v1.0
 * @desc:      相册模块通用JS·编辑文本插件
 * @css:       album.css/picView.css
 * @params:    options = {
 *                txt : '',    	//为空时默认文本
 *				  djaxUrl : '', //djax指向后端url
 *                objId : '',   //对象id
 *				  maxNum : 140  //可以输入最多字数
 *             }
 *
 * @html: 
 *		      <div class="pic_name">
 *		        <p class="clearfix"><em></em><span>默认文本</span></p>
 *		      </div>
 * 使用:     
 *			  $('.pic_name').editText({
 *				  txt:'',
 *				  djaxUrl:'',
 *				  objId:'',
 *				  maxNum:''
 *			  });
 *	
 **/

(function($){
	function CLASS_EDITTEXT(options){
		this.opts = options;

		this.init();
	}

	CLASS_EDITTEXT.prototype = {
		init : function(){
			this.event('pClick');
		},
		view : function(method,arg){
			var self = this;
			var _class = {
				setValue : function(arg){
	 				if(arg[1] !== arg[2]){
						self.model('djax',[self.opts.djaxUrl,{id:self.opts.objId,info:arg[1]},
							function(m){
								if(m.status == 1){
									if(m.data === ''){
										m.data = self.opts.txt;
									}

									arg[0].parent().text(m.data).parent().prepend('<em></em>');

									if(self.opts.obj.find('span').is('.picNameText')){
										self.opts.obj.find('.picNameText').width(280);
									}

									if(self.opts.obj.find('span').is('.picDescText')){
										self.opts.obj.find('.picDescText').width(424);
									}
								}
								else{
									$.alert(m.info,'提示');
								}
							},
							function(XMLHttpRequest,textStatus,errorThrown){
								$.alert('网络连接失败，请检查您的网络连接。','提示');
							}
						]);
					}
					else{
						if(arg[2] === ''){
							arg[2] = self.opts.txt;
						}

						arg[0].parent().text(arg[2]).parent().prepend('<em></em>');

						if(self.opts.obj.find('span').is('.picNameText')){
							self.opts.obj.find('.picNameText').width(280);
						}

						if(self.opts.obj.find('span').is('.picDescText')){
							self.opts.obj.find('.picDescText').width(424);
						}
					}
	 			}
			};
			return _class[method](arg);
		},
		event : function(method,arg){
			var self = this;
			var _class = {
	 			pClick : function(){
	 				self.opts.obj.delegate('p','click',function(){
						var replace_text = $(this).find('span').text();
						
						if(replace_text === self.opts.txt){
							replace_text = '';
						}

						var replace_html = '<textarea maxlength="' + self.opts.maxNum + '">'+replace_text+
						'</textarea><div class="optBtn clearfix"><span class="btnGray"><a href="javascript:;">取消</a></span><span class="btnBlue"><a href="javascript:;">确定</a></span></div>';

						if($(this).children('span').is('.picNameText')){
							$(this).children('.picNameText').width(302);
						}

						if($(this).children('span').is('.picDescText')){
							$(this).children('.picDescText').width(440);
						}

						$(this).find('span').html(replace_html).find('textarea').focus();
						$(this).find('em').remove();

						self.opts.obj.find('p').delegate('textarea,.optBtn','click',function(e){
							e.stopPropagation();

						}).undelegate('.btnBlue','click').delegate('.btnBlue','click',function(){
							var obj = $(this).parent().prev('textarea');
							var newText = $.trim(obj.val());

							if(newText.length > self.opts.maxNum){
								$.alert('输入内容最多允许'+self.opts.maxNum+'个字');
								return false;
							}

							self.view('setValue',[obj,newText,replace_text]);

						}).undelegate('.btnGray','click').delegate('.btnGray','click',function(){
							var obj = $(this).parent().prev('textarea');

							if(replace_text === ''){
								replace_text = self.opts.txt;
							}

							obj.parent().text(replace_text).parent().prepend('<em></em>');

							if(self.opts.obj.find('span').is('.picNameText')){
								self.opts.obj.find('.picNameText').width(280);
							}

							if(self.opts.obj.find('span').is('.picDescText')){
								self.opts.obj.find('.picDescText').width(424);
							}

						});
					});
	 			}
			};
			return _class[method](arg);
		},
		model : function(method,arg){
			var self = this;
			var _class = {
				djax : function(arg){
	 				$.djax({
						type:'post',
						url:arg[0],
						data:arg[1],
						dataType:'json',
						success:arg[2],
						error:arg[3]
					});
	 			}
			};
			return _class[method](arg);
		}
	}

	$.fn.editText = function(options){
		var opts = $.extend({},$.fn.defaults,options);
		return this.each(function(i){
			opts.obj = $(this);
			new CLASS_EDITTEXT(opts);
		});
	}

	$.fn.defaults = {
		txt : '',
		djaxUrl : '',
		objId : '',
		maxNum : 140
	}
})(jQuery)