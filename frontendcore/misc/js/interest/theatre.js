(function(){
      var cityFn=$("#city_fn"),
          cityEara=$(".city_select"),
          btnShift=cityFn.find(".shift_city"),
          cityDrop=cityFn.find(".city_drop");
      btnShift.bind("click",function(){
          cityDrop.css("left",btnShift[0].offsetLeft+"px");
      		cityDrop.toggle();
      	});
      cityEara.bind("mouseleave",function(){
      	cityDrop.hide();
      });
      var siftClassify=function(){
        $("#theatre_classify").toggle();
        return false;
      }
      $("#btn_theatre_list").bind("click",siftClassify);
})();