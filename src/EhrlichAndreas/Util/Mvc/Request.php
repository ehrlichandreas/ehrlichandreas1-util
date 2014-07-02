<?php

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Mvc_Request
{
    
    /**
     * Has the action been dispatched?
     * @var boolean
     */
    protected $_dispatched = false;

    /**
     * Module
     * @var string
     */
    protected $_module;

    /**
     * Module key for retrieving module from params
     * @var string
     */
    protected $_moduleKey = 'module';

    /**
     * Submodule
     * @var string
     */
    protected $_submodule;

    /**
     * Module key for retrieving submodule from params
     * @var string
     */
    protected $_submoduleKey = 'submodule';

    /**
     * Controller
     * @var string
     */
    protected $_controller;

    /**
     * Controller key for retrieving controller from params
     * @var string
     */
    protected $_controllerKey = 'controller';

    /**
     * Action
     * @var string
     */
    protected $_action;

    /**
     * Action key for retrieving action from params
     * @var string
     */
    protected $_actionKey = 'action';
    
    /**
     * Scheme for http
     *
     */
    const SCHEME_HTTP  = 'http';

    /**
     * Scheme for https
     *
     */
    const SCHEME_HTTPS = 'https';

    /**
     * Allowed parameter sources
     * @var array
     */
    protected $_paramSources = array('_GET', '_POST');

    /**
     * REQUEST_URI
     * @var string;
     */
    protected $_requestUri;

    /**
     * Base URL of request
     * @var string
     */
    protected $_baseUrl = null;

    /**
     * Base path of request
     * @var string
     */
    protected $_basePath = null;

    /**
     * PATH_INFO
     * @var string
     */
    protected $_pathInfo = '';

    /**
     * Request parameters
     * @var array
     */
    protected $_params = array();

    /**
     * Raw request body
     * @var string|false
     */
    protected $_rawBody;

    /**
     * Alias keys for request parameters
     * @var array
     */
    protected $_aliases = array();

    /**
     * Constructor
     *
     * If a $uri is passed, the object will attempt to populate itself using
     * that information.
     *
     * @param string $uri
     * @return void
     * @throws EhrlichAndreas_Util_Exception when invalid URI passed
     */
    public function __construct($uri = null)
    {
        if (null !== $uri)
        {
            // Separate the scheme from the scheme-specific parts
            $uri            = explode(':', $uri, 2);
            
            $scheme         = strtolower($uri[0]);
            
            $schemeSpecific = isset($uri[1]) === true ? $uri[1] : '';
        
            // High-level decomposition parser
            $pattern = '~^((//)([^/?#]*))([^?#]*)(\?([^#]*))?(#(.*))?$~';
            
            $status  = @preg_match($pattern, $schemeSpecific, $matches);
            
            if ($status === false)
            {
                throw new EhrlichAndreas_Util_Exception('Internal error: scheme-specific decomposition failed');
            }

            // Failed decomposition; no further processing needed
            if ($status === false)
            {
                return;
            }

            // Save URI components that need no further decomposition
            $path     = isset($matches[4]) === true ? $matches[4] : '';
            
            $query    = isset($matches[6]) === true ? $matches[6] : '';
            
            $fragment = isset($matches[8]) === true ? $matches[8] : '';

            // Additional decomposition to get username, password, host, and port
            $combo   = isset($matches[3]) === true ? $matches[3] : '';
            
            $pattern = '~^(([^:@]*)(:([^@]*))?@)?((?(?=[[])[[][^]]+[]]|[^:]+))(:(.*))?$~';
            
            $status  = @preg_match($pattern, $combo, $matches);
            
            if ($status === false)
            {
                throw new EhrlichAndreas_Util_Exception('Internal error: authority decomposition failed');
            }

            // Save remaining URI components
            $username = isset($matches[2]) === true ? $matches[2] : '';
            
            $password = isset($matches[4]) === true ? $matches[4] : '';
            
            $host     = isset($matches[5]) === true 
                             ? preg_replace('~^\[([^]]+)\]$~', '\1', $matches[5])  // Strip wrapper [] from IPv6 literal
                             : '';
            
            $port     = isset($matches[7]) === true ? $matches[7] : '';
            
            if (!empty($query))
            {
                $path .= '?' . $query;
            }

            /*
            print_r($port);
            die();
        
            if (!$uri instanceof Zend_Uri) {
                $uri = Zend_Uri::factory($uri);
            }
            if ($uri->valid()) {
                $path  = $uri->getPath();
                $query = $uri->getQuery();
                if (!empty($query)) {
                    $path .= '?' . $query;
                }

                $this->setRequestUri($path);
            } else {
                throw new EhrlichAndreas_Util_Exception('Invalid URI provided to constructor');
            }
             * 
             */
        }
        else
        {
            $this->setRequestUri();
        }
    }

    /**
     * Access values contained in the superglobals as public members
     * Order of precedence: 1. GET, 2. POST, 3. COOKIE, 4. SERVER, 5. ENV
     *
     * @see http://msdn.microsoft.com/en-us/library/system.web.httprequest.item.aspx
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->_params[$key]))
        {
            return $this->_params[$key];
        }
        
        if (isset($_GET[$key]))
        {
            return $_GET[$key];
        }
        
        if (isset($_POST[$key]))
        {
            return $_POST[$key];
        }
        
        if (isset($_COOKIE[$key]))
        {
            return $_COOKIE[$key];
        }
        
        if ($key == 'REQUEST_URI')
        {
            return $this->getRequestUri();
        }
        
        if ($key == 'PATH_INFO')
        {
            return $this->getPathInfo();
        }
        
        if (isset($_SERVER[$key]))
        {
            return $_SERVER[$key];
        }
        
        if (isset($_ENV[$key]))
        {
            return $_ENV[$key];
        }
        
        return null;
    }

    /**
     * Set values
     *
     * In order to follow {@link __get()}, which operates on a number of
     * superglobals, setting values through overloading is not allowed and will
     * raise an exception. Use setParam() instead.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     * @throws EhrlichAndreas_Util_Exception
     */
    public function __set($key, $value)
    {
        throw new EhrlichAndreas_Util_Exception('Setting values in superglobals not allowed; please use setParam()');
    }

    /**
     * Unset all user parameters
     *
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function clearParams()
    {
        $this->_params = array();
        
        return $this;
    }

    /**
     * Alias to __get
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->__get($key);
    }

    /**
     * Retrieve the action key
     *
     * @return string
     */
    public function getActionKey()
    {
        return $this->_actionKey;
    }

    /**
     * Retrieve the action name
     *
     * @return string
     */
    public function getActionName()
    {
        if (null === $this->_action)
        {
            $this->_action = $this->getParam($this->getActionKey());
        }

        return $this->_action;
    }

    /**
     * Retrieve the controller key
     *
     * @return string
     */
    public function getControllerKey()
    {
        return $this->_controllerKey;
    }

    /**
     * Retrieve the controller name
     *
     * @return string
     */
    public function getControllerName()
    {
        if (null === $this->_controller)
        {
            $this->_controller = $this->getParam($this->getControllerKey());
        }

        return $this->_controller;
    }

    /**
     * Retrieve a member of the $_COOKIE superglobal
     *
     * If no $key is passed, returns the entire $_COOKIE array.
     *
     * @todo How to retrieve from nested arrays
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getCookie($key = null, $default = null)
    {
        if (null === $key)
        {
            return $_COOKIE;
        }

        return (isset($_COOKIE[$key])) ? $_COOKIE[$key] : $default;
    }

    /**
     * Retrieve a member of the $_ENV superglobal
     *
     * If no $key is passed, returns the entire $_ENV array.
     *
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getEnv($key = null, $default = null)
    {
        if (null === $key)
        {
            return $_ENV;
        }

        return (isset($_ENV[$key])) ? $_ENV[$key] : $default;
    }

    /**
     * Retrieve the module key
     *
     * @return string
     */
    public function getModuleKey()
    {
        return $this->_moduleKey;
    }

    /**
     * Retrieve the module name
     *
     * @return string
     */
    public function getModuleName()
    {
        if (null === $this->_module)
        {
            $this->_module = $this->getParam($this->getModuleKey());
        }

        return $this->_module;
    }

    /**
     * Get an action parameter
     *
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed
     */
    public function getParam($key, $default = null)
    {
        $key = (string) $key;
        
        if (isset($this->_params[$key]))
        {
            return $this->_params[$key];
        }

        return $default;
    }

    /**
     * Get all action parameters
     *
     * @return array
     */
     public function getParams()
     {
         return $this->_params;
     }

    /**
     * Retrieve a member of the $_POST superglobal
     *
     * If no $key is passed, returns the entire $_POST array.
     *
     * @todo How to retrieve from nested arrays
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getPost($key = null, $default = null)
    {
        if (null === $key)
        {
            return $_POST;
        }

        return (isset($_POST[$key])) ? $_POST[$key] : $default;
    }

    /**
     * Retrieve a member of the $_GET superglobal
     *
     * If no $key is passed, returns the entire $_GET array.
     *
     * @todo How to retrieve from nested arrays
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getQuery($key = null, $default = null)
    {
        if (null === $key)
        {
            return $_GET;
        }

        return (isset($_GET[$key])) ? $_GET[$key] : $default;
    }

    /**
     * Returns the REQUEST_URI taking into account
     * platform differences between Apache and IIS
     *
     * @return string
     */
    public function getRequestUri()
    {
        if (empty($this->_requestUri))
        {
            $this->setRequestUri();
        }

        return $this->_requestUri;
    }

    /**
     * Retrieve a member of the $_SERVER superglobal
     *
     * If no $key is passed, returns the entire $_SERVER array.
     *
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getServer($key = null, $default = null)
    {
        if (null === $key)
        {
            return $_SERVER;
        }

        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
    }

    /**
     * Retrieve the module key
     *
     * @return string
     */
    public function getSubmoduleKey()
    {
        return $this->_moduleKey;
    }

    /**
     * Retrieve the module name
     *
     * @return string
     */
    public function getSubmoduleName()
    {
        if (null === $this->_module)
        {
            $this->_module = $this->getParam($this->getModuleKey());
        }

        return $this->_module;
    }

    /**
     * Retrieve a single user param (i.e, a param specific to the object and not the environment)
     *
     * @param string $key
     * @param string $default Default value to use if key not found
     * @return mixed
     */
    public function getUserParam($key, $default = null)
    {
        if (isset($this->_params[$key]))
        {
            return $this->_params[$key];
        }

        return $default;
    }

    /**
     * Retrieve only user params (i.e, any param specific to the object and not the environment)
     *
     * @return array
     */
    public function getUserParams()
    {
        return $this->_params;
    }

    /**
     * Alias to __isset()
     *
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return $this->__isset($key);
    }

    /**
     * Determine if the request has been dispatched
     *
     * @return boolean
     */
    public function isDispatched()
    {
        return $this->_dispatched;
    }

    /**
     * Check to see if a property is set
     *
     * @param string $key
     * @return boolean
     */
    public function __isset($key)
    {
        if (isset($this->_params[$key]))
        {
            return true;
        }
        
        if (isset($_GET[$key]))
        {
            return true;
        }
        
        if (isset($_POST[$key]))
        {
            return true;
        }
        
        if (isset($_COOKIE[$key]))
        {
            return true;
        }
        
        if (isset($_SERVER[$key]))
        {
            return true;
        }
        
        if (isset($_ENV[$key]))
        {
            return true;
        }
        
        return false;
    }

    /**
     * Alias to __set()
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value)
    {
        return $this->__set($key, $value);
    }

    /**
     * Set the action key
     *
     * @param string $key
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setActionKey($key)
    {
        $this->_actionKey = (string) $key;
        
        return $this;
    }

    /**
     * Set the action name
     *
     * @param string $value
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setActionName($value)
    {
        $this->_action = $value;
        
        if (null === $value)
        {
            $this->setParam($this->getActionKey(), $value);
        }
        
        return $this;
    }

    /**
     * Set the controller key
     *
     * @param string $key
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setControllerKey($key)
    {
        $this->_controllerKey = (string) $key;
        
        return $this;
    }

    /**
     * Set the controller name to use
     *
     * @param string $value
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setControllerName($value)
    {
        $this->_controller = $value;
        
        return $this;
    }

    /**
     * Set flag indicating whether or not request has been dispatched
     *
     * @param boolean $flag
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setDispatched($flag = true)
    {
        $this->_dispatched = $flag ? true : false;
        
        return $this;
    }

    /**
     * Set the module key
     *
     * @param string $key
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setModuleKey($key)
    {
        $this->_moduleKey = (string) $key;
        
        return $this;
    }

    /**
     * Set the module name to use
     *
     * @param string $value
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setModuleName($value)
    {
        $this->_module = $value;
        
        return $this;
    }

    /**
     * Set an action parameter
     *
     * A $value of null will unset the $key if it exists
     *
     * @param string $key
     * @param mixed $value
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setParam($key, $value)
    {
        $key = (string) $key;

        if ((null === $value) && isset($this->_params[$key]))
        {
            unset($this->_params[$key]);
        }
        elseif (null !== $value)
        {
            $this->_params[$key] = $value;
        }

        return $this;
    }

    /**
     * Set action parameters en masse; does not overwrite
     *
     * Null values will unset the associated key.
     *
     * @param array $array
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setParams(array $array)
    {
        $this->_params = $this->_params + (array) $array;

        foreach ($array as $key => $value)
        {
            if (null === $value)
            {
                unset($this->_params[$key]);
            }
        }

        return $this;
    }

    /**
     * Set POST values
     *
     * @param  string|array $spec
     * @param  null|mixed $value
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setPost($spec, $value = null)
    {
        if ((null === $value) && !is_array($spec))
        {
            throw new EhrlichAndreas_Util_Exception('Invalid value passed to setPost(); must be either array of values or key/value pair');
        }
        
        if ((null === $value) && is_array($spec))
        {
            foreach ($spec as $key => $value)
            {
                $this->setPost($key, $value);
            }
            
            return $this;
        }
        
        $_POST[(string) $spec] = $value;
        
        return $this;
    }

    /**
     * Set the REQUEST_URI on which the instance operates
     *
     * If no request URI is passed, uses the value in $_SERVER['REQUEST_URI'],
     * $_SERVER['HTTP_X_REWRITE_URL'], or $_SERVER['ORIG_PATH_INFO'] + $_SERVER['QUERY_STRING'].
     *
     * @param string $requestUri
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setRequestUri($requestUri = null)
    {
        if ($requestUri === null)
        {
            if (isset($_SERVER['HTTP_X_ORIGINAL_URL']))
            {
                // IIS with Microsoft Rewrite Module
                $requestUri = $_SERVER['HTTP_X_ORIGINAL_URL'];
            }
            elseif (isset($_SERVER['HTTP_X_REWRITE_URL']))
            {
                // IIS with ISAPI_Rewrite
                $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
            }
            elseif (
                // IIS7 with URL Rewrite: make sure we get the unencoded url (double slash problem)
                isset($_SERVER['IIS_WasUrlRewritten'])
                && $_SERVER['IIS_WasUrlRewritten'] == '1'
                && isset($_SERVER['UNENCODED_URL'])
                && $_SERVER['UNENCODED_URL'] != ''
                )
            {
                $requestUri = $_SERVER['UNENCODED_URL'];
            }
            elseif (isset($_SERVER['REQUEST_URI']))
            {
                $requestUri = $_SERVER['REQUEST_URI'];
                
                // Http proxy reqs setup request uri with scheme and host [and port] + the url path, only use url path
                $schemeAndHttpHost = $this->getScheme() . '://' . $this->getHttpHost();
                
                if (strpos($requestUri, $schemeAndHttpHost) === 0)
                {
                    $requestUri = substr($requestUri, strlen($schemeAndHttpHost));
                }
            }
            elseif (isset($_SERVER['ORIG_PATH_INFO']))
            {
                // IIS 5.0, PHP as CGI
                $requestUri = $_SERVER['ORIG_PATH_INFO'];
                
                if (!empty($_SERVER['QUERY_STRING']))
                {
                    $requestUri .= '?' . $_SERVER['QUERY_STRING'];
                }
            }
            else
            {
                return $this;
            }
        }
        elseif (!is_string($requestUri))
        {
            return $this;
        }
        else
        {
            // Set GET items, if available
            if (false !== ($pos = strpos($requestUri, '?')))
            {
                // Get key => value pairs and set $_GET
                $query = substr($requestUri, $pos + 1);
                
                parse_str($query, $vars);
                
                $this->setQuery($vars);
            }
        }

        $this->_requestUri = $requestUri;
        
        return $this;
    }

    /**
     * Set GET values
     *
     * @param  string|array $spec
     * @param  null|mixed $value
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setQuery($spec, $value = null)
    {
        if ((null === $value) && !is_array($spec))
        {
            throw new EhrlichAndreas_Util_Exception('Invalid value passed to setQuery(); must be either array of values or key/value pair');
        }
        
        if ((null === $value) && is_array($spec))
        {
            foreach ($spec as $key => $value)
            {
                $this->setQuery($key, $value);
            }
            
            return $this;
        }
        
        $_GET[(string) $spec] = $value;
        
        return $this;
    }

    /**
     * Set the module key
     *
     * @param string $key
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setSubmoduleKey($key)
    {
        $this->_moduleKey = (string) $key;
        
        return $this;
    }

    /**
     * Set the module name to use
     *
     * @param string $value
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setSubmoduleName($value)
    {
        $this->_module = $value;
        
        return $this;
    }
}