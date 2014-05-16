<?php 

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Zend_I18n_Translator
{
    /**
     * 
     * @param object $translator
     * @param string $locale
     * @param string $textDomain
     * @return array 
     */
    public static function getMessages($translator, $locale = null, $textDomain = 'default')
    {
        if (is_array($translator))
        {
            return $translator;
        }
        
        if (is_object($translator) && method_exists($translator, 'getMessages'))
        {
            return $translator->getMessages($locale);
        }
        
        $translator->translate('n', 'default', $locale);
        
        $messages = var_export($translator, true);
        
        $messages = preg_replace('#(\w+\\\\)*\w+\:\:__set_state#ui', 'array', $messages);
        
        $messages = '?><?php return ' . $messages . ';';
        
        $messages = eval($messages);
        
        $messages = \MiniPhp_Util_Array::objectToArray($messages);
        
        $messages = $messages[0]['messages'];
        
        $messagesTmp = $messages;
        
        foreach ($messagesTmp as $line1 => $array1)
        {
            foreach ($array1 as $line2 => $array2)
            {
                $messagesTmp[$line1][$line2] = array();
                
                foreach ($array2 as $array3)
                {
                    $messagesTmp[$line1][$line2] = array_merge($messagesTmp[$line1][$line2], $array3);
                }
            }
        }
        
        $messages = $messagesTmp;
        
        if (!isset($messages[$textDomain]))
        {
            return array();
        }
        
        $messages = $messages[$textDomain];
        
        if (!is_null($locale) && !isset($messages[$locale]))
        {
            return array();
        }
        
        if (!is_null($locale))
        {
            $messages = $messages[$locale];
        }
        
        return $messages;
    }
}

