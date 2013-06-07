<!--{foreach $rows as $row}-->
<li>
    <div class="groupEventInfo clearfix">
        <a href="#" class="groupEventImg"><img src="<!--{$row.img}-->" /></a>
        <dl class="groupEventTxt">
            <dt>
                <a href="<!--{$row.user_url}-->"><!--{$row.user_name}--></a> 发布活动 <a class="eventLink" href="javascript:void(0);" ref="<!--{$row.url}-->"><!--{$row.name}--></a>
                <i></i>
            </dt>
            <dd>活动地点: <!--{$row.address}--></dd>
            <dd>活动时间: <!--{$row.starttime}-->——<!--{$row.endtime}--></dd>
            <dd>活动人数: <!--{$row.join_num}-->&#12288活动状态: <!--{$row.status}--></dd>
        </dl>
    </div>
</li>
<!--{/foreach}-->

