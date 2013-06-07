					<div class="eventHead clearfix">
						<div class="eventType">
							<h3>
								<!--{if $page == 'mylist' || $page == 'endlist'}-->
                                <!--{if $visitor==''}-->
								我的活动
                                <!--{else}-->
                                <!--{$visitor.username}-->的活动
                                <!--{/if}-->
								<!--{/if}-->
								<!--{if $page == 'other'}-->
								他的活动
								<!--{/if}-->
							</h3>
						</div>
						<div id="eventActive" class="dropWrap dropMenu"></div>
					</div>

