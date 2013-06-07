<link href="<?=MISC_ROOT?>css/common/base.css" rel="stylesheet" type="text/css" />
<link href="<?=MISC_ROOT?>css/reg/reg.css" rel="stylesheet" type="text/css" />
<link href="<?=MISC_ROOT?>css/error/error.css" rel="stylesheet" type="text/css" />
		<div class="modlueBody">
			<div class="sorry">
				<? foreach ($this->msg as $item) : ?>
				<p><?=$item?></p>
				<? endforeach; ?>
				<ul>
					<li>您可以：</li>
					<? if ($this->url) : ?>
					<li>返回 <a href="<?=$this->url?>">上一页</a></li>
					<li>返回 <a href="<?=WEB_ROOT?>">首页</a></li>
					<? else: ?>
					<li>返回 <a href="<?=WEB_ROOT?>">首页</a></li>
					<? endif; ?>
				</ul>
			</div>
		</div>

