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
     *
     * @var EhrlichAndreas_Util_Mvc_Request 
     */
    protected $_request = null;
    
    /**
     *
     * @var type 
     */
    protected $_response = null;
    
    /**
     * 
     * @param EhrlichAndreas_Util_Mvc_Request $request
     * @param type $response
     */
    public function __construct($request, $response = null)
    {
        $this->setRequest($request);
        
        $this->setResponse($response);
        
        $this->init();
    }

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

        $response = $this->__call($action . 'Action', array());

        $this->postDispatch();
        
        return $response;
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
     * Return the Request object
     *
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Return the Response object
     *
     * @return Zend_Controller_Response_Abstract
     */
    public function getResponse()
    {
        return $this->_response;
    }
    
    /**
     * 
     */
    protected function init()
    {
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
    protected function preDispatch()
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
    protected function postDispatch()
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
     * Set the Request object
     *
     * @param EhrlichAndreas_Util_Mvc_Request $request
     * @return EhrlichAndreas_Util_Mvc_Controller
     */
    public function setRequest($request)
    {
        $this->_request = $request;
        
        return $this;
    }

    /**
     * Set the Response object
     *
     * @param type $response
     * @return EhrlichAndreas_Util_Mvc_Controller
     */
    public function setResponse($response)
    {
        $this->_response = $response;
        
        return $this;
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

