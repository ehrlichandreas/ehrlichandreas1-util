<?php

//require_once 'EhrlichAndreas/Util/Regex.php';

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Vsprintf
{
	
	public static function vsprintf($format = '', $args = array(), $times = 1)
	{
		if ($times <= 0)
		{
			return $format;
		}
        
		if (is_null($format))
		{
			return $format;
		}
		
		if (is_null($args))
		{
			return $format;
		}
		
		if (!is_scalar($format))
		{
			return $format;
		}
		
		if (strlen($format) == 0)
		{
			return $format;
		}
			
		if (is_scalar($format) && strlen($format) == 0)
		{
			return $format;
		}
			
		if (is_scalar($args) && strlen($args) == 0)
		{
			return $format;
		}
			
		if (is_scalar($args) && empty($args))
		{
			return $format;
		}
		
		if (is_scalar($args))
		{
			
			$args = array
			(
				$args,
			);
		}
		
		if (count($args) == 0)
		{
			return $format;
		}
		
        $argsTmp = $args;
        
		for ($i = 0; $i < $times; $i++)
        {
            $args = (array) $argsTmp;
            
            foreach ($args as $key => $value)
            {
                if (is_scalar($value))
                {
                    $search = array
                    (
                        '%' . $key . '%',
                        '%(' . $key . ')%',
                        '%(' . $key . ')s',
                        '{' . $key . '}',
                        '[' . $key . ']',
                        '[i18n]' . $key . '[/i18n]',
                    );

                    $format = str_replace($search, (string)$value, $format);


                    $keyTmp = EhrlichAndreas_Util_Regex::quote($key, '#');

                    $matches = array();

                    $regex = '#\%\('.$keyTmp.'\)(([+-\.0-9]*)?(\'.)?([+-\.0-9]*)?([bcdeufFosxX]))#';

                    if (strlen($keyTmp) > 0 && preg_match($regex, $format, $matches))
                    {
                        if (count($matches) > 1)
                        {
                            $format = str_replace($matches[0], sprintf('%' . $matches[1], (string)$value), $format);
                        }
                    }

                    if (!is_int($key))
                    {
                        unset($args[$key]);
                    }
                }
                else
                {
                    unset($args[$key]);
                }
            }

            $vsprintfRegex = "~%(?:(\d+)[$])?[-+]?(?:[ 0]|['].)?(?:[-]?\d+)?(?:[.]\d+)?[%bcdeEufFgGosxX]~";

            if (count($args) > 0 && preg_match_all($vsprintfRegex, $format, $expected) > 0)
            {
                $expected = intval(max($expected[1], count(array_unique($expected[1]))));

                if (count($args) >= $expected)
                {
                    $format = vsprintf($format, $args); // yeah!
                }
            }
        }
		
		return $format;
	}
}

