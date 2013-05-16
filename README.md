zf-memcached-nginx
==================

Alternate implementation of Zend_Cache_Frontend_Page and Zend_Cache_Backend_Memcached (Zend Framework 1.x)

Allows your ZF app to cache the raw response to memcached (as opposed to the original Zend_Cache_* implementations which add some meta data as well), allowing nginx to serve subsequent requests directly from memcached, without employing PHP.

### Why should I want nginx to serve the cached stuff directly?
Just see the benchmarks below.

### How do I know if the response is served from memcache directly?
Just add a custom header indicating the cache status. See the nginx example config for details.

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




## Benchmark

### nginx with direct fastcgi_pass to php

```shell

$> ab -n 10000 -c 100 -k http://jns.io/
This is ApacheBench, Version 2.3 <$Revision: 655654 $>
Copyright 1996 Adam Twiss, Zeus Technology Ltd, http://www.zeustech.net/
Licensed to The Apache Software Foundation, http://www.apache.org/

Benchmarking jns.io (be patient)
Completed 1000 requests
Completed 2000 requests
Completed 3000 requests
Completed 4000 requests
Completed 5000 requests
Completed 6000 requests
Completed 7000 requests
Completed 8000 requests
Completed 9000 requests
Completed 10000 requests
Finished 10000 requests


Server Software:        nginx
Server Hostname:        jns.io
Server Port:            80

Document Path:          /
Document Length:        15379 bytes

Concurrency Level:      100
Time taken for tests:   55.850 seconds
Complete requests:      10000
Failed requests:        0
Write errors:           0
Keep-Alive requests:    0
Total transferred:      155690000 bytes
HTML transferred:       153790000 bytes
Requests per second:    179.05 [#/sec] (mean)
Time per request:       558.502 [ms] (mean)
Time per request:       5.585 [ms] (mean, across all concurrent requests)
Transfer rate:          2722.30 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   2.1      0      24
Processing:    24  556  34.8    554     628
Waiting:       16  554  34.9    552     627
Total:         40  556  33.2    554     628

Percentage of the requests served within a certain time (ms)
  50%    554
  66%    558
  75%    563
  80%    567
  90%    577
  95%    588
  98%    604
  99%    610
 100%    628 (longest request
```

Great. And what about nginx using memcached_pass?

### nginx with memcached_pass directly fetching our framework-cached stuff.

```
$> ab -n 10000 -c 100 -k http://jns.io/
This is ApacheBench, Version 2.3 <$Revision: 655654 $>
Copyright 1996 Adam Twiss, Zeus Technology Ltd, http://www.zeustech.net/
Licensed to The Apache Software Foundation, http://www.apache.org/

Benchmarking jns.io (be patient)
Completed 1000 requests
Completed 2000 requests
Completed 3000 requests
Completed 4000 requests
Completed 5000 requests
Completed 6000 requests
Completed 7000 requests
Completed 8000 requests
Completed 9000 requests
Completed 10000 requests
Finished 10000 requests


Server Software:        nginx
Server Hostname:        jns.io
Server Port:            80

Document Path:          /
Document Length:        15341 bytes

Concurrency Level:      100
Time taken for tests:   2.208 seconds
Complete requests:      10000
Failed requests:        0
Write errors:           0
Keep-Alive requests:    9945
Total transferred:      155219725 bytes
HTML transferred:       153410000 bytes
Requests per second:    4529.94 [#/sec] (mean)
Time per request:       22.075 [ms] (mean)
Time per request:       0.221 [ms] (mean, across all concurrent requests)
Transfer rate:          68665.56 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   1.1      0      14
Processing:     0   22  36.1      5     321
Waiting:        0   21  36.1      4     321
Total:          0   22  36.3      5     321

Percentage of the requests served within a certain time (ms)
  50%      5
  66%     20
  75%     36
  80%     44
  90%     61
  95%     73
  98%     84
  99%    137
 100%    321 (longest request)
 ```
