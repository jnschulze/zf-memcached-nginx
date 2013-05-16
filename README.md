zf-memcached-nginx
==================

Alternate implementation of Zend_Cache_Frontend_Page and Zend_Cache_Backend_Memcached (Zend Framework 1.x)

Allows your ZF app to cache the raw response to memcached (as opposed to the original Zend_Cache_* implementations which add some meta data as well), allowing nginx to serve subsequent requests directly from memcached, without employing PHP.

## Usage

### Configure your App
Do the following in your ZF Bootstrap file

```php
Zend_Registry::set('CACHE_ENABLED', true);

// these options don't differ from the original Zend_Cache_Frontend_Page implementation
$frontendOptions = array('lifetime' => 7200,
                         'debug_header' => false,
                         'default_options' => array('cache' => false),
                         'regexps' => array(
                             '^/$' => array('cache' => true),
));

/* default options
$backendOptions = array('servers' => array(array(
              'host' => 'localhost',
              'port' => 11211,
        'persistent' => true,
            'weight' => 1,
           'timeout' => 5,
    'retry_interval' => 15,
            'status' => true)));
*/

$cache = Zend_Cache::factory(new ZendExtra_Cache_Frontend_Page($frontendOptions),
                             new ZendExtra_Cache_Backend_RawMemcached());
                             
$fc = Zend_Controller_Front::getInstance();
$fc->registerPlugin(new ZendExtra_Controller_Plugin_PageCache($cache));
```

### Configure your web server
See the examples folder for details.
