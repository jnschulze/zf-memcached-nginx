<?php

class ZendExtra_Controller_Plugin_PageCache extends Zend_Controller_Plugin_Abstract
{
    protected $_cacheFrontend;

    public function __construct(ZendExtra_Cache_Frontend_Page $frontend)
    {
        $this->_cacheFrontend = $frontend;
    }
    
    protected function _skip(Zend_Controller_Request_Abstract $request)
    {
        if($this->getResponse()->isException())
        {
            return true;
        }

        if (!($this->_isDispatchable($request)))
        {
           return true;
        }

        return false;
    }

    protected function _isDispatchable(Zend_Controller_Request_Abstract $request)
    {
        return Zend_Controller_Front::getInstance()->getDispatcher()->isDispatchable($request);
    }

    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        $enabled = Zend_Registry::get('CACHE_ENABLED');

        if($enabled && !Zend_Session::sessionExists() && !$this->_skip($request))
        {
            $this->_cacheFrontend->start();
        }
    }

    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        $errors = $request->getParam('error_handler');
        if($errors)
        {
            $this->_cacheFrontend->cancel();
        }
    }
}
