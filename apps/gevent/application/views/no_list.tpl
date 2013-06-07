
<link href="<!--{$smarty.const.MISC_ROOT}-->css/event/event.css" type="text/css" rel="stylesheet" />
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
	<div id="event">
		<div class="eventBlock">
			<p class="noEventList"><img src="<!--{$smarty.const.MISC_ROOT}-->img/system/icon_date.gif" width="28" height="29" alt="" />当前没有活动&nbsp;
			<!--{if $page == 'mylist'}-->
			<a href="<!--{$create_a}-->">创建活动</a>
			<!--{/if}-->
			</p>
		</div>
	</div>
</div>

<script src="<!--{$smarty.const.MISC_ROOT}-->js/event/geventList.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/calendar/dk_calendar.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/area-utils/area_utils.js" type="text/javascript"></script>
