<?php 
/**
 * Cache - A simple php file cache
 * @author      Peter Chigozie(NG) peterujah
 * @copyright   Copyright (c), 2021 Peter(NG) peterujah
 * @license     MIT public license
 */
namespace Peterujah\NanoBlock;
use \Generator;
use \Exception;
use \InvalidArgumentException;
/**
 * Class Cache.
 */
class Cache {
   /**
     * Hold the cache extension type PHP
     * @var string PHP constant
    */
    public const PHP = ".catch.php";

    /**
     * Hold the cache extension type JSON
     * @var string JSON constant
    */
    public const JSON = ".json";

    /**
     * Hold the cache extension TEXT
     * @var string TEXT constant
    */
    public const TEXT = ".txt";

    /**
     * Hold the cache default file path and file name
     * @var string
    */
    protected const NANO = "_system.cache";

    /**
     * Hold the cache directory path
     * @var string
    */
    protected string $cacheLocation = '';

    /**
     * Hold the cache security status option
     * @var bool
    */
    protected bool $cacheSecurity = true;

    /**
     * Hold the cache file extension type
     * @var string
    */
    protected string $cacheFileExtension;

    /**
     * Hold the cache debug status option
     * @var bool
    */
    protected bool $isDebugging = false;
    
    /**
     * Hold the cache details array 
     * @var array
    */
    protected array $cacheArray = [];

    /**
     * Hold the cache expiry delete option
     * @var bool
    */
    private bool $canDeleteExpired;

    /**
     * Hold the cache base64 enabling option
     * @var bool
    */
    private bool $encodeInBase64 = true;

    /**
     * Hold the cache expiry time
     * @var int
    */
    private int $cacheTime = 60;

    /**
     * Hold the cache response data
     * @var mixed
    */
    private mixed $response = null;

    /**
     * Hold the cache response data
     * @var string
    */
    private string $cacheFilename = '';

    /**
     * Hold the cache response data
     * @var string
    */
    private string $cacheFilenameHashed = '';

    /**
     * Constructor.
     * @param string $name cache file name
     * @param string $path cache directory. Must end with "/"
     * @throws Exception if there is a problem fetching the cache
    */
	public function __construct(string $name = self::NANO, string $path = self::NANO . "/"){
        $this->setFilename($name);
        $this->setCacheLocation($path);
        $this->setExtension(self::JSON);
        $this->setDebugMode(false);
        $this->enableDeleteExpired(true);
        $this->onCreate();
	}

    /**
     * Set the new cache directory path
     * @param string $path cache directory must end with /
     * @return Catch $this
    */
    public function setCacheLocation(string $path): self{
        $this->cacheLocation = $path;
        return $this;
    }

    /**
     * Sets the new cache file name.
     * @param string $name cache file name
     * @return Catch $this
    */
    public function setFilename(string $name): self {
        $this->cacheFilename = $name;
        $this->cacheFilenameHashed = md5($name);
        return $this;
    }

    /**
     * Sets the cache file extension type
     * @param string $extension 
     * @return Catch $this
    */
    public function setExtension(string $extension): self{
        $this->cacheFileExtension = $extension;
        return $this;
    }

    /**
     * Sets the cache debugging mode
     * @param bool $mode 
     * @return Catch $this
    */
    public function setDebugMode(bool $mode): self{
        $this->isDebugging = $mode;
        return $this;
    }

    /**
     * Sets the cache expiry time duration
     * @param int $time 
     * @return Catch $this
    */
    public function setExpire(int $time = 60): self{
        $this->cacheTime = $time;
        return $this;
    }

    /**
     * Enable the cache to store data in base64 encoded.
     * @param bool $encode true or false
     * @return Catch $this
    */
    public function enableBase64(bool $encode): self{
        $this->encodeInBase64 = $encode;
        return $this;
    }

    /**
     * Enable the cache delete expired data
     * @param bool $allow true or false
     * @return Catch $this
    */
    public function enableDeleteExpired(bool $allow): self{
        $this->canDeleteExpired = $allow;
        if($this->canDeleteExpired){
            $this->removeIfExpired();
        }
        return $this;
    }

    /**
     * Enable the cache to store secure data in php file extension.
     * @param bool $encode true or false
     * @return Catch $this
    */
    public function enableSecureAccess(bool $secure): self{
        $this->cacheSecurity = $secure;
        return $this;
    }


    /**
     * Gets Combines directory, filename and extension into a full filepath
     * @return string
    */
    public function getCacheFilePath(): string {
        return $this->cacheLocation . $this->cacheFilenameHashed . $this->cacheFileExtension;
    }

    /**
     * Creates cache timestamp expired status
     * @param int $timestamp old timestamp
     * @param int $expiration The number of seconds after the timestamp expires
     * @return bool true or false
    */
    private function diffTime(int $timestamp, int $expiration): bool {
        return (time() - $timestamp) >= $expiration;
    }

    /**
     * Loads, create, update and delete cache with fewer options
     * @param string $key cache key
     * @param object $cacheCallback Callback called when data needs to be refreshed.
     * @return mixed|null Data currently stored under key
     * @throws Exception if the file cannot be saved
    */
    public function onExpired(string $key, object $cacheCallback): mixed {
        return $this->widthExpired($key, $cacheCallback, $this->cacheTime, false);
    }

    /**
     * Loads, create, update and delete cache with more options
     * @param string $key cache key
     * @param int $time cache expiry time
     * @param bool $lock lock catch to avoid deletion even when cache time expire
     * @param object $cacheCallback Callback called when data needs to be refreshed.
     * @return mixed|null Data currently stored under key
     * @throws Exception if the file cannot be saved
    */
    public function widthExpired(string $key, object $cacheCallback, int $time, bool $lock): mixed {
		if($this->isDebugging){
            $this->response = $cacheCallback();
			return $this->response;
		}

        if ($this->hasExpired($key)){
            $this->response = $cacheCallback();
            if(!empty($this->response)){
                $this->buildData($key, $this->response, $time, $lock);
            }
            return $this->response;
        }else{
            $this->response = $this->retrieveData($key);
        }
        return $this->response;
    }

    /**
     * Loads, create, update and delete cache with fewer options, without return
     * @param string $key cache key
     * @param object $cacheCallback Callback called when data needs to be refreshed.
     * @throws Exception if the file cannot be saved
    */

    public function onOneExpired(string $key, object $cacheCallback): void {
        $this->widthOneExpired($key, $cacheCallback, $this->cacheTime, false);
    }

    /**
     * Loads, create, update and delete cache with more options, but no return
     * @param string $key cache key
     * @param int $time cache expiry time
     * @param bool $lock lock catch to avoid deletion even when cache time expire
     * @param object $cacheCallback Callback called when data needs to be refreshed.
     * @throws Exception if the file cannot be saved
    */
     public function widthOneExpired(string $key, object $cacheCallback, int $time, bool $lock): void {
        if($this->isDebugging){
            $this->response = $cacheCallback();
        }

        if ($this->hasExpired($key)){
            $this->response = $cacheCallback();
            if(!empty($this->response)){
                $this->buildData($key, $this->response, $time, $lock);
            }
        }else{
            $this->response = $this->retrieveData($key);
        }
    }


    /**
     * Gets cache response data all or by key
     * @param string $key cache response key
     * @return mixed
    */
    public function get(?string $key=null): mixed{
        return (empty($key) ? $this->response : ($this->response[$key]??null));
    }

    /**
     * Gets cache response data row key by default
     * @return mixed
    */
    public function row(): mixed{
        return $this->response["row"]??[];
    }

    /**
     * Creates, Reloads and retrieve cache once class is created
     * @return object $this
     * @throws Exception if there is a problem loading the cache
    */
    protected function onCreate(): self {
        $this->cacheArray = is_readable($this->getCacheFilePath()) ? $this->fetchCatchData() : [];
        return $this;
    }

    /**
     * Checks if cache key exist
     * @param string $key cache key
     * @return bool true or false
    */
    public function hasCached(string $key): bool {
        return isset($this->cacheArray[$key]);
    }

    /**
     * Remove expired cache by key
     * @return int number of deleted keys
     * @throws Exception if the file cannot be saved
    */
    public function removeIfExpired(): int {
        $counter = 0;
        foreach ($this->cacheArray as $key => $value) {
            if ($this->hasExpired($key) && !$value["lock"]) {
                //$this->remove($key);
                unset($this->cacheArray[$key]);
                $counter++;
            }
        }

        if ($counter > 0){
            $this->writeCacheData();
        }
        return $counter;
    }

    /**
     * Checks if the cache timestamp has expired by key
     * @param string $key cache key
     * @return bool true or false
    */
    public function hasExpired(string $key): bool {
        if (!$this->hasCached($key)){
            return true;
        }

        $item = $this->cacheArray[$key];
        return $this->diffTime($item["time"], $item["expire"]);
    }

    /**
     * Deletes data associated with $key
     * @param string $key cache key
     * @return bool true or false
     * @throws Exception if the file cannot be saved
    */
    public function remove(string $key): bool {
        if (!$this->hasCached($key)) {
            return false;
        }
        unset($this->cacheArray[$key]);
        $this->writeCacheData();
        return true;
    }

    /**
     * Deletes data associated array of keys
     * @param array $array cache keys
     * @return Generator
     * @throws Exception if the file cannot be saved
    */
    public function removeList(array $array): Generator {
        foreach($array as $key){
            yield $this->remove($key);
        }
    }

    /**
     * Builds cache data to save
     * @param string $key cache keys
     * @param string|int|array|object $data cache data
     * @param string $expiration cache expiration time
     * @param string $lock cache lock expiry deletion
     * @return object $this
     * @throws InvalidArgumentException if the file cannot be saved
    */
    protected function buildData(string $key,  $data, int $expiration = 60, $lock = false): self {
        if(!is_string($key)) {
            throw new InvalidArgumentException('$key must be a string, got type "' . get_class($key) . '" instead');
        }

        $this->cacheArray[$key] = array(
            "time" => time(),
            "expire" => ($this->isDebugging ? 1 : $expiration),
            "data" => ($this->encodeInBase64 ? @base64_encode(@serialize($data)) : @serialize($data)) ,
            "lock" => $lock
        );
        $this->writeCacheData();
        return $this;
    }

    /**
     * Fetch cache data from disk
     * @return mixed $data
    */
    protected function fetchCatchData(): mixed {
        $filepath = $this->getCacheFilePath();
        $file = file_get_contents($filepath);

        if (!$file) {
            throw new Exception("Cannot load cache file! ({$this->cacheFilename})");
        }

        $data = unserialize($this->removeSecurity($file));

        if ($data === false) {
            unlink($filepath);
            throw new Exception("Cannot unserialize cache file, cache file deleted. ({$this->cacheFilename})");
        }

        if (!isset($data["hash-sum"])) {
            unlink($filepath);
            throw new Exception("No hash found in cache file, cache file deleted");
        }

        $hash = $data["hash-sum"];
        unset($data["hash-sum"]);

        if ($hash !== md5(serialize($data))) {
            unlink($filepath);
            throw new Exception("Cache data miss-hashed, cache file deleted");
        }

        return $data;
    }

    /**
     * Remove the security line in php file cache
     * @param string $str cache string
     * @return string cache text without the first security line or false on failure
    */
    protected function removeSecurity(string $str): string {
        $position = strpos($str, PHP_EOL);
        if ($position === false){
            return $str;
        }
        return substr($str, $position + 1);
    }

    /**
     * Retrieve cache data from disk
     * @param string $key cache key
     * @return mixed returns data if $key is valid and not expired, NULL otherwise
     * @throws Exception if the file cannot be saved
    */
    public function retrieveData(string $key): mixed {
        if($this->canDeleteExpired){
            $this->removeIfExpired();
        }
        if (!isset($this->cacheArray[$key])){
            return null;
		}
        $data = $this->cacheArray[$key];
       return unserialize($this->encodeInBase64 ? base64_decode($data["data"]) : $data["data"]);    
    }

    /**
     * Clears the cache
     * @throws Exception if the file cannot be saved
    */
    public function clearCache(): void {
        $this->cacheArray = [];
        $this->writeCacheData();
    }

    /**
     * Write the cache data disk.
     * @return Catch $this
    */
    protected function writeCacheData(): self {
        if (!file_exists($this->cacheLocation)){
            mkdir($this->cacheLocation, 0755, true);
		}
        $cache = $this->cacheArray;
        $cache["hash-sum"] = md5(serialize($cache));
		$writeLine = '';
		if($this->cacheFileExtension == self::PHP && $this->cacheSecurity){
			$writeLine = '<?php header("Content-type: text/plain"); die("Access denied"); ?>' . PHP_EOL;
		}

        $writeLine .= serialize($cache);
        $saved = file_put_contents($this->getCacheFilePath(), $writeLine);
    
        if ($saved === false) {
            throw new Exception("Cannot save cache");
        }

        return $this;
    }
	
	/**
     * Remove cache file
     * @return bool true if file path exist else false
    */
    public function removeCache(): bool {
		$fileCache = $this->getCacheFilePath();
		if(file_exists($fileCache)){
			unlink($fileCache);
			return true;
		}
		return false;
    }

    /**
     * Remove cache file from disk with full path
     * @param string $path cache full path /
     * @param array $filenames cache file array names
     * @param string $extension cache file extension type
     * @return bool
    */
    public function removeCacheDisk(string $path, array $filenames, string $extension = self::JSON): bool {
        $success = true;
        foreach($filenames as $name){
            $fileCache = $path . md5($name) . $extension;
            if(file_exists($fileCache)){
                if (!unlink($fileCache)) {
                    $success = false;
                }
            }
        }
        return $success;
    }
}
