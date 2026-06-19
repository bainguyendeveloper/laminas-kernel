<?php

namespace AppKernel\Controller\Factory;

use Laminas\ServiceManager\FactoryInterface,
    Laminas\ServiceManager\ServiceLocatorInterface;

class DefaultLoggerFactory implements FactoryInterface {

    /**
     * {@inheritdoc}
     */
    public function __invoke(\Interop\Container\ContainerInterface $container, $requestedName, $options = null) {

        $logName = str_replace('\\', '-',  mb_strtolower($requestedName));
        $logPath = $options['path'] ?? (PATH_DATA . '/logs/' . date('Y-m-d') . "_{$logName}.log");
        $logger = new \Monolog\Logger($logName);
        $stream = new \Monolog\Handler\StreamHandler($logPath, \Monolog\Logger::DEBUG);
        $formatter = new \Monolog\Formatter\LineFormatter(null, null, true, true); // 3rd param true = allowInlineLineBreaks
        $stream->setFormatter($formatter);
        $logger->pushHandler($stream);
        return $logger;
    }

    public function createService(ServiceLocatorInterface $serviceLocator) {
        
    }
}
