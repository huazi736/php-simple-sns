/**
 * author: wushaojie
 * date: 12-7-2
 * time: 上午10:49
 */
document.domain = CONFIG['local_run'] ? "duankou.com" : CONFIG['domain'].substring(1);
(function($) {
	
    var verifyFileType = function(fileInput) {
        var extension = fileInput.val().split('.').pop().toLowerCase(),
            mimetypes = fileInput.attr("accept").toLowerCase().split(",");
        for(var i in mimetypes) {
            var type = mimetypes[i].split("/")[1];
            if(type == extension || (type == "jpg" && extension == "jpeg")) {
				
                return true;
            }
        }
        return false;
    };
    var uploader = function(form, callback) {

        var uploadTargetFrameId = "uploadTargetFrame_" + Math.random() * 10000,
            form = $(form),
            fileInputs = form.find("input[type='file']");
        var uploadTargetFrame = $('<iframe name="' + uploadTargetFrameId + '" src="javascript:void((function(){document.open();document.domain=\'duankou.com\';document.close()}()))"></iframe>');
        uploadTargetFrame.css({
            width: 0,
            height: 0,
            border: 0,
            visibility: 'hidden'
        });

        form.parent().append(uploadTargetFrame);

        form.attr("target", uploadTargetFrameId);


        fileInputs.change(function() {
			
            var verify = verifyFileType($(this));
            if(verify) {

				if($(".inputFileImg")[0]){
					var parentElm = $(this).parents(".uploadWrap");
				}else{
					var parentElm = $(this).parent();
				}
				var upload_index	= ( $(".uploadWrap").index(parentElm) );
                parentElm.css({
                    "background":"url(" + CONFIG["misc_path"] + "/img/system/loading.gif) no-repeat center center"
                });
				
				parentElm.append('<input type="hidden" name="upload_index" value="'+upload_index+'" > ');
				
                form.submit();
				
/*                 var timeId = setInterval(function() {

                    var parames = uploadTargetFrame.contents().find("script").html();
					
                    if(parames != null) {
						
                        clearInterval(timeId);
                        parames = parames.split("params = ");
                        callback(parames, parentElm);
						
                    };
                }, 3000); */
            }
            return false;
        });
    };
    jQuery.uploader = uploader;
})(jQuery);
