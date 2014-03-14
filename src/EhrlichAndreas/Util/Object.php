<?php

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Object
{

    public static function isInstanceOf ($object_or_class = null, $class = null)
    {
        if (empty($object_or_class) || empty($class))
        {
            return false;
        }
        
        $return = false;
        
        if (! $return && (is_object($object_or_class) || is_string($object_or_class)))
        {
            $return = ($object_or_class instanceof $class);
        }
        
        if (! $return && is_string($object_or_class) && is_string($class))
        {
            $return = (strtolower($object_or_class) == strtolower($class));
        }
        
        if (! $return && is_string($object_or_class))
        {
            try
            {
                $reflection = new ReflectionClass($object_or_class);
                
                $return = $reflection->isSubclassOf($class) || $reflection->implementsInterface($class);
            }
            catch (ReflectionException $e)
            {
            }
        }
        
        return $return;
    }
}

