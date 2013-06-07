/*
 * Created on 2012-4-20.
 * @name: CLASS_TIMELINE_NAV v2.0
 * @author: hcj
 * @desc: new CLASS_TIMELINE_NAV();

 * Updata 添加层级样式
 */
 /*
	新增头部事件方法HEADTOP_EVENT
	朱立琦
 */

function CLASS_TIMELINE_NAV(options) {
    this.data = options.data;
    this.content = options.content.children();
}
CLASS_TIMELINE_NAV.prototype = {
    init:function () {
        var self = this;
        var data = this.data;
        var bottom1;
        this.content.html('');
        var date = new Date(CONFIG['time']*1000);
        this.thisYear = date.getFullYear();
        this.thisMonth = date.getMonth()+1;
        this.prevMonth = date.getMonth();

        if (data[1] && String(data[1].date).split('/')[0] == this.thisYear && String(data[1].date).split('/')[1] == this.prevMonth) {
            self.formartHtml(1, data.slice(0, 2)); // 现在 和上一个月
            data = data.slice(2);
        } else {
            self.formartHtml(1, data.slice(0, 1)); // 现在
            data = data.slice(1);
        }
        //data.shift();data.shift();			   // 删除项
        if (data[data.length - 1] && data[data.length - 1].title) {
            bottom1 = [data.pop()];			// 如果有出生数据
        }
        var data0,data1,data2;
        data0 = this.formatDate(data,'months');
        if (data0.length > 10) {
            data1 = this.formatDate(data0, 'years');
            if (data1.length > 10) {
                data2 = this.formatDate(data1, 'century');
            }
            self.formartHtml(0, data2 || data1);
        } else {
            self.formartHtml(1, data0);
        }
        if (bottom1) {
            self.formartHtml(1, bottom1);		// 出生
        }
		self.headTop_event();//新增 头部事件
        this.content.parent().attr('data-loaded',1);
    },
    toNumStr:function (val) {
        var str = '';
        switch (parseInt(val)) {
            case 1 :
                str = '一';
                break;
            case 2 :
                str = '二';
                break;
            case 3 :
                str = '三';
                break;
            case 4 :
                str = '四';
                break;
            case 5 :
                str = '五';
                break;
            case 6 :
                str = '六';
                break;
            case 7 :
                str = '七';
                break;
            case 8 :
                str = '八';
                break;
            case 9 :
                str = '九';
                break;
            case 10 :
                str = '十';
                break;
            case 11 :
                str = '十一';
                break;
            case 12 :
                str = '十二';
                break;
        }
        return str += '月';
    },
    addNewYear:function (newData,_self,classTimeLine) {
        if(!newData.status){
            return;
        }

        var self = this,
            tempObj,
            tempYear = newData.data.ymd.year,
            tempMonth = newData.data.ymd.month2,
            nowYear = this.thisYear,
            nowMonth = this.thisMonth;

        if(newData.data.months.length){
            var j = (function(){
                var k,tempArr,_tempYear,_tempMonth,_tempdata = self.data;
                for(var i = 0,len = _tempdata.length;i < len;i ++){
                    tempArr = String(_tempdata[i].date).split('/');
                    _tempYear = tempArr[0];
                    _tempMonth = tempArr[1];
//                    if (_tempYear == tempYear && ((_tempMonth > newData.data.months[0] && (_tempYear == self.thisYear && _tempMonth != self.thisMonth && _tempMonth != self.prevMonth || _tempYear != self.thisYear ))|| !_tempMonth)) {
                     if (_tempYear == tempYear && (_tempMonth > newData.data.months[0]|| !_tempMonth)  || _tempYear > tempYear) {
                        k = i;
                    }
                }
                k = k == undefined ?  _tempdata.length - 2 : k;
                return k;
            })();
//            self.data[j].months =  newData.data.months;
            for (var i = 0, len = newData.data.months.length; i < len; i++) {
                var temptime = tempYear + '-' + newData.data.months[i];
                self.data.splice(j+i+1,0,{date:tempYear + '/' + newData.data.months[i]});
            }
            self.init();
        }




        for (var k in self.data) {
            var _exp = /\//g,oldTime,oldTimeArr,oldyear,nextYear,oldMonth,nextYearArr;
            oldTime = self.data[k].date || self.data[k];
            oldTimeArr = String(oldTime).split('/');
            oldyear = oldTimeArr[0];
            oldMonth = oldTimeArr[1];

            if(self.data.length > 1){
                nextYear = self.data[1].date || self.data[1];
                nextYearArr = String(nextYear).split('/');
                nextYear = nextYearArr[0] == self.thisYear && nextYearArr[1] == this.prevMonth ? nextYear : false;
            }
            if(parseInt(nowYear) == tempYear ){
                var res;
                if(self.data[k].hasOwnProperty('months')){
                    $.each(self.data[k].months,function(j,v){
                        if(tempMonth == v){
                            res = oldyear + '-' + v;
                        }
                    });
                }
                if(tempMonth == nowMonth){
                    res = oldTime;
                }else if(nextYear && tempMonth == this.prevMonth){
                    res = nextYear;
                }
                if(res){
                    return self.content.find('a[time=' + String(res).replace(/\//g, '-') + ']');
                }
            }
            if(tempYear == oldyear){
                if(self.data[k].hasOwnProperty('months')){
                    $.each(self.data[k].months,function(j,v){
                        if(tempMonth == v){
                            res = oldyear + '-' + v;
                        }
                    });
                }else if(tempMonth == oldMonth){
                    res = oldTime;
                }
                if(res){
                    return self.content.find('a[time=' + String(res).replace(/\//g, '-') + ']');
                }
            }
        }
        if(parseInt(self.data[0].date) == tempYear && ((nowMonth - tempMonth) == 1)){
            var str = self.toNumStr(tempMonth);
            tempYear =  tempYear + '/' + tempMonth;

        }
        tempObj = {
            date:tempYear + '/'+tempMonth,
            title:str,
            memo:newData.data.timedesc
        };

        for (var i in self.data) {
            var isReturn = false,
                tempDate = tempYear.date,
                selfDate = self.data[i].date || self.data[i];
            if (tempYear > parseInt(selfDate) ) {
                self.data.splice(i, 0, tempObj);
                _self.dateArray.splice(i, 0, tempObj);
                isReturn = true;
            }else if(tempYear == parseInt(selfDate) && tempMonth > String(selfDate).split('/')[1]){
                self.data.splice(i, 0, tempObj);
                _self.dateArray.splice(i, 0, tempObj);
                isReturn = true;
            } else if(i == self.data.length-1){
                self.data.push(tempObj);
                _self.dateArray.push(tempObj);
                isReturn = true;
            }
            if(isReturn){
                self.init();
                _self.view(["timelineNav"],[_self.timelineSelect,self.data]);
                return self.content.find('a[time=' + String(tempObj.date || tempObj).replace(/\//g, '-') + ']');
            }
        }

    },
    formatDate:function (data, type) {
        var name,
            temp = {},
            _data = [];
        switch (type) {
            case 'months':
                var hasMonthsYear,newYearMonth;
                for (var i = 0,len = data.length; i < len; i++ ){
                    if(String(data[i].date).indexOf('/') != -1){
                        if(i > 0 && parseInt(data[i].date) == parseInt(data[i-1].date)){
                            name = name || data[i-1];
                        }else {
                            name = name || data[i];
                            newYearMonth = data[i].date.split('/')[1];
                        }
                        name['date'] = parseInt(name.date);
                        name['months'] = name['months'] || [];
                        name['months'].push(newYearMonth || data[i].date.split('/')[1]);
                        hasMonthsYear = name;
                    } else {
                        newYearMonth && hasMonthsYear && hasMonthsYear['months'].reverse() && _data.push(hasMonthsYear);
                        hasMonthsYear = null;
                        _data.push(data[i]);
                    }
                }
                break;
            case 'years':
                for (var i = 0, len = data.length; i < len; i++) {
                    var yearName,
                        _exp = /\//g;
                    yearName = data[i].date ? (_exp.test(data[i].date) ? RegExp["$`"] : data[i].date ) : data[i];
                    yearName = parseInt(yearName);
                    if(yearName >= 0){
//                        yearName = yearName > 0 ? yearName : 1;
                        name = parseInt( yearName / 10) * 10 + '年代';
                    }else {
                        if(yearName >= -99990000 && yearName <= -1 ){
                            name = '新生代';
                        }else if(yearName >= -200000000 && yearName <= -100000000){
                            name = '中生代';
                        }else if(yearName >= -2300000000 && yearName <= -300000000){
                            name = '古生代';
                        }else if(yearName >= -4400000000 && yearName <= -2400000000){
                            name = '元古代';
                        }else if(yearName <= -4500000000 ){
                            name = '太古代';
                        }
                    }
                    temp[name] = temp[name] || [];
                    temp[name].push(data[i]);
                }
                break;
            case 'century':
                for (var i = 0, len = data.length; i < len; i++) {
                    var century = parseInt(data[i].nd);
                    if(century >= 0){
                        name = parseInt(century / 100) + 1;
                        name = name > 0 ? name : 1;
                        temp[name + '世纪'] = temp[name + '世纪'] || [];
                        temp[name + '世纪'].push(data[i]);
                    }else {
                        temp[data[i].nd] = data[i].value;
                    }
                }
                break;
            default:
                break;
        }
        if(type != 'months'){
            for (var k in temp) {
                var o = {};
                o.nd = k;
                o.value = temp[k];
                _data.push(o);
            }
        }
        return _data;
    },
    formartHtml:function (type, data,hasMonth) {
        var str = "",self = this;
        if (type) {
            $.each(data, function (a, b) {
                var selected = "",
                    birthday = "";
                if (b) {
                    b = self.toTimeTile(b);
                    selected = (b.title == '现在') ? "current" : '';
                    if (b.title == "出生") {
                        birthday = "birthday=" + b.birthday;
                    }
                    str += '<li class="' + selected + '"><a time="' + b.time + '" class="time" ' + birthday + b.attrtitle +'>' + b.title + '</a>';
                    if (b.months.length > 0) {
                        str += '<ul class="child">';
                        $.each(b.months, function (f,e) {
                            str += '<li><a time="' + b.time +'-'+ e + '" class="time">' + self.toNumStr(e) + '</a></li>';
                        });
                        str +='</ul>';
                    }
                    str += '</li>'
                }
            });
        } else {
            $.each(data, function (a, b) {
                var temp;
                str += '<li><a s="' + self.replaceBC(b.nd) + '">' + self.replaceBC(b.nd) + '</a>';
                str += '<ul class="child">';

                $.each(b.value, function (c, d) {
                    if (d.value) {
                        str += '<li><a s="' + self.replaceBC(d.nd) + '">' + self.replaceBC(d.nd) + '</a>';
                        str += '<ul class="child">';
                        $.each(d.value, function (g, h) {
                            temp = self.toTimeTile(h);
                            str += '<li><a time="' + temp.time + '" class="time"' + temp.attrtitle + ' >' + temp.title + '</a>';
                            if (temp.months.length > 0) {
                                str += '<ul class="child">';
                                $.each(temp.months, function (f, e) {
                                    str += '<li><a time="' + temp.time + '-' + e + '" class="time">' + self.toNumStr(e) + '</a></li>';
                                });
                                str += '</ul>';
                            }
                            str += '</li>';
                        });
                        str += '</ul></li>';
                    } else {
                        temp = self.toTimeTile(d);
                        str += '<li><a time="' + temp.time + '" class="time" ' + temp.attrtitle + '>' + temp.title + '</a>';
                        if (temp.months.length > 0) {
                            str += '<ul class="child">';
                            $.each(temp.months, function (f, e) {
                                str += '<li><a time="' + temp.time + '-' + e + '" class="time">' + self.toNumStr(e) + '</a></li>';
                            });
                            str += '</ul>';
                        }
                        str += '</li>';
                    }
                });
                str += '</ul></li>'
            });

        }
        this.content.append(str);
    },
    toTimeTile : function(data){
        var o = {},self = this;

        o.time = data.date ? data.date : data;
        o.time = String(o.time).replace(/\//g,'-');
        o.title =  data.title ||  data.date || data;
//        o.title = o.title > 0 ? (o.title+'年') : self.replaceBC(o.title);
        if(o.time < 0){
            o.title = parseInt(o.time);
            var wan,yi;
            yi = o.title / 100000000;
            wan = o.title / 10000;
            if((yi >> 0) != 0 ){
                o.title = yi + '亿';
            }else if((wan >> 0) != 0 ){
                o.title = wan + '万';
            }
            o.title = self.replaceBC(o.title);
        }
        if(!data.title){
            o.title += '年';
        }
        o.attrtitle = data.memo ? ('title = ' + data.memo) : '';
        o.months = data.months || [];
        return o;
    },
    replaceBC : function(str){
        return String(str).replace('-', 'B.C ');
    },
	headTop_event : function(){
		var self = this;
		
		$(document).delegate("li.selectEidtLi","click",function(){
			if($(this).hasClass('dropDown')){
				$(this).removeClass('dropDown');
				$(this).find("ul.editPro").hide();
			}else{
				$(this).addClass('dropDown');
				$(this).find("ul.editPro").show();
			}
		});
		$(document).click(function(e){
			var target = $(e.target);
			if(target.parents(".selectEidtLi").length == 0){
				$("ul.editPro").hide();
			}
		});
		$("li.selectEidtLi > .editPro").find("li").find("a").click(function(){
			var li_seft	= this;
			var id	= self.cint( $(this).parents("li").attr("lang") );
			var val	= self.cint( $(this).parents("li").attr("val") );
			var aweb_id	= self.cint( $(this).parents("li").attr("web_id") );
			var button 		= "";
			var content_val = "";
			
			if(id==1 || id==2 || id==3 || id==5){
				button = '<span class="popBtns blueBtn callbackBtn">确认</span>&nbsp;&nbsp;<span class="popBtns closeBtn">取消</span>';
				var tt = '';
				if(id==3){
					tt = '<div style="color:#999; font-size:11px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(系统将在三天后删除该网页,并通知网页粉丝)</div>';
				}
				content_val	= '<br><div >&nbsp;&nbsp;&nbsp;&nbsp;你确定要"'+$(this).text()+'"吗？</div>'+tt+'<br>';
			}else{
				button = '<span class="popBtns closeBtn">取消</span>';
				if(id==13){
					content_val	= '<br><div >&nbsp;&nbsp;&nbsp;&nbsp;网页己在 "'+$(this).text()+'" </div><br>';
				}else{
					content_val	= '<br><div >&nbsp;&nbsp;&nbsp;&nbsp;你确定要"'+$(this).text()+'"吗？</div><br>';
				}
			}
			
			
			$(this).popUp({
				width:300,
				title:'消息框',
				content: content_val,
				buttons: button,
				mask:true,
				maskMode:true,
				callback:function(){
					$("#popUp").remove();
					$("#popMask").remove();
					//slef.request_post(id);
					if(id==1 || id==5 ){
						url	= mk_url("interest/web_setting/sysname",{synname:val,web_id:aweb_id});
					}else if(id==2){
						url	= mk_url("interest/web_setting/topweb",{web_id:aweb_id});
					}else if(id==3){
						url	= mk_url("interest/web_setting/del_web",{web_id:aweb_id});
					}else{
						return ;	
					}
					
					$.djax({
						url: url,
						type: 'GET',
						data: {url: window.location.href},
						cache: false,
						dataType: 'jsonp',
						jsonp:'callback',
						success: function(rest) {
							//var rest	= eval("(" + rest + ")");  // 'act'  'msg'
							if(rest['data']['state']==1){
								if( id==1 ){
									$(li_seft).parents("li").attr("lang","5");
									$(li_seft).text("隐藏自己信息");
								}else if(id==5){
									$(li_seft).parents("li").attr("lang","1");
									$(li_seft).text("公开自己信息");
								}else if(id==3){
									$(li_seft).parents("li").attr("lang","13");
									$(li_seft).text("删除中...");
								}
								
								$.alert('成功');
							}
						}
					});
					
					
				}
			});
	
		})
	},
	/*
	request_post:function(id){
		
	},
	*/
	cint:function(value){						//  parseInt  转成数字  整型
		if( (!value))	return 0;
		var number	=  parseInt(value,10);
		if(isNaN(number)) return 0;
		return number;
	}
	
	
};
