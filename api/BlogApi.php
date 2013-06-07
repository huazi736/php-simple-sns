<?php

class BlogApi extends DkApi {

    /**
     * 删除日志
     * @author yinyancai
     * @date 2012/08/07
     * @param  $blogId   	日志ID
     */
    public function delBlog($blogId = FALSE) {
        if ($blogId) {
            $theBlogModel = DKBase::import('TheBlog', 'app');
            return $theBlogModel->delBlog($blogId);
        }
        return false;
    }

}