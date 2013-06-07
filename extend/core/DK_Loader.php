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
 * Loader Class
 *
 * Loads views and files
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @author		ExpressionEngine Dev Team
 * @category	Loader
 * @link		http://codeigniter.com/user_guide/libraries/loader.html
 */
class DK_Loader extends CI_Loader {

	/**
	 * Constructor
	 *
	 * Sets the path to the view files and gets the initial output buffering level
	 */
	public function __construct()
	{
		$this->_ci_ob_level  = ob_get_level();
		$this->_ci_library_paths = array(APPPATH, EXTEND_PATH, BASEPATH);
		$this->_ci_helper_paths = array(APPPATH, EXTEND_PATH, BASEPATH);
		$this->_ci_model_paths = array(APPPATH, EXTEND_PATH);
		$this->_ci_view_paths = array(APPPATH.'views/'	=> TRUE);

		log_message('debug', "Loader Class Initialized");
	}
	
	// --------------------------------------------------------------------

	/**
	 * Initialize the Loader
	 *
	 * This method is called once in CI_Controller.
	 *
	 * @param 	array
	 * @return 	object
	 */
	public function initialize()
	{
		$this->_ci_classes = array();
		$this->_ci_loaded_files = array();
		$this->_ci_models = array();
		$this->_base_classes = is_loaded();

		$this->_ci_autoloader();

		return $this;
	}
    
    // --------------------------------------------------------------------

    /**
	 * Autoloader
	 *
	 * The config/autoload.php file contains an array that permits sub-systems,
	 * libraries, and helpers to be loaded automatically.
	 *
	 * @param	array
	 * @return	void
	 */
	protected function _ci_autoloader()
	{
		if (defined('ENVIRONMENT') AND file_exists(ROOT_PATH.'config/'.ENVIRONMENT.'/autoload.php'))
		{
			include(ROOT_PATH.'config/'.ENVIRONMENT.'/autoload.php');
		}
		else
		{
			include(ROOT_PATH.'config/autoload.php');
		}

		if ( ! isset($autoload))
		{
			return FALSE;
		}

		// Autoload packages
		if (isset($autoload['packages']))
		{
			foreach ($autoload['packages'] as $package_path)
			{
				$this->add_package_path($package_path);
			}
		}

		// Load any custom config file
		if (count($autoload['config']) > 0)
		{
			$CI =& get_instance();
			foreach ($autoload['config'] as $key => $val)
			{
				$CI->config->load($val);
			}
		}

		// Autoload helpers and languages
		foreach (array('helper', 'language') as $type)
		{
			if (isset($autoload[$type]) AND count($autoload[$type]) > 0)
			{
				$this->$type($autoload[$type]);
			}
		}

		// A little tweak to remain backward compatible
		// The $autoload['core'] item was deprecated
		if ( ! isset($autoload['libraries']) AND isset($autoload['core']))
		{
			$autoload['libraries'] = $autoload['core'];
		}

		// Load libraries
		if (isset($autoload['libraries']) AND count($autoload['libraries']) > 0)
		{
			// Load the database driver.
			if (in_array('database', $autoload['libraries']))
			{
				$this->database();
				$autoload['libraries'] = array_diff($autoload['libraries'], array('database'));
			}

			// Load all other libraries
			foreach ($autoload['libraries'] as $item)
			{
				$this->library($item);
			}
		}

		// Autoload models
		if (isset($autoload['model']))
		{
			$this->model($autoload['model']);
		}
	}
    
    // --------------------------------------------------------------------
    
	/**
	 * Load Helper
	 *
	 * This function loads the specified helper file.
	 *
	 * @param	mixed
	 * @return	void
	 */
	public function helper($helpers = array())
	{
		foreach ($this->_ci_prep_filename($helpers, '_helper') as $helper)
		{
			if (isset($this->_ci_helpers[$helper]))
			{
				continue;
			}

			$ext_helper = APPPATH.'helpers/'.config_item('subclass_prefix').$helper.'.php';

			// Is this a helper extension request?
			if (file_exists($ext_helper))
			{
                $extend_helper = EXTEND_PATH.'helpers/'.config_item('extend_subclass_prefix').$helper.'.php';
				$base_helper = BASEPATH.'helpers/'.$helper.'.php';

				if ( ! file_exists($base_helper))
				{
					show_error('Unable to load the requested file: helpers/'.$helper.'.php');
				}

				include_once($ext_helper);
                
                if (file_exists($extend_helper))
				{
					include_once($extend_helper);
				}
                
				include_once($base_helper);

				$this->_ci_helpers[$helper] = TRUE;
				log_message('debug', 'Helper loaded: '.$helper);
				continue;
			}

			// Try to load the helper
			foreach ($this->_ci_helper_paths as $path)
			{
				if (file_exists($path.'helpers/'.$helper.'.php'))
				{
					include_once($path.'helpers/'.$helper.'.php');

					$this->_ci_helpers[$helper] = TRUE;
					log_message('debug', 'Helper loaded: '.$helper);
					break;
				}
			}

			// unable to load the helper
			if ( ! isset($this->_ci_helpers[$helper]))
			{
				show_error('Unable to load the requested file: helpers/'.$helper.'.php');
			}
		}
	}

    // --------------------------------------------------------------------
    
	/**
	 * Remove Package Path
	 *
	 * Remove a path from the library, model, and helper path arrays if it exists
	 * If no path is provided, the most recently added path is removed.
	 *
	 * @param	type
	 * @param 	bool
	 * @return	type
	 */
	public function remove_package_path($path = '', $remove_config_path = TRUE)
	{
		$config =& $this->_ci_get_component('config');

		if ($path == '')
		{
			$void = array_shift($this->_ci_library_paths);
			$void = array_shift($this->_ci_model_paths);
			$void = array_shift($this->_ci_helper_paths);
			$void = array_shift($this->_ci_view_paths);
			$void = array_shift($config->_config_paths);
		}
		else
		{
			$path = rtrim($path, '/').'/';
			foreach (array('_ci_library_paths', '_ci_model_paths', '_ci_helper_paths') as $var)
			{
				if (($key = array_search($path, $this->{$var})) !== FALSE)
				{
					unset($this->{$var}[$key]);
				}
			}

			if (isset($this->_ci_view_paths[$path.'views/']))
			{
				unset($this->_ci_view_paths[$path.'views/']);
			}

			if (($key = array_search($path, $config->_config_paths)) !== FALSE)
			{
				unset($config->_config_paths[$key]);
			}
		}

		// make sure the application default paths are still in the array
		$this->_ci_library_paths = array_unique(array_merge($this->_ci_library_paths, array(APPPATH, EXTEND_PATH, BASEPATH)));
		$this->_ci_helper_paths = array_unique(array_merge($this->_ci_helper_paths, array(APPPATH, EXTEND_PATH, BASEPATH)));
		$this->_ci_model_paths = array_unique(array_merge($this->_ci_model_paths, array(APPPATH, EXTEND_PATH)));
		$this->_ci_view_paths = array_merge($this->_ci_view_paths, array(APPPATH.'views/' => TRUE));
		$config->_config_paths = array_unique(array_merge($config->_config_paths, array(ROOT_PATH)));
	}
    
    // --------------------------------------------------------------------

	/**
	 * Load class
	 *
	 * This function loads the requested class.
	 *
	 * @param	string	the item that is being loaded
	 * @param	mixed	any additional parameters
	 * @param	string	an optional object name
	 * @return	void
	 */
	protected function _ci_load_class($class, $params = NULL, $object_name = NULL)
	{
		// Get the class name, and while we're at it trim any slashes.
		// The directory path can be included as part of the class name,
		// but we don't want a leading slash
		$class = str_replace('.php', '', trim($class, '/'));

		// Was the path included with the class name?
		// We look for a slash to determine this
		$subdir = '';
		if (($last_slash = strrpos($class, '/')) !== FALSE)
		{
			// Extract the path
			$subdir = substr($class, 0, $last_slash + 1);

			// Get the filename from the path
			$class = substr($class, $last_slash + 1);
		}

		// We'll test for both lowercase and capitalized versions of the file name
		foreach (array(ucfirst($class), strtolower($class)) as $class)
		{
			$subclass = APPPATH.'libraries/'.$subdir.config_item('subclass_prefix').$class.'.php';

			// Is this a class extension request?
			if (file_exists($subclass))
			{
                $extendclass = EXTEND_PATH.'libraries/'.$subdir.config_item('extend_subclass_prefix').$class.'.php';
				$baseclass = BASEPATH.'libraries/'.ucfirst($class).'.php';

				if ( ! file_exists($baseclass))
				{
					log_message('error', "Unable to load the requested class: ".$class);
					show_error("Unable to load the requested class: ".$class);
				}

				// Safety:  Was the class already loaded by a previous call?
				if (in_array($subclass, $this->_ci_loaded_files))
				{
					// Before we deem this to be a duplicate request, let's see
					// if a custom object name is being supplied.  If so, we'll
					// return a new instance of the object
					if ( ! is_null($object_name))
					{
						$CI =& get_instance();
						if ( ! isset($CI->$object_name))
						{
							return $this->_ci_init_class($class, config_item('subclass_prefix'), $params, $object_name);
						}
					}

					$is_duplicate = TRUE;
					log_message('debug', $class." class already loaded. Second attempt ignored.");
					return;
				}

				include_once($baseclass);
                if (file_exists($extendclass))
				{
					include_once($extendclass);
				}
				include_once($subclass);
				$this->_ci_loaded_files[] = $subclass;

				return $this->_ci_init_class($class, config_item('subclass_prefix'), $params, $object_name);
			}

            $subclass = EXTEND_PATH.'libraries/'.$subdir.config_item('extend_subclass_prefix').$class.'.php';

			// Is this a class extension request?
			if (file_exists($subclass))
			{
				$baseclass = BASEPATH.'libraries/'.ucfirst($class).'.php';

				if ( ! file_exists($baseclass))
				{
					log_message('error', "Unable to load the requested class: ".$class);
					show_error("Unable to load the requested class: ".$class);
				}

				// Safety:  Was the class already loaded by a previous call?
				if (in_array($subclass, $this->_ci_loaded_files))
				{
					// Before we deem this to be a duplicate request, let's see
					// if a custom object name is being supplied.  If so, we'll
					// return a new instance of the object
					if ( ! is_null($object_name))
					{
						$CI =& get_instance();
						if ( ! isset($CI->$object_name))
						{
							return $this->_ci_init_class($class, config_item('extend_subclass_prefix'), $params, $object_name);
						}
					}

					$is_duplicate = TRUE;
					log_message('debug', $class." class already loaded. Second attempt ignored.");
					return;
				}

				include_once($baseclass);
				include_once($subclass);
				$this->_ci_loaded_files[] = $subclass;

				return $this->_ci_init_class($class, config_item('extend_subclass_prefix'), $params, $object_name);
			}
            
			// Lets search for the requested library file and load it.
			$is_duplicate = FALSE;
			foreach ($this->_ci_library_paths as $path)
			{
				$filepath = $path.'libraries/'.$subdir.$class.'.php';

				// Does the file exist?  No?  Bummer...
				if ( ! file_exists($filepath))
				{
					continue;
				}

				// Safety:  Was the class already loaded by a previous call?
				if (in_array($filepath, $this->_ci_loaded_files))
				{
					// Before we deem this to be a duplicate request, let's see
					// if a custom object name is being supplied.  If so, we'll
					// return a new instance of the object
					if ( ! is_null($object_name))
					{
						$CI =& get_instance();
						if ( ! isset($CI->$object_name))
						{
							return $this->_ci_init_class($class, '', $params, $object_name);
						}
					}

					$is_duplicate = TRUE;
					log_message('debug', $class." class already loaded. Second attempt ignored.");
					return;
				}

				include_once($filepath);
				$this->_ci_loaded_files[] = $filepath;
				return $this->_ci_init_class($class, '', $params, $object_name);
			}

		} // END FOREACH

		// One last attempt.  Maybe the library is in a subdirectory, but it wasn't specified?
		if ($subdir == '')
		{
			$path = strtolower($class).'/'.$class;
			return $this->_ci_load_class($path, $params);
		}

		// If we got this far we were unable to find the requested class.
		// We do not issue errors if the load call failed due to a duplicate request
		if ($is_duplicate == FALSE)
		{
			log_message('error', "Unable to load the requested class: ".$class);
			show_error("Unable to load the requested class: ".$class);
		}
	}

	// --------------------------------------------------------------------
    
    /**
	 * Instantiates a class
	 *
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @param	string	an optional object name
	 * @return	null
	 */
	protected function _ci_init_class($class, $prefix = '', $config = FALSE, $object_name = NULL)
	{
		// Is there an associated config file for this class?  Note: these should always be lowercase
		if ($config === NULL)
		{
			// Fetch the config paths containing any package paths
			$config_component = $this->_ci_get_component('config');

			if (is_array($config_component->_config_paths))
			{
				// Break on the first found file, thus package files
				// are not overridden by default paths
				foreach ($config_component->_config_paths as $path)
				{
					// We test for both uppercase and lowercase, for servers that
					// are case-sensitive with regard to file names. Check for environment
					// first, global next
					if (defined('ENVIRONMENT') AND file_exists($path .'config/'.ENVIRONMENT.'/'.strtolower($class).'.php'))
					{
						include($path .'config/'.ENVIRONMENT.'/'.strtolower($class).'.php');
						break;
					}
					elseif (defined('ENVIRONMENT') AND file_exists($path .'config/'.ENVIRONMENT.'/'.ucfirst(strtolower($class)).'.php'))
					{
						include($path .'config/'.ENVIRONMENT.'/'.ucfirst(strtolower($class)).'.php');
						break;
					}
					elseif (file_exists($path .'config/'.strtolower($class).'.php'))
					{
						include($path .'config/'.strtolower($class).'.php');
						break;
					}
					elseif (file_exists($path .'config/'.ucfirst(strtolower($class)).'.php'))
					{
						include($path .'config/'.ucfirst(strtolower($class)).'.php');
						break;
					}
				}
			}
		}

		if ($prefix == '')
		{
			if (class_exists('CI_'.$class))
			{
				$name = 'CI_'.$class;
			}
            elseif (class_exists(config_item('extend_subclass_prefix').$class))
			{
				$name = config_item('extend_subclass_prefix').$class;
			}
			elseif (class_exists(config_item('subclass_prefix').$class))
			{
				$name = config_item('subclass_prefix').$class;
			}
			else
			{
				$name = $class;
			}
		}
		else
		{
			$name = $prefix.$class;
		}

		// Is the class name valid?
		if ( ! class_exists($name))
		{
			log_message('error', "Non-existent class: ".$name);
			show_error("Non-existent class: ".$class);
		}

		// Set the variable name we will assign the class to
		// Was a custom class name supplied?  If so we'll use it
		$class = strtolower($class);

		if (is_null($object_name))
		{
			$classvar = ( ! isset($this->_ci_varmap[$class])) ? $class : $this->_ci_varmap[$class];
		}
		else
		{
			$classvar = $object_name;
		}

		// Save the class name and object name
		$this->_ci_classes[$class] = $classvar;

		// Instantiate the class
		$CI =& get_instance();
		if ($config !== NULL)
		{
			$CI->$classvar = new $name($config);
		}
		else
		{
			$CI->$classvar = new $name;
		}
	}

	// --------------------------------------------------------------------
    
    	// --------------------------------------------------------------------

	/**
	 * Database Loader
	 *
	 * @param	string	the DB credentials
	 * @param	bool	whether to return the DB object
	 * @param	bool	whether to enable active record (this allows us to override the config setting)
	 * @return	object
	 */
	public function database($params = '', $return = FALSE, $active_record = NULL)
	{
		// Grab the super object
		$CI =& get_instance();

		// Do we even need to load the database class?
		if (class_exists('CI_DB') AND $return == FALSE AND $active_record == NULL AND isset($CI->db) AND is_object($CI->db))
		{
			return FALSE;
		}

		require_once(EXTEND_PATH.'database/DB.php');
        
		if ($return === TRUE)
		{
			return DB($params, $active_record);
		}
		// Initialize the db variable.  Needed to prevent
		// reference errors with some configurations
		$CI->db = '';

		// Load the DB class
		$CI->db =& DB($params, $active_record);
	}

	// --------------------------------------------------------------------

	/**
	 * Load the Utilities Class
	 *
	 * @return	string
	 */
	public function dbutil()
	{
		if ( ! class_exists('CI_DB'))
		{
			$this->database();
		}

		$CI =& get_instance();

		// for backwards compatibility, load dbforge so we can extend dbutils off it
		// this use is deprecated and strongly discouraged
		$CI->load->dbforge();

		require_once(EXTEND_PATH.'database/DB_utility.php');
		require_once(EXTEND_PATH.'database/drivers/'.$CI->db->dbdriver.'/'.$CI->db->dbdriver.'_utility.php');
		$class = 'CI_DB_'.$CI->db->dbdriver.'_utility';

		$CI->dbutil = new $class();
	}

	// --------------------------------------------------------------------

	/**
	 * Load the Database Forge Class
	 *
	 * @return	string
	 */
	public function dbforge()
	{
		if ( ! class_exists('CI_DB'))
		{
			$this->database();
		}

		$CI =& get_instance();

		require_once(EXTEND_PATH.'database/DB_forge.php');
		require_once(EXTEND_PATH.'database/drivers/'.$CI->db->dbdriver.'/'.$CI->db->dbdriver.'_forge.php');
		$class = 'CI_DB_'.$CI->db->dbdriver.'_forge';

		$CI->dbforge = new $class();
	}
	
	
	/**
	 * Redis Loader
	 */
    public function redis($params = '', $return = FALSE,$object_name = null)
    {        
        
        require_once EXTEND_PATH . 'libraries/DK_Redis.php';
        
        if($return === true)
        {
            return DK_Redis::getInstance($params);
        }
        
        $object_name = (is_string($object_name) && !empty($object_name)) ? $object_name : 'redis';
        
        $CI = get_instance();
        $CI->$object_name = '';        
        $CI->$object_name = DK_Redis::getInstance($params);
    }
    
    /**
     * Mongodb Loader
     */
    public function mongodb($params = '', $return = FALSE,$object_name = null)
    {        
    	
    	
        if($params ==''){
        	return false;
        }
    	
        require_once EXTEND_PATH . 'libraries/DK_Mongodb.php';
        
        if($return === true)
        {
            return DK_Mongodb::getInstance($params);
        }
        
        $object_name = (is_string($object_name) && !empty($object_name)) ? $object_name : 'mongodb';
        
        $CI = get_instance();
        $CI->$object_name = '';        
        $CI->$object_name = DK_Mongodb::getInstance($params);
    }
    
    /**
     * Memcache Loader
     */
    public function memcache($params = '', $return = FALSE,$object_name = null)
    {        
        
        require_once EXTEND_PATH . 'libraries/DK_Memcache.php';
        
        if($return === true)
        {
            return DK_Memcache::getInstance($params);
        }
        
        $object_name = (is_string($object_name) && !empty($object_name)) ? $object_name : 'memcache';
        $CI = get_instance();               
        $CI->$object_name = '';        
        $CI->$object_name = DK_Memcache::getInstance($params);
    }
    
    /**
     * FastDFS Loader
     */
    public function fastdfs($params='',$return = false,$object_name = null)
    {
        
        require_once EXTEND_PATH . 'libraries/DK_Storage.php';
        
        if($return === true)
        {
            return DK_Storage::getInstance($params);
        }
        
        $object_name = (is_string($object_name) && !empty($object_name)) ? $object_name : 'storage';
        $CI = get_instance();
        $CI->$object_name = '';
        
        $CI->$object_name = DK_Storage::getInstance($params);
    }

    
    /**
     * Httpsqs Loader
     */
    public function httpsqs($params='',$return = false,$object_name = null)
    {
        
        require_once EXTEND_PATH . 'libraries/DK_Httpsqs.php';
        
        if($return === true)
        {
            return DK_Httpsqs::getInstance($params);
        }
        
        $object_name = (is_string($object_name) && !empty($object_name)) ? $object_name : 'httpsqs';
        $CI = get_instance();
        $CI->$object_name = '';
        
        $CI->$object_name = DK_Httpsqs::getInstance($params);
    }
	// --------------------------------------------------------------------
}

/* End of file Loader.php */
/* Location: ./system/core/Loader.php */