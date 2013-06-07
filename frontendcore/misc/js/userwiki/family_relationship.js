/**
 * Created on  2012-6-25 
 * @author: 程婷婷
 * @desc: 资料编辑模块——家庭关系 在userwiki.js里应用
 */
 
 /*
 *Update on 2012-7-15
 *@author: 梁珊珊
 *@needs misc/js/plug/jQuery-searcher/ViolenceSearch.js
 *@desc: 选择亲友自动匹配
 */

 var family = {
 	init: function(){
 		var self = this;
 		self.friends_detail = $("<div id='friends_detail'></div>");
 		self.checkFamily();
 		self.savePerson();
 		self.deletAction();
 		self.KEY = {
 			UP: 38,
			DOWN: 40,
			BACKSPACE: 8,
			ENTER:13
 		};
 	},
 	cache:{
 		delUids: []//删除的亲人关系
 	},
 	checkFamily: function(){
 		var self = this;
 		$("body").on('focus',"input[name='addRelationships']",function(e){//获取好友列表
			var $input = $(this);
			$.djax({
				url: mk_url('user/userwiki/getFriends'),
				success:function(json){
					self.search_friends([$input,json,self.friends_detail]);
				}
			});	
		});
 	},
 	search_friends:function(arg){
		var self = this;
		ViolenceSearch.init({
			input: arg[0],
			resource: arg[1].data,
			filter: arg[2],
			filterWord: 'name',
			filterKey: 'id',
			isFilterSelected:true,
			callback: function(data){//根据输入框内字符删选后的数据
				if (data) {
					$('#relationshipEdit').data('checkeddata',data);//将数据存储在#relationshipEdit
					self.addfriends(arg[0]);
				}else{
					$('#people').hide();
				};
			},
			descend: false
		});
	},
 	addfriends: function($input){
 		var self = this,
 			temp = 0;
 			existUids = $('#addRelativeFrames').find('tr.dataRow'),		//已在亲人列表的好友(每次都需要重新获取)
 			data = $('#relationshipEdit').data('checkeddata'),
			people = $('#people');
		people.remove();
		var str = '<div id="people" class="compactpeople"><ul>';
		for(var i=0,ilen = data.length; i<ilen; i++){
			for( var j=0, jlen = existUids.length; j<jlen; j++){

				if(existUids.eq(j).attr('uid') === $input.closest('.dataRow').attr('uid')){
					continue;
				}
				if(data[i].item.id == existUids.eq(j).attr('uid')){
					temp =1;
					break;
				}

			}
			if(temp ===1){temp = 0;continue;}
			str +='<li class=""><img src="'+data[i].item.face+'" class="uiProfilePhoto"><span rel="'+data[i].item.id+'" class="compactName">'+data[i].item.name+'</span></li>';
		}
		str +='</ul></div>';
		$input.after(str);
		$input.next().find('li').first().addClass('selected');
		self.selectPerson($input);//选择
		if($('#people li').length<1){
			$('#people').append('<li class="pds">暂无匹配到该用户，请先将他加为好友</li>');
		}
 	},
 	selectPerson: function($input){
 		var self = this,
 			$li = $input.next().find('li'),
 			parent = $input.closest('tr.dataRow'),
 			originalUids = self.cache.relativeMemeber;

 		$input.unbind('keydown').bind('keydown',function(e){
 			var _this = $(this);
			var compactedHuman = $('#people').find('li');	
			var currentIndex = 0;
			for (var i = 0; i < compactedHuman.length; i++) {
				if ($(compactedHuman[i]).hasClass('selected')) {
					currentIndex = i;
				}
			}
			var KEY = self.KEY;
			switch (e.keyCode){
				case KEY.UP:
					$(compactedHuman[currentIndex]).removeClass('selected');
					if (currentIndex < 1) {
						currentIndex = compactedHuman.length;
					}
					$(compactedHuman[currentIndex - 1]).addClass('selected');
					currentIndex--;
					break;
				case KEY.DOWN:
					$(compactedHuman[currentIndex]).removeClass('selected');
					if (currentIndex >= compactedHuman.length - 1) {
						currentIndex = -1;
					}
					$(compactedHuman[currentIndex + 1]).addClass('selected');
					currentIndex++;
					break;
				case KEY.ENTER:
					for (i = 0; i < compactedHuman.length; i++) {
						if ($(compactedHuman[i]).hasClass('selected')) {
							insertToken(compactedHuman[i]);
						}
					}
					return false;
					break;
			}
		});

		function insertToken(item){
			var _this = $(item),
				span = _this.find('span'),
				id = span.attr('rel'),
				face = _this.find('img').attr('src'),
				name = span.text();
				if(parent.attr('uid')){
					self.cache.delUids.push(parent.attr('uid'));
				}
				$input.val(name).attr('id',id);
				parent.attr('uid',id);
				parent.find('img.face').attr('src',face);
				$input.next().remove();
			
		}
		$li.each(function(){
			var _this = $(this),
				span = _this.find('span'),
				id = span.attr('rel'),
				face = _this.find('img').attr('src'),
				name = span.text();

			_this.click(function(){
				if(parent.attr('uid')){
					self.cache.delUids.push(parent.attr('uid'));
				}
				$input.val(name).attr('id',id);
				parent.attr('uid',id);
				parent.find('img.face').attr('src',face);
				$input.next().remove();
			}).hover(function(){
				$(this).addClass('selected').siblings().removeClass('selected');
			}, function(){
				$(this).removeClass('selected');
			});
		});
	},
	savePerson: function(){
		var self = this,
			addData = {},
			editUid = [],
			editType = [],
			editmemebers = [];
		addData.uids = [];
		addData.types = [];
		addData.faces = [];
		addData.names = [];
		addData.memebers = [];
		$('body').on('click','.relationshipStore',function(){//保存亲属
			var _this = $(this),
				addFrames = $('#addRelativeFrames'),
				delUid = self.cache.delUids,		//删除的数据
				relativeMemeber = $('#memerberList li'),		//原始成员
				newMemeber = addFrames.find('tr.dataRow');		//最新成员数据
			$.unique(delUid);
			newMemeber.each(function(){			

				var _this = $(this);
				var input = _this.find('input[name="addRelationships"]');
				var select = _this.find('select');
				var relativemate = _this.attr('uid');
				var type = select.val();
				var name = input.val();
				var face = _this.find('img.face').attr('src');
				var memeber = select.children('option[value="'+type+'"]').text();
				var i = 0,ilen = relativeMemeber.length;
				var dataRow = _this.closest('.dataRow');
				if(relativemate){					//获取新增的成员
					for(i = 0; i<ilen; i++){
						if(relativeMemeber.eq(i).attr('uid') == dataRow.attr('uid')){
							break;
						}
					}
					if(i>=ilen){
						addData.uids.push(relativemate);
						addData.types.push(type);
						addData.faces.push(face);
						addData.names.push(name);
						addData.memebers.push(memeber);
					}
				}
				for(i = 0; i<ilen; i++){					//start：获取编辑过的成员数据
					if(relativeMemeber.eq(i).attr('uid') == dataRow.attr('uid') && relativeMemeber.eq(i).find('span').attr('value') == type){
						break;
					}
				}
				if(i>=ilen){
					editUid.push(dataRow.attr('uid'));
					editType.push(type);
					editmemebers.push(memeber);
				}											//end：获取编辑过的成员数据

			});
			for(var i = 0, ilen = addData.uids.length; i<ilen; i++){		//去除删除成员列表中的重复数据
				for(var j = 0, jlen = delUid.length; j<jlen; j++){
					if(addData.uids[i] == delUid[j]){
						delUid.splice(j,1);
						break;
					}
				}
			}
			if (delUid.length!=0||addData.uids.length!=0||editUid.length!=0) {//判断是否发生添加/删除/修改关系
				$.djax({
					data:{'relativemate':addData.uids.join(' '),'type':addData.types.join(' '),'editUid':editUid.join(' '),'editType':editType.join(' '),'delUid':delUid.join(' ')},
					dataType:"json",
					url:mk_url('user/jobandschooldataedit/addFamilyMember'),
					success:function(json){
						var temp =  0;
						if(json.status === 1){
							//添加
							if (json.data.add === 1) {
								var str = '';
								for(var i = 0,len = addData.names.length;i<len;i++ ){
									if(addData.uids[i]){
										str +='<li class="familyMemeber clearfix" uid="' + addData.uids[i] +'"><img src="'+addData.faces[i]+'" width="50" height="50" /><div class="info"><a href="">'+addData.names[i]+'</a><span value="' + addData.types[i] + '">'+addData.memebers[i]+'</span></div></li>';
									}
								}
								$("#memerberList").append(str);
								if($('#memerberList').find('li').length>0){
									$('#nomemerber').hide();
								}
								addData.uids = [];
								addData.types = [];
								addData.faces = [];
								addData.names = [];
								addData.memebers = [];
							}
							//修改关系
							if(json.data.edit === 1){
								for(var i = 0,ilen = editUid.length; i<ilen; i++){
									for(var j = 0, jlen = relativeMemeber.length; j<jlen; j++){
										if(relativeMemeber.eq(j).attr('uid') == editUid[i]){
											relativeMemeber.eq(j).find('span').text(editmemebers[i]).attr('value',editType[i]);
											break;
										}
									}
								}
								editType = [];
								editUid = [];
								editmemebers = [];
							}
							//删除
							if(json.data.del === 1){
								for(var i=0, ilen=delUid.length; i<ilen; i++){
									for(var j=0,jlen=relativeMemeber.length; j<jlen; j++){
										if(delUid[i] == relativeMemeber.eq(j).attr('uid')){
											relativeMemeber.eq(j).remove();
											break;
										}
									}
								}
								if($('#memerberList li').length < 1){
									$('#nomemerber').show();
								}
								self.cache.delUids = [];
							}
						}
					}
				});
			};
			
			_this.closest('.tip_win').hide();
		});
		
	},
	deletAction: function(){
		var self = this;
			deletAction = $('#addRelativeFrames').find('img.deletAction'),//删除按钮
			data = $('#relationshipEdit').data('checkeddata');
		$('body').on('click','img.deletAction',function(){
			var _this = $(this),
				parent = _this.parents('tr.dataRow'),
				str = '<tr class="dataRow" uid=""><th width="10%"></th><td width="6%" class="data img"><img class="face"  src="'+CONFIG["misc_path"]+'img/system/blank-profilepic.png" width="50" height="50" /></td><td width="35%" class="data"><div class="fname"><input type="text" name="addRelationships"/></div></td><td width="35%" class="data"><select><option value="1">姐姐</option><option value="2">太太</option><option value="3">妈妈</option><option value="4">女儿</option><option value="5">妹妹</option><option value="6">外祖母</option><option value="7">孙女</option><option value="8">姨妈（母亲的姐姐）</option><option value="9">姑妈（父亲的姐姐）</option><option value="10">姨妈（母亲的妹妹）</option><option value="11">姑妈（父亲的妹妹）</option><option value="12">外甥女</option><option value="13">侄女</option><option value="14">表姐妹</option><option value="15">堂姐妹</option><option value="16">姻亲（女）</option><option value="17">儿媳妇</option><option value="18">岳母</option><option value="19">婆婆</option><option value="20">女性伙伴</option><option value="21">哥哥</option><option value="22">老爷</option><option value="23">爸爸</option><option value="24">儿子</option><option value="25">弟弟</option><option value="26">外祖父</option><option value="27">孙子</option><option value="28">姨父（母亲的姐姐的丈夫）</option><option value="29">姑父（父亲的姐姐的丈夫）</option><option value="30">姨父（母亲的妹妹的丈夫）</option><option value="31">姑父（父亲的妹妹的丈夫）</option><option value="32">外甥</option><option value="33">侄儿</option><option value="34">表兄弟</option><option value="35">堂兄弟</option><option value="36">姻亲（男）</option><option value="37">女婿</option><option value="38">岳父</option><option value="39">公公</option><option value="40">男性伙伴</option></select></td><td width="10%" class="data"><img class="deletAction" style="cursor:pointer" src="http://static.duankou.dev/misc/img/system/icon_close_01.gif" width="15" height="16" /></td></tr>';
			self.cache.delUids.push(parent.attr('uid'));
			if(!parent.next()[0] && parent.index() === 0){
				parent.after(str);
			}
			if(parent.find('th').text() === '家人：'){
				parent.next().find('th').text('家人：');
			}
			parent.remove();
		})
	}
 };