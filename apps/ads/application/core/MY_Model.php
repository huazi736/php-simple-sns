<?php

/**
 * +-------------------------------
 * 模型
 * +-------------------------------
 * @author hujiashan
 * @date <2012/07/05>
 */


	//表字段
	define('AD_COMPANY',    'ad_company');
	define('AD_LIST',   'ad_list');
	define('AD_TASK', 'ad_task');
	define('AD_LOG',  'ad_log');
	define('AD_CROWD',  'ad_crowd');
	define('AD_COST',  'ad_cost');
	define('AD_PAY',  'ad_pay');
	define('AD_INVOICE',  'ad_invoice');
	define('AD_COMPANYCOST',  'ad_company_cost');	
	define('AD_CONFIG',  'ad_config');
	define('AD_PAY_LOG',  'ad_pay_log');
	define('AD_LOG_COUNT',  'ad_log_count');
	define('AD_COMPANY_COST',  'ad_company_cost');
	define('AD_NOTICE_LOG',  'ad_notice_log');
	define('MONGODB_COLLECTION_ADS','ads');
	define('AD_CUSTOM',  'ad_custom');
	define('MONGODB_COLLECTION_ADS_COUNTER','ads_counter');


class MY_Model extends DK_Model
{		
	/**
	 * 构造函数
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->init_db('ads');
	}

}

?>