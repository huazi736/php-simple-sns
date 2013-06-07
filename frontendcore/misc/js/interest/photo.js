(function(){

	if($(".PHOTOIDX").length == 0){
		return false;
	}

	function gen_pack_info(s_idx , b_show_info)
	{
		var a_html = [];
		a_html.push("<div class='pack_info clearfix" + (b_show_info?" pack_info-show":" pack_info-hide") + "'>");
		a_html.push("<p class='pack_info_idx'>" + s_idx + "</p>");
		a_html.push("<div class='pack_info_expand'>");
		a_html.push("<div class='pack_info_expand_triangle'></div><a class='pack_info_expand_tip'>展开</a>");
		a_html.push("</div></div>");

		return a_html.join("");
	}
	function gen_pack_names(a_data , b_show_names)
	{
		var a_html = [];
		var i_idx = 0;
		//获取当前的 字符
		var s_char = $(".PHOTOIDX .idxs_idx-cur").text();
		a_html.push("<div class='pack_names" + (b_show_names?" pack_names-show":" pack_names-hide") + "'>");
		a_html.push("<div class='pack_names_char'>" + s_char + "</div>");
		a_html.push("<ul class='pack_names_list clearfix'>");
		for(var i_idx=0,i_len=a_data.length; i_idx < i_len; i_idx++)
		{
			a_html.push("<li class='pack_names_list_item'>");
			a_html.push("<span class='pack_names_list_item_triangle'></span>");
			a_html.push("<a href='" + a_data[i_idx]['url'] + "'>" + a_data[i_idx]['name'] + "</a>");
			a_html.push("</li>");
		}
		a_html.push("</ul>");
		a_html.push("<div class='pack_names_collapse'><a class='pack_names_collapse_tip'>收起</a><div class='pack_names_collapse_triangle'></div></div>");
		a_html.push("</div>");
		return a_html.join("");
	}

	function gen_pack(s_idx , a_data , b_first , b_show_names)
	{
		var a_html = [];
		var b_show_info = ! b_show_names;
		a_html.push("<div class='pack clearfix" + (b_first?" pack-first":"") + "'>" );
		a_html.push(gen_pack_info(s_idx , b_show_info));
		a_html.push(gen_pack_names(a_data , b_show_names));
		a_html.push("</div>");

		return a_html.join("");
	}
	function f_callback(o_ret , textStatus)
	{
		if(textStatus !== "success"){
			return;
		}

		//遍历
		var a_html = [], o_data = o_ret['data'];
		var b_first = true, b_show_names = true;
		for(var p in o_data){
			if(o_data[p] instanceof Array){
				a_html.push(gen_pack(p, o_data[p],b_first,b_show_names));
				b_first = b_show_names = false;
			}
		}

		var $j_outer = $(".PHOTOIDX");
		$j_outer.html($j_outer.html() + a_html.join(""));
	}

	function f_idx_bind()
	{
		var $j_outer = $(".PHOTOIDX");
		var is_create = $("#hidden_is_create").val();
		$j_outer.delegate(".pack_info" , "click" , function(){
			$(this).addClass("pack_info-hide").removeClass("pack_info-show")
				.next().addClass("pack_names-show").removeClass("pack_names-hide");			
		});
		$j_outer.delegate(".pack_names" , "mouseover" , function(){
			$(this).addClass("pack_names-hover")
				.mouseout(function(){$(this).removeClass("pack_names-hover");});			
		});
		$j_outer.delegate(".pack_names_collapse" , "click" , function(){
			$(this).parent().removeClass("pack_names-show").addClass("pack_names-hide")
				.prev().removeClass("pack_info-hide").addClass("pack_info-show");		
		});
		$j_outer.delegate(".idxs_idx" , "click" , function(){
			if($(this).hasClass("idxs_idx-cur")){
				return false;
			}
			$(this).addClass("idxs_idx-cur").siblings().removeClass("idxs_idx-cur");
			$(".PHOTOIDX .pack").remove();
			var a_data = $(this).attr("data").split("_");
			var o_data = {
				'imid':a_data[0],
				'iid':a_data[1],
				'char':a_data[2],
				'is_create':is_create
			};
			var s_url = mk_url("interest/index/alist");


			$.get(s_url , o_data , f_callback , "json");
			return false;
		});
	}
	$(document).ready(f_idx_bind);
})();


$(document).ready(function(){
	if($(".PHOTOUPLOAD").length === 0){
		return false;
	}

	var o_global = {
		'drag':{},
		'j_outer': $(".PHOTOUPLOAD"),
		'j_btn': $("#distributeButton")
	};

	function get_cookie(s_cookie , s_name)
	{
		var i_bg = s_cookie.indexOf(s_name + "=");
		if(i_bg < 0){
			return "";
		}
		var i_ed = s_cookie.indexOf(";" , i_bg);
		if(i_ed < 0){
			i_ed = s_cookie.length;
		}
		var s_val = s_cookie.substring(i_bg , i_ed).split("=")[1];
		return s_val;
	}

	function gen_pict(o_pict)
	{
		var a_html = [];
		a_html.push("<li class='pict area back' img_id='" + o_pict["id"]+ "'>");
		a_html.push("<div class='pict_drag'></div>");
		a_html.push("<div class='pict_del'></div>");
		a_html.push("<div class='pict_form clearfix'>");
		a_html.push("<img class='pict_form_item pict_form_img' src='" + o_pict['imgurl'] + "'>");
		a_html.push("<textarea class='pict_form_desp pict_form_item'>请输入详细信息</textarea>");
		a_html.push("<div class='pict_form_item pict_form_pict-info pict_form_pict-info-name clearfix'>");
		a_html.push("<label class='label'>名称</label>");
		a_html.push("<input type='text' class='val name' name='' value='' />");
		a_html.push("</div>");
		a_html.push("<div class='pict_form_item pict_form_pict-info pict_form_pict-info-tag clearfix'>");
		a_html.push("<label class='label'>标签</label>");
		a_html.push("<input type='text' class='val tag' name='' value='' />");
		a_html.push("</div>");
		a_html.push("</div>");
		a_html.push("</li>");
		return a_html.join("");
	}
	function gen_form_data()
	{
		console.log("inside gen_form_data()");
		var o_ret = {};
		var j_title = j_desp = j_picts = null;
		var j_childs = o_global.j_outer.children();
		j_title = j_childs.filter(".desp_title");
		j_desp = j_childs.filter(".desp_all");
		j_picts = j_childs.filter(".picts").children();

		j_picts_desp = j_picts.find(".pict_form_desp");
		j_picts_title = j_picts.find(".pict_form_pict-info .name");
		j_picts_tags = j_picts.find(".pict_form_pict-info .tag");

		var i_total = j_picts.length;
		o_ret["s_title"] = j_title.val();
		o_ret["s_desp"] = j_desp.val();
		o_ret["a_pict"] = [];
		o_ret['web_id'] = CONFIG['web_id'];

		o_ret['s_title'] = o_ret['s_title'] === '请输入影集名称' ? '' : o_ret['s_title'];
		o_ret['s_desp'] = o_ret['s_desp'] === '请输入详细信息' ? '' : o_ret['s_desp'];
		var i = 0;
		for(i = 0; i < i_total; i++){
			o_ret["a_pict"][i] = {};
		}
		i = 0;
		var r_blank = /^[ 	,;]+|[ 	,;]+$/g;
		var r_sep = /[ 	,;]+/g;
		j_picts.each(function(){
			o_ret["a_pict"][i++].i_id = $(this).attr("img_id").replace(r_blank , "");
			i %= i_total;
		});
		j_picts_title.each(function(){
			o_ret["a_pict"][i++].s_title = $(this).val().replace(r_blank , "");
			i %= i_total;
		});

		j_picts_tags.each(function(){
			o_ret["a_pict"][i++].s_tags = $(this).val().replace(r_sep , " ").replace(r_blank , "");
			i %= i_total;
		});

		j_picts_desp.each(function(){
			o_ret["a_pict"][i++].s_desp = $(this).val().replace(r_blank , "");
			i %= i_total;
		});

		console.log(o_ret);
		return o_ret;
	}
	function f_submit_form()
	{
		//alert("now submit the form!");
		//$.post(mk_url("channel/shoot/addWorksInfo") , gen_form_data() , function(){alert('yh!');} , 'json');
		var j_p = o_global.j_btn.parent();
		if(j_p.hasClass("active") && j_p.attr('data') === "true")
		{
			var o_data = gen_form_data();
			o_data.type = 'shoot';
			$.post(mk_url("webmain/web/doPost") , o_data , function(){alert('yh!');} , 'json');			
		}

	}
	function f_activate_button()
	{
		if($('#shootDK')[0]){
			o_global.j_btn.parent().removeClass("disable").addClass("active").attr("data" , "true").unbind("click" , f_submit_form).bind("click" , f_submit_form);
		}
	}
	function f_freeze_button()
	{
		if($('#shootDK')[0]){
			o_global.j_btn.parent().removeClass("active").addClass("disable").attr("data" , "false").unbind("click" , f_submit_form);
		}
	}

	function f_del_pict()
	{
		//alert("pict deleted");
		$(this).closest(".pict").remove();
		if($(".PHOTOUPLOAD .pict").length === 0){
			f_freeze_button();
		}
	}

	function f_auto_tip(j_outer , s_selector , s_tip)
	{
		j_outer.delegate(s_selector , "blur" , function(){
			if($(this).val() === ""){
				$(this).val(s_tip);
			}
		}).delegate(s_selector , "focus" , function(){
			if($(this).val() === s_tip){
				$(this).val("");
			}
		});
	}

	o_global.j_outer.delegate(".pict_del" , "click" , f_del_pict);
	f_auto_tip(o_global.j_outer , ".desp_all" , "请输入描述信息");
	f_auto_tip(o_global.j_outer , ".desp_title" , "请输入影集名称");
	f_auto_tip(o_global.j_outer , ".pict_form_desp" , "请输入详细信息");


	//$(".PHOTOUPLOAD").mousedown(function(){alert("your sis!");});
	var s_path_of_swf = CONFIG['misc_path'] + "flash/plug-flash/jQuery-uploadify/uploadify.swf";
	$("#add_img").uploadify({
		'uploader':s_path_of_swf,
		'script':mk_url('channel/shoot/uploadImage'),
		'width':397,
		'height':68,
		'method':'POST',
		'buttonImg':CONFIG['misc_path']+'img/photo/begin.jpg',
		'multi':true,
		'auto':true,
		'fileExt':'*.jpg;*.jpeg;*.png;*.gif',
		'sizeLimit':10485760,
		'fileDataName':'uploadfile',
		'queueSizeLimit':15,
		'scriptData':{
			'sessionid':get_cookie(document.cookie , "PHPSESSID")
		},
		'ShowProgressBar':false,
		'onError':function(e,ID,fileObj,errorObj){
			$.alert(errorObj.type+':'+errorObj.info,'错误提示');
		},
		'onCheck':function(){
			alert('ssss');
		},
		'onComplete':function(e,queueId,fileObj,response,data){
			var o_ret = eval("(" + response + ")");
			if(o_ret['status'] === 1){
				$(".PHOTOUPLOAD .picts").append(gen_pict(o_ret['data']));
				f_activate_button();
			}else{
				//失败,后期处理

			}
		}
	});
	

});
