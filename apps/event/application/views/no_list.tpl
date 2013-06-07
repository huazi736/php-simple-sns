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
			<!--{include file="tpl/top_user.tpl"}-->
			<div class="userActions">
				<span class="btnGray">
					<i></i><a id="btnCreate" href="<!--{$create_a}-->">创建活动</a>
				</span>
			</div>
		</div>
		<div class="modlueBody">
		<!-- start: 活动 -->
			<div id="contentArea">
				<div id="event">
					<!--{include file="tpl/eventType.tpl"}-->

					<div class="eventBlock">
						<p class="noEventList"><img src="<!--{$smarty.const.MISC_ROOT}-->img/system/icon_date.gif" width="28" height="29" alt="日期" />当前没有活动&nbsp;
						<!--{if $page == 'mylist'}-->
						<a href="<!--{$create_a}-->">创建活动</a>
						<!--{/if}-->
						</p>
					</div>
					<!--{if $page == 'endlist'}-->
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

<script src="<!--{$smarty.const.MISC_ROOT}-->js/event/event.js" type="text/javascript"></script>

<!--{/block}-->