<?php

namespace AppKernel\Plugin\Converter;

use Laminas\ServiceManager\AbstractPluginManager;

class ConverterContentPluginManager extends AbstractPluginManager {

    protected $invokableClasses = array(
        //represent invokables key
        'xls' => 'AppKernel\Plugin\Converter\Xls',
        'pdf' => 'AppKernel\Plugin\Converter\Pdf'
    );

    public function validatePlugin($plugin) {
        if ($plugin instanceof Plugin\PluginInterface) {
            // we're okay
            return;
        }

        throw new \InvalidArgumentException(sprintf(
                'Plugin of type %s is invalid; must implement %s\Plugin\PluginInterface', (is_object($plugin) ? get_class($plugin) : gettype($plugin)), __NAMESPACE__
        ));
    }

}
