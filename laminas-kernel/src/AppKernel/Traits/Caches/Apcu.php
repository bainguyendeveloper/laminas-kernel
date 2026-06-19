<?php

namespace AppKernel\Traits\Caches;

trait ApcuTrait {

    public function getKeys() {
        $cacheInfo = [];
        if (function_exists('apcu_cache_info')):
            $cacheInfo = apcu_cache_info();
        endif;

        if (!empty($cacheInfo['cache_list'])):
            foreach ($cacheInfo['cache_list'] as $entry):
                $keys[] = $entry['info']; // Key của cache
            endforeach;
        endif;
        return $keys;
    }

    public function findKeys(string $key = '') {
        $cacheInfo = [];
        if (function_exists('apcu_cache_info')):
            $cacheInfo = apcu_cache_info();
        endif;
        $userKeys = [];
        if (!empty($cacheInfo['cache_list'])):
            foreach ($cacheInfo['cache_list'] as $entry):
                if (strpos($entry['info'], $key) !== false):
                    $userKeys[] = $entry['info'];
                endif;
            endforeach;
        endif;
        return $userKeys;
    }

    public function apcuflush() {
        // xoá toàn bộ code
        if (function_exists('apcu_clear_cache')):
            apcu_clear_cache();
        endif;
    }

    public function delete(string $key = '') {
        if (function_exists('apcu_exists') && function_exists('apcu_delete')):
            if (apcu_exists($key)):
                apcu_delete($key); // Xóa cache cũ
            endif;
        endif;
    }

    public function store(string $key = '', string $value = '', int $ttl = 3600) {
        if (function_exists('apcu_store')):
            apcu_store($key, $value, $ttl);
        endif;
    }

    public function deleteByKey(string $key = '') {
        $cacheInfo = [];
        if (function_exists('apcu_cache_info')):
            $cacheInfo = apcu_cache_info();
        endif;
        if (!empty($cacheInfo['cache_list'])):
            foreach ($cacheInfo['cache_list'] as $entry):
                if (strpos($entry['info'], $key) !== false):
                    $this->delete($entry['info']);
                endif;
            endforeach;
        endif;
    }
}
