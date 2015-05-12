<?php

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Html
{
    
    /**
     * 
     * 
     * @param string $document
     * @return string
     */
    public static function htmlEntityDecode ($document = '')
    {
        static $html_translation_table = array();
        static $search = array();
        static $replace = array();
        
        if (empty($html_translation_table))
        {
            $html_translation_table = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES | ENT_COMPAT);
            
            $keys = array_keys($html_translation_table);
            $values = array_values($html_translation_table);
            
            $html_translation_table = array_combine($values, $keys);
            $html_translation_table['&nbsp;'] = ' ';
            
            $search = array_keys($html_translation_table);
            $replace = array_values($html_translation_table);
        }
        
        $document = str_replace($search, $replace, $document);
        
        $pattern = array(
            "'&(quot|#34|#034|#x22);'i", // replace html
                                         // entities
            "'&(amp|#38|#038|#x26);'i", // added hexadecimal values
            "'&(lt|#60|#060|#x3c);'i",
            "'&(gt|#62|#062|#x3e);'i",
            "'&(nbsp|#160|#xa0);|" . chr(160) . "'i",
            "'&(iexcl|#161);'i",
            "'&(cent|#162);'i",
            "'&(pound|#163);'i",
            "'&(copy|#169);'i",
            "'&(reg|#174);'i",
            "'&(deg|#176);'i",
            "'&(#39|#039|#x27);'",
            "'&(euro|#8364);'i", // europe
            "'&a(uml|UML);|&#228;'", // german
            "'&o(uml|UML);'",
            "'&u(uml|UML);'",
            "'&A(uml|UML);'",
            "'&O(uml|UML);'",
            "'&U(uml|UML);'",
            "'&szlig;'i"
        );
        $replacement = array(
            "\"",
            "&",
            "<",
            ">",
            " ",
            '¡',
            '¢',
            '£',
            '©',
            '®',
            '°',
            "'",
            "€",
            "ä",
            "ö",
            "ü",
            "Ä",
            "Ö",
            "Ü",
            "ß"
        );
        $document = preg_replace($pattern, $replacement, $document);
        
        return $document;
    }
    
    /**
     * 
     * 
     * @param string|array $document
     * @return mixed
     */
    public static function htmlEntityDecodeArray ($array = array())
    {
        if (! is_array($array))
        {
            return self::htmlEntityDecode($array);
        }
        
        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $array[$key] = self::htmlEntityDecodeArray($value);
            }
            else
            {
                $array[$key] = self::htmlEntityDecode($value);
            }
        }
        
        return $array;
    }
    
    /**
     * expand each link into a fully qualified URL
     * 
     * @access public
     * @param string $links
     *            links to qualify
     * @param string $URI
     *            full URI to get the base from
     * @return string expanded links
     */
    public static function expandLinks ($links, $URI)
    {
        preg_match('/^[^\?]+/', $URI, $match);
        
        $match = preg_replace('|/[^\/\.]+\.[^\/\.]+$|', '', $match[0]);
        $match = preg_replace("|/$|", "", $match);
        $match_part = parse_url($match);
        $match_root = $match_part["scheme"] . "://" . $match_part["host"];
        
        $search = array(
            "|^https?://" . preg_quote($match_part["host"]) . "|i",
            '|^(\/)|i',
            '|^(?!https?://)(?!mailto:)|i',
            '|/\./|',
            '|/[^\/]+/\.\./|'
        );
        
        $replace = array(
            "",
            $match_root . "/",
            $match . "/",
            "/",
            "/"
        );
        
        $expandedLinks = preg_replace($search, $replace, $links);
        
        //$search = '#' . preg_quote($match_part["host"]) . '#';
        
        return $expandedLinks;
    }
    
    public static function parseForms ($body = '', $headers = array())
    {
        $forms = self::stripForm($body);
        
        $matchesForms = array();
        preg_match_all('#(<form.*</form>)#Usi', $forms, $matchesForms);
        
        $return = array();
        
        foreach ($matchesForms[0] as $matchForms)
        {
            $valuesForm = array();
            $matchesInput = array();
            if (preg_match_all('#<(input|button)(.*)>#Usi', $matchForms, $matchesInput))
            {
                foreach ($matchesInput[2] as $keyInput => $matchInput)
                {
                    preg_match('#name\=[\'"](.*)[\'"]#Usi', $matchInput, $name);
                    if (! isset($name[1]))
                    {
                        $name[1] = '';
                    }
                    $name = $name[1];
                    
                    preg_match('#value\=[\'"](.*)[\'"]#Usi', $matchInput, $value);
                    if (! isset($value[1]))
                    {
                        $value[1] = '';
                    }
                    $value = $value[1];
                    
                    if (isset($valuesForm[$name]))
                    {
                        $var = $valuesForm[$name];
                        if (! is_array($var))
                        {
                            $var = array(
                                $valuesForm[$name] => $valuesForm[$name]
                            );
                        }
                        $var[$value] = $value;
                        
                        $value = $var;
                    }
                    
                    $valuesForm[$name] = $value;
                }
            }
            
            $matchesSelect = array();
            if (preg_match_all('#<select(.*)>.*</?select>#Usi', $matchForms, $matchesSelect))
            {
                foreach ($matchesSelect[1] as $keySelect => $matchSelect)
                {
                    preg_match('#name\=[\'"](.*)[\'"]#Usi', $matchSelect, $name);
                    if (! isset($name[1]))
                    {
                        $name[1] = '';
                    }
                    $name = $name[1];
                    
                    $valuesForm[$name] = array();
                    
                    $matchesOptions = array();
                    if (preg_match_all('#<option(.*)>(.*)(</|<option)#Usi', $matchesSelect[0][$keySelect], $matchesOptions))
                    {
                        foreach ($matchesOptions[1] as $keyOptions => $matchOptions)
                        {
                            preg_match('#value\=[\'"](.*)[\'"]#Usi', $matchOptions, $value);
                            
                            $nomatch = false;
                            if (! isset($value[1]))
                            {
                                $value[1] = '';
                                
                                $nomatch = true;
                            }
                            $value = $value[1];
                            
                            if ($nomatch)
                            {
                                $value = trim($matchesOptions[2][$keyOptions]);
                            }
                            
                            $valuesForm[$name][$value] = trim($matchesOptions[2][$keyOptions]);
                        }
                    }
                }
            }
            
            unset($valuesForm['']);
            
            $return[] = $valuesForm;
        }
        
        $return = self::stripTextArray($return);
        
        return $return;
    }
    
    /**
     * strip the form elements from an html document
     * 
     * @access public
     * @param string $document
     *            to strip
     * @return string
     */
    public static function stripForm ($document)
    {
        preg_match_all('#<\/?(FORM|INPUT|BUTTON|SELECT|TEXTAREA|(OPTION))[^<>]*>(?(2)(.*(?=<\/?(option|select)[^<>]*>[\r\n]*)|(?=[\r\n]*))|(?=[\r\n]*))#Usi', $document, $elements);
        
        // catenate the matches
        $match = implode("\n", $elements[0]);
        
        // return the links
        return $match;
    }
    
    /**
     * strip the hyperlinks from an html document
     * 
     * @access public
     * @param string $document            
     * @return string
     */
    public static function stripLinks ($document)
    {
        /*
         * $regex = "'<\s*a\s.*?href\s*=\s* # find <a href= ([\"\'])? # find
         * single or double quote (?(1) (.*?)\\1 | ([^\s\>]+)) # if quote found,
         * match up to next matching # quote, otherwise match up to next space
         * 'isx";
         */
        $regex = '';
        $regex .= '#<\s*a\s.*?href\s*=\s*'; // find <a href=
        $regex .= '([\"\\\'])?'; // find single or double quote
        $regex .= '(?(1) (.*?)\\1 | ([^\s\>]+))?'; // if quote found, match up
                                                   // to next matching quote,
                                                   // otherwise match up to next
                                                   // space
        $regex .= '#isx';
        
        preg_match_all($regex, $document, $links);
        
        // catenate the non-empty matches from the conditional subpattern
        
        $match = array();
        
        while (list ($key, $val) = each($links[2]))
        {
            if (! empty($val))
                $match[] = $val;
        }
        
        while (list ($key, $val) = each($links[3]))
        {
            if (! empty($val))
                $match[] = $val;
        }
        
        // return the links
        return $match;
    }
    
    /**
     * strip the text from an html document
     * 
     * @access public
     * @param string $document
     *            document to strip
     * @return string
     */
    public static function stripText ($document = '')
    {
        $search = array(
            "'<script[^>]*?>.*?</script>'si", // strip out
                                              // javascript
            '\'<[\/\!]*?[^<>]*?>\'si', // strip out html tags
            '\'([\r\n])[\s]+\'', // strip out white space
            "'&(quot|#34|#034|#x22);'i", // replace html entities
            "'&(amp|#38|#038|#x26);'i", // added hexadecimal values
            "'&(lt|#60|#060|#x3c);'i",
            "'&(gt|#62|#062|#x3e);'i",
            "'&(nbsp|#160|#xa0);'i",
            "'&(iexcl|#161);'i",
            "'&(cent|#162);'i",
            "'&(pound|#163);'i",
            "'&(copy|#169);'i",
            "'&(reg|#174);'i",
            "'&(deg|#176);'i",
            "'&(#39|#039|#x27);'",
            "'&(euro|#8364);'i", // europe
            "'&a(uml|UML);'", // german
            "'&o(uml|UML);'",
            "'&u(uml|UML);'",
            "'&A(uml|UML);'",
            "'&O(uml|UML);'",
            "'&U(uml|UML);'",
            "'&szlig;'i"
        );
        $replace = array(
            "",
            "",
            "\\1",
            "\"",
            "&",
            "<",
            ">",
            " ",
            '¡',
            '¢',
            '£',
            '©',
            '®',
            '°',
            "'",
            "€",
            "ä",
            "ö",
            "ü",
            "Ä",
            "Ö",
            "Ü",
            "ß",
        );
        $replace[2] = "\n";
        
        return preg_replace($search, $replace, $document);
    }
    
    /**
     * strip the text from an array of html documents
     * 
     * @access public
     * @param string|array $array
     *            documents to strip
     * @return string
     */
    public static function stripTextArray ($array = array())
    {
        if (! is_array($array))
        {
            return self::stripText($array);
        }
        
        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $array[$key] = self::stripTextArray($value);
            }
            else
            {
                $array[$key] = self::stripText($value);
            }
        }
        
        return $array;
    }
    
    /**
     * strip the multiple white spaces from a document
     * 
     * @access public
     * @param string $document
     *            document to strip
     * @return string
     */
    public static function stripTextWhite ($document = '')
    {
        $search = array(
            '#([' . chr(194) . chr(226) . ']*[\s])+|[' . chr(160) . ']+#Usi'
        ); // strip
           // out
           // white
           // space
        $replace = array(
            ' '
        );
        return preg_replace($search, $replace, $document);
    }
    
    /**
     * strip the multiple white spaces from an array of documents
     * 
     * @access public
     * @param string $array
     *            documents to strip
     * @return string
     */
    public static function stripTextWhiteArray ($array = array())
    {
        if (! is_array($array))
        {
            return self::stripTextWhite($array);
        }
        
        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $array[$key] = self::stripTextWhiteArray($value);
            }
            else
            {
                $array[$key] = self::stripTextWhite($value);
            }
        }
        
        return $array;
    }
}