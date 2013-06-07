<?php

class DK_SearchUtil {

    public function sayhello() {
        return 'hello';
    }

    static $pattern = "\\\\?\\*\\-\\+\\s\\(\\)\\[\\]\\{\\}!\\^~\"\\&\\|:!";
    static $char = array('?', '*', '~', '^', '+', '-', '(', ')', '!', '[', ']', ':', '{', '}', '"');
    static $transfer = array('\?', '\*', '\~', '\^', '\+', '\-', '\(', '\)', '\!', '\[', '\]', '\:', '\{', '\}', '\"');

    public function addWeightByField($name, $keyword, $has_prefix= true) {
        $str = '';
        $explode = preg_match("/\\s/", $keyword) ? explode(' ', $keyword) : false;
        $indent = str_replace(' ', '', $keyword);
        $filter = str_replace('\\', '\\\\', $keyword);
        $match = str_replace(' ', '*', $keyword);
        $match = str_replace(self::$char, self::$transfer, $filter);
        $indent = str_replace(self::$char, self::$transfer, $indent);
        $filter = str_replace(self::$char, self::$transfer, $filter);

        if ($explode !== false) {
            $str.=$name . ':' . $indent;
            $str.=' OR ' . $name . ':"' . $filter . '"';
            if (self::canMatchPrefixStar($indent))
                $str.=' OR ' . $name . ':*' . $match . '*';

            foreach ($explode as $key => $val) {
                if (trim($val) == "")
                    continue;
                $both = '';
                $val = str_replace('\\', '\\\\', $val);
                $val = str_replace(self::$char, self::$transfer, $val);
                if (self::canMatchPrefixStar($val) && $has_prefix)
                    $both = ' OR ' . $name . ':*' . $val . '*';
                $str.=' OR ' . $name . ':' . $val . '*' . $both;
            }
        }else {
            $str.=$name . ':' . $filter;
            $str.=' OR ' . $name . ':' . $filter . '*';
            if (self::canMatchPrefixStar($filter) && $has_prefix)
                $str.=' OR ' . $name . ':*' . $filter . '*';
        }
        return '(' . $str . ')';
    }

    public function canMatchPrefixStar($keyword) {
        $bool = preg_match("/\\?|\\*/", $keyword);
        return !$bool;
    }

    public function htmlChars($html, $single = true) {
        $html = str_replace('&gt;', '>', $html);
        $html = str_replace('&lt;', '<', $html);
        $html = str_replace('&quot;', '"', $html);
        $html = str_replace('&amp;', '&', $html);
        if ($single)
            $html = str_replace("&#039;", "'", $html);
        return $html;
    }

    // ####################################################

    public function extractPlainText($content) {
        $pattern = '<\\s*([a-z]).*>(.*)<\\s*\/\\s*\\1\\s*>';
        $content = preg_replace('/' . $pattern . '/iUs', '\\2', $content);
        $pattern = '<\\s*[a-z].*\/\\s*>';
        $content = preg_replace('/' . $pattern . '/iUs', '', $content);
        return $content;
    }

    public function formatObject($result) {
        $response = array();
        if ($result) {
            $response['total'] = $result->response->numFound;
            $response['object'] = $result->response->docs;
        } else {
            $response['total'] = 0;
        }
        return $response;
    }

    /**
     * 获取问答的可选项
     * @param int $poll_id 问答ID
     */
    public function getAskOptionalText($options) {
        $array_list = array();
        foreach ($options as $key => $val) {
            $option = json_encode(array('option_message' => stripslashes($val['message']), 'option_votes' => $val['votes']));
            array_push($array_list, $option);
        }
        return $array_list;
    }

}
