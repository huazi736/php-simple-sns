
<link href="<!--{$smarty.const.MISC_ROOT}-->css/common/base.css" rel="stylesheet" type="text/css" />
<link href="<!--{$smarty.const.MISC_ROOT}-->css/reg/reg.css" rel="stylesheet" type="text/css" />
<link href="<!--{$smarty.const.MISC_ROOT}-->css/error/error.css" rel="stylesheet" type="text/css" />

<div class="body clearfix">
	<div class="mainArea">
		<div class="modlueBody">
			<div class="sorry">
				<!--{foreach from=$msg item=item}-->
				<p><!--{$item}--></p>
				<!--{/foreach}-->
				<ul>
					<li>您可以：</li>
					<li>2、返回 <a href="<!--{$url}-->">上一页</a></li>
					<li>3、返回 <a href="<!--{$smarty.const.WEB_ROOT}-->">首页</a></li>
				</ul>
			</div>
		</div>
	</div>
</div>

