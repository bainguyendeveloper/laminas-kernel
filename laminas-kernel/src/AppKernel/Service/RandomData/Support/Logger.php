<?php

namespace AppKernel\Service\RandomData\Support;

use AppKernel\Service\RandomData\Core\Context;

class Logger {

    public function getFile(string $name, $folder = '') {
        if(!$folder):
            $folder = date('Y-m-d');
        endif;
        $dir = PATH_DATA . '/logs/testrunner/' . $folder;
        if (!is_dir($dir)):
            mkdir($dir, 0777, true);
        endif;

        return $dir . '/' . $name . '.json';
    }

    public function save(Context $context, string $name,$seed=0): string {
        $folder = ($seed??date('Y-m-d'));
        $file = $this->getFile($name, $folder);
        file_put_contents($file, json_encode($context));

        return $file;
    }
}
