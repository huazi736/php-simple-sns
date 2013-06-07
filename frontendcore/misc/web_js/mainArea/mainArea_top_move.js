/*
 * @author:    李世君
 * @created:   2012/3/24
 * @version:   v1.0
 * @desc:      时间线头部模块排序功能
*/

function CLASS_MAINAREATOPMOVE(){
	this.init();
}

CLASS_MAINAREATOPMOVE.prototype = {
	init : function(){
		this.$linkObj = $('div.tip'); //点击对象
		this.$dragRange = $('#menuLeftUl').find('ul.moveRange'); //拖动范围
		this.dragBar = 'div.canmove'; //拖动手柄
		this.dragAjaxUrl = mk_url('webmain/menu/resetAppSort'); //排序提交url
		this.$perssionBar = $('span.tab').find('div.listPermission'); //权限开关
		this.perssionType = 'app'; //权限类型值
		this.perssionUrl = mk_url("webmain/menu/changeAppPermissions"); //权限提交url
		this.perssionIsIm = true; //权限值是否即时传递

		this.perssionPos = 'right'; //权限列表位置
		this.$lis = $('ul.fr').find('li');
		this.webId = $('input.web_id').val();
		this.webUid = $('input.web_uid').val();
		this.albumSetNumberAjaxUrl = mk_url('walbum/api/get_album_number', {uid: this.webUid, web_id: this.webId});
		this.eventSetNumCoverAjaxUrl = mk_url('wevent/api/userCover', {web_id: this.webId});
		this.videoSetNumberAjaxUrl = mk_url('wvideo/videoapi/timeline_video_num',{'web_id':CONFIG['web_id']});

		this.$albumSetPos = $('span.album'); //相册
		this.$eventSetPos = $('span.event'); //活动
		this.$eventCoverPos = this.$eventSetPos.parent().prev('.imgLinkLiImg').find('img');
		this.$videoSetPos = $('span.video'); //视频

		this.event('normalClick');
		this.plug('dropdown');
		this.event('dragBarClick');
		this.plug('dragsort');
		this.event('setZindex');

		if(this.$albumSetPos){
			this.view('setNumber',[this.albumSetNumberAjaxUrl,this.$albumSetPos]);
		}

		if(this.$eventSetPos){
			this.view('setCover',[this.eventSetNumCoverAjaxUrl,this.$eventSetPos,this.$eventCoverPos]);
		}

		if(this.$videoSetPos){
			this.view('setNumber',[this.videoSetNumberAjaxUrl,this.$videoSetPos]);
		}

	},
	view : function(method,arg){
		var self = this;
		var _class = {
			setNumber:function(arg){
				self.model('ajax',[arg[0],{},function(data){
					arg[1].text(data.num);
				}]);
			},
			setCover:function(arg){
				self.model('ajax',[arg[0],{},function(data){
					//arg[1].text(data.data);
					arg[2].attr('src',data.img);
				}]);
			}
		};

		return _class[method](arg);
	},
	event : function(method,arg){
		var self = this;
		var _class = {
			dragBarClick:function(){
				var nowmouseX,nowmouseY,nowmouseX2,nowmouseY2;
				self.$dragRange.find(self.dragBar).each(function(){
					$(this).bind({
						'mousedown':function(e){
							if(e.which === 1 || e.whick === 0){ //左键操作，防止右键作用 1为火狐，0为ie
								nowmouseX = parseInt(e.screenX);
								nowmouseY = parseInt(e.screenY);
							}
						},
						'mouseup':function(e){
							if(e.which === 1 || e.whick === 0){ //左键操作，防止右键作用 1为火狐，0为ie
								nowmouseX2 = parseInt(e.screenX);
								nowmouseY2 = parseInt(e.screenY);
								if(nowmouseX === nowmouseX2 && nowmouseY === nowmouseY2){
									window.location.href = $(this).attr("url");
								}
							}
						}
					});
				});
			},
			normalClick:function(){
				self.$linkObj.bind('click',function(){
					if($(this).attr('url')){
						window.location.href = $(this).attr("url");
					}
				});
			},
			setZindex:function(){
				self.$lis.bind('click',function(){
					self.$lis.not(this).removeAttr('style');
					$(this).css('z-index','999');
				});
			}
		};
		return _class[method](arg);
	},
	plug : function(method,arg){
		var self = this;
		var _class = {
			dragsort:function(){
				self.$dragRange.dragsort({
					dragSelector:self.dragBar,
					dragEnd:function(){
						var data = self.$dragRange.find('li').map(function(){
							return $(this).find("input[name='objId']").val();
						}).get();
						var dataMap = data.join('|'); //排序后顺序

						self.model('ajax',[self.dragAjaxUrl,{dataMap:dataMap,webId:self.webId},function(data){}]);

					}
				});
			},
			dropdown:function(){
				self.$perssionBar.dropdown({
					permission:{
						type:self.perssionType,
						url:self.perssionUrl,
						im:self.perssionIsIm
					},
					position:self.perssionPos
				});
			}
		};
		return _class[method](arg);
	},
	model : function(method,arg){
		var _class = {
			ajax:function(arg){
				$.ajax({
					type:'post',
					url:arg[0],
					data:arg[1],
					dataType:'jsonp',
					success:arg[2]
				});
			}
		};
		return _class[method](arg);
	}
}

$(function(){
	new CLASS_MAINAREATOPMOVE();
})