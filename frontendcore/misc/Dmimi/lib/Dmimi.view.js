/*
	@ view
	1、界面包括获取html
	2、通过json包填充html
	3、插入到指定地方
	4、当没有json包，默认只加载html

	desc $.view([ask,list,0],data);




		 $.view([ask,detail],[data]);



		 $.view([ask]);



		 $.view([ask,list])


		ask.1

			
		ask.html > <body><div id=1></div> <div id=2></body>
		

		ask.detail 

		
		// <li></li>





*/



Dmimi.prototype.view = function(name,arg){

	
	var data = arg[1];


	this.view._class = {
		info:function(arg){
			var str="";
			return str;
		},
		ask:function(arg){
			var id = arg[0].join(".");
			$.each($("#"+id).find("name",function(){
				if(arg[1][name]){
					if(typeof(arg[1][name])!="object"){
						var name = $(this).attr("name");
						$(this).html(arg[1][name]);
					}else{
						$(this).
					}

				}

			})
		}
	}


	if(name[1]){			//ask     //list 
		return $.view._class[name[0]](name[1])  // ask(detail)
	}



	if(this.view.[options.type]){
		return this.view.[options.type](options.arg);
	}else{
		self.Dmimi.include({view:options.type},{async:true});
		self.view(options);
	}
};