/**
 * JAVASCRIPT
 *
 * @author        liangshanshan
 * @date          2011/10/25
 * @version       1.0
 * @description   验证正则类
 * @history       <author><time><version><desc>
 */


var validator={
	require : /[^(^\s*)|(\s*$)]/,
	email : /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/,
	phone : /^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/,
	mobile : /^((\(\d{3}\))|(\d{3}\-))?13[0-9]\d{8}?$|15\d{9}?$|18\d{9}?$|147\d{8}?$/,
	url : /^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/,
	number : /^\d+$/,
	qq : /^[1-9]\d{4,9}$/,
	integer : /^[-\+]?\d+$/,
	english : /^[A-Za-z]+$/,
	chinese : /^[\u0391-\uFFE5]+$/,
	patt: /\;\s*\,/,
	zip : /^[1-9]\d{5}$/,
	ip: /^((?:(?:25[0-5]|2[0-4]\d|[01]?\d?\d)\.){3}(?:25[0-5]|2[0-4]\d|[01]?\d?\d))$/,
	userName : /^[A-Za-z0-9_]{3,}$/i,
	//验证只能输26英文字母和中文
	codeName : /^[a-zA-Z\u4e00-\u9fa5]+$/
};
