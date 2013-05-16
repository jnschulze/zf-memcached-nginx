<?php

class ZendExtra_Controller_Plugin_PageCache extends Zend_Controller_Plugin_Abstract
{
    protected $_cacheFrontend;

    public function __construct(ZendExtra_Cache_Frontend_Page $frontend)
    {
        $this->_cacheFrontend = $frontend;
    }

    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        $enabled = Zend_Registry::get('CACHE_ENABLED');
        if($enabled && !Zend_Session::sessionExists())
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
