<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2006 - 2012, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Migration Class
 *
 * All migrations should implement this, forces up() and down() and gives
 * access to the CI super-global.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		Reactor Engineers
 * @link
 */
class DK_Migration extends CI_Migration {
	public function __construct($config = array())
	{
		# Only run this constructor on main library load
		if (get_parent_class($this) !== FALSE)
		{
			return;
		}

		foreach ($config as $key => $val)
		{
			$this->{'_' . $key} = $val;
		}

		log_message('debug', 'Migrations class initialized');

		// Are they trying to use migrations while it is disabled?
		if ($this->_migration_enabled !== TRUE)
		{
			show_error('Migrations has been loaded but is disabled or set up incorrectly.');
		}

		// If not set, set it
		$this->_migration_path == '' OR $this->_migration_path = EXTEND_PATH . 'migrations/';

		// Add trailing slash if not set
		$this->_migration_path = rtrim($this->_migration_path, '/').'/';

		// Load migration language
		$this->lang->load('migration');

		// They'll probably be using dbforge
		$this->load->dbforge();

		// If the migrations table is missing, make it
		if ( ! $this->db->table_exists('migrations'))
		{
			$this->dbforge->add_field(array(
				'version' => array('type' => 'INT', 'constraint' => 3),
			));

			$this->dbforge->create_table('migrations', TRUE);

			$this->db->insert('migrations', array('version' => 0));
		}
	}
}

/* End of file Migration.php */
/* Location: ./system/libraries/Migration.php */