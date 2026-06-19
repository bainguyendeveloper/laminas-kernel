<?php

namespace AppKernel\View\Helper\User;

use Laminas\View\Helper\AbstractHelper,
    Laminas\ServiceManager\ServiceLocatorAwareInterface,
    Laminas\ServiceManager\ServiceLocatorInterface;

class UserHelper extends AbstractHelper {

    private $em;
    private $container;
    private $config;
    private $translator;
    private $cacheEnable;
    private $cacheTime;

    public function __construct($em, $translator, $config, $container) {
        $this->em = $em;
        $this->translator = $translator;
        $this->config = $config;
        $this->container = $container;
        $settings = $this->getSettings();
        $this->cacheEnable = (bool) ($settings['general']['cacheenable'] ?? false);
        $this->cacheTime = (int) ($settings['general']['cachetime'] ?? false);
    }

    public function __invoke() {
        return $this;

//        $args = func_get_args();
//        if (!isset($args[0])) {
//            throw new \RuntimeException("Function Name can not empty");
//        }
//        $function = $args[0];
//        unset($args[0]);
//        return call_user_func_array([$this, $function], $args);
    }

    public function generatorAccessToken($leng = 64) {
        return bin2hex(openssl_random_pseudo_bytes($leng));
    }

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

    public function getNotifications($uid = 0) {
        if (!$uid):
            return ['total' => 0, 'items' => []];
        endif;
//        $now->setTimeZone(new \DateTimeZone('GMT+7'));
        $orm = $this->em;
        $qb = $orm->getRepository('AppEntity\AppNotifications')->createQueryBuilder('a', 'a.id');
        $qb->where('a.user = ' . $uid);
        $qbtotal = clone $qb;
//        $cacheSettings = $this->config['settings']['caches'];
        $totals = $qbtotal->select('count(a.id) counter')->andWhere('a.readtime is null')->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-notifications-total-user-' . $uid)
                ->getResult();
        $items = $qb->setMaxResults(4)->orderBy('a.created', 'desc')->getQuery()->getResult();
        return ['total' => $totals[0]['counter'], 'items' => $items];
    }

    public function getRolesInlist(\AppEntity\AppAdminUsers $user = null, $currentRouter = '') {
        if (!$user):
            return [];
        else:
            $isAdmin = $user->getIsAdmin();

            if (!$currentRouter):
                $currentRouter = $this->container->get('Application')->getMvcEvent()->getRouteMatch()->getMatchedRouteName();
            endif;

            if ($isAdmin):
                $application = $this->container->get('Application');
                $menuConfig = $application->getServiceManager()->get('menuConfig');
                $allows = [
                    'add' => isset($menuConfig[$currentRouter . '/add']),
                    'edit' => isset($menuConfig[$currentRouter . '/edit']),
                    'editown' => isset($menuConfig[$currentRouter . '/edit-own']),
                    'approve' => isset($menuConfig[$currentRouter . '/approve']),
                    'view' => isset($menuConfig[$currentRouter . '/view']),
                    'delete' => isset($menuConfig[$currentRouter . '/delete']),
                    'detail' => isset($menuConfig[$currentRouter . '/detail']),
                    'import' => isset($menuConfig[$currentRouter . '/import']),
                    'export' => isset($menuConfig[$currentRouter . '/export'])
                ];
            else:
                $roles = $user->getRoles();
                $role = [];
                if ($roles && $rules = $roles->getRules()):
                    $role = json_decode($rules, true);
                endif;
                $allows = [
                    'add' => ($role[$currentRouter . '/add'] ?? false),
                    'edit' => ($role[$currentRouter . '/edit'] ?? false),
                    'editown' => ($role[$currentRouter . '/edit-own'] ?? false),
                    'approve' => ($role[$currentRouter . '/approve'] ?? false),
                    'view' => ($role[$currentRouter . '/view'] ?? false),
                    'delete' => ($role[$currentRouter . '/delete'] ?? false),
                    'detail' => ($role[$currentRouter . '/detail'] ?? false),
                    'import' => ($role[$currentRouter . '/import'] ?? false),
                    'export' => ($role[$currentRouter . '/export'] ?? false),
                ];
            endif;

            return $allows;
        endif;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles(\AppEntity\AppAdminUsers $user = null) {
        if (!$user):
            return [];
        else:
            $roles = $user->getRoles();
            if ($roles && $rules = $roles->getRules()):
                return json_decode($rules, true);
            else:
                return [];
            endif;
        endif;
    }

    public function findUser($uid = 0) {
        if (!$uid):
            return null;
        else:
            $em = $this->em;
            return $em->getRepository('AppEntity\AppUsers')->find($uid);
        endif;
    }
}
