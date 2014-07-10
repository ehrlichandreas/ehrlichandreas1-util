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
    protected $_module = 'default';

    /**
     * Module key for retrieving module from params
     * @var string
     */
    protected $_moduleKey = 'module';

    /**
     * Submodule
     * @var string
     */
    protected $_submodule = 'default';

    /**
     * Module key for retrieving submodule from params
     * @var string
     */
    protected $_submoduleKey = 'submodule';

    /**
     * Controller
     * @var string
     */
    protected $_controller = 'index';

    /**
     * Controller key for retrieving controller from params
     * @var string
     */
    protected $_controllerKey = 'controller';

    /**
     * Action
     * @var string
     */
    protected $_action = 'index';

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
            
            $this->setRequestUri($path);

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
     * Retrieve an alias
     *
     * Retrieve the actual key represented by the alias $name.
     *
     * @param string $name
     * @return string|null Returns null when no alias exists
     */
    public function getAlias($name)
    {
        if (isset($this->_aliases[$name]))
        {
            return $this->_aliases[$name];
        }

        return null;
    }

    /**
     * Retrieve the list of all aliases
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->_aliases;
    }

    /**
     * Everything in REQUEST_URI before PATH_INFO not including the filename
     * <img src="<?=$basePath?>/images/zend.png"/>
     *
     * @return string
     */
    public function getBasePath()
    {
        if (null === $this->_basePath)
        {
            $this->setBasePath();
        }

        return $this->_basePath;
    }

    /**
     * Everything in REQUEST_URI before PATH_INFO
     * <form action="<?=$baseUrl?>/news/submit" method="POST"/>
     *
     * @return string
     */
    public function getBaseUrl($raw = false)
    {
        if (null === $this->_baseUrl)
        {
            $this->setBaseUrl();
        }

        return (($raw == false) ? urldecode($this->_baseUrl) : $this->_baseUrl);
    }

    /**
     * Get the client's IP addres
     *
     * @param  boolean $checkProxy
     * @return string
     */
    public function getClientIp($checkProxy = true)
    {
        if ($checkProxy && $this->getServer('HTTP_CLIENT_IP') != null)
        {
            $ip = $this->getServer('HTTP_CLIENT_IP');
        }
        else if ($checkProxy && $this->getServer('HTTP_X_FORWARDED_FOR') != null)
        {
            $ip = $this->getServer('HTTP_X_FORWARDED_FOR');
        }
        else
        {
            $ip = $this->getServer('REMOTE_ADDR');
        }

        return $ip;
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
     * Return the value of the given HTTP header. Pass the header name as the
     * plain, HTTP-specified header name. Ex.: Ask for 'Accept' to get the
     * Accept header, 'Accept-Encoding' to get the Accept-Encoding header.
     *
     * @param string $header HTTP header name
     * @return string|false HTTP header value, or false if not found
     * @throws EhrlichAndreas_Util_Exception
     */
    public function getHeader($header)
    {
        if (empty($header))
        {
            throw new EhrlichAndreas_Util_Exception('An HTTP header name is required');
        }

        // Try to get it from the $_SERVER array first
        $temp = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        
        if (isset($_SERVER[$temp]))
        {
            return $_SERVER[$temp];
        }

        // This seems to be the only way to get the Authorization header on
        // Apache
        if (function_exists('apache_request_headers'))
        {
            $headers = apache_request_headers();
            
            if (isset($headers[$header]))
            {
                return $headers[$header];
            }
            
            $header = strtolower($header);
            
            foreach ($headers as $key => $value)
            {
                if (strtolower($key) == $header)
                {
                    return $value;
                }
            }
        }

        return false;
    }

    /**
     * Get the HTTP host.
     *
     * "Host" ":" host [ ":" port ] ; Section 3.2.2
     * Note the HTTP Host header is not the same as the URI host.
     * It includes the port while the URI host doesn't.
     *
     * @return string
     */
    public function getHttpHost()
    {
        $host = $this->getServer('HTTP_HOST');
        
        if (!empty($host))
        {
            return $host;
        }

        $scheme = $this->getScheme();
        
        $name   = $this->getServer('SERVER_NAME');
        
        $port   = $this->getServer('SERVER_PORT');

        if(null === $name)
        {
            return '';
        }
        elseif (($scheme == self::SCHEME_HTTP && $port == 80) || ($scheme == self::SCHEME_HTTPS && $port == 443))
        {
            return $name;
        }
        else
        {
            return $name . ':' . $port;
        }
    }

    /**
     * Return the method by which the request was made
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->getServer('REQUEST_METHOD');
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
     * Retrieve a parameter
     *
     * Retrieves a parameter from the instance. Priority is in the order of
     * userland parameters (see {@link setParam()}), $_GET, $_POST. If a
     * parameter matching the $key is not found, null is returned.
     *
     * If the $key is an alias, the actual key aliased will be used.
     *
     * @param mixed $key
     * @param mixed $default Default value to use if key not found
     * @return mixed
     */
    public function getParam($key, $default = null)
    {
        $keyName = (null !== ($alias = $this->getAlias($key))) ? $alias : $key;

        $paramSources = $this->getParamSources();
        
        if (isset($this->_params[$keyName]))
        {
            return $this->_params[$keyName];
        }
        elseif (in_array('_GET', $paramSources) && (isset($_GET[$keyName])))
        {
            return $_GET[$keyName];
        }
        elseif (in_array('_POST', $paramSources) && (isset($_POST[$keyName])))
        {
            return $_POST[$keyName];
        }

        return $default;
    }

    /**
     * Retrieve an array of parameters
     *
     * Retrieves a merged array of parameters, with precedence of userland
     * params (see {@link setParam()}), $_GET, $_POST (i.e., values in the
     * userland params will take precedence over all others).
     *
     * @return array
     */
    public function getParams()
    {
        $return       = $this->_params;
        
        $paramSources = $this->getParamSources();
        
        if (in_array('_GET', $paramSources) && isset($_GET) && is_array($_GET))
        {
            $return += $_GET;
        }
        
        if (in_array('_POST', $paramSources) && isset($_POST) && is_array($_POST))
        {
            $return += $_POST;
        }
        
        return $return;
    }

    /**
     * Returns everything between the BaseUrl and QueryString.
     * This value is calculated instead of reading PATH_INFO
     * directly from $_SERVER due to cross-platform differences.
     *
     * @return string
     */
    public function getPathInfo()
    {
        if (empty($this->_pathInfo))
        {
            $this->setPathInfo();
        }

        return $this->_pathInfo;
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
     * Return the raw body of the request, if present
     *
     * @return string|false Raw body, or false if not present
     */
    public function getRawBody()
    {
        if (null === $this->_rawBody)
        {
            $body = file_get_contents('php://input');

            if (strlen(trim($body)) > 0)
            {
                $this->_rawBody = $body;
            }
            else
            {
                $this->_rawBody = false;
            }
        }
        
        return $this->_rawBody;
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
     * Get the request URI scheme
     *
     * @return string
     */
    public function getScheme()
    {
        return ($this->getServer('HTTPS') == 'on') ? self::SCHEME_HTTPS : self::SCHEME_HTTP;
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
     * Was the request made by DELETE?
     *
     * @return boolean
     */
    public function isDelete()
    {
        if ('DELETE' == $this->getMethod())
        {
            return true;
        }

        return false;
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
     * Is this a Flash request?
     *
     * @return boolean
     */
    public function isFlashRequest()
    {
        $header = strtolower($this->getHeader('USER_AGENT'));
        
        return (strstr($header, ' flash')) ? true : false;
    }

    /**
     * Was the request made by GET?
     *
     * @return boolean
     */
    public function isGet()
    {
        if ('GET' == $this->getMethod())
        {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by HEAD?
     *
     * @return boolean
     */
    public function isHead()
    {
        if ('HEAD' == $this->getMethod())
        {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by OPTIONS?
     *
     * @return boolean
     */
    public function isOptions()
    {
        if ('OPTIONS' == $this->getMethod())
        {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by POST?
     *
     * @return boolean
     */
    public function isPost()
    {
        if ('POST' == $this->getMethod())
        {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by PUT?
     *
     * @return boolean
     */
    public function isPut()
    {
        if ('PUT' == $this->getMethod())
        {
            return true;
        }

        return false;
    }

    /**
     * Is https secure request
     *
     * @return boolean
     */
    public function isSecure()
    {
        return ($this->getScheme() === self::SCHEME_HTTPS);
    }

    /**
     * Is the request a Javascript XMLHttpRequest?
     *
     * Should work with Prototype/Script.aculo.us, possibly others.
     *
     * @return boolean
     */
    public function isXmlHttpRequest()
    {
        return ($this->getHeader('X_REQUESTED_WITH') == 'XMLHttpRequest');
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
     * Set a key alias
     *
     * Set an alias used for key lookups. $name specifies the alias, $target
     * specifies the actual key to use.
     *
     * @param string $name
     * @param string $target
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setAlias($name, $target)
    {
        $this->_aliases[$name] = $target;
        
        return $this;
    }

    /**
     * Set the base path for the URL
     *
     * @param string|null $basePath
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setBasePath($basePath = null)
    {
        if ($basePath === null)
        {
            $filename = (isset($_SERVER['SCRIPT_FILENAME'])) ? basename($_SERVER['SCRIPT_FILENAME']) : '';

            $baseUrl = $this->getBaseUrl();
            
            if (empty($baseUrl))
            {
                $this->_basePath = '';
                
                return $this;
            }

            if (basename($baseUrl) === $filename)
            {
                $basePath = dirname($baseUrl);
            }
            else
            {
                $basePath = $baseUrl;
            }
        }

        if (substr(PHP_OS, 0, 3) === 'WIN')
        {
            $basePath = str_replace('\\', '/', $basePath);
        }

        $this->_basePath = rtrim($basePath, '/');
        
        return $this;
    }

    /**
     * Set the base URL of the request; i.e., the segment leading to the script name
     *
     * E.g.:
     * - /admin
     * - /myapp
     * - /subdir/index.php
     *
     * Do not use the full URI when providing the base. The following are
     * examples of what not to use:
     * - http://example.com/admin (should be just /admin)
     * - http://example.com/subdir/index.php (should be just /subdir/index.php)
     *
     * If no $baseUrl is provided, attempts to determine the base URL from the
     * environment, using SCRIPT_FILENAME, SCRIPT_NAME, PHP_SELF, and
     * ORIG_SCRIPT_NAME in its determination.
     *
     * @param mixed $baseUrl
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setBaseUrl($baseUrl = null)
    {
        if ((null !== $baseUrl) && !is_string($baseUrl))
        {
            return $this;
        }

        if ($baseUrl === null)
        {
            $filename = (isset($_SERVER['SCRIPT_FILENAME'])) ? basename($_SERVER['SCRIPT_FILENAME']) : '';

            if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === $filename)
            {
                $baseUrl = $_SERVER['SCRIPT_NAME'];
            }
            elseif (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === $filename)
            {
                $baseUrl = $_SERVER['PHP_SELF'];
            }
            elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename)
            {
                $baseUrl = $_SERVER['ORIG_SCRIPT_NAME']; // 1and1 shared hosting compatibility
            }
            else
            {
                // Backtrack up the script_filename to find the portion matching
                // php_self
                $path    = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
                
                $file    = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
                
                $segs    = explode('/', trim($file, '/'));
                
                $segs    = array_reverse($segs);
                
                $index   = 0;
                
                $last    = count($segs);
                
                $baseUrl = '';
                
                do
                {
                    $seg     = $segs[$index];
                    
                    $baseUrl = '/' . $seg . $baseUrl;
                    
                    ++$index;
                }
                while (($last > $index) && (false !== ($pos = strpos($path, $baseUrl))) && (0 != $pos));
            }

            // Does the baseUrl have anything in common with the request_uri?
            $requestUri = $this->getRequestUri();

            if (0 === strpos($requestUri, $baseUrl))
            {
                // full $baseUrl matches
                $this->_baseUrl = $baseUrl;
                
                return $this;
            }

            if (0 === strpos($requestUri, dirname($baseUrl)))
            {
                // directory portion of $baseUrl matches
                $this->_baseUrl = rtrim(dirname($baseUrl), '/');
                
                return $this;
            }

            $truncatedRequestUri = $requestUri;
            
            if (($pos = strpos($requestUri, '?')) !== false)
            {
                $truncatedRequestUri = substr($requestUri, 0, $pos);
            }

            $basename = basename($baseUrl);
            
            if (empty($basename) || !strpos($truncatedRequestUri, $basename))
            {
                // no match whatsoever; set it blank
                $this->_baseUrl = '';
                
                return $this;
            }

            // If using mod_rewrite or ISAPI_Rewrite strip the script filename
            // out of baseUrl. $pos !== 0 makes sure it is not matching a value
            // from PATH_INFO or QUERY_STRING
            if ((strlen($requestUri) >= strlen($baseUrl))
                && ((false !== ($pos = strpos($requestUri, $baseUrl))) && ($pos !== 0)))
            {
                $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
            }
        }

        $this->_baseUrl = rtrim($baseUrl, '/');
        
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
        $key = (null !== ($alias = $this->getAlias($key))) ? $alias : $key;
        
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
     * Set allowed parameter sources
     *
     * Can be empty array, or contain one or more of '_GET' or '_POST'.
     *
     * @param  array $paramSoures
     * @return Zend_Controller_Request_Http
     */
    public function setParamSources(array $paramSources = array())
    {
        $this->_paramSources = $paramSources;
        
        return $this;
    }

    /**
     * Set parameters
     *
     * Set one or more parameters. Parameters are set as userland parameters,
     * using the keys specified in the array.
     *
     * @param array $params
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setParams(array $params)
    {
        foreach ($params as $key => $value)
        {
            $this->setParam($key, $value);
        }
        
        return $this;
    }

    /**
     * Set the PATH_INFO string
     *
     * @param string|null $pathInfo
     * @return EhrlichAndreas_Util_Mvc_Request
     */
    public function setPathInfo($pathInfo = null)
    {
        if ($pathInfo === null)
        {
            $baseUrl = $this->getBaseUrl(); // this actually calls setBaseUrl() & setRequestUri()
            
            $baseUrlRaw = $this->getBaseUrl(false);
            
            $baseUrlEncoded = urlencode($baseUrlRaw);
        
            if (null === ($requestUri = $this->getRequestUri()))
            {
                return $this;
            }
        
            // Remove the query string from REQUEST_URI
            if ($pos = strpos($requestUri, '?'))
            {
                $requestUri = substr($requestUri, 0, $pos);
            }
            
            if (!empty($baseUrl) || !empty($baseUrlRaw))
            {
                if (strpos($requestUri, $baseUrl) === 0)
                {
                    $pathInfo = substr($requestUri, strlen($baseUrl));
                }
                elseif (strpos($requestUri, $baseUrlRaw) === 0)
                {
                    $pathInfo = substr($requestUri, strlen($baseUrlRaw));
                }
                elseif (strpos($requestUri, $baseUrlEncoded) === 0)
                {
                    $pathInfo = substr($requestUri, strlen($baseUrlEncoded));
                }
                else
                {
                    $pathInfo = $requestUri;
                }
            }
            else
            {
                $pathInfo = $requestUri;
            }
        
        }

        $this->_pathInfo = (string) $pathInfo;
        
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