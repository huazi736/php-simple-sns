<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * User Agent Class
 *
 * Identifies the platform, browser, robot, or mobile devise of the browsing agent
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	User Agent
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/user_agent.html
 */
class DK_User_agent extends CI_User_agent {
	/**
	 * Compile the User Agent Data
	 *
	 * @access	private
	 * @return	bool
	 */
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public  function _load_agent_file()
	{
		if (defined('ENVIRONMENT') AND is_file(ROOT_PATH.'config/'.ENVIRONMENT.'/user_agents.php'))
		{
			include(ROOT_PATH.'config/'.ENVIRONMENT.'/user_agents.php');
		}
		elseif (is_file(ROOT_PATH.'config/user_agents.php'))
		{
			include(ROOT_PATH.'config/user_agents.php');
		}
		else
		{
			return FALSE;
		}

		$return = FALSE;

		if (isset($platforms))
		{
			$this->platforms = $platforms;
			unset($platforms);
			$return = TRUE;
		}

		if (isset($browsers))
		{
			$this->browsers = $browsers;
			unset($browsers);
			$return = TRUE;
		}

		if (isset($mobiles))
		{
			$this->mobiles = $mobiles;
			unset($mobiles);
			$return = TRUE;
		}

		if (isset($robots))
		{
			$this->robots = $robots;
			unset($robots);
			$return = TRUE;
		}

		return $return;
	}
}


/* End of file User_agent.php */
/* Location: ./system/libraries/User_agent.php */