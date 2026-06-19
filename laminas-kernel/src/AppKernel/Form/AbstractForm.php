<?php

namespace AppKernel\Form;

use Laminas\Form\Form;
use Laminas\Captcha;
use Laminas\Form\Element;

class AbstractForm extends Form {

    use \AppKernel\Traits\Utils;

    public $em;
    public $item;
    public $config;
    public $formName;
    public $cateid = 0;

    public function __construct(\Doctrine\ORM\EntityManager $em = null, $item = null, $config = []) {
        $formNames = explode('\\', get_called_class());
        $formName = mb_strtolower(end($formNames));
        $this->formName = $formName;
        $this->em = $em;
        $this->item = $item;
        $this->config = $config;
        parent::__construct($formName);
        $this->setUseInputFilterDefaults(false);
    }

    public function addElement($option = []) {
        $captcha = null;
        if (extension_loaded('gd')):


            $captcha = new Captcha\Image();
            $captcha->setFont(PATH_ROOT . '/assets/fonts/Philosopher-Regular.ttf');
            $captcha->setImgDir(PATH_ROOT . '/filemanager/userfiles/captcha');

            $captcha->setImgUrl('/filemanager/userfiles/captcha');
            $captcha->setFontSize(25);
            $captcha->setWordlen(6);
            $captcha->setHeight(80);
            $captcha->setWidth(180);
            $captcha->setDotNoiseLevel(1);
            $captcha->setLineNoiseLevel(1);
        endif;
        $defaultOptions = [
            'hidden' => [
                'name' => '',
                'type' => 'hidden',
            ],
            'textarea' => [
                'name' => '',
                'type' => Element\Textarea::class,
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'class' => 'form-control',
                    'placeholder' => 'Untitled',
                    'data-class' => 'col-12',
                    'rows' => 3
                ]
            ],
            'text' => [
                'name' => '',
                'type' => Element\Text::class,
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'class' => 'form-control',
                    'placeholder' => 'Untitled',
                    'data-class' => 'col-12',
                ]
            ],
            'password' => [
                'name' => '',
                'type' => Element\Password::class,
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'class' => 'form-control',
                    'placeholder' => 'Untitled',
                    'data-class' => 'col-12',
                ]
            ],
            'number' => [
                'name' => '',
                'type' => Element\Number::class,
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'class' => 'form-control',
                    'placeholder' => 'Untitled',
                    'data-class' => 'col-12',
                ]
            ],
            'search' => [
                'name' => '',
                'type' => Element\Search::class, // đây là kiểu button submit image
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'class' => 'form-control',
                    'placeholder' => 'Untitled',
                    'data-class' => 'col-12',
                ]
            ],
            'email' => [
                'name' => '',
                'type' => Element\Email::class,
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'class' => 'form-control',
                    'placeholder' => 'Untitled',
                    'data-class' => 'col-12',
                ]
            ],
            'tel' => [
                'name' => '',
                'type' => Element\Tel::class,
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'class' => 'form-control',
                    'placeholder' => 'Untitled',
                    'data-class' => 'col-12'
                ]
            ],
            'url' => [
                'name' => '',
                'type' => Element\Url::class,
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'class' => 'form-control',
                    'placeholder' => 'Untitled',
                    'data-class' => 'col-12'
                ]
            ],
            'color' => [
                'name' => '',
                'type' => Element\Color::class,
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'class' => 'form-control',
                    'placeholder' => 'Untitled',
                    'data-class' => 'col-12',
                ]
            ],
            'image' => [
                'name' => '',
                'type' => Element\Image::class, // đây là kiểu button submit image
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'class' => 'form-control',
                    'placeholder' => 'Untitled',
                    'data-class' => 'col-12',
                    'src' => '/filemanager/userfiles/logo/logo.png'
                ]
            ],
            'range' => [
                'name' => '',
                'type' => Element\Range::class, // đây là kiểu button submit image
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'class' => 'form-control',
                    'placeholder' => 'Untitled',
                    'data-class' => 'col-12',
                    'min' => 0,
                    'max' => 100,
                    'step' => 1
                ]
            ],
            'checkbox' => [
                'name' => '',
                'type' => Element\Checkbox::class,
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'data-class' => 'col-12'
                ]
            ],
            'checkboxes' => [
                'name' => '',
                'type' => Element\MultiCheckbox::class,
                'options' => ['label' => 'Untitled', 'value_options' => []],
                'attributes' => [
                    'data-class' => 'col-12'
                ]
            ],
            'radio' => [
                'name' => '',
                'type' => Element\Radio::class,
                'options' => ['label' => 'Untitled', 'value_options' => []],
                'attributes' => [
                    'data-class' => 'col-12'
                ]
            ],
            'date' => [
                'name' => '',
                'type' => Element\Date::class,
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'data-class' => 'col-12',
                    'class' => 'form-control'
                ]
            ],
            'datetime' => [
                'name' => '',
                'type' => Element\DateTimeLocal::class,
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'data-class' => 'col-12',
                    'class' => 'form-control'
                ]
            ],
            'time' => [
                'name' => '',
                'type' => Element\Time::class,
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'data-class' => 'col-12',
                    'class' => 'form-control'
                ]
            ],
            'month' => [
                'name' => '',
                'type' => Element\MonthSelect::class,
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'data-class' => 'col-12',
                    'class' => 'form-control'
                ]
            ],
            'monthselect' => [
                'name' => '',
                'type' => Element\MonthSelect::class,
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'data-class' => 'col-12',
                    'class' => 'form-control'
                ]
            ],
            'week' => [
                'name' => '',
                'type' => Element\Week::class,
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'data-class' => 'col-12',
                    'class' => 'form-control'
                ]
            ],
            'file' => [
                'name' => '',
                'type' => Element\File::class,
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'data-class' => 'col-12',
                ]
            ],
            'dateselect' => [
                'name' => '',
                'type' => Element\DateSelect::class,
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'data-class' => 'col-12',
                    'class' => 'form-control'
                ]
            ],
            'dateselect' => [
                'name' => '',
                'type' => Element\DateT::class,
                'options' => ['label' => 'Untitled'],
                'attributes' => [
                    'data-class' => 'col-12',
                    'class' => 'form-control'
                ]
            ],
            'select' => [
                'name' => '',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Untitled',
                    'value_options' => []
                ],
                'attributes' => [
                    'class' => 'form-control selectpicker',
                    'data-class' => 'col-xs-12 col-12',
                    'data-count-selected-text' => 'count',
                    'data-size' => 8,
                    'data-live-search' => 1,
                ]
            ],
            'csrf' => [
                'name' => $this->formName . '_csrf',
                'type' => Element\Csrf::class,
                'options' => [
                    'csrf_options' => [
                        'timeout' => 1800,
                    ]
                ],
            ],
            'captcha' => [
                'name' => '',
                'type' => Element\Captcha::class,
                'options' => ['label' => 'Captcha', 'captcha' => $captcha],
                'attributes' => [
                    'class' => 'form-control captcha',
                    'required' => true,
                    'data-ng-model' => 'captcha',
                    'data-class' => 'col-12 col-sm-6 captcha-form-group',
                    'placeholder' => 'Captcha'
                ]
            ],
            'submit' => [
                'name' => '',
                'type' => Element\Submit::class,
                'options' => ['label' => ''],
                'attributes' => [
                    'class' => 'btn btn-primary',
                    'value' => 'Submit',
                ]
            ],
            'button' => [
                'name' => '',
                'type' => Element\Button::class,
                'options' => ['label' => ''],
                'attributes' => [
                    'class' => 'btn btn-primary',
                    'value' => 'Button',
                ]
            ],
        ];
        
        $type = mb_strtolower(($option['type'] ?? 'text'));
        $id = ($option['attributes']['id'] ?? $type . $option['name']);
        $_option = $defaultOptions[$type] ?? $defaultOptions['text'];
        if(!$captcha):
            unset($_option['captcha']);
        endif;
        if (in_array($type, ['text', 'tel', 'email', 'search', 'number', 'url', 'textarea']) && !($option['attributes']['placeholder'] ?? false) && ($option['options']['label'] ?? false)):
            $option['attributes']['placeholder'] = $option['options']['label'];
        endif;
        $newoption = $this->deepMerge($_option, $option);
        if ($newoption['options']['value_options'] ?? false):
            $value_options = $newoption['options']['value_options'];
            if ((gettype($value_options) == 'string') && method_exists($this, $value_options)):
                $optionParams = $newoption['options']['params'] ?? [];
                $newoption['options']['value_options'] = $this->$value_options($optionParams);
            endif;
        endif;
        $newoption['attributes']['id'] = $id;
        $newoption['type'] = $_option['type'];

        $this->add($newoption);
        return $this;
    }

    public function bindForm($item, $fieldTypes = []) {
        if (!$item):
            return $this;
        endif;
        $manytoonefields = (array) ($fieldTypes['manytoonefields'] ?? []);
        $datefields = (array) ($fieldTypes['datefields'] ?? []);
        $datetimefields = (array) ($fieldTypes['datetimefields'] ?? []);
        $mediafields = (array) ($fieldTypes['mediafields'] ?? []);
        $manytomanyfields = (array) ($fieldTypes['manytomanyfields'] ?? []);
        $tagsFields = (array) ($fieldTypes['tagsFields'] ?? []);
        $floatfields = (array) ($fieldTypes['floatfields'] ?? []);
        $jsonfields = (array) ($fieldTypes['jsonfields'] ?? []);
        $elems = $this->getElements();

        foreach ($elems as $key => $elem) :
            $function = 'get' . ucfirst($key);
            if (method_exists($item, $function)) :
                $_fieldData = $item->$function();
                if (in_array($key, $datefields)) :
                    $data[$key] = ($_fieldData) ? $_fieldData->format('d-m-Y') : '';
                    $elem->setValue($data[$key]);
                    continue;
                elseif (in_array($key, $datetimefields)) :
                    $data[$key] = ($_fieldData) ? $_fieldData->format('d-m-Y H:i') : '';
                    $elem->setValue($data[$key]);
                    continue;
                elseif (in_array($key, $mediafields)) :
                    continue;
                elseif (in_array($key, $manytoonefields)) :
                    if ($_fieldData) :
                        $elem->setValue($_fieldData->getId());
                    endif;
                elseif (in_array($key, $tagsFields)):
                    $_val = [];
                    if ($_fieldData):
                        foreach ($_fieldData as $entity):
                            $_val[] = $entity->getName();
                        endforeach;
                    endif;
                    $elem->setValue(implode(',', $_val));
                elseif (in_array($key, $manytomanyfields)) :
                    $_val = ($_fieldData) ? $_fieldData->getKeys() : [];
                    $elem->setValue($_val);
                elseif (in_array($key, $floatfields)) :
                    $_val = (float) $_fieldData;
                    $elem->setValue($_val);
                elseif (in_array($key, $jsonfields)) :
                    $_val = trim($_fieldData || '');
                    $val = ($_val) ? json_decode($_val, true) : [];
                    $elem->setValue($val);
                else :
                    $elem->setValue($_fieldData);
                endif;

            endif;
        endforeach;
        return $this;
    }

    public function getCustomerOptions($accountType=null) {
        if (!$this->em):
            return ['' => '-- Vui lòng chọn --'];
        endif;
        $qb = $this->em->getRepository(\AppEntity\AppCustomers::class)
                ->createQueryBuilder('a')
//                ->setMaxResults(1000)
                ->addOrderBy('a.id', 'desc')
                ->select('a.id,a.name');
        $datas = $qb->getQuery()->getResult();
        $result = ['' => '-- Vui lòng chọn --'];
        if ($datas):
            foreach ($datas as $data):
                $result[$data['id']] = $data['name'];
            endforeach;
        endif;

        return $result;
    }

//    public function getCategoriesOptions() {
//        if (!$this->em):
//            return ['' => 'Choose category'];
//        endif;
//        $qb = $this->em->getRepository('AppEntity\AppCategories')
//                ->createQueryBuilder('a', 'a.id')->select('a.name,a.id')
//                ->andWhere("a.contenttype = 'post' and a.translateOf is null")->orderBy('a.ordering', 'asc')
//                ->addOrderBy('a.id', 'desc');
//        $datas = $qb->getQuery()->getResult();
//        $result = ['' => 'Choose category'];
//        if ($datas):
//            foreach ($datas as $data):
//                $result[$data['id']] = $data['name'];
//            endforeach;
//        endif;
//
//        return $result;
//    }

    public function getAuthorOptions() {
        if (!$this->em):
            return ['' => 'Choose a author'];
        endif;
        $qb = $this->em->getRepository('AppEntity\AppAdminUsers')
                ->createQueryBuilder('a')
//                ->setMaxResults(100)
                ->addOrderBy('a.id', 'desc')
                ->select('a.id,a.name');
        $datas = $qb->getQuery()->getResult();
        $result = ['' => 'Chọn tác giả'];
        if ($datas):
            foreach ($datas as $data):
                $result[$data['id']] = $data['name'];
            endforeach;
        endif;
        return $result;
    }
    public function getQuoteOptions() {
        if (!$this->em):
            return ['' => 'Chọn báo giá'];
        endif;
        $qb = $this->em->getRepository(\AppEntity\AppProjectQuote::class)
                ->createQueryBuilder('a')
//                ->setMaxResults(100)
                ->addOrderBy('a.id', 'desc')
                ->select('a.id,a.code');
        $datas = $qb->getQuery()->getResult();
        $result = ['' => 'Chọn báo giá'];
        if ($datas):
            foreach ($datas as $data):
                $result[$data['id']] = $data['code'];
            endforeach;
        endif;
        return $result;
    }

//    public function getThreadOptions() {
//        if (!$this->em):
//            return ['' => 'Choose a author'];
//        endif;
//        $qb = $this->em->getRepository(\AppEntity\AppThreads::class)
//                ->createQueryBuilder('a')
//                ->addOrderBy('a.id', 'desc')
//                ->select('a.id,a.name');
//        $datas = $qb->getQuery()->getResult();
//        $result = ['' => 'Chọn chủ đề'];
//        if ($datas):
//            foreach ($datas as $data):
//                $result[$data['id']] = $data['name'];
//            endforeach;
//        endif;
//        return $result;
//    }
    public function getQuestionsOptions() {
        if (!$this->em):
            return ['' => 'Choose a question'];
        endif;
        $qb = $this->em->getRepository(\AppEntity\AppQuestions::class)
                ->createQueryBuilder('a')
                ->orderBy('a.ordering', 'asc')
                ->select('a.id,a.ordering');
        $datas = $qb->getQuery()->getResult();
        $result = [];
        if ($datas):
            foreach ($datas as $data):
                $result[$data['id']] = "Câu {$data['ordering']}";
            endforeach;
        endif;
        return $result;
    }

    public function getLevelOptions() {
        $excludeId = 0;
        return $this->getCategoriesOptions('quiz-level', $excludeId, false);
    }

    public function getQuizCategoryOptions() {
        $excludeId = 0;
        return $this->getCategoriesOptions('quiz-cate', $excludeId, false);
    }

    public function getCreatorCategoriesOptions() {
        $excludeId = 0;
        return $this->getCategoriesOptions('creator', $excludeId, true);
    }
    public function getServicesOptions() {
        $excludeId = 0;
        return $this->getCategoriesOptions('service', $excludeId, false);
    }

    public function getSkillsOptions() {
        if (!$this->em) :
            return ['' => 'Chọn sản phẩm'];
        endif;
        $qb = $this->em->getRepository(\AppEntity\AppSkill::class)
                ->createQueryBuilder('a', 'a.id')->select('a.name,a.id')
                //                ->andWhere("a.translateOf is null")
//                ->orderBy('a.ordering', 'asc')
                ->addOrderBy('a.id', 'desc');
        $datas = $qb->getQuery()->getResult();

        $result = [];
        if ($datas) :
            foreach ($datas as $data) :
                $result[$data['id']] = $data['name'];
            endforeach;
        endif;
        return $result;
    }

    public function getPageOptions() {
        if (!$this->em):
            return ['' => 'Chọn trang'];
        endif;
        $qb = $this->em->getRepository(\AppEntity\AppPages::class)
                ->createQueryBuilder('a', 'a.id')->select('a.name,a.id')
                ->andWhere("a.status=1")
                ->andWhere("a.mainSystem=:mainSystem")->setParameter('mainSystem', CURRENT_SYSTEM)
                ->orderBy('a.name', 'asc')
                ->addOrderBy('a.id', 'desc');
        $datas = $qb->getQuery()->getResult();
        $result = [];
        if ($datas):
            foreach ($datas as $data):
                $result[$data['id']] = $data['name'];
            endforeach;
        endif;
        return $result;
    }

    public function getPaymentmethodOptions() {
        if (!$this->em):
            return ['' => 'Chọn phương thức'];
        endif;
        $qb = $this->em->getRepository(\AppEntity\AppPaymentMethod::class)
                ->createQueryBuilder('a', 'a.id')->select('a.name,a.id')
                ->andWhere("a.status=1")
                ->andWhere("a.mainSystem=:mainSystem")->setParameter('mainSystem', CURRENT_SYSTEM)
                ->orderBy('a.name', 'asc')
                ->addOrderBy('a.id', 'desc');
        $datas = $qb->getQuery()->getResult();
        $result = [];
        if ($datas):
            foreach ($datas as $data):
                $result[$data['id']] = $data['name'];
            endforeach;
        endif;
        return $result;
    }

    public function getCitiesOptions() {
        if (!$this->em):
            return ['' => 'Chọn tỉnh thành'];
        endif;
        $qb = $this->em->getRepository(\AppEntity\AppLocationCity::class)
                ->createQueryBuilder('a', 'a.id')->select('a.name,a.id')
                ->andWhere("a.status=1")
                ->addOrderBy('a.id', 'desc');
        $datas = $qb->getQuery()->getResult();
        $result = ['' => 'Chọn tỉnh thành'];
        if ($datas):
//            $result = [];
            foreach ($datas as $data):
                $result[] = ['value' => $data['id'], 'label' => $data['name']];
            endforeach;
        endif;

        return $result;
    }

    public function getPostCategoriesOptions($optionParams = []) {
        $excludeId = $optionParams['cateId'] ?? 0;
        return $this->getCategoriesOptions('post', $excludeId);
    }
    public function getYoutubeCategoriesOptions($optionParams = []) {
        $excludeId = $optionParams['cateId'] ?? 0;
        return $this->getCategoriesOptions('youtubefeed', $excludeId);
    }
    public function getRssCategoriesOptions($optionParams = []) {
        $excludeId = $optionParams['cateId'] ?? 0;
        return $this->getCategoriesOptions('feed', $excludeId);
    }

    public function getBannerPositions($optionParams = []) {
        $excludeId = $optionParams['cateId'] ?? 0;
        return $this->getCategoriesOptions('banner', $excludeId);
    }

    public function getCategoriesOptions($contenttype = 'post', $excludeId = 0, $withEmpty = true) {
        $emptyData = ($withEmpty) ? ['' => 'Please select'] : [];
        if (!$this->em):
            return $emptyData;
        endif;
        $result = $this->getCategory(null, -1, $emptyData, $contenttype, $excludeId, $withEmpty);

        return $result;
    }

    public function getCategory($parent = null, $level = -1, $data = [], $contenttype = 'post', $excludeId = 0) {
        $qb = $this->em->getRepository('AppEntity\AppCategories')->createQueryBuilder('a', 'a.id');

        if ($parent):
            $qb->where('a.parent = ' . $parent->getId());
        else:
//            $qb->where('a.parent is null');
        endif;
        $cates = $qb->andWhere("a.contenttype = '{$contenttype}'")
                        ->andWhere("a.id != " . $excludeId)
                        ->andWhere("a.mainSystem=:mainSystem")->setParameter('mainSystem', CURRENT_SYSTEM)
                        ->orderBy('a.parent', 'asc')->addOrderBy('a.ordering', 'asc')->addOrderBy('a.id', 'desc')
                        ->getQuery()->getResult();
        if ($cates):
            $level++;
            foreach ($cates as $cate):
                $data[$cate->getId()] = str_repeat('⟼', $level) . $cate->getName();
                $data = $this->getCategory($cate, $level, $data, $contenttype, $excludeId);
            endforeach;
            return $data;
        else:
            return $data;
        endif;
    }

    public function getproductCategoriesOptions($optionParams = []) {
        $excludeId = $optionParams['cateId'] ?? 0;
        if (!$this->em) :
            return ['' => 'No parent'];
        endif;
        $result = $this->getProductCategory(null, -1, ['' => 'No parent'], $excludeId);

        return $result;
    }

    public function getProductCategory($parent = null, $level = -1, $data = [], $excludeId = 0) {
        $qb = $this->em->getRepository('AppEntity\AppProductCategories')->createQueryBuilder('a', 'a.id');

        if ($parent) :
            $qb->where('a.parent = ' . $parent->getId());
        else :
            $qb->where('a.parent is null');
        endif;
//        $qb->andWhere('a.translateOf is null');
        $cates = $qb->andWhere("a.id != " . $excludeId)
                        ->andWhere("a.mainSystem=:mainSystem")->setParameter('mainSystem', CURRENT_SYSTEM)
                        ->orderBy('a.parent', 'asc')->addOrderBy('a.ordering', 'asc')->addOrderBy('a.id', 'desc')
                        ->getQuery()->getResult();
        if ($cates) :
            $level++;
            foreach ($cates as $cate) :
                $data[$cate->getId()] = str_repeat('⟼', $level) . $cate->getName();
                $data = $this->getProductCategory($cate, $level, $data);
            endforeach;
            return $data;
        else :
            return $data;
        endif;
    }

    public function getProjectOptions() {
        if (!$this->em) :
            return ['' => 'Choose project'];
        endif;
        $datas = $this->em->getRepository(\AppEntity\AppProject::class)->createQueryBuilder('a', 'a.id')
                        ->select('a.id value,a.name label')
                        ->getQuery()->getResult();
        $result = ['' => 'Choose project'];
        if ($datas) :
            foreach ($datas as $data) :
                $result[$data['value']] = $data['label'];
            endforeach;
        endif;

        return $result;
    }

    public function getWidgetOptions() {
        if (!$this->em) :
            return [];
        endif;
        $datas = $this->em->getRepository('AppEntity\AppWidget')->createQueryBuilder('a', 'a.id')
                        ->select('a.id value,a.name label')
                        ->getQuery()->getResult();
        $result = [];
        if ($datas) :
            foreach ($datas as $data) :
                $result[$data['value']] = $data['label'];
            endforeach;
        endif;

        return $result;
    }

    public function getRoleOptions() {
        $results = $this->em->getRepository('AppEntity\AppRoles')->createQueryBuilder('a INDEX BY a.id')
                        ->select('a.id as value,a.name as label')
                        ->addOrderBy('a.id', 'ASC')->getQuery()->getArrayResult();

        return $results;
    }

    public function getBankCodeOptions() {
        $results = $this->em->getRepository(\AppEntity\AppBank::class)->createQueryBuilder('a')
                        ->select('a.bankCode as value,a.name as label')
                        ->orderBy('a.name', 'ASC')->getQuery()->getArrayResult();
        return $results;
    }

    public function getBankOptions() {
        $results = $this->em->getRepository(\AppEntity\AppBank::class)->createQueryBuilder('a')
                        ->select("a.id as value,concat(a.name,' (',a.shortName,')') as label")
                        ->orderBy('a.name', 'ASC')->getQuery()->getArrayResult();
        return $results;
    }
    

    public function getPackagesOptions() {
        if (!$this->em):
            return ['' => '--- Không ---'];
        endif;
        $datas = $this->em->getRepository(\AppEntity\AppBusinessPackage::class)->createQueryBuilder('a', 'a.id')
                        ->select('a.id value,a.name label')
                        ->getQuery()->getResult();
        $result = [];
        if ($datas):
            foreach ($datas as $data):
                $result[$data['value']] = $data['label'];
            endforeach;
        endif;

        return $result;
    }
    public function getCompaniesOptions() {
        if (!$this->em):
            return ['' => '--- Không ---'];
        endif;
        $datas = $this->em->getRepository(\AppEntity\AppCompany::class)->createQueryBuilder('a', 'a.id')
                        ->select('a.id value,a.name label')
                        ->getQuery()->getResult();
        $result = ['' => '--- Không ---'];
        if ($datas):
            foreach ($datas as $data):
                $result[$data['value']] = $data['label'];
            endforeach;
        endif;

        return $result;
    }
    public function getCreatorOptions() {
        if (!$this->em):
            return ['' => '--- Không ---'];
        endif;
        $datas = $this->em->getRepository(\AppEntity\AppCreator::class)->createQueryBuilder('a', 'a.id')
                        ->select('u.id value,u.name label')
                        ->join('a.user','u')
                        ->getQuery()->getResult();
        $result = ['' => '--- Không ---'];
        if ($datas):
            foreach ($datas as $data):
                $result[$data['value']] = $data['label'];
            endforeach;
        endif;

        return $result;
    }
    public function getVehiclePowersOptions() {
        if (!$this->em):
            return ['' => '--- Không ---'];
        endif;
        $datas = $this->em->getRepository(\AppEntity\AppVehiclePower::class)->createQueryBuilder('a', 'a.id')
                        ->select('a.id value,a.name label')
                        ->getQuery()->getResult();
        $result = ['' => '--- Không ---'];
        if ($datas):
            foreach ($datas as $data):
                $result[$data['value']] = $data['label'];
            endforeach;
        endif;

        return $result;
    }
    public function getVehicleTypesOptions() {
        if (!$this->em):
            return ['' => '--- Không ---'];
        endif;
        $datas = $this->em->getRepository(\AppEntity\AppVehicleType::class)->createQueryBuilder('a', 'a.id')
                        ->select('a.id value,a.name label')
                        ->getQuery()->getResult();
        $result = ['' => '--- Không ---'];
        if ($datas):
            foreach ($datas as $data):
                $result[$data['value']] = $data['label'];
            endforeach;
        endif;

        return $result;
    }
}
