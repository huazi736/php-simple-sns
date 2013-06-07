/*
 * Created on 2012-3-6.
 * @name: calendar v1.0
 * @author: linchangyuan
 * @modify: qiuminggang
 * @desc: 	
			begin_year:开始年份
			end_year:结束年份
			date:默认日期
			type:日期格式
			hyphen:日期链接符
			wday:周第一天
			button:是否需要确定 清空

			
	$(".html_date").calendar({button:false,time:false});
 */
 
(function($){
	function CLASS_CALENDAR(elm, options ,index){
		var self = this;
		this.$e = $(elm);
		this.opts = options;
		this.init();
	}
	CLASS_CALENDAR.prototype = {
		init:function(){
			this.view();
			this.event();
		},
		view:function(){
			var self = this;
			var dateArr;
			if(this.$e.val().length>0){
				this.$e.attr('autocomplete','off');
				dateArr = this.$e.val().split("-");
				self.val_date =  self.setDate(dateArr[0],dateArr[1]-1,dateArr[2]);
				self.val_date.setHours(0);
				self.val_date.setMinutes(0);
				self.val_date.setSeconds(0);
				//self.opts.date=Date.parse(self.opts.date);
			}else{
				self.val_date =  self.opts.date
			}

			dateArr = self.opts.begin_year.split("-");

			self.begin_year =  self.setDate(dateArr[0],dateArr[1]-1,dateArr[2]);
			dateArr = self.opts.end_year.split("-");

			self.end_year =  self.setDate(dateArr[0],dateArr[1]-1,dateArr[2]);



			self.opts.date.setHours(0);
			self.opts.date.setMinutes(0);
			self.opts.date.setSeconds(0);
	
			self.def_year=self.val_date.getFullYear();
			self.def_month=self.val_date.getMonth()+1;

			self.def_day=self.val_date.getDate();
	
			// 判断闰年
			self.leapYear=function(y){
				return ((y%4==0 && y%100!=0) || y%400==0) ? 1 : 0;
			};
	
			// 定义每月的天数
			self.date_name_month=new Array(31,28+self.leapYear(self.def_year),31,30,31,30,31,31,30,31,30,31);
	
			// 定义每周的日期
			self.date_name_week=["日","一","二","三","四","五","六"];
	
			// 定义周末
			self.saturday=6-self.opts.wday;
			self.sunday=(7-self.opts.wday>=7) ? 0 : (7-self.opts.wday);
	
			// 创建选择器
		  
			self.date_pane=$("<div></div>",{"class":"dk_calendar"}).appendTo("body");
			self.date_title=$("<h5></h5>",{"class":"title"}).appendTo(self.date_pane);
			self.date_content = $("<div class='content'><span class='msg'></span></div>").appendTo(self.date_pane);
			self.date_table=$("<table></table>").appendTo(self.date_content);
	
			if($("#calendar_block").length<1){
				$("<div></div>",{"id":"calendar_block"}).appendTo("body");
			}
			self.calendar_block=$("#calendar_block");


            var _yearInput = '';
            //if input
            if (self.opts.input){
                _yearInput  = '<input type="text" value="" class="yearInput"  />';
            }
	
			self.temp_html="<table class='calendarHead'><tr><td><a class='pre'></a></td><td><div class='select'><span class='year'></span>"+_yearInput+"<ul>";

			for(var i=parseInt(self.opts.begin_year);i<=parseInt(self.opts.end_year);i++){
				self.temp_html+="<li value='"+i+"'><a>"+i+"</a></li>";
			}
			self.temp_html+="</ul></div></td><td><span>年</span></td><td><div class='select'><span class='month'></span><ul class='month'>";
			for(var i=1;i<=12;i++){
				self.temp_html+="<li value='"+i+"'><a>"+i+"</a></li>";
			}
			self.temp_html+="</ul></div></td><td><span>月</span></td><td><a class='next'></a></td></tr></table>";
			self.date_title.html(self.temp_html);
				
				
			
			self.select_year=self.date_title.find("span.year");
			self.select_month=self.date_title.find("span.month");
            self.select_yearInput = self.date_title.find('input.yearInput');
			self.date_exit=self.date_title.find("em");

			self.select_year.text(self.def_year);
			self.select_month.text(self.def_month);



			self.temp_html="<thead><tr>";
			for(var i=0;i<7;i++){
				self.temp_html+= (i+self.opts.wday<7) ? "<th>"+self.date_name_week[i+self.opts.wday]+"</th>" : "<th>"+self.date_name_week[i+self.opts.wday-7]+"</th>";
			}
			self.temp_html+="</tr></thead>";
			self.temp_html+="<tfoot>";
			if(self.opts.time){
				self.temp_html+="<tr><tr class='time'><td colspan='7'><input type='text' name='hour' maxlength='2'/>：<input type='text' name='minute' maxlength='2' />：<input type='text' name='seconds' maxlength='2' /></td></tr>";
			}
			if (self.opts.button) {
				self.temp_html+="<tr><td colspan='3'><a class='clear'>清除</a></td><td></td><td colspan='3'><a class='ok' name='ok'>确定</a></td></tr>";
			}

			self.temp_html+="</tfoot><tbody></tbody>";
			self.date_table.html(self.temp_html);
	
			// 高亮周末
			var table_th=self.date_table.find("thead").find("th");
			table_th.eq(self.saturday).addClass("sat");
			table_th.eq(self.sunday).addClass("sun");
	
			// 上一月、下一月、清除
		
			self.date_pre=self.date_title.find(".pre");
			self.date_next=self.date_title.find(".next");
			self.date_clear=self.date_table.find("tfoot").find(".clear");
			
			self.dateChange(self.def_year,self.def_month);
		},
		event:function(){
			var self = this;
			// 关闭日期函数
			self.dateExit=function(){
				self.date_pane.hide();
				self.calendar_block.hide();
				self.dateSelectStr="";
			};
	
			// 显示面板事件
			self.$e.bind("click",function(){
				var pane_top,pane_left;
				pane_top=self.$e.offset().top+self.$e.outerHeight();
				pane_left=self.$e.offset().left;
				self.date_pane.css({"top":pane_top,"left":pane_left}).show();

			//	self.dateChange(self.select_year.text(),self.select_month.text());
				self.calendar_block.css({width:$(window).width(),height:$(document).height()}).show();
			});

            //bind the input
            if(self.opts.input){
                var changeValue = function(e){
                    var that = $(e.target),
                        thatVal = parseInt(that.val()),
                         nowYear = new Date(self.opts.date).getFullYear();
                    //if input's val is the string
                    if(thatVal >= 0){
                        if(thatVal <= 0 ){
                            thatVal = 1;
                        }else if( thatVal > nowYear){
                            thatVal = nowYear;
                        }
                        that.val(thatVal);
                        that.prev().text(thatVal);
                        that.prev().trigger('change');
                    }else {
                        that.val(that.prev().val());
                    }
                    dateSelected(e);
                };
                self.select_yearInput.change(changeValue);
            }

	
			// 更改年月事件

			self.select_year.bind("change",function(){
				var m;
				var $monthLi = self.select_month.next().children();
				if(self.select_year.text()==self.end_year.getFullYear()){
					m = self.end_year.getMonth()+1;
					$monthLi.slice(m-1,$monthLi.size()).remove();
				}
				
				if(self.select_year.text()==self.begin_year.getFullYear()){
			
					m = self.begin_year.getMonth()+1;
					$monthLi.slice(0,m-1).remove();
				}

				self.dateChange(self.select_year.text(),self.select_month.text());
			});
			self.select_month.bind("change",function(){
				self.dateChange(self.select_year.text(),self.select_month.text());
			});
			
			self.begin_month = self.opts.begin_year.replace(/\d+\-(\d+)\-\d+/g,'$1');
			self.end_month = self.opts.end_year.replace(/\d+\-(\d+)\-\d+/g,'$1');
            self.begin_day = self.opts.begin_year.replace(/\d+\-\d+\-(\d+)/g,'$1');
	
			// 上月、下月事件
			var preHandle = function(){
				if(parseInt(self.begin_month) < parseInt(self.select_month.text()) || parseInt(self.opts.begin_year) < parseInt(self.select_year.text())){
					self.date_pre.text('<');
					self.date_pre.unbind('click').bind("click",function(){
						self.dateChange(self.select_year.text(),parseInt(self.select_month.text())-1);
						nextHandle();
						if(parseInt(self.begin_month) == parseInt(self.select_month.text()) && parseInt(self.opts.begin_year) == parseInt(self.select_year.text())){
							$(this).unbind("click");
							$(this).text('');
						}
					});
				}
			};
			var nextHandle = function(){
				if(parseInt(self.end_month) > parseInt(self.select_month.text()) || parseInt(self.opts.end_year) > parseInt(self.select_year.text())){
					self.date_next.text('>');
					self.date_next.unbind('click').bind("click",function(){
						self.dateChange(self.select_year.text(),parseInt(self.select_month.text())+1);
						preHandle();
						if(parseInt(self.end_month) == parseInt(self.select_month.text()) && parseInt(self.opts.end_year) == parseInt(self.select_year.text())){
							$(this).unbind("click");
							$(this).text('');
						}
					});
				}
			};
			preHandle();
			nextHandle();
	
			// 清除事件
			self.date_clear.bind("click",function(){
				self.$e.val("");
				self.dateChange(self.def_year,self.def_month);
				
				self.dateExit();
			});
	
			// 关闭面板事件
			self.calendar_block.bind("click",function(){
				self.dateExit();
			});
			var ok = self.date_pane.find("a.ok").bind("click",function(){
				var txt = self.date_table.find("td.selected").text();
				if(!txt){
					self.date_content.find("span.msg").text("请选择日!").show().fadeOut(2000);
					
					return false;
				}
				self.dateSelect(self.date_table.find("td.selected").text());
				if(self.opts.time){
					self.dateSelectStr+=' '+self.hours+':'+self.minute+':'+self.seconds+'';
				}
				self.$e.val(self.dateSelectStr);
				self.dateExit();
			});
			//span year;
			var hideCalendarUl = function(e){
				if($(e.target).attr("class")!="month"&&$(e.target).attr("class")!="year"){
					$("div.select ul").hide();
					$("body").unbind("click",hideCalendarUl);
				}
			};
            var hideInput  = function(e){
                if( !$(e.target).parent().hasClass('select') ){
                    $("div.select input").hide();
                    $("body").unbind("click",hideInput);
                }
            };

			self.date_pane.find("div.select span").bind("click",function(){
				var txt = $(this).text();
				var _this = $(this);
                if($(this).next().css("display")=="none"){
                    $(this).next().show();
                    if(self.opts.input && $(this).next()[0].tagName == 'INPUT'){
                        var _thisInput = $(this).next();
                        _thisInput.focus().val(txt).focusout(hideInput).select();
                        $("body").bind("click",hideInput);
                    }else {
                        var thisLi = $(this).next().children("li[value="+txt+"]");
                        var top = thisLi.prevAll().size()*thisLi.height()-(((192/thisLi.height())/2)*thisLi.height());
                        $(this).next().children("li").removeClass("selected");
                        $(this).next().children("li[value="+txt+"]").attr("class","selected");
                        if(self.end_month == self.select_month.text() && parseInt(self.opts.end_year) == self.select_year.text()){
                            $(this).next().children("li[value="+txt+"]").nextAll().hide();
                        }else if(parseInt(self.opts.end_year) != parseInt(self.select_year.text())){
                            $(this).next().children("li").show();
                        }
                        setTimeout(function(){
                            _this.next().scrollTop(top);
                        },100);
                        $("body").bind("click",hideCalendarUl);
                    }
                }else{
                    $(this).next().hide();
                }
			});
			self.date_pane.find("div.select ul").bind("click",function(e){
                dateSelected(e);
			});
            function dateSelected(e){
                if($(e.target)[0].tagName=="A" || e.type == 'change'){
                    var obj = $(e.target);


                    var txt = obj.text();
                    var span = obj.closest("ul").prev();
                    span.text(txt);

                    obj.closest("ul").hide();
                    var m,str="";
                    var $monthLi = self.select_month.next().children();
                    if(self.select_year.text()==self.end_year.getFullYear()){
                        m = self.end_year.getMonth()+1;
                        for(var i=1;i<=m;i++){
                            if(self.select_month.text()==i){
                                str+='<li value="'+i+'" class="selected"><a>'+i+'</a></li>';
                            }else{
                                str+='<li value="'+i+'"><a>'+i+'</a></li>';
                            }
                        }
                        self.select_month.next().html(str);

                    }else if(self.select_year.text()==self.begin_year.getFullYear()){
                        m = self.begin_year.getMonth()+1;
                        for(var i=m;i<=12;i++){
                            if(self.select_month.text()==i){
                                str+='<li value="'+i+'" class="selected"><a>'+i+'</a></li>';
                            }else{
                                str+='<li value="'+i+'"><a>'+i+'</a></li>';
                            }
                        }
                        self.select_month.next().html(str);

                    }else{
                        for(var i=1;i<=12;i++){
                            if(self.select_month.text()==i){

                                str+='<li value="'+i+'" class="selected"><a>'+i+'</a></li>';
                            }else{
                                str+='<li value="'+i+'"><a>'+i+'</a></li>';
                            }
                        }
                        self.select_month.next().html(str);

                    }

                    //self.select_month = self.select_month.next().find("a.selected");





                    preHandle();
                    nextHandle();


                    if(parseInt(self.end_month) <= parseInt(self.select_month.text()) && parseInt(self.opts.end_year) == self.select_year.text()){
                        self.select_month.text(self.end_month);
                        self.date_next.unbind("click");
                        self.date_next.text('');
                    }
                    if(parseInt(self.begin_month) >= parseInt(self.select_month.text()) && parseInt(self.opts.begin_year) == self.select_year.text()){
                        self.select_month.text(self.begin_month);
                        self.date_pre.unbind("click");
                        self.date_pre.text('');
                    }


                    self.dateChange(self.select_year.text(),self.select_month.text());
                }
            }
			$("body").bind("click",hideCalendarUl);
			// 日历框不允许输入
			self.$e.keydown(function(){
				return false;
			});
		},
		dateChange:function(y,m){
			var self = this;
	
			if(m<1){
				y--;
				m=12;
			}else if(m>12){
				y++;
				m=1;
			}
			m--;
			
			self.date_name_month[1]=28+self.leapYear(y);
			self.temp_html="";
			var temp_date=self.setDate(y,m,1);
			
			
			var now_date = self.opts.date;
			var val_date = self.val_date;

			// 获取当月第一天
			var firstday=(temp_date.getDay()-self.opts.wday<0) ? temp_date.getDay()-self.opts.wday+7 : temp_date.getDay()-self.opts.wday;
			// 每月所需要的行数
			//var tr_row=Math.ceil((self.date_name_month[m]+firstday)/7);
			var tr_row=6;
			var td_num,day_num,diff_now,diff_set,diff_end,diff_star,end_date,end_arr,end_m;


			for(var i=0;i<tr_row;i++){
				self.temp_html+="<tr>";
				for(var k=0;k<7;k++){
					td_num=i*7+k;
					day_num=td_num-firstday+1;
					day_num=(day_num<=0 || day_num>self.date_name_month[m] ) ? "" : td_num-firstday+1;
					self.temp_html+="<td ";

					// 高亮今天和选中日期
					diff_now=null;
					diff_set=null;
					diff_end=null;
					if(typeof(day_num)=="number"){
						temp_date=self.setDate(y,m,day_num);
						
						diff_now=self.compareDate(now_date,temp_date); // ==0 说明是当前日期
						end_arr=self.opts.end_year.split('-');
						end_m = parseInt(end_arr[1])-1;
						end_date=self.setDate(end_arr[0],end_m.toString(),end_arr[2]);
						diff_end=self.compareDate(self.end_year,temp_date);  // < 0 的时候说明超出范围了
                        // hide the day which less than the specified date
           				

                        diff_star = self.compareDate(self.begin_year,self.end_year); // < =0 的时候说明在范围内
              
						diff_set= self.compareDate(val_date,temp_date);  // ==0 说明当前选择的日期
//						if(diff_now>=0 && diff_star <= 0 ){
						if(diff_star <= 0 && diff_end >= 0 ){  // 根据上面返回值的判断得出在这个范围是begin_year end_year 之间
							self.temp_html+=(" data-set='num' title='"+y+self.opts.hyphen+(m+1)+self.opts.hyphen+day_num+"'");
						}
					}

					if(diff_end<0){
						self.temp_html+=("style='color:#ccc'>"+day_num+"</td>");
					}else{
						if(diff_set==0){
							self.temp_html+=" class='selected'";
						}else if(diff_now==0){
							self.temp_html+=" class='now'";
						}else if(diff_star > 0){
                            self.temp_html+=("class='num'></td>");
                        }else{


                        }
						self.temp_html+=(" class='pointer'>"+day_num+"</td>");
					}
				}
				self.temp_html+="</tr>";
			}
			self.date_table.find("tbody").html(self.temp_html);

			var table_tr=self.date_table.find("tbody").find("tr");
			var table_td=self.date_table.find("tbody").find("td[data-set='num']");
			table_td.addClass("num").bind("click",function(){
				table_td.removeClass("selected");
				$(this).addClass("selected");
				if(!self.opts.button){
					self.dateSelect($(this).text());
					self.$e.val(self.dateSelectStr);
					self.dateExit();
					return false;
				}else{
					self.dateSelect($(this).text());
				}
				
			});
			
			// 高亮周末
			table_tr.find("td:eq("+self.saturday+")").addClass("sat");
			table_tr.find("td:eq("+self.sunday+")").addClass("sun");

			self.select_year.text(y);
			self.select_month.text(m+1);
			
			var timeDate = new Date();
			self.hours = timeDate.getHours();
			self.minute = timeDate.getMinutes();
			self.seconds = timeDate.getSeconds();
			self.date_table.find("tfoot").find("input[name=hour]").val(self.hours);
			self.date_table.find("tfoot").find("input[name=minute]").val(self.minute);
			self.date_table.find("tfoot").find("input[name=seconds]").val(self.seconds);
			
			
		},
		dateSelect:function(d){
			var self = this;
			var temp_month,temp_day;
				temp_month=self.select_month.text();
				temp_day=d;
			if(self.opts.type=="yyyy-mm-dd"){
				temp_month="0"+self.select_month.text();
				temp_day="0"+d;
				temp_month=temp_month.substr((temp_month.length-2),temp_month.length);
				temp_day=temp_day.substr((temp_day.length-2),temp_day.length);
			}
			self.dateSelectStr = self.select_year.text()+self.opts.hyphen+temp_month+self.opts.hyphen+temp_day;
            self.opts.yearSelectCallBack && self.opts.yearSelectCallBack(self.$e,self.select_year.text(), self.dateSelectStr);//添加返回年月日的参数
		},
        setDate : function(y,m,d){
            var date = new Date();
            date.setFullYear(y);
            date.setMonth(m);
            date.setDate(d);
            return date;
        },
        compareDate : function(date1,date2){
            var res,
                year1,
                year2,
                month1,
                month2,
                date1,
                date2;
            year1 = date1.getFullYear();
            year2 = date2.getFullYear();
            month1 = date1.getMonth();
            month2 = date2.getMonth();
            date1 = date1.getDate();
            date2 = date2.getDate();
            res = compare(year1,year2);
            if(res == 0){
                res = compare(month1,month2);
                if(res == 0){
                    res = compare(date1,date2);
                }
            }
            function compare(value1, value2) {
                var a;
                if (value1 > value2) {
                    a = 1;
                } else if (value1 == value2) {
                    a = 0
                } else {
                    a = -1;
                }
                return a;
            }
            return res;
        }
		
	};
	$.fn.calendar = function(options) {
		
		var opts = $.extend({}, $.fn.calendar.defaults, options);
		return this.each(function(index) {

			var dateArr = $(this).attr("now").split("-");
			opts.date = new Date(dateArr[0],dateArr[1]-1,dateArr[2]);

			if($(this).attr("begin_year")&&$(this).attr("begin_year")!=""){
				opts.begin_year = $(this).attr("begin_year");
			}
			if($(this).attr("end_year")&&$(this).attr("end_year")!=""){
				opts.end_year = $(this).attr("end_year");
			}
			new CLASS_CALENDAR(this, opts,index);
		});
	};
	$.fn.calendar.defaults = { 
		begin_year:'1950-1-1',
		end_year:'2030-1-1',
		type:"yyyy-m-d",
		hyphen:"-",
		wday:0,
		time:true,
		button:true
	};
})(jQuery);