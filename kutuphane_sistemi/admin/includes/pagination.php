<?php
class SimpleCache {
    private $cache_dir;
    private $default_ttl;
    
    public function __construct($cache_dir = '../cache/', $default_ttl = 3600) {
        $this->cache_dir = $cache_dir;
        $this->default_ttl = $default_ttl;
        
        // Cache klasörünü oluştur
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    private function getCacheFile($key) {
        return $this->cache_dir . md5($key) . '.cache';
    }
    
    public function get($key, $default = null) {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return $default;
        }
        
        $data = file_get_contents($file);
        $cache_data = unserialize($data);
        
        // Süre kontrolü
        if ($cache_data['expires'] < time()) {
            unlink($file);
            return $default;
        }
        
        return $cache_data['data'];
    }
    
    public function set($key, $data, $ttl = null) {
        $ttl = $ttl ?: $this->default_ttl;
        $file = $this->getCacheFile($key);
        
        $cache_data = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        return file_put_contents($file, serialize($cache_data)) !== false;
    }
    
    public function delete($key) {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }
    
    public function clear() {
        $files = glob($this->cache_dir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }
    
    public function exists($key) {
        return $this->get($key, false) !== false;
    }
}

// Global cache instance
$cache = new SimpleCache();
?>