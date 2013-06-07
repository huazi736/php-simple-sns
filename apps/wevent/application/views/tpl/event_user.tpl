							<ul class="uiList clearfix">
							<!--{foreach $event_user.type1 as $e_user}-->
								<li>
									<a href="<!--{$e_user.link}-->">
										<img src="<!--{$e_user.avatar}-->" onerror='this.src="<!--{$smarty.const.MISC_ROOT}-->img/default/avatar_30.gif"' width="32" height="32" />
									</a>
									<span><a href="<!--{$e_user.link}-->"><!--{$e_user.name|strip|truncate:8:"..."|escape:'html'}--></a>
									</span>
								</li>
							<!--{/foreach}-->
							<!--{if $event_user.online == true}-->
								<li class="oneLineList">
									<ul class="clearfix">
									<!--{foreach $event_user.type2 as $e_user}-->
										<li>
											
											<a href="<!--{$e_user.link}-->" title="<!--{$e_user.name}-->">
											
												<img src="<!--{$e_user.avatar}-->" onerror='this.src="<!--{$smarty.const.MISC_ROOT}-->img/default/avatar_30.gif"' width="32" height="32" />
											</a>
										</li>
									<!--{/foreach}-->
									</ul>
								</li>
							<!--{/if}-->
							</ul>

