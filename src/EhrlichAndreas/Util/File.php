<?php 

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_File
{

    /**
     *
     * @param string $from            
     * @return array
     */
    public static function listDirsFiles ($from = '.', $recursive = true)
    {
        if (! @is_dir($from))
        {
            return array
            (
                false,
                false,
            );
        }
        
        $from = array
        (
            $from,
        );
        
        $return = array(
            'dirs'  => array(),
            'files' => array(),
        );
        
        while (NULL !== ($dir = @array_pop($from)))
        {
            $dh = @opendir($dir);
            
            if ($dh)
            {
                $dir = rtrim($dir, '\\/');
                
                while (false !== ($file = @readdir($dh)))
                {
                    if ($file == '.' || $file == '..')
                    {
                        continue;
                    }
                    
                    $path = $dir . '/' . $file;
                    
                    if (@is_dir($path))
                    {
                        $return['dirs'][] = $path;
						
						if ($recursive)
						{
							$from[] = $path;
						}
                    }
                    else
                    {
                        $return['files'][] = $path;
                    }
                }
                
                @closedir($dh);
            }
        }
        
        return $return;
    }

    /**
     *
     * @param string $from            
     * @return array
     */
    public static function listFiles ($from = '.', $recursive = true)
    {
        $list_dirs_and_files = self::listDirsFiles($from, $recursive);
        
        if (isset($list_dirs_and_files['files']))
        {
            return $list_dirs_and_files['files'];
        }
        
        return false;
    }

    /**
     *
     * @param string $from            
     * @return array
     */
    public static function listDirs ($from = '.', $recursive = true)
    {
        $list_dirs_and_files = self::listDirsFiles($from, $recursive);
        
        if (isset($list_dirs_and_files['dirs']))
        {
            return $list_dirs_and_files['dirs'];
        }
        
        return false;
    }
}

