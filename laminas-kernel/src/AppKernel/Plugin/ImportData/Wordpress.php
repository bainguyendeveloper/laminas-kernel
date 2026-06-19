<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppKernel\Plugin\ImportData;

class Wordpress {

    public $em;
    public $config;
    public $wp_tags;
    public $wp_sub_tags;
    public $wxr_version;
    public $in_post;
    public $cdata;
    public $data;
    public $sub_data;
    public $in_tag;
    public $in_sub_tag;
    public $authors;
    public $posts;
    public $term;
    public $category;
    public $tag;
    public $base_url;

    use \AppKernel\Traits\Utils;

    public function __construct($em, $config = []) {
        $this->em = $em;
        $this->config = $config;
        $this->wp_tags = array(
            'wp:post_id', 'wp:post_date', 'wp:post_date_gmt', 'wp:comment_status', 'wp:ping_status', 'wp:attachment_url',
            'wp:status', 'wp:post_name', 'wp:post_parent', 'wp:menu_order', 'wp:post_type', 'wp:post_password',
            'wp:is_sticky', 'wp:term_id', 'wp:category_nicename', 'wp:category_parent', 'wp:cat_name', 'wp:category_description',
            'wp:tag_slug', 'wp:tag_name', 'wp:tag_description', 'wp:term_taxonomy', 'wp:term_parent',
            'wp:term_name', 'wp:term_description', 'wp:author_id', 'wp:author_login', 'wp:author_email', 'wp:author_display_name',
            'wp:author_first_name', 'wp:author_last_name',
        );
        $this->wp_sub_tags = array(
            'wp:comment_id', 'wp:comment_author', 'wp:comment_author_email', 'wp:comment_author_url',
            'wp:comment_author_IP', 'wp:comment_date', 'wp:comment_date_gmt', 'wp:comment_content',
            'wp:comment_approved', 'wp:comment_type', 'wp:comment_parent', 'wp:comment_user_id',
        );
    }
    public function downloadImageFromPost() {
        $posts = $this->em->getRepository('AppEntity\AppPosts')->findAll();
        $mediaPath = '/' . $this->config['settings']['media']['mediapath'];

        $counter = 0;
        foreach ($posts as $post):
            $patterns = [
                '/https:\/\/cuahangsamsung.vn\/cabit-content\/files/',
                '/https:\/\/cuahangsamsung.com\/cabit-content\/files/',
                '/http:\/\/cuahangsamsung.vn\/cabit-content\/files/',
                '/http:\/\/www.cuahangsamsung.vn\/cabit-content\/files/',
                '/https:\/\/cuahangsamsung.vn\/\/cabit-content\/files/',
                '/https:\/\/cuahangsamsung.com\/\/cabit-content\/files/',
                '/http:\/\/cuahangsamsung.vn\/\/cabit-content\/files/',
                '/http:\/\/www.cuahangsamsung.vn\/\/cabit-content\/files/',
            ];
            $replacements = [$mediaPath, $mediaPath, $mediaPath, $mediaPath, $mediaPath, $mediaPath, $mediaPath, $mediaPath];
            $content = $post->getContent();
            if ($content):
                $results = [];
                preg_match_all('/<img[^>]+>/i', $content, $results);
                if ($results && isset($results[0]) && $results[0]):

                    foreach ($results[0] as $result):
                        $srcs = [];
                        preg_match_all('/(src)=("[^"]*")/i', $result, $srcs);
                        if ($srcs):
                            foreach ($srcs[2] as $src):

                                $_src = trim($src, '"');
                                $parsed = parse_url($_src);
                                if (isset($parsed['scheme']) && isset($parsed['host'])):
                                    if (strpos($_src, 'cabit-content') !== false):
                                        $downloaded = $this->downloadMedia($_src);
                                        $_addslashes = '/' . str_replace("/", "\/", $_src) . '/';
                                        if (!in_array($_addslashes, $patterns) && $downloaded):

                                            $patterns[] = $_addslashes;
                                            $replacements[] = $mediaPath . '/' . $downloaded['folder'] . '/' . $downloaded['filename'];
                                            $counter++;
                                        endif;

                                    else:
                                        $downloaded = $this->downloadMedia($_src, $parsed['host'] . '/');
                                        $_addslashes = '/' . str_replace("/", "\/", $_src) . '/';
                                        if (!in_array($_addslashes, $patterns) && $downloaded):
                                            $patterns[] = $_addslashes;
                                            $replacements[] = $mediaPath . '/' . $downloaded['folder'] . '/' . $downloaded['filename'];
                                            $counter++;
                                        endif;

                                    endif;
                                elseif (strpos($_src, 'cabit-content') !== false):
                                    $newfile = preg_replace('/\/\/cabit-content\/files/', '', $_src);
                                    if (!is_file(PATH_ROOT . $newfile)):
                                        $file = preg_replace('/filemanager\/userfiles\/\//', '', $_src);
                                        $downloaded = $this->downloadMedia('https://cuahangsamsung.vn' . $file);
                                        $_addslashes = '/' . str_replace("/", "\/", $_src) . '/';
                                        if (!in_array($_addslashes, $patterns) && $downloaded):
                                            $patterns[] = $_addslashes;
                                            $replacements[] = $mediaPath . '/' . $downloaded['folder'] . '/' . $downloaded['filename'];
                                            $counter++;
                                        endif;
                                    endif;
                                endif;

                            endforeach;
                        endif;
                    endforeach;
                endif;
            endif;
            $patterns[] = '/\/\/cabit-content\/files/';
            $replacements[] = '';
            $newcontent = preg_replace($patterns, $replacements, $content);
            $post->setContent($newcontent);
            $this->em->persist($post);
        endforeach;
        $this->em->flush();
        echo 'downloaded' . $counter;
        exit;
    }
    public function importPost() {
        $startTime = microtime(true);
        $adapter = new \Laminas\File\Transfer\Adapter\Http();

        $_files = $adapter->getFileInfo();
        if (!empty($_files)):
            $allowed = ['xml'];

            foreach ($_files as $key => $files):
                if ($files['error'] == 0):
                    $fileInfo = pathinfo($_FILES[$key]['name']);

                    $ext = $fileInfo['extension'];
                    $fileName = $this->makeSafe($fileInfo['filename']) . '.' . $ext;
                    if (!in_array(strtolower($ext), $allowed)):
                        return [
                            'status' => 'error',
                            'msg' => 'File extension is not allowed. Please select the picture has extionsion as ' . implode(', ', $allowed)
                        ];
                    endif;
                    $dest = realpath(PATH_DATA . '/tmp');
                    $folder = 'import-post';
                    $path = $dest . DS . $folder;
                    if (!file_exists($path)):
                        mkdir($path, 0755, true);
                    endif;
                    $file = sprintf('%s/%s', $folder, $fileName);
                    $adapter->addFilter('Rename', array(
                        'target' => $dest . DS . $file,
                        'overwrite' => true), $files['name']
                    );

                    if (!$adapter->receive($files['name'])):
                        return [
                            'status' => 'error',
                            'msg' => 'Have error when uploading. Please submit again'
                        ];
                    else:
                        $authors = $posts = $categories = $tags = $terms = array();
                        $data = file_get_contents($dest . DS . $file);
                        $_datas = explode('<item>', $data);
                        $importcontent = $_datas[0];
                        $_total = count($_datas) - 1;
                        for ($i = 1; $i < $_total; $i++):
                            $importcontent .= '<item>' . $_datas[$i];
                            if (!($i % 50) || ($i == ($_total - 1))):
                                $this->wxr_version = $this->in_post = $this->cdata = $this->data = $this->sub_data = $this->in_tag = $this->in_sub_tag = false;
                                $this->authors = $this->posts = $this->term = $this->category = $this->tag = array();
                                $importcontent .= '</channel></rss>';
//                                    $fileName = ceil($i/50).'.xml';
//                                    file_put_contents($dest . DS .$fileName, $content);
//                                    exit;
                                $xml = xml_parser_create('UTF-8');
                                xml_parser_set_option($xml, XML_OPTION_SKIP_WHITE, 1);
                                xml_parser_set_option($xml, XML_OPTION_CASE_FOLDING, 0);
                                xml_set_object($xml, $this);
                                xml_set_character_data_handler($xml, 'cdata');
                                xml_set_element_handler($xml, 'tag_open', 'tag_close');
                                $datas = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $importcontent);
                                $importcontent = $_datas[0];
                                if (!xml_parse($xml, $datas, true)):
//            $current_line = xml_get_current_line_number($xml);
//            $current_column = xml_get_current_column_number($xml);
                                    $error_code = xml_get_error_code($xml);
                                    return [
                                        'status' => 'error',
                                        'msg' => xml_error_string($error_code)
                                    ];
                                endif;
                                xml_parser_free($xml);
                                if ($this->authors):
                                    foreach ($this->authors as $_key => $author) :
                                        if (!isset($authors[$_key])):
                                            if (!($_author = $this->em->getRepository('AppEntity\AppAdminUsers')->findOneBy(['email' => $author['author_email']]))):
                                                $_author = new \AppEntity\AppAdminUsers();
                                                $_author->setEmail($author['author_email']);
                                                $_author->setName($author['author_display_name']);
                                                $_author->setState(1);
                                                $_author->setUsername($author['author_login']);
                                                $_author->setPassword(password_hash($author['author_login'], PASSWORD_DEFAULT, array('cost' => 12)));
                                                $_author->setIsAdmin(1);
                                                $this->em->persist($_author);
                                            endif;
                                            $authors[$_key] = $_author;
                                        endif;
                                    endforeach;
                                endif;
//                                    $patterns = [
////            '/https:\/\/minasoft.vn\/wp-content\/uploads/',
////            '/https:\/\/www.minasoft.vn\/wp-content\/uploads/',
//                                        '/https:\/\/cuahangsamsung.vn\/cabit-content\/files/',
//                                        '/https:\/\/cuahangsamsung.com\/cabit-content\/files/',
//                                        '/http:\/\/cuahangsamsung.vn\/cabit-content\/files/',
//                                        '/http:\/\/www.cuahangsamsung.vn\/cabit-content\/files/',
//                                        '/https:\/\/cuahangsamsung.vn\/\/cabit-content\/files/',
//                                        '/https:\/\/cuahangsamsung.com\/\/cabit-content\/files/',
//                                        '/http:\/\/cuahangsamsung.vn\/\/cabit-content\/files/',
//                                        '/http:\/\/www.cuahangsamsung.vn\/\/cabit-content\/files/'
//                                    ];
//                                    $replacements = [
//                                        '/' . $this->config['settings']['media']['mediapath'],
//                                        '/' . $this->config['settings']['media']['mediapath'],
//                                        '/' . $this->config['settings']['media']['mediapath'],
//                                        '/' . $this->config['settings']['media']['mediapath'],
//                                        '/' . $this->config['settings']['media']['mediapath'],
//                                        '/' . $this->config['settings']['media']['mediapath'],
//                                        '/' . $this->config['settings']['media']['mediapath'],
//                                        '/' . $this->config['settings']['media']['mediapath']
//                                    ];
                                if ($this->posts):
                                    $total = count($this->posts);
                                    foreach ($this->posts as $postkey => $_post) :
//                                            if($_post['post_id']== 13289):
//                                                echo '<pre>';
//                                                print_r($_post);
//                                                echo '</pre>';
//                                                exit;
//                                                endif;
                                        $_post['post_name'] = $this->createAlias($_post['post_name']);
//                                            preg_match_all('/<img[^>]+>/i', $_post['post_content'], $imagelist);
//                                            echo '<pre>';
//                                            print_r($imagelist);
//                                            echo '</pre>';
//                                            exit;
//                                            $content = preg_replace($patterns, $replacements, $_post['post_content']);
                                        $content = $_post['post_content'];
                                        if ($_post['post_type'] == 'post'):
                                            if (!($post = $this->em->getRepository('AppEntity\AppPosts')->findOneBy(['alias' => $_post['post_name']]))):
                                                $post = new \AppEntity\AppPosts();
                                            else:
                                                $post->getTags()->clear();
                                            endif;
                                            $post->setAlias($_post['post_name']);
                                            $post->setCreated(new \DateTime($_post['post_date']));
                                            if (isset($_post['post_author']) && $_post['post_author'] && isset($authors[$_post['post_author']])):
                                                $post->setCreatedBy($authors[$_post['post_author']]);
                                            else:
                                                $post->setCreatedBy($user);
                                            endif;
                                            $post->setExcerpt($_post['post_excerpt']);
                                            $post->setStatus(1);
                                            $post->setMetatitle($_post['post_title']);
                                            $post->setName($_post['post_title']);
                                            $post->setWpid((int) $_post['post_id']);
                                            $post->setContent($content);
                                            $postCategories = $_post['terms'];
                                            if ($postCategories):
                                                foreach ($postCategories as $_postcategory):
                                                    if (!isset($categories[$_postcategory['domain']])):
                                                        $categories[$_postcategory['domain']] = [];
                                                    endif;
                                                    if (!isset($categories[$_postcategory['domain']][$_postcategory['slug']])):
                                                        if ($_postcategory['domain'] == 'category'):
                                                            if (!($_category = $this->em->getRepository('AppEntity\AppCategories')->findOneBy(['contenttype' => 'post', 'alias' => $_postcategory['slug']]))):
                                                                $_category = new \AppEntity\AppCategories();
                                                                $_category->setAlias($_postcategory['slug']);
                                                                $_category->setContenttype('post');
                                                                $_category->setName($_postcategory['name']);
                                                                $_category->setStatus(1);
                                                                $this->em->persist($_category);
                                                                $categories[$_postcategory['domain']][$_postcategory['slug']] = $_category;
                                                            endif;
                                                            $post->setCategory($_category);
                                                        elseif ($_postcategory['domain'] == 'post_tag'):
                                                            if (!($_tag = $this->em->getRepository('AppEntity\AppTags')->findOneBy(['alias' => $_postcategory['slug']]))):
                                                                $_tag = new \AppEntity\AppTags();
                                                                $_tag->setAlias($_postcategory['slug']);
                                                                $_tag->setName($_postcategory['name']);
                                                                $categories[$_postcategory['domain']][$_postcategory['slug']] = $_tag;
                                                                $this->em->persist($_tag);
                                                            endif;
                                                            $post->addTag($_tag);
                                                        endif;
                                                    else:
                                                        if ($_postcategory['domain'] == 'category'):
                                                            $post->setCategory($categories[$_postcategory['domain']][$_postcategory['slug']]);
                                                        elseif ($_postcategory['domain'] == 'post_tag'):
                                                            $post->addTag($categories[$_postcategory['domain']][$_postcategory['slug']]);
                                                        endif;
                                                    endif;
                                                endforeach;
                                            endif;
                                            if (isset($_post['postmeta']) && $_post['postmeta']):
                                                foreach ($_post['postmeta'] as $postMeta):
                                                    if ($postMeta['key'] == '_wpt_view_count'):
                                                        $post->setViews((int) $postMeta['value']);
                                                    elseif (($postMeta['key'] == '_thumbnail_id') && ((int) $postMeta['key'])):
                                                        if ($image = $this->em->getRepository('AppEntity\AppMedia')->findOneBy(['wpid' => $postMeta['key']])):
                                                            $post->setImage($image);
                                                        endif;

                                                    endif;
                                                endforeach;
                                            endif;
                                            $this->em->persist($post);
                                        elseif (($_post['post_type'] == 'attachment') && $_post['post_parent']):
                                            if (!$post = $this->em->getRepository('AppEntity\AppPosts')->findOneBy(['wpid' => $_post['post_parent']])):
                                                continue;
                                            endif;
                                            $image = $this->em->getRepository('AppEntity\AppMedia')->findOneBy(['wpid' => $_post['post_id']]);

                                            if (!$image):
                                                $image = new \AppEntity\AppMedia();
                                            endif;

                                            $image->setEnabled(1);
                                            $image->setWpid($_post['post_id']);
                                            $image->setName($_post['post_title']);
                                            if ($_post['postmeta']):
                                                foreach ($_post['postmeta'] as $postMeta):
                                                    if ($postMeta['key'] == '_wp_attached_file'):
//                                                    $file ='https://cuahangsamsung.vn//cabit-content/files/2022/03/samsung-galaxy-a13-4g-1-3.png';
//                                                            $exploded = explode('cabit-content/files/', $postMeta['value']);
                                                        $_baseName = basename($postMeta['value']);
                                                        $pathinfo = pathinfo($_baseName);
                                                        $ext = $pathinfo['extension'];
                                                        $filename = $this->makeSafe($pathinfo['filename']) . ".{$ext}";
                                                        $folder = trim(str_replace($_baseName, '', $postMeta['value']), '/');
                                                        $fullFolder = PATH_ROOT . '/' . $this->config['settings']['media']['mediapath'] . '/' . $folder;
                                                        if (!file_exists($fullFolder)):
                                                            @mkdir($fullFolder, 0755, true);
                                                        endif;
                                                        $fullPathFile = PATH_ROOT . '/' . $this->config['settings']['media']['mediapath'] . '/' . $folder . '/' . $filename;
                                                        if (!is_file($fullPathFile)):
                                                            $downloaded = $this->downloadMedia($this->base_url . '/cabit-content/files/' . $postMeta['value']);
                                                            if ($downloaded):
                                                                $folder = $downloaded['folder'];
                                                                $filename = $downloaded['filename'];
                                                            endif;
                                                        endif;
                                                        $image->setContext($folder);
                                                        $image->setFolder($folder);
                                                        $image->setFilename($filename);

                                                    elseif (($postMeta['key'] == '_wp_attachment_metadata') && ($postMeta['value'])):
                                                        if ($extraData = unserialize($postMeta['value'])):
                                                            if (isset($extraData['width'])):
                                                                $image->setWidth($extraData['width']);
                                                            endif;
                                                            if (isset($extraData['height'])):
                                                                $image->setHeight($extraData['height']);
                                                            endif;
                                                            if (isset($extraData['sizes']) && isset($extraData['sizes']['thumbnail'])):
                                                                $image->setContentType($extraData['sizes']['thumbnail']['mime-type']);
                                                            endif;
                                                        endif;
                                                    endif;
                                                endforeach;
                                            endif;
                                            $post->setImage($image);
                                            $this->em->persist($image);
                                            $this->em->persist($post);

                                        endif;
                                        if ($postkey && ($postkey % 50 == 0)):
                                            $this->em->flush();
                                        endif;
                                    endforeach;
                                else:
                                    return [
                                        'status' => 'error',
                                        'msg' => 'Does not have any posts imported'
                                    ];
                                endif;
                            endif;
                        endfor;
                    endif;
                else:
                    return [
                        'status' => 'error',
                        'msg' => 'Have error when uploading. Please submit again'
                    ];
                endif;
            endforeach;
            $this->em->flush();
            $executeTime = microtime(true) - $startTime;
            return [
                'status' => 'success',
                'msg' => 'Đã import xong dữ liệu trong thời gian ' . number_format($executeTime) . ' s'
            ];
//                $this->flashMessenger()->addSuccessMessage('');
        endif;
        return [
            'status' => 'error',
            'msg' => 'Vui lòng upload file xml được export từ wordpress'
        ];
    }

    public function downloadMedia($file, $explodeString = 'cabit-content/files/') {
        $_file = trim($file);
//                                                    $file ='https://cuahangsamsung.vn//cabit-content/files/2022/03/samsung-galaxy-a13-4g-1-3.png';
        $patterns = [
            '/\/\/dev.samsungshop.vn/',
            '/\/\/cuahangsamsung.com/',
            '/\/\/samsung.tabletplaza.vn/',
            '/\/\/samsungshop.vn/',
            '/\/\/cuahangsamsung.com.vn/',
            '/\/\/cuahangtrainghiemsamsung.net/',
            '/\/\/samsungbinhduong.com/',
            '/\/\/samsungbinhduong.net/',
            '/\/\/cuahangsamsung.net/',
            '/\/\/tragopsamsung.com/',
            '/\/\/tragopsamsung.net/',
            '/\/\/samplaza.com/',
            '/\/\/samplaza.vn/',
            '/\/\/samplaza.net/',
            '/\/\/samplaza.com.vn/',
            '/\/\/cuahangsamsung.com/',
        ];
        $domain = '//cuahangsamsung.vn';
        $replacements = [$domain, $domain, $domain, $domain, $domain, $domain, $domain, $domain, $domain, $domain, $domain, $domain, $domain, $domain, $domain, $domain];
        $file = preg_replace($patterns, $replacements, $_file);
        $exploded = explode($explodeString, $file);

        if (count($exploded) < 2):
            if (((strpos($file, 'cuahangsamsung.vn') !== false)) && (strpos($file, 'wp-content/uploads') !== false)):
                $file = preg_replace(['/wp-content\/uploads/'], ['cabit-content/files'], $file);
                return $this->downloadMedia($file);
            endif;
        else:
            $_baseName = basename($exploded[1]);
        endif;
        $pathinfo = pathinfo($_baseName);
        if (!isset($pathinfo['extension'])):
            return [];
        endif;
        $ext = $pathinfo['extension'];

        $filename = $this->makeSafe($pathinfo['filename']) . ".{$ext}";
        $folder = trim(str_replace($_baseName, '', $exploded[1]), '/');
        if (!$folder):
            return [];
        endif;
        $fullFolder = PATH_ROOT . '/' . $this->config['settings']['media']['mediapath'] . '/' . $folder;
        if (!file_exists($fullFolder)):
            mkdir($fullFolder, 0755, true);
        endif;
        $fullPathFile = $fullFolder . '/' . $filename;
        if (!is_file($fullPathFile)):
//                                                        file_put_contents($fullPathFile, file_get_contents(urlencode($file)));
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $file);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_COOKIESESSION, true);

//                                                        curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
//                                                        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
//                                                        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            $result = curl_exec($ch);
            curl_close($ch);
            if ($result):
                file_put_contents($fullPathFile, $result);
            else:
                return [];
            endif;

        endif;
        return ['folder' => $folder, 'filename' => $filename];
    }

    function tag_open($parse, $tag, $attr) {
        if (in_array($tag, $this->wp_tags)):
            $this->in_tag = substr($tag, 3);
            return;
        endif;

        if (in_array($tag, $this->wp_sub_tags)):
            $this->in_sub_tag = substr($tag, 3);
            return;
        endif;

        switch ($tag) {
            case 'category':
                if (isset($attr['domain'], $attr['nicename'])) :
                    $this->sub_data['domain'] = $attr['domain'];
                    $this->sub_data['slug'] = $attr['nicename'];
                endif;
                break;
            case 'item': $this->in_post = true;
            case 'title': if ($this->in_post)
                    $this->in_tag = 'post_title';
                break;
            case 'guid': $this->in_tag = 'guid';
                break;
            case 'dc:creator': $this->in_tag = 'post_author';
                break;
            case 'content:encoded': $this->in_tag = 'post_content';
                break;
            case 'excerpt:encoded': $this->in_tag = 'post_excerpt';
                break;

            case 'wp:term_slug': $this->in_tag = 'slug';
                break;
            case 'wp:meta_key': $this->in_sub_tag = 'key';
                break;
            case 'wp:meta_value': $this->in_sub_tag = 'value';
                break;
        }
    }

    function cdata($parser, $cdata) {
        if (!trim($cdata))
            return;

        if ((false !== $this->in_tag) || (false !== $this->in_sub_tag)):
            $this->cdata .= $cdata;
        else :
            $this->cdata .= trim($cdata);
        endif;
    }

    function tag_close($parser, $tag) {
        switch ($tag):
            case 'wp:comment':
                unset($this->sub_data['key'], $this->sub_data['value']); // remove meta sub_data
                if (!empty($this->sub_data))
                    $this->data['comments'][] = $this->sub_data;
                $this->sub_data = false;
                break;
            case 'wp:commentmeta':
                $this->sub_data['commentmeta'][] = array(
                    'key' => $this->sub_data['key'],
                    'value' => $this->sub_data['value']
                );
                break;
            case 'category':
                if (!empty($this->sub_data)):
                    $this->sub_data['name'] = $this->cdata;
                    $this->data['terms'][] = $this->sub_data;
                endif;
                $this->sub_data = false;
                break;
            case 'wp:postmeta':
                if (!empty($this->sub_data))
                    $this->data['postmeta'][] = $this->sub_data;
                $this->sub_data = false;
                break;
            case 'item':
                $this->posts[] = $this->data;
                $this->data = false;
                break;
            case 'wp:category':
            case 'wp:tag':
            case 'wp:term':
                $n = substr($tag, 3);
                array_push($this->$n, $this->data);
                $this->data = false;
                break;
            case 'wp:termmeta':
                if (!empty($this->sub_data)):
                     $this->data['termmeta'][] = $this->sub_data;
                endif;
                $this->sub_data = false;
                break;
            case 'wp:author':
                if (!empty($this->data['author_login']))
                    $this->authors[$this->data['author_login']] = $this->data;
                $this->data = false;
                break;
            case 'wp:base_site_url':
                $this->base_url = $this->cdata;
                if (!isset($this->base_blog_url)) :
                     $this->base_blog_url = $this->cdata;
                endif;
                break;
            case 'wp:base_blog_url':
                $this->base_blog_url = $this->cdata;
                break;
            case 'wp:wxr_version':
                $this->wxr_version = $this->cdata;
                break;

            default:
                if ($this->in_sub_tag):
                    $this->sub_data[$this->in_sub_tag] = !empty($this->cdata) ? $this->cdata : '';
                    $this->in_sub_tag = false;
                elseif ($this->in_tag):
                    $this->data[$this->in_tag] = !empty($this->cdata) ? $this->cdata : '';
                    $this->in_tag = false;
            endif;
        endswitch;

        $this->cdata = false;
    }
}
