<?php

/**
 * @desc : Define new TYPE for ROUTE (default by Segment, Literal, ...)
 * @auth : Eugene L
 * @use :
 *  'rewrite' => array(
 *         'type' => 'Rubedo\Router\FrontofficeRoute',
 *          'options' => array(
 *              'route' => '/',
 *              'defaults' => array(
 *                  'controller' => 'Rubedo\Frontoffice\Controller\Index',
 *                  'action' => 'index'
 *              )
 *          )
 *      ),
 * ......
 */

namespace AppKernel\Router;

use Laminas\Mvc\Router\Http\RouteInterface;
use Laminas\Mvc\Router\Http\RouteMatch;
use phpDocumentor\Reflection\Types\Parent_;

class WebAppRouterRoute implements RouteInterface {

    /**
     * Default params
     *
     * @var array
     */
    protected $defaults = array();

    /**
     * Matched params
     *
     * @var array
     */
    protected $matchedParams = array();
    protected $locale = null;
    protected $uri = null;

    /*
     * (non-PHPdoc) @see \Laminas\Mvc\Router\RouteInterface::assemble()
     */

    public function assemble(array $params = array(), array $options = array()) {
        
    }
    public function match($request) {
        return parent::match($request);
    }
    public function getAssembledParams(){
        return parent::getAssembledParams();
    }
    public function factory($options = []){
        return parent::factory($options = []);
    }
}
