<?php

$error_reporting_EhrlichAndreas_Util_Mvc_Parameter = error_reporting();

error_reporting(0);

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Mvc_Parameter extends ArrayObject implements Serializable
{
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
	 * Returns the column/value data as an array.
	 *
	 * @return array
	 */
	public function toArray()
    {
		return (array) $this->getArrayCopy();
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


error_reporting($error_reporting_EhrlichAndreas_Util_Mvc_Parameter);

unset($error_reporting_EhrlichAndreas_Util_Mvc_Parameter);

