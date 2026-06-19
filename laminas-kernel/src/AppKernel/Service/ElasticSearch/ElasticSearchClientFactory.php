<?php

/**
 * http://ebanshi.cc/questions/2577908/how-to-integrate-elasticsearch-in-zend-framework-2-using-doctrine-2
 * http://stackoverflow.com/questions/25242126/how-to-integrate-elasticsearch-in-zend-framework-2-using-doctrine-2
 */

namespace AppKernel\Service\ElasticSearch;

use Laminas\ServiceManager\FactoryInterface,
    Laminas\ServiceManager\ServiceLocatorInterface,
    Elasticsearch\ClientBuilder;

class ElasticSearchClientFactory implements FactoryInterface {

    public function createService(ServiceLocatorInterface $serviceLocator) {
        $config = $serviceLocator->get('Config');
        $elasticsearch = ClientBuilder::create()->setHosts($config['elasticsearch'])->build();
        return $elasticsearch;
    }

}
