<?php

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Mvc
{

    /**
     * Set whether {@link dispatch()} should return the response without first
     * rendering output. By default, output is rendered and dispatch() returns
     * nothing.
     *
     * @param boolean $flag
     * @return boolean|EhrlichAndreas_Util_Mvc Used as a setter, returns object; as a getter, returns boolean
     */
    public function returnResponse($flag = null)
    {
        if (true === $flag)
        {
            $this->_returnResponse = true;
            
            return $this;
        }
        elseif (false === $flag)
        {
            $this->_returnResponse = false;
            
            return $this;
        }

        return $this->_returnResponse;
    }
    
    public function dispatch($invokeParams = null)
    {
        if (is_null($invokeParams))
        {
            $invokeParams = array_merge($_GET, $_POST);
        }
        
		$returnResponse = $this->returnResponse();
    }
}

