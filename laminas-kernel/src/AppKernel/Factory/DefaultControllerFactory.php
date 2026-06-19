<?php
namespace AppKernel\Controller\Factory;

use Laminas\ServiceManager\FactoryInterface,
    Laminas\ServiceManager\ServiceLocatorInterface;

class DefaultControllerFactory implements FactoryInterface {

    /**
     * {@inheritdoc}
     */
    public function __invoke(\Interop\Container\ContainerInterface $container, $requestedName, $options = null) {
        
        return new $requestedName(
                $container->get('doctrine.entitymanager.orm_default'), 
                $container->get('translator'), $container->get('Config'),
                $container
                
        );
    }
    public function createService(ServiceLocatorInterface $serviceLocator) {
        
    }

}
