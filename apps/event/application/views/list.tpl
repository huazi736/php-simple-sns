<!--{extends file='base_layout.html'}-->
<!--{block name='title' prepend}-->
活动-
<!--{/block}-->
<!--{block name='header_js'}-->
<link href="<!--{$smarty.const.MISC_ROOT}-->css/event/event.css" rel="stylesheet" type="text/css" />
<!--{/block}-->
<!--{block name='body'}-->
<div class="body clearfix">
	<div class="mainArea">
		<div class="modlueHeader clearfix">
			<!--{include file="tpl/top_user.tpl"}-->
			<div class="userActions">
            	<!--{if $visitor==''}-->
				<span class="btnGray">                
					<i></i><a id="btnCreate" href="<!--{$create_a}-->">创建活动</a>               
				</span>
                <!--{/if}-->
			</div>
		</div>
		<div class="modlueBody">
		<!-- start: 活动 -->
			<div id="contentArea">
				<div id="event">
					<!--{include file="tpl/eventType.tpl"}-->
					<div class="eventBlock">
						<div class="eventBox">
							<div class="eventList"></div>
							<div class="eventLoading hide"><img src="<!--{$smarty.const.MISC_ROOT}-->img/system/loading.gif"></div>
						</div>
					</div>
					<!--{if $page != 'endlist'}-->
					<div class="eventFooter hide"><a href="<!--{$endlist_a}-->">已结束的活动</a></div>
					<!--{/if}-->
				</div>
			</div>
		<!-- end: 活动 -->
		</div>
	</div>
</div>
<input type="hidden" class="eventType" eventType="<!--{$page}-->" page="1" />
<input id="doMoreList_url" type='hidden' value="<!--{$doMoreList_url}-->" />

<!--{/block}-->
<!--{block name='footer_js'}-->
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/dk-ui/dk.UICombox.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/event/event.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/timeline/scrollLoad.js" type="text/javascript"></script>
<!--{/block}-->
