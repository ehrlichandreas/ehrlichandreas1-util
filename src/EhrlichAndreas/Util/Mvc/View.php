<?php

$error_reporting_EhrlichAndreas_Util_Mvc_View = error_reporting();

error_reporting(0);

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Mvc_View
{

    /**
     * Callback for escaping.
     *
     * @var string
     */
    protected $_escape = 'htmlspecialchars';

    /**
     * Encoding to use in escaping mechanisms; defaults to utf-8
     * @var string
     */
    protected $_encoding = 'UTF-8';
    
    /**
     *
     * @var string 
     */
    protected $_fileExtension = 'phtml';
    
    /**
     *
     * @var string 
     */
    protected $_layout = 'layout';
    
    /**
     *
     * @var string 
     */
    protected $_scriptPath = null;

	/**
	 * @var EhrlichAndreas_Util_Array Variables container
	 */
	protected $_vars = null;
    
    /**
     * 
     */
    public function __construct()
    {
        $this->_vars = new EhrlichAndreas_Util_Array();
    }

    /**
	 *
	 * @param  string $name
	 * @return mixed
	 */
	public function __get($name)
    {
		if (isset($this->_vars[$name]))
        {
			$var = & $this->_vars[$name];
            
			return $var;
		}
        
		$var = null;
        
		return $var;
	}

    /**
     * Allows testing with empty() and isset() to work inside
     * templates.
     *
     * @param  string $name
	 * @return bool
	 */
	public function __isset($name)
    {
		$vars = $this->_vars;
        
		return isset($vars[$name]);
	}

    /**
     * Directly assigns a variable to the view script.
     *
     * Checks first to ensure that the caller is not attempting to set a
     * protected or private member (by checking for a prefixed underscore); if
     * not, the public member is set; otherwise, an exception is raised.
     *
     * @param string $name The variable name.
     * @param mixed $value The variable value.
     * @return void
     * @throws EhrlichAndreas_Util_Exception if an attempt to set a private or 
     * protected member is detected
     */
	public function __set($name, $value)
    {
		$vars = $this->_vars;
        
		if (is_null($value) && isset($vars[$name]))
        {
			unset($vars[$name]);
		}
        else
        {
			$vars[$name] = $value;
		}
        
		$this->_vars = $vars;
	}

    /**
     * Allows unset() on object properties to work
     *
     * @param string $name
     * @return void
     */
	public function __unset($name)
    {
		$vars = $this->_vars;
        
		if (! isset($vars[$name]))
        {
			return;
		}
        
		unset($vars[$name]);
        
		$this->_vars = $vars;
	}

    /**
     * Finds a view script from the available directories.
     *
     * @param string $name The base name of the script.
     * @return void
     */
    protected function _script($name)
    {
        if (is_null($this->_scriptPath))
        {
            $message = 'no view script directory set; unable to determine location for view script';
            
            $e = new EhrlichAndreas_Util_Exception($message);
            
            throw $e;
        }
        
        $scriptPath = rtrim($this->_scriptPath, '\\/') . DIRECTORY_SEPARATOR;
        
        $fileExtension = '.' . ltrim($this->_fileExtension, '.');
        
        $file = $name . $fileExtension ;
        
        $path = $scriptPath . $file;
        
        if (is_readable($path))
        {
            return $path;
        }

        $message = "script '" . $file . "' not found in path (" . $scriptPath  . ")";
        
        $e = new EhrlichAndreas_Util_Exception($message);
        
        throw $e;
    }

    /**
     * Includes the view script in a scope with only public $this variables.
     *
     * @param string The view script to execute.
     */
    protected function _run()
    {
        include func_get_arg(0);
    }

    /**
     *
     * @param  array $config  Configuration object
     * @param  string      $section Name of the config section containing view's definitions
     * @throws EhrlichAndreas_Util_Exception
     * @return EhrlichAndreas_Util_Mvc_Router
     */
    public function addConfig($config, $section = null)
    {
        if ($section !== null)
        {
            $config = EhrlichAndreas_Util_Array::objectToArray($config);
            
            if (!isset($config[$section]) || is_null($config[$section]))
            {
                throw new EhrlichAndreas_Util_Exception("No route configuration in section '{$section}'");
            }

            $config = $config[$section];
        }

        foreach ($config as $name => $info)
        {
            $name = strtolower($name);
            
            if ($name == 'encoding')
            {
                $this->setEncoding($info);
            }
            elseif ($name == 'escape')
            {
                $this->_escape = $info;
            }
            elseif ($name == 'fileextension')
            {
                $this->_fileExtension = $info;
            }
            elseif ($name == 'layout')
            {
                $this->_layout = $info;
            }
            elseif ($name == 'scriptpath')
            {
                $this->_scriptPath = $info;
            }
        }

        return $this;
    }

	/**
	 * Assigns variables to the view script via differing strategies.
	 *
	 * EhrlichAndreas_Util_Mvc_View::assign('name', $value) assigns a variable 
     * called 'name' with the corresponding $value.
	 *
	 * EhrlichAndreas_Util_Mvc_View::assign($array) assigns the array keys 
     * as variable names (with the corresponding array values).
	 *
	 * @see    __set()
	 * @param  string|array The assignment strategy to use.
	 * @param  mixed (Optional) If assigning a named variable, use this
	 * as the value.
	 * @return EhrlichAndreas_Util_Mvc_View Fluent interface
	 * @throws EhrlichAndreas_Util_Exception if $spec is neither a string 
     * nor an array, or if an attempt to set a private or protected member 
     * is detected
	 */
	public function assign($spec, $value = null)
    {
		// which strategy to use?
        
		if (is_object($spec))
        {
            $spec = EhrlichAndreas_Util_Array::objectToArray($spec);
		}
        
		if (is_string($spec))
        {
			// assign by name and value
			$this->__set($spec, $value);
		}
        elseif (is_array($spec))
        {
			// assign from associative array
			foreach ($spec as $key => $val)
            {
				$this->__set($key, $val);
			}
		}
        else
        {
            $message = 'assign() expects a string or array, received '.gettype($spec);
            
			$e = new EhrlichAndreas_Util_Exception($message);
            
			throw $e;
		}

		return $this;
	}

    /**
     * Clear all assigned variables
     *
     * Clears all variables assigned to EhrlichAndreas_Util_Mvc_View either 
     * via {@link assign()} or property overloading ({@link __set()}).
     *
     * @return void
     */
    public function clearVars()
    {
        foreach ($this->_vars as $key => $value)
        {
            unset($this->_vars[$key]);
        }
    }

    /**
     * Escapes a value for output in a view script.
     *
     * If escaping mechanism is one of htmlspecialchars or htmlentities, uses
     * {@link $_encoding} setting.
     *
     * @param mixed $var The output to escape.
     * @return mixed The escaped value.
     */
    public function escape($var)
    {
        $escape = $this->_escape;
        
        $encoding = $this->_encoding;
        
        if (in_array($escape, array('htmlspecialchars', 'htmlentities')))
        {
            return call_user_func($escape, $var, ENT_COMPAT, $encoding);
        }

        if (1 == func_num_args())
        {
            return call_user_func($escape, $var);
        }
        
        $args = func_get_args();
        
        return call_user_func_array($escape, $args);
    }

    /**
     * Return current escape encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->_encoding;
    }

    /**
     * Return full path to a view script specified by $name
     *
     * @param  string $name
     * @return false|string False if script not found
     * @throws EhrlichAndreas_Util_Exception if no script directory set
     */
    public function getScriptPath($name)
    {
        try
        {
            $path = $this->_script($name);
            
            return $path;
        }
        catch (EhrlichAndreas_Util_Exception $e)
        {
            if (strstr($e->getMessage(), 'no view script directory set'))
            {
                throw $e;
            }

            return false;
        }
    }

    /**
     * Return list of all assigned variables
     *
     * Returns all public properties of the object. Reflection is not used
     * here as testing reflection properties for visibility is buggy.
     *
     * @return array
     */
    public function getVars()
    {
        $vars = $this->_vars->toArray();

        return $vars;
    }

    /**
     * Allow custom object initialization when extending 
     * EhrlichAndreas_Util_Mvc_View
     *
     * Triggered by {@link __construct() the constructor} as its final action.
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Processes a view script and returns the output.
     *
     * @param string $name The script name to process.
     * @return string The script output.
     */
    public function render($name)
    {
        // find the script file name using the parent private method
        $file = $this->_script($name);
        
        // remove $name from local scope
        unset($name);

        ob_start();
        
        $this->_run($file);
        
        $content = ob_get_contents();
        
        ob_end_clean();

        return $content;
    }

    /**
     * Set encoding to use with htmlentities() and htmlspecialchars()
     *
     * @param string $encoding
     * @return EhrlichAndreas_Util_Mvc_View
     */
    public function setEncoding($encoding)
    {
        $this->_encoding = $encoding;
        return $this;
    }

    /**
     * Resets the stack of view script paths.
     *
     * To clear path, use EhrlichAndreas_Util_Mvc_View::setScriptPath(null).
     *
     * @param string|array The directory (-ies) to set as the path.
     * @return EhrlichAndreas_Util_Mvc_View
     */
    public function setScriptPath($path)
    {
        $this->_scriptPath = $path;
        
        return $this;
    }
}


error_reporting($error_reporting_EhrlichAndreas_Util_Mvc_View);

unset($error_reporting_EhrlichAndreas_Util_Mvc_View);

