<?php

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Mvc_Controller
{
    /**
     *
     * @var array 
     */
    protected $_invokeParams = array();
    
    /**
     * View object
     * @var EhrlichAndreas_Util_Mvc_View
     */
    protected $_view = null;

    /**
     * Proxy for undefined methods.  Default behavior is to throw an
     * exception on undefined methods, however this function can be
     * overridden to implement magic (dynamic) actions, or provide run-time
     * dispatching.
     *
     * @param  string $methodName
     * @param  array $args
     * @return void
     * @throws Zend_Controller_Action_Exception
     */
    public function __call($methodName, $args)
    {
        $methods = get_class_methods($this);
        
        $methods = array_combine($methods, $methods);
        
        if (isset($methods[$methodName]))
        {
            return $this->$methodName($args);
        }
        
        if ('Action' == substr($methodName, -6))
        {
            $action = substr($methodName, 0, strlen($methodName) - 6);
            
            $message = 'Action "%s" does not exist and was not trapped in __call()';
            
            throw new EhrlichAndreas_Util_Exception(sprintf($message, $action), 404);
        }
        
        $message = 'Method "%s" does not exist and was not trapped in __call()';

        throw new EhrlichAndreas_Util_Exception(sprintf($message, $methodName), 500);
    }

    /**
     * Dispatch the requested action
     * 
     * @param string $action Method name of action
     * @return void
     */
    public function dispatch($action)
    {
        $this->preDispatch();

        $this->__call($action . 'Action', array());

        $this->postDispatch();
    }
    
    /**
     * 
     * @return array
     */
    public function getInvokeParams()
    {
        return $this->_invokeParams;
    }
    
    /**
     * Pre-dispatch routines
     *
     * Called before action method. If using class with
     * {@link EhrlichAndreas_Util_Mvc}, it may modify the
     * {@link $_request Request object} and reset its dispatched flag in order
     * to skip processing the current action.
     *
     * @return void
     */
    public function preDispatch()
    {
    }

    /**
     * Post-dispatch routines
     *
     * Called after action method execution. If using class with
     * {@link EhrlichAndreas_Util_Mvc}, it may modify the
     * {@link $_request Request object} and reset its dispatched flag in order
     * to process an additional action.
     *
     * Common usages for postDispatch() include rendering content in a sitewide
     * template, link url correction, setting headers, etc.
     *
     * @return void
     */
    public function postDispatch()
    {
    }

    /**
     * 
     * @param array $invokeParams
     */
    public function setInvokeParams($invokeParams = array())
    {
        $this->_invokeParams = $invokeParams;
    }
    
    /**
     * 
     * @param EhrlichAndreas_Util_Mvc_View $view
     */
    public function setView($view)
    {
        $this->_view = $view;
    }
}

