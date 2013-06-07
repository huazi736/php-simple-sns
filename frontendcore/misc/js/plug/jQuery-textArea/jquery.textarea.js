/**
 * Created by hcj.
 * Date: 12-5-22
 * Time: 上午11:27
 * V C model
 * des : obj = $.textarea(opts);
 * opts = {
 *     isScroll     there is the scroll or not          default : false
 *     tipKind      the tip'kind ( 'str' || 'percent' : 11/12)   default : 'str'
 *     width        the textarea's width            default : 300
 *     height       the height include textarea and tip      default : 65
 *     count        the max number of you can keypress     default : 140
 *     tipErrorColor  the color that if you keypress over the count    default : red
 * }
 * obj.isOK  // is you keypress  num in max number ,that is true ,or false
 */
(function ($) {
    $.fn.textarea = function (options) {
        var that,defaults, opts, TextareaObj, tempObj;
        //defaults setting
        defaults = {isScroll:false, tipKind:'str', width:'300', height:'65', count:20, tipErrorColor:'red',btnOK : 'btnOK',btnDisable : 'btnDisable',isNull:1};
        opts = $.extend({}, defaults, options);
        that = $(this);
        TextareaObj = function (opts) {
            this.opts = opts;
            this.ele = that;
        };
        $.extend(TextareaObj, {
            prototype:{
                init:function () {
                    this.view();
                    this.controls();
                },
                view:function () {
                    var html,
                        self = this,
                        TIP_HEIGHT = 18,
                        WIDTH = self.opts.width,
                        HEIGHT = self.opts.height,
                        props = [],
                        setId = "",
                        setMsg = "",
                        setClass = "",
                        setName = "";
//                    if(this.opts.setId){
////                        setId = "id=" + self.opts.setId;
//                        setId =  self.opts.setId;
//                        props.push(setId);
//                    }
//                    if(this.opts.setMsg) {
////                        setMsg = 'msg=' + this.opts.setMsg;
//                        setMsg = this.opts.setMsg;
//                        props.push(setMsg);
//                    }
//                    if(this.opts.setClass) {
////                        setClass = this.opts.setClass + ' jtextarea"';
//                        setClass = this.opts.setClass;
//                        props.push(setClass);
//                    }
//                    if(this.opts.setName){
////                        setName = "name="+this.opts.setName;
//                        setName = this.opts.setName;
//                    }

//                    html = '<textarea rows="" cols="" ' + setId + ' '+setName+' '+setMsg+' class="'+(setClass||"jtextarea")+'" style="width:' + (WIDTH - 2) + 'px;"></textarea>';

                    html = '<div class="jtextarea_item_tip" style="height:' + TIP_HEIGHT + 'px;margin-top:5px; display:none"><p class="jtextarea_tip" style="float:right;"></p></div>';

                    self.ele.css({
                        'width':WIDTH
//                        'height':HEIGHT - TIP_HEIGHT
                    }).append(html);


                    self.tipEle = self.ele.find('p.jtextarea_tip');
                    self.textEle = self.ele.find('textarea');
//                    self.textEle.css('width',(WIDTH - 2)+'px')
//                        .attr({
//                            'id' : setId,
//                            'msg': setMsg,
//                            'name': setName
//                        }).addClass('setClass');
                    //isScroll
                    if (!self.opts.isScroll) {
                        var _opts = {
                            'minHeight':HEIGHT - TIP_HEIGHT,
                            'maxHeight':1000
                        };
                        for (var key in _opts) {
                            self.textEle.attr(key, _opts[key]);
                        }
                        self.textEle.height(_opts.minHeight);
						self.textEle.msg({});
                        function resetHeight() {
                            !$.browser.msie && $(this).height(0);
                            var h = parseFloat(this.scrollHeight);
                            h = h < _opts.minHeight ? _opts.minHeight : h > _opts.maxHeight ? _opts.maxHeight : h;
                            $(this).height(h).scrollTop(h);
                            if (h >= _opts.maxHeight) {
                                $(this).css('overflow-y', 'scroll');
                            } else {
                                $(this).css('overflow-y', 'hidden');
                            }
                            if(self.tipEle.parent().css("display")=="none"){
                                self.tipEle.parent().show();
								if (!self.opts.isNull){
									self.opts.btn.addClass(self.opts.btnDisable);
								}
                            }
                        }

                        self.textEle.keyup(resetHeight).change(resetHeight).focus(resetHeight);
                    } else {
                        self.textEle.css('height', HEIGHT - TIP_HEIGHT);
                    }
                },
                controls:function () {
                    var btn,btnOK,btnDisable,self = this,
                        tipEle = self.tipEle,
                        COUNT = self.opts.count;

                    if(self.opts.btn){
                        btn = self.opts.btn;
                        btnOK = self.opts.btnOK;
                        btnDisable = self.opts.btnDisable;
                    }
                    self.textEle.keyup(function () {
                        var textareaVal = self.textEle[0].value;
                        isCount(textareaVal);
                    });
                    self.textEle.blur(function(){
                        var val = self.textEle[0].value;
                        if (val.indexOf(' ') > -1) {
                            val = val.replace(/^\s*|\s*$/g, '');
                        }
                        if (val == '') {
                            self.tipEle.parent().hide();
                        }
                    });

                    tipEle.html(tipFun(0, true));

                    //tipKind
                    function tipFun(val, str) {

                        var result,
                            errorStyle = 'style="color:' + self.opts.tipErrorColor + ';"';
                        if (self.opts.tipKind == 'str') {
                            result = str ? '还可以输入<span>' + val + '</span>' : '已超过<span ' + errorStyle + '>' + val + '</span>';
                        } else {
                            result = str ? '<span>' + (val) + '</span>/' + COUNT : '<span ' + errorStyle + '>' + (val) + '</span>/' + COUNT;
                        }
                        return result;
                    }

                    //main function
                    function isCount(val) {
                        var nowCount;
                        if (val.indexOf(' ') > -1) {
                            val = val.replace(/^\s*|\s*$/g, '');
                        }
                        if (val == '') {
                            if(btn&&(!parseInt(self.opts.isNull))){
//                                btn[0].disabled = true;
                                btn.addClass(btnDisable);
                            }
                            tipEle.html(tipFun(0, true));
                            return false;
                        }
//                        var len = (function (str) {
//                            var strlen = 0;
//                            for (var i = 0; i < str.length; i++) {
//                                if (str.charCodeAt(i) > 128) {
//                                    strlen += 2;
//                                } else {
//                                    strlen++;
//                                }
//                            }
//                            return strlen;
//                        })(val);
                        var len = val.length;
                        nowCount = len;


                        if (len <= COUNT) {
                            if(btn&&(!parseInt(self.opts.isNull))){
                                btn.removeClass(btnDisable);
                            }
                            tipEle.html(tipFun(nowCount, true));
                        } else {


                            if(btn){
                                btn.addClass(btnDisable);
                            }
                            nowCount = 1 * nowCount;
                            tipEle.html(tipFun(nowCount, false));
                            return false;
                        }
                        return true;
                    }
                }
            }
        });
        tempObj = new TextareaObj(opts);
        tempObj.init();
        return tempObj;
    }
})(jQuery);