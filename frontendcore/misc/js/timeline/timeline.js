/*
 * Created on 2012-4-20.
 * @name: CLASS_TIMELINE_NAV v2.0
 * @author: hcj
 * @desc: new CLASS_TIMELINE_NAV();

 * Updata 添加层级样式
 */

function CLASS_TIMELINE_NAV(options) {
    this.data = options.data;
    this.content = options.content;

}
CLASS_TIMELINE_NAV.prototype = {
    init:function () {
        var self = this;
        var data = this.data;
        var bottom1;
        this.content.html('');
        if (data[1] && /\//g.test(data[1].date) ) {
            self.formartHtml(1, data.slice(0, 2)); // 现在 和上一个月
            data = data.slice(2);
        } else {
            self.formartHtml(1, data.slice(0, 1)); // 现在
            data = data.slice(1);
        }
        //data.shift();data.shift();			   // 删除项
        if (data[data.length - 1] && data[data.length - 1].birthday) {
            bottom1 = [data.pop()];			// 如果有出生数据
        }
        if (data.length > 10) {
            var data1 = this.formatDate(data, 'years');
            if (data1.length > 10) {
                var data2 = this.formatDate(data1, 'century');
            }
            self.formartHtml(0, data2 || data1);
        } else {
            self.formartHtml(1, data);
        }
        if (bottom1) {
            self.formartHtml(1, bottom1);		// 出生
        }
        this.content.parent().attr('data-loaded',1);

    },
    addNewYear:function (newData,_self) {
        if(!newData.status){
            return;
        }
        var self = this,
            _date = new Date(newData.data.ctime*1000),
            tempYear = _date.getFullYear(),
            nowDay = new Date(),
            nowMonth = nowDay.getMonth()+1,
            _tempMonth = _date.getMonth()+1;
        for (var k in self.data) {
            var _exp = /\//g,oldyear,nextYear;
            oldyear = self.data[k].date || self.data[k];
            if(self.data.length > 1){
                nextYear = self.data[1].date || self.data[1];
            }
            if((parseInt(oldyear) == tempYear) ){
                var res;
                res = _tempMonth == nowMonth ? oldyear : tempYear;
                if(nextYear && (nextYear.split('/')[1] == _tempMonth)){
                    res = nextYear;
                    return this.content.find('a[time=' + String(res).replace(/\//g, '-') + ']');
                }else if(res == oldyear && (_tempMonth != (nowMonth -1))){
                    return this.content.find('a[time=' + String(res).replace(/\//g, '-') + ']');
                }
            }


           // if(year==thisYear&&(month==thisMonth||month==prevMonth)){//当前月 或者上一个月
           //     if(month==thisMonth){//当前月

           //     }else{//上一个月

           //     }
           // }else{
           //     if(year==thisYear){// 当前年除了当前月和上一个月

           //     }else{//2011年前

           //     }
           // }
        }
        var str='';
        if(parseInt(self.data[0].date) == tempYear && ((nowMonth - _tempMonth) == 1)){
            switch (_tempMonth) {
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
            }
            str += '月';
            tempYear =  tempYear + '/' + _tempMonth;

        }
        tempYear = {
            date:tempYear,
            title:str,
            memo:newData.data.timedesc
        };
        for (var i in self.data) {
            var isReturn = false,
                tempDate = tempYear.date,
                selfDate = self.data[i].date || self.data[i];

            if (parseInt(tempDate) > parseInt(selfDate) ) {
                self.data.splice(i, 0, tempYear);
                _self.dateArray.splice(i, 0, tempYear);
                isReturn = true;
            }else if(parseInt(tempDate) == parseInt(selfDate) && String(tempDate).split('/')[1] < String(selfDate).split('/')[1]){
                self.data.splice(i + 1, 0, tempYear);
                _self.dateArray.splice(i + 1, 0, tempYear);
                isReturn = true;
            } else if(i == self.data.length-1){
                self.data.push(tempYear);
                _self.dateArray.push(tempYear);
                isReturn = true;
            }
            if(isReturn){
                self.init();
                _self.view(["timelineNav"],[_self.timelineSelect,self.data]);
                return this.content.find('a[time=' + String(tempYear.date || tempYear).replace(/\//g, '-') + ']');
            }
        }
    },
    formatDate:function (data, type) {
        var name,
            temp = {},
            _data = [];
        switch (type) {
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
        for (var k in temp) {
            var o = {};
            o.nd = k;
            o.value = temp[k];
            _data.push(o);
        }
        return _data;
    },
    formartHtml:function (type, data) {
        var str = "",self = this;
        if (type) {
            $.each(data, function (a, b) {
                var selected = "",
                    birthday = "";
                if (b) {
                    b = self.toTimeTile(b);
                    selected = (b.title == '现在') ? "current" : '';
                    if (b.birthday) {
                        birthday = "birthday=" + b.birthday;
                    }
                    str += '<li class="' + selected + '"><a time="' + b.time + '" class="time" ' + birthday + b.attrtitle +'>' + b.title + '</a></li>';
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
                            str += '<li><a time="' + temp.time + '" class="time"' + temp.attrtitle + ' >' + temp.title + '</a></li>';
                        });
                        str += '</ul></li>';
                    } else {
                        temp = self.toTimeTile(d);
                        str += '<li><a time="' + temp.time + '" class="time" ' + temp.attrtitle + '>' + temp.title + '</a></li>';
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
        //o.title = o.title > 0 ? (o.title+'年') : self.replaceBC(o.title);
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
        o.birthday = data.birthday || '';
        return o;
    },
    replaceBC : function(str){
        return String(str).replace('-', 'B.C ');
    }
};
