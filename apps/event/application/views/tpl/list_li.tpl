								<!--{foreach $rows as $row}-->
								<li eid="<!--{$row.id}-->">
									<div class="listwrap clearfix">
										<a href="<!--{$row.url}-->" class="eventPic">
											<img src="<!--{$row.img}-->" width="112" height="95" onerror="this.src='<!--{$smarty.const.MISC_ROOT}-->img/default/event.jpg'">
										</a>

										<div class="eventSite">
											<span class="joinNum"><!--{$row.join_num}-->人参加</span>
											<p>
	                                        
													<!--{if $page == 'mylist' }-->
													<!--{if $row.answer == 1}-->
													<!--{if $isMy}-->
													<a class="eventyes">同意</a> · <a class="eventno">不同意</a>
													<!--{else}-->
													尚未答复
													<!--{/if}-->
													<!--{elseif $row.answer == 0}-->
													不参加
													<!--{elseif $row.type == '-1'}-->
													被禁止活动
													<!--{else}-->
													确定参加
													<!--{/if}-->
	                                                <!--{/if}-->
                                            </p>
										</div>

										<div class="eventInfo">
											<a href="<!--{$row.url}-->" class="eventname" title="<!--{$row.name}-->"><!--{$row.name|truncate:36:"...":true}--></a>
											<span class="eventPlace">活动地点：<!--{$row.address}--></span>
											<span class="eventtime">活动时间：<!--{$row.starttime}--> － <!--{$row.endtime}--></span>
											<span class="evenStatus">
                                            <!--{if $visitor==''}-->我<!--{else}-->他<!--{/if}-->的状态：
                                            <!--{if $row.event_type == 1}-->
											<!--{if $row.answer == 1}-->
												被邀请
											<!--{elseif $row.answer == 2}-->
												<!--{if $row.type == 1}-->
												管理员
                                                 <!--{elseif $row.type == 2}-->
												创建人
												<!--{else}-->
												普通用户
												<!--{/if}-->
											<!--{/if}-->
										<!--{/if}-->
											</span>
										</div>
									</div>
								</li>
								<!--{/foreach}-->

