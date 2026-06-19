<?php

namespace AppKernel\View\Helper\Config;

use Laminas\View\Helper\AbstractHelper;

class ConfigHelper extends AbstractHelper {

    private $em;
    private $container;
    private $config;
    private $translator;

    public function __construct($em, $translator, $config, $container) {
        $this->em = $em;
        $this->translator = $translator;
        $this->config = $config;
        $this->container = $container;
    }

    public function __invoke() {
        return $this;
    }

    /**
     * @desc get setting values in database
     * @param type string $type 
     */
    public function getSettings($type = 'general') {

        $settings = $this->config['settings'];
        if ($type == 'all'):
            $settings['general'] = $settings['general' . CURRENT_SYSTEM] ?? $settings['general'] ?? [];
            unset($settings['general' . CURRENT_SYSTEM]);
            $settings['media'] = $settings['media' . CURRENT_SYSTEM] ?? $settings['media'] ?? [];
            unset($settings['media' . CURRENT_SYSTEM]);
            return $settings;
        else:
            return [$type => $settings[$type . CURRENT_SYSTEM] ?? $settings[$type] ?? []];
        endif;
    }

    public function getConfigs($type = '') {
        if ($type == 'all'):
            return $this->config;
        endif;

        $config = $this->config[$type] ?? [];
        return $config;
    }
}
