
function CLASS_PRAISE(){
	var self = this;
	
	self.$timelineTree = $('#timelineTree');
	//self.$timelineTree.find("div.timelinebody").remove();
	self.$timelinebody = self.$timelineTree.find("div.timelinebody"); // 该代码存在问题（在IE7中导致“赞”的“加入端口网”模块错位16个像素），以下为正确代码——陈海云注释
	/*
	 * 作用：修复在IE7中导致“赞”的“加入端口网”模块错位16个像素
	 * 注释：陈海云
	 * 时间：2012-06-11
	*/
	//self.$timelinebody = self.$timelineTree;


	self.$timelineContent = $("#timelineContent");
	self.$yearList = $('#yearList');
	self.$praiseList = $('#praiseList');
	self.web_p = null;		//放置web域名，用于commentEasy
	self.flag = true; 		//标识年份
	self.isself = '你'; 	//标识进入自己还是别人的页面，默认进入自己
	
	self.init();
}
CLASS_PRAISE.prototype = {
	init:function() {
		var self = this;
		
		self.model('yearList',function(data){
			self.view('yearList',data);
		});
		
		self.event('praise',self.$praiseList.children('li'));
	},
	view:function(method,arg) {
		var self = this;
		var _class = {
			yearList:function(arg){
				self.isself = arg.isself;
				self.web_p = arg.webpath;
				
				if(arg.status === 1) {
					var data = arg.data;
					var str = '<a class="on">' + data[0] + '</a>';
					
					for(var i = 1, len = data.length; i < len; i++){
						str += '<a>' + data[i] + '</a>';
					}
					self.$yearList.append(str).css('zoom','normal');
					self.event('selectyear',self.$yearList.children('a'));
					
					var width = 0;//自动换行显示
					for(var i = 0, len = self.$yearList.children().length; i < len; i++){
						width += self.$yearList.children().eq(i).width() + 20;	//padding + border + margin - right = 20px
		
						if(width > 805){
							if( i < len){
								var more = len - i;
								var yearNum = $('<a class="yearNum"><i></i><span>' + more + '</span></a>');
								
								self.$yearList.prepend(yearNum);
								self.$yearList.children('a:gt('+i+')').hide();
								self.event('showyear',[yearNum,i]);
							}
							break;
						}
					}
					
					var year = self.$yearList.find('a.on').text();
					var type = self.$praiseList.find('li.on').attr('type');
					var flowLayout = new CLASS_FLOWLAYOUT({
						e:self.$timelinebody,
						data:{data:{'year':year,'type':type}},
						url: mk_url("main/praise/getData", ["action_dkcode=" + action_dkcode]),
						success:function(data){
							if (data.status == 0 || data.data.length == 0) {
								self.$timelinebody.html('<div class="noInfo"><div>别人还未赞过' + self.isself + '的信息</div></div>');
							}else{
								
							};
						}
					});
					
					flowLayout.init();
				}else{	//没有年份，没有赞过
					self.flag = false;
					self.$yearList.hide();
					
					self.$timelinebody.html('<div class="noInfo"><div>别人还未赞过' + self.isself + '的信息</div></div>');
				}
			}
		};
		
		return _class[method](arg);
	},
	event:function(method,dom) {
		var self = this;
		var _class ={
			showyear:function(dom){
				dom[0].toggle(function(){
					self.$yearList.children('a').show();
					$(this).find("span").hide().siblings("i").css({"background-position":"-48px 0","margin-right":"9px"});
				},function(){
					self.$yearList.children('a:gt('+dom[1]+')').hide();
					$(this).find("span").show().siblings("i").css({"background-position":"-32px 0","margin-right":"0"});
				});	
			},
			selectyear:function(dom){
				dom.each(function(){
					$(this).click(function(){
						var _this = $(this);
						var year = $(this).text();
						var type = self.$praiseList.find('li.on').attr('type');
						
						self.$praiseList.children('li').eq(0).addClass('on').siblings().removeClass('on');
						self.$timelinebody.children().not("#defaultTimeBox2").remove();

						var flowLayout = new CLASS_FLOWLAYOUT({
							e:self.$timelinebody,
							data:{data:{'year':year,'type':1}},
							url:mk_url("main/praise/getData", ["action_dkcode=" + action_dkcode]),
							success:function(data){
								if (data.status == 0 || data.data.length == 0) {
									self.$timelinebody.html('<div class="noInfo"><div><div>别人还未赞过' + self.isself + '的信息</div></div>');
								}else{
									
								};
							}
						});
						
						flowLayout.init();
						_this.addClass('on').siblings().removeClass('on');
					});
				});	
			},
			praise:function(dom){
				dom.each(function(){
					var $this = $(this);
					
					$this.click(function(){
						var type = $this.attr('type');
						
						//判断是否没有年份数据（有年份数据）
						if (self.flag) {
							var year = self.$yearList.find('a.on').text();
							
							self.$timelinebody.children().not("#defaultTimeBox2").remove();
							
							var comment_path_web = (type == 3) ? 'web_comment' : 'comment';
							var flowLayout = new CLASS_FLOWLAYOUT({
								p:comment_path_web,//别人赞了你的(dev)
								e:self.$timelinebody,
								data:{data:{'year':year,'type':type}},
								url: mk_url("main/praise/getData", ["action_dkcode=" + action_dkcode]),
								success:function(data){
									if (data.status == 0 || data.data.length == 0) {
										switch (type){
											case '1':
												self.$timelinebody.html('<div class="noInfo"><div>别人还未赞过' + self.isself + '的信息</div></div>');
												break;
											case '2':
												self.$timelinebody.html('<div class="noInfo"><div>' + self.isself + '还未赞过任何信息</div></div>');
												break;
											case '3':
												self.$timelinebody.html('<div class="noInfo"><div>' + self.isself + '还未赞过网页的信息</div></div>');
												break;
										}
									}else{
										self.$timelineTree.show();
									};
								}
							});
							
							flowLayout.init();
						}else{
							switch (type){
								case '1':
									self.$timelinebody.html('<div class="noInfo"><div>别人还未赞过'+self.isself+'的信息</div></div>');
									break;
								case '2':
									self.$timelinebody.html('<div class="noInfo"><div>'+self.isself+'还未赞过任何信息</div></div>');
									break;
								case '3':
									self.$timelinebody.html('<div class="noInfo"><div>'+self.isself+'还未赞过网页的信息</div></div>');
									break;
							}
						}
						
						$this.addClass('on').siblings().removeClass('on');
					});
				});
			}
		};
		
		return _class[method](dom);
	},
	model:function(method,arg) {
		var self = this;
		var _class = {
			yearList:function(arg){
				$.djax({
					url: mk_url("main/praise/getYears", ["action_dkcode=" + action_dkcode]),
					dataType:'json',
					success:arg,
					error:'',
					cache:false
				});
			},
			sendyear:function(arg){
				$.djax({
					url: mk_url("main/praise/getData", ["action_dkcode=" + action_dkcode]),
					dataType:'json',
					data:arg[0],
					success:arg[1],
					error:'',
					cache:false
				});
			},
			praise:function(arg){
				$.djax({
					url: mk_url("main/praise/getData", ["action_dkcode=" + action_dkcode]),
					dataType:'json',
					data:arg[0],
					success:arg[1],
					error:'',
					cache:false
				});
			}
		};
		
		return _class[method](arg);
	}
};

$(document).ready(function(){
	new CLASS_PRAISE();
});
