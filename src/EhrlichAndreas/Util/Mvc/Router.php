<?php

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Mvc_Router 
{
    
    /**
     * URI delimiter
     */
    const URI_DELIMITER = '/';
    
    /**
     * Front controller instance
     * @var EhrlichAndreas_Util_Mvc
     */
    protected $_frontController;

    /**
     * Array of invocation parameters to use when instantiating action
     * controllers
     * @var array
     */
    protected $_invokeParams = array();

    /**
     * Whether or not to use default routes
     *
     * @var boolean
     */
    protected $_useDefaultRoutes = true;

    /**
     * Array of routes to match against
     *
     * @var array
     */
    protected $_routes = array();

    /**
     * Currently matched route
     *
     * @var EhrlichAndreas_Util_Mvc_Route
     */
    protected $_currentRoute = null;

    /**
     * Global parameters given to all routes
     *
     * @var array
     */
    protected $_globalParams = array();

    /**
     * Separator to use with chain names
     *
     * @var string
     */
    protected $_chainNameSeparator = '-';

    /**
     * Determines if request parameters should be used as global parameters
     * inside this router.
     *
     * @var boolean
     */
    protected $_useCurrentParamsAsGlobal = false;

    /**
     * Constructor
     *
     * @param   array $params
     * @return  void
     */
    public function __construct(array $params = array())
    {
        $this->setParams($params);
    }

    /**
     * Get a route frm a config instance
     *
     * @param   array $info
     * @return  EhrlichAndreas_Util_Mvc_Route
     */
    protected function _getRouteFromConfig($info)
    {
        $info = EhrlichAndreas_Util_Array::objectToArray($info);
        
        $class = 'EhrlichAndreas_Util_Mvc_Route';
        
        if (isset($info['type']))
        {
            $class = $info['type'];
        }

        $route = call_user_func(array($class, 'getInstance'), $info);

        if (isset($info->abstract) && $info->abstract && method_exists($route, 'isAbstract'))
        {
            $route->isAbstract(true);
        }

        return $route;
    }

    /**
     * 
     * @param EhrlichAndreas_Util_Mvc_Request $request
     * @param array $params
     * @return void
     */
    protected function _setRequestParams($request, $params)
    {
        if (!is_object($request) || !method_exists($request, 'setParam'))
        {
            return;
        }
        
        foreach ($params as $param => $value)
        {
            $request->setParam($param, $value);

            if (method_exists($request, 'getModuleKey') && method_exists($request, 'setModuleName') && $param === $request->getModuleKey())
            {
                $request->setModuleName($value);
            }
            elseif (method_exists($request, 'getSubmoduleKey') && method_exists($request, 'setSubmoduleKey') && $param === $request->getSubmoduleKey())
            {
                $request->setSubmoduleKey($value);
            }
            elseif (method_exists($request, 'getControllerKey') && method_exists($request, 'setControllerName') && $param === $request->getControllerKey())
            {
                $request->setControllerName($value);
            }
            elseif (method_exists($request, 'getActionKey') && method_exists($request, 'setActionName') && $param === $request->getActionKey())
            {
                $request->setActionName($value);
            }
        }
    }

    /**
     * Create routes out of array configuration
     *
     * Example INI:
     * routes.archive.route = "archive/:year/*"
     * routes.archive.defaults.controller = archive
     * routes.archive.defaults.action = show
     * routes.archive.defaults.year = 2000
     * routes.archive.reqs.year = "\d+"
     *
     * routes.news.type = "EhrlichAndreas_Util_Mvc_Route"
     * routes.news.route = "news"
     * routes.news.defaults.controller = "news"
     * routes.news.defaults.action = "list"
     *
     * And finally after you have created an config array with above ini:
     * $router = new EhrlichAndreas_Util_Mvc_Router();
     * $router->addConfig($config, 'routes');
     *
     * @param  array $config  Configuration object
     * @param  string      $section Name of the config section containing route's definitions
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
            $route = $this->_getRouteFromConfig($info);

            $this->addRoute($name, $route);
        }

        return $this;
    }

    /**
     * Add default routes which are used to mimic basic router behaviour
     *
     * @return EhrlichAndreas_Util_Mvc_Router
     */
    public function addDefaultRoutes()
    {
        return $this;
        
        //TODO
        if (!$this->hasRoute('default'))
        {
            $dispatcher = $this->getFrontController()->getDispatcher();
            
            $request = $this->getFrontController()->getRequest();

            $compat = new EhrlichAndreas_Util_Mvc_Route(array(), $dispatcher, $request);

            $this->_routes = array('default' => $compat) + $this->_routes;
        }

        return $this;
    }

    /**
     * Add route to the route chain
     *
     * If route contains method setRequest(), it is initialized with a request object
     *
     * @param  string                                   $name       Name of the route
     * @param  EhrlichAndreas_Util_Mvc_Route $route     Instance of the route
     * @return EhrlichAndreas_Util_Mvc_Router
     */
    public function addRoute($name, EhrlichAndreas_Util_Mvc_Route $route)
    {
        if (method_exists($route, 'setRequest'))
        {
            $route->setRequest($this->getFrontController()->getRequest());
        }

        $this->_routes[$name] = $route;

        return $this;
    }

    /**
     * Add routes to the route chain
     *
     * @param  array $routes Array of routes with names as keys and routes as values
     * @return EhrlichAndreas_Util_Mvc_Router
     */
    public function addRoutes($routes)
    {
        foreach ($routes as $name => $route)
        {
            $this->addRoute($name, $route);
        }

        return $this;
    }

    /**
     * Generates a URL path that can be used in URL creation, redirection, etc.
     *
     * @param  array $userParams Options passed by a user used to override parameters
     * @param  mixed $name The name of a Route to use
     * @param  bool $reset Whether to reset to the route defaults ignoring URL params
     * @param  bool $encode Tells to encode URL parts on output
     * @throws EhrlichAndreas_Util_Exception
     * @return string Resulting absolute URL path
     */
    public function assemble($userParams, $name = null, $reset = false, $encode = true)
    {
        if (!is_array($userParams))
        {
            throw new EhrlichAndreas_Util_Exception('userParams must be an array');
        }
        
        if ($name == null)
        {
            try
            {
                $name = $this->getCurrentRouteName();
            }
            catch (EhrlichAndreas_Util_Exception $e)
            {
                $name = 'default';
            }
        }

        // Use UNION (+) in order to preserve numeric keys
        $params = $userParams + $this->_globalParams;

        $route = $this->getRoute($name);
        
        $url   = $route->assemble($params, $reset, $encode);

        if (!preg_match('|^[a-z]+://|', $url))
        {
            $url = rtrim($this->getFrontController()->getBaseUrl(), self::URI_DELIMITER) . self::URI_DELIMITER . $url;
        }

        return $url;
    }

    /**
     * Clear the controller parameter stack
     *
     * By default, clears all parameters. If a parameter name is given, clears
     * only that parameter; if an array of parameter names is provided, clears
     * each.
     *
     * @param null|string|array single key or array of keys for params to clear
     * @return EhrlichAndreas_Util_Mvc_Router
     */
    public function clearParams($name = null)
    {
        if (null === $name)
        {
            $this->_invokeParams = array();
        }
        elseif (is_string($name) && isset($this->_invokeParams[$name]))
        {
            unset($this->_invokeParams[$name]);
        }
        elseif (is_array($name))
        {
            foreach ($name as $key)
            {
                if (is_string($key) && isset($this->_invokeParams[$key]))
                {
                    unset($this->_invokeParams[$key]);
                }
            }
        }

        return $this;
    }

    /**
     * Get the separator to use for chain names
     *
     * @return string
     */
    public function getChainNameSeparator()
    {
        return $this->_chainNameSeparator;
    }

    /**
     * Retrieve a currently matched route
     *
     * @throws EhrlichAndreas_Util_Exception
     * @return EhrlichAndreas_Util_Mvc_Route Route object
     */
    public function getCurrentRoute()
    {
        if (!isset($this->_currentRoute))
        {
            throw new EhrlichAndreas_Util_Exception("Current route is not defined");
        }
        
        return $this->getRoute($this->_currentRoute);
    }

    /**
     * Retrieve a name of currently matched route
     *
     * @throws EhrlichAndreas_Util_Exception
     * @return string Route object
     */
    public function getCurrentRouteName()
    {
        if (!isset($this->_currentRoute))
        {
            throw new EhrlichAndreas_Util_Exception("Current route is not defined");
        }
        
        return $this->_currentRoute;
    }

    /**
     * Retrieve Front Controller
     *
     * @return EhrlichAndreas_Util_Mvc
     */
    public function getFrontController()
    {
        // Used cache version if found
        if (null !== $this->_frontController)
        {
            return $this->_frontController;
        }

        $this->_frontController = EhrlichAndreas_Util_Mvc::getInstance();
        
        return $this->_frontController;
    }

    /**
     * Retrieve a single parameter from the controller parameter stack
     *
     * @param string $name
     * @return mixed
     */
    public function getParam($name)
    {
        if(isset($this->_invokeParams[$name]))
        {
            return $this->_invokeParams[$name];
        }

        return null;
    }

    /**
     * Retrieve action controller instantiation parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_invokeParams;
    }

    /**
     * Retrieve a named route
     *
     * @param string $name Name of the route
     * @throws EhrlichAndreas_Util_Exception
     * @return EhrlichAndreas_Util_Mvc_Route Route object
     */
    public function getRoute($name)
    {
        if (!isset($this->_routes[$name]))
        {
            throw new EhrlichAndreas_Util_Exception("Route $name is not defined");
        }

        return $this->_routes[$name];
    }

    /**
     * Retrieve an array of routes added to the route chain
     *
     * @return array All of the defined routes
     */
    public function getRoutes()
    {
        return $this->_routes;
    }

    /**
     * Check if named route exists
     *
     * @param  string $name Name of the route
     * @return boolean
     */
    public function hasRoute($name)
    {
        return isset($this->_routes[$name]);
    }

    /**
     * Remove all standard default routes
     *
     * @return EhrlichAndreas_Util_Mvc_Router
     */
    public function removeDefaultRoutes()
    {
        $this->_useDefaultRoutes = false;

        return $this;
    }

    /**
     * Remove a route from the route chain
     *
     * @param  string $name Name of the route
     * @throws EhrlichAndreas_Util_Exception
     * @return EhrlichAndreas_Util_Mvc_Router
     */
    public function removeRoute($name)
    {
        if (!isset($this->_routes[$name]))
        {
            throw new EhrlichAndreas_Util_Exception("Route $name is not defined");
        }

        unset($this->_routes[$name]);

        return $this;
    }

    /**
     * Find a matching route to the current PATH_INFO and inject
     * returning values to the Request object.
     *
     * @throws EhrlichAndreas_Util_Exception
     * @return EhrlichAndreas_Util_Mvc_Request Request object
     */
    public function route($request)
    {
        if ($this->_useDefaultRoutes)
        {
            $this->addDefaultRoutes();
        }
        
        if (!is_object($request))
        {
            $request = new EhrlichAndreas_Util_Mvc_Request($request);
        }

        // Find the matching route
        $routeMatched = false;
        
        $match = $request->getRequestUri();

        foreach (array_reverse($this->_routes, true) as $name => $route)
        {
            if ($params = $route->match($match))
            {
                $this->_setRequestParams($request, $params);
                
                $this->_currentRoute = $name;
                
                $routeMatched        = true;
                
                break;
            }
        }

        if (!$routeMatched)
        {
             throw new EhrlichAndreas_Util_Exception('No route matched the request', 404);
        }

        if($this->_useCurrentParamsAsGlobal)
        {
            $params = $request->getParams();
            
            foreach($params as $param => $value)
            {
                $this->setGlobalParam($param, $value);
            }
        }

        return $request;
    }

    /**
     * Set the separator to use with chain names
     *
     * @param string $separator The separator to use
     * @return EhrlichAndreas_Util_Mvc_Router
     */
    public function setChainNameSeparator($separator)
    {
        $this->_chainNameSeparator = $separator;

        return $this;
    }

    /**
     * Set Front Controller
     *
     * @param EhrlichAndreas_Util_Mvc $controller
     * @return EhrlichAndreas_Util_Mvc_Router
     */
    public function setFrontController($controller)
    {
        $this->_frontController = $controller;
        
        return $this;
    }

    /**
     * Set a global parameter
     *
     * @param  string $name
     * @param  mixed $value
     * @return EhrlichAndreas_Util_Mvc_Router
     */
    public function setGlobalParam($name, $value)
    {
        $this->_globalParams[$name] = $value;

        return $this;
    }

    /**
     * Add or modify a parameter to use when instantiating an action controller
     *
     * @param string $name
     * @param mixed $value
     * @return EhrlichAndreas_Util_Mvc_Router
     */
    public function setParam($name, $value)
    {
        $name = (string) $name;
        
        $this->_invokeParams[$name] = $value;
        
        return $this;
    }

    /**
     * Set parameters to pass to action controller constructors
     *
     * @param array $params
     * @return EhrlichAndreas_Util_Mvc_Router
     */
    public function setParams(array $params)
    {
        $this->_invokeParams = array_merge($this->_invokeParams, $params);
        
        return $this;
    }

    /**
     * 
     * @param EhrlichAndreas_Util_Mvc_Request $request
     * @param array $params
     * @return void
     */
    public function setRequestParams($request, $params)
    {
        $this->_setRequestParams($request, $params);
    }

    /**
     * Determines/returns whether to use the request parameters as global parameters.
     *
     * @param boolean|null $use
     *           Null/unset when you want to retrieve the current state.
     *           True when request parameters should be global, false otherwise
     * @return boolean|EhrlichAndreas_Util_Mvc_Router
     *              Returns a boolean if first param isn't set, returns an
     *              instance of EhrlichAndreas_Util_Mvc_Router otherwise.
     *
     */
    public function useRequestParametersAsGlobal($use = null)
    {
        if($use === null)
        {
            return $this->_useCurrentParamsAsGlobal;
        }

        $this->_useCurrentParamsAsGlobal = (bool) $use;

        return $this;
    }
}