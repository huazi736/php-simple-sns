<!--{include file="http:`$smarty.const.WEB_BAR`/main/application/views/header.html"}-->
<link href="<!--{$smarty.const.MISC_ROOT}-->css/event/event.css" type="text/css" rel="stylesheet" />
</head>
<body>


	<!--{include file="http:`$smarty.const.WEB_BAR`/main/application/views/top_bar.html"}-->

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
						<div class="eventBox">
							<div class="eventList"></div>
							<div class="eventLoading hide"><img src="<!--{$smarty.const.MISC_ROOT}-->img/system/loading.gif"></div>
						</div>
					</div>
					<!--{if $page != 'endlist'}-->
					<div class="eventFooter"><a href="<!--{$endlist_a}-->">已结束的活动</a></div>
					<!--{/if}-->
				</div>
			</div>
		<!-- end: 活动 -->
		</div>
	</div>
</div>
<input type="hidden" class="eventType" eventType="<!--{$page}-->" page="1" />
<input id="doMoreList_url" type='hidden' value="<!--{$doMoreList_url}-->" />

<!--{include file="http:`$smarty.const.WEB_BAR`/main/application/views/footer.html"}-->
<script src="<!--{$smarty.const.MISC_ROOT}-->js/timeline/scrollLoad.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/event/event.js" type="text/javascript"></script>
</body>
</html>
