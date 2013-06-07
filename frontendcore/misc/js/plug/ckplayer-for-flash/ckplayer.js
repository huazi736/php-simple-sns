/*
JavaScript Document
ckplayer5.2.1,有问题请访问http://www.ckplayer.com,欢迎把您的风格提供给大家分享
该软件完全免费，可以用于商业或非商业的网站。如果需要商业授权的话，请联系QQ:14926108,申请时注明购买商业授权，首先说明，商业授权的和免费的版本功能完全相同，仅右键可以修改成自己的。
*/
function ckstyle(){
	var ck=new Object();
	//ck.mylogo= webpath +'misc/js/plug/ckplayer-for-flash/assets/logo.swf';//开始加载的logo,如果设置成ck.mylogo='""';就不加载,切记不能设置成ck.mylogo='';
	ck.mylogo='""';
	//ck.logo= webpath +'misc/js/plug/ckplayer-for-flash/assets/cklogo.png,2,100,20';//logo地址,对齐方式(0/1/2/3),x,y,如果不想显示图片，地址设置ck.logo='"",0,0,0'
	ck.logo='"",0,0,0'
	ck.buffer= webpath +'misc/js/plug/ckplayer-for-flash/assets/buffer.swf';//缓冲图标地址,推荐使用swf
	ck.play_g= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_Play_out.png';//播放按钮普通状态图片
	ck.play_m= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_Play_on.png';//播放按钮鼠标经过状态图片
	ck.pause_g= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_Pause_out.png';//暂停按钮普通状态图片
	ck.pause_m= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_Pause_on.png';//暂停按钮鼠标经过状态图片
	ck.pause_c= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_Pause_Scgedyke.png';//中间暂停按钮图片
	ck.sound_g= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_Sound_out.png';//静音按钮普通状态图片
	ck.sound_m= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_Sound_on.png';//静音按钮鼠标经过状态图片
	ck.mute_g= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_Mute_out.png';//取消静音按钮普通状态图片
	ck.mute_m= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_Mute_on.png';//取消静音按钮鼠标经过状态图片
	ck.full_g= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_Full_out.png';//全屏按钮普通状态图片
	ck.full_m= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_Full_on.png';//全屏按钮鼠标经过状态图片
	ck.general_g= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_General_out.png';//取消全屏按钮普通状态图片
	ck.general_m= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_General_on.png';//取消全屏按钮鼠标经过状态图片
	ck.volume_g= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_Volume_back.png';//音量调节底部图片
	ck.volume_m= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_Volume_on.png';//音量调节亮部图片
	ck.volume_s= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_Volume_Float.png';//音量调节按钮图片
	ck.schedule_s= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_Schedule.png';//进度调节按钮图片
	ck.bottom_g= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_buttom_bg.png';//底部背景图片
	ck.schedule_bg= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_Schedule_bg.png';//加载进度背景图片
	ck.schedule_load= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_Schedule_load.png';//加载进度图片
	ck.schedule_play= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_Schedule_play.png';//播放进度图片
	ck.advmu= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_v_off.png';//前置广告静音按钮图片
	ck.advnomu= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_v_on.png';//前置广告取消静音按钮图片
	ck.advjump= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_adv_skip.png';//前置广告跳过广告图片
	ck.advmclose= webpath +'misc/js/plug/ckplayer-for-flash/assets/images_close_adv.png';//关闭滚动广告的按钮
	ck.ctbar='30,0,0,30,1,5000';//底部控制栏的高,左边的距离,右边的距离,下边的距离,隐藏方式(0是不隐藏,1是全屏时隐藏,2是都隐藏),隐藏延迟时间
	ck.play_left=0;//播放/暂停按钮离左边的距离
	ck.clock='0xfdfdfd,70,40,5';//已播放时间/总时间的颜色,宽度,离左边的距离,离上边的距离
	ck.full_right=35;//全屏/取消全屏按钮离右边的距离,至少设置成按钮的宽度
	ck.vol='53,4,95,12,6,12';//音量调节框宽,高,离右边距离,上方距离,滑块的宽,高	
	ck.sound_right=125;//静音/取消静音离右边的距离
	ck.pgb='5,0,0,35,14,8';//进度框的高,离左边距离,离右边距离,离下面距离,滑块的宽,高
	ck.video_bottom=35;//视频区域底部离播放器底部的距离
	ck.re_buttom=35;//底部距离参考值,该值很重要,用来计算如暂停广告,悬浮按钮等的坐标值,正常设置成控制栏或进度调节框离底部的距离
	ck.video_bo='100,1';//视频缓冲时间,是否采用图形优化
	ck.pa='0,30';//浮动暂停按钮是否固定(0,1),透明度|说明：0在有广告时左下角对齐,1表示固定中间
	ck.buffer_wh='30,30';//缓冲图标的宽,高
	ck.pr='0x000000,0xfdfdfd,0xffffff,6,30,80,18,5';//提示框背景颜色,边框颜色,文字的颜色,边框的弧度,提示框透明度,文字透明度,提示框的高度,离按钮的距离
	ck.advmaskap=50;//前置广告遮罩层的透明度
	ck.advstatus='1,3,280,30';//前置广告是否显示静音按钮(0/1),位置(0/1/2/3),x,y
	ck.advjp='1,0,3,190,30';//前置广告是否显示跳过广告按钮,跳过按钮触发对象(值0/1,0是直接跳转,1是触发js:function ckadjump(){}),对齐方式,x,y
	ck.advt='100,3,100,30';//前置广告倒计时文本宽,对齐方式,x,y
	ck.advmarquee='1,0x000000,20,50,50,60,-5,20';//是否显示底部广告(0/1),背景颜色,高度,左边的距离,右边的距离,离下面的距离,滚动的距离(0静止),间隙时间(越小越快,建议不小于20),如果开启广告需要有function ckmarqueeadv(){return '广告内容'}
	ck.advms='1,3,40,60';//滚动广告是否显示关闭按钮(0/1),位置(0/1/2/3),x,y
	ck.statustrue=0;//是否跟踪状态-0是关闭,大于0是开启,1是调用普通状态,2增加调用视频信息,3增加调用视频播放过程状态,调用js类ckplayer_status(str),str的参数详细说明参考ckplayer.com
	ck.pr_play='点击播放';
	ck.pr_pause='点击暂停';
	ck.pr_mute='点击静音';
	ck.pr_nomute='取消静音';
	ck.pr_full='点击全屏';
	ck.pr_nofull='退出全屏';
	ck.pr_time='[$Time]';//[$Time]会自动替换目前进度提示
	ck.pr_volume='[$Volume]';//[$Volume]会自动替换音量
	ck.pr_adv='广告剩余：<font color="#FF0000" size="14"><b>[$Second]</b></font> 秒';//[$Second]会自动替换成时间
	return ck;
	}
function ckplayer(){
	 this.ckplayer_url='';
	 this.ckplayer_flv='';
	 this.ckplayer_volume=50;
	 this.ckplayer_play=true;
	 this.ckplayer_width=600;
	 this.ckplayer_height=400;
	 this.ckplayer_bgcolor='#000000';
	 this.ckplayer_allowFullScreen=true;
	 this.ckplayer_loadimg='';
	 this.ckplayer_pauseflash='';
	 this.ckplayer_pauseurl='';
	 this.ckplayer_loadadv='';
	 this.ckplayer_loadurl='';
	 this.ckplayer_loadtime=0;
	 this.ckplayer_endstatus=0;
	 this.swfwrite=function(str){
		var swfhtml='';
		swfhtml+='<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" width="'+this.ckplayer_width+'" height="'+this.ckplayer_height+'" id="ckplayer_'+str+'" align="middle">';
		swfhtml+='		<param name="movie" value="'+this.ckplayer_url+'" />';
		swfhtml+='		<param name="quality" value="high" />';
		swfhtml+='		<param name="bgcolor" value="'+this.ckplayer_bgcolor+'" />';
		swfhtml+='		<param name="wmode" value="transparent" />';
		swfhtml+='		<param name="allowScriptAccess" value="sameDomain" />';
		swfhtml+='      <param name="allowFullScreen" value="'+this.ckplayer_allowFullScreen+'" />';
		swfhtml+='      <param name="Flashvars" value="fvolume='+this.ckplayer_volume+'&fplay='+this.ckplayer_play+'&flv='+this.ckplayer_flv+'&load_img='+this.ckplayer_loadimg+'&pause_adv='+this.ckplayer_pauseflash+'&pause_url='+this.ckplayer_pauseurl+'&load_adv='+this.ckplayer_loadadv+'&load_url='+this.ckplayer_loadurl+'&load_time='+this.ckplayer_loadtime+'&end_status='+this.ckplayer_endstatus+'" />';
		swfhtml+='      <embed src="'+this.ckplayer_url+'" flashvars="fvolume='+this.ckplayer_volume+'&fplay='+this.ckplayer_play+'&flv='+this.ckplayer_flv+'&load_img='+this.ckplayer_loadimg+'&pause_adv='+this.ckplayer_pauseflash+'&pause_url='+this.ckplayer_pauseurl+'&load_adv='+this.ckplayer_loadadv+'&load_url='+this.ckplayer_loadurl+'&load_time='+this.ckplayer_loadtime+'&end_status='+this.ckplayer_endstatus+'" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="'+this.ckplayer_width+'" height="'+this.ckplayer_height+'" allowFullScreen="'+this.ckplayer_allowFullScreen+'"></embed>';
		swfhtml+='	</object>';
		document.getElementById(str).style.width=this.ckplayer_width+'px';
		document.getElementById(str).style.height=this.ckplayer_height+'px';
		document.getElementById(str).style.backgroundColor=this.ckplayer_bgcolor;
		document.getElementById(str).innerHTML=swfhtml;
		}
	}
