/************************************************************************
*************************************************************************
@Name :       	jRating - jQuery Plugin
@Revison :    	2.2
@Date : 		26/01/2011
@Author:     	 ALPIXEL - (www.myjqueryplugins.com - www.alpixel.fr) 
@License :		 Open Source - MIT License : http://www.opensource.org/licenses/mit-license.php
 
**************************************************************************
*************************************************************************/
//如果已经扩展此对象，不要重复扩展
(function($) {
	$.fn.jRating = function(op) {
		var defaults = {
			/** String vars **/
			bigStarsPath : CONFIG['misc_path'] + 'js/plug/jRating/' + 'icons/stars.png', // path of the icon stars.png
			smallStarsPath : CONFIG['misc_path'] + 'js/plug/jRating/' + 'icons/small.png', // path of the icon small.png
			phpPath : '', // path of the php file jRating.php
			type : 'big', // can be set to 'small' or 'big'
			
			/** Boolean vars **/
			step:false, // if true,  mouseover binded star by star,
			isDisabled:false,
			showRateInfo: true,
			
			/** Integer vars **/
			length:5, // number of star to display
			decimalLength : 1, // number of decimals.. Max 3, but you can complete the function 'getNote'
			rateMax : 10, // maximal rate - integer from 0 to 9999 (or more)
			//rateInfosX : -45, // relative position in X axis of the info box when mouseover
			//rateInfosY : 5, // relative position in Y axis of the info box when mouseover
			rateInfosId: "",  // rateInfos display dom id  edit by lijianwei
			
			/** Functions **/
			onSuccess : null,
			onError : null
		};
		if(this.length>0)					
		return this.each(function() {
			var opts = $.extend(defaults, op),    
			newWidth = 0,
			starWidth = 0,
			starHeight = 0,
			bgPath = '';

			if($(this).hasClass('jDisabled') || opts.isDisabled)
				var jDisabled = true;
			else
				var jDisabled = false;

			getStarWidth();
			$(this).height(starHeight);

			var average_value = parseFloat($(this).attr('id').split('_')[0]),
			idBox = $(this).attr('id').split('_')[1], // get the id of the box
			widthRatingContainer = starWidth*opts.length, // Width of the Container
			widthColor = average_value/opts.rateMax*widthRatingContainer, // Width of the color Container
			
			quotient = 
			$('<div>', 
			{
				'class' : 'jRatingColor',
				css:{
					width:widthColor
				}
			}).appendTo($(this)),
			
			average = 
			$('<div>', 
			{
				'class' : 'jRatingAverage',
				css:{
					width:0,
					top:- starHeight
				}
			}).appendTo($(this)),

			 jstar =
			$('<div>', 
			{
				'class' : 'jStar',
				css:{
					width:widthRatingContainer,
					height:starHeight,
					top:- (starHeight*2),
					background: 'url('+bgPath+') repeat-x'
				}
			}).appendTo($(this));
            
			if (opts.showRateInfo)
				 $("#" + opts.rateInfosId).text(average_value);

			$(this).css({width: widthRatingContainer,overflow:'hidden',zIndex:1,position:'relative'});
            
			if(!jDisabled)
			$(this).bind({
				mouseenter : function(e){
					var realOffsetLeft = findRealLeft(this);
					var relativeX = e.pageX - realOffsetLeft;
					if (opts.showRateInfo)
						$("#" + opts.rateInfosId).text(getNote(relativeX));
					/*
					var tooltip = 
					$('<p>',{
						'class' : 'jRatingInfos',
						html : getNote(relativeX)+' <span class="maxRate">/ '+opts.rateMax+'</span>',
						css : {
							top: (e.pageY + opts.rateInfosY),
							left: (e.pageX + opts.rateInfosX)
						}
					}).appendTo('body').show();
					*/
				},
				mouseover : function(e){
					$(this).css('cursor','pointer');	
				},
				mouseout : function(){
					$(this).css('cursor','default');
					//if(!check_is_rate())
						average.width(0);
				},
				mousemove : function(e){
					var realOffsetLeft = findRealLeft(this);
					var relativeX = e.pageX - realOffsetLeft;
					if(opts.step) newWidth = Math.floor(relativeX/starWidth)*starWidth + starWidth;
					else newWidth = relativeX;
					average.width(newWidth);					
					if (opts.showRateInfo)
                        $("#" + opts.rateInfosId).text(getNote(newWidth));
					/*
					$("p.jRatingInfos")
					.css({
						left: (e.pageX + opts.rateInfosX)
					})
					.html(getNote(newWidth) +' <span class="maxRate">/ '+opts.rateMax+'</span>');
					*/
				},
				mouseleave : function(){
                    $("#" + opts.rateInfosId).text(average_value);
					//$("p.jRatingInfos").remove();
				},
				click : function(e){
					//$(this).unbind().css('cursor','default').addClass('jDisabled');
                    if(check_is_rate()){
						alert('你已经评价过!');
						return false;
					}
					   //$("p.jRatingInfos").fadeOut('fast',function(){$(this).remove();});
					e.preventDefault();
					var rate = getNote(newWidth);
					//average.width(newWidth);
					$.post(opts.phpPath,{
							idBox : idBox,
							rate : rate,
							current_average: opts.otherdata.current_average,
							current_rate_nums: opts.otherdata.current_rate_nums
						},
						function(data) {
							if(data.status == 1)
							{
								set_rate_cookie();
								alert('谢谢你的评价!');
								return true;
							}
							else if(data.status == 2)
							{
								alert(data.info);
								return false;
							}
						},
						'json'
					);
				}
			});

			function getNote(relativeX) {
				var noteBrut = parseFloat((relativeX*100/widthRatingContainer)*opts.rateMax/100);
				switch(opts.decimalLength) {
					case 1 :
						var note = Math.round(noteBrut*10)/10;
						break;
					case 2 :
						var note = Math.round(noteBrut*100)/100;
						break;
					case 3 :
						var note = Math.round(noteBrut*1000)/1000;
						break;
					default :
						var note = Math.round(noteBrut*1)/1;
				}
				return note;
			};

			function getStarWidth(){
				switch(opts.type) {
					case 'small' :
						starWidth = 12; // width of the picture small.png
						starHeight = 10; // height of the picture small.png
						bgPath = opts.smallStarsPath;
					break;
					default :
						starWidth = 23; // width of the picture stars.png
						starHeight = 20; // height of the picture stars.png
						bgPath = opts.bigStarsPath;
				}
			};
			
			function findRealLeft(obj) {
			  if( !obj ) return 0;
			  return obj.offsetLeft + findRealLeft( obj.offsetParent );
			};

			function check_is_rate(){
				var web_id = $("input#web_id").val();
				var dkcode = CONFIG['action_dkcode'];
				if(getcookie(dkcode + "web_plugin_rate_" + web_id + "_" + idBox))
					return true;
				return false;
			};

			function set_rate_cookie(){
				var web_id = $("input#web_id").val();
				var dkcode = CONFIG['action_dkcode'];
				key = dkcode + "web_plugin_rate_" + web_id + "_" + idBox ;
				value = 1;
				setcookie(key, value, 30);
			};
			
			function getcookie(c_name){
				if(document.cookie.length>0){
					c_start = document.cookie.indexOf(c_name + "=");
					if(c_start != -1){
						c_start = c_start + c_name.length + 1;
						c_end = document.cookie.indexOf(";", c_start);
						if(c_end == -1) c_end = document.cookie.length;
						return unescape(document.cookie.substring(c_start, c_end));
					}
				}
			};

			function setcookie(c_name, value, expiredays){
				var exdate = new Date();
				exdate.setDate(exdate.getDate() + expiredays);
				document.cookie = c_name + "=" + escape(value) + ((expiredays == null) ? "" : ";expires="+ exdate.toGMTString())+";path=/";
			};
		});
	}
})(jQuery);