<?php

/**
 * @author Jan N. Schulze   <hi@jns.io>
 * @author Tokarchuk Andrey <netandreus@gmail.com> (draft)
 * @see    http://tokarchuk.ru/2010/11/connecting-php-fpm-and-memcached-to-nginx/
 */
 
class ZendExtra_Cache_Frontend_Page extends Zend_Cache_Frontend_Page
{
    protected function _makeId()
    {
        /**
         * @todo make prefix configurable
         *
         * nginx config:
         * set $memcached_key 'nginx_$host$uri?$args';
         */
        $cacheKey = 'nginx_'.$_SERVER['HTTP_HOST'].$_SERVER['DOCUMENT_URI'].'?'.$_SERVER['QUERY_STRING'];
        return $cacheKey;
    }
    
    public function _flush($data)
    {
        if (!$this->_cancel) {
            $this->save($data, null, $this->_activeOptions['tags'], $this->_activeOptions['specific_lifetime'], $this->_activeOptions['priority']);
        }

        return $data;
    }
    
    public function load($id, $doNotTestCacheValidity = false, $doNotUnserialize = false)
    {
        if (!$this->_options['caching']) {
            return false;
        }
        $id = $this->_id($id); // cache id may need prefix
        $this->_lastId = $id;
        self::_validateIdOrTag($id);
        $data = $this->_backend->load($id, $doNotTestCacheValidity);
        
        // false || emulate array behaviour of original implementation
        return ($data === false) ? $data : array('data' => $data, 'headers' => array());
    }
    
    public function test($id)
    {
        if (!$this->_options['caching']) {
            return false;
        }
        $id = $this->_id($id); // cache id may need prefix
        self::_validateIdOrTag($id);
        $this->_lastId = $id;
        return $this->_backend->test($id);
    }
    
    public function save($data, $id = null, $tags = array(), $specificLifetime = false, $priority = 8)
    {
        if (!$this->_options['caching']) {
            return true;
        }
        if ($id === null) {
            $id = $this->_lastId;
        } else {
            $id = $this->_id($id);
        }
        self::_validateIdOrTag($id);
        self::_validateTagsArray($tags);

        // As save() is intended to be called from _flush() only allow strings
        if(!is_string($data))
        {
            Zend_Cache::throwException('Data must be be string');
        }

        // automatic cleaning
        if ($this->_options['automatic_cleaning_factor'] > 0) {
            $rand = rand(1, $this->_options['automatic_cleaning_factor']);
            if ($rand==1) {
                if ($this->_extendedBackend) {
                    // New way
                    if ($this->_backendCapabilities['automatic_cleaning']) {
                        $this->clean(Zend_Cache::CLEANING_MODE_OLD);
                    } else {
                        $this->_log('Zend_Cache_Core::save() / automatic cleaning is not available/necessary with this backend');
                    }
                } else {
                    // Deprecated way (will be removed in next major version)
                    if (method_exists($this->_backend, 'isAutomaticCleaningAvailable') && ($this->_backend->isAutomaticCleaningAvailable())) {
                        $this->clean(Zend_Cache::CLEANING_MODE_OLD);
                    } else {
                        $this->_log('Zend_Cache_Core::save() / automatic cleaning is not available/necessary with this backend');
                    }
                }
            }
        }
        if ($this->_options['ignore_user_abort']) {
            $abort = ignore_user_abort(true);
        }
        if (($this->_extendedBackend) && ($this->_backendCapabilities['priority'])) {
            $result = $this->_backend->save($data, $id, $tags, $specificLifetime, $priority);
        } else {
            $result = $this->_backend->save($data, $id, $tags, $specificLifetime);
        }
        if ($this->_options['ignore_user_abort']) {
            ignore_user_abort($abort);
        }
        if (!$result) {
            // maybe the cache is corrupted, so we remove it !
            if ($this->_options['logging']) {
                $this->_log("Zend_Cache_Core::save() : impossible to save cache (id=$id)");
            }
            $this->remove($id);
            return false;
        }
        if ($this->_options['write_control']) {
            $data2 = $this->_backend->load($id, true);
            if ($data!=$data2) {
                $this->_log('Zend_Cache_Core::save() / write_control : written and read data do not match');
                $this->_backend->remove($id);
                return false;
            }
        }

        return true;
    }

    public function remove($id)
    {
        if (!$this->_options['caching']) {
            return true;
        }
        $id = $this->_id($id); // cache id may need prefix
        self::_validateIdOrTag($id);
        return $this->_backend->remove($id);
    }

    public function clean($mode = 'all', $tags = array())
    {
        if (!$this->_options['caching']) {
            return true;
        }
        if (!in_array($mode, array(Zend_Cache::CLEANING_MODE_ALL,
                                   Zend_Cache::CLEANING_MODE_OLD,
                                   Zend_Cache::CLEANING_MODE_MATCHING_TAG,
                                   Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG,
                                   Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG))) {
            Zend_Cache::throwException('Invalid cleaning mode');
        }
        self::_validateTagsArray($tags);
        return $this->_backend->clean($mode, $tags);
    }

    protected static function _validateIdOrTag($string)
    {
        if (!is_string($string)) {
            Zend_Cache::throwException('Invalid id or tag : must be a string');
        }
        if (substr($string, 0, 9) == 'internal-') {
            Zend_Cache::throwException('"internal-*" ids or tags are reserved');
        }
        /*
        if (!preg_match('~^[a-zA-Z0-9_]+$~D', $string)) {
            Zend_Cache::throwException("Invalid id or tag '$string' : must use only [a-zA-Z0-9_]");
        }
        */
    }

    protected static function _validateTagsArray($tags)
    {
        if (!is_array($tags)) {
            Zend_Cache::throwException('Invalid tags array : must be an array');
        }
        foreach($tags as $tag) {
            self::_validateIdOrTag($tag);
        }
        reset($tags);
    }
}
