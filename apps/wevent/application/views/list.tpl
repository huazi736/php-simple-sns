<!--{extends file='base_layout.html'}-->

<!--{block name='title' prepend}-->
	活动列表
<!--{/block}-->

<!--{block name='header_js'}-->
<!--{/block}-->

<!--{block name='header_css'}-->
<link href="<!--{$smarty.const.MISC_ROOT}-->css/event/event.css" type="text/css" rel="stylesheet" />
<link href="<!--{$smarty.const.MISC_ROOT}-->css/pro/pro.css" type="text/css" rel="stylesheet"/>
<!--{/block}-->
<!--{block name='body'}-->
<div class="body clearfix">
	<div class="mainArea">
		<div class="modlueHeader clearfix">
			<!--{include file="tpl/top_user.tpl"}-->
			<div class="userActions">
            <!--{if $is_create==true}-->
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
		<!-- start 侧栏广告 by dongweiliang-->
		<div id="adsArea"></div>
		<!-- end 侧栏广告 by dongweiliang-->
	</div>
</div>
<input type="hidden" class="eventType" eventType="<!--{$page}-->" page="1" />
<input id="doMoreList_url" type='hidden' value="<!--{$doMoreList_url}-->" />
<!--{/block}-->
<!--{block name='footer_js'}-->
<script src="<!--{$smarty.const.MISC_ROOT}-->js/event/scrollLoad.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->web_js/event/event.js" type="text/javascript"></script>
<!-- start proArea javascript -->
<script src="<!--{$smarty.const.MISC_ROOT}-->js/pro/showPro.js" type="text/javascript"></script>
<!-- end proArea javascript -->
<!--{/block}-->
