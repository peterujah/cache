# cache
A simple php file cache.
The Cache is designed to simplify the process of caching data in PHP applications. It provides various methods for configuration, data caching, retrieval, and cache management. It helps to reduce database queries, API calls, or expensive computations by storing the results in the cache.

## Installation

Installation is super-easy via Composer:
```md
composer require peterujah/cache
```
# USAGES

Initialize DBController with configuration array

```php
use Peterujah\NanoBlock\Cache;
$cache = new Cache("CACHE_NAME", __DIR__ . "/temp/caches/");
```

Query database and save response for later use

```php
$cache->setExpire(7200);
$user = $cache->onExpired("LIST", function () use($connConfig, $user_id){
	$conn_handler = new Peterujah\NanoBlock\DBController($connConfig);
	$conn_handler->prepare('
	      SELECT * FROM user_table
	      WHERE user_id = :fund_user_id
	      LIMIT 1
	');
	$conn_handler->bind(':fund_user_id', $user_id);
	$conn_handler->execute();
	$user = $conn_handler->getOne();
	$conn_handler->free();
	return  array(
	    "user" => $user,
	    "time" => time(),
	    "morething" => "More"
	);
});
```

 Sets the cache debugging mode, the default is false
 
```php
$cache->setDebugMode(true|false);
```

Sets the cache file extension type default is JSON
```php
$cache->setExtension(Cache::PHP | Cache::JSON | Cache::TEXT);
```

Enable the cache to store secure data available only with php file extension, the default is true.

```php
$cache->enableSecureAccess(true | false);
```
