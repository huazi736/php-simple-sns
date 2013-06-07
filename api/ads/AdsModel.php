<?php

/**
 * 
 * 广告充值
 * @author hujiashan
 *
 */
class AdsModel extends DkModel {
    const TABLE_PAY = 'ad_pay';
    const TABLE_COMPANY = 'ad_company';
    const TABLE_COMPANY_COST= 'ad_company_cost';
    const TABLE_NOTICE_LOG= 'ad_notice_log';
    const TABLE_ADLIST= 'ad_list';
    const TABLE_ADCOST= 'ad_cost';
    const TABLE_ADPAYASSIGN= 'ad_pay_assign';
    const TABLE_ADLOG= 'ad_log';

    public function __initialize() {
        $this->init_db('ads');
    }

    /**
     * 
     * 充值接口
     * @author hujiashan
     * @date 2012-07-07
     * @param array $data
     * $data = array(
     * 		  'cid' => $cid 广告商ID 
     * 		  'orderid' => $orderid   交易号
     * 		  'uid' => 	$uid , 用户ID
     * 		  'type'    =>  $type, 分类（网银 1 支付宝 2 其他 3
     *        'content' =>  $content 交易内容
     *        'descriiption'  => $descriiption 描述
     * 		  'money'    =>  $money, 金额
     *        'state'    =>  $state, 充值状态(0未支付 1成功 3出错
     *        'dateline'    =>  $dateline, 充值时间
     * )
     */
    public function paid($data = array()) {

        $result = $this->get($data['uid'], 'id');
        if (!$result) {
            return FALSE;
        }
        $data['cid'] = $result['id'];
        $this->db->insert(self::TABLE_PAY, $data);
        return ($this->db->affected_rows() > 0) ? TRUE : FALSE;
    }

    /**
     * 
     * 查看广告商信息
     * @author hujiashan
     * @date 2012-07-07
     * @param int $uid
     * @param string $field
     */
    function get($uid = NULL, $field = ' * ') {
        $uid = intval($uid);
        if (!$uid) {
            return false;
        }
        $this->db->select($field);
        $this->db->where('uid', $uid);
        $query = $this->db->get(self::TABLE_COMPANY);
        if ($query->num_rows() > 0) {
            return $query->row_array();
        }
        return FALSE;
    }

    /**
     * 
     * 删除充值记录
     * @author hujiashan
     * @date  2012-07-13
     * @param string $orderid
     * @param int $uid
     */
    function delete($orderid = NULL, $uid = NULL) {
        $uid = intval($uid);
        if (empty($orderid) || empty($uid)) {
            return FALSE;
        }

        $this->db->where_in('orderid', $orderid);
        $this->db->where_in('uid', $uid);
        $this->db->delete(self::TABLE_PAY);

        return ($this->db->affected_rows() > 0) ? TRUE : FALSE;
    }

    /**
     * 
     * 通过 交易号获取广告ID
     * @param string $orderid
     */
    function get_ad_id($orderid = NULL, $field = ' * ', $where = NULL) {
        if (!$orderid) {
            return FALSE;
        }

        $this->db->select($field);
        $this->db->where('orderid', $orderid);
        if (!empty($where)) {
            if (is_array($where)) {

                foreach ($where as $key => $val) {
                    $this->db->where($key, $val);
                }
            } else {
                $this->db->where($where);
            }
        }
        $query = $this->db->get(self::TABLE_PAY);
        if ($query->num_rows() > 0) {
            return $query->row_array();
        }
        return FALSE;
    }

    public function pay($data = array()) {

        $result = $this->get($data['uid'], 'id');
        if (!$result) {
            return FALSE;
        }

        //判断订单是否已处理
        $re = $this->get_ad_id($data['orderid'], 'pay_id', array('tradeid' => $data['tradeid']));
        if ($re) {
            return TRUE;
        }

        $this->db->trans_start();

        $data['cid'] = $result['id'];
        $this->db->insert(self::TABLE_PAY, $data);

        if ($this->db->affected_rows() > 0) {
            if ($data['status'] == 1) {
                //更新广告商充值总金额
                $this->db->where('cid', $data['cid']);
                $this->db->set('all_money', 'all_money+' . $data['money'], FALSE);
                $this->db->update(self::TABLE_COMPANY_COST);

                $this->db->trans_complete();

                //加入通知提醒
                $notarr = array(
                    'cid' => $data['cid'],
                    'content' => '您已成功充值"' . $data['money'] . '"元',
                    'dateline' => time()
                );
                $this->db->insert(self::TABLE_NOTICE_LOG, $notarr);

                return TRUE;
            } else {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * @author: qianc
     * @date: 2012-7-28
     * @desc: 获取广告列表
     * @access public
     * @return array
     */
    function getAds($nowpage =0, $limit=8, $where, $orderby) {
        $sql = "SELECT t1.*,t2.budget,t2.budget_sort,t2.bid,t2.charge_mode,t2.cost_money FROM " . self::TABLE_ADLIST . " t1 LEFT JOIN " . self::TABLE_ADCOST . " t2 
		on t1.ad_id = t2.ad_id WHERE " . $where;
        $nums = $this->db->query($sql)->num_rows();

        if ($nums) {

            $sql.=" ORDER BY " . $orderby . " DESC LIMIT " . $nowpage . " , " . $limit;
            $data = $this->db->query($sql)->result_array();
            foreach ($data as $k => $v) {
                switch ($v['sort']) {
                    case '1':
                        $data[$k]['str_status'] = '已暂停';
                        break;
                    case '3':
                        $data[$k]['str_status'] = '进行中';
                        break;
                }

                switch ($v['is_checked']) {
                    case '-1':
                        $data[$k]['str_checked'] = '未审核';
                        break;
                    case '1':
                        $data[$k]['str_checked'] = '不通过';
                        break;
                    case '3':
                        $data[$k]['str_checked'] = '通过';
                        break;
                }

                switch ($v['budget_sort']) {
                    case '0':
                        $data[$k]['str_budget_sort'] = '每日预算';
                        break;
                    case '3':
                        $data[$k]['str_budget_sort'] = '总预算';
                        break;
                }
                $data[$k]['start_time'] = date("Y-m-d", $data[$k]['start_time']);
                $data[$k]['budget_format'] = number_format($data[$k]['budget'], 2, '.', ' '); //预算
                $data[$k]['cost_money_format'] = number_format($data[$k]['cost_money'], 2, '.', ' '); //花费    
                $data[$k]['surplus_format'] = number_format($data[$k]['budget'] - $data[$k]['cost_money'], 2, '.', ' '); //结余
                $data[$k]['bid_format'] = number_format($data[$k]['bid'], 2, '.', ' '); //竞价 		     		 		   		
            }
            return array('nums' => $nums, 'data' => $data);
        }

        return FALSE;
    }

    /**
     * 	取得广告详情
     *  @author	    qianc
     * 	@date	    2012/7/30
     * @return		array
     */
    function getAdInfo($where) {
        if (!$where) {
            return FALSE;
        }
        $sql = "SELECT * FROM  " . self::TABLE_ADLIST . " WHERE " . $where;
        $data = $this->db->query($sql)->result_array();

        if ($data) {
            foreach ($data as $k => $v) {
                switch ($v['sort']) {
                    case '1':
                        $data[$k]['str_status'] = '已暂停';
                        break;
                    case '3':
                        $data[$k]['str_status'] = '进行中';
                        break;
                }
                $data[$k]['start_time'] = date("Y-m-d", $data[$k]['start_time']);
                if ($data[$k]['end_time']) {
                    $data[$k]['end_time'] = date("Y-m-d", $data[$k]['end_time']);
                } else {
                    $data[$k]['end_time'] = '';
                }
            }
            return $data;
        }
        return FALSE;
    }

    /**
     * 	取得随机广告
     *  @author	    qianc
     * 	@date	    2012/7/30
     * @return		array
     */
    function getAdRandom($where, $num) {
        if (!$where) {
            return FALSE;
        }
        $sql = "SELECT * FROM  " . self::TABLE_ADLIST . " AS a LEFT JOIN (SELECT MAX(ad_id) as adid FROM " . self::TABLE_ADLIST . ") AS b ON (a.ad_id >= floor(b.adid * rand())) WHERE " . $where . " LIMIT " . $num;

        $data = $this->db->query($sql)->result_array();
        if ($data) {

            foreach ($data as $k => $v) {
                switch ($v['sort']) {
                    case '1':
                        $data[$k]['str_status'] = '已暂停';
                        break;
                    case '3':
                        $data[$k]['str_status'] = '进行中';
                        break;
                }
                $data[$k]['start_time'] = date("Y-m-d", $data[$k]['start_time']);
                if ($data[$k]['end_time']) {
                    $data[$k]['end_time'] = date("Y-m-d", $data[$k]['end_time']);
                } else {
                    $data[$k]['end_time'] = '';
                }
            }
            return $data;
        }

        return FALSE;
    }

    /**
     * 	取得广告分成相关数据
     *  @author	    qianc
     * 	@date	    2012/8/6
     * @return		array
     */
    function getAdPayAssign($dkcode) {
        if (!$dkcode) {
            return FALSE;
        }


        $sql = "SELECT ad_id FROM " . self::TABLE_ADPAYASSIGN . "  WHERE dkcode = " . $dkcode . " GROUP BY ad_id";
        $adidRs = $this->db->query($sql)->result_array();

        if ($adidRs) {
            $data_show = array();
            $data_click = array();
            foreach ($adidRs as $k => $v) {
                $sql = "SELECT a.ad_id, a.title,SUM(b.p_money) p_money_num, COUNT(c.id) show_count_num FROM " . self::TABLE_ADLIST . " a
				LEFT JOIN " . self::TABLE_ADPAYASSIGN . " AS b ON a.ad_id = b.ad_id
				LEFT JOIN " . self::TABLE_ADLOG . " AS c ON a.ad_id = c.ad_id				
				WHERE a.ad_id = " . $v['ad_id'] . " AND c.event_type = 1 AND c.type = 2 AND c.is_valid = 0 AND c.typeid = " . $dkcode . "
				GROUP BY a.ad_id ORDER BY b.dateline DESC";
                $data_show[] = $this->db->query($sql)->result_array();
            }
            foreach ($adidRs as $k => $v) {
                $sql = "SELECT a.ad_id, a.title,SUM(b.p_money) p_money_num, COUNT(c.id) click_count_num FROM " . self::TABLE_ADLIST . " a
				LEFT JOIN " . self::TABLE_ADPAYASSIGN . " AS b ON a.ad_id = b.ad_id
				LEFT JOIN " . self::TABLE_ADLOG . " AS c ON a.ad_id = c.ad_id				
				WHERE a.ad_id = " . $v['ad_id'] . " AND c.event_type = 2 AND c.type = 2 AND c.is_valid = 0 AND c.typeid = " . $dkcode . "
				GROUP BY a.ad_id ORDER BY b.dateline DESC";
                $data_click[] = $this->db->query($sql)->result_array();
            }
            if (empty($data_click[0])) {
                $data_click_new = array();
                foreach ($data_click as $k => $v) {
                    if (isset($v[0])) {
                        $data_click_new[$v[0]['ad_id']] = $v;
                    }
                }
                foreach ($data_show as $k => $v) {
                    $data_show_arr[$k] = $v[0];
                    $data_show_arr[$k]['click_count_num'] = isset($data_click_new[$v[0]['ad_id']][0]['click_count_num']) ? $data_click_new[$v[0]['ad_id']][0]['click_count_num'] : 0;
                }
                return $data_show_arr;
            } elseif (empty($data_show[0])) {
                $data_show_new = array();
                foreach ($data_show as $k => $v) {
                    if (isset($v[0])) {
                        $data_show_new[$v[0]['ad_id']] = $v;
                    }
                }
                foreach ($data_click as $k => $v) {
                    $data_click_arr[$k] = $v[0];
                    $data_click_arr[$k][0]['show_count_num'] = isset($data_show_new[$v[0]['ad_id']][0]['show_count_num']) ? $data_show_new[$v[0]['ad_id']][0]['show_count_num'] : 0;
                }
                return $data_click_arr;
            }
            return FALSE;
        }
        return FALSE;
    }

}

?>