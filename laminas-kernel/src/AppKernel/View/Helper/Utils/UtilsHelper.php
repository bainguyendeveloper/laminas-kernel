<?php

namespace AppKernel\View\Helper\Utils;

use Laminas\View\Helper\AbstractHelper;

class UtilsHelper extends AbstractHelper {

    private $em;
    private $container;
    private $config;
    private $translator;

    use \AppKernel\Traits\Utils;

    public function __construct($em, $translator, $config, $container) {
        $this->em = $em;
        $this->translator = $translator;
        $this->config = $config;
        $this->container = $container;
    }

    public function __invoke() {
        return $this;
//        $args = func_get_args();
//        $method = $args[0]??'';
//        if (!$method or !is_string($method)):
//            throw new \RuntimeException("Method can not empty");
//        endif;
//
//        if (!method_exists($this, $method)):
//            throw new \RuntimeException("Method '{$method}' does not exist in '". __NAMESPACE__."'");
//        endif;
//        unset($args[0]);
//
//        return call_user_func_array([$this, $method], $args);
    }

    /*
     * @return \Laminas\ServiceManager\ServiceLocatorInterface
     */

    public function getQuery() {
        $routeMatch = $this->container->get('Application')->getMvcEvent()->getRouteMatch();
        $result = array();
        if ($routeMatch) :
            $params = $routeMatch->getParams();
            if (isset($params['action'])) :
                $result['action'] = strtolower($params['action']);
                unset($params['action']);
            endif;
            if (isset($params['controller'])) :
                $controller = explode('\\', $params['controller']);
                $result['module'] = strtolower($controller[1]);
                $result['controller'] = strtolower(end($controller));
                unset($params['controller']);
            endif;
            if (!empty($params)) :
                foreach ($params as $key => $value) :
                    if ($value && is_string($value)) :
                        $result[strtolower($key)] = strtolower($value);
                    endif;
                endforeach;
            endif;
        endif;
        return $result;
    }

    public function getCurrentRouter($withparams = false) {

        $rm = $this->container->get('Application')->getMvcEvent()->getRouteMatch();

        if ($rm) :
            if ($withparams) :
                $params = $rm->getParams();
                unset($params['controller']);
                unset($params['module']);
                unset($params['homeroute']);
                unset($params['action']);
                $_params['params'] = $params;
                $_params['query'] = $this->container->get('Application')->getMvcEvent()->getRequest()->getQuery()->toArray();
                $_params['route'] = $rm->getMatchedRouteName();
                return $_params;
            else :
                return $rm->getMatchedRouteName();
            endif;

        else :
            return '';
        endif;
    }

    public function productCustomSort($products, $order) {
        if (!$order || !$products || ($order && count($order) == 0) || ($products && count($products) == 0)) :
            return $products;
        endif;

        $newProducts = [];
        foreach ($products as $key => $value) {
            $newProducts[] = $value;
        }

        $sortedProducts = [];
        foreach ($order as $index) {
            if (isset($newProducts[$index - 1])) {
                $sortedProducts[] = $newProducts[$index - 1];
            }
        }
        foreach ($newProducts as $product) {
            if (!in_array($product, $sortedProducts)) {
                $sortedProducts[] = $product;
            }
        }
        return $sortedProducts;
    }

    public function groupByAttributeSet($data = []) {
        $grouped = [];
        if ($data != null) {
            foreach ($data as $item) {
                $attributes = json_decode($item['attributeset'], true);
                if (isset($attributes['2'])) :
                    $index2 = $attributes['2'];
                    if (!isset($grouped[$index2])) {
                        $grouped[$index2] = [];
                    }
                    $grouped[$index2][] = $item;
                else :
                    break;
                endif;
            }
        }

        return $grouped;
    }

    public function sortObjectsByArray($objects, $orderArray) {
        if ($orderArray && is_array($orderArray)) :
            uksort($objects, function ($a, $b) use ($orderArray) {
                return array_search($a, array_keys($orderArray)) <=> array_search($b, array_keys($orderArray));
            });
        endif;

        return $objects;
    }

    public function filterPages($additionalPages = [], $pages = [], $pageType = null) {

        $page = null;
        if ($pageType && $pages && count($pages) > 0) :
            foreach ($pages as $key => $value) :
                if ($value['pageType'] == $pageType) :
                    $page = $value;
                    break;
                endif;
            endforeach;
        endif;

        if ($page && in_array($page['id'], $additionalPages)) :
            return $page;
        endif;
        return null;
    }

    public function getValues($attributes) {
        //dd($attributes);
        $_value = [];
        foreach ($attributes as $key => $value) {
            $_value[] = $value;
        }
        return $_value;
    }

    public function filterByAttributeSet($t1, $t2) {
        // Thêm trường attributesets cho t2
        // function nextItem($itemValue, $array) {
        //     $found = false;
        //     foreach ($array as $key => $value) {
        //         if ($found) {
        //             return $value;
        //         }
        //         if ($value === $itemValue) {
        //             $found = true;
        //         }
        //     }
        //     return; // Item không được tìm thấy
        // }
        $attArr = [];
        $images = [];
        foreach ($t2 as $_key => $item) {
            $attributesets = [];
            foreach ($t1 as $product) {
                $attributes = json_decode($product['attributeset'], true);
                foreach ($attributes as $key => $value) {
                    if ($key === $item['id']) {
                        $attributesets[] = ["value" => $value, 'image' => $product['image'], 'colorCode' => ($product['colorCode'] ?? ''), 'id' => $product['id']];
                        $attArr[$value][] = $this->getValues($attributes);
                        if ($product['image']) :
                            $images[$value] = $product['image'];
                        endif;
                        break;
                    }
                }
            }
            $t2[$_key]['attributesets'] = $attributesets;
        }
        $result = [];
        foreach ($t2 as $item) {
            $attributesets = [];
            $seenValues = [];

            foreach ($item['attributesets'] as $attributesetValue) {
                if (!in_array($attributesetValue['value'], $seenValues)) {
                    $attributesetValue['parentOf'] = isset($attArr[$attributesetValue['value']]) ? $attArr[$attributesetValue['value']] : null;
                    $attributesetValue['image'] = isset($images[$attributesetValue['value']]) ? $images[$attributesetValue['value']] : null;
                    $attributesets[] = $attributesetValue;
                    $seenValues[] = $attributesetValue['value']; // Thêm giá trị vào mảng phụ
                }
            }

            if (!isset($item['attributesets'])) {
                $item['attributesets'] = [];
            }

            $itemData = [
                'id' => $item['id'],
                'name' => $item['name'],
                'attributesets' => $attributesets
            ];
            $result[] = $itemData;
        }

        // dd($result);
        return $result;
    }

    public function wordLimit($args = []) {
        $str = trim($args['str'] ?? '');
        $limit = (int) ($args['limit'] ?? 100);
        $end_char = trim($args['end_char'] ?? '');
        $strip_tags = (bool) ($args['strip_tags'] ?? true);
//        $_str, $limit = 100, $strip_tags = true, $end_char = ' &#8230;'
        // remove special characters
//        $str = self::cleanText($_str);
        if (trim($str) == '') :
            return $str;
        endif;

        if ($strip_tags) :
            $str = trim(preg_replace('#<[^>]+>#', ' ', $str));
        endif;
        $words = explode(' ', $str);
        $_words = array_filter($words);
        $string = '';
        if (count($_words) > $limit):
            $i = 0;
            foreach ($words as $word) :
                if ($i < $limit):
                    $string .= $word . ' ';
                    $i++;
                else:
                    break;
                endif;
            endforeach;
            $string .= $end_char;
        else:
            $string = $str;
        endif;

        return rtrim($string);
    }

    function customSortArray($array1, $array2) {
        // Lấy các khóa của mảng thứ nhất
        $keys = array_keys($array1);

        // Sắp xếp các khóa dựa trên thứ tự của mảng thứ hai
        usort($keys, function ($a, $b) use ($array2) {
            return array_search($a, $array2) <=> array_search($b, $array2);
        });

        // Tạo mảng kết quả mới dựa trên thứ tự các khóa đã sắp xếp
        $sortedArray = [];
        foreach ($keys as $key) {
            if (isset($array1[$key])) {
                $sortedArray[$key] = $array1[$key];
            }
        }

        return $sortedArray;
    }

    function getImageQrCodeBank($bankCode, $accountNumber, $accountName, $amount, $note = '') {
        $params = http_build_query([
            'amount' => $amount,
            'addInfo' => $note,
            'accountName' => $accountName
        ]);
        $imgSrc = "https://img.vietqr.io/image/{$bankCode}-{$accountNumber}-qr_only.png?{$params}";
        return $imgSrc;
    }

    function maskString(string $str, $firstLength = 1, $lastLength = 1): string {
        $_str = trim($str);
        $length = mb_strlen($_str);
        $_minLength = $firstLength + $lastLength;
        if ($length <= $_minLength):
            // Trường hợp tên quá ngắn, không đủ để ẩn
            return $_str;
        endif;

        $firstChar = mb_substr($_str, 0, $firstLength);
        $lastChar = mb_substr($_str, -1 * $lastLength);
        $masked = str_repeat('*', $length - $_minLength);

        return $firstChar . $masked . $lastChar;
    }
}
