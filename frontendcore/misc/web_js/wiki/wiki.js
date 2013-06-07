/**
 * Created on  2012-07-09
 * @author: zhaohailong
 * @desc: 端口百科
 */
 	var wiki = {}, editor, oldContent, editorDom, editorBody;
 	wiki.view = function(name,arg){  
 		this.view._class = {
 			init:function(){
 				//设置摘要内容剩余字数
 				$('#descNum').text(300 - $('#description').val().length);

 				//解决IE下设置为readonly的input元素，键入backspace键会触发history.back()问题
				$('input[readonly]').keydown(function(e){
					e.preventDefault();
				})

				//初始化编辑器
				editor = new UE.ui.Editor();
				editor.render('editor');

				//设置编辑器
				setTimeout(function(){
					oldContent = editor.getContent();//存储旧内容

					//获取编辑器DOM
					editorDom = document.getElementById('baidu_editor_0').contentDocument || document.getElementById('baidu_editor_0').contentWindow.document;
					editorBody = editorDom.getElementsByTagName('body')[0];

					//编辑器处理焦点事件
					// var initVal = '<p>'+ UEDITOR_CONFIG.initialContent +'</p>';
					// editorBody.onfocus = function(){
					// 	if(editor.getContent() == initVal){
					// 		editor.setContent('');
					// 	}
					// }
					// editorBody.onblur = function(){
					// 	if(editor.getContent() == ''){
					// 		editor.setContent(initVal);;
					// 	}
					// }
				},300)
 			},
 			//处理目录锚点
 			prossAnchor:function(arg){
				arg[0].click(function(e) {
					var target = wiki.event('getEventTarget',e);
					var dir = $(target).closest(arg[0]);//当前目录
					if(target.tagName == 'H2' || target.tagName == 'H3'){
						var dirItems = dir.find(target.tagName);
						var textItems = (arg[1]) ? arg[1].find(target.tagName) : editorBody.getElementsByTagName(target.tagName);
						var index;//记录当前点击元素的索引
						for(var i = 0, len = dirItems.length; i < len; i++){
							if(dirItems[i] == target){
								index = i;
							}
						}
						var t = textItems[index].offsetTop;//获取元素距离父元素的位置
						if(arg[2] && arg[2] == 'window_scroll'){
							window.scrollTo(0,t);
						}
						if(arg[2] && arg[2] == 'div_scroll'){
							$(arg[3]).scrollTop(t);
						}
					}
				});
 			},
 			//生成目录
 			updateDir:function(arg){
 				var dir = [];//目录列表
				var s = (arg[1]) ? arg[1] : $(editorBody);
				var dcf = document.createDocumentFragment();
					if(s.length > 0){
						dir = s.find('h2,h3');
						//判断是否显示目录
						if(dir.length === 0 && arg[2]){
							arg[2].remove();
							return false;
						}

						for(var i = 0, len = dir.length; i < len; i++){
							var tagName = dir.eq(i)[0].tagName;
							var text = $.trim(dir.eq(i).text());
							if(text){
								var elem = document.createElement(tagName);
								elem.innerHTML = text;
								dcf.appendChild(elem);
							}else{
								continue;
							}
						}
						$(arg[0]).html(dcf);
					}else{
						$(arg[0]).html('');
					}
 			},
 			showMoreDir:function(){
 				var wikiDirWrap = $('#wikiDirWrap');
 				wikiDirWrap.find('.s').click(function(){
 					wikiDirWrap.eq(0).toggleClass('o');
					$(this).toggleClass('x');
 				})
					
 			},
 			showSideDir:function(){
 				var wikiDir = $('.wikiDir');
 				if(wikiDir.length > 0){
	 				var wikiSide = $('.wikiSide');
	 				var wikiDirWrap = $('.wikiDirWrap');
	 				var wikiDirItem = $('.wikiDirItem');
	 				var wikiDirPanel = $('.wikiDirPanel')
	 				var dirTop = wikiDir.offset().top + wikiDir.outerHeight();
	 				setInterval(function(){
			 			window.onscroll = function(){
			 				var winTop = (document.body && document.body.scrollTop) ? document.body.scrollTop : document.documentElement.scrollTop;
			 				if(winTop > dirTop){
			 					wikiSide.fadeIn();
			 				}else{
			 					wikiSide.fadeOut();
			 					wikiDirWrap.fadeOut(function(){
			 						$(this).removeAttr('style').addClass('vb')
			 					});
			 				}
				 		}
			 		},1000);

			 		$('.wikiDirBtn').click(function(){
			 			wikiDirWrap.toggleClass('vb');
			 			$(this).toggleClass('t');
			 		});
			 		$('.wikiPrev').click(function(){
			 			var itemTop = wikiDirItem.position().top + 70;
			 			if(itemTop > 0) return false;
			 			wikiDirItem.animate({top:'+=70'},'slow');
			 		})
			 		$('.wikiNext').click(function(){
			 			var ph = wikiDirPanel.outerHeight();
			 			var itemTop = wikiDirItem.position().top + wikiDirItem.outerHeight() - 70;
			 			if(itemTop < ph) return false;
			 			wikiDirItem.animate({top:'-=70'},'slow');
			 		})

			 		window.onload = window.onresize = function(){
			 			var left = document.documentElement.clientWidth/2 - $('.body').width()/2 + $('.mainArea').width() - parseInt($('.modlueBody').css('padding-left')) - 2;
			 			$('.wikiSide').css('left', left);
			 		}
		 		}
 			},
 			showMoreSense:function(){
 				$('#seemore').click(function(){
 					$('.senseLeft').toggleClass('ha');
 					$(this).find('.text').toggleClass('bp');
 				})
 			},
 			compareVersion:function(arg){
 				arg[0].click(function(){
 					wiki.plug('popUp',[800,'',$('.compareVersionWrap'),'']);
 					$('.popTitle,.popBtnsWrap').remove();
 				})
 			},
 			prossUploadImg:function(arg){
 				var uploadFile = $('#uploadFile');
 				var uploadPath = $('#uploadPath');
 				var uploadImg = $('#uploadImg');

 				//动态生成表单
 				var uForm = document.createElement('form');
				uForm.method = 'post';
				uForm.target = 'uploadFrame';
				uForm.enctype = 'multipart/form-data';
				uForm.encoding = 'multipart/form-data';
				uForm.name = "uploadForm";
				uForm.id = "uploadForm";
				uploadFile.wrap(uForm);

 				//绑定文件上传改变事件
 				uploadFile.change(function(){
 					uploadPath.val(uploadFile.val());
 					$('#uploadForm')[0].action = mk_url('wiki/module/doDescImage');
 					$('#uploadForm')[0].submit();

 					uploadImg.hide();
 					$('#wikiImgBgWrap').addClass('wikiImgBgLoad');
 				});

 				$('#delUploadImgBtn').click(function(){
 					window.onbeforeunload = null;
 					$('#uploadImgUrl').val(null);
 					$('#imgDesc').val(null);
 					uploadPath.val(null);
 					uploadImg.attr('src',uploadImg.attr('data'));
 				});
 			},
 			prossTextValue:function(){
 				$("[defaultVal]").focus(function(){
 					var defaultVal = $(this).attr('defaultVal');
 					if(defaultVal){
 						if($(this).val() == defaultVal) $(this).val('');
 					}
 				}).blur(function(){
 					if($(this).val() == '') $(this).val($(this).attr('defaultVal'));
 				});
 			},
 			tip:function(arg){
 				arg[0].text(arg[1]).show().fadeOut(3000);
 			},
 			closeOtherElem:function(arg){
 				$(document).click(function(e){
 					var target = wiki.event('getEventTarget',e);
 					while(target){
 						if(target.id == arg[0]){
 							return;
 						}
 						target = target.parentNode;
 					}

 					arg[1].eq(0).removeClass('o');
					arg[1].find('.s').removeClass('x');
 				})
 			},
			setImgSize:function(ImgCell, w, h) {
			    var ImgWidth = ImgCell.width;
			    var ImgHeight = ImgCell.height;
			    if (ImgWidth > w) {
			        var newHeight = w * ImgHeight / ImgWidth;
			        if (newHeight <= h) {
			            ImgCell.width = w;
			            ImgCell.height = newHeight
			        } else {
			            ImgCell.height = h;
			            ImgCell.width = h * ImgWidth / ImgHeight
			        }
			    } else {
			        if (ImgHeight > h) {
			            ImgCell.height = h;
			            ImgCell.width = h * ImgWidth / ImgHeight
			        } else {
			            ImgCell.width = ImgWidth;
			            ImgCell.height = ImgHeight
			        }
			    }
			},
			surplus:function(arg){
				var speed = 100;
				arg[0].focus(function(){
					var self = this;
					surplus_timer = setInterval(function(){
						surplus_f(self,arg[1],arg[2]);
					},speed);
				}).blur(function(){
					clearInterval(surplus_timer);
				});

				//计算剩余字数
				function surplus_f(self,num,max){
					var val = $(self).val();
					num.text(max - val.length);
				}
			}
 		};
 		return this.view._class[name](arg);
 	},
 	wiki.event = function(name,arg){
 		this.event._class = {
 			reference:function(arg){
 				var flag = true;
 				var web_id = $('#webId').val();
 				var url = mk_url("wiki/webwiki/wiki_match", {"web_id": web_id});
 				arg[0].live('click',function(){
 					if(flag){
 						flag = false;
 						wiki.model('ajax',[url,{version:$(this).attr('version'),item_id:$(this).attr('item_id')},function(data){
	 						if(data.status == 1){
	 							alert('引用成功');
	 							location.href = data.data.url;
	 							flag = true;
	 						}else{
	 							alert('引用失败');
	 						}
	 					}]);
 					}
 				});
 			},
 			getItems:function(arg){
 				var flag = true;
 				arg[0].click(function(){
 					var web_id = $('#webId').val();
 					var url = mk_url("wiki/webwiki/get_items", {"web_id": web_id});
 					var page = $(this).attr('page');
 					if(flag){
 						flag = false;
 						wiki.model('ajax',[url,{page:page},function(data){
	 						if(data.data.is_end === 0){
	 							arg[2].append(data.data.data);
	 							arg[0].attr('page',++page);
	 							flag = true;
	 						}
	 						if(page * 5 >= data.data.item_num){
	 							arg[1].remove();
	 						}
	 					}]);
 					}
 					
 				})
 			},
 			submitContent:function(arg){
 				arg[0].submit(function(){
 					var item_desc = $('#item_desc');
 					var description = $('#description');
 					var content = $.trim(editor.getContent());
 					var reason = $('#reason');
 					var imgDesc = $('#imgDesc');
 					
 					//表单验证
 					var flag = false, arr = [], str = '',
 					reg = new RegExp("[\r\t\n]","g");
		            var contentText = editor.getContentTxt().replace(reg,"");

 					($.trim(item_desc.val()).length > 0 && item_desc.attr('defaultVal') != item_desc.val()) ? flag = true : arr.push('添加义项');
 					(content.length > 0 && content != UEDITOR_CONFIG.initialContent) ? flag = true : arr.push('正文内容');
 					(UEDITOR_CONFIG.maximumWords - contentText.length >= 0) ? flag = true : arr.push('正确的正文内容');
 					($.trim(description.val()).length > 0 && description.attr('defaultVal') != description.val()) ? flag = true : arr.push('摘要内容');
 					(300 - $.trim(description.val()).length >= 0) ? flag = true : arr.push('正确的摘要内容');
 					if($('input[name="item_id"]').val() != 0){
 						($.trim(reason.val()).length > 0 && reason.attr('defaultVal') != reason.val()) ? flag = true : arr.push('操作原因');
 					}

 					if(flag && arr.length === 0){
 						if($.trim(imgDesc.val()) == imgDesc.attr('defaultVal')){
	 						imgDesc.val('');
	 					}
	 					if(content == UEDITOR_CONFIG.initialContent){
	 						editor.setContent('');
	 					}
 						window.onbeforeunload = null;
 						return true;
 					}else{
 						wiki.view('tip',[$('.errorTip'), '您好，请输入' + arr.toString()]);
 					}
 					return false;
 				});
 			},
 			getEventTarget:function(e){
 				e = e || window.event;
			  	return e.target || e.srcElement;
 			}
 		};
 		return this.event._class[name](arg);
 	},
 	wiki.plug = function(name,arg){
 		this.plug._class = {
 			popUp:function(arg){
 				$(this).popUp({
 					width:arg[0],
                    title:arg[1],
                    content:arg[2],
                    buttons:arg[3],
                    mask:true,
                    maskMode:false,
                    callback:arg[4]
 				});
 			}
 		}
 		return this.plug._class[name](arg);
 	},
 	wiki.model = function(name,arg){
 		this.model._class = {
 			ajax:function(arg){
 				$.ajax({
 					type:'post',
	 				url:arg[0],
	 				data:arg[1],
	 				dataType:'json',
	 				success:arg[2]
 				});
 			},
 			getModelData:function(arg){//获得后台模块数据
 				$.djax({
 					type:'post',
	 				url:'',
	 				dataType:'json',
	 				success:arg
 				});
 			}
 		}
 		return this.model._class[name](arg);
 	}
