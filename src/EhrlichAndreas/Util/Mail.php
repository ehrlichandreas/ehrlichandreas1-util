<?php

//require_once 'EhrlichAndreas/Util/Exception.php';

//require_once 'EhrlichAndreas/Util/Array.php';

//require_once 'EhrlichAndreas/Util/Mime.php';

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Mail
{
	
	/**
	 * @var array
	 */
	protected $configs = array();
	
	
	/**
	 * Gets the configuration object.
	 *
	 * @param  string $name A Name for the configuration object for the Zend_Mail
	 * @return array or false
	 */
	public function getConfig($name = null)
	{
		if (is_null($name))
		{
			return $this->configs;
		}
		
		if (is_scalar($name) && isset($this->configs[$name]))
		{
			foreach ($this->configs[$name]['mail'] as $key => $value)
			{
				if (empty($value['transport']) || !is_object($value['transport']))
				{
					$zfVersion = 0;

					$zfVersion = $this->setupZfVersion($value);
					
					$this->configs[$name]['mail'][$key]['transport'] = $this->setupTransport($value['transport'], $zfVersion);
				}
			}
			
			return $this->configs[$name];
		}
		
		return false;
	}

	/**
	 * Gets the default configuration object for the Zend_Mail.
	 *
	 * @return array or false
	 */
	public function getDefaultConfig()
	{
		return $this->getConfig('default');
	}

	/**
	 * Answers if the configuration object is available or not.
	 *
	 * @param  string $name A Name for the configuration object for the Zend_Mail
	 * @return bool
	 */
	public function issetConfig($name = null)
	{
		if (is_null($name))
		{
			return !empty($this->configs);
		}
		
		if (is_scalar($name) && isset($this->configs[$name]) && !empty($this->configs[$name]))
		{
			return true;
		}
		
		return false;
	}

	/**
	 *
	 * @param  mixed $config An mailing configuration object
	 * @param  string $name A Name for the configuration object for the Zend_Mail
	 * @return EhrlichAndreas_Util_Mail
	 */
	public function setConfig($config = array(), $name = null)
	{
		if (empty($config))
		{
			return $this;
		}
		
		$config = EhrlichAndreas_Util_Array::objectToArray($config);
		
		if (empty($name))
		{
			if (!isset($config['name']))
			{
				foreach ($config as $key=>$value)
				{
					if (isset($value['name']))
					{
						$this->setConfig($value,$value['name']);
					}
					else
					{
						$this->setConfig($value,$key);
					}
				}
				
				return $this;
			}
			
			$name = $config['name'];
		}
		
		$var = array();
		
		if (isset($config['mail']))
		{
			foreach ($config['mail'] as $key => $value)
			{
				if (!is_numeric($key))
				{
					$var[] = $config['mail'];
					
					break;
				}
				else
				{
					$var[] = $value;
				}
			}
		}
		
		$config['mail'] = $var;

		foreach ($config['mail'] as $key => $value)
		{
			$tmp = array
			(
				'encoding'   => 'quoted-printable',
				'charset'    => 'UTF-8',
				'from'       => array
				(
					'email'  => '',
				),
				'replyto'    => array(),
				'to'         => array(),
				'cc'         => array(),
				'bcc'        => array(),
				'header'     => array(),
				'subject'    => '',
				'body'       => array
				(
					'html'   => '',
					'text'   => '',
				),
				'transport'  => NULL,
				'attachment' => false,
				'eol'        => "\r\n",
				'zfversion'  => 0,
			);

			
			if (isset($value['encoding']))
			{
				$tmp['encoding'] = $value['encoding'];
			}

			
			if (isset($value['charset']))
			{
				$tmp['charset'] = $value['charset'];
			}
			
			
			if (isset($value['from']['email']))
			{
				$tmp['from']['email'] = $value['from']['email'];
			}
			
			
			if (isset($value['from']['name']))
			{
				$tmp['from']['name'] = $value['from']['name'];
			}
			
			
			if (isset($value['replyto']['email']))
			{
				$value['replyto']['email'] = trim($value['replyto']['email']);
				
				if (strlen($value['replyto']['email']) > 0)
				{
					$tmp['replyto']['email'] = $value['replyto']['email'];
				}
			}
			
			
			if (isset($value['replyto']['name']))
			{
				$value['replyto']['name'] = trim($value['replyto']['name']);
				
				if (strlen($value['replyto']['name']) > 0)
				{
					$tmp['replyto']['name'] = $value['replyto']['name'];
				}
			}
			
			
			if (isset($value['to']))
			{
				foreach ($value['to'] as $k => $v)
				{
					if (!is_numeric($k))
					{
						$tmp['to'][] = $value['to'];
						
						break;
					}
					else
					{
						$tmp['to'][] = $v;
					}
				}
			}
			
			
			if (isset($value['cc']))
			{
				foreach ($value['cc'] as $k => $v)
				{
					if (!is_numeric($k))
					{
						$tmp['cc'][] = $value['cc'];
						
						break;
					}
					else
					{
						$tmp['cc'][] = $v;
					}
				}
			}
			
			
			if (isset($value['bcc']))
			{
				foreach ($value['bcc'] as $k => $v)
				{
					if (!is_numeric($k))
					{
						$tmp['bcc'][] = $value['bcc'];
						
						break;
					}
					else
					{
						$tmp['bcc'][] = $v;
					}
				}
			}
			
			
			if (isset($value['header']))
			{
				foreach ($value['header'] as $k => $v)
				{
					if (!is_numeric($k))
					{
						$tmp['header'][] = $value['header'];
						
						break;
					}
					else
					{
						$tmp['header'][] = $v;
					}
				}
			}
			
			
			if (isset($value['subject']) && strlen($value['subject']) > 0)
			{
				$tmp['subject'] = $value['subject'];
			}
			
			
			if (isset($value['body']['html']) && strlen($value['body']['html']) > 0)
			{
				$tmp['body']['html'] = $value['body']['html'];
			}
			
			
			if (isset($value['body']['text']) && strlen($value['body']['text']) > 0)
			{
				$tmp['body']['text'] = $value['body']['text'];
			}
			
			
			if (isset($value['eol']) && strlen($value['eol']) > 0)
			{
				$tmp['eol'] = $value['eol'];
			}
			
			
			if (isset($value['attachment']) && strlen($value['attachment']) > 0)
			{
				$tmp['attachment'] = $value['attachment'];
			}
			
			if (empty($tmp['attachment']))
			{
				$tmp['attachment'] = false;
			}
			else
			{
				$tmp['attachment'] = true;
			}
			
			
			if (isset($value['transport']))
			{
				$tmp['transport'] = $value['transport'];
			}
			
			
			$tmp['zfversion'] = $this->setupZfVersion($value);
			
			
			$config['mail'][$key] = $tmp;
		}

		$this->configs[$name] = $config;
		
		return $this;
	}
	
	/**
	 * 
	 * @param array $attachments
	 * @return array
	 */
	protected function setupAttachments($attachments = null)
	{
		if (empty($attachments))
		{
			return array();
		}
		
		$attachments = EhrlichAndreas_Util_Array::objectToArray($attachments);
		
		if (is_scalar($attachments))
		{
			$attachments = array
			(
				$attachments
			);
		}
		
		$unset = false;
		
		foreach ($attachments as $key => $value)
		{
			$unset = false;
		
			if (is_array($value) && count($value) == 0)
			{
				$unset = true;
			}
			
			if (is_scalar($value) && strlen($value) <= 0)
			{
				$unset = true;
			}
			
			if ($unset)
			{
				unset($attachments[$key]);
			}
		}
		
		$attachments = array_values($attachments);
		
		return $attachments;
	}
	
	/**
	 * 
	 * @param array $options
	 * @param int $zfVersion
	 * @return transportName
	 * @throws Zend_Application_Resource_Exception
	 */
	protected function setupZfVersion($options = array())
	{
		$options = EhrlichAndreas_Util_Array::objectToArray($options);
		
		$zfVersion = 0;
		
		if (is_array($options))
		{
			foreach ($options as $key => $value) 
			{
				if (is_scalar($value) && empty($zfVersion) && stripos($key, 'zf') !== false && stripos($key, 'version') !== false)
				{
					$zfVersion = $value;
				}
			}
		}
		
		if (empty($zfVersion))
		{
			$zfVersion = 1;
		}
		
		$zfVersion = intval($zfVersion);

		return $zfVersion;
	}
	
	/**
	 * 
	 * @param array $options
	 * @param int $zfVersion
	 * @return transportName
	 * @throws Zend_Application_Resource_Exception
	 */
	protected function setupTransport($options = array(), $zfVersion = 0)
	{
		$options = EhrlichAndreas_Util_Array::objectToArray($options);
		
		
		$type = '';
		
		$name = '';
		
		$host = '';
		
		$port = '';
		
		$auth = '';
		
		$username = '';
		
		$password = '';
		
		$ssl = '';
		
		$path = '';
		
		$callback = '';

		
		if (isset($options['type']))
		{
			$type = $options['type'];
		}

		if (isset($options['smtp']) && !empty($options['smtp']))
		{
			$type = 'smtp';
		}
		
		if (isset($options['name']))
		{
			$name = $options['name'];
		}

		if (isset($options['host']))
		{
			$host = $options['host'];
		}

		if (isset($options['port']))
		{
			$port = $options['port'];
		}

		if (isset($options['auth']))
		{
			$auth = $options['auth'];
		}
		elseif (isset($options['connection_class']))
		{
			$auth = $options['connection_class'];
		}

		if (isset($options['user']))
		{
			$username = $options['user'];
		}
		elseif (isset($options['username']))
		{
			$username = $options['username'];
		}

		if (isset($options['pass']))
		{
			$password = $options['pass'];
		}
		elseif (isset($options['password']))
		{
			$password = $options['password'];
		}

		if (isset($options['ssl']))
		{
			$ssl = $options['ssl'];
		}

		if (isset($options['path']))
		{
			$path = $options['path'];
		}

		if (isset($options['callback']))
		{
			$callback = $options['callback'];
		}
		
		if (is_array($options))
		{
			foreach ($options as $key => $value)
			{
				if (is_scalar($value))
				{
					if ($type === '' && stripos($key, 'type') !== false)
					{
						$type = $value;
					}

					if ($type === '' && stripos($key, 'smtp') !== false && !empty($value))
					{
						$type = 'smpt';
					}

					if ($name === '' && stripos($key, 'name') !== false && stripos($key, 'host') === false && stripos($key, 'user') === false)
					{
						$name = $value;
					}

					if ($host === '' && stripos($key, 'host') !== false)
					{
						$host = $value;
					}

					if ($port === '' && stripos($key, 'port') !== false)
					{
						$port = $value;
					}

					if ($auth === '' && stripos($key, 'auth') !== false)
					{
						$auth = $value;
					}

					if ($auth === '' && stripos($key, 'connection') !== false && stripos($key, 'class') !== false)
					{
						$auth = $value;
					}

					if ($username === '' && stripos($key, 'user') !== false)
					{
						$username = $value;
					}

					if ($password === '' && stripos($key, 'pass') !== false)
					{
						$password = $value;
					}

					if ($ssl === '' && stripos($key, 'ssl') !== false)
					{
						$ssl = $value;
					}

					if ($path === '' && stripos($key, 'path') !== false)
					{
						$path = $value;
					}

					if ($callback === '' && stripos($key, 'call') !== false && stripos($key, 'back') !== false)
					{
						$callback = $value;
					}
				}
				elseif (($username === '' || $password === '') && stripos($key, 'connection') !== false && stripos($key, 'config') !== false)
				{
					foreach ($value as $k => $v)
					{
						if (is_scalar($v))
						{
							if ($username === '' && stripos($k, 'user') !== false)
							{
								$username = $v;
							}

							if ($password === '' && stripos($k, 'pass') !== false)
							{
								$password = $v;
							}

							if ($ssl === '' && stripos($k, 'ssl') !== false)
							{
								$ssl = $v;
							}
						}
					}
				}
			}
		}
		
		
		if (empty($zfVersion))
		{
			$zfVersion = 1;
		}
		
		$zfVersion = intval($zfVersion);
		
		
		$type = strtolower($type);
		
		$port = strtolower($port);
		
		$auth = strtolower($auth);
		
		$ssl  = strtolower($ssl);
		
			
		if (empty($port))
		{
			$port = 25;
		}
		
		
		if ($zfVersion == 1)
		{
			//require_once 'Zend/Loader/Autoloader.php';
			
			$options = array
			(
				'host' => $host,
				'port' => $port,
				'auth' => $auth,
				'username' => $username,
				'password' => $password,
			);
			
			
			if (!empty($type) && in_array($type, array('smtp', 'file', 'sendmail')))
			{
				$options['type'] = $type;
			}
			else
			{
				$options['type'] = 'sendmail';
			}
			
			if (!empty($ssl) && in_array($ssl, array('tls', 'ssl')))
			{
				$options['ssl'] = $ssl;
			}
			
			if (!empty($path))
			{
				$options['path'] = $path;
			}
			
			if (!empty($callback) && is_callable($callback))
			{
				$options['callback'] = $callback;
			}
			
			
			if(!isset($options['type']))
			{
				$options['type'] = 'sendmail';
			}

			$transportName = $options['type'];
            
            ob_start();
			
			if(!Zend_Loader_Autoloader::autoload($transportName))
			{
				$transportName = ucfirst(strtolower($transportName));

				if(!Zend_Loader_Autoloader::autoload($transportName))
				{
					$transportName = 'Zend_Mail_Transport_' . $transportName;
                    
					if(!Zend_Loader_Autoloader::autoload($transportName))
					{
						throw new Zend_Application_Resource_Exception(
							"Specified Mail Transport '{$transportName}'"
							. 'could not be found'
						);
					}
				}
			}
            
            ob_end_clean();

			unset($options['type']);

			switch($transportName)
			{
				case 'Zend_Mail_Transport_Smtp':
					if(!isset($options['host']))
					{
						throw new Zend_Application_Resource_Exception(
							'A host is necessary for smtp transport,'
							.' but none was given');
					}
                    
                    $optionsTmp = $options;
                    
                    unset($optionsTmp['smtp']);
                    
                    unset($optionsTmp['host']);
                    
                    if (!isset($optionsTmp['name']))
                    {
                        $optionsTmp['name'] = '127.0.0.1';
                    }

					$transport = new $transportName($options['host'], $optionsTmp);
					
					break;
				
				case 'Zend_Mail_Transport_Sendmail':
					
				default:
                    $options['port'] = '';
                    
					$transport = new $transportName($options);
					
					break;
			
			}

			return $transport;
		}
		elseif ($zfVersion == 2)
		{
			switch($type)
			{
				case 'smtp':
					$options = array
					(
						'name'              => $name,
						'host'              => $host,
						'port'              => $port,
						'connection_class'  => $auth,
						'connection_config' => array
						(
							'username'      => $username,
							'password'      => $password,
						),
					);
					
					if (!empty($ssl) && in_array($ssl, array('tls', 'ssl')))
					{
						$options['connection_config']['ssl'] = $ssl;
					}
                    
                    $classSmtpOptions = 'Zend\\Mail\\Transport\\SmtpOptions';
					
					$transportOptions = new $classSmtpOptions();
					
					$transportOptions->setFromArray($options);
					
                    $classSmtp = 'Zend\\Mail\\Transport\\Smtp';
					
					$transport = new $classSmtp();

					$transport->setOptions($transportOptions);
					
					break;
					
				case 'file':
					$options = array
					(
						'path'              => $path,
					);
			
					if (!empty($callback) && is_callable($callback))
					{
						$options['callback'] = $callback;
					}
                    
                    $classFileOptions = 'Zend\\Mail\\Transport\\FileOptions';
					
					$transportOptions = new $classFileOptions();
					
					$transportOptions->setFromArray($options);
					
                    $classFile = 'Zend\\Mail\\Transport\\File';
					
					$transport = new $classFile();

					$transport->setOptions($transportOptions);
					
					break;
					
				default:
                    $classSendmail = 'Zend\\Mail\\Transport\\Sendmail';
                    
					$transport = new $classSendmail();
					
					break;
			}

			return $transport;
		}
	}

	/**
	 * Sets the default configuration for the Zend_Mail.
	 *
	 * @param  mixed $config
	 * @return EhrlichAndreas_Util_Mail
	 */
	public function setDefaultConfig($config = null)
	{
		return $this->setConfig($config, 'default');
	}

	/**
	 *
	 * @param mixed $config
	 * @param array $replacement
	 * @param array $attachment
	 * @param bool $log
	 * @param integer $vsprintfTimes
	 * @param callback $filter
	 * @return bool
	 */
	public function send($config = null, $replacement = array(), $attachment = array(), $log = false, $vsprintfTimes = 1, $filter = null)
	{
		if (is_scalar($config))
		{
			$config = $this->getConfig($config);
		}
		
		if (isset($config['mail']))
		{
			$config = $config['mail'];
		}

		//TODO
		if (count($config) > 0 && count($attachment) > 0)
		{
			foreach ($attachment as $att)
			{
				if ($att->filename)
				{
					$extension = strtolower(pathinfo(strval($att->filename), PATHINFO_EXTENSION));
					
					if (isset(EhrlichAndreas_Util_Mime::$MIME_TYPE[$extension]))
					{
						$att->type = EhrlichAndreas_Util_Mime::$MIME_TYPE[$extension];
					}
				}
			}
		}

        if (!is_null($filter))
        {
            if ($config[0]['zfversion'] == 1)
            {
                $tmp = Zend_Json::encode($replacement);
            }
            elseif ($config[0]['zfversion'] == 2)
            {
                $callback = array
                (
                    'Zend\\Json\\Json',
                    'encode',
                );

                $param_arr = array
                (
                    $tmp,
                );

                $tmp = call_user_func_array($callback, $param_arr);
            }

            $tmp = base64_encode($tmp);

            $tmp = ' replacement=\''.$tmp.'\'';
        }

        $send = true;
        
		foreach ($config as $conf)
		{
			$fromEmail = EhrlichAndreas_Util_Vsprintf::vsprintf($conf['from']['email'], $replacement, $vsprintfTimes);
			
			$fromName = null;
		
			$replytoEmail = null;
			
			$replytoName = null;
		
			$toEmail = array();
			
			$toName = array();
		
			$ccEmail = array();
			
			$ccName = array();
		
			$bccEmail = array();
			
			$bccName = array();
			
			$header = array();
			
			$subject = null;
			
			$bodyHtml = null;
			
			$bodyText = null;
			
			
			if (isset($conf['from']['name']))
			{
				$fromName = EhrlichAndreas_Util_Vsprintf::vsprintf($conf['from']['name'], $replacement, $vsprintfTimes);
			}
			
			if (isset($conf['replyto']['email']))
			{
				$replytoEmail = EhrlichAndreas_Util_Vsprintf::vsprintf($conf['replyto']['email'], $replacement, $vsprintfTimes);
			}
			
			if (isset($conf['replyto']['name']))
			{
				$replytoName = EhrlichAndreas_Util_Vsprintf::vsprintf($conf['replyto']['name'], $replacement, $vsprintfTimes);
			}
			
			if (count($conf['to']) > 0)
			{
				foreach ($conf['to'] as $key => $value)
				{
					$email = EhrlichAndreas_Util_Vsprintf::vsprintf($value['email'], $replacement, $vsprintfTimes);
					
					$name = '';
					
					if (isset($value['name']))
					{
						$name = EhrlichAndreas_Util_Vsprintf::vsprintf($value['name'], $replacement, $vsprintfTimes);
					}
					
					//TODO
					/*
					if (isset($value['name']) && $value['name'] === $name)
					{
						$name = null;
					}
					 */
					
					$toEmail[$key] = $email;
					
					$toName[$key] = $name;
				}
			}
			
			if (count($conf['cc']) > 0)
			{
				foreach ($conf['cc'] as $key => $value)
				{
					$email = EhrlichAndreas_Util_Vsprintf::vsprintf($value['email'], $replacement, $vsprintfTimes);
					
					$name = '';
					
					if (isset($value['name']))
					{
						$name = EhrlichAndreas_Util_Vsprintf::vsprintf($value['name'], $replacement, $vsprintfTimes);
					}
					
					$ccEmail[$key] = $email;
					
					$ccName[$key] = $name;
				}
			}
			
			if (count($conf['bcc']) > 0)
			{
				foreach ($conf['bcc'] as $key => $value)
				{
					$email = EhrlichAndreas_Util_Vsprintf::vsprintf($value['email'], $replacement, $vsprintfTimes);
					
					$name = '';
					
					if (isset($value['name']))
					{
						$name = EhrlichAndreas_Util_Vsprintf::vsprintf($value['name'], $replacement, $vsprintfTimes);
					}
					
					$bccEmail[$key] = $email;
					
					$bccName[$key] = $name;
				}
			}
			
			if (count($conf['header']) > 0)
			{
				foreach ($conf['header'] as $key => $value)
				{
					if (!isset($value['append']))
					{
						$value['append'] = false;
					}
					
					$header[] = $value;
				}
			}
			
			if (isset($conf['subject']) && strlen($conf['subject']) > 0)
			{
                $subject = $conf['subject'];
                
                if (!is_null($filter))
                {
                    $subject = str_replace(' replacement=\'%replacement%\'', $tmp, $subject);
                }
                else
                {
                    $subject = preg_replace('#\\[base64.*\\[\\/base64\\]#ui', '', $subject);
                }
                
                if (!is_null($filter))
                {
                    $subject = str_replace(' replacement=\'%replacement%\'', $tmp, $subject);
                    
                    $subject = EhrlichAndreas_Util_Vsprintf::vsprintf($subject, $replacement, $vsprintfTimes);
                }
                else
                {
                    $subject = EhrlichAndreas_Util_Vsprintf::vsprintf($subject, $replacement, 1);
                    
                    $subject = preg_replace('#\\[base64.*\\[\\/base64\\]#ui', '', $subject);
                    
                    $subject = EhrlichAndreas_Util_Vsprintf::vsprintf($subject, $replacement, $vsprintfTimes - 1);
                }
			}
            
            if (!is_null($subject) && !is_null($filter))
            {
                $subject = call_user_func_array($filter, array($subject));
            }
			
			if (isset($conf['body']['html']) && strlen($conf['body']['html']) > 0)
			{
                $bodyHtml = $conf['body']['html'];
                
                if (!is_null($filter))
                {
                    $bodyHtml = str_replace(' replacement=\'%replacement%\'', $tmp, $bodyHtml);
                    
                    $bodyHtml = EhrlichAndreas_Util_Vsprintf::vsprintf($bodyHtml, $replacement, $vsprintfTimes);
                }
                else
                {
                    $bodyHtml = EhrlichAndreas_Util_Vsprintf::vsprintf($bodyHtml, $replacement, 1);
                    
                    $bodyHtml = preg_replace('#\\[base64.*\\[\\/base64\\]#ui', '', $bodyHtml);
                    
                    $bodyHtml = EhrlichAndreas_Util_Vsprintf::vsprintf($bodyHtml, $replacement, $vsprintfTimes - 1);
                }
			}
            
            if (!is_null($bodyHtml) && !is_null($filter))
            {
                $bodyHtml = call_user_func_array($filter, array($bodyHtml));
            }
			
            if (!is_null($bodyHtml))
            {
				$bodyHtml = preg_replace('#\r?\n|\r\n?#ui', $conf['eol'], $bodyHtml);
            }
			
			if (isset($conf['body']['text']) && strlen($conf['body']['text']) > 0)
			{
                $bodyText = $conf['body']['text'];
                
                if (!is_null($filter))
                {
                    $bodyText = str_replace(' replacement=\'%replacement%\'', $tmp, $bodyText);
                    
                    $bodyText = EhrlichAndreas_Util_Vsprintf::vsprintf($bodyText, $replacement, $vsprintfTimes);
                }
                else
                {
                    $bodyText = EhrlichAndreas_Util_Vsprintf::vsprintf($bodyText, $replacement, 1);
                    
                    $bodyText = preg_replace('#\\[base64.*\\[\\/base64\\]#ui', '', $bodyText);
                    
                    $bodyText = EhrlichAndreas_Util_Vsprintf::vsprintf($bodyText, $replacement, $vsprintfTimes - 1);
                }
                
				$bodyText = EhrlichAndreas_Util_Vsprintf::vsprintf($bodyText, $replacement, $vsprintfTimes);
			}
            
            if (!is_null($bodyText) && !is_null($filter))
            {
                $bodyText = call_user_func_array($filter, array($bodyText));
            }
			
            if (!is_null($bodyText))
            {
				$bodyText = preg_replace('#\r?\n|\r\n?#ui', $conf['eol'], $bodyText);
            }
			
			
			if ($conf['zfversion'] == 1)
			{
                //require_once 'Zend/Mail.php';

				$message = new Zend_Mail($conf['charset']);
				
				$message->setHeaderEncoding($conf['encoding']);
			}
			elseif ($conf['zfversion'] == 2)
			{
                $classMailMessage = 'Zend\\Mail\\Message';
                
				$message = new $classMailMessage();
				
				$message->setEncoding($conf['charset']);
				
                $classMimeMessage = 'Zend\\Mime\\Message';
                
				$mimeBody = new $classMimeMessage();
				
				$message->setBody($mimeBody);
				
				
				$headers = $message->getHeaders();

				$headers->setEncoding('ASCII');
			}
			
			
			$message->setFrom($fromEmail, $fromName);
			
			if (!is_null($replytoEmail))
			{
				$message->setReplyTo($replytoEmail, $replytoName);
			}
			
			foreach ($toEmail as $key => $email)
			{
				$name = $toName[$key];
				
                $message->addTo($email, $name);
			}
			
			foreach ($ccEmail as $key => $email)
			{
				$name = $ccName[$key];
				
                $message->addCc($email, $name);
			}
			
			foreach ($bccEmail as $key => $email)
			{
				$name = $bccName[$key];
				
                $message->addBcc($email, $name);
			}
			
			if ($conf['zfversion'] == 1)
			{
				foreach ($header as $key => $value)
				{
					$message->addHeader($value['name'], $value['value'], $value['append']);
				}
			}
			elseif ($conf['zfversion'] == 2)
			{
				$headers = $message->getHeaders();
				
				//$headers->setEncoding($conf['charset']);
				
				foreach ($header as $key => $value)
				{
                    $classGenericMultiHeader = 'Zend\\Mail\\Header\\GenericMultiHeader';
                    
					$headerTmp = new $classGenericMultiHeader($value['name'], $value['value']);
					
					$headerTmp->setEncoding($conf['charset']);
					
					$headers->addHeader($headerTmp);
				}
			}
			
			if (!is_null($subject))
			{
				$message->setSubject($subject);
			}
			
			if ($conf['zfversion'] == 1)
			{
				if (!is_null($bodyHtml))
				{
					$message->setBodyHtml($bodyHtml, $conf['charset'], $conf['encoding']);
				}
				
				if (!is_null($bodyText))
				{
					$message->setBodyText($bodyText, $conf['charset'], $conf['encoding']);
				}
			}
			elseif ($conf['zfversion'] == 2)
			{
				$htmlPart = null;
				
				$textPart = null;
				
				//$mimeMessage = null;
				
				if (!is_null($bodyText))
				{
                    $classMimePart = 'Zend\\Mime\\Part';
                    
					$textPart = new $classMimePart($bodyText);
					
					$textPart->type = 'text/plain';
					
					$textPart->charset = $conf['charset'];
					
					$textPart->encoding = $conf['encoding'];
					
					$message->getBody()->addPart($textPart);
                    
                    $classGenericMultiHeader = 'Zend\\Mail\\Header\\GenericMultiHeader';
                
					$headerTmp = new $classGenericMultiHeader('content-type', 'text/plain; charset=' . $conf['charset']);

					$headerTmp->setEncoding('ASCII');
                    
                    
                    $headers->removeHeader('content-type');

					
					$headers->addHeader($headerTmp);
				}
				
				if (!is_null($bodyHtml))
				{
                    $classMimePart = 'Zend\\Mime\\Part';
                    
					$htmlPart = new $classMimePart($bodyHtml);
					
					$htmlPart->type = 'text/html';
					
					$htmlPart->charset = $conf['charset'];
					
					$htmlPart->encoding = $conf['encoding'];
					
					$message->getBody()->addPart($htmlPart);
                    
                    $classGenericMultiHeader = 'Zend\\Mail\\Header\\GenericMultiHeader';
                
					$headerTmp = new $classGenericMultiHeader('content-type', 'text/html; charset=' . $conf['charset']);

					$headerTmp->setEncoding('ASCII');
                    
                    
                    $headers->removeHeader('content-type');

					
					$headers->addHeader($headerTmp);
				}
				
				/*
				if (!is_null($htmlPart) || !is_null($textPart))
				{
					$mimeMessage = new Zend\Mime\Message();
				}
				
				if (!is_null($htmlPart))
				{
					$mimeMessage->addPart($htmlPart);
				}
				
				if (!is_null($textPart))
				{
					$mimeMessage->addPart($textPart);
				}
				
				if (!is_null($mimeMessage))
				{
					$alternativePart = new Zend\Mime\Part($mimeMessage->generateMessage());
					
					$alternativePart->type = 'multipart/alternative';
					
					$alternativePart->boundary = $mimeMessage->getMime()->boundary();
					
					$alternativePart->charset = $conf['charset'];
					
					//$alternativePart->encoding = $conf['encoding'];
					
					
					$message->getBody()->addPart($alternativePart);
				}
				 * 
				 */
			}
            
			if ($conf['zfversion'] == 1)
			{
                if (isset($conf['attachment']) && strlen($conf['attachment'])>0 && $conf['attachment'])
                {
                    foreach ($attachment as $att)
                    {
                        $message->addAttachment($att);
                    }
                }
			}
			elseif ($conf['zfversion'] == 2)
			{
                //TODO add attachements
            }
			
/*
 * 
 * TODO add attachements
 * 
			if (count($config)>0&&count($attachment)>0) {
				foreach ($attachment as $att) {
					if ($att->filename) {
						$extension = strtolower(pathinfo(strval($att->filename),PATHINFO_EXTENSION));
						if (isset(EhrlichAndreas_Util_Mime::$MIME_TYPE[$extension])) {
							$att->type = EhrlichAndreas_Util_Mime::$MIME_TYPE[$extension];
						}
					}
				}
			}
			
			if (isset($conf['attachment'])&&strlen($conf['attachment'])>0&&$conf['attachment']) {
				foreach ($attachment as $att) {
					$mail->addAttachment($att);
				}
			}
 * 
 */
				
				
			
			if ($conf['zfversion'] == 1)
			{
			}
			elseif ($conf['zfversion'] == 2)
			{
				$headers = $message->getHeaders();

				//$headers->setEncoding('ASCII');
					
				
                $classGenericMultiHeader = 'Zend\\Mail\\Header\\GenericMultiHeader';
                
				$headerTmp = new $classGenericMultiHeader('Content-Transfer-Encoding', $conf['encoding']);

				$headerTmp->setEncoding('ASCII');

                
				$headers->addHeader($headerTmp);
                
                
				
				if (!is_null($bodyText) && !is_null($bodyHtml))
				{
                    $headers->removeHeader('Content-Transfer-Encoding');

                    $headers->removeHeader('content-type');
                    
                    $boundary = $message->getBody()->getMime()->boundary();
                    
                    $classGenericMultiHeader = 'Zend\\Mail\\Header\\GenericMultiHeader';
                    
                    $headerTmp = new $classGenericMultiHeader('content-type', 'multipart/alternative; boundary="' . $boundary . '"');

                    $headerTmp->setEncoding('ASCII');

                    $headers->addHeader($headerTmp);
                }
				
				
				if (false && $message->getBody()->isMultiPart())
				{
                    $classGenericMultiHeader = 'Zend\\Mail\\Header\\GenericMultiHeader';
                    
					$headerTmp = new $classGenericMultiHeader('content-type', 'multipart/alternative; charset=' . $conf['charset']);

					$headerTmp->setEncoding('ASCII');

					
					$headers->addHeader($headerTmp);
				}
			}
            
            /**
            echo '<pre>';
            var_dump
            (
                $fromEmail,
                $fromName,
                $replytoEmail,
                $replytoName,
                $toEmail,
                $toName,
                $ccEmail,
                $ccName,
                $bccEmail,
                $bccName,
                $header,
                $subject,
                $bodyHtml,
                $bodyText
            );
            print_r($replacement);
            die();
            **/

			if ($conf['zfversion'] == 1)
			{
            	$defaultTranslator = Zend_Validate_Abstract::getDefaultTranslator();

                if (is_object($defaultTranslator))
                {
                    Zend_Validate_Abstract::setDefaultTranslator(null);

                    $defaultTranslatorOptions = $defaultTranslator->getOptions();

                    $defaultTranslator->setOptions(array('logUntranslated'=>false));
                }
			}
			else
			{
			}
			
			$messageTransport = $conf['transport'];

            try
            {
                $messageTransport->send($message);
                
                $sendTmp = true;
            }
            catch(Exception $e)
            {
                $sendTmp = false;
            }
            
            $send = $send && $sendTmp;

			if ($conf['zfversion'] == 1)
			{
                if (is_object($defaultTranslator))
                {
                    $defaultTranslator->setOptions($defaultTranslatorOptions);

                    Zend_Validate_Abstract::setDefaultTranslator($defaultTranslator);
                }
			}
			else
			{
			}
		}
			
        return $send;
	}
}

