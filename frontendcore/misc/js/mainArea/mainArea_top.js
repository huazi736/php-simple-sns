/*@author:    梁珊珊
 *@created:   2012/2/22
 *@version:   v1.0
 *@desc:      时间线头部(封面，头像，导航，资料)功能
*/
function CLASS_LAYOUT(){
		this.init();
	}
	
	CLASS_LAYOUT.prototype={
		init:function(){
			var self = this;
			this.dkTimelineSection = $('.dkTimelineSection');
			this.$seemore = $('#seemore');
			this.$navList = $('#menuLeftUl');
			this.$attention = $('#attention');
			this.$personalImg = $('#editPersonImg');
			this.$addCover = $('#addCoverbnt');
			this.$editCover = $('#editCover');
			this.$uiBntConfirm = $('.uiBntConfirm a');
			this.$cancelBnt = $('.cancelBnt');
			this.$dkCoverImageContainer =$('#dkCoverImageContainer');
			this.$noImg = $('#noImg');
			this.$containImg = $('#containImg');
			this.web_id = parseInt(window.location.href.split('web_id=')[1]);
			this.uploadpath = isNaN(this.web_id) ? "main" : "webmain";
			this.photopath = isNaN(this.web_id) ? "album" : "walbum";
			this.IE6 = ($.browser.msie)&&($.browser.version==6.0);
			self.view('nav_bnt',self.$navList);			
			self.event('edit_head',self.$personalImg);
			self.event('add_cover',self.$addCover);
			self.event('edit_cover',self.$editCover);
			self.event('show_edit_conver_bnt',self.$containImg);
			// start: 显示权限设置按钮 by卜海亮 2012-07-12
			this.$moveRange = $("ul.moveRange");
			self.event('show_edit_permission', this.$moveRange);
			// end: 显示权限设置按钮
			
			// start: 显示积分等级 by罗豪鑫 2012-07-17
			this.$inlet = $("#inlet");
			self.event('inlet', this.$inlet);
			// end: 显示积分等级
		},
		view:function(method,arg){
			var self = this;
			var _class ={
				nav_bnt:function(arg){
					var appL = arg.find('.fr').find('li').length;
					if (appL<=6){
						self.$seemore.closest('.menuRight').hide();
					}else{
						self.event('open_nav',self.$seemore);
					}; 
				},
				edit_head:function(arg){//编辑头像
                    var uploadPicurl = mk_url(self.uploadpath + "/avatar/set_avatar", {web_id: self.web_id});
                    var selectBrowsePhotourl = mk_url(self.uploadpath + "/avatar/set_avatar", {camera: 1, web_id: self.web_id});
					var str = '<ul class="uiMenu"><li class="selectBrowsePhoto"><a href="#"><s class="icon_1"></s><font>从已传的照片中挑选···</font></a></li><li><a href="' + selectBrowsePhotourl + '"><s class="icon_2"></s><font>拍照···</font></a></li><li class="uploadPic"><a href="' + uploadPicurl + '"><s class="icon_3"></s><font>上传照片···</font></a></li><li class="delete_head"><a href="#"><s class="icon_5"></s><font>删除头像</font></a></li></ul>'
					arg[0].after(str);
					var _parent = arg[0].parent();
					var nohead = arg[0].attr('nohead');
					if(nohead==1){
						arg[0].next().find('.delete_head').hide();
					}else{
						self.event('delete_head',_parent.find('li.delete_head'));
					}
					self.event('selectBrowsePhoto',[_parent.find('li.selectBrowsePhoto'),'head']);	
				},
				edit_cover:function(arg){//编辑封面
					var $str =$('<ul class="uiMenu"><li class="selectBrowsePhoto"><a href="#"><s class="icon_1"></s><font>从已上传的照片中挑选···</font></a></li><li class="uploadPic"><a href="#"><s class="icon_3"></s><font>上传照片···</font></a></li><li class="delete_head"><a href="#"><s class="icon_5"></s><font>删除封面</font></a></li></ul>');
					arg.after($str);
					
					var _parent = arg.parent();
					self.event('selectBrowsePhoto',[_parent.find('li.selectBrowsePhoto'),'cover','edit']);
					self.event('delete_cover',_parent.find('li.delete_head'));
					self.plug('upload_cover',[_parent.find('li.uploadPic'),'updataCover','uploadPicInput1','uploadPhotoForm1']);
					window.updataCover=function(json){
						if(json.status==1){
							var imageUrl=json.data[0];
							var $coverImg = self.$dkCoverImageContainer.find('img');
							var area =self.$dkCoverImageContainer.closest('.coverImage');
                            var counter = 0;
							$coverImg.attr('src',imageUrl);
							self.plug('easydrag',[area,$coverImg]);
							self.dkTimelineSection.addClass('dkEditCover');
							self.$editCover.hide();//隐藏编辑按钮
							self.$containImg.unbind('mouseover').unbind('mouseout');
                            self.$uiBntConfirm.click(function(){
                                var top = $coverImg.css('top');
                                if(counter++ > 0) return;
                                self.model('confirmCover',[{'top':top,'web_id':self.web_id},function(){
                                    counter = 0;
                                    self.dkTimelineSection.removeClass('dkEditCover');
                                    self.plug('easydrag',[area,$coverImg,'unbind']);//取消拖拽事件
                                    self.event('show_edit_conver_bnt',self.$containImg);//重新绑定hover,显示编辑
                                }]);
                            });
                            self.$cancelBnt.click(function(){
                                self.$noImg.addClass('hide');
								self.$containImg.removeClass('hide');
								$coverImg.attr('src',json.data[1]).attr('style','top:0px');
								self.dkTimelineSection.removeClass('dkEditCover');
								self.event('show_edit_conver_bnt',self.$containImg);//重新绑定hover,显示编辑
							});	
							//头像定位触发IE6（兼容IE6）
							if(self.IE6){
								self.$personalImg.css('zoom','normal');
							}
						}else if(json.status==0){
							self.plug('errorPop',['',function(){
									$.closePopUp();
								},'<div style="display:block; padding:15px; em-size:14px;">'+json.msg+'</div>']);
						};
					}
				},
				add_cover:function(arg){//添加封面
					var str = '<ul class="uiMenu"><li class="selectBrowsePhoto"><a href="#"><s class="icon_1"></s><font>从已上传的照片中挑选···</font></a></li><li class="uploadPic"><a href="#"><s class="icon_3"></s><font>上传照片···</font></a></li></ul>';
					arg.after(str);
					var _parent = arg.parent();
					self.event('selectBrowsePhoto',[_parent.find('li.selectBrowsePhoto'),'cover','add']);
					self.plug('upload_cover',[_parent.find('li.uploadPic'),'addCover','uploadPicInput0','uploadPhotoForm0']);
                    window.addCover=function(json){
						if(json.status==1){
							var imageUrl=json.data[0];						
							var $coverImg = self.$dkCoverImageContainer.find('img');
							var area =self.$dkCoverImageContainer.closest('.coverImage');
                            var counter = 0;
							$coverImg.attr('src',imageUrl);
							self.plug('easydrag',[area,$coverImg]);
							self.dkTimelineSection.addClass('dkEditCover');
							self.$editCover.hide();//隐藏编辑按钮
							self.$containImg.unbind('mouseover').unbind('mouseout');//取消hover,不显示编辑按钮
							self.$noImg.addClass('hide');
                            self.$containImg.removeClass('hide');
                            self.$uiBntConfirm.click(function(){
                                if(counter++ > 0) return;
                                var top = $coverImg.css('top');
                                self.model('confirmCover',[{'top':top,'src':imageUrl,'web_id':self.web_id},function(){
                                    counter = 0;
                                    self.dkTimelineSection.removeClass('dkEditCover');
                                    self.plug('easydrag',[area,$coverImg,'unbind']);//取消拖拽事件
                                    self.event('show_edit_conver_bnt',self.$containImg);//重新绑定hover,显示编辑按钮
                                }]);
                            });
                            self.$cancelBnt.click(function(){
								self.$noImg.removeClass('hide');
								self.$containImg.addClass('hide');
								self.dkTimelineSection.removeClass('dkEditCover');
								self.event('show_edit_conver_bnt',self.$containImg);//重新绑定hover,显示编辑
							});
							//头像定位触发IE6（兼容IE6）
							if(self.IE6){
								self.$personalImg.css('zoom','normal');
							}
						}else if(json.status==0){
							self.plug('errorPop',['',function(){
								$.closePopUp();
							},'<div style="display:block; padding:15px; em-size:14px;">'+json.msg+'</div>']);
						};
					}
				},
				photo_list:function(data){//相片列表
					if(data.status==1){                                                                 
						var str = '';
						var data = data.data;
						if(data=='no'){
							str = '<div class="clearfix"><div class="clearfix uiBoxGray pam"><div class="fl f14 fb">你的照片</div><div class="fr fb f14 hovercolor seeAlbum">查看相册</div></div><div class="gray fb mal pam f14">There are no photos avaliable.</div></div>';
						}else if(data){
							str = '<div class="clearfix"><div class="clearfix uiBoxGray pam"><div class="fl f14 fb">最近上传</div><div class="fr fb f14 hovercolor seeAlbum">查看相册</div></div><div><ul class="photoList">';
							for(var i=0,len=data.length;i<len;i++ ){
								str +='<li pid="'+data[i].id+'" imgurl="'+data[i].img+'"><a class="uiMediaThumbLarge uiMediaThumb"><i style="background-image:url('+data[i].img_s+');"></i></a></li>';
							}
							str +='</ul></div></div>';
						}
						return $(str);
					}else if(json.status==0){
						self.plug('errorPop',['',function(){
							$.closePopUp();
						},'<div style="display:block; padding:15px; em-size:14px;">'+json.msg+'</div>']);
					};
				},
				album_list:function(data){//相册列表
					if(data.status==1){
						var data = data.data;
						var str = '<ul class="photoList albumList">';
						for(var i=0,len=data.length;i<len;i++ ){
							var aname =(data[i].name.length>12)? data[i].name.substring(0,12)+'……':data[i].name;
							str +='<li aid="'+data[i].id+'"><a class="uiMediaThumbLarge uiMediaThumb"><span class="uiMediaThumbWrap"><i style="background-image:url('+data[i].album_cover+');"></i></span></a><a class="fb black" title="最近上传">'+aname+'</a></li>';
						}
						str +='</ul>';
						return $(str);
					}else if(json.status==0){
						self.plug('errorPop',['',function(){
							$.closePopUp();
						},'<div style="display:block; padding:15px; em-size:14px;">'+json.msg+'</div>']);
					};
				}
			};
			return _class[method](arg);
			
		},
		event:function(type,dom){
			var self = this;
			switch (type){
				case 'open_nav':	
					dom.click(function(){ //打开应用区||modify by李世君 2012-3-29
						if($(this).hasClass('opennav')){
							var noMoveRangeLiNum = self.$navList.find('ul.noMoveRange').children('li').length;
							var hideIndex;
							if(noMoveRangeLiNum === 0){
								hideIndex = 5;
							}
							else if(noMoveRangeLiNum === 1){
								hideIndex = 4;
							}
							else if(noMoveRangeLiNum === 2){
								hideIndex = 3;
							}

                            self.$seemore.closest('.menuRight').find(".uiArrow").hide();
							$(this).removeClass('opennav').find('.text').attr('style','visibility:visible');
							
							self.$navList.find('ul.moveRange').children('li:gt('+hideIndex+')').each(function () {
							  $(this).addClass('hide');
							});
						}else{
							$(this).addClass('opennav').find('.text').attr('style','visibility:hidden');
                            self.$seemore.closest('.menuRight').find(".uiArrow").show();
                            self.$navList.find('ul.moveRange').children().removeClass('hide');
						}
					});
				break;
				case 'edit_head'://编辑头像
					dom.hover(function(){
						$(this).find('.editHead').removeClass('hide');
					},
					function(){
						$(this).find('.editHead').addClass('hide');
					});
					dom.css('cursor','pointer').find('#editPerson').click(function(){
						var $menu = $(this).parent().find('.uiMenu');
						if($menu.length==0){
							self.view('edit_head',[$(this),'eidt']);
						}else if(!$menu.hasClass('hide')){
							$menu.addClass('hide');
						}else{
							$menu.removeClass('hide');
						}
					});
				break;
				case 'show_edit_conver_bnt':
					dom.mouseover(function(e){
						self.$editCover.show();
					});
					dom.mouseout(function(e){
						self.$editCover.hide();
					});
				break;
				case 'add_cover':
					dom.click(function(){
						var $menu = $(this).parent().find('.uiMenu');
						if($menu.length==0){
							self.view('add_cover',$(this));
						}else if(!$menu.hasClass('hide')){
							$menu.addClass('hide');
						}else{
							$menu.removeClass('hide');
						}
					});
				case 'edit_cover':
					dom.find('#editCoverbnt').click(function(){
						var $menu = $(this).parent().find('.uiMenu');
						if($menu.length==0){
							self.view('edit_cover',$(this));
						}else if(!$menu.hasClass('hide')){
							$menu.addClass('hide');
						}else{
							$menu.removeClass('hide');
						}
					});
					
				break;
				case 'selectBrowsePhoto'://从已传照片中挑选
					window.getAlbum = function(data) {
					if (data.status==1&&(!data.data.length)) {
							var data = self.photopath == "album" ? {} : {web_id: self.web_id};
							self.plug('errorPop',['','','<div style="padding:15px">你还没有相册，请先<a href="'+ mk_url(self.photopath + "/", data) + '">创建相册</a>吧！</div>']);
						return;
					};
					window.getPhotoList = function(data){
						self.plug('photoPop',data);
						var $popUp = $('#popUp');
						$popUp.find('.photoList').children().each(function(){
							if(dom[1]=='cover'){
								self.event('set_cover',[$(this),dom[2]]);
							}else{
								self.event('set_head',$(this));
							}
						});
						function seeAlbum(){
							$popUp.find('.seeAlbum').click(function(){
								$.closePopUp();
								self.model('getAlbum',['', function(data){
									self.plug('getAlbumPop',data);
									var $popUp = $('#popUp');
									$popUp.find('.albumList').children().each(function(){
										$(this).click(function(){
											var aid = $(this).attr('aid');
											$.closePopUp();
											self.model('getPhotoList',[aid,function(data){

												self.plug('photoPop',data);
												var $popUp = $('#popUp');
												$popUp.find('.photoList').children().each(function(){
													if(dom[1]=='cover'){
														self.event('set_cover',[$(this),dom[2]]);
													}else{
														self.event('set_head',$(this));
													}
												});
												seeAlbum();
											}]);
										});
									});
								}]);
							});
						}
						seeAlbum();
					}
					self.plug('getAlbumPop',data);
					var $popUp = $('#popUp');
					$popUp.find('.albumList').children().each(function(){
						var aid = $(this).attr('aid');
						$(this).click(function(){
							$.closePopUp();
							self.model('getPhotoList',[aid]);
						});
					});
				}
					dom[0].click(function(){
						self.model('getAlbum',['', getAlbum]);
					});
				break;
				case 'set_cover':
					dom[0].click(function(){
						var img = $(this).attr('imgurl');//当前选择图片信息
						var pid = $(this).attr('pid');
						self.model('sendPhotoInfo',[{'pic':img,'pid':pid,'from':'album','web_id':self.web_id},function(json){
							if(json.status==1){
								self.dkTimelineSection.addClass('dkEditCover');
								self.$editCover.hide();
								self.$containImg.unbind('mouseover').unbind('mouseout');
								var $coverImg = $('#dkCoverImageContainer').find('img');
								var oldCover = json.data[1];
                                var counter = 0;
								$coverImg.attr('src',json.data[0]);//设置封面
								var area = $('.coverImage');
								self.plug('easydrag',[area,$coverImg]);//绑定拖动事件
								self.$noImg.addClass('hide');
								self.$containImg.removeClass('hide');
                                self.$uiBntConfirm.click(function(event){
                                    event.preventDefault();
                                    if(counter++ > 0) return;
                                        var top = $coverImg.css('top');
                                    self.model('confirmCover',[{'top':top,'web_id':self.web_id},function(){
                                        counter = 0;
                                        self.dkTimelineSection.removeClass('dkEditCover');
                                        self.plug('easydrag',[area,$coverImg,'unbind']);//取消拖拽事件
                                        self.event('show_edit_conver_bnt',self.$containImg);//重现绑定hover,显示编辑
                                    }]);
                                });
                                if (dom[1]=='add') {
									self.$cancelBnt.click(function(){
										self.$noImg.removeClass('hide');
										self.$containImg.addClass('hide');
										self.dkTimelineSection.removeClass('dkEditCover');
										self.event('show_edit_conver_bnt',self.$containImg);
									});
								}else{
									self.$cancelBnt.click(function(){
										$coverImg.attr('src',oldCover).attr('style','top:0px');
										self.dkTimelineSection.removeClass('dkEditCover');
										self.event('show_edit_conver_bnt',self.$containImg);
									});
								};
								//头像定位触发IE6（兼容IE6）
								if(self.IE6){
									self.$personalImg.css('zoom','normal');
								}	
							}else if(json.status==0){
								self.plug('errorPop',['',function(){
									$.closePopUp();
								},'<div style="display:block; padding:15px; em-size:14px;">'+json.msg+'</div>']);
							};
						}]);
						$.closePopUp();
					});
				break;
				case 'set_head':
					dom.click(function(){
						var img = $(this).attr('imgurl');//当前选择图片信息
						var pid = $(this).attr('pid');
						var timeStr = Date.parse(new Date());						
						window.location.href= mk_url(self.uploadpath + "/avatar/set_avatar", { p: img, pid: pid, web_id: self.web_id});
					});
				break;										
				case 'delete_head':
					dom.click(function(){//删除头像
						var _this = $(this);
						self.plug('deletepop',[$(this),function(){
							self.model('delete_head',[{'web_id':self.web_id},function(data){
								if(data.status===1){
									self.$personalImg.find('img').attr('src',data.data);
									_this.hide();

								}else if(json.status==0){
									self.plug('errorPop',['',function(){
										$.closePopUp();
									},'<div style="display:block; padding:15px; em-size:14px;">'+json.msg+'</div>']);
								};
							}]);
							$.closePopUp();
						}]);
					});
				break;
				case 'delete_cover':
					dom.click(function(){//删除封面
						self.plug('deletepop',[$(this),function(){
							self.model('delete_cover',[{'web_id':self.web_id},function(data){
								if(data.status===1){
									self.$noImg.removeClass('hide');
									self.$containImg.addClass('hide');
									//头像定位触发bottom（兼容IE6）
									/*var bottom = self.$personalImg.css('bottom');
									self.$personalImg.css('bottom', bottom);*/
									self.$personalImg.css('zoom','1');
								}else if(json.status==0){
									self.plug('errorPop',['',function(){
										$.closePopUp();
									},'<div style="display:block; padding:15px; em-size:14px;">'+json.msg+'</div>']);
								};
							}]);
							$.closePopUp();
						}]);
					});
				break;				
				case 'show_edit_permission':
					dom.find('li').hover(function(){
						$(this).find('div.dropWrap').show();
					}, function(){
						$(this).find('div.dropWrap').hide();
						if($('.dropDown', $(this)).length > 0) {
							$('.dropDown', $(this)).removeClass('dropDown').css("z-index","0");
						}
					});	
				break;
				//显示积分等级
				case 'inlet':
					dom.mouseover(function(){
						$(this).find('.dropBox').css({
							visibility:'visible'
						});					
					}).mouseleave(function(){
						$(this).find('.dropBox').css({
							visibility:'hidden'
						});
					});
					(function(){
						var inlet = $('#inlet'),
							curExp = inlet.find('div.expBar-cur'),
							totalExp = inlet.find('div.expBar-total'),
							curCredit = Number(inlet.find('span.cur_cd').html()),
							total = Number(inlet.find('span.total_cd').html()),
							tipBox = $('.c-tip-box'),
							name = $('#name'),
							curNumberBox = inlet.find('.cur_cd_b'), curNumber = curNumberBox.find('.cur_cd'), curRefer = curNumberBox.find('.refer_s');
						var curExpWidth = curCredit*(totalExp.width()/total), 
							minWidth = curNumber.innerWidth()/2, maxWidth = curNumber.innerWidth() + curExpWidth;	
							inlet.css({
								left:170+name.width(),
								visibility:'visible'
							});
							curExp.css({
								width:curExpWidth
							});		
							if(curExpWidth < minWidth){
								//定位当前积分数
								curNumberBox.css({
									left:curExpWidth
								});			
								//定位小三角图标
								curRefer.css({
									left:0,
									'background-position':'-77px -97px'
								});						
							}else if( (curExpWidth + minWidth) >= totalExp.width() ){
								curNumberBox.css({
									left:curExpWidth - curNumber.outerWidth(true)
								});	
								curRefer.css({
									right:'-4.5px', //"4.5px"为小三角宽度的一半
									'background-position':'-64px -97px'
								});											
							}else{
								curNumberBox.css({
									left:curExpWidth - curNumber.innerWidth()/2
								});
								curRefer.css({
									left:curNumber.innerWidth()/2 - curRefer.width()/2,
									'background-position':'-54px -97px' 
								});																
							}
					})();
				break;
			}		
		},
		plug:function(method,arg){
			var self = this;
			var _class={
				photoPop:function(arg){
					var content = self.view('photo_list',arg);
					$(this).popUp({
						width:560,
						title:'从照片里选择',
						content:content,
						buttons:'<span class="popBtns closeBtn">取消</span>',
						mask:true,
						maskMode:true,
						callback:function(){
							$.closePopUp();
						}
					});
				},
				deletepop:function(arg){//删除照片弹框
					arg[0].popUp({
						width:580,
						title:'删除照片？',
						content:'<strong style="display:block; padding:15px; em-size:14px;">您确定要删除这张照片吗？</strong>',
						buttons:'<span class="popBtns blueBtn callbackBtn">确定</span><span class="popBtns closeBtn">取消</span>',
						mask:true,
						maskMode:true,
						callback:arg[1]
					});
				},
				errorPop:function(arg){
					$(this).popUp({
						width:580,
						title:'友情提示',
						content:arg[2],
						buttons:'<span class="popBtns blueBtn closeBtn">关闭</span>',
						mask:true,
						maskMode:true,
						callback:arg[1]
						
					});
				},
				upload_cover:function(arg){
                    arg[0].uploader({
						inputFileId:arg[2],
						formId:arg[3],
						inputWidth:'200px',
						img:true,
                        loading: true,
						url:mk_url(self.uploadpath + "/avatar/setProfilePic", {web_id: self.web_id}),
						callback:arg[1]
					});
					if($.browser.mozilla){
						$('#'+arg[2]).attr('style','width: 200px; height: 22px; position: absolute; cursor: pointer; opacity: 0; top: -1000px; left: 0px;');
							arg[0].find('a').click(function(){	
								$('#'+arg[2]).click();
							});
					}else if (($.browser.msie)&&($.browser.version==8.0)) {
						$('#'+arg[2]).attr('style','overflow:visible; zoom:4; position: absolute; cursor: pointer; top:-8px; left:-135px; filter:alpha(opacity=1)');
					}else if(($.browser.msie)&&($.browser.version==7.0||$.browser.version==6.0)){
						$('#'+arg[2]).attr('style','overflow:visible; zoom:4; position: absolute; cursor: pointer; top:-8px; left:-500px; filter:alpha(opacity=1)');
					}
				},
				easydrag:function(arg){//拖拽
					arg[0].easydrag({
						imgArea:arg[0],
						currentElement:arg[1],
						unbind:arg[2]
					});
				},
				getAlbumPop:function(arg){
					var content = self.view('album_list',arg);
					$(this).popUp({
						width:560,
						title:'从照片里选择',
						content:content,
						buttons:'<span class="popBtns closeBtn">取消</span>',
						mask:true,
						maskMode:true,
						callback:function(){
							$.closePopUp();
						}
					});
				}
				
			};
			return _class[method](arg);
		},
		model:function(method,arg){
			var self = this;
			var _class = {
				delete_head:function(arg){
					$.djax({
						url:mk_url(self.uploadpath + "/avatar/delete_avatar", {}),
						type:"POST",
						data:arg[0],
						loading:true,
						relative:true,
						success:arg[1]
					});	
				},
				delete_cover:function(arg){
					$.djax({
						url:mk_url(self.uploadpath + "/avatar/delete_cover", {}),
						type:"POST",
						data:arg[0],
						loading:true,
						relative:true,
						success:arg[1]
					});	
				},
				getPhotoList:function(arg){
					$.getScript(mk_url(self.photopath + '/api/get_photo_list', {aid: arg[0], web_id: self.web_id, callback: "getPhotoList"}));
				},
				sendPhotoInfo:function(){
					$.ajax({
						url: mk_url(self.uploadpath + '/avatar/setProfilePic', {web_id: self.web_id}),
						data: arg[0],
                        dataType: "json",
						type: 'POST',
						success: arg[1]
					});
				},
				getAlbum:function(){
					$.getScript(mk_url(self.photopath + '/api/get_album_list', {order: "date_asc", web_id: self.web_id, callback: "getAlbum"}));
				},
				confirmCover:function(){
					$.ajax({
						url: mk_url(self.uploadpath + "/avatar/set_cover", {web_id: self.web_id}),
						data: arg[0],
						type: 'POST',
						dataType: 'json',
						success: arg[1]
					});
				}
			};
			return _class[method](arg);
		}
	}

$(function(){

	new CLASS_LAYOUT();
});

