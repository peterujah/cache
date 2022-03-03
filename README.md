# cache
A simple php file cache

## Installation

Installation is super-easy via Composer:
```md
composer require peterujah/cache
```
# USAGES

Initialize DBController with configuration array

```php
$cache = new Peterujah\NanoBlock\Cache("CACHE_NAME", __DIR__ . "/temp/caches/");
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

