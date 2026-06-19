<?php

namespace AppKernel\View\Helper\Entity;

use Laminas\View\Helper\AbstractHelper;

class EntityHelper extends AbstractHelper {

    private $em;
    private $container;
    private $config;
    private $cacheEnable;
    private $cacheTime;
    private $translator;

    public function __construct($em, $translator, $config, $container) {
        $this->em = $em;
        $this->translator = $translator;
        $this->config = $config;
        $this->container = $container;
        $settings = $this->getSettings();
        $this->cacheEnable = (bool) $settings['general']['cacheenable'];
        $this->cacheTime = (int) $settings['general']['cachetime'];
    }

    public function __invoke() {
        return $this;
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

    public function getTotalVisitor() {
        return 1000 + (int) $this->em->getRepository('AppEntity\AppVisitorStatistic')->createQueryBuilder('a')->select('count(a.id)')->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'total-visitor')
                        ->getSingleScalarResult();
    }

    public function getBanners($id = 0, $limit = 6) {
        if (!$id) :
            return [];
        endif;
        return $this->em->getRepository('AppEntity\AppBanners')
                        ->createQueryBuilder('a')->where('a.status = 1 and a.position =' . $id)
                        ->join('a.position', 'c')->andWhere('c.status=1')
                        ->setMaxResults($limit)
                        ->orderBy('a.ordering', 'asc')
                        ->addOrderBy('a.id', 'desc')
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-banners-list-category-' . $id)
                        ->getResult();
    }

//    public function getTranslateEntity($entity = null, $lang = 'ko') {
//        if (!$entity) :
//            return null;
//        endif;
//        $class = get_class($entity);
//
//        $classes = explode('\\', $class);
//        $_entity = '';
//        if (($counter = count($classes)) <= 2) :
//            $_entity = $class;
//        else :
//            $_entity = $classes[$counter - 2] . '\\' . end($classes);
//        endif;
//        $eid = $entity->getId();
//        $cachekey = str_replace('\\', '_', strtolower($_entity)) . "_{$eid}_{$lang}";
//        $cacheSettings = $this->config['settings']['caches'];
//        return $this->em->getRepository($_entity)
//                        ->createQueryBuilder('a')
//                        ->where("a.lang = '$lang'")
//                        ->andWhere("a.translateOf=$eid")
//                        ->addOrderBy('a.id', 'desc')
//                        ->setMaxResults(1)
//                        ->getQuery()
//                        ->useQueryCache(true)
//                        ->useResultCache($cacheSettings['app-translate-entity']['enable'], $cacheSettings['app-translate-entity']['ttl'], $cacheSettings['app-translate-entity']['key'] . '-' . $cachekey)
//                        ->getOneOrNullResult();
//    }

    public function getCategoriesOptions($params = []) {

        if (!class_exists(\AppEntity\AppCategories::class)) :
            return [];
        endif;
        $limit = ($params && isset($params['limit']) && (int) $params['limit'] >= 0) ? (int) $params['limit'] : 12;
        $contenttype = ($params && isset($params['contenttype']) && trim($params['contenttype'])) ? trim($params['contenttype']) : '';
        $parent = ($params && isset($params['parent']) && (int) $params['parent'] > 0) ? (int) $params['parent'] : 0;
        $noparent = ($params && isset($params['noparent']) && (int) $params['noparent']) ? 1 : 0;
        $qb = $this->em->getRepository(\AppEntity\AppCategories::class)
                ->createQueryBuilder('a');
        if ($noparent) :
            $qb->where('a.parent is null');
        elseif ($parent) :
            $qb->where("a.parent = $parent");
        endif;
        return $qb->where("a.status = 1 and a.contenttype='{$contenttype}'")
                        ->andWhere('a.mainSystem = :mainsystem')->setParameter('mainsystem', CURRENT_SYSTEM)
                        ->setMaxResults($limit)
                        ->select('a.name,a.id,a.alias,a.level')
                        ->orderBy('a.lft', 'asc')
                        ->addOrderBy('a.id', 'desc')
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-categories-list-' . $contenttype . '_category_list_all')
                        ->getResult();
    }

    public function getPageOptions() {

        if (!class_exists(\AppEntity\AppPages::class)) :
            return [];
        endif;
        return $this->em->getRepository(\AppEntity\AppPages::class)
                        ->createQueryBuilder('a')
                        ->where('a.status = 1')
                        //                        ->setMaxResults(20)
                        ->select('a.name,a.id,a.alias,a.isHome')
                        ->orderBy('a.name', 'asc')
                        ->addOrderBy('a.id', 'desc')
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-pages-list-page_list_all')
                        ->getResult();
    }
    public function getDegreeOptions() {

        if (!class_exists(\AppEntity\AppDegree::class)) :
            return [];
        endif;
        return $this->em->getRepository(\AppEntity\AppDegree::class)
                        ->createQueryBuilder('a')
                        ->where('a.status = 1')
                        //                        ->setMaxResults(20)
                        ->select('a.name,a.id')
                        ->orderBy('a.name', 'asc')
                        ->addOrderBy('a.id', 'desc')
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-degree_list_all')
                        ->getResult();
    }
    public function getSkillOptions() {

        if (!class_exists(\AppEntity\AppSkill::class)) :
            return [];
        endif;
        return $this->em->getRepository(\AppEntity\AppSkill::class)
                        ->createQueryBuilder('a')
//                        ->where('a.status = 1')
                        //                        ->setMaxResults(20)
                        ->select('a.name,a.id')
                        ->orderBy('a.name', 'asc')
                        ->addOrderBy('a.id', 'desc')
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-degree_skill_all')
                        ->getResult();
    }

    public function getPageOptionsByPageType() {

        if (!class_exists('AppEntity\AppPages')) :
            return [];
        endif;
//        $cacheSettings = $this->config['settings']['caches'];
        return $this->em->getRepository('AppEntity\AppPages')
                        ->createQueryBuilder('a', 'a.id')
                        ->where('a.status = 1')
                        ->andWhere('a.pageType > 0')
                        ->select('a.name,a.id,a.alias,a.isHome,a.pageType')
                        ->orderBy('a.name', 'asc')
                        ->addOrderBy('a.id', 'desc')
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-pages-list-page_list_all')
                        ->getResult();
    }

    function buildCategoryTree(array $categories, $parentId = null) {
        $branch = [];
        foreach ($categories as $category):
            if ($category['parent'] == $parentId):
                $children = $this->buildCategoryTree($categories, $category['id']);
                if ($children):
                    $category['children'] = $children;
                endif;
                $branch[] = $category;
            endif;
        endforeach;
        return $branch;
    }

    public function getCategoryTree() {
        // cần mysql 8 trở lên
//        $cacheSettings = $this->config['settings']['caches'];
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('name', 'name');
        $rsm->addScalarResult('parent', 'parent');
        $rsm->addScalarResult('depth', 'level');
        $rsm->addScalarResult('alias', 'alias');
        $sql = 'WITH RECURSIVE category_tree AS ( SELECT u.id, u.name,u.alias, u.parent, 0 AS depth FROM app_product_categories u WHERE u.parent IS NULL UNION ALL SELECT c.id, c.name,c.alias, c.parent, ct.depth + 1 FROM app_product_categories c INNER JOIN category_tree ct ON c.parent = ct.id) SELECT * FROM category_tree ORDER BY depth, parent';

        $result = $this->em->createNativeQuery($sql, $rsm)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-product-categories-tree')
                ->getResult();
        return $this->buildCategoryTree($result);
    }

    public function getTreeProductCategoriesOptions() {
        if (!$this->em) :
            return ['' => 'Please select'];
        endif;
//        $cacheSettings = $this->config['settings']['caches'];
        $result = $this->getTreeProductCategories(null, -1, []);
        return $result;
    }

    public function getTreeProductCategories($parent = null, $level = -1, $data = []) {
        $qb = $this->em->getRepository('AppEntity\AppProductCategories')->createQueryBuilder('a', 'a.id');
        $pid = ($parent) ? $parent->getId() : 0;
        if ($parent) :
            $qb->where('a.parent = ' . $parent->getId());
        else :
            $qb->where('a.parent is null');
        endif;
        $qb->andWhere('a.isProductPage=1')
//                ->andWhere('a.mainSystem = :mainsystem')->setParameter('mainsystem', CURRENT_SYSTEM)
        ;

        $cates = $qb->orderBy('a.parent', 'asc')->addOrderBy('a.ordering', 'asc')->addOrderBy('a.id', 'desc')
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-product-categories-tree-parent-' . $pid)
                ->getResult();
        if ($cates) :
            $level++;
            foreach ($cates as $cate) :
                $cid = $cate->getId();
                $cname = $cate->getName();
                $data[$cid] = [
                    'id' => $cid,
                    'name' => $cname,
                    'level' => $level,
                    'label' => str_repeat('|-', $level) . $cname,
                    'alias' => $cate->getAlias()
                ];
                $data = $this->getTreeProductCategories($cate, $level, $data);
            endforeach;
            return $data;
        else :
            return $data;
        endif;
    }

    public function getProductCategories($params = []) {
        $limit = ($params && isset($params['limit']) && (int) $params['limit'] >= 0) ? (int) $params['limit'] : 12;
        $parent = ($params && isset($params['parent']) && (int) $params['parent'] > 0) ? (int) $params['parent'] : 0;
        $noparent = ($params && isset($params['noparent']) && (int) $params['noparent']) ? 1 : 0;
        $ids = ($params && isset($params['ids'])) ? (array) $params['ids'] : null;

//        $mediapath = $this->config['settings']['media']['mediapath'];
        $qb = $this->em->getRepository('AppEntity\AppProductCategories')->createQueryBuilder('a');
        if ($noparent) :
            $qb->where('a.parent is null');
        elseif ($parent) :
            $qb->where("a.parent = $parent");
        endif;
        if ($ids !== null) :
            $cacheKey = implode('-', $ids);
            if (!$ids) :
                $ids = [0];
            endif;
            $qb->andWhere('a.id IN (:ids)')->setParameter('ids', $ids);
        else:
            $cacheKey = 'all';
        endif;
        return $qb->setMaxResults($limit)
                        ->orderBy('a.ordering', 'desc')
                        ->addOrderBy('a.id', 'desc')
                        ->select('a.id,a.name,a.alias')
//                        ->leftJoin('a.image', 'm')
//                        ->addSelect("(CASE when a.image is not null then concat('{$mediapath}/',m.folder,'/',m.filename) ELSE '' END) image")
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-product-categories-list-' . $cacheKey)
                        ->getResult();
    }

    public function getProductBrands($limit = 12) {
        return $this->em->getRepository('AppEntity\AppBrands')
                        ->createQueryBuilder('a')
                        ->setMaxResults($limit)
                        //                        ->orderBy('a.ordering', 'desc')
                        ->addOrderBy('a.id', 'desc')
                        //                        ->where('a.parent is null')
                        ->select('a.id,a.name,a.alias,a.image')
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-brands-list-limit-' . $limit)
                        ->getResult();
    }

    public function getBranches() {
        $branches = $this->em->getRepository('AppEntity\AppBranch')
                ->createQueryBuilder('a')
                ->orderBy('a.region', 'asc')
                ->addOrderBy('a.ordering', 'asc')
                ->addOrderBy('a.id', 'asc')
                ->where('a.status =1')
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-branch-list-all')
                ->getResult();
        if ($branches) :
            $regions = $this->config['settings']['regions'];
            $_regions = [];
            foreach ($branches as $branch) :
                $_region = (int) $branch->getRegion();
                if (!isset($_regions[$_region])) :
                    $_regions[$_region] = [
                        'name' => $regions[$_region],
                        'branches' => []
                    ];
                endif;
                $_regions[$_region]['branches'][] = [
                    'name' => $branch->getName(),
                    'email' => $branch->getEmail(),
                    'address' => $branch->getAddress(),
                    'gmapLink' => $branch->getGmapLink(),
                    'phone' => $branch->getPhone(),
                ];
            endforeach;
            return $_regions;
        else :
            return [];
        endif;
    }

    public function getPostAuthor() {
//        $cacheSettings = $this->config['settings']['caches'];
        return $this->em->getRepository('AppEntity\AppAdminUsers')
                        ->createQueryBuilder('a')
                        ->setMaxResults(100)
                        ->addOrderBy('a.id', 'desc')
                        ->select('a.id,a.name')
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-admin-users-list-limit-100')
                        ->getResult();
    }

    public function getAttributeSetOptions($params = []) {
//        $mediapath = '/' . $this->config['settings']['media']['mediapath'];
//        $cacheSettings = $this->config['settings']['caches'];
        $qb = $this->em->getRepository('AppEntity\AppProductAttributeset')
                ->createQueryBuilder('a');
        $qb->select('a.id,a.name');
        if ($params && isset($params['withimage'])) :
            $qb->addSelect("a.image");
        endif;
        return $qb->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-product-attributeset-list-all')
                        ->getResult();
    }

    public function getProductOptions($params = []) {
//        $mediapath = '/' . $this->config['settings']['media']['mediapath'];
//        $cacheSettings = $this->config['settings']['caches'];
        $qb = $this->em->getRepository('AppEntity\AppProducts')
                ->createQueryBuilder('a');

        $qb->where('a.status=1')->select('a.name,a.id,a.code,a.featuredImage,a.images')->groupBy('a.id');
        if ($params && isset($params['morefields']) && $params['morefields']) :
            if ($params['morefields']) :
                foreach ($params['morefields'] as $field) :
                    $qb->addSelect("a.{$field}");
                endforeach;
            endif;

        endif;

        return $qb->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-products-list-options')
                        ->getResult();
    }

    public function getProductByIds($ids) {
        if (!$ids) :
            return [];
        endif;
        $qb = $this->em->getRepository('AppEntity\AppProducts')
                ->createQueryBuilder('a');
//        $cacheSettings = $this->config['settings']['caches'];
        return $qb->where('a.id IN (:ids) and a.status=1')->setParameter('ids', $ids)
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-products-list-ids-' . implode('-', $ids))
                        ->getResult();
    }

    public function getProductAttributes(\AppEntity\AppProducts $product) {
        $attributesetdata = $product->getAttributesetdata();
        $attributesetIds = [];
        if ($attributesetdata) :
            $attributesetIds = array_keys(json_decode($attributesetdata, true));
        endif;
        if (!$attributesetIds) :
            return null;
        endif;
        $pid = $product->getId();
        $code = '_' . implode('_', $attributesetIds) . '_'; // để đầu và cuối đều có dấu _
//        $cacheSettings = $this->config['settings']['caches'];
        return $this->em->getRepository('AppEntity\AppProductAttribute')
                        ->createQueryBuilder('a')
                        ->where('a.code = :code')->setParameter('code', $code)
                        ->andWhere('a.product=' . $product->getId())
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-product-attribute-list-product-' . $pid . '-code-' . $code)
                        ->getResult();
    }

    public function getProductAttributesCustom(\AppEntity\AppProducts $product) {

        $attributesetdata = $product->getAttributesetdata();
        $pid = $product->getId();
        $settings = $this->getSettings('all');
        $attributesetdatas = [];
        if ($attributesetdata) :
            $attributesetdatas = json_decode($attributesetdata, true);
        endif;
        $attributesetIds = ($attributesetdatas) ? array_keys($attributesetdatas) : [];
        $link = '/' . $settings['media']['mediapath'] . '/';
        $code = ($attributesetIds) ? '_' . implode('_', $attributesetIds) . '_' : ''; // để đầu và cuối đều có dấu _
//        $cacheSettings = $this->config['settings']['caches'];
        return $this->em->getRepository('AppEntity\AppProductAttribute')
                        ->createQueryBuilder('a')
                        ->where('a.code = :code')->setParameter('code', $code)
                        ->andWhere('a.product=' . $pid)
                        ->select('a.id,a.price,a.code,a.instock,a.attributeset,a.colorCode')
                        ->leftJoin('a.image', 'm')
                        ->addSelect("concat('$link',m.folder,'/',m.filename) image")
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-product-attribute-list-custom-product-' . $pid . '-code-' . $code)
                        ->getResult();
    }

    public function getProductAttributesets(\AppEntity\AppProducts $product) {
        $attributesetdata = $product->getAttributesetdata();
        $attributesetdatas = [];
        if ($attributesetdata) :
            $attributesetdatas = json_decode($attributesetdata, true);
        endif;
        $attributesetIds = ($attributesetdatas) ? array_keys($attributesetdatas) : [];
//        $cacheSettings = $this->config['settings']['caches'];
        return $this->em->getRepository('AppEntity\AppProductAttributeset')
                        ->createQueryBuilder('a')
                        ->select('a.id,a.name')
                        ->where('a.id IN (:ids)')->setParameter('ids', $attributesetIds)
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-product-attributeset-list-' . implode('-', $attributesetIds))
                        ->getResult();
    }

    public function getAllShortCodes() {
        $shortCodes = [];
        $shortCodes['positions'] = $this->em->getRepository(\AppEntity\AppCategories::class)
                ->createQueryBuilder('a')
                ->where("a.status = 1 and a.contenttype='banner'")
                ->andWhere("a.mainSystem=:mainsystem")->setParameter('mainsystem', CURRENT_SYSTEM)
                ->select('a.id,a.name')
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-categories-list-shortcode')
                ->getResult();
        $shortCodes['blocks'] = $this->em->getRepository(\AppEntity\AppBlocks::class)
                ->createQueryBuilder('a')
                ->where('a.status = 1')
                ->andWhere("a.mainSystem=:mainsystem")->setParameter('mainsystem', CURRENT_SYSTEM)
                ->select('a.id,a.name')
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-blocks-list-shortcode')
                ->getResult();
        return $shortCodes;
    }

    public function getProductLabels() {
//        $cacheSettings = $this->config['settings']['caches'];
        $_labels = $this->em->getRepository('AppEntity\AppProductLabel')
                ->createQueryBuilder('a')
                ->select('a.id,a.name,a.position,a.customStyle')
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-product-label-list-all')
                ->getResult();
        $labels = [];
        $positions = $this->config['settings']['product-label-position'] ?? [];

        if ($_labels) :
            foreach ($_labels as $_label) :
                if (!isset($labels[$_label['position']])) :
                    $labels[$_label['position']] = [
                        'name' => $positions[$_label['position']],
                        'labels' => []
                    ];
                endif;
                $labels[$_label['position']]['labels'][] = $_label;
            endforeach;
        endif;
        return $labels;
    }

    public function getCustomFieldTreeData(\AppEntity\AppCustomFieldGroup $item = null) {
        if (!$item) :
            return null;
        endif;
        $datas = [];
//        $cacheSettings = $this->config['settings']['caches'];
        $gid = $item->getId();
        $customFields = $this->em->getRepository('AppEntity\AppCustomField')
                ->createQueryBuilder('a')
                ->where('a.fieldGroup = ' . $gid)
                ->andWhere('a.parent is null')
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-custom-field-list-group-' . $gid)
                ->getResult();
        if ($customFields) :
            foreach ($customFields as $customField) :
                $datas[] = $customField->getDatas();
            endforeach;
        endif;
        return $datas;
    }

    public function getCustomFieldTreeDataChild($parentId) {
        if (!$parentId) :
            return null;
        endif;
        $datas = [];
//        $cacheSettings = $this->config['settings']['caches'];
        $customFields = $this->em->getRepository('AppEntity\AppCustomField')
                ->createQueryBuilder('a')
                ->andWhere('a.parent =' . $parentId)
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-custom-field-list-parent-' . $parentId)
                ->getResult();
        if ($customFields) :
            foreach ($customFields as $customField) :
                $datas[] = $customField->getDatas();
            endforeach;
        endif;
        return $datas;
    }

    public function getCustomFieldGroups(\AppEntity\AppPages $item = null) {
        if (!$item) :
            return null;
        endif;
//        $cacheSettings = $this->config['settings']['caches'];
        $pageId = $item->getId();
        return $this->em->getRepository('AppEntity\AppCustomFieldGroup')
                        ->createQueryBuilder('a')
                        ->join('a.pages', 'p')
                        ->where('p.id = ' . $pageId)
                        ->orderBy('a.ordering', 'asc')
                        ->orderBy('a.id', 'desc')
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-custom-field-group-list-page-' . $pageId)
                        ->getResult();
    }

    public function getCustomFieldPages(\AppEntity\AppPages $item = null) {
        if (!$item) :
            return null;
        endif;
//        $cacheSettings = $this->config['settings']['caches'];
        $pageId = $item->getId();
        return $this->em->getRepository('AppEntity\AppCustomFieldPage')
                        ->createQueryBuilder('a')
                        ->select('a.id,identity(a.customField) customField,a.value,a.type,identity(a.parent) parent')
                        ->where('a.page = ' . $item->getId())
                        ->orderBy('a.id', 'asc')
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-custom-field-page-list-page-' . $pageId)
                        ->getResult();
    }

    public function getCitiesOptions() {
        if (!$this->em) :
            return [];
        endif;
//        $cacheSettings = $this->config['settings']['caches'];
        $qb = $this->em->getRepository('AppEntity\AppLocationCity')
                ->createQueryBuilder('a', 'a.id')->select('a.name,a.id')
                ->andWhere("a.status=1")
                ->orderBy('a.ordering', 'desc')
                ->addOrderBy('a.id', 'desc');
        $datas = $qb->getQuery()->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-location-city-list-options')
                ->getResult();
        $result = [];
        if ($datas) :
            $result = [];
            foreach ($datas as $data) :
                $result[] = ['value' => $data['id'], 'label' => $data['name']];
            endforeach;
        endif;

        return $result;
    }

    public function getDistrictsOptions() {
        if (!$this->em) :
            return ['' => 'Choose cities'];
        endif;
//        $cacheSettings = $this->config['settings']['caches'];
        $qb = $this->em->getRepository('AppEntity\AppLocationDistrict')
                ->createQueryBuilder('a', 'a.id')->select('a.name,a.id,identity(a.city) city')
                ->andWhere("a.status=1")
                ->orderBy('a.name', 'desc');
        $datas = $qb->getQuery()->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-location-district-list-options')
                ->getResult();
        $result = ['' => 'Choose cities'];
        if ($datas) :
            $result = [];
            foreach ($datas as $data) :
                $result[] = ['value' => $data['id'], 'label' => $data['name'], 'city' => $data['city']];
            endforeach;
        endif;

        return $result;
    }

    public function getDataSampleOptions() {
        if (!$this->em) :
            return [];
        endif;
//        $cacheSettings = $this->config['settings']['caches'];
        $qb = $this->em->getRepository('AppEntity\AppSample')
                ->createQueryBuilder('a', 'a.id')->select('a.name,a.specifications')
                ->addOrderBy('a.id', 'desc');
        $datas = $qb->getQuery()->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-sample-list-options')
                ->getResult();
        $result = [];
        if ($datas) :
            $result = [];
            foreach ($datas as $data) :
                $result[] = ['value' => $data['specifications'], 'label' => $data['name']];
            endforeach;
        endif;

        return $result;
    }

    public function getProductsByCates($categoryIds, $limit = 12) {
        $data = [];
//        $cacheSettings = $this->config['settings']['caches'];
        if ($categoryIds) :
            $cacheKey = implode('-', $categoryIds) . '-limit-' . $limit;
            $data['categories'] = $this->em->getRepository('AppEntity\AppProductCategories')->createQueryBuilder('a', 'a.id')
                    ->where('a.status=1 and a.id IN (:categories)')->setParameter('categories', $categoryIds)
                    ->select('a.id,a.name')->setMaxResults($limit)
                    ->orderBy('a.ordering', 'desc')
                    ->addOrderBy('a.id', 'desc')
                    ->getQuery()
                    ->useQueryCache(true)
                    ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-product-categories-list-' . $cacheKey)
                    ->getResult();
            $_categoryIds = ($data['categories']) ? array_keys($data['categories']) : [];
            $qb = $this->em->getRepository('AppEntity\AppProducts')->createQueryBuilder('a', 'a.id');
            $qb->where('a.status = 1')
                    ->orWhere('a.status = 5')
                    ->join('a.categories', 'c');
            $products = [];
            foreach ($_categoryIds as $cat) :
                $newQb = clone $qb;
                $newQb->andWhere('c.id = ' . $cat);
                $products[$cat] = $newQb->setMaxResults($limit)
                        ->orderBy('c.ordering', 'desc')
                        ->addOrderBy('a.ordering', 'desc')
                        ->addOrderBy('a.id', 'desc')
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-products-list-category-' . $cat . '-limit-' . $limit)
                        ->getResult();

            endforeach;
            $data['products'] = $products;
        else :
            return '';
        endif;

        return $data;
    }

    public function getWidgetByType($type = null) {
        $widget = null;
//        $cacheSettings = $this->config['settings']['caches'];
        if ($type) :
            $widget = $this->em->getRepository('AppEntity\AppWidget')->createQueryBuilder('a', 'a.id')
                    ->where('a.type = :type and a.status=1')->setParameter('type', $type)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->useQueryCache(true)
                    ->useResultCache($this->cacheEnable, $this->cacheTime, "app-widget-detail-type-{$type}")
                    ->getOneOrNullResult();
        endif;

        return $widget;
    }

    public function getMenus($name = '', $locale = '') {
//        $cacheSettings = $this->config['settings']['general'];
        if (!$name || !($menu = $this->em->getRepository(\AppEntity\AppMenus::class)
                ->createQueryBuilder('a')
                ->where("a.name='" . trim($name) . "'")
                ->andWhere("a.mainSystem=:mainsystem")->setParameter('mainsystem', CURRENT_SYSTEM)
                ->setMaxResults(1)->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, "app-menus-detail-menu_{$name}_{$locale}")
                ->getOneOrNullResult()
                )) :
            return [];
        else :
            try {
                $json = json_decode($menu->getMenucontent(), true);
                if ($json) :
                    return [
                        'class' => $menu->getAlias(),
                        'name' => $menu->name,
                        'menuitems' => $json
                    ];
                else :
                    return [];
                endif;
            } catch (\Exception $exception) {
                $logger = $this->container->build('Logger', [
                    'name' => __FUNCTION__
                ]);
                $logger->error(
                        sprintf(
                                "%s:%d %s (%d) [%s] \n %s \n----------------------------------------------------------------------\n", $exception->getFile(), $exception->getLine(), $exception->getMessage(), $exception->getCode(), get_class($exception), $exception->getTraceAsString()
                        )
                );
                return [];
            }

        endif;
    }

    public function getVehiclesByOwner($owner = null) {
        if (!$owner):
            return null;
        endif;
        $qb = $this->em->getRepository(\AppEntity\AppVehicles::class)->createQueryBuilder('a', 'a.id');
        return $qb->where('a.status=1')
                        ->andWhere('a.owner=' . $owner->id)
                        ->select('a.name,a.id')
                        ->getQuery()->getResult();
    }

    public function getDriversByOwner($owner = null) {
        if (!$owner):
            return null;
        endif;
        $qb = $this->em->getRepository(\AppEntity\AppCustomers::class)->createQueryBuilder('a', 'a.id');
        return $qb->where('a.state=1 and a.accountType=1')
                        ->join('a.company', 'c')
                        ->andWhere('c.owner=' . $owner->id)
                        ->select('a.name,a.id')
                        ->orderBy('a.name', 'asc')
                        ->getQuery()->getResult();
    }
    public function getAssignById($id =0) {
        if (!$id):
            return null;
        endif;
        $qb = $this->em->getRepository(\AppEntity\AppTourAssignmentVehicle::class)->createQueryBuilder('a', 'a.id');
        return $qb->where('a.tour='.$id)
                        ->getQuery()->getResult();
    }

    public function getPosts() {
        $currentQuery = $_REQUEST;
        $cate = trim($currentQuery['cate'] ?? '');
        $tag = trim($currentQuery['tag'] ?? '');
        $page = (int) ($currentQuery['page'] ?? 1);
        if ($page < 1):
            $page = 1;
        endif;
        $limit = (int) ($currentQuery['limit'] ?? 6);
        if ($limit < 0):
            $limit = 0;
        endif;
        $search = trim($currentQuery['search'] ?? '');
        $qb = $this->em->getRepository(\AppEntity\AppPosts::class)->createQueryBuilder('a', 'a.id');
        $now = new \DateTime();
        $qb->where('a.status=1')
                ->andWhere("(a.created <= :tocreateddate or a.created is null)")
                ->andWhere('a.mainSystem = :mainsystem')->setParameter('mainsystem', CURRENT_SYSTEM)
                ->setParameter('tocreateddate', $now->format('Y-m-d H:i:s'))->leftJoin('a.category', 'c'); // parameter thay đổi nên ko cache được
        $category = null;
        $offset = ($page - 1) * $limit;
        $cacheKey = '';
        $application = $this->container->get('Application');

        $routeMatch = $application->getMvcEvent()->getRouteMatch();
        $_rmParams = $routeMatch->getParams() ?: [];
        if (!($cate && $cate != 'c') && ($_rmParams['cate'] ?? '')):
            $cate = $_rmParams['cate'];
        endif;

        if ($cate && $cate != 'c'):
            $template = 'list';
            $cacheKey .= '-c-' . $cate;
            $category = $this->em->getRepository(\AppEntity\AppCategories::class)
                    ->createQueryBuilder('a')
//                    ->select('a.name,a.alias,a.id')
                    ->where("a.contenttype='post' and a.alias='$cate' and a.status=1")
                    ->andWhere('a.mainSystem = :mainsystem')->setParameter('mainsystem', CURRENT_SYSTEM)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->useQueryCache(true)
                    ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-categories-detail-alias-' . $cate)
                    ->getOneOrNullResult();
            $cid = ($category) ? $category->getId() : 0;

            if ($cid):
                $childIds = $this->em->getRepository(\AppEntity\AppCategories::class)
                        ->createQueryBuilder('a', 'a.id')
                        ->select('a.id')
                        ->where("a.contenttype='post' and a.parent=" . $cid . " and a.status=1")
                        ->orderBy('a.parent', 'desc')->addOrderBy('a.ordering', 'asc')
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-categories-list-parent-' . $cid)
                        ->getResult();
                if ($childIds):
                    $childIds = array_keys($childIds);
                endif;
                $childIds[] = $cid;
                $qb->andWhere('a.category in (' . implode(',', $childIds) . ')');
            elseif ($_tag = $this->em->getRepository(\AppEntity\AppTags::class)
                    ->createQueryBuilder('a')
                    ->select('a.name,a.alias,a.id')
                    ->where("a.alias='$cate'")
                    ->setMaxResults(1)
                    ->getQuery()
                    ->useQueryCache(true)
                    ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-tags-detail-alias-' . $cate)
                    ->getOneOrNullResult()):
                $tag = $cate;
            endif;
        endif;

        if ($tag) :
            $cacheKey .= '-t-' . $tag;
            $qb->leftJoin('a.tags', 't')
                    ->andWhere('t.alias=:tag')
                    ->setParameter('tag', $tag);
        endif;
        $qbtotal = clone $qb;
        $_cacheKey = md5($cacheKey);

        $total = (int) $qbtotal->select('count(a.id)')->setMaxResults(1)->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-posts-total' . $_cacheKey)
                        ->getSingleScalarResult();
        $posts = $qb->setMaxResults($limit)->setFirstResult($offset)->orderBy('a.created', 'desc')->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-posts-list' . $_cacheKey)
                ->getResult();

        // danh mục tin tuc
        $qbcate = $this->em->getRepository(\AppEntity\AppPosts::class)->createQueryBuilder('a', 'a.id');
        $qbcate->join('a.category', 'c')->groupBy('c.id');
        $qbcate->select('c.name,c.alias,c.id,count(a.id) counter');
        $qbcate->where("a.id is not null and c.contenttype='post' and a.status=1 and c.status=1")
                ->andWhere('a.mainSystem = :mainsystem')->setParameter('mainsystem', CURRENT_SYSTEM)
                ->andWhere("(a.created <= :tocreateddate or a.created is null)")->setParameter('tocreateddate', $now->format('Y-m-d H:i:s'));
        $categories = $qbcate->setMaxResults(10)
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-categories-top-post-10')
                ->getResult();
        // Xem nhieu
        $qbMostViews = $this->em->getRepository(\AppEntity\AppPosts::class)->createQueryBuilder('a', 'a.id');
        $qbMostViews->where('a.status = 1')
                ->andWhere('a.mainSystem = :mainsystem')->setParameter('mainsystem', CURRENT_SYSTEM)
//                ->andWhere('a.translateOf is null')
                ->leftJoin('a.category', 'c');

        $mostViews = $qbMostViews->setMaxResults(5)->orderBy('a.views', 'desc')
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-posts-list-most-views')
                ->getResult();

        return [
            'limit' => $limit,
            'page' => $page,
            'total' => $total,
            'search' => $search,
            'currentQuery' => $currentQuery,
            'category' => $category,
            'categories' => $categories,
            'posts' => array_values($posts),
            'mostViews' => $mostViews,
            'template' => $template,
        ];
    }
}
