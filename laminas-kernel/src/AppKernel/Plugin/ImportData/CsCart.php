<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppKernel\Plugin\ImportData;

class CsCart {

    public $em;
    public $config;

    use \AppKernel\Traits\Utils;

    public function __construct($em, $config = []) {
        $this->em = $em;
        $this->config = $config;
    }

    public function importBlog() {
        if (is_file(PATH_ROOT . '/cscart_page_descriptions.php') && is_file(PATH_ROOT . '/cscart_page_descriptions.php')):
            $posts = require_once PATH_ROOT . '/cscart_page_descriptions.php';
            $_postDatas = require_once PATH_ROOT . '/cscart_pages.php';

            $postDatas = [];
            foreach ($_postDatas as $_postData):
                $date = new \DateTime();
                $date->setTimestamp((int) $_postData['timestamp']);
                $postDatas[$_postData['page_id']] = [
                    'created' => $date,
                    'status' => in_array($_postData['status'], ['A', 'H']) ? 1 : 0
                ];
            endforeach;
            foreach ($posts as $post):
                if ($post['lang_code'] == 'vi'):
                    if (!($blog = $this->em->getRepository('AppEntity\AppPosts')->findOneBy(['wpid' => $post['page_id']]))):
                        $blog = new \AppEntity\AppPosts();
                        $blog->setWpid($post['page_id']);
                    endif;

                    $name = ($post['page_title']) ?: $post['page'];
                    $blog->setAlias($this->createAlias($name));
                    $blog->setContent($post['description']);
                    $blog->setLang('vi');
                    $blog->setStatus($postDatas[$post['page_id']]['status']);
                    $blog->setCreated($postDatas[$post['page_id']]['created']);
                    $blog->setMetadesc($post['meta_description']);
                    $blog->setMetakey($post['meta_keywords']);
                    $blog->setName($name);
                    $this->em->persist($blog);
                endif;

            endforeach;
            $this->em->flush();
            echo __FILE__ . '<br/>';
            echo __FUNCTION__ . '<br/>';
            echo 'imported';
            exit;
        else:
            echo __FILE__ . '<br/>';
            echo __FUNCTION__ . '<br/>';
            echo 'File không tồn tại';
            exit;
        endif;
    }
}
