								<!--{foreach $rows as $row}-->
								<li eid="<!--{$row.id}-->">
									<div class="listwrap clearfix">
										<a href="<!--{$detail_a}-->&id=<!--{$row.id}-->" class="eventPic">
											<img src="<!--{$row.img}-->" width="50" height="50" onerror="this.src='<!--{$smarty.const.MISC_ROOT}-->img/default/eventList.jpg'">
										</a>

										<div class="eventSite">
										<!--{if $page != 'endlist'}-->
											<!--{if $row.type == -1}-->
											<!--{elseif $row.type == -2}-->
											<!--{elseif $row.answer == 1}-->
												<!--{$visitor.username}-->尚未答复
											<!--{elseif $row.answer == 0}-->
												<!--{$visitor.username}-->不参加
											<!--{elseif $row.answer == 2}-->
												<!--{if $row.type == 2}-->
												<!--{$visitor.username}-->是创建者
												<!--{elseif $row.type == 1}-->
												<!--{$visitor.username}-->是管理员
												<!--{else}-->
												<!--{$visitor.username}-->可能参加
												<!--{/if}-->
											<!--{elseif $row.answer == 3}-->
												<!--{if $row.type == 2}-->
												<!--{$visitor.username}-->是创建者
												<!--{elseif $row.type == 1}-->
												<!--{$visitor.username}-->是管理员
												<!--{else}-->
												<!--{$visitor.username}-->确定参加
												<!--{/if}-->
											<!--{/if}-->
										<!--{/if}-->
										</div>

										<div class="eventInfo">
											<a href="<!--{$detail_a}-->&id=<!--{$row.id}-->" class="eventname" title="<!--{$row.name}-->"><!--{$row.name|truncate:12:"...":true}--></a>
											<span class="eventtime"><!--{$row.starttime}--></span>
											<div class="eventmember">
											</div>
										</div>
									</div>
								</li>
								<!--{/foreach}-->

