<!--{extends file='base_layout.html'}-->

<!--{block name='title' prepend}-->
	活动列表
<!--{/block}-->

<!--{block name='header_js'}-->
<!--{/block}-->

<!--{block name='header_css'}-->
<link href="<!--{$smarty.const.MISC_ROOT}-->css/event/event.css" type="text/css" rel="stylesheet" />
<!--{/block}-->
<!--{block name='body'}-->

<div class="body clearfix">
	<div class="mainArea">
		<div class="modlueHeader clearfix">
			<!--{include file="visitor/tpl/top_user.tpl"}-->
		</div>
		<div class="modlueBody">
		<!-- start: 活动 -->
			<div id="contentArea" uid="3" tid="d89f521c-fa63-8948-dd4a-8531bd8fd818">
				<div id="event">
					<!--{include file="visitor/tpl/eventType.tpl"}-->

					<div class="eventBlock">
						<p class="noEventList"><img src="<!--{$smarty.const.MISC_ROOT}-->img/system/icon_date.gif" width="28" height="29" alt="日期" />
						
						<!--{if $page != 'endlist'}-->
							<!--{$webinfo.name}-->没有正在进行的活动
						<!--{else}-->
							<!--{$webinfo.name}-->没有己结束的活动
						<!--{/if}-->
					</p>
					</div>
					<!--{if $page != 'endlist'}-->
					<div class="eventFooter hide"><a href="<!--{$endlist_a}-->">己结束的活动</a></div>
					<!--{/if}-->
				</div>
			</div>
		<!-- end: 活动 -->
		</div>
	</div>
</div>
<!--{/block}-->
<!--{block name='footer_js'}-->
<script src="<!--{$smarty.const.MISC_ROOT}-->web_js/event/event.js" type="text/javascript"></script>
<!--{/block}-->
