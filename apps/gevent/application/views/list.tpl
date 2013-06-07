<link href="<!--{$smarty.const.MISC_ROOT}-->css/event/geventList.css" type="text/css" rel="stylesheet" />

<div id="groupEvent">
	<div class="groupEventLabel">
		<ul class="eventLabel clearfix">
			<li class="allGroupEvent hover">全部活动</li>
			<li class="myGroupEvent">我的活动</li>
		</ul>
		<span id="eventPub" class="btnGray">
			<i></i><a href="javascript:void(0);">创建活动</a>            
		</span>
	</div>
	<div class="groupEventList">
		<div class="eventList"></div>
    	<div class="eventLoading hide"><img src="<!--{$smarty.const.MISC_ROOT}-->img/system/loading.gif"></div>
	</div>
	<div class="loadmore">
		<a id="doMoreList" href="javascript:void(0);" eventType="<!--{$page}-->" page="2" ><i></i>查看更多</a>
	</div>
</div>

<script src="<!--{$smarty.const.MISC_ROOT}-->js/event/geventList.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/calendar/dk_calendar.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/area-utils/area_utils.js" type="text/javascript"></script>