<?php

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Mvc_View
{
    protected $scriptPath = null;
    
    protected $fileExtension = 'phtml';

	/**
	 * @var array Variables container
	 */
	protected $_vars = array();

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
     * @throws Zend_View_Exception if an attempt to set a private or protected
     * member is detected
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
        
		if (!isset($vars[$name]))
        {
			return;
		}
        
		unset($vars[$name]);
        
		$this->_vars = $vars;
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
     * Finds a view script from the available directories.
     *
     * @param string $name The base name of the script.
     * @return void
     */
    protected function _script($name)
    {
        $scriptPath = rtrim($this->scriptPath, '\\/') . DIRECTORY_SEPARATOR;
        
        $fileExtension = '.' . ltrim($this->fileExtension, '.');
        
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
			foreach ($spec as $key=>$val)
            {
				$this->__set($key,$val);
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
     * Processes a view script and returns the output.
     *
     * @param string $name The script name to process.
     * @return string The script output.
     */
    public function render($name)
    {
        // find the script file name using the parent private method
        $file = $this->_script($name);
        unset($name); // remove $name from local scope

        ob_start();
        
        $this->_run($file);
        
        $content = ob_get_contents();
        
        ob_end_clean();

        return $content;
    }
}

