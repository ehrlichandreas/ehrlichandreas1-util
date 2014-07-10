<?php

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Mvc
{
    
    /**
     *
     * @var string 
     */
    protected $_baseUrl = '';
    
    /**
     * Singleton instance
     *
     * Marked only as protected to allow extension of the class. To extend,
     * simply override {@link getInstance()}.
     *
     * @var EhrlichAndreas_Util_Mvc
     */
    protected static $_instance = null;

    /**
     * Whether or not to return the response prior to rendering output while in
     * {@link dispatch()}; default is to send headers and render output.
     * @var boolean
     */
    protected $_returnResponse = false;
    
    /**
     *
     * @var EhrlichAndreas_Util_Mvc_Router 
     */
    protected $_router = null;
    
    /**
     *
     * @var EhrlichAndreas_Util_Mvc_View 
     */
    protected $_view = null;
    
    /**
     *
     * @var string 
     */
    protected $_request = null;
    
    /**
     * 
     */
    public function __construct()
    {
        $this->_router = new EhrlichAndreas_Util_Mvc_Router();
        
        $this->_view = new EhrlichAndreas_Util_Mvc_View();
    }
    
    /**
     * 
     * @param array $config
     * @param  string      $section Name of the config section containing view's definitions
     * @return EhrlichAndreas_Util_Mvc
     */
    public function addRouterConfig($config, $section = null)
    {
        $this->_router->addConfig($config, $section);
        
        return $this;
    }
    
    /**
     * 
     * @param array $config
     * @param  string      $section Name of the config section containing view's definitions
     * @return EhrlichAndreas_Util_Mvc
     */
    public function addViewConfig($config, $section)
    {
        $this->_view->addConfig($config, $section);
        
        return $this;
    }
    
    /**
     * TODO
     * 
     * @return type
     */
    public function dispatch()
    {
		$returnResponse = $this->returnResponse();
        
        $request = new EhrlichAndreas_Util_Mvc_Request();
        
        $response = $this->runByParameter($request);
        
        return $response;
    }

    /**
     * Retrieve the currently set base URL
     *
     * @return string
     */
    public function getBaseUrl()
    {
        $request = $this->getRequest();
        
        if ((null !== $request) && method_exists($request, 'getBaseUrl'))
        {
            return $request->getBaseUrl();
        }

        return $this->_baseUrl;
    }

    /**
     * Singleton instance
     *
     * @return EhrlichAndreas_Util_Mvc
     */
    public static function getInstance()
    {
        if (null === self::$_instance)
        {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Return the request object.
     *
     * @return null|EhrlichAndreas_Util_Mvc_Controller
     */
    public function getRequest()
    {
        return $this->_request;
    }
    
    /**
     * 
     * @return EhrlichAndreas_Util_Mvc_Router
     */
    public function getRouter()
    {
        return $this->_router;
    }

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
    
    /**
     * 
     * @return EhrlichAndreas_Util_Mvc_View
     */
    public function getView()
    {
        return $this->_view;
    }
    
    public function runByParameter($invokeParams = null)
    {
        if (is_null($invokeParams))
        {
            return false;
        }
        
        if (!EhrlichAndreas_Util_Object::isInstanceOf($invokeParams, 'EhrlichAndreas_Util_Mvc_Request'))
        {
            $invokeParams = EhrlichAndreas_Util_Array::objectToArray($invokeParams);
            
            $request = new EhrlichAndreas_Util_Mvc_Request();
            
            $this->_router->setRequestParams($request, $invokeParams);
        }
        else
        {
            $request = $invokeParams;
        }
        
        $class = implode('_', array
        (
            ucfirst($request->getModuleName()),
            ucfirst($request->getSubmoduleName()),
            ucfirst($request->getControllerName()) . 'Controller',
        ));
        
        $action = $request->getActionName();
        
        $controller = new $class($request);
        
        return $controller->dispatch($action);
    }

    /**
     * Set the base URL used for requests
     *
     * Use to set the base URL segment of the REQUEST_URI to use when
     * determining PATH_INFO, etc. Examples:
     * - /admin
     * - /myapp
     * - /subdir/index.php
     *
     * Note that the URL should not include the full URI. Do not use:
     * - http://example.com/admin
     * - http://example.com/myapp
     * - http://example.com/subdir/index.php
     *
     * If a null value is passed, this can be used as well for autodiscovery (default).
     *
     * @param string $base
     * @return EhrlichAndreas_Util_Mvc
     * @throws EhrlichAndreas_Util_Exception for non-string $base
     */
    public function setBaseUrl($base = null)
    {
        if (!is_string($base) && (null !== $base))
        {
            throw new Zend_Controller_Exception('Rewrite base must be a string');
        }

        $this->_baseUrl = $base;

        if ((null !== ($request = $this->getRequest())) && (method_exists($request, 'setBaseUrl')))
        {
            $request->setBaseUrl($base);
        }

        return $this;
    }

    /**
     * Set request class/object
     *
     * Set the request object.  The request holds the request environment.
     *
     * If a class name is provided, it will instantiate it
     *
     * @param string|EhrlichAndreas_Util_Mvc_Request $request
     * @throws EhrlichAndreas_Util_Exception if invalid request class
     * @return EhrlichAndreas_Util_Mvc
     */
    public function setRequest($request)
    {
        if (is_string($request))
        {
            $request = new $request();
        }

        $this->_request = $request;

        return $this;
    }
}

