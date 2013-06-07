								<!--{foreach $rows as $row}-->
								<li eid="<!--{$row.id}-->">
									<div class="listwrap clearfix">
										<a href="<!--{$detail_a}-->&id=<!--{$row.id}-->" class="eventPic">
											<img src="<!--{$row.img}-->" width="112" height="95" onerror="this.src='<!--{$smarty.const.MISC_ROOT}-->img/default/event.jpg'">
										</a>

										<div class="eventSite">
											<span class="joinNum"><!--{$row.join_num}-->人参加</span>
											<p></p>
										</div>

										<div class="eventInfo">
											<a href="<!--{$detail_a}-->&id=<!--{$row.id}-->" class="eventname" title="<!--{$row.name}-->"><!--{$row.name|truncate:12:"...":true}--></a>
											<span class="eventPlace">活动地点：<!--{$row.address}--></span>
											<span class="eventtime">活动时间：<!--{$row.starttime}--> － <!--{$row.endtime}--></span>
											<span class="evenStatus">我的状态：
                                            <!--{if  $is_create  == true }-->
                                            创建人
                                            <!--{elseif $row.answer == 2 }-->
                                            参加
                                            <!--{elseif $row.answer == -1 }-->
                                            未参加
                                            <!--{elseif $row.answer == 0 }-->
                                            不参加       
                                            <!--{/if}-->
                                            </span>
										</div>
									</div>
								</li>
								<!--{/foreach}-->

