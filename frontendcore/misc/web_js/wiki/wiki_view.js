/**
 * Created on  2012-06-15
 * @author: zhaohailong
 * @desc: 端口百科
 */
 $(function() {
	wiki.view('updateDir',[$('.wikiDirList'),$('#wikiText'),$('.wikiDirControl')]);
	wiki.view('prossAnchor',[$('.wikiDirList'),$('#wikiText'),'window_scroll']);
	wiki.view('showMoreDir');
	wiki.view('showSideDir');
	wiki.view('showMoreSense');
	wiki.view('closeOtherElem',['wikiDirWrap',$('#wikiDirWrap')]);
	if($.browser.version == 6.0){
		wiki.view('setImgSize',$('.absImg'),180,145);
	}	
	wiki.event('reference',[$('#btn_match')]);
 });