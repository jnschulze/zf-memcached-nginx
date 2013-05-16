<?php

/**
 * @author Jan N. Schulze   <hi@jns.io>
 * @author Tokarchuk Andrey <netandreus@gmail.com> (draft)
 * @see    http://tokarchuk.ru/2010/11/connecting-php-fpm-and-memcached-to-nginx/
 */
 
class ZendExtra_Cache_Backend_RawMemcached extends Zend_Cache_Backend_Memcached
{
    public function load($id, $doNotTestCacheValidity = false)
    {
        $tmp = $this->_memcache->get($id);
        return ($tmp) ? $tmp : false;
    }
    
    public function test($id)
    {
        return $this->load($id);
    }

    public function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        $lifetime = $this->getLifetime($specificLifetime);
        
        $flag = ($this->_options['compression']) ? MEMCACHED_COMPRESSED : 0;
        
        $result = @$this->_memcache->set($id, $data, $flag, $lifetime);
        if (count($tags) > 0)
        {
            $this->_log(self::TAGS_UNSUPPORTED_BY_SAVE_OF_MEMCACHED_BACKEND);
        }
        
        return $result;
    }
    
    /**
     * Not possible since we don't store any metadata in memcached
     */
    public function touch($id, $extraLifetime)
    {
        return false;
    }
}
