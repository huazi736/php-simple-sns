
/**
 * 全局访问路径生成方法
 *
 * @author        yewang
 * @update        2012/07/05
 * @description   调用说明: mk_url('blog/post/view',{dkcode:10000,id:2})
 *                          参数1:必填, 相对应3个指向分别为 应用/控制器/方法
 *                          参数2:选填, 所夹带的参数如上生成为 (.....view?dkcode=10000&id=2)
 */

function mk_url(udi,params) {
    var obj = {udi: udi};
    if(params) {
    	obj.params = params;
    }
    if(CONFIG['local_run'] === 1) {
    	obj.local_run = true;
	}
	return reverse(obj);
}

var reverse = function(obj) {
	var acm = obj.udi.split('/'),
		url = 'http://',
		l = 1;
	if(obj.local_run) {
		url += CONFIG['domain'];
		l = 0;
	} else {
		if(acm[0] === 'front') {
			url += 'www' + CONFIG['domain'] + '/';
			l = 0;
		} else {
			var sign = false;
			if(acm[0] === 'main') {
				sign = true;
				acm[0] = 'www';
			} else {
				for(var _i = 0, _l = CONFIG['subdomain'].length; _i < _l; _i++) {
					if(acm[0] === CONFIG['subdomain'][_i]) {
						sign = true;
						break;
					}
				}
			}

			if(sign !== true) {
				url += 'www' + CONFIG['domain'] + '/' + acm[0] + '/';
			} else {
				url += acm[0] + CONFIG['domain'] + '/';
			}			
		}
	}

	for(var i = l, len = acm.length; i < len; i++) {
		if(i < (len - 1)) {
			acm[i] += '/';
		}
		url += acm[i];
	}
	if(obj.params) {
		var _params = '?';
		for(var i in obj.params) {
			_params += i + '=' + obj.params[i] + '&';
		}
		_params = _params.substring(0, (_params.length - 1));
		url += _params;
	}

	return url;
}