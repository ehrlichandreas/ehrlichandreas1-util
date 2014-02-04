<?php 

require_once 'EhrlichAndreas/Util/Exception.php';

/**
 * Class for connecting to SQL databases and performing common operations.
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Dsn
{
    

    public static function parseDsn ($dsn)
    {
        $data = self::parseUri($dsn);
        
        $driver_name = $data[0];
        
        $driver_dsn = $data[1];
        
        if (empty($driver_name))
        {
            throw new EhrlichAndreas_Util_Exception('could not find driver');
        }
        
        if (! empty($driver_dsn))
        {
            
            $pairs = explode(';', $driver_dsn);
            
            $driver_dsn = array();
            
            foreach ($pairs as $pair)
            {
                
                $pos = strpos($pair, '=');
                
                if (false !== $pos)
                {
                    $key = substr($pair, 0, $pos);
                    
                    $val = substr($pair, $pos + 1);
                }
                else
                {
                    $key = $pair;
                    
                    $val = '';
                }
                
                $key = trim($key);
                
                $key = strtolower($key);
                
                $val = trim($val);
                
                $driver_dsn[$key] = $val;
            }
        }
        else
        {
            $driver_dsn = array();
        }
        
        return $driver_dsn;
    }

    public static function parseUri ($dsn)
    {
        $pos = strpos($dsn, ':');
        
        if ($pos === false)
        {
            throw new EhrlichAndreas_Util_Exception('invalid data source name');
        }
        
        $driver_name = substr($dsn, 0, $pos);
        
        $driver_name = trim($driver_name);
        
        $driver_name = strtolower($driver_name);
        
        $driver_dsn = substr($dsn, $pos + 1);
        
        $driver_dsn = trim($driver_dsn);
        
        $data = array
        (
            $driver_name,
            
            $driver_dsn,
        );
        
        return $data;
    }
}