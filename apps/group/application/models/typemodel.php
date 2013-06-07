<?php
/*
 * 群组
 * title :
 * Created on 2012-05-28
 * @author hexin
 * discription : 群组分类数据
 */
class TypeModel extends MY_Model
{
	public function getAll()
	{
		return array(
			GroupConst::GROUP_TYPE_FRIEND 	 => '好友',
			GroupConst::GROUP_TYPE_ATTENTION => '关注',
			GroupConst::GROUP_TYPE_FANS 	 => '粉丝',
			GroupConst::GROUP_TYPE_CLASSMATE => '同学',
			GroupConst::GROUP_TYPE_COLLEAGUE => '同事',
            GroupConst::GROUP_TYPE_PEER => '同行',
            GroupConst::GROUP_TYPE_RELATIVE => '亲人',
			GroupConst::GROUP_TYPE_CUSTOM => '自定义',
		);
	}
}