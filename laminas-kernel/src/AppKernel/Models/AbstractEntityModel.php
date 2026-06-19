<?php

namespace AppKernel\Models;

class AbstractEntityModel {

    public function __get(string $name = '') {
        if (!$name):
            return null;
        endif;
        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)):
            return $this->$method();
        endif;
        return null;
    }

    public function __set(string $name = '', $data = null) {
        if (!$name):
            return $this;
        endif;
        $method = 'set' . ucfirst($name);
        if (method_exists($this, $method)):
            $this->$method($data);
            return $this;
        endif;
        return $this;
    }

    public function getExcludedFields() {
        return [];
    }

    public function getImageFields() {
        return [];
    }

    public function getImagesFields() {
        return [];
    }

    public function getDateFields() {
        return [];
    }

    public function getNumberFields() {
        return [];
    }

    public function getJsonFields() {
        return [];
    }

    public function getEntityFields() {
        return [];
    }

    public function getHyphenFields() { //dữ liệu được nối với nhau băng dấu gạch nối ví dụ _1_2_3_
        return [];
    }

    public function getDatas($baseUrl = '') {
        $excludedFields = $this->getExcludedFields();
        $data = get_object_vars($this);

        unset($data['__initializer__']);
        unset($data['__cloner__']);
        unset($data['__isInitialized__']);
        if (!empty($excludedFields)):
            foreach ($excludedFields as $excludedField):
                if ($excludedField && is_string($excludedField) && array_key_exists($excludedField, $data)):
                    unset($data[$excludedField]);
                endif;
            endforeach;
        endif;

        $dateFields = $this->getDateFields();
        if (!empty($dateFields)):
            foreach ($dateFields as $dateField => $format):
                if ($dateField && is_string($dateField) && isset($data[$dateField])):
                    $data[$dateField] = ($data[$dateField]) ? $data[$dateField]->format($format) : null;
                endif;
            endforeach;
        endif;

        $numberFields = $this->getNumberFields();
        if (!empty($numberFields)):
            foreach ($numberFields as $numberField):
                if ($numberField && is_string($numberField) && isset($data[$numberField])):
                    $data[$numberField] = ($data[$numberField] !== null) ? (float) $data[$numberField] : null;
                endif;
            endforeach;
        endif;
        $imageFields = $this->getImageFields();
        if (!empty($imageFields) && $baseUrl):
            foreach ($imageFields as $imageField):
                if ($data[$imageField] ?? ''):
                    if (str_starts_with($data[$imageField], 'http')):
                        $data[$imageField] = $data[$imageField];
                    else:
                        $data[$imageField] = $baseUrl . '/' . $data[$imageField];
                    endif;
                else:
                    $data[$imageField] = null;
                endif;
            endforeach;
        endif;
        $imagesFields = $this->getImagesFields();
        if (!empty($imagesFields) && $baseUrl):
            $_images = [];
            foreach ($imagesFields as $imagesField):
                if ($data[$imagesField] ?? ''):
                    $_imagesFields = json_decode($data[$imagesField], true);
                    if ($_imagesFields):
                        foreach ($_imagesFields as $_imagesField):
                            if (str_starts_with($_imagesField, 'http')):
                                $_images[] = $_imagesField;
                            else:
                                $_images[] = $baseUrl . '/' . $_imagesField;
                            endif;
                        endforeach;
                    endif;

                else:
                    $_images = null;
                endif;
            endforeach;
            $data[$imagesField] = $_images;
        endif;
        $entityFields = $this->getEntityFields();
        if (!empty($entityFields)):
            foreach ($entityFields as $entityField => $_fields):

                if ($entityField && is_string($entityField) && isset($data[$entityField])):

                    if ($data[$entityField]):
                        if ($data[$entityField] instanceof \Doctrine\ORM\PersistentCollection):
                            $_entityDatas = [];
                            foreach ($data[$entityField] as $childEntity):
                                $_entityData = [];
                                foreach ($_fields as $_field):
                                    $_method = 'get' . ucfirst($_field);
                                    if (method_exists($childEntity, $_method)):
                                        if ($_field == 'datas'):
                                            $_entityData = $childEntity->$_method($baseUrl);
                                        else:
                                            $_entityData[$_field] = $childEntity->$_method();
                                        endif;

                                    endif;
                                endforeach;
                                $_entityDatas[] = $_entityData;
                            endforeach;
                        else:
                            $_entityDatas = [];
                            if (!empty($_fields)):
                                foreach ($_fields as $_field):
                                    $_method = 'get' . ucfirst($_field);
                                    if (method_exists($data[$entityField], $_method)):
                                        if ($_field == 'datas'):
                                            $_entityDatas = $data[$entityField]->$_method($baseUrl);
                                        else:
                                            $__imageFields  = $data[$entityField]->getImageFields();
                                            if($__imageFields && in_array($_field, $__imageFields)):
                                                $_entityDatas[$_field] = $baseUrl.DS.$data[$entityField]->$_method();
                                            else:
                                                $_entityDatas[$_field] = $data[$entityField]->$_method($baseUrl);
                                            endif;
                                            
                                        endif;

                                    endif;
                                endforeach;
                            endif;
                        endif;
                        $data[$entityField] = $_entityDatas;
                    else:
                        $data[$entityField] = null;
                    endif;
                else:
                    $data[$entityField] = null;
                endif;
            endforeach;
        endif;
        $jsonFields = $this->getJsonFields();
        if (!empty($jsonFields)):
            foreach ($jsonFields as $_field):
                if ($data[$_field]):
                    $data[$_field] = json_decode($data[$_field], true);
                else:
                    $data[$_field] = null;
                endif;
            endforeach;
        endif;
        $hyphenFields = $this->getHyphenFields();
        if (!empty($hyphenFields)):

            foreach ($hyphenFields as $_field):
                if ($data[$_field]):
//                    $_str = trim($data[$_field], ',');
                    $data[$_field] = explode(',', $data[$_field]);
                else:
                    $data[$_field] = null;
                endif;
            endforeach;
        endif;
        return $data;
    }
}
