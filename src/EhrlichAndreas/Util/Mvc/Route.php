<?php

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Mvc_Route 
{
    
    /**
     * URI delimiter
     */
    const URI_DELIMITER = '/';
    
    /**
     * Wether this route is abstract or not
     *
     * @var boolean
     */
    protected $_isAbstract = false;

    /**
     * Path matched by this route
     *
     * @var string
     */
    protected $_matchedPath = null;
	
	protected $_callbacks = array();
    
    protected $_defaults = array();
    
    protected $_map = array();
    
    protected $_regex = null;
    
    protected $_reverse = null;
    
    protected $_values = array();

	public function __construct($route, $defaults = array(), $map = array(), $reverse = null, $callbacks = array())
    {
        $this->_regex    = $route;
        
        $this->_defaults = EhrlichAndreas_Util_Array::objectToArray($defaults);
        
        $this->_map      = EhrlichAndreas_Util_Array::objectToArray($map);
        
        $this->_reverse  = $reverse;
        
		foreach ($callbacks as $key => $callback)
        {
			if (is_string($callback))
            {
				$callback = str_ireplace(',', '|', $callback);
                
				$callback = trim($callback);
                
				$callback = trim($callback, '|,');
                
				$callback = explode('|', $callback);
                
				$array = array();
                
				foreach ($callback as $call)
                {
					$call = trim($call);
                    
					if (strlen($call) > 0)
                    {
						$array[] = $call;
					}
				}
                
				$this->_callbacks[$key] = $array;
                
			}
            elseif (is_array($callback))
            {
				$array = array();
                
				$ar = array();
                
				foreach ($callback as $k => $conf)
                {
					if (is_array($conf))
                    {
						if (isset($conf['class']) && isset($conf['function']))
                        {
							$ar[0] = $conf['class'];
                            
							$ar[1] = $conf['function'];
                            
							$array[] = $ar;
                            
							$ar = array();
						}
                        else
                        {
							foreach ($conf as $i => $val)
                            {
								if (is_array($val))
                                {
									if (isset($val['class']) && isset($val['function']))
                                    {
										$ar[0] = $val['class'];
                                        
										$ar[1] = $val['function'];
                                        
										$array[] = $ar;
                                        
										$ar = array();
									}
								}
                                
								if (is_string($val))
                                {
									$array[] = $val;
                                    
									$ar = array();
								}
							}
						}
					}
                    else
                    {
						$array[] = $conf;
					}
				}
                
				$this->_callbacks[$key] = $array;
			}
		}
    }

    /**
     * _arrayMergeNumericKeys() - allows for a strict key (numeric's included) array_merge.
     * php's array_merge() lacks the ability to merge with numeric keys.
     *
     * @param array $array1
     * @param array $array2
     * @return array
     */
    protected function _arrayMergeNumericKeys($array1, $array2)
    {
        $returnArray = $array1;
        
        foreach ($array2 as $array2Index => $array2Value)
        {
            $returnArray[$array2Index] = $array2Value;
        }
        
        return $returnArray;
    }

    /**
     * Maps numerically indexed array values to it's associative mapped counterpart.
     * Or vice versa. Uses user provided map array which consists of index => name
     * parameter mapping. If map is not found, it returns original array.
     *
     * Method strips destination type of keys form source array. Ie. if source array is
     * indexed numerically then every associative key will be stripped. Vice versa if reversed
     * is set to true.
     *
     * @param  array   $values Indexed or associative array of values to map
     * @param  boolean $reversed False means translation of index to association. True means reverse.
     * @param  boolean $preserve Should wrong type of keys be preserved or stripped.
     * @return array   An array of mapped values
     */
    protected function _getMappedValues($values, $reversed = false, $preserve = false)
    {
        if (count($this->_map) == 0)
        {
            return $values;
        }

        $return = array();

        foreach ($values as $key => $value)
        {
            if (is_int($key) && !$reversed)
            {
                if (array_key_exists($key, $this->_map))
                {
                    $index = $this->_map[$key];
                }
                elseif (false === ($index = array_search($key, $this->_map)))
                {
                    $index = $key;
                }
                
                $return[$index] = $values[$key];
            }
            elseif ($reversed)
            {
                $index = $key;
                
                if (!is_int($key))
                {
                    if (array_key_exists($key, $this->_map))
                    {
                        $index = $this->_map[$key];
                    }
                    else
                    {
                        $index = array_search($key, $this->_map, true);
                    }
                }
                
                if (false !== $index)
                {
                    $return[$index] = $values[$key];
                }
            }
            elseif ($preserve)
            {
                $return[$key] = $value;
            }
        }

        return $return;
    }

	/**
	 * Assembles a URL path defined by this route
	 *
	 * @param  array $data An array of name (or index) and value pairs used as parameters
	 * @return string Route path with user submitted parameters
	 */
	public function assemble($data = array(), $reset = false, $encode = false, $partial = false)
    {
		if ($this->_reverse===null)
        {
			throw new EhrlichAndreas_Util_Exception('Cannot assemble. Reversed route is not specified.');
		}
		
		$callbacks = $this->_callbacks;
        
		$defaultValuesMapped = $this->_defaults;
        
		$matchedValuesMapped = $this->_values;
        
		$dataValuesMapped = $data;
		
		$defaultValuesMapped = $this->_getMappedValues($defaultValuesMapped, true, false);
        
		$matchedValuesMapped = $this->_getMappedValues($matchedValuesMapped, true, false);
        
		$dataValuesMapped = $this->_getMappedValues($dataValuesMapped, true, false);
        
        $resetKeys = array_search(null, $dataValuesMapped, true);
		
		// handle resets, if so requested (By null value) to do so
		if ($resetKeys !== false)
        {
			foreach ((array)$resetKeys as $resetKey)
            {
				if (isset($matchedValuesMapped[$resetKey]))
                {
					unset($matchedValuesMapped[$resetKey]);
                    
					unset($dataValuesMapped[$resetKey]);
				}
			}
		}
		
		// merge all the data together, first defaults, then values matched, then supplied
		$mergedData = $defaultValuesMapped;
        
		$mergedData = $this->_arrayMergeNumericKeys($mergedData, $matchedValuesMapped);
        
		$mergedData = $this->_arrayMergeNumericKeys($mergedData, $dataValuesMapped);
		
		$mergedData = $this->_getMappedValues($mergedData, false, true);
        
		foreach ($mergedData as $k => $d)
        {
			if (isset($callbacks[$k]) && is_array($callbacks[$k]) && count($callbacks[$k])>0)
            {
				foreach ($callbacks[$k] as $callback)
                {
					if (is_array($callback))
                    {
						//$callbacks [$k] = implode('::', $callbacks [$k]);
						$d = call_user_func_array($callback, array($d, $mergedData));
					}
                    else
                    {
						$d = call_user_func($callback, $d);
					}
				}
                
				$mergedData[$k] = $d;
			}
		}
        
		$mergedData = $this->_getMappedValues($mergedData,true,false);
		
		if ($encode)
        {
			foreach ($mergedData as $key => $value)
            {
				$value = urlencode($value);
                
                $mergedData[$key] = $value;
			}
		}
		
		ksort($mergedData);
		
		$return = EhrlichAndreas_Util_Vsprintf::vsprintf($this->_reverse, $mergedData);
		
		if ($return === false)
        {
			$mergedData = print_r($mergedData,true);
            
			$mergedData = '$mergedData = ' . $mergedData . "\n<br>";
            
			$reverse = print_r($this->_reverse,true);
            
			$reverse = '$reverse = '.$reverse."\n<br>";
            
			throw new EhrlichAndreas_Util_Exception('Cannot assemble. Too few arguments?'/*."\n<br>".$mergedData.$reverse*/);
		}
		
		return $return;
	}

    /**
     * Return a single parameter of route's defaults
     *
     * @param string $name Array key of the parameter
     * @return string Previously set default
     */
    public function getDefault($name)
    {
        if (isset($this->_defaults[$name]))
        {
            return $this->_defaults[$name];
        }
    }

    /**
     * Return an array of defaults
     *
     * @return array Route defaults
     */
    public function getDefaults()
    {
        return $this->_defaults;
    }

	/**
	 * Instantiates route based on passed Zend_Config structure
	 *
	 * @param array $config Configuration object
	 */
	public static function getInstance($config)
    {
        $config = EhrlichAndreas_Util_Array::objectToArray($config);
        
        $route = '';
        
        $defs = array();
        
        $map = array();
        
        $reverse = null;
        
        $callbacks = array();
        
        if (isset($config['route']))
        {
            $route = $config['route'];
        }
        
        if (isset($config['defaults']))
        {
            $defs = $config['defaults'];
        }
        
        if (isset($config['map']))
        {
            $map = $config['map'];
        }
        
        if (isset($config['reverse']))
        {
            $reverse = $config['reverse'];
        }
        
        if (isset($config['callbacks']))
        {
            $callbacks = $config['callbacks'];
        }
        
		return new self($route, $defs, $map, $reverse, $callbacks);
	}

    /**
     * Get partially matched path
     *
     * @return string
     */
    public function getMatchedPath()
    {
        return $this->_matchedPath;
    }

    /**
     * Check or set wether this is an abstract route or not
     *
     * @param  boolean $flag
     * @return boolean
     */
    public function isAbstract($flag = null)
    {
        if ($flag !== null)
        {
            $this->_isAbstract = $flag;
        }

        return $this->_isAbstract;
    }

    /**
     * Get all variables which are used by the route
     *
     * @return array
     */
    public function getVariables()
    {
        $variables = array();

        foreach ($this->_map as $key => $value)
        {
            if (is_numeric($key))
            {
                $variables[] = $value;
            }
            else
            {
                $variables[] = $key;
            }
        }

        return $variables;
    }

    /**
     * Matches a user submitted path with a previously defined route.
     * Assigns and returns an array of defaults on a successful match.
     *
     * @param  string $path Path used to match against this routing map
     * @return array|false  An array of assigned values or a false on a mismatch
     */
    public function match($path, $partial = false)
    {
        if (!$partial)
        {
            $path = trim(urldecode($path), self::URI_DELIMITER);
            
            $regex = '#^' . $this->_regex . '$#i';
        }
        else
        {
            $regex = '#^' . $this->_regex . '#i';
        }

        $res = preg_match($regex, $path, $values);

        if ($res === 0)
        {
            return false;
        }

        if ($partial)
        {
            $this->setMatchedPath($values[0]);
        }

        // array_filter_key()? Why isn't this in a standard PHP function set yet? :)
        foreach ($values as $i => $value)
        {
            if (!is_int($i) || $i === 0)
            {
                unset($values[$i]);
            }
        }

        $this->_values = $values;

        $values   = $this->_getMappedValues($values);
        
        $defaults = $this->_getMappedValues($this->_defaults, false, true);
        
        $return   = $values + $defaults;

        return $return;
    }

    /**
     * Set partially matched path
     *
     * @param  string $path
     * @return void
     */
    public function setMatchedPath($path)
    {
        $this->_matchedPath = $path;
    }
}

