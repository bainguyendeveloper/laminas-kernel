<?php

/**
 * http://ebanshi.cc/questions/2577908/how-to-integrate-elasticsearch-in-zend-framework-2-using-doctrine-2
 * http://stackoverflow.com/questions/25242126/how-to-integrate-elasticsearch-in-zend-framework-2-using-doctrine-2
 */

namespace AppKernel\Service\ElasticSearch;

interface ElasticSearchAwareInterface {

    public function getElasticSearchClient();

    public function setElasticSearchClient(\Elasticsearch\Client $client);
}
