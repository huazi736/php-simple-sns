			<span class="userImg"><a href="<!--{$main_a}-->"><img src="<!--{if $visitor}--><!--{$visitor.avatar}-->
            <!--{else}-->
            <!--{$user.avatar}--><!--{/if}-->" onerror="this.src='<!--{$smarty.const.MISC_ROOT}-->img/default/avatar_30.gif'" /></a></span>
			<div class="userName">
				<span class="nameTxt"><a href="<!--{$main_a}-->">
                <!--{if $visitor==''}-->
                <!--{$user.username}-->
                <!--{else}-->
                <!--{$visitor.username}-->
                <!--{/if}-->
                </a></span>
				<span class="nameTxt"><a href="<!--{$event_a}-->">活动</a></span>
				<!--{if $page == 'create'}-->
				<span class="eventName">创建活动</span>
				<!--{elseif $page == 'edit'}-->
				<span class="eventName">编辑活动</span>
				<!--{/if}-->
			</div>

