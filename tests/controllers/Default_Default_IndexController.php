<?php

class Default_Default_IndexController extends EhrlichAndreas_Util_Mvc_Controller
{
    public function indexAction()
    {
        $mvc = EhrlichAndreas_Util_Mvc::getInstance();
        
        $router = $mvc->getRouter();
        
        $view = $mvc->getView();
        
        $userParams = array
        (
            'newsletter_id' => 17,
            'title'         => 'fasdf af asD',
        );
        
        $response = 'hallo ';
        
        $response .= $router->assemble($userParams, 'newsletter', true, true);
        
        return $response;
    }
}