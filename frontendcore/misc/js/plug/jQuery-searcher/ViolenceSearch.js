/**
 * Created on 2011-12-15
 * @author: willian12345@126.com
 * @desc: 匹配输入框内输入的数据排序后返回数组
 * @version: 1.0
 *              1.1 添加向服务器请求列表数据的功能 by wangyuefei
 *              1.2 延时向服务器请求数据 by wangyuefei
 *				1.3 修复过滤关键字重复时，返回数据完全一致的bug by chengtingting
 * @eg:
 * ViolenceSearch.init(options);
 * @param1 
 * {
 * 		input: 你的输入框,
 * 		
 *		resource: 你的资源(json格式)
        例如：   
	    [{"avatar":"\/misc\/images\/avatars\/0T15420K-7.jpg","userid":"2012","username":"\u72d7\u5b50\u4e8c","location":"\u6d59\u6c5f\u676d\u5dde"},{"avatar":"\/misc\/images\/avatars\/16.jpg","userid":"2013","username":"\u4e8c\u72d7\u5b50","location":"\u6d59\u6c5f\u676d\u5dde"},{"avatar":"\/misc\/images\/avatars\/01135553C-27.jpg","userid":"2014","username":"\u72d7\u72d7\u4e8c\u5b50","location":"\u6d59\u6c5f\u676d\u5dde"}]
 *
 * 		filter: 已选择的数据父容器
 * 		filterWord: 过滤关键字
 * 		filterKey: 过滤数据项的唯一id
 * 		isFilterSelected: 是否过滤,
 * 		callback: 回调函数中会返回排序后的数据(对象)
 * 		descend: 是否降序排序
 * 		searchUrl:添加向服务器请求列表数据的url by wangyuefei
 * 	    delayTime:延时请求时间
 * }
 * 
 * callback函数表达式结果返回参数数组 [Object{index:序号,item:{原json对象}}]
 */


;if(typeof ViolenceSearch == 'undefined'){
	ViolenceSearch = {};
}

ViolenceSearch.init = function(options){
	return new SEARCHER(options);
}

function SEARCHER(options){
	this.currentInput = options.input;
	this.resource =  options.resource;
	this.originResource = [].concat(this.resource);		//得到一个新对象
	this.filter = options.filter;
	this.filterWord = options.filterWord;
	this.filterKey = options.filterKey;
	this.isFilterSelected = options.isFilterSelected;
	this.callback =  options.callback;
    this.searchUrl = options.searchUrl;
    this.delayTime = options.delayTime|1;
	this.descend = (options.descend == 'undefined' || options.descend == false) ? false : true;
	this.compactArray = [];
	this.bindChangeEvent();//input发生改变(兼容浏览器)
};

SEARCHER.prototype = {
	onInputChanged: function(_value){
		var that = this;
        var json={};
        json.keyword=_value;
        if(_value != ''){
            if(that.searchUrl){
                $.djax({
                    url:that.searchUrl,
                    data:json,
                    type:'GET',
                    dataType:'jsonp',
                    success:function(data){
                        that.callback(data);
                    }
                })
            }else{
                that.getCompactData(_value);
            }
        }else{
            if(that.searchUrl){
                $.djax({
                    url:that.searchUrl,
                    data:json,
                    type:'GET',
                    dataType:'jsonp',
                    success:function(data){
                        that.callback(data);
                    }
                })
            }else{
                that.callback();
            }
        }
	},
	bindChangeEvent: function(){	
		var that = this,time;
		/**当用户在<发送到信息框>中输入时实时匹配显示输入**/
		$(that.currentInput).bind($.browser.opera ? "input" : "keyup", function(e){
			if(e.keyCode != 40 && e.keyCode != 38 && e.keyCode != 13){
                var $this=$(this);
                if(time){
                    clearTimeout(time);
                }
				time = setTimeout(function(){
                    that.onInputChanged($.trim($this.val()));
                },that.delayTime);
			}
		});
	},
	compare: function(object1,object2){
		if(object1.index > object2.index) return 1;
		else if(object1.index < object2.index) return -1;
		else return 0;
	},
	getSelected_userids: function(){//获得已选择的数据id
		var filterIDs = [];
		var spans = $(this.filter).find('span');
		var spansLength = spans.length;
		for(var i=0;i < spansLength; i++){
			filterIDs.push($(spans[i]).attr('rel'));
		}
		return filterIDs;
	},
	getCompactData: function(_value){
		var that = this;
		var value = _value;
		
		that.compactArray.length = 0;
		
		for(var i=0, len = that.resource.length; i< len; i++){//为所有数据项加上visible属性用于过滤与还原
			that.resource[i].visible = true;
		}
		
		if(that.isFilterSelected){//是否过滤已添加的数据

			var filterIDs = that.getSelected_userids();	

			var len=$(that.filter).find('span').length;

			for(var i=0; i < len; i++){
				var num = that.resource.length - 1;
				that.resource[num].visible = true;
			}
			for(var i=0; i < len; i++){//过滤已经选择的数据
				var num = that.resource.length - 1;
				while(num >= 0){
					if(filterIDs[i] == that.resource[num][that.filterKey]){
						that.resource[num].visible = false;
					}
					num--;
				}
			}
		}

		that.compactArray.length = 0;

		for(var i=0, len = that.resource.length; i< len; i++){//匹配查找到匹配并且可见的数据项
			var currentItem = that.resource[i];
			var _index = currentItem[that.filterWord].indexOf(_value);
			if(_index >= 0 && currentItem.visible){
				var object = {};
				object.index = _index;
				object.item = currentItem;
				that.compactArray.push(object);
			}
		}
		
		if(that.compactArray.length > 0){
			that.compactArray.sort(that.compare);
			if(that.descend){
				that.compactArray.reverse();
			}
		}
		var temp = {};
		var arr = [];
		var arr1 = [];
		

		//修复过滤关键字重复时，返回数据完全一致的bug by chengtingting
		$.each(that.compactArray,function(i,o){
			temp[o.item[that.filterKey]] = o;
			temp[o.item[that.filterKey]].ischecked = false;		//当关键子有重复时，用ischecked判断，避免重复插入
			arr.push(o.item[this.filterWord]);
		});
		arr = arr.sort();			//按关键字排序
		$.each(arr,function(i,o){		//o关键字

			for(var i in temp){
				if(o === temp[i].item[this.filterWord] && !temp[i].ischecked){
					arr1.push(temp[i]);
					temp[i].ischecked = true;
				}
			}
		})
		that.callback(arr1);
	}

};
