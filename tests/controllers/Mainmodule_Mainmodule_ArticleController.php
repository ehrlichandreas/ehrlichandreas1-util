<?php

class Mainmodule_Mainmodule_ArticleController extends EhrlichAndreas_Util_Mvc_Controller
{
    public function indexAction()
    {
        return $this->_view->render(__METHOD__);
    }
    
    public function newsletterAction()
    {
        $request = $this->getRequest();
        
        $invokeParams = $request->getParams();
        
        if (!isset($invokeParams['mail']))
        {
            $invokeParams['mail'] = null;
        }
        
        if (!isset($invokeParams['name']))
        {
            $invokeParams['name'] = null;
        }
        
        $this->_view->assign('invokeParams', $invokeParams);
        
        return $this->_view->render(__METHOD__);
    }
}

