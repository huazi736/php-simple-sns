<?php

class TheBlogModel extends DkModel {

    public function __initialize() {
        $this->init_db('blog');
    }

    public function delBlog($blogId) {
        $this->db->where('id', $blogId);
        $res = $this->db->update('blog', array('status' => '0'));
        return $res;
    }

}