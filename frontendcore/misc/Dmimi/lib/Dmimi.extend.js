
DMIMI.extend = function(a,b){
	
	var _class = {};
	for(var name in b){
		_class[name] = b[name];
		
	}
	for(var name in a){
		if(b[name]==a[name]){
			return;
		}
		_class[name] = a[name];
		
	}

	return _class;
	
};