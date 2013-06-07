/**
 * Created on  2012-07-09
 * @author: zhaohailong
 * @desc: 端口百科
 */
 $(function () {
 	wiki.view('init');
	setInterval(function(){
		wiki.view('updateDir',[$('#wikiDir')]);
	},500);
	wiki.view('prossAnchor',[$('#wikiDir'),null,'window_scroll']); 
	wiki.view('prossTextValue');
	wiki.view('prossUploadImg');
	wiki.view('surplus',[$('#description'),$('#descNum'),300]);
	wiki.event('submitContent',[$('#wikiEditForm')]);
 });