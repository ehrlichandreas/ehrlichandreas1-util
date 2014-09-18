<?php

$error_reporting_EhrlichAndreas_Util_Array = error_reporting();

error_reporting(0);

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Array extends ArrayObject implements Serializable
{
    /**
     * Constructor
     *
     * Enforces that we have an array, and enforces parameter access to array
     * elements.
     *
     * @param  array $values
     */
    public function __construct(array $values = null)
    {
        if (null === $values)
        {
            $values = array();
        }
        
        parent::__construct($values, ArrayObject::ARRAY_AS_PROPS);
    }
    
	/**
	 * Returns the column/value data as an array.
	 *
	 * @return array
	 */
	public function __toArray()
    {
		return $this->toArray();
	}

    /**
     * A recursive array_change_key_case function.
     * 
     * @param array $input            
     * @param integer $case            
     */
    public static function arrayChangeKeyCaseRecursive ($input, $case = null)
    {
        if (! is_array($input))
        {
            trigger_error("Invalid input array '{$input}'", E_USER_NOTICE);
            exit();
        }
        
        // CASE_UPPER|CASE_LOWER
        if (null === $case)
        {
            $case = CASE_LOWER;
        }
        
        if ($case !== CASE_UPPER && $case !== CASE_LOWER)
        {
            trigger_error("Case parameter '{$case}' is invalid.", E_USER_NOTICE);
            exit();
        }
        
        $input = array_change_key_case($input, $case);
        
        foreach ($input as $key => $array)
        {
            if (is_array($array))
            {
                $input[$key] = self::arrayChangeKeyCaseRecursive($array, $case);
            }
        }
        
        return $input;
    }

    /**
     *
     * @param array $array            
     * @param array $conditions            
     * @return multitype:
     */
    public static function arrayCondition ($array = array(), $conditions = array())
    {
        if (empty($array) || empty($conditions))
        {
            return $array;
        }
        
        $empty = empty($conditions);
        
        foreach ($array as $key_tmp => $value_tmp)
        {
            $check = true;
            
            if (! $empty)
            {
                
                foreach ($conditions as $key_where => $value_where)
                {
                    
                    if (is_int($key_where))
                    {
                        $check = $check && self::arrayConditionHelp($value_tmp, $value_where);
                    }
                    else
                    {
                        $check = $check && isset($value_tmp[$key_where]);
                        
                        $check = $check && ($value_tmp[$key_where] == $value_where);
                    }
                    
                    if (! $check)
                    {
                        break;
                    }
                }
            }
            
            if (! $check)
            {
                unset($array[$key_tmp]);
            }
        }
        
        $array = array_values($array);
        
        return $array;
    }

    /**
     *
     * @param array $array            
     * @param string $condition            
     * @return boolean
     */
    public static function arrayConditionHelp ($array = array(), $condition = '')
    {
        static $conditions_equal = array
        (
            '='     => '=',
            '=='    => '==',
            '<>'    => '<>',
            '!='    => '!=',
            '<'     => '<',
            '<='    => '<=',
            '>'     => '>',
            '>='    => '>=',
        );
        
        static $conditions_regex = array
        (
            'like'  => 'like',
            'regex' => 'regex',
        );
        
        static $conditions = array();
        
        $condition = preg_replace('/[ ]+/', ' ', $condition, 3);
        
        $var = explode(' ', $condition, 3);
        
        if (count($var) == 3)
        {
            $key = $var[0];
            
            $con = $var[1];
            
            $val = $var[2];
            
            $con = strtolower($con);
            
            $val_is_null = $val;
            
            $val_is_null = strtolower($val_is_null);
            
            $val_is_null = ($val_is_null == 'null');
            
            if (isset($conditions_equal[$con]))
            {
                
                $valTmp = trim($val, '\'"');
                
                if ($valTmp != $val)
                {
                    $val = $valTmp;
                }
                
                switch ($con)
                {
                    case '=':
                        
                    case '==':
                        if ($val_is_null && (! isset($array[$key])) || is_null($array[$key]) || $array[$key] == '')
                        {
                            return true;
                        }
                        
                        if ($array[$key] != $val)
                        {
                            return false;
                        }
                        
                        break;
                    
                    case '!=':
                        
                    case '<>':
                        if ($val_is_null && (! isset($array[$key])) || is_null($array[$key]) || $array[$key] == '')
                        {
                            return false;
                        }
                        
                        if ($array[$key] == $val)
                        {
                            return false;
                        }
                        
                        break;
                    
                    case '<':
                        if (! isset($array[$key]) || $array[$key] >= $val)
                        {
                            return false;
                        }
                        
                        break;
                    
                    case '<=':
                        if (! isset($array[$key]) || $array[$key] > $val)
                        {
                            return false;
                        }
                        
                        break;
                    
                    case '>':
                        if (! isset($array[$key]) || $array[$key] <= $val)
                        {
                            return false;
                        }
                        
                        break;
                    
                    case '>=':
                        if (! isset($array[$key]) || $array[$key] < $val)
                        {
                            return false;
                        }
                        
                        break;
                }
            }
            elseif (isset($conditions_regex[$con]))
            {
                
                $valTmp = trim($val, '\'"');
                
                if ($valTmp != $val)
                {
                    $val = $valTmp;
                }
                
                $con = strtolower($con);
                
                switch ($con)
                {
                    case 'like':
                        $val = '#^' . str_replace('%', '.*', preg_quote($val, '#')) . '$#ui';
                        
                        break;
                }
                
                switch ($con)
                {
                    case 'regex':
                        
                    case 'like':
                        if (! isset($array[$key]) || ! preg_match($val, $array[$key]))
                        {
                            return false;
                        }
                        
                        break;
                }
            }
        }
        
        return true;
    }

    /**
     * Populate from native PHP array
     *
     * @param  array $values
     * @return void
     */
    public function fromArray(array $values)
    {
        $this->exchangeArray($values);
    }

    /**
     * Populate from query string
     *
     * @param  string $string
     * @return void
     */
    public function fromString($string)
    {
        $array = array();
        
        parse_str($string, $array);
        
        $this->fromArray($array);
    }

    public static function objectToArray ($array = array())
    {
        if (! is_array($array) && ! is_object($array))
        {
            return $array;
        }
        
        if (is_object($array) && method_exists($array, '__toArray'))
        {
            $array = $array->__toArray();
        }
        
        if (is_object($array) && method_exists($array, 'toArray'))
        {
            $array = $array->toArray();
        }
        
        if (is_object($array))
        {
            $array = get_object_vars($array);
        }
        
        foreach ($array as $key => $value)
        {
            if (is_array($value) || is_object($value))
            {
                $array[$key] = self::objectToArray($value);
            }
        }
        
        return $array;
    }

    /**
     * Retrieve by key
     *
     * Returns null if the key does not exist.
     *
     * @param  string $name
     * @return mixed
     */
    public function offsetGet($name)
    {
        if ($this->offsetExists($name))
        {
            return parent::offsetGet($name);
        }
        
        return null;
    }

    public static function arrayFlipMulti ($param = array())
    {
        $return = array();
        
        foreach ($param as $key => $row)
        {
            foreach ($row as $k => $v)
            {
                $return[$k][$key] = $v;
            }
        }
        
        return $return;
    }

    public static function arrayLimit ($array = array(), $offset = null, $count = null)
    {
		
        if ($count == null && $offset == null)
        {
            return $array;
        }
        
        $offset = ($offset == null) ? 0 : (int) $offset;
        
        $count = ($count == null) ? 0 : (int) $count;
        
        $array = array_slice($array, $offset, $count);
        
        return $array;
    }

    public static function arrayLimitPage ($array = array(), $page, $count)
    {
        $page = ($page > 0) ? $page : 1;
        
        $count = ($count > 0) ? $count : 1;
        
        $count = (int) $count;
        
        $offset = (int) $count * ($page - 1);
        
        return self::arrayLimit($array, $offset, $count);
    }

    public static function arrayOrderBy ($param = array(), $orderby = array())
    {
        if (empty($param))
        {
            return $param;
        }
        
        $orderbyTmp = array();
        
        foreach ($orderby as $order)
        {
            
            $order = explode(' ', $order);
            
            if (! isset($order[1]))
            {
                $order[1] = 'desc';
            }
            
            $order[1] = strtolower($order[1]) == 'asc' ? SORT_ASC : SORT_DESC;
            
            $orderbyTmp[] = array
            (
                'field' => $order[0],
                'order' => $order[1],
                'sort'  => SORT_STRING,
            );
        }
        
        $orderby = $orderbyTmp;
        
        $paramTmp = self::arrayFlipMulti($param);
        
        $rowset = $param;
        
        $param_arr = array();
        
        foreach ($orderby as $order)
        {
            
            if (! isset($order['field']) || ! isset($paramTmp[$order['field']]))
            {
                $keysTmp = array();
            }
            else
            {
                $keysTmp = @array_map('strtolower', $paramTmp[$order['field']]);
            }
            
            $numeric = true;
            
            foreach ($keysTmp as $key)
            {
                if (! is_numeric($key))
                {
                    $numeric = false;
                }
            }
            
            if ($numeric)
            {
                $order['sort'] = SORT_NUMERIC;
            }
            
            $param_arr[] = &$keysTmp;
            
            $param_arr[] = &$order['order'];
            
            $param_arr[] = &$order['sort'];
        }
        
        $param_arr[] = &$rowset;
        
        call_user_func_array('array_multisort', $param_arr);
        
        return $rowset;
    }
    
	/**
	 * Returns the column/value data as an array.
	 *
	 * @return array
	 */
	public function toArray()
    {
		return (array) $this->getArrayCopy();
	}

    /**
     * Serialize to query string
     *
     * @return string
     */
    public function toString()
    {
        return http_build_query($this);
    }
    
	/**
	 * Serialize an ArrayObject
	 * @return void The serialized representation of the <b>ArrayObject</b>.
	 */
    public function serialize()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '>='))
        {
            return parent::serialize();
        }
        
        $serialized = serialize($this->getArrayCopy());
        
        $serialized = 'x:i:0;' . $serialized . ';m:a:0:{}';
        
        return $serialized;
    }
    
	/**
	 * Unserialize an ArrayObject
	 * @param string $serialized <p>
	 * The serialized <b>ArrayObject</b>.
	 * </p>
	 * @return void The unserialized <b>ArrayObject</b>.
	 */
    public function unserialize($serialized)
    {
        if (version_compare(PHP_VERSION, '5.3.0', '>='))
        {
            return parent::unserialize($serialized);
        }
        
        $serialized = substr($serialized, strlen('x:i:0;'));
        
        $serialized = substr($serialized, 0, strlen($serialized) - strlen(';m:a:0:{}'));
        
        $unserialized = unserialize($serialized);
        
        $this->exchangeArray($unserialized);
    }
}


error_reporting($error_reporting_EhrlichAndreas_Util_Array);

unset($error_reporting_EhrlichAndreas_Util_Array);

