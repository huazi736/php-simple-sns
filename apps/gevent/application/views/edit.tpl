
<link href="<!--{$smarty.const.MISC_ROOT}-->css/plug-css/calendar/dk_calendar.css" type="text/css" rel="stylesheet" />
<link href="<!--{$smarty.const.MISC_ROOT}-->css/event/event.css" type="text/css" rel="stylesheet" />
</head>
<body>


<div class="clearfix">
	<div class="mainArea">
		<div class="modlueHeader clearfix">
			<!--{include file="tpl/top_user.tpl"}-->
			<div class="userActions">
				<span class="btnGray">
					<i id="returnList"></i><a id="btnReturn" href="javascript:void(0);">关闭页面</a>
				</span>
			</div>
		</div>
		<div class="modlueBody">
		<div id="contentCol" class="clearfix home fullRight">
			<!-- start: 活动 -->
			<div id="contentArea" uid="<!--{$user.uid}-->">	
				<div id="event">
					<div id="leftColContainer">
						<div id="leftCol">
							<div id="eventImg">
								<img width="180" height="150" src="<!--{$event.img}-->" onerror="this.src='<!--{$smarty.const.MISC_ROOT}-->img/default/event.jpg'" alt="活动照片" />
							</div>
							<div class="addEventImg">
								<!--{if $event.img == '<!--{$smarty.const.MISC_ROOT}-->img/default/event.jpg'}-->
								<img width="8" height="8" alt="+" src="<!--{$smarty.const.MISC_ROOT}-->img/system/icon_plus_blue.png">
								上传活动封面
								<!--{else}-->
								<img src="<!--{$smarty.const.MISC_ROOT}-->img/system/edit_icon.gif" height="11" width="11" alt="+" />
								更改活动封面
								<!--{/if}-->
							</div>
							<div class="addEventTip">（请上传小于4MB的JPG、JPEG、PNG、GIF格式图片）</div>
						</div>
					</div>

					<div class="eventEdit">
						<span id="eventEditTip">请输入活动名称。</span>
						<form id="eventEditForm" action="<!--{$doedit_a}-->" method="post" target="nofreshFrame">
							<input type="hidden" id="eventid" name="eventid" value="<!--{$formToken}-->"/>
							<table class="eventEditTable">
								<tbody>
								  <tr class="dataRow">
									<th class="label">活动标题：</th>
									<td><input id="eventName" name="eventName" type="text" class="inputTextTitle" maxlength="45"  value="<!--{$event.name|strip}-->"/></td>
								  </tr>
								  <tr class="dataRow">
									<th class="label">开始时间：</th>
									<td>
										<input id="startDate" class="html_date" name="startDate" type="text" now="<!--{date('Y-m-d',time())}-->" value="<!--{$event.startDate}-->" />
										<div class="timeSelect ieInline zindex"> 
											<input type="hidden" value="" id="startTime" name="startTime" sel="<!--{$event.startTime}-->" />
											<div class="timeValue">
												<span><!--{$event.startTime2}--></span>
												<i></i>
												<div class="timeList hide"></div>
											</div>
										</div>
									</td>
								  </tr>
								  <tr id="endTimeRow" class="dataRow ">
									<th class="label">结束时间：</th>
									<td>
										<input id="endDate" class="html_date" name="endDate" type="text" now="<!--{date('Y-m-d',time())}-->" value="<!--{$event.endDate}-->"/>
										<div class="timeSelect ieInline">
											<input type="hidden" value="" id="endTime" name="endTime" sel="<!--{$event.endTime}-->" />
											<div class="timeValue">
												<span><!--{$event.endTime2}--></span>
												<i></i>
												<div class="timeList hide"></div>
											</div>		
										</div>
									</td>
								  </tr>
								  <tr class="dataRow">
									<th class="label">活动地点：</th>
									<td><span id="event_area" ref='<!--{$event.area}-->' ></span></td>
								  </tr>
								  <tr class="dataRow">
									<th class="label"></th>
									<td><input type="text" name="eventPlace" class="inputText" maxlength="15"  value="<!--{$event.address|strip}-->"/></td>
								  </tr>
								  <tr class="dataRow">
									<th class="label">详细内容：<br /><br /><br /><br /><br /></th>
									<td><textarea id="eventInfo" name="eventInfo" ><!--{$event.detail|strip}--></textarea></td>
								  </tr>
								  
								  <tr class="dataRow">
									<th class="label"></th>
									<td>
										<ul class="uiList">
											<li>
												<!--{if $event.is_show_users == 1}-->
												<input id="guestListSet" name="showattend" type="checkbox" checked="checked" value="1"/>
												<!--{else}-->
												<input id="guestListSet" name="showattend" type="checkbox" value="1"/>
												<!--{/if}-->

												<label for="guestListSet">在活动页显示好友名单</label>
											</li>
										</ul>
									</td>
								  </tr>
								</tbody>
								<tfoot>
								  <tr class="dataRow">
									<th class="label"></th>
									<td><label class="btnBlue"><input id="btnEventSave" name="btnEventSave" type="submit" value="保存修改"  /></label></td>
								  </tr>
								</tfoot>
							</table>
						</form>
						<div id="friends_detail">
							<div class="emailBox">
								<label for="emailInvite">发送电子邮件邀请朋友加入：</label>
								<span>多个邮件用逗号分隔开</span>
								<input id="emailInvite" name="emailInvite" />
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- end: 活动 -->
		</div>
		</div>
	</div>
</div>
<div id="haveTextarea"></div>

<script src="<!--{$smarty.const.MISC_ROOT}-->js/event/gevent.js" type="text/javascript"></script>
</body>
</html>
