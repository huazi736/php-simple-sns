<!--{extends file='base_layout.html'}-->
<!--{block name='title' prepend}-->
活动-
<!--{/block}-->
<!--{block name='header_js'}-->
<link href="<!--{$smarty.const.MISC_ROOT}-->css/plug-css/calendar/dk_calendar.css" type="text/css" rel="stylesheet" />
<link href="<!--{$smarty.const.MISC_ROOT}-->css/event/event.css" type="text/css" rel="stylesheet" />
<!--{/block}-->
<!--{block name='body'}-->
<div class="body clearfix">
	<div class="mainArea">
		<div class="modlueHeader clearfix">
			<!--{include file="tpl/top_user.tpl"}-->
			<div class="userActions">
				<span class="btnGray">
					<i id="returnList"></i><a id="btnReturn" href="javascript:void(0);" ref="<!--{$mylist_a}-->">返回列表</a>
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
						<form id="eventEditForm" action="<!--{$doedit_a}-->" method="post">
							<input type="hidden" id="eventid" name="eventid" value="<!--{$formToken}-->"/>
							<table class="eventEditTable">
								<tbody>
								  <tr class="dataRow">
									<th class="label">活动标题：</th>
									<td><input id="eventName" name="eventName" type="text" class="inputTextTitle" maxlength="45"  value="<!--{$event.name}-->"/></td>
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
									<td><span id="event_area" ref='<!--{$event.area}-->'></span></td>
								  </tr>
								  <tr class="dataRow">
									<th class="label"></th>
									<td><input type="text" name="eventPlace" class="inputText" maxlength="15"  value="<!--{$event.address}-->"/></td>
								  </tr>
								  <tr class="dataRow">
									<th class="label">详细内容：<br /><br /><br /><br /><br /></th>
									<td><textarea id="eventInfo" name="eventInfo" ><!--{$event.detail}--></textarea></td>
								  </tr>
								  <tr class="dataRow">
									<th class="label">管理员：</th>
									<td>
										<div class="receivePeopleWrap" id="addAdminWrap">
											<div class="tokenarea" id="tokenarea">
												<i id="adminList">
												<!--{foreach $admins as $admin}-->
													<!--{if $admin.type == 2}-->
														<span rel="<!--{$admin.id}-->"><!--{$admin.name}--></span>
													<!--{else}-->
														<span rel="<!--{$admin.id}-->"><!--{$admin.name}--><a class="deleteToken" href="javascript:void(0)"></a></span>
													<!--{/if}-->
												<!--{/foreach}-->
												</i>
												<input class="toPeopleInput" id="addAdmin" type="text" value="" />
												<input class="hideEle" id="addAdmin2" name="addAdmin2" value="" />
											</div>
											<div class="compactPeople" id="compactAdmin">
												<ul></ul>
											</div>
										</div>
										<div class="btmTip"><span class="red">*</span>（只有当对方处于确定参加活动的情况下添加管理员有效。）</div>
									</td>
								  </tr>
								  <tr class="dataRow">
									<th class="label">邀请粉丝：</th>
									<td><a class="uiToolbarItem uiButton" id="inviteGuest" href="#">选择粉丝</a></td>
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
												<label for="guestListSet">公开活动名单</label>
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
<!--{/block}-->
<!--{block name='footer_js'}-->
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/calendar/dk_calendar.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/area-utils/area_utils.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/jQuery-textAreaHeight/jQuery.textAreaHeight.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/dk-ui/dk.UICombox.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/jQuery-searcher/ViolenceSearch.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/jQuery-searcher/jQuery.searcher.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/friends_list/friends_list.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/event/event.js" type="text/javascript"></script>
<!--{/block}-->
