/**
 * Created on 2011-12-29
 * @author: willian12345@126.com
 * @desc: dk.uploader(js异步上传文件插件)
	$(dom).uploader({
		inputFileId:'input file id',
		formId:'form id',
		url:'处理文件上传服务器地址',
		callback:'处理完文件后的回调函数名称'
	});
 * 其它可选参数请参考源文件末尾注释,注意在js文件与php输出文件中都需要指定当前域名，如: localhost
 * @version: 1.0
 * @eg:
 * 
 * ====================== HTML ======================
 * <div id="attachFileButton"></div>

 * ====================== javascript ======================
 * document.domain = '192.168.12.117';//设置当前域名
 * window.sendAttachedFileComplete = function(data){//文件上传后的回调函数
		sendAttachedFileCallback(data, '#attachedFiles');
	};
 * $('#attachFileButton').uploader({
			inputFileId:'FileDataInput',
			formId:'jsUploaderForm',
			url:'/app/modules/home/views/wangxiaodong-service/wangxd--upload.php?uploadAttachedFile',
			callback:'sendAttachedFileComplete'
		});
	
 * 
 * ====================== 服务端  php 代码 ====================
 * 	if (isset($_FILES['FileData'])) {
		$attachedFileId = '232434';
		$attachedFileOriginalName = $_FILES['FileData']['name'];
		$attachedFileName = rand(0,500000).dechex(rand(0,10000)).$attachedFileOriginalName;
		$attachedFileSize = $_FILES['FileData']['size'];
		$callback = $_POST['callback'];
		$inputFileId= $_POST['inputFileId'];
		$filePath = basename('--'.rand().$attachedFileOriginalName);
		move_uploaded_file($_FILES['FileData']['tmp_name'],"E:/wamp/www/duankouweb/misc/images/temp/".$filePath);
		$arr = array('id' => $attachedFileId,'filename'=>$attachedFileName,'fileOriginalName'=>$attachedFileOriginalName,'fileSize'=>$attachedFileSize );
		
		
		
		echo uploaderResult($callback, $inputFileId, $arr);
	}else{
		echo '<script type="text/javascript"> alert("上传失败"); </script>';
	}
	//uploaderResult(param1, param2)
 	//param1:客户端上传上来的回调函数
 	//param2:保存文件后服务器输出的结果
 	
	function uploaderResult($_callback, $_inputFileId, $_arr){
		$result  = '<script type="text/javascript">';
		$result .= 'document.domain = "192.168.12.117";';
		$result .= ';window.parent[\''.$_callback.'\'].call(window,';
		$result .= '\''. json_encode($_arr);
		$result .= '\''.');';
		$result .= 'window.parent.document.getElementById("uploader-loading").style.display = "none";';
		$result .= 'window.parent.document.getElementById("'.$_inputFileId.'").style.display = "block";';
		$result .= '</script>';
		return $result;
	}
 * 
 **/
/*
 *	upDate
 *  liangss(ssinsky@hotmail.com)
 * 
 * */

;(function($){
	function Uploader(elem,options){
		this.opts = options;
		this.$elem = $(elem);
		this.$loading = null;
		this.$fileInput = null;
		this.$pretender = $(this.opts.pretender);
		this.init();
	}
	
	Uploader.prototype = {
		init: function(){
			this.view();
			this.bindEvent();
		},
		view: function(){
			var that = this;
			that.$elem.prepend(that.$pretender);
			var file = '<iframe name="'+ that.opts.iframeName +'" scrolling="0" frameborder="0"></iframe>';
			file += '<form id="'+ that.opts.formId +'" enctype="multipart/form-data" target="'+ that.opts.iframeName +'" method="post" action="' + that.opts.url + '">';
			file += '<input type="hidden" name="callback" value="'+ that.opts.callback +'" />';
			file += '<input type="hidden" name="inputFileId" value="'+ that.opts.inputFileId +'" />';
			file += '<input id="'+ that.opts.inputFileId +'" type="file" name="'+ that.opts.inputFileName +'" />';
			file += '</form>';
			that.$elem.append(file);
			that.$fileInput = $('#' + that.opts.inputFileId).css({
				width:that.opts.inputWidth ? that.opts.inputWidth  : that.$pretender.outerWidth(),
				height:(that.opts.inputHeight === '22px') ? that.opts.inputHeight : that.$pretender.outerHeight(),
				position:'absolute',
				cursor:'pointer',
				opacity:0
			}).offset({left:that.$pretender.offset().left,top:that.$pretender.offset().top});
			if($.browser.mozilla){
				that.$fileInput.css({left:0});
			}
			//进度条
			that.$loading = that.opts.loading ? $('<div id="uploader-loading" style="background: url('+ CONFIG['misc_path'] +'/img/system/more_loading.gif) no-repeat center center; height: 11px; width:20px; position:absolute; top:0; left:0; display: none;"></div>') : null;
			that.$loadingContainer = that.opts.loadingContainer ? $(that.opts.loadingContainer) : null;
			if(that.$loading){
				//进度条默认位置
				if(!that.$loadingContainer){
					if(!that.$pretender.find('#uploader-loading')[0]){
						that.$pretender.append(that.$loading);
						that.$loading.css({
							width: that.$pretender.outerWidth(),
							height: that.$pretender.outerHeight()
						}).offset({left:that.$pretender.offset().left, top:that.$pretender.offset().top});
					}
				}else{//进度条用户选择位置
					if(!that.$loadingContainer.find('#uploader-loading')[0]){
						that.$loadingContainer.append(that.$loading);
					}
				}
			}
		},
		bindEvent: function(){
			var that = this;
			//选择文件
			that.$fileInput.bind('change',function(){
				var fileName = that.$fileInput.val();
				if(fileName && !((fileName.substr(fileName.lastIndexOf('.')) === ".exe"))){//过滤exe文件
					if(that.opts.img){
						var type = fileName.substr(fileName.lastIndexOf('.')).toLowerCase();
						if((type === ".png")||(type === ".jpg")||(type === ".gif")||(type === ".bmp")||(type==='.tiff')||(type===".jpeg")){
						
							that.$elem.find('#' + that.opts.formId).eq(0).submit();
							if(that.$loading){
								that.$loading.show();
							}
						}else{
							alert("您不能上传非图像文件，我们支持图片格式：jpg,png,gif,bmp,tiff,jpeg");
						}
					}else{
						that.$elem.find('#' + that.opts.formId).submit();
						if(that.$loading){
							that.$loading.show();
							//that.$fileInput.hide();
						}
					}
					
				}else{
					alert("您不能上传.exe后缀的文件作为附件");
				}
			});
		}
	};
	
	$.fn.uploader = function(options){
		var opts = $.extend({}, $.fn.uploader.defaults, options);
		return new Uploader(this,opts);
	};
	
	$.fn.uploader.defaults = {
		pretender:'<span id="uploader-pretender"></span>',//伪图片上传按钮
		inputFileId: 'FileDataInput',//input file id 此属性对应回调函数中处理显示此标签
		inputFileName: 'FileData',//input file name
		inputHeight: '22px',//input file height
		inputWidth:null,//input file width
		formId: 'jsUploaderForm',//上传文件表单id
		iframeName:'uploaderHideIframe',//异步上传文件form对应target及iframe name
		loading:true,//是否显示进步条
		loadingContainer:null,
		url:'',//文件上传服务器处理地址
		callback:null,//处理完文件上传后服务器需要在页面中输出并调用的js方法
		img:false//若上传文件为图片，则限定图片格式为png,jpg,gif,bmp,tiff。
	};
})(jQuery);
