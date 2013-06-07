/*@desc:用于资料编辑模块、注册模块的兴趣爱好选择效果
 *@example：Tagpic("#tagpicker")；
 *@before：<div id="tagpicker"><div class="tagrabs">……</div>/div>
 *@param:对应<div class="tagrabs"></div>的祖先元素
 * */
function Tagpic(parent,obj){
	var $input = obj;
	var $wiz = parent;
	var $tag_cloud_a=$("div.tag_cloud a",$wiz);
	var getTagNum=$tag_cloud_a.length;
	var $tagPicker = $('div.tagPicker',$wiz);
	var $fav_tags = $('div.tag_cloud',$tagPicker);
	var $fav_tag_basket = $tagPicker.next('div.tagBasket');
	var getFavTags = function() {
		return $('div.tagList>span',$fav_tag_basket);
	}
	var getFavTagsData = function() {
		var json = {};
		$('div.tagList>span',$fav_tag_basket).each(function(){
			var data = $(this).data();
			var cat = data.cat;
			var tag = data.tag;
			var f = json[cat];
			json[cat] = f ? f.concat(tag) : [tag];
		});
		return JSON.stringify(json);
	}
	var getFavTagsCount = function() {
		return getFavTags().length;
	}
	var calcFavTags = function() {
		$('em.num',$fav_tag_basket).text(getFavTagsCount() + '/'+getTagNum);
	}
 
	var tagExisted = function(tag,cat) {
		return $fav_tag_basket.find('span').filter(function() {
			var data = $(this).data();
			return data.tag === tag && data.cat === cat;
		}).length !== 0;
	}
	var b='';
	$('a',$fav_tags).click(function(e) {
		var $a = $(this), tag = $a.text(), cat = $a.closest('div.tag_cloud').data().cat ,dataID=$a.attr("id");
		if( tagExisted(tag,cat) ) return;
		if(getFavTagsCount()>=getTagNum){
			alert('您最多只能选择'+getTagNum+'个性标签！');
			return;
		}
		$a.addClass('cur');
		var newTag = '<span data-cat="'+cat+'" data-tag="'+tag+'" tagID="'+dataID+'">'+tag+'<a class="tagDel" href="javascript;"></a></span>';
		$('div.tagList',$fav_tag_basket).append(newTag);
		b +=tag+'、';
		$input.val(b);
		calcFavTags();
		e.stopPropagation();
	});
	$fav_tag_basket.delegate('a','click',function(e) {
		var $span = $(this).parent(), data = $span.data();
		var tag = data.tag, cat = data.cat;
		$span.remove();
		$fav_tags.filter(function() {
			return $(this).data().cat == cat;
		}).find('a').filter(function() {
			return $(this).text() == tag;
		}).removeClass('cur');
		var regex=new RegExp(tag+'、');
		b=$input.val();
		b=b.replace(regex,'');
		$input.val(b);
		calcFavTags();
		e.stopPropagation();
		return false;
	});
	calcFavTags();
}