<?php

/**
 *
 * @author Ehrlich, Andreas <ehrlich.andreas@googlemail.com>
 */
class EhrlichAndreas_Util_Cli
{

    /**
     *
     * @param array $requests            
     * @return array
     * @throws Exception
     */
    public static function run (array $requests = array(), $callback = null, $window_size = 5, $usleep = 1000000)
    {
        if (empty($requests))
        {
            return array();
        }
        
        $options = array(
            '0' => array(
                'pipe',
                'r'
            ),
            '1' => array(
                'pipe',
                'w'
            )
        );
        
        $window_size = intval($window_size);
        
        if (empty($window_size) || $window_size <= 0)
        {
            $window_size = 5;
        }
        
        // make sure the rolling window isn't greater than the # of requests
        if (count($requests) < $window_size)
        {
            $window_size = count($requests);
        }
        
        $results = array();
        $response_map = array();
        $requests_map = array();
        $requests_keys = array_keys($requests);
        $requests_values = array_values($requests);
        
        // start the first batch of requests
        for ($i = 0; $i < $window_size; $i ++)
        {
            
            $pipes = array();
            
            $key = array_shift($requests_keys);
            $command = array_shift($requests_values);
            
            $requests_map[$key] = proc_open($command, $options, $pipes);
            $response_map[$key] = $pipes;
        }
        
        do
        {
            usleep($usleep);
            
            $running = false;
            $added = 0;
            $removed = 0;
            
            if (empty($requests_map))
            {
                break;
            }
            
            foreach ($requests_map as $key => $thread)
            {
                // Get the status
                $status = proc_get_status($thread);
                
                if ($status['running'] != 'true' || $status['signaled'] == 'true')
                {
                    
                    $result = stream_get_contents($response_map[$key][1]);
                    $results[$key] = $result;
                    
                    fclose($response_map[$key][1]);
                    
                    unset($requests_map[$key]);
                    unset($response_map[$key]);
                    
                    // send the return values to the callback function.
                    if (is_callable($callback))
                    {
                        call_user_func($callback, $result, $status);
                    }
                    
                    $removed ++;
                }
            }
            
            $added = array(
                $removed,
                $window_size,
                count($requests_keys)
            );
            
            $added = min($added);
            
            // start the first batch of requests
            for ($i = 0; $i < $added; $i ++)
            {
                
                $pipes = array();
                
                $key = array_shift($requests_keys);
                $command = array_shift($requests_values);
                
                $requests_map[$key] = proc_open($command, $options, $pipes);
                $response_map[$key] = $pipes;
            }
            
            $running = ! empty($requests_map);
        }
        while ($running);
        
        return $results;
    }
}

