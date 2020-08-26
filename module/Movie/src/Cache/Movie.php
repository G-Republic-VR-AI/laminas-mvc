<?php

namespace Movie\Cache;

use Laminas\Cache\StorageFactory;
use Laminas\Http\Request;
use Laminas\Http\Client;

class Movie {

    const JSON_REQUEST_UTl = 'http://localhost/json-files/showcase.json';
//    const JSON_REQUEST_UTl = 'https://mgtechtest.blob.core.windows.net/files/showcase.json';

    const SERVICE_UNAVAILABLE = "Service is down; Please try again later!";
    const CACHE_IMAGE_PATH = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "img" . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR;

    /**
     *
     * @var \Laminas\Cache\Storage\Adapter\Filesystem 
     */
    protected $cache = null;

    /**
     *
     * @var array 
     */
    protected $items = [];

    public function __construct() {
        $this->cache = StorageFactory::factory([
                    'adapter' => [
                        'name' => 'filesystem',
                        'options' => ['ttl' => 300],
                    ],
                    'plugins' => [
                        'exception_handler' => [
                            'throw_exceptions' => false
                        ],
                    ],
        ]);
    }

    public function getItems() {
        $body = $this->getItemsFromCache();
        $this->items = json_decode($body, true, 512, JSON_INVALID_UTF8_IGNORE);
        if (json_last_error()) {
            return $this->items = [];
        }
        foreach ($this->items as $key => $item) {
            $this->items[$key] = $this->getItemImages($item);
//            var_dump($this->items[$key]);
//            exit;
        }
        return $this->items;
    }

    public function getItemImages($item) {
        foreach ($item['keyArtImages'] as $key => $value) {
            $cacheKey = $item['id'] . '-keyArtImages-' . $key;
            $image = $this->cacheImage($cacheKey, $value);
            if (false !== $image && !empty($image)) {
                $item['keyArtImages'][$key]['cachedImage'] = $image;
            } else {
                unset($item['keyArtImages'][$key]);
            }
        }

        return $item;
    }

    public function getItemsFromCache() {
        $cacheKey = 'read-json-cache-key';
        $result = $this->cache->getItem($cacheKey, $success);
        var_dump($success);
        if (!$success) {
            var_dump(__METHOD__ . __LINE__);
            $result = $this->readJsonFromRequest();
            $this->cache->setItem($cacheKey, $result);
        }
        return $result;
    }

    private function readJsonFromRequest() {
        var_dump(__METHOD__ . __LINE__);
        try {
            $request = new Request();
            $client = new Client();
            $request->setMethod(Request::METHOD_GET);
            $request->setUri(self::JSON_REQUEST_UTl);
            $response = $client->send($request);
            if (200 != $response->getStatusCode()) {
                return "";
            }
        } catch (Exception $ex) {
            throw new \Exception(self::SERVICE_UNAVAILABLE, 0x200001);
        }

        return $response->getBody();
    }

    private function cacheImage($cacheKey, $item) {
        $result = $this->cache->getItem($cacheKey, $success);
        if (!$success) {
            $result = $this->getMedia($cacheKey, $item['url']);
            $this->cache->setItem($cacheKey, $result);
        }
        return $result;
    }

    private function getMedia($cacheKey, $url) {
        $filename = $cacheKey . ".jpg";
        try {
            if (file_exists(self::CACHE_IMAGE_PATH . $filename)) {
                return $filename;
            }
            $request = new Request();
            $client = new Client();
            $request->setMethod(Request::METHOD_GET);
            $request->setUri($url);
            $response = $client->send($request);
            if (200 === $response->getStatusCode()) {
                $body = $response->getBody();
                file_put_contents(self::CACHE_IMAGE_PATH . $filename, $body);
                return $filename;
            }
        } catch (Exception $ex) {
            throw new \Exception(self::SERVICE_UNAVAILABLE, 0x200001);
        }
        return false;
    }

}
