
/*
 * Created on 2011-12-05.
 * @name: tip v1.9
 * @author: linchangyuan
 * @desc: $(".tip").tip({
 direction:"right"
 //更多属性见 [default setting]
 });


 * Update desc  
 1.0 可设置4个方向，可设置触发事件
 1.1 对上方向做了边缘判断
 1.2 对右方做了边缘判断 resize监听事件
 1.3 增加滚动条监听事件 增加异步djax
 1.4 解决hover 闪动问题
 1.5 解决click 事件绑定问题导致点开两个tip的时候前一个没有消失
 1.6 性能优化，触发事件的时候才生成VIEW。
 1.7 解决唯一标识在不同类型tip调用的时候 耦合。
 1.8 解决chrome下的滚动条监听失效问题，tip的三角提示图标的像素差问题和tip内容增大提示图标的位置不正确问题。 by wangyuefei
 1.9 解决ie6下挡不住select控件的问题,解决ID重复会绑定到同ID的其他dom上的问题。by wangyuefei
 2.0 增加支持阻止冒泡的参数，可以支持阻止冒泡。by wangyuefei
 2.1 增加设置点击tip外部是否关闭tip，默认自动关闭clickBlank:true 。by liangshanshan 
 */
(function($) {
    function CLASS_TIP(elm, options ,index){
        var self = this;
        this.ie6=($.browser.msie&&($.browser.version==6.0))?true:false;
        this.$e = $(elm);
        this.opts = options;
        this.$arrow = null;
        this.style = null;
        this.content = null;
        this.tipWinIndex = index;
        this.$tip = null;
        this.init();
        this.clickBool = false;
        this.$e._class = this;
    }
    CLASS_TIP.prototype = {
        init:function(){
            if(this.opts.hold){
                this.view();			//创建tips
            }
            this.bindEvent();		//绑定事件
            //this.position();		//设置定位
        },
        view:function(){
            var self = this;
            switch(this.opts.direction){
                case "right": this.$arrow = "arrow arrow_l";
                    break;
                case "left" : this.$arrow = "arrow arrow_r";
                    break;
                case "up"   : this.$arrow = "arrow arrow_b";
                    break;
                case "down" : this.$arrow = "arrow arrow_t";
                    break;
            }
            if(this.opts.content){
                this.content = this.opts.content;
                if(typeof(this.opts.content)=="object"){
                    this.content = this.opts.content.outerHTML();
                }
            }else{
                if(this.$e.attr("tip")){
                    this.content = this.$e.attr("tip");
                }else{
                    this.content = this.opts.content;
                }
            }

            if(this.opts.skin){
                this.skin = "tip_win_"+this.opts.skin;
            }else{
                this.skin = "";
            }
            var index = $("body").find("[tipid]").size();
            var tipId = "tip_"+this.opts.key+index;
            if($("div.tip_win[tipId="+tipId+"]").size()==0){
                this.$tip = $('<div id="tip_win_'+this.opts.showOn+'_'+new Date().getTime()+'" tipId="'+tipId+'" class="tip_win '+this.skin+'"><div class="'+this.opts.direction+'"><div class="bg">'+this.content+'</div><div class="'+this.$arrow+'"></div></div></div>').appendTo(this.opts.pBox);
            }else{
                this.$tip = $("div.tip_win[tipId="+tipId+"]");
            }
            this.ie6&&this.$tip.append('<iframe class="frameBg"></iframe>');
            this.$bg = this.$tip.children().children(":first");
            if(this.opts.maxHeight){
                this.$tip.find(".bg").css({
                    height:this.opts.maxHeight+"px",
                    overflow:"auto"
                });
            }
            this.ie6&&self.ie6iframeH();
            this.$e.attr("tipId",tipId);
            this.$tip.find(".bg").children().show();
            if(this.opts.showOn=="click"){
                this.$tip.hover(function(){
                    self.clickBool = false;
                },function(){
                    self.clickBool = true;
                });
            }
        },
        djax:function(_djax,obj){
            var self = this;

            if(_djax){
                var djax = $.extend({},{
                    data:null,
                    cache:true	//缓存 默认true
                },_djax);

                if(djax.cache&&obj.html()!=""){
                    return false;
                }
                $.djax({
                    //obj:obj,
                    //loading:true,
                    relative:true,
                    url:djax.url||self.$e.attr("url"),
                    data:djax.data||{id:self.$e.attr("tipId")},
                    dataType:djax.dataType||"json",
                    type:"POST",
                    success:function(data){
                        if(djax.dataType=="json"){
                            if(djax.handle){
                                var _data = djax.handle(data);
                                obj.html(_data);
                            }else{
                                obj.html(data.data);
                            }
                        }else{

                            obj.html(data);
                        }

                        if(djax.success){
                            djax.success(self.$e,self.$tip,self);
                        }
                        self.ie6&&self.ie6iframeH();
                        //alert('暂停');
                        self.position();
                    }
                });
            }
        },
        bindEvent:function(){
            var self = this;

            switch(this.opts.showOn){
                case "hover":
                    if(this.opts.hold){
                        this.$e.hover(
                            function(e){
                                $(this).closest(".hide").show();
                                self.view();
                                self.djax(self.opts.djax,self.$bg);
                                self.position();
                                self.$tip.show();
                                if(self.opts.stopPropagation){
                                    e.stopPropagation();
                                }
                            },
                            function(e){
                                if(self.$tip){
                                    if(self.$tip&&!self.opts.djax){
                                        self.$tip.remove();
                                        self.$tip = null;
                                    }else{
                                        self.$tip.hide();
                                    }
                                    if(self.opts.stopPropagation){
                                        e.stopPropagation();
                                    }
                                }
                            }
                        );
                        if(!this.$tip){
                            self.view();
                        }else{
                            this.$tip.hover(
                                function(e){
                                    self.view();
                                    self.position();
                                    self.$tip.show();
                                    self.djax(self.opts.djax,self.$bg);
                                    if(self.opts.stopPropagation){
                                        e.stopPropagation();
                                    }
                                },
                                function(e){
                                    if(self.$tip){
                                        if(self.$tip&&!self.opts.djax){
                                            self.$tip.remove();
                                            self.$tip = null;
                                        }else{
                                            self.$tip.hide();
                                        }
                                    }
                                    if(self.opts.stopPropagation){
                                        e.stopPropagation();
                                    }
                                }
                            );
                        }
                    }else{
                        this.$e.hover(
                            function(e){
                                self.view();
                                self.position();
                                self.$tip.show();
                                self.djax(self.opts.djax,self.$bg);
                                if(self.opts.stopPropagation){
                                    e.stopPropagation();
                                }
                            },
                            function(e){
                                if(self.$tip){
                                    if(self.$tip&&!self.opts.djax){
                                        self.$tip.remove();
                                        self.$tip = null;
                                    }else{
                                        self.$tip.hide();
                                    }
                                }
                                if(self.opts.stopPropagation){
                                    e.stopPropagation();
                                }
                            }

                        );
                    }
                    if(this.opts.clickHide){
                        this.$e.click(
                            function(e){
                                if(self.$tip){
                                    if(self.$tip&&!self.opts.djax){
                                        self.$tip.remove();
                                        self.$tip = null;
                                    }else{
                                        self.$tip.hide();
                                    }
                                }
                                if(self.opts.stopPropagation){
                                    e.stopPropagation();
                                }
                            }
                        );
                    }
                    break;
                case "toggle":
                    this.$e.click(function(e){
                        if(!self.$tip||self.$tip.css("display")=="none"){
                            self.view();
                            self.position();
                            self.$tip.show();
                            self.djax(self.opts.url,self.$bg);
                        }else if(self.$tip&&!self.opts.djax){
                            self.$tip.remove();
                            self.$tip = null;
                        }else{
                            self.$tip.hide();
                        }
                        self.clickBool = true;
                        if(self.opts.stopPropagation){
                            e.stopPropagation();
                        }
                    }).css("cursor","pointer");
                    break;
                case "click":
                    this.$e.click(function(){
                        if(!self.$tip){
                            self.view();
                        }
                        self.position();
                        self.$tip.show();
                        self.djax(self.opts.djax,self.$bg);
                        if (self.opts.clickBlank) {
                            setTimeout(function(){
                                $(document).bind("click",function(e){
                                    if(self.clickBool && (self.$tip && $(e.target).closest("[id="+self.$tip.attr('id')+"]").size()==0)){
                                        if(self.$tip){
                                            self.$tip.remove();
                                            self.$tip = null;
                                        }
                                        e.stopPropagation();
                                        $(document).unbind("click");
                                    }
                                });
                            },1)
                        };
                    }).css("cursor","pointer");
                    this.$e.hover(function(){
                        self.clickBool = false;
                    },function(){
                        self.clickBool = true;
                    });

                    break;
                default :

                    break;
            }
            $(window).resize(function() {
                if(self.$tip&&self.$tip.css("display")!="none"){
                    self.position();
                }
            });
            $(window).scroll(function () {
                if(self.$tip&&self.$tip.css("display")!="none"){
                    self.position();
                }
            });

        },
        position:function(){

            var offsetTop = this.$e.offset().top,offsetLeft = this.$e.offset().left;

            switch(this.opts.direction){
                case "right":
                    //右边缘判断，如果超出就向左显示
                    if(offsetLeft+this.$tip.outerWidth()+this.$e.outerWidth()>$("body").width()){
                        offsetLeft-=(this.$tip.outerWidth()+this.opts.arrowMargin);
                        this.$tip.find(".arrow").css({top:"5px",left:this.$tip.outerWidth()}).attr("class","arrow arrow_r")
                    }else{
                        offsetLeft+=this.$e.outerWidth()+this.opts.arrowSize-this.opts.tipMargin;
                        this.$tip.find(".arrow").css({top:"5px",left:-this.opts.arrowSize}).attr("class","arrow arrow_l")
                    }
                    break;
                case "left":
                    offsetLeft-=this.opts.width+this.opts.tipMargin;
                    this.$tip.find(".arrow").css({top:"5px",right:-this.opts.arrowSize});
                    break;
                case "up":

                    if(this.opts.position=="right"){
                        offsetLeft = offsetLeft+this.$e.outerWidth()-this.$tip.outerWidth();

                    }
                    //上边缘判断，如果超出就向下显示
                    var scrollTop=$('html').scrollTop()||$('body').scrollTop();
                    if((offsetTop-this.$tip.outerHeight()-this.opts.tipMargin)<scrollTop){

                        offsetTop+=this.$e.outerHeight()+this.opts.tipMargin;

                        if(this.opts.skin=="black"){
                            this.$tip.find(".arrow").removeAttr('style').css({top:-4}).attr("class","arrow arrow_t");
                        }else{
                            this.$tip.find(".arrow").removeAttr('style').css({"background-position":"0px 0px",top:-11}).attr("class","arrow arrow_t");
                        }
                        /*
                         if($.browser.msie){
                         top = this.$tip.outerHeight()-this.opts.tipBorderWidth;

                         }else{
                         if(this.opts.djax){
                         top = this.$tip.outerHeight()-this.opts.tipBorderWidth*2;
                         }else{
                         top = this.$tip.outerHeight()-this.opts.tipBorderWidth;
                         }
                         }
                         */
                    }else{
                        offsetTop-=this.$tip.outerHeight()+this.opts.tipMargin;
                        //var top=this.$tip.outerHeight()-this.opts.tipBorderWidth;
                        /*if($.browser.msie){
                         top = this.$tip.outerHeight()-this.opts.tipBorderWidth;

                         }else{
                         if(this.opts.djax){
                         top = this.$tip.outerHeight()-this.opts.tipBorderWidth*2;
                         }else{
                         top = this.$tip.outerHeight()-this.opts.tipBorderWidth;
                         }
                         }*/
                        if(this.opts.skin=="black"){
                            this.$tip.find(".arrow").removeAttr('style').attr("class","arrow arrow_b");
                        }else{
                            var tips=this.$tip.find(".arrow");
                            if(this.ie6){
                                tips.removeAttr('style').css({"margin-top":"-1px"})
                            }else{
                                tips.removeAttr('style').css({bottom:-11})
                            }
                            tips.css({"background-position":"0px -11px"}).attr("class","arrow arrow_b");
                        }
                    }

                    //右边缘判断，如果超出就向左显示
                    if(offsetLeft+this.opts.width>$("body").width()){
                        offsetLeft-=(this.opts.width-this.$e.outerWidth());
                        //this.$tip.find(".arrow").css({left:(this.opts.width-this.$e.outerWidth()+this.opts.arrowMargin)});
                        this.$tip.find(".arrow").css({left:(this.opts.width-this.$e.outerWidth()-2*this.opts.arrowMargin)});
                    }else{
                        this.$tip.find(".arrow").css({left:this.opts.arrowMargin});
                    }
                    if(this.opts.position=="right"){
                        this.$tip.find(".arrow").css({left:this.$tip.width()-this.opts.arrowMargin-this.opts.arrowSize});
                    }
                    break;
                case "down":
                    offsetTop+=this.$e.outerHeight()+this.opts.tipMargin;
                    this.$tip.find(".arrow").css({top:-this.opts.arrowSize});
                    break;
            }

            this.$tip.css({
                width:this.opts.width,
                height:this.opts.height,
                top:offsetTop,
                left:offsetLeft
            });
        },
        ie6iframeH:function(){
            this.$tip.find('iframe.frameBg').height(this.$tip.height());
        }
    }
    $.fn.tip = function(options) {
        var opts = $.extend({}, $.fn.tip.defaults, options);
        if(opts.skin=="black"){
            opts.arrowMargin=5;
            opts.arrowSize=8;
            opts.width="auto";
            if(opts.direction=="right"){
                opts.tipMargin=-1;
                opts.arrowMargin=2;
                opts.arrowSize=4;
            }else{
                opts.tipMargin= 5;
            }
            opts.tipBorderWidth=0;
        }

        return this.each(function(index) {
            return new CLASS_TIP(this, opts,index);
        });

    }

    $.fn.tip.defaults = {
        direction:"up",		// 设置初始弹出方向
        skin:false,				// 还有一种black
        content:"",
        width:300,
        height:"auto",
        position:"left",		// 默认左侧弹出
        maxHeight:"",
        showTimeout:0,			// 显示延时时间
        hideTimeout:0,			// 消失延时时间
        arrowMargin:15,			// 箭头离对象间距
        arrowSize:8,			// 箭头尺寸
        tipMargin:8,			// 提示框距离对象间距
        tipBorderWidth:1,		// 提示框边框宽度
        showOn:'hover',			// 触发的动作
        hold:false,				// 移到tip上是否保持tip
        pBox:document.body,		// 父容器
        clickHide:false,		// 点击需要隐藏
        key:"",					// 唯一标识区分相同层级不同调用
        stopPropagation:false,   //是否阻止冒泡
        clickBlank:true          //点击tip外自动关闭tip
    };
})(jQuery);