<!--{include file="`$smarty.const.TEMPLATE_PATH`header.html"}-->
<link href="<!--{$smarty.const.MISC_ROOT}-->css/event/stream.css" type="text/css" rel="stylesheet" />
<link href="<!--{$smarty.const.MISC_ROOT}-->css/plug-css/comment-easy/comment_easy.css" type="text/css" rel="stylesheet" />
<link href="<!--{$smarty.const.MISC_ROOT}-->css/plug-css/firends_list/friends_list.css" type="text/css" rel="stylesheet" />
<link href="<!--{$smarty.const.MISC_ROOT}-->css/event/event.css" type="text/css" rel="stylesheet" />
<link href="<!--{$smarty.const.MISC_ROOT}-->css/plug-css/JQuery-uploadify/JQuery_uploadify.css" type="text/css" rel="stylesheet"/>
</head>
<body>

<!--{include file="`$smarty.const.TEMPLATE_PATH`top_bar.html"}-->

<div class="body clearfix">
	<div class="mainArea">
		<div class="modlueHeader clearfix">
			<!--{include file="visitor/tpl/top_user.tpl"}-->
			<div class="uiToolBar fr">
				<div class="userActions">
					<span class="btnGray">
						<i id="returnList"></i><a href="<!--{$mylist_a}-->">返回列表</a>
					</span>
					<!--{if $current_user.type == -2 }-->
					<span class="btnGray">
						<a id="btnCreate" class="im_in" href="javascript:void(0);">我要参加</a>
					</span>
					<!--{/if}-->
				</div>
				<div id="friends_detail"></div>
			</div>
		</div>
		<div class="modlueBody">
			<div id="contentCol" class="clearfix home fullRight">
			<!-- start: 活动 -->
				<div id="contentArea" uid="3" tid="d89f521c-fa63-8948-dd4a-8531bd8fd818">
					<div id="eventDetail" eventid="<!--{$event.id}-->">
						<h3 class="eventname"><!--{$event.name|strip|escape:'html'}--></h3>
						<p class="subHeader">
							由
							<!--{foreach $admin_users as $key => $row}-->
								<!--{if $key == 0}-->
									<a href="<!--{$row.link}-->"><!--{$row.name|strip|escape:'html'}--></a>
								<!--{elseif $key == 1}-->
									和
									<a href="<!--{$row.link}-->"><!--{$row.name|strip|escape:'html'}--></a>
								<!--{elseif $key == 2}-->
									等
								<!--{/if}-->
							<!--{/foreach}-->
							创建
						</p>
						<ul class="uiList">
							<li class="uiListItem clearfix">
								<div class="dateStartRow fl"><i class="eventIcon iconDate"></i><span><!--{$event.starttime}--></span> 开始</div>
								<div class="dateEndRow"><i class="eventIcon iconTime"></i><span><!--{$event.endtime}--></span> 结束</div>
							</li>
							<li class="uiListItem">
								<i class="eventIcon iconPlace"></i>
								<!--{$event.address|strip|escape:'html'}-->&nbsp;
								<!--{$event.city|strip|escape:'html'}-->&nbsp;
								<!--{$event.street|strip|escape:'html'}-->&nbsp;
							</li>
							<li class="uiListItem last">
								<p><i class="eventIcon iconDesc"></i><!--{$event.detail|strip|escape:'html'}-->&nbsp;
							</li>
						</ul>

						<!-- start: streamComposer 信息发布框开始-->
						<div class="streamComposer" style="<!--{if $canReply == true}-->display:block<!--{else}-->display:none<!--{/if}-->;">
							<div id="distributeInfoBody">
								<input type="hidden" id="currentComposerAttachment" value="0" />
								<div class="showWhenLoading"></div>
								<ul id="composerAttachments" class="clearfix">
									<li class="s_msg act" ref="0"><span><i class="uiIconP icons3 bp_currentState"></i>留言</span></li>
									<li class="s_photo" ref="1"><span><i class="uiIconP icons1 bp_photo"></i>照片</span></li>
									<li class="s_video" ref="2"><span><i class="uiIconP icons1 bp_video"></i>视频</span></li>
								</ul>
								<div class="pointUp"></div>
								<div class="distributeInfoBox">
									<!-- start: distributeMsg 发表状态开始-->
									<div id="distributeMsg" class="distributeInfo">
										<textarea id="myStatusTextArea" class="shareInfoCont fieldWithText" ref="写点什么吧" maxlength="140">写点什么吧</textarea>
										<!-- start: distributeLinked 发表链接开始-->
										<div id="distributeLinked" class="hideEle">
											<div class="distributeBox">
												<div id="deleteLinkedBtn" class="png"></div>
												<div id="linkedResponseMessage">
													<div class="uiShareStage hasImages clearfix">
														<div class="uiShareStageImage">
															<div id="collectSiteImages" class="uiThumbPager">
																<div class="uiThumbPagerLoader"> <img src="<!--{$smarty.const.MISC_ROOT}-->images/plug-img/djax/loading2.gif" width="16" height="11" alt="loading" /> </div>
																<div class="uiThumbPagerThumbs" id="distributeUiThumbPagerThumbs"><!--此处动态加载采集到的图片--></div>
															</div>
														</div>
														<div class="uiShareStageContent">
															<strong class="uiShareStageTitle"><a class="inlineEdit" href="#"><!--此处动态加载采集到的网站名称--></a></strong>
															<div class="uiShareStageSubtitle"><!--此处动态加载采集到的网站地址--></div>
															<div class="uiShareStageSummary">
																<p class="uiShareStageContentText"> <a class="inlineEdit" href="#"><!--此处动态加载采集到的网站描述--></a> </p>
																<div class="uiThumbPagerControl">
																	<div id="uiThumbPagerControlButtons" class="clearfix">
																		<div class="uiThumbPagerControlButtons uiThumbPagerControlFirst"> <a class="uiThumbPagerControlButton uiThumbPagerControlButtonLeft" href="#"></a> <a class="uiThumbPagerControlButton uiThumbPagerControlButtonRight" href="#"></a> </div>
																		<div class="uiThumbPagerControlText"> <span class="uiThumbPagerControlTotalNumber">33</span>个中的第<span class="uiThumbPagerControlCurrentNumber">1</span>个<span class="tc_9">选择一个缩略图</span> </div>
																	</div>
																	<div class="uiThumbPagerControlCheckBox">
																		<input id="noPicture" type="checkbox" />
																		<label id="labelNoPicture" for="noPicture">不加图片</label>
																	</div>
																</div>
															</div>
														</div>
													</div>
													<div class="fileOption">
														<textarea id="attachLinkIntroduce" class="distributeAttachIntro fieldWithText" ref="给这条链接做些说明吧">给这条链接做些说明吧</textarea>
													</div>
												</div>
											</div>
										</div>
										<!-- end: distributeLinked 发表链接结束--> 
									</div>
									<!-- end: distributeMsg 状态结束--> 
									<!-- start: distributePhoto 发表照片开始-->
									<div id="distributePhoto" class="distributeInfo hideEle">
										<div class="distributeBox">
											<div id="photoUploadWay">
												<div class="clearfix">
													<div class="partChoice"> <a id="upoadPhotoFromLocal" class="choiceButton" href="javascript:void(0)"> <span class="choiceButtonText">上传照片</span> <span class="detailIntro">从硬盘</span> </a> </div>
													<div class="partChoice" style="display:none;"> <a id="snapshotPhoto" href="javascript:void(0)" class="choiceButton"> <span class="choiceButtonText">拍照</span> <span class="detailIntro">用网络摄像头</span> </a> </div>
												</div>
											</div>
											<div id="photoFileOption" class="fileOption">
												<iframe src="" width="0" height="0" class="hideEle" name="uploadPhotoHiddenIframe"></iframe>
												<form id="uploadPhotoForm" name="uploadPhotoForm" action="<!--{$replyImg_a}-->" method="post" target="uploadPhotoHiddenIframe" enctype="multipart/form-data">
													<input id="tokenShareDestinations" type="hidden" value="token1" name="tokenShareDestinations" />
													<div id="uploadPhotoPanel">
														<div class="uploadButtonCont">
															<input type="file" id="uploadPhotoButton" name="uploadPhotoFile" />
															<span class="blue">从你的电脑中选择一个图像文件</span>
														</div>
													</div>
													<div id="photoSnapshotPanel"> 
														<!-- <img src="<!--{$smarty.const.MISC_ROOT}-->images/common/flash_snap_demo.png" alt="flash" width="472" /> -->
														<div id="takePigPhoto"></div>
													</div>
													<textarea id="attachPhotoIntroduce" class="distributeAttachIntro fieldWithText" name="distributeAttachIntro" ref="给这张照片做些说明吧">给这张照片做些说明吧</textarea>
												</form>
											</div>
										</div>
									</div>
									<!-- end: distributePhoto 发表照片结束-->
									<!-- start: distributeVideo 发表视频开始-->
									<div id="distributeVideo" class="distributeInfo hideEle">
										<div class="distributeBox">
											<div id="videoUploadWay">
												<div class="clearfix">
													<div class="partChoice"> <a id="upoadVideoFromLocal" href="javascript:void(0)" class="choiceButton"> <span class="choiceButtonText">上传视频</span> <span class="detailIntro">从硬盘</span> </a> </div>
												</div>
											</div>
											<div id="videoFileOption" class="fileOption">
												<input id="uploadDoVideoPost" type="hidden" value="" />
												<input id="uploadedVideoId" type="hidden" value="" name="uploadedVideoId" />
												<div id="uploadVideoFlashWrap">
													<div class="flashContent">
														<input type="file" id="uploadVideoFlash"/>
														<div class="uploadVideoTxt">从你的电脑中选择一个视频文件</div>
													</div>
													<p id="up_success" class="hide">
														<span>上传成功！</span>
														<a href="javascript:void(0)">重新上传</a>
													</p>
													<div id="queueID"></div>
												</div>
												<div id="recordVideoPanel"><img src="<!--{$smarty.const.MISC_ROOT}-->images/common/flash_snap_demo.png" alt="flash" width="472" /></div>
												<textarea id="attachVideoIntroduce" class="distributeAttachIntro fieldWithText" name="attachVideoIntroduce" ref="给这段视频做些说明吧">给这段视频做些说明吧</textarea>
											</div>
										</div>
									</div>
									<!-- end: distributeVideo 发表视频结束-->
									<!-- start: footer -->
									<div class="footer"> 
										<div class="shareIt">
											<!-- start: shareDestination 选择分享对象 -->
											<div id="shareDestinationObjects" class="uiComboxHeaderGray" style="display:none;"></div>
											<!-- end: shareDestination 选择分享对象 -->
											<label class="btnBlue"><input type="button" id="distributeButton" value="发表" /></label>
										</div>
									</div>
									<!-- end: footer -->
								</div>
							</div>
						</div>
						<!-- end: streamComposer 信息发布框结束-->

						<!-- start: 信息流开始 -->
						<div id="infoArea" style="display:;">
							<div class="infoMain"><!--信息流列表--></div>
						</div>
						<!-- end: 信息流结束 -->

					</div>
					<div id="eventGuest">
						<div id="eventImg"><img src="<!--{$event.img}-->" onerror='this.src="<!--{$smarty.const.MISC_ROOT}-->img/default/event.jpg"' width="179" height="134" alt="活动照片" /></div>

						<!--{if $is_show_users == true}-->
						<div id="guest_going">
							<h3 class="clearfix"><a href="#" ref="4" class="sure">确定参加（<em><!--{$event_users_sure.num}--></em>人）</a></h3>
							<!--{include file="tpl/event_user.tpl" event_user=$event_users_sure}-->
							</ul>
							<!--{if $event_users_sure.num > 11}-->
							...
							<!--{/if}-->
						</div>
						<div id="guest_maybe">
							<h3 class="clearfix"><a href="#" ref="3">可能参加（<em><!--{$event_users_mabe.num}--></em>人）</a></h3>
							<!--{include file="tpl/event_user.tpl" event_user=$event_users_mabe}-->
							<!--{if $event_users_mabe.num > 10}-->
							...
							<!--{/if}-->
						</div>
						<div id="guest_invited">
							<h3 class="clearfix"><a href="#" ref="8">尙未答复（<em><!--{$event_users_unknown.num}--></em>人）</a></h3>
							<!--{include file="tpl/event_user.tpl" event_user=$event_users_unknown}-->
							<!--{if $event_users_unknown.num > 10}-->
							...
							<!--{/if}-->
						</div>
						<!--{/if}-->

						<div class="guestFooter">·<a href="javascript:void(0);">导出活动</a></div>
					</div>
				</div>
			<!-- end: 活动 -->
			</div>
		</div>
	</div>
</div>

<input type='hidden' id='hd_UID' value="<!--{$user.uid}-->" />
<input type='hidden' id='hd_userName' value='<!--{$user.username}-->' />
<input type='hidden' id='hd_avatar' value='<!--{$user.avatar}-->' />
<input type='hidden' id='hd_sessionid' value='<!--{$sessionid}-->' />

<!--{include file="`$smarty.const.TEMPLATE_PATH`footer.html"}-->
<script src="<!--{$smarty.const.MISC_ROOT}-->web_js/event/event.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/embed-for-flash/swfobject.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/jQuery-textAreaHeight/jQuery.textAreaHeight.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/dk-ui/dk.UICombox.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/jQuery-searcher/ViolenceSearch.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/jQuery-searcher/jQuery.searcher.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/friends_list/friends_list.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/event/scrollLoad.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/comment-easy/commentEasy.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/jQuery-uploadify/jquery.uploadify.v2.1.4.min.js"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->js/plug/ckplayer-for-flash/ckplayer.js" type="text/javascript"></script>
<script src="<!--{$smarty.const.MISC_ROOT}-->web_js/event/stream.js" type="text/javascript"></script>
</body>
</html>
