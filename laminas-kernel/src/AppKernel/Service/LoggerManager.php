<?php

namespace AppKernel\Service;

use Monolog\Logger;

class LoggerManager {

    public Logger $baseLogger;

    public function __construct($requestedName = '', $type = 'debug') {
        switch ($type):
            case 'info':
                $level = \Monolog\Logger::INFO;
                break;
            case 'notice':
                $level = \Monolog\Logger::NOTICE;
                break;
            case 'warning':
                $level = \Monolog\Logger::WARNING;
                break;
            case 'error':
                $level = \Monolog\Logger::ERROR;
                break;
            case 'critical':
                $level = \Monolog\Logger::CRITICAL;
                break;
            case 'alert':
                $level = \Monolog\Logger::ALERT;
                break;
            case 'emergency':
                $level = \Monolog\Logger::EMERGENCY;
                break;
            default:
                $level = \Monolog\Logger::DEBUG;
                break;
        endswitch;
        $logName = str_replace('\\', '-', mb_strtolower($requestedName));
        $pathInfo = pathinfo($logName);
        
        $logfolder = PATH_DATA . '/logs/' . date('Y-m-d');
        if ($pathInfo['dirname'] != '.'):
            $logfolder .= DS . $pathInfo['dirname'];
        endif;
        if (!file_exists($logfolder)):
            mkdir($logfolder, 0777, true);
        endif;
        $logPath = $logfolder. "/{$pathInfo['basename']}.log";
        $logger = new \Monolog\Logger($logName);
        $stream = new \Monolog\Handler\StreamHandler($logPath, $level);
        $formatter = new \Monolog\Formatter\LineFormatter(null, null, true, true); // 3rd param true = allowInlineLineBreaks
        $stream->setFormatter($formatter);
        $logger->pushHandler($stream);
        $this->baseLogger = $logger;
    }

//    public function get(string $moduleName): Logger {
//        $logger = clone $this->baseLogger;
//       
//            $logName = str_replace('\\', '-', mb_strtolower($moduleName));
//            $logPath = PATH_DATA . '/logs/' . date('Y-m-d') . "_{$logName}.log";
//            $stream = new \Monolog\Handler\StreamHandler($logPath, \Monolog\Logger::DEBUG);
//            $logger->pushHandler($stream);
//
//        return $logger;
//    }
}
