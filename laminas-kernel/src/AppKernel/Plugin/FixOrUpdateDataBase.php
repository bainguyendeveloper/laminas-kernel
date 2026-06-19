<?php

namespace AppKernel\Plugin;

class FixOrUpdateDataBase {

    public $em;
    public $config;

    use \AppKernel\Traits\Utils;

    public function __construct($em, $config = []) {
        $this->em = $em;
        $this->config = $config;
    }

    public function addExcerptToPost() {

        $posts = $this->em->getRepository('AppEntity\AppPosts')->createQueryBuilder('a')
//                        ->where("a.excerpt is null or a.excerpt=''")
//                ->where('a.id=361')
                        ->getQuery()->getResult();
        $isFixHtml = isset($_REQUEST['fixhtml']);
        $isForce = isset($_REQUEST['force']);
        foreach ($posts as $post):

            if (!$isFixHtml):
                $content = $post->getContent();
                $str = $this->wordLimit($this->normalize(strip_tags($content)), 75);
                $_string = str_replace(["\r\n", "\r"], "\n", $str);
                $string = preg_replace("/\n{2,}/", "\n\n", $_string);
                if ($isForce || !$post->getExcerpt()):
                    $post->setExcerpt($string);
                endif;
                if ($isForce || !$post->getMetadesc()):
                    $post->setMetadesc($string);
                endif;
                if ($isForce || !$post->getMetakey()):
                    $topKeywords = $this->getTopkeywords($content);
                    $_tags = ($topKeywords) ? array_keys($topKeywords) : [];
                    if (($tags = $post->getTags()) && ($tags->count())):
                        foreach ($tags as $tag):
                            $_tags[] = $tag->getName();
                        endforeach;
                    endif;
                    $post->setMetakey(implode(', ', $_tags));
                endif;
            else:
                $post->setContent(html_entity_decode($post->getContent()));
                $post->setExcerpt(html_entity_decode($post->getExcerpt()));
                $post->setMetadesc(html_entity_decode($post->getMetadesc()));
            endif;

            $this->em->persist($post);
        endforeach;
        $this->em->flush();
        echo __FILE__ . '<br/>';
        echo __FUNCTION__ . '<br/>';
        echo count($posts);
        exit;
    }

    public function fixExcerptProduct() {
        $products = $this->em->getRepository('AppEntity\AppProducts')->findAll();
        foreach ($products as $product):

            $content = $product->getExcerpt();
            if (!$content):
                $content = $product->getContent();
            endif;
            $str = $this->wordLimit($this->normalize(strip_tags($content)), 75);
            $_string = str_replace(["\r\n", "\r"], "\n", $str);
            $string = preg_replace("/\n{2,}/", "\n\n", $_string);
            $topKeywords = $this->getTopkeywords($content);
            $_tags = ($topKeywords) ? array_keys($topKeywords) : [];
            $product->setMetakey(implode(', ', $_tags));
            $product->setMetadesc($string);
            $product->setExcerpt($string);
            $this->em->persist($product);
        endforeach;
        $this->em->flush();
        echo __FILE__ . '<br/>';
        echo __FUNCTION__ . '<br/>';
        echo 'updated';
        exit;
    }

    public function fixBrowserData($page = 0) {
        if (!$page):
            $page = (int) ($_REQUEST['page'] ?? 1);
        endif;
        $originalLimit = (string)ini_get('max_execution_time');
        ini_set('max_execution_time', '300'); // tạm thời tăng lên



        $limit = 10000;
        $offset = ($page - 1) * $limit;
        $stts = $this->em->getRepository('AppEntity\AppVisitorStatistic')->createQueryBuilder('a')
                        ->where("SUBSTRING(a.browser, 1, 4)='Zalo' and a.userAgent NOT LIKE '%zalo%'")
                        ->setMaxResults($limit)
                        ->setFirstResult($offset)
                        ->getQuery()->getResult();

        if ($stts):
            foreach ($stts as $stt):
                $browser = $this->getBrowser($stt->userAgent);
                $stt->browser = $browser['name'];
                $this->em->persist($stt);
            endforeach;
            $this->em->flush();
        endif;
        $numberResult = count($stts);
        if ($numberResult == $limit):
            $page++;
            $this->fixBrowserData($page);
        else:
            // Khôi phục lại thời gian ban đầu
            ini_set('max_execution_time', $originalLimit);
            echo '<pre>';
            echo __FILE__ . '<br/>';
            echo __FUNCTION__ . '<br/>';
            print_r($offset + $numberResult);
            echo '</pre>';
            exit;
        endif;
    }
}
