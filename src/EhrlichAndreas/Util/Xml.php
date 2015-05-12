<?php

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Xml
{

    /**
     *
     * @var EhrlichAndreas_Util_Xml
     */
    protected static $parser = null;

    protected $encoding = 'UTF-8';

    /**
     */
    protected function __construct ($options = array())
    {
        if (isset($options['encoding']))
        {
            $this->encoding = $options['encoding'];
        }
    }

    /**
     *
     * @return EhrlichAndreas_Util_Xml
     */
    public static function getInstance ($options = array())
    {
        if (is_null(self::$parser))
        {
            self::$parser = new self($options);
        }
        
        return self::$parser;
    }

    public static function parseXml ($xml, $encoding = null)
    {
        return self::getInstance()->_parseXml($xml, $encoding);
    }

    public static function parseXmlRaw ($xml, $encoding = null)
    {
        return self::getInstance()->_parseXmlRaw($xml, $encoding);
    }

    /**
     *
     * @param string $xml            
     * @throws Exception
     * @return unknown
     */
    protected function _parseXmlRaw ($xml, $encoding = null)
    {
        $xml = trim($xml);
        
        if (! is_null($encoding) && is_string($encoding))
        {
            $this->encoding = trim($encoding);
        }
        
        if (preg_match('/^<\?xml\s+version=(?:"[^"]*"|\'[^\']*\')\s+encoding=("[^"]*"|\'[^\']*\')/s', $xml, $match))
        {
            $this->encoding = trim($match[1], '"\'');
        }
        
        $this->encoding = trim($this->encoding);
        $this->encoding = strtoupper($this->encoding);
        
        $this->arrOutput = array();
        
        $parser = xml_parser_create($this->encoding);
        // Set the options for parsing the XML data.
        // xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, $this->encoding);
        // Set the object for the parser.
        xml_set_object($parser, $this);
        // Set the element handlers for the parser.
        xml_set_element_handler($parser, 'startElement', 'endElement');
        xml_set_character_data_handler($parser, 'characterData');
        
        if (! xml_parse($parser, $xml, true))
        {
            // Display an error message.
            $err = sprintf('XML error parsing XML payload on line %d: %s', xml_get_current_line_number($parser), xml_error_string(xml_get_error_code($parser)));
            throw new Exception($err);
        }
        
        xml_parser_free($parser);
        
        $return = $this->arrOutput;
        
        $this->arrOutput = array();
        
        return $return;
    }

    /**
     *
     * @param string $xml            
     * @throws Exception
     * @return unknown
     */
    protected function _parseXml ($xml, $encoding = null)
    {
        $array_raw = $this->_parseXmlRaw($xml, $encoding);
        
        // echo '<pre>';print_r($array_raw);die();
        
        $node = array_shift($array_raw);
        
        $return = array();
        $return = $this->computeNode($node);
        
        return $return;
    }

    protected function computeNode ($node = array(), &$response = null)
    {
        $return = array();
        
        if (isset($node['attributes']))
        {
            foreach ($node['attributes'] as $key => $value)
            {
                if (is_null($response))
                {
                    $return[$key] = $value;
                }
                else
                {
                    $response[$key] = $value;
                }
            }
        }
        
        if (isset($node['childrens']))
        {
            foreach ($node['childrens'] as $key => $value)
            {
                $name = $value['nodename'];
                if (is_null($response))
                {
                    if (! isset($return[$name]))
                    {
                        $return[$name] = $this->computeNode($value);
                    }
                    else
                    {
                        if (! is_array($return[$name]) || ! isset($return[$name][0]))
                        {
                            $return[$name] = array(
                                $return[$name]
                            );
                        }
                        
                        $pos = count($return[$name]);
                        $return[$name][$pos] = $this->computeNode($value);
                    }
                }
                else
                {
                    // TODO
                    if (! isset($response[$name]))
                    {
                        $response[$name] = array();
                    }
                    
                    $pos = count($response[$name]);
                    $this->computeNode($value, $response[$name][$pos]);
                }
            }
        }
        
        if (isset($node['nodevalue']))
        {
            if (is_null($response))
            {
                if (count($return) == 0)
                {
                    $return = $node['nodevalue'];
                }
                else
                {
                    $return['value'] = $node['nodevalue'];
                }
            }
            else
            {
                // TODO
                if (count($response) == 0)
                {
                    $response = $node['nodevalue'];
                }
                else
                {
                    $response['value'] = $node['nodevalue'];
                }
            }
        }
        
        if (is_null($response))
        {
            return $return;
        }
    }
    
    // called on each xml tree
    protected function startElement ($parser, $name, $attrs)
    {
        $tag = array(
            "nodename" => $name,
            "attributes" => $attrs
        );
        
        array_push($this->arrOutput, $tag);
    }
    
    // called on data for xml
    protected function characterData ($parser, $tagData)
    {
        if (strlen(trim($tagData)) > 0)
        {
            if (isset($this->arrOutput[count($this->arrOutput) - 1]['nodevalue']))
            {
                $this->arrOutput[count($this->arrOutput) - 1]['nodevalue'] .= $this->parseXMLValue($tagData);
            }
            else
            {
                $this->arrOutput[count($this->arrOutput) - 1]['nodevalue'] = $this->parseXMLValue($tagData);
            }
        }
    }
    
    // called when finished parsing
    protected function endElement ($parser, $name)
    {
        $this->arrOutput[count($this->arrOutput) - 2]['childrens'][] = $this->arrOutput[count($this->arrOutput) - 1];
        
        if (count($this->arrOutput[count($this->arrOutput) - 2]['childrens']) == 1)
        {
            // $this->arrOutput [count ( $this->arrOutput ) - 2] ['firstchild']
            // = & $this->arrOutput [count ( $this->arrOutput ) - 2]
            // ['childrens']
            // [0];
        }
        
        array_pop($this->arrOutput);
    }

    protected function parseXMLValue ($tvalue)
    {
        if ($this->encoding != 'UTF-8')
        {
            $tvalue = iconv($this->encoding, 'UTF-8', $tvalue);
        }
        
        $tvalue = htmlspecialchars($tvalue, null, 'UTF-8', null);
        
        return $tvalue;
    }
}

