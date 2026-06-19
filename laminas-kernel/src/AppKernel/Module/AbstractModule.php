<?php

namespace AppKernel\Module;

use Laminas\Mvc\MvcEvent,
    Laminas\ModuleManager\Feature\AutoloaderProviderInterface,
    Laminas\ModuleManager\Feature\ConfigProviderInterface,
    Laminas\ModuleManager\Feature\ViewHelperProviderInterface,
//    Laminas\Mvc\ModuleRouteListener,
    Laminas\View\Model\ViewModel;

class AbstractModule implements AutoloaderProviderInterface, ConfigProviderInterface, ViewHelperProviderInterface {

    // The onBootstrap() method is called for every module implementing this feature, on every page request, 
    // and should only be used for performing lightweight tasks such as registering event listeners.
    public $event; //MvcEvent
    public $app; //Application
    public $sm; //ServiceManager
    public $eventManager; //ServiceManager
    public $moduleName; //ServiceManager
//    public $logger; //ServiceManager
    public $baseModel;

    /**
     * 1. getAutoloaderConfig() [nếu dùng]
     * 2. getConfig()
     * 3. getServiceConfig()
     * 4. getControllerConfig() / getControllerPluginConfig()
     * 5. getViewHelperConfig()
     * 6. onBootstrap(MvcEvent $e)
     */

    /**
     * 2. getAutoloaderConfig() [nếu dùng]
     */
    public function getAutoloaderConfig() {
        $paths = explode(DIRECTORY_SEPARATOR, $this->currentPathModule);
        $this->moduleName = end($paths);
        return [];
//        return [
//            'Laminas\Loader\ClassMapAutoloader' => [
//                __DIR__ . '/autoload_classmap.php',
//            ],
//            \Laminas\Loader\StandardAutoloader::class => [
//                'namespaces' => [
//                    $this->currentPathModule,
//                ],
//            ],
//        ];
    }

    public function getConfig() {



        $configFile = $this->currentPathModule . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'module.config.php';
        $controllerFolder = $this->currentPathModule . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $this->moduleName . DIRECTORY_SEPARATOR . 'Controller';

        if (!file_exists($controllerFolder)):
            $controllerFolder = realpath($this->currentPathModule . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Controller');
            if (!file_exists($controllerFolder)):
                $controllerFolder = realpath($this->currentPathModule . DIRECTORY_SEPARATOR . 'Controller');
            endif;
        endif;

        $nameSpace = str_replace('\Module', '', get_called_class());
        $files = scandir($controllerFolder);
        $factories = [];
        foreach ($files as $file):
            if (substr($file, -4) !== '.php'):
                continue;
            endif;

            $className = $nameSpace . '\\Controller\\' . pathinfo($file, PATHINFO_FILENAME);
            if (class_exists($className)):
                $factories[$className] = \AppKernel\Controller\Factory\DefaultControllerFactory::class;
            endif;
        endforeach;
        $defaultConfig = [
            'view_manager' => [
                'strategies' => ['ViewJsonStrategy'],
                'template_path_stack' => [
                    PATH_TEMPLATE . '/views',
                    $this->currentPathModule . '/view',
                ],
            ],
            // should not load this service from global configuration
            'service_manager' => [
                'factories' => [],
                'invokables' => []
            ],
            'controllers' => [
                'factories' => $factories,
                'initializers' => [],
            ],
            // Placeholder for console routes
            'console' => [
                'router' => [
                    'routes' => [],
                ],
            ],
        ];

        if (is_file($configFile)):
            $_configFile = include $configFile;
            $_configFile['router'] = array_merge(['router_class' => \Laminas\Mvc\I18n\Router\TranslatorAwareTreeRouteStack::class], $_configFile['router']);

            return array_merge($defaultConfig, $_configFile);
        else:
            return $defaultConfig;
        endif;
    }

    /**
     * 3. getServiceConfig()
     */
    public function getServiceConfig() {

        $configFile = $this->currentPathModule . '/config/services.config.php';
        if (is_file($configFile)):
            return include $configFile;
        else:
            return [];
        endif;
    }

    /**
     * 4. getControllerConfig()
     */
    public function getControllerConfig() {
        return [];
    }

    /**
     * 5. getViewHelperConfig()
     */
    public function getViewHelperConfig() {
        return [];
    }

    /**
     * 6. onBootstrap(MvcEvent $e)
     */
    public function onBootstrap(MvcEvent $e) {
        $this->event = $e;
        $this->app = $this->event->getApplication();
        $this->sm = $this->app->getServiceManager();

        $this->eventManager = $this->app->getEventManager();
        $this->eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'onDispatchError'), 0);
        $this->eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, array($this, 'onRenderError'), 0);
        
//        $moduleRouteListener = new ModuleRouteListener();
//        $moduleRouteListener->attach($this->eventManager);
    }

    public function setLocale($keyLang = '') {

        $translator = $this->app->getServiceManager()->get('translator');
        $lang = ($_COOKIE[$keyLang] ?? 'vi');
        $locale = in_array($lang, ['vi', 'en']) ? $lang : 'vi';
        if ($locale == 'vi'):
            $locale = 'vi_VN';
        elseif ($locale == 'en'):
            $locale = 'en_US';
        endif;
        $translator->setLocale($locale);
    }

    public function onDispatchError($event) {
        return $this->getModelError($event);
    }

    public function onRenderError($event) {
        return $this->getModelError($event);
    }

    public function getModelError($event) {
        if (!$this->baseModel):
            $this->baseModel = new ViewModel();
        endif;
        $currentModel = $event->getResult();
        if ($event->getError() == "ACL_ACCESS_DENIED"):
            $authService = $event->getApplication()->getServiceManager()->get('Laminas\Authentication\AuthenticationService');
            if ($authService->hasIdentity()):

                $this->baseModel->setTemplate('layout/layout');

                $model = new ViewModel(['test' => 123]);
                $model->setTemplate('error/403');

                $this->baseModel->addChild($model);
                $this->baseModel->setTerminal(true);

                $event->setViewModel($this->baseModel);

                $response = $event->getResponse();
                $response->setStatusCode(403);

                $event->setResponse($response);
                $event->setResult($baseModel);
            else:
                $url = $event->getRouter()->assemble(array(), array('name' => 'home/login'));
                $response = $event->getResponse();
                $response->getHeaders()->addHeaderLine('Location', $url);
                $response->setStatusCode(302);
                $response->sendHeaders();
                return $response;
            endif;
        endif;

        // Find out what the error is
        $exception = $currentModel->getVariable('exception');
        if (!$event->getRouteMatch()):
            $url = $event->getRouter()->assemble(array(), array('name' => 'home'));
            $response = $event->getResponse();
            $response->getHeaders()->addHeaderLine('Location', $url);
            $response->setStatusCode(302);
            $response->sendHeaders();
            return $response;
        endif;

        if ($exception):
            // find the previous exceptions
            $messages = [];
            // ... write log
            $_exception = $exception;
            do {
                $trace = $exception->getTrace();
                $functionName = $trace[0]['function'] ?? 'Global Scope';
                $className = $trace[0]['class'] ?? '';
                $messages[] = "* " . $exception->getMessage();
                $loggerManager = new \AppKernel\Service\LoggerManager($className."::".$functionName);
                $logger = $loggerManager->baseLogger;
                $_tas = $exception->getTraceAsString();
                $_file = $exception->getFile();
                $rPath = realpath(PATH_ROOT.'/../');
                $tas = str_replace($rPath, 'ROOT', $_tas);
                $file = str_replace($rPath, 'ROOT', $_file);
                $logger->error(
                        sprintf(
                                "\n%s:%d%s (%d) \n[%s] \n%s \n----------------------------------------------------------------------\n", $file, $exception->getLine(), $exception->getMessage(), $exception->getCode(), get_class($exception), $tas
                        )
                );
            } while ($exception = $exception->getPrevious());
            $env = getenv('APPLICATION_ENV') ?: 'production';

            $this->baseModel->setTemplate('layout/layout');
            $model = new ViewModel();
            if ($env == 'development'):
                $model->setTemplate('error/503debug');
            else:
                $model->setTemplate('error/503');
            endif;
            $model->setVariable('exception', $_exception);
            $this->baseModel->addChild($model);
            $this->baseModel->setTerminal(true);

            $event->setViewModel($this->baseModel);

            $response = $event->getResponse();
            if ($this->baseModel instanceof \Laminas\View\Model\JsonModel):

                $resp = [
                    'code' => $response->getStatusCode(),
                    'msg' => $_exception->getMessage(),
                ];
                $model->setVariable('body', $resp);
            else:
                $response->setStatusCode(503);
            endif;
            $event->setResponse($response);
            $event->setResult($this->baseModel);
        else:

        endif;
    }

    public function initAcl() {

        $em = $this->sm->get('doctrine.entitymanager.orm_default');
        $config = $this->sm->get('Config');
        $acl = new \Laminas\Permissions\Acl\Acl();
        $roles = $this->getRoles($em, $config['router']['routes'] ?? []);

        foreach ($roles as $role => $resources):
//            if (($key = array_search('appmobile', $resources)) !== FALSE):
//                unset($resources[$key]);
//            endif;
            $role = new \Laminas\Permissions\Acl\Role\GenericRole($role);
            $acl->addRole($role);
//            $allResources = [];
            $allResources = $resources;
            //adding resources
            foreach ($allResources as $resource):
                if (!$acl->hasResource($resource)):
                    $acl->addResource(new \Laminas\Permissions\Acl\Resource\GenericResource($resource));
                endif;
            endforeach;
            //adding restrictions
            foreach ($allResources as $resource):
                $acl->allow($role, $resource);
            endforeach;
        endforeach;
        $this->event->getViewModel()->acl = $acl;
    }

    // Em tim cach move ra Midleware gium anh -- anh ko biet ly do la gi nhung ve y nghia cua ACL em khong nen placed trong Module nhu the nay
    public function checkAcl() {
        $route = $this->event->getRouteMatch()->getMatchedRouteName();
        $config = $this->sm->get('Config');

        $acl = $this->event->getViewModel()->acl;
        if ($acl->hasResource($route)):

            $authService = $this->sm->get('Laminas\Authentication\AuthenticationService');
            $user = $authService->getIdentity();
            $userRole = 'guest';
            $group_roles = array();
            // mac dinh la guest
            $isAllow = $acl->isAllowed($userRole, $route);
            if ($user):
                // neu la admin (chi duy nhat 1 account co va ko duoc xoa)
                if (method_exists($user, 'getGroups') && $groups = $user->getGroups()):
                    foreach ($groups as $group):
                        if ($role = $group->getRoles()):
                            $group_roles[] = $role->getId();
                        endif;
                    endforeach;
                endif;
                if (!$isAllow):
                    if ($user->getIsAdmin()):
                        $userRole = 'admin';
                        $isAllow = true;
                    else:
                        //neu user co role rieng
                        if ($role = $user->getRoles()):
                            $userRole = $role->getId();
                            $isAllow = $acl->isAllowed($userRole, $route) || $acl->isAllowed('user', $route);
                        else:
                            if (!empty($group_roles)):
                                foreach ($group_roles as $role):
                                    if ($isAllow = $acl->isAllowed($role, $route)):
                                        break;
                                    endif;
                                endforeach;
                            endif;
                        endif;
                    endif;
                else:
                    if ($user->getIsAdmin()):
                        $userRole = 'admin';
                    elseif ($role = $user->getRoles()):
                        $userRole = $role->getId();
                    endif;
                endif;
            endif;
            $view = $this->event->getViewModel();

            $resources = $acl->getResources();

            $allowResources = array();
            if ($user && !$user->getIsAdmin()):
                if (!empty($resources)):
                    foreach ($resources as $key => $resource):
                        if ($acl->isAllowed($userRole, $resource)):
                            $allowResources[] = $resources[$key];
                        else:
                            foreach ($group_roles as $role):
                                if ($acl->isAllowed($role, $resource)):
                                    $allowResources[] = $resources[$key];
                                endif;
                            endforeach;
                        endif;

                    endforeach;
                endif;
            else:
                $allowResources = $resources;
            endif;
            $menus = [];
            $menus['left'] = $this->getMenus($allowResources, $config['router']['routes']);
            $menus['top'] = $this->getMenus($allowResources, $config['router']['routes'], 'top');
            $menus['all'] = $this->getMenus($allowResources, $config['router']['routes'], 'all');
            $this->sm->setService('menuConfig', $menus['all']);

            $view->setVariable('resources', $allowResources);
            $view->setVariable('currentRouter', $route);
            $view->setVariable('menus', $menus);
            if (!$isAllow):
                if (!$user && ($route != 'home/login')):
                    $url = $this->event->getRouter()->assemble(array(), array('name' => 'home/login'));
                    $response = $this->event->getResponse();
                    $response->getHeaders()->addHeaderLine('Location', $url);
                    $response->setStatusCode(302);
                    $response->sendHeaders();
                    return $response;
                endif;

                $response = $this->event->getResponse();
                $response->setStatusCode(403);

                $this->event->setError('ACL_ACCESS_DENIED')->setParam('route', $route);
                $baseModel = new \Laminas\View\Model\ViewModel();
                $baseModel->setTemplate('layout/layout');

                $model = new \Laminas\View\Model\ViewModel(['test' => 123]);
                $model->setTemplate('error/403');

                $baseModel->addChild($model);
                $baseModel->setTerminal(true);

                $this->event->setViewModel($baseModel);

                $this->event->setResponse($response);
                $this->event->setResult($baseModel);
                return false;
            else:
                return true;
            endif;
        else:
            return true; /// cho phep tat ca truy cap vao link nay
        endif;
    }

    public function getRoles($em, $routers) {
        $roles = $em->getRepository('AppEntity\AppRoles')->findAll();
        $_roles = array(
            'guest' => [],
            'user' => [],
            'admin' => []
        );
        if ($routerEmbeded = $this->embedRouter($routers)):
            foreach ($routerEmbeded as $key => $embeded):
                if ($embeded['public'] == 'guest'):
                    $_roles['guest'][] = $key;
                endif;
                if ($embeded['public'] == 'user'):
                    $_roles['user'][] = $key;
                endif;
                $_roles['admin'][] = $key;
            endforeach;
        endif;
        if ($roles):
            foreach ($roles as $role):
                $rules = array_keys((array) json_decode($role->getRules()));
                $rules = array_merge($rules, $_roles['guest']);
                $rules = array_unique($rules);
                $_roles[$role->getId()] = $rules;
            endforeach;
            return $_roles;
        else:
            return $_roles;
        endif;
    }

    private function getOldMenus($resources, $routers, $menu = 'left') {
        $routerEmbeded = $this->embedRouter($routers);

        if ($menu == 'all'):
            foreach ($resources as $resource):
                $menus[$resource] = (isset($routerEmbeded[$resource]) && isset($routerEmbeded[$resource]['label'])) ? $routerEmbeded[$resource]['label'] : '';
            endforeach;

        else:
            $currentLevel = 0;
            $currentposition = 1;
            foreach ($resources as $resource):
                if (isset($routerEmbeded[$resource]) && isset($routerEmbeded[$resource]['position']) && in_array($menu, $routerEmbeded[$resource]['menu'])):
                    if (($menu != 'top') && $routerEmbeded[$resource]['level'] >= 3)://depth toi da la 2
                        continue;
                    endif;
                    if ($currentLevel >= $routerEmbeded[$resource]['level']):
                        $position = $this->menuPosition($menus, $routerEmbeded[$resource]['position']);
//                        $position = $routerEmbeded[$resource]['position'];
                        $menus[$position] = $routerEmbeded[$resource];
                        $menus[$position]['router'] = $resource;
                        $menus[$position]['position'] = $position;
                        $menus[$position]['children'] = [];
                        $currentposition = $position;
                    else:
                        if ($menus):
                            $position = $this->menuPosition($menus[$currentposition]['children'], $routerEmbeded[$resource]['position']);
                            $menus[$currentposition]['children'][$position] = $routerEmbeded[$resource];
                            $menus[$currentposition]['children'][$position]['router'] = $resource;
                            $menus[$currentposition]['children'][$position]['position'] = $position;
                            $menus[$currentposition]['children'][$position]['children'] = [];
                            $menus[$currentposition]['children'][$position]['test'] = '2';
                        else:
                            $position = $this->menuPosition($menus, $routerEmbeded[$resource]['position']);
                            $menus[$position] = $routerEmbeded[$resource];
                            $menus[$position]['router'] = $resource;
                            $menus[$position]['position'] = $position;
                            $menus[$position]['children'] = [];
                            $currentposition = $position;
                        endif;

                    endif;

//                    $currentLevel = $routerEmbeded[$resource]['level'];
                endif;
            endforeach;
            ksort($menus);
        endif;
//        print ('<pre>');
//        var_dump($menus); die;
        return $menus;
    }

    private function arrangeMenu($routerEmbeded = [], $menu = '', $parent = false, $menus = []) {
        if ($routerEmbeded):
            $_routerEmbeded = $routerEmbeded;
            foreach ($routerEmbeded as $key => $_r):
                if ($parent === (bool) $_r['parent']):
                    if (isset($_r['menu']) && $_r['menu'] && in_array($menu, $_r['menu'])):
                        if (!$_r['parent']):
                            $menus[$key] = $_r;
                            $menus[$key]['router'] = $key;
                        else:
                            if (isset($menus[$_r['parent']])):
                                $menus[$_r['parent']]['children'][$key] = $_r;
                                $menus[$_r['parent']]['children'][$key]['router'] = $key;

                            endif;
                        endif;
                    endif;
                    unset($_routerEmbeded[$key]);
                endif;
            endforeach;
            if ($_routerEmbeded):

                $menus = $this->arrangeMenu($_routerEmbeded, $menu, true, $menus);

            endif;
            return $menus;

        else:
            return [];
        endif;
    }

    private function getMenus($resources, $routers, $menu = 'left') {
        $routerEmbeded = $this->embedRouter($routers);
        $menus = [];
        if ($menu == 'all'):
            foreach ($resources as $resource):
                $menus[$resource] = (isset($routerEmbeded[$resource]) && isset($routerEmbeded[$resource]['label'])) ? $routerEmbeded[$resource]['label'] : '';
            endforeach;

        else:
            $currentLevel = 0;
            $currentposition = 1;
            foreach ($resources as $resource):
                if (isset($routerEmbeded[$resource]) && isset($routerEmbeded[$resource]['position']) && in_array($menu, $routerEmbeded[$resource]['menu'])):
                    if (($menu != 'top') && $routerEmbeded[$resource]['level'] >= 3)://depth toi da la 2
                        continue;
                    endif;
                    if ($currentLevel >= $routerEmbeded[$resource]['level']):
                        $position = $this->menuPosition($menus, $routerEmbeded[$resource]['position']);
//                        $position = $routerEmbeded[$resource]['position'];
                        $menus[$position] = $routerEmbeded[$resource];
                        $menus[$position]['router'] = $resource;
                        $menus[$position]['position'] = $position;
                        $menus[$position]['children'] = [];
                        $currentposition = $position;
                    else:
                        if ($menus):
                            $position = $this->menuPosition($menus[$currentposition]['children'], $routerEmbeded[$resource]['position']);
                            $menus[$currentposition]['children'][$position] = $routerEmbeded[$resource];
                            $menus[$currentposition]['children'][$position]['router'] = $resource;
                            $menus[$currentposition]['children'][$position]['position'] = $position;
                            $menus[$currentposition]['children'][$position]['children'] = [];
                            $menus[$currentposition]['children'][$position]['test'] = '2';
                        else:
                            $position = $this->menuPosition($menus, $routerEmbeded[$resource]['position']);
                            $menus[$position] = $routerEmbeded[$resource];
                            $menus[$position]['router'] = $resource;
                            $menus[$position]['position'] = $position;
                            $menus[$position]['children'] = [];
                            $currentposition = $position;
                        endif;

                    endif;

//                    $currentLevel = $routerEmbeded[$resource]['level'];
                endif;
            endforeach;
            ksort($menus);
        endif;
//        print ('<pre>');
//        var_dump($menus); die;
        return $menus;
    }

    private function menuPosition($menu = [], $position = 1) {
        if (isset($menu[$position])):
            return $this->menuPosition($menu, $position + 1);
        else:
            return $position;
        endif;
    }

    private function embedRouter($router = array(), $embeded = array(), $parentkey = '', $level = 0) {
        if (!empty($router)):

            foreach ($router as $key => $r):
                if (strpos($r['options']['defaults']['controller'], 'Application') === false):
                    continue;
                endif;
                if (!isset($embeded[$parentkey . $key])):

                    $embeded[$parentkey . $key] = $r['options']['defaults'];
                    $embeded[$parentkey . $key]['label'] = (isset($r['label'])) ? $r['label'] : '';
                    $embeded[$parentkey . $key]['grouplabel'] = ($r['grouplabel'] ?? '');
                    $embeded[$parentkey . $key]['menu'] = (isset($r['menu'])) ? (array) $r['menu'] : [];
                    $embeded[$parentkey . $key]['position'] = (isset($r['position'])) ? $r['position'] : 0;
                    $embeded[$parentkey . $key]['level'] = (isset($r['level'])) ? $r['level'] : $level;
                    $embeded[$parentkey . $key]['icon'] = (isset($r['icon'])) ? $r['icon'] : '';
                    $embeded[$parentkey . $key]['parent'] = (isset($r['parent'])) ? $r['parent'] : '';
                    if (isset($r['public'])):
                        $embeded[$parentkey . $key]['public'] = $r['public'];
                    else:
                        $embeded[$parentkey . $key]['public'] = false;
                    endif;
                endif;
                if (isset($r['child_routes'])):

                    if ($r['options']['defaults']['controller'] != 'Application\Dashboard\Controller\Index'):
                        $level++;
                    endif;

                    $embeded = $this->embedRouter($r['child_routes'], $embeded, $parentkey . $key . '/', $level);
                endif;
            endforeach;
        endif;

        return $embeded;
    }
}
