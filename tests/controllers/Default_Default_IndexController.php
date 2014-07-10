<?php

class Default_Default_IndexController extends EhrlichAndreas_Util_Mvc_Controller
{
    public function indexAction()
    {
        return $this->_view->render(__METHOD__);
    }
}

