<?php

namespace AppKernel\Listeners;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;

class StaticCacheListener extends AbstractListenerAggregate
{
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_FINISH, [$this, 'onFinish'], -100);
    }

    public function onFinish(MvcEvent $e)
    {
        $response = $e->getResponse();
        $content  = $response->getBody();

        $uri = $e->getRequest()->getUri()->getPath();
        if (strpos($uri, '/admin') === 0) {
            return; // bỏ qua trang admin
        }
       
        $cacheDir = PATH_ROOT . '/cache' . $uri;
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        file_put_contents($cacheDir . '/index.html', $content);
    }
}
