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
 * Output Class
 *
 * Responsible for sending final output to browser
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Output
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/output.html
 */
class DK_Output extends CI_Output {
	/**
	 * Constructor
	 *
	 */
	function __construct()
	{
		$this->_zlib_oc = @ini_get('zlib.output_compression');

		// Get mime types for later
		if (defined('ENVIRONMENT') AND file_exists(ROOT_PATH.'config/'.ENVIRONMENT.'/mimes.php'))
		{
		    include ROOT_PATH.'config/'.ENVIRONMENT.'/mimes.php';
		}
		else
		{
			include ROOT_PATH.'config/mimes.php';
		}


		$this->mime_types = $mimes;

		log_message('debug', "Output Class Initialized");
	}
}
// END Output Class

/* End of file Output.php */
/* Location: ./system/core/Output.php */