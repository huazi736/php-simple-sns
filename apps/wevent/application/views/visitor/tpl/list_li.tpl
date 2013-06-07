								<!--{foreach $rows as $row}-->
								<li eid="<!--{$row.id}-->">
									<div class="listwrap clearfix">
										<a href="<!--{$detail_a}-->&id=<!--{$row.id}-->" class="eventPic">
											<img src="<!--{$row.img}-->" width="50" height="50" onerror="this.src='<!--{$smarty.const.MISC_ROOT}-->img/default/eventList.jpg'">
										</a>

										<div class="eventSite">
										<!--{$visitor.username}-->
										<!--{if $page != 'endlist'}-->
										<!--{if $row.type == -2}-->
										<!--{elseif $row.answer == 1}-->
											尚未答复
										<!--{elseif $row.answer == 0}-->
											不参加
										<!--{elseif $row.answer == 2}-->
											<!--{if $row.type == 2}-->
											是创建人
											<!--{elseif $row.type == 1}-->
											是管理员
											<!--{else}-->
											可能参加
											<!--{/if}-->
										<!--{elseif $row.answer == 3}-->
											<!--{if $row.type == 2}-->
											是创建人
											<!--{elseif $row.type == 1}-->
											是管理员
											<!--{else}-->
											确定参加
											<!--{/if}-->
										<!--{/if}-->
										<!--{/if}-->
										</div>

										<div class="eventInfo">
											<a href="<!--{$detail_a}-->&id=<!--{$row.id}-->" class="eventname" title="<!--{$row.name}-->"><!--{$row.name|truncate:12:"..."}--></a>
											<span class="eventtime"><!--{$row.starttime}--></span>
											<div class="eventmember">
											</div>
										</div>
									</div>
								</li>
								<!--{/foreach}-->

