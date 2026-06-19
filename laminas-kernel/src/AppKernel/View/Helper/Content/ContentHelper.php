<?php

namespace AppKernel\View\Helper\Content;

use Laminas\View\Helper\AbstractHelper;

class ContentHelper extends AbstractHelper {

    private $shortcode_tags;
    private $em;
    private $container;
    private $config;
    private $cacheEnable;
    private $cacheTime;
    private $globalParams;
    private $translator;

    public function __construct($em, $translator, $config, $container) {
        $this->em = $em;
        $this->translator = $translator;
        $this->config = $config;
        $this->container = $container;
        $settings = $this->getSettings();
        $this->cacheEnable = (bool) ($settings['general']['cacheenable'] ?? false);
        $this->cacheTime = (int) ($settings['general']['cachetime'] ?? false);
        $this->shortcode_tags = [
            'block' => 'Block',
            'position' => 'Position',
            'button' => 'Button',
            'productpost' => 'Productpost',
        ];
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

    public function do_shortcode($content = '', $ignore_html = false, $globalParams = []) {
        $shortcode_tags = $this->shortcode_tags;
        $this->globalParams = $globalParams;
        if (false === strpos($content, '[')) {
            return $content;
        }

        if (empty($shortcode_tags) || !is_array($shortcode_tags))
            return $content;

        // Find all registered tag names in $content.
        preg_match_all('@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches);
        $tagnames = array_intersect(array_keys($shortcode_tags), $matches[1]);

        if (empty($tagnames)) {
            return $content;
        }

        $content = $this->do_shortcodes_in_html_tags($content, $ignore_html, $tagnames);

        $pattern = $this->get_shortcode_regex($tagnames);
        $content = preg_replace_callback("/$pattern/", array($this, 'do_shortcode_tag'), $content);

        // Always restore square braces so we don't break things like <!--[if IE ]>
        $content = $this->unescape_invalid_shortcodes($content);

        return $content;
    }

    protected function do_shortcodes_in_html_tags($content, $ignore_html, $tagnames) {
        // Normalize entities in unfiltered HTML before adding placeholders.
        $trans = array('&#91;' => '&#091;', '&#93;' => '&#093;');
        $content = strtr($content, $trans);
        $trans = array('[' => '&#91;', ']' => '&#93;');

        $pattern = $this->get_shortcode_regex($tagnames);
        $textarr = $this->html_split($content);

        foreach ($textarr as &$element) {
            if ('' == $element || '<' !== $element[0]) {
                continue;
            }

            $noopen = false === strpos($element, '[');
            $noclose = false === strpos($element, ']');
            if ($noopen || $noclose) {
                // This element does not contain shortcodes.
                if ($noopen xor $noclose) {
                    // Need to encode stray [ or ] chars.
                    $element = strtr($element, $trans);
                }
                continue;
            }

            if ($ignore_html || '<!--' === substr($element, 0, 4) || '<![CDATA[' === substr($element, 0, 9)) {
                // Encode all [ and ] chars.
                $element = strtr($element, $trans);
                continue;
            }

            $attributes = $this->kses_attr_parse($element);
            if (false === $attributes) {
                // Some plugins are doing things like [name] <[email]>.
                if (1 === preg_match('%^<\s*\[\[?[^\[\]]+\]%', $element)) {
                    $element = preg_replace_callback("/$pattern/", array($this, 'do_shortcode_tag'), $element);
                }

                // Looks like we found some crazy unfiltered HTML.  Skipping it for sanity.
                $element = strtr($element, $trans);
                continue;
            }

            // Get element name
            $front = array_shift($attributes);
            $back = array_pop($attributes);
            $matches = array();
            preg_match('%[a-zA-Z0-9]+%', $front, $matches);
            $elname = $matches[0];

            // Look for shortcodes in each attribute separately.
            foreach ($attributes as &$attr) {
                $open = strpos($attr, '[');
                $close = strpos($attr, ']');
                if (false === $open || false === $close) {
                    continue; // Go to next attribute.  Square braces will be escaped at end of loop.
                }
                $double = strpos($attr, '"');
                $single = strpos($attr, "'");
                if ((false === $single || $open < $single) && (false === $double || $open < $double)) {
                    // $attr like '[shortcode]' or 'name = [shortcode]' implies unfiltered_html.
                    // In this specific situation we assume KSES did not run because the input
                    // was written by an administrator, so we should avoid changing the output
                    // and we do not need to run KSES here.
                    $attr = preg_replace_callback("/$pattern/", array($this, 'do_shortcode_tag'), $attr);
                } else {
                    // $attr like 'name = "[shortcode]"' or "name = '[shortcode]'"
                    // We do not know if $content was unfiltered. Assume KSES ran before shortcodes.
                    $count = 0;
                    $new_attr = preg_replace_callback("/$pattern/", array($this, 'do_shortcode_tag'), $attr, -1, $count);
                    if ($count > 0) {
                        // Sanitize the shortcode output using KSES.
                        $new_attr = wp_kses_one_attr($new_attr, $elname);
                        if ('' !== trim($new_attr)) {
                            // The shortcode is safe to use now.
                            $attr = $new_attr;
                        }
                    }
                }
            }
            $element = $front . implode('', $attributes) . $back;

            // Now encode any remaining [ or ] chars.
            $element = strtr($element, $trans);
        }

        $content = implode('', $textarr);

        return $content;
    }

    protected function get_shortcode_regex($tagnames = null) {
        $shortcode_tags = $this->shortcode_tags;

        if (empty($tagnames)) {
            $tagnames = array_keys($shortcode_tags);
        }
        $tagregexp = join('|', array_map('preg_quote', $tagnames));

        // WARNING! Do not change this regex without changing do_shortcode_tag() and strip_shortcode_tag()
        // Also, see shortcode_unautop() and shortcode.js.
        return
                '\\['                              // Opening bracket
                . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
                . "($tagregexp)"                     // 2: Shortcode name
                . '(?![\\w-])'                       // Not followed by word character or hyphen
                . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
                . '[^\\]\\/]*'                   // Not a closing bracket or forward slash
                . '(?:'
                . '\\/(?!\\])'               // A forward slash not followed by a closing bracket
                . '[^\\]\\/]*'               // Not a closing bracket or forward slash
                . ')*?'
                . ')'
                . '(?:'
                . '(\\/)'                        // 4: Self closing tag ...
                . '\\]'                          // ... and closing bracket
                . '|'
                . '\\]'                          // Closing bracket
                . '(?:'
                . '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
                . '[^\\[]*+'             // Not an opening bracket
                . '(?:'
                . '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
                . '[^\\[]*+'         // Not an opening bracket
                . ')*+'
                . ')'
                . '\\[\\/\\2\\]'             // Closing shortcode tag
                . ')?'
                . ')'
                . '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
    }

    protected function html_split($input) {
        return preg_split($this->get_html_split_regex(), $input, -1, PREG_SPLIT_DELIM_CAPTURE);
    }

    protected function get_html_split_regex() {
        static $regex;

        if (!isset($regex)) {
            $comments = '!'           // Start of comment, after the <.
                    . '(?:'         // Unroll the loop: Consume everything until --> is found.
                    . '-(?!->)' // Dash not followed by end of comment.
                    . '[^\-]*+' // Consume non-dashes.
                    . ')*+'         // Loop possessively.
                    . '(?:-->)?';   // End of comment. If not found, match all input.

            $cdata = '!\[CDATA\['  // Start of comment, after the <.
                    . '[^\]]*+'     // Consume non-].
                    . '(?:'         // Unroll the loop: Consume everything until ]]> is found.
                    . '](?!]>)' // One ] not followed by end of comment.
                    . '[^\]]*+' // Consume non-].
                    . ')*+'         // Loop possessively.
                    . '(?:]]>)?';   // End of comment. If not found, match all input.

            $escaped = '(?='           // Is the element escaped?
                    . '!--'
                    . '|'
                    . '!\[CDATA\['
                    . ')'
                    . '(?(?=!-)'      // If yes, which type?
                    . $comments
                    . '|'
                    . $cdata
                    . ')';

            $regex = '/('              // Capture the entire match.
                    . '<'           // Find start of element.
                    . '(?'          // Conditional expression follows.
                    . $escaped  // Find end of escaped element.
                    . '|'           // ... else ...
                    . '[^>]*>?' // Find end of normal element.
                    . ')'
                    . ')/';
        }

        return $regex;
    }

    protected function kses_attr_parse($element) {
        $valid = preg_match('%^(<\s*)(/\s*)?([a-zA-Z0-9]+\s*)([^>]*)(>?)$%', $element, $matches);
        if (1 !== $valid) {
            return false;
        }

        $begin = $matches[1];
        $slash = $matches[2];
        $elname = $matches[3];
        $attr = $matches[4];
        $end = $matches[5];

        if ('' !== $slash) {
            // Closing elements do not get parsed.
            return false;
        }

        // Is there a closing XHTML slash at the end of the attributes?
        if (1 === preg_match('%\s*/\s*$%', $attr, $matches)) {
            $xhtml_slash = $matches[0];
            $attr = substr($attr, 0, -strlen($xhtml_slash));
        } else {
            $xhtml_slash = '';
        }

        // Split it
        $attrarr = $this->kses_hair_parse($attr);
        if (false === $attrarr) {
            return false;
        }

        // Make sure all input is returned by adding front and back matter.
        array_unshift($attrarr, $begin . $slash . $elname);
        array_push($attrarr, $xhtml_slash . $end);

        return $attrarr;
    }

    protected function kses_hair_parse($attr) {
        if ('' === $attr) {
            return array();
        }

        $regex = '(?:'
                . '[-a-zA-Z:]+'   // Attribute name.
                . '|'
                . '\[\[?[^\[\]]+\]\]?' // Shortcode in the name position implies unfiltered_html.
                . ')'
                . '(?:'               // Attribute value.
                . '\s*=\s*'       // All values begin with '='
                . '(?:'
                . '"[^"]*"'   // Double-quoted
                . '|'
                . "'[^']*'"   // Single-quoted
                . '|'
                . '[^\s"\']+' // Non-quoted
                . '(?:\s|$)'  // Must have a space
                . ')'
                . '|'
                . '(?:\s|$)'      // If attribute has no value, space is required.
                . ')'
                . '\s*';              // Trailing space is optional except as mentioned above.
        // Although it is possible to reduce this procedure to a single regexp,
        // we must run that regexp twice to get exactly the expected result.

        $validation = "%^($regex)+$%";
        $extraction = "%$regex%";

        if (1 === preg_match($validation, $attr)) {
            preg_match_all($extraction, $attr, $attrarr);
            return $attrarr[0];
        } else {
            return false;
        }
    }

    protected function do_shortcode_tag($m) {
        $shortcode_tags = $this->shortcode_tags;
        // allow [[foo]] syntax for escaping a tag
        if ($m[1] == '[' && $m[6] == ']') {
            return substr($m[0], 1, -1);
        }

        $tag = $m[2];

        $attr = $this->shortcode_parse_atts($m[3]);
        if (!is_callable([$this, $shortcode_tags[$tag]])) {
            /* translators: %s: shortcode tag */
            $message = sprintf('Attempting to parse a shortcode without a valid callback: %s', $tag);
            return $m[0];
        }
        $content = isset($m[5]) ? $m[5] : null;
        $output = $m[1] . call_user_func([$this, $shortcode_tags[$tag]], $attr, $content, $tag) . $m[6];

        return $output;
    }

    protected function shortcode_parse_atts($text) {
        $atts = array();
        $pattern = $this->get_shortcode_atts_regex();
        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
        if (preg_match_all($pattern, $text, $match, PREG_SET_ORDER)) {
            foreach ($match as $m) {
                if (!empty($m[1]))
                    $atts[strtolower($m[1])] = stripcslashes($m[2]);
                elseif (!empty($m[3]))
                    $atts[strtolower($m[3])] = stripcslashes($m[4]);
                elseif (!empty($m[5]))
                    $atts[strtolower($m[5])] = stripcslashes($m[6]);
                elseif (isset($m[7]) && strlen($m[7]))
                    $atts[] = stripcslashes($m[7]);
                elseif (isset($m[8]) && strlen($m[8]))
                    $atts[] = stripcslashes($m[8]);
                elseif (isset($m[9]))
                    $atts[] = stripcslashes($m[9]);
            }

            // Reject any unclosed HTML elements
            foreach ($atts as &$value) {
                if (false !== strpos($value, '<')) {
                    if (1 !== preg_match('/^[^<]*+(?:<[^>]*+>[^<]*+)*+$/', $value)) {
                        $value = '';
                    }
                }
            }
        } else {
            $atts = ltrim($text);
        }
        return $atts;
    }

    protected function get_shortcode_atts_regex() {
        return '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|\'([^\']*)\'(?:\s|$)|(\S+)(?:\s|$)/';
    }

    protected function unescape_invalid_shortcodes($content) {
        // Clean up entire string, avoids re-parsing HTML.
        $trans = array('&#91;' => '[', '&#93;' => ']');
        $content = strtr($content, $trans);

        return $content;
    }

    protected function block($attr = [], $content = '', $tag = '') {
        $id = (isset($attr['id']) && ((int) $attr['id'])) ? (int) $attr['id'] : 0;
        // $cacheSettings = $this->config['settings']['caches'];
        $now = new \DateTime();
        if (!$id || !($block = $this->em->getRepository('AppEntity\AppBlocks')->createQueryBuilder('a')->where('a.status = 1 and a.id=' . $id)->setMaxResults(1)
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-blocks-detail-' . $id)
                ->getOneOrNullResult())) :
            return '';
        else :
            if (method_exists($block, 'getTranslateOf') && ($blockTranslate = $block->getTranslateOf)) :
                $block = $blockTranslate;
            endif;
            $_template = $block->getBlockTemplate();
            $_blockconfig = $block->getConfig();
            $blockconfig = [];
            if ($_blockconfig) :
                $blockconfig = json_decode($_blockconfig, true);
            endif;
            $template = 'blocks/block-content/' . $_template;
//            $type = (isset($attr['type']) && $attr['type']) ? trim($attr['type']) : '';
            $view = $this->getView();
            $settings = $this->getSettings('all');
            $data = ['attr' => $attr, 'block' => $block, 'settings' => $settings, 'blockconfig' => $blockconfig];
            if ($_template == 'post-newest') :
                $qb = $this->em->getRepository(\AppEntity\AppPosts::class)->createQueryBuilder('a');
//                $qb->where('a.status = 1')->andWhere('a.mainSystem = :mainsystem')->setParameter('mainsystem', CURRENT_SYSTEM);
//                $_ids = (isset($attr['postids']) && trim($attr['postids'])) ? trim($attr['postids']) : '';
//                $_catid = (isset($attr['catid']) && (int) ($attr['catid'])) ? (int) trim($attr['catid']) : 0;
                $cateids = $blockconfig['categories'] ?? [];
//                if ($blockconfig['newest'] ?? '') :
//                    $cateids = $blockconfig['newest'];
//                endif;
                $cacheKey = 'block_post';

//                if ($_ids) :
//                    $cacheKey .= '_posts_ids_' . $_ids;
//                    $qb->andWhere('a.id IN (' . $_ids . ' )');
//                endif;
                if ($cateids) :
                    $cacheKey .= '_posts_in_cat_' . implode('_', $cateids);
                    $qb->andWhere('a.category IN (' . implode(', ', $cateids) . ')');
                endif;

                $posts = $qb->setMaxResults(5)
                        ->orderBy('a.publishedFrom', 'desc')
                        ->where("a.status=3") //public
                        ->andWhere("(a.publishedFrom <= :tocreateddate or a.publishedFrom is null)")
                        ->andWhere("(a.publishedTo >= :tocreateddate or a.publishedTo is null)")
                        ->setParameter('tocreateddate', $now->format('Y-m-d H:i:s'))
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-posts-list-' . $cacheKey)
                        ->getResult();
                $data['posts'] = $posts;
                if (!$posts) :
                    return '';
                endif;
            elseif ($_template == 'top-categories') :

                $_blockconfig = $block->getConfig();
                $blockconfig = [];
                if ($_blockconfig) :
                    $blockconfig = json_decode($_blockconfig, true);
                endif;
                $categoryIds = ($blockconfig && isset($blockconfig['categories'])) ? $blockconfig['categories'] : [];
                if ($categoryIds) :
                    $data['featuredCategories'] = $this->getFeaturedCategories($categoryIds);
                else :
                    return '';
                endif;
            elseif ($_template == 'youtube-feed') :

                $_blockconfig = $block->getConfig();
                $blockconfig = [];
                if ($_blockconfig) :
                    $blockconfig = json_decode($_blockconfig, true);
                endif;
                $categoryIds = (array) ($blockconfig['youtube-categories'] ?? []);
                if (!($totalLink = count($categoryIds))):
                    return '';
                endif;
                foreach ($categoryIds as $categoryId):
                    $youtubeFeedCategory = $this->em->getRepository(\AppEntity\AppCategories::class)->find($categoryId);
                    $this->getYoutubeFeedByCategory($youtubeFeedCategory, 10, true);
                endforeach;
                $youtubeFeeds = $this->em->getRepository(\AppEntity\AppYoutubeFeeds::class)->createQueryBuilder('a')
                                ->select('a.id,a.title,a.url,a.ytId,a.image,a.published')
                                ->where('a.category IN (:cateIds)')->setParameter('cateIds', $categoryIds)
                                ->orderBy('a.published', 'desc')
                                ->addOrderBy('a.id', 'desc')
                                ->setMaxResults(5)
                                ->getQuery()->getResult();
                $data['youtubeFeeds'] = $youtubeFeeds;
//            elseif ($_template == 'youtube-feed') :
//
//                $this->getMultiMediaCategories();
            elseif ((in_array($_template, ['cat-feeds-style-1', 'cat-feeds-style-2']))) :
                $_blockconfig = $block->getConfig();
                $blockconfig = [];
                if ($_blockconfig) :
                    $blockconfig = json_decode($_blockconfig, true);
                endif;
                $categoryId = (int) ($blockconfig['rss-categories'] ?? 0);
                if (!$categoryId):
                    return '';
                endif;
                $data['category'] = $this->em->getRepository(\AppEntity\AppCategories::class)->findOneBy(['id' => $categoryId, 'contenttype' => 'feed']);

                $data['feeds'] = ($data['category']) ? $this->getFeedByCategory($data['category']) : [];

//            elseif ($_template == 'faqs'):
//                $qb = $this->em->getRepository(\AppEntity\AppFaqs::class)->createQueryBuilder('a');
//                $qb->where('a.status = 1')->andWhere('a.mainSystem = :mainsystem')->setParameter('mainsystem', CURRENT_SYSTEM);
//                $faqs = $qb->setMaxResults(40)
//                        ->orderBy('a.id', 'desc')
//                        ->getQuery()
//                        ->useQueryCache(true)
//                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-faqs-list-40')
//                        ->getResult();
//                $data['faqs'] = $faqs;
//                if (!$faqs) :
//                    return '';
//                endif;
            elseif ($_template == 'documents'):
                $qb = $this->em->getRepository(\AppEntity\AppFastLink::class)->createQueryBuilder('a');
                $qb->where('a.status = 1')->andWhere('a.type = 1')->select('a.id,a.title,a.url,a.image,a.ordering');
                $data['documents'] = $qb->setMaxResults(4)
                        ->orderBy('a.ordering', 'asc')
                        ->addOrderBy('a.id', 'desc')
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-fast-links-4')
                        ->getResult();
                $_blockconfig = $block->getConfig();
                $blockconfig = [];
                if ($_blockconfig) :
                    $blockconfig = json_decode($_blockconfig, true);
                endif;
                $categoryId = (int) ($blockconfig['banner-position'] ?? 0);
                $data['banners'] = ($categoryId) ? $this->em->getRepository(\AppEntity\AppBanners::class)
                                ->createQueryBuilder('a')->where('a.status = 1 and a.position =' . $categoryId)->setMaxResults(4)
                                ->orderBy('a.ordering', 'asc')
                                ->addOrderBy('a.id', 'asc')
                                ->getQuery()
                                ->useQueryCache(true)
                                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-banners-list-category-' . $categoryId)
                                ->getResult() : [];
//            elseif ($_template == 'philanthropize'):
//
//                $application = $this->container->get('Application');
//
//                $routeMatch = $application->getMvcEvent()->getRouteMatch();
//                $currentRoute = $routeMatch->getMatchedRouteName();
//                $_rmParams = $routeMatch->getParams() ?: [];
//                $data['currentRoute'] = $currentRoute;
//                $data['currentRouteParams'] = $_rmParams;
//                $showDatas = $blockconfig['show-datas'] ?? [];
//                if (in_array('list', $showDatas)):
//                    $qb = $this->em->getRepository(\AppEntity\AppPhilanthropize::class)->createQueryBuilder('a');
//                    $qb->where('a.status >= 1')->andWhere('a.mainSystem = :mainsystem')->setParameter('mainsystem', CURRENT_SYSTEM);
//                    $page = (int) ($_REQUEST['page'] ?? 1);
//                    $limit = 20;
//                    if ($page < 1):
//                        $page = 1;
//                    endif;
//                    $offset = ($page - 1) * $limit;
//                    $qbTotal = clone $qb;
//                    $_total = $qbTotal->select('count(a.id)')
//                            ->getQuery()
//                            ->useQueryCache(true)
//                            ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-philanthropize-total')
//                            ->getSingleScalarResult();
//                    $items = $qb->setMaxResults($limit)
//                            ->setFirstResult($offset)
//                            ->orderBy('a.id', 'desc')
//                            ->getQuery()
//                            ->useQueryCache(true)
//                            ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-philanthropize-list-page' . $page)
//                            ->getResult();
//                    $data['items'] = $items;
//                    $data['limit'] = $limit;
//                    $data['page'] = $page;
//                    $data['total'] = $_total;
//                endif;
//                if (in_array('form', $showDatas)):
//                    $user = $this->container->get('Laminas\Authentication\AuthenticationService')->getIdentity();
//                    $data['form'] = new \Frontend\Dashboard\Form\Philanthropize($this->em, $user);
//                endif;
//            elseif ($_template == 'news'):
//                $application = $this->container->get('Application');
//
//                $routeMatch = $application->getMvcEvent()->getRouteMatch();
//                $currentRoute = $routeMatch->getMatchedRouteName();
//                $_rmParams = $routeMatch->getParams() ?: [];
//                $data['currentRoute'] = $currentRoute;
//                $data['currentRouteParams'] = $_rmParams;
//            elseif ($_template == 'products') :
//
//                $qb = $this->em->getRepository(\AppEntity\AppProducts::class)->createQueryBuilder('a');
//                $qb->where('a.status = 1');
//                $cacheKey = $_template;
//
//                $products = $qb->setMaxResults(24)
//                        ->orderBy('a.ordering', 'desc')
//                        ->addOrderBy('a.id', 'desc')
//                        ->getQuery()
//                        ->useQueryCache(true)
//                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-products-list-' . $cacheKey)
//                        ->getResult();
//                $data['products'] = $products;
//                if (!$products) :
//                    return '';
//                endif;
//
//            elseif ($_template == 'testimonial'):
//                $cacheKey = $_template;
//
//                $testimonials = $this->em->getRepository(\AppEntity\AppTestimonials::class)->createQueryBuilder('a')
//                        ->where('a.status = 1')->andWhere('a.mainSystem = :mainsystem')->setParameter('mainsystem', CURRENT_SYSTEM)
//                        ->getQuery()
//                        ->useQueryCache(true)
//                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-testimonials-list-' . $cacheKey)
//                        ->getResult();
//
//                $data['testimonials'] = $testimonials;

            endif;
            if ($this->globalParams) :
                $data['custom_data'] = $this->globalParams;
            endif;
            return $view->render($template, $data);
        endif;
    }

    protected function position($attr = [], $content = '', $tag = '') {
        $id = (isset($attr['id']) && ((int) $attr['id'])) ? (int) $attr['id'] : 0;
        // $cacheSettings = $this->config['settings']['caches'];
        if (!$id || !($block = $this->em->getRepository('AppEntity\AppCategories')->createQueryBuilder('a')->where('a.status = 1 and a.contenttype=\'banner\' and a.id=' . $id)->setMaxResults(1)
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-categories-detail-' . $id)
                ->getOneOrNullResult())):
            return '';
        else:
            $view = $this->getView();
            $settings = $this->getSettings('media');
            $attr['template'] = $block->getAlias();
            $template = 'blocks/banners/' . $attr['template'];
            $banners = $this->em->getRepository('AppEntity\AppBanners')
                    ->createQueryBuilder('a')->where('a.status = 1 and a.position =' . $id)->setMaxResults(20)
                    ->orderBy('a.ordering', 'asc')
                    ->addOrderBy('a.id', 'asc')
                    ->getQuery()
                    ->useQueryCache(true)
                    ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-banners-list-category-' . $id)
                    ->getResult();
            return $view->render($template, ['attr' => $attr, 'banners' => $banners, 'settings' => $settings, 'block' => $block]);
        endif;
    }

    public function getSpotLight() {

        $now = new \DateTime();
        $top1Posts = $this->em->getRepository(\AppEntity\AppPosts::class)->createQueryBuilder('a')
                ->where("a.status=3") //public
                ->andWhere('a.featured=2')// tin noi bat
                ->andWhere('a.type=3')// tin chu tich
                ->andWhere("(a.publishedFrom <= :tocreateddate or a.publishedFrom is null)")
                ->andWhere("(a.publishedTo >= :tocreateddate or a.publishedTo is null)")
                ->setParameter('tocreateddate', $now->format('Y-m-d H:i:s'))
                ->setMaxResults(4)
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-posts-top1')
                ->getResult();
        $top2Posts = $this->em->getRepository(\AppEntity\AppPosts::class)->createQueryBuilder('a')
                ->where("a.status=3") //public
                ->andWhere('a.featured=2')// tin noi bat
                ->andWhere('a.type=2')// tin pho chu tich
                ->andWhere("(a.publishedFrom <= :tocreateddate or a.publishedFrom is null)")
                ->andWhere("(a.publishedTo >= :tocreateddate or a.publishedTo is null)")
                ->setParameter('tocreateddate', $now->format('Y-m-d H:i:s'))
                ->setMaxResults(4)
                ->orderBy('a.ordering', 'asc')
                ->addOrderBy('a.publishedFrom', 'desc')
                ->addOrderBy('a.id', 'desc')
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-posts-top2')
                ->getResult();
//        $top3Posts = $this->em->getRepository(\AppEntity\AppPosts::class)->createQueryBuilder('a')
//                ->where("a.status=3") //public
//                ->andWhere('a.featured=2')// tin noi bat
//                ->andWhere('(a.type is null or a.type not in (2,3))')// tin binh thuong
//                ->andWhere("(a.publishedFrom <= :tocreateddate or a.publishedFrom is null)")
//                ->andWhere("(a.publishedTo >= :tocreateddate or a.publishedTo is null)")
//                ->setParameter('tocreateddate', $now->format('Y-m-d H:i:s'))
//                ->setMaxResults(2)
//                ->orderBy('a.ordering', 'asc')
//                ->addOrderBy('a.publishedFrom', 'desc')
//                ->addOrderBy('a.id', 'desc')
//                ->getQuery()
//                ->useQueryCache(true)
//                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-posts-top3')
//                ->getResult();
        return [
            'top1' => $top1Posts, // tin chu tich
            'top2' => $top2Posts, // tin pho chu tich
//            'top3' => $top3Posts// phong ban khac
        ];
        ;
    }

    public function getFeaturedCategories($categoryIds = []) {
        if (!$categoryIds):
            return [];
        endif;
        $categories = $this->em->getRepository(\AppEntity\AppCategories::class)->createQueryBuilder('a', 'a.id')
                ->where("a.status=1") //public
                ->select('a.id,a.name,a.alias')
                ->andWhere('a.id in (:categoryIds)')->setParameter('categoryIds', $categoryIds)// nhập id ở đây
                ->setMaxResults(8)
                ->orderBy('a.lft', 'asc')
                ->addOrderBy('a.ordering', 'asc')
                ->addOrderBy('a.id', 'desc')
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-categories-featured')
                ->getResult();
        if (!$categories):
            return [];
        endif;
        $now = new \DateTime();
        foreach ($categories as $cateId => &$cate):
            $cate['posts'] = $this->em->getRepository(\AppEntity\AppPosts::class)->createQueryBuilder('a')
                    ->select('a.id,a.name,a.image,a.alias,a.excerpt')
                    ->where("a.status=3") //public
                    ->andWhere('a.category=' . $cateId)
//                ->andWhere('a.featured=2')// tin noi bat
                    ->andWhere('(a.type is null or a.type not in (2,3))')// tin binh thuong
                    ->andWhere("(a.publishedFrom <= :tocreateddate or a.publishedFrom is null)")
                    ->andWhere("(a.publishedTo >= :tocreateddate or a.publishedTo is null)")
                    ->setParameter('tocreateddate', $now->format('Y-m-d H:i:s'))
                    ->setMaxResults(5)
                    ->orderBy('a.ordering', 'asc')
                    ->addOrderBy('a.publishedFrom', 'desc')
                    ->addOrderBy('a.id', 'desc')
                    ->getQuery()
                    ->useQueryCache(true)
                    ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-posts-category-5-' . $cateId)
                    ->getResult();
        endforeach;
        return $categories;
    }

    public function getBreakingPosts() {
        $now = new \DateTime();
        return $this->em->getRepository(\AppEntity\AppPosts::class)->createQueryBuilder('a')
                        ->select('a.id,a.name,a.image,a.alias,a.excerpt,c.alias category')
                        ->leftJoin('a.category', 'c')
                        ->where("a.status=3") //public
                        ->andWhere('a.featured=1')// tin nong
                        ->andWhere('(a.type is null or a.type not in (2,3))')// tin binh thuong
                        ->andWhere("(a.publishedFrom <= :tocreateddate or a.publishedFrom is null)")
                        ->andWhere("(a.publishedTo >= :tocreateddate or a.publishedTo is null)")
                        ->setParameter('tocreateddate', $now->format('Y-m-d H:i:s'))
                        ->setMaxResults(5)
                        ->orderBy('a.ordering', 'asc')
                        ->addOrderBy('a.publishedFrom', 'desc')
                        ->addOrderBy('a.id', 'desc')
                        ->getQuery()
                        ->useQueryCache(true)
                        ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-posts-breaking-5')
                        ->getResult();
    }

    public function getMultiMediaCategories() {
        $now = new \DateTime();
        $datas = [
            'photos' => [
                'title' => 'Photos',
                'iconClass' => 'far fa-images sm:hidden',
                'items' => []
            ],
            'videos' => [
                'title' => 'Video',
                'iconClass' => 'fab fa-youtube sm:hidden',
                'items' => []
            ],
            'infographics' => [
                'title' => 'Infographics',
                'iconClass' => 'far fa-file-image sm:hidden',
                'items' => []
            ],
            'emagazines' => [
                'title' => 'Emagazines',
                'iconClass' => 'far fa-newspaper sm:hidden',
                'items' => []
            ],
        ];
        $datas['videos']['items'] = $this->em->getRepository(\AppEntity\AppPostVideo::class)->createQueryBuilder('a')
                ->select('a.id,a.name,a.alias,a.youtubeUrl,a.youtubeId,a.youtubeThumbnail image,c.id category,a.excerpt')
                ->leftJoin('a.category', 'c')
                ->where("a.status=3") //public
                ->andWhere("(a.publishedFrom <= :tocreateddate or a.publishedFrom is null)")
                ->andWhere("(a.publishedTo >= :tocreateddate or a.publishedTo is null)")
                ->setParameter('tocreateddate', $now->format('Y-m-d H:i:s'))
                ->setMaxResults(7)
                ->orderBy('a.featured', 'desc')
                ->addOrderBy('a.ordering', 'asc')
                ->addOrderBy('a.publishedFrom', 'desc')
                ->addOrderBy('a.id', 'desc')
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-post-video-7')
                ->getResult();
        $datas['photos']['items'] = $this->em->getRepository(\AppEntity\AppPostImage::class)->createQueryBuilder('a')
                ->select('a.id,a.name,a.alias,a.featuredImage image,a.images,c.id category,a.excerpt')
                ->leftJoin('a.category', 'c')
                ->where("a.status=3") //public
                ->andWhere("(a.publishedFrom <= :tocreateddate or a.publishedFrom is null)")
                ->andWhere("(a.publishedTo >= :tocreateddate or a.publishedTo is null)")
                ->setParameter('tocreateddate', $now->format('Y-m-d H:i:s'))
                ->setMaxResults(9)
                ->orderBy('a.featured', 'desc')
                ->addOrderBy('a.ordering', 'asc')
                ->addOrderBy('a.publishedFrom', 'desc')
                ->addOrderBy('a.id', 'desc')
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-post-video-7')
                ->getResult();
        $datas['infographics']['items'] = $this->em->getRepository(\AppEntity\AppPostInfogaphic::class)->createQueryBuilder('a')
                ->select('a.id,a.name,a.alias,a.image image,c.id category,a.excerpt')
                ->leftJoin('a.category', 'c')
                ->where("a.status=3") //public
                ->andWhere("(a.publishedFrom <= :tocreateddate or a.publishedFrom is null)")
                ->andWhere("(a.publishedTo >= :tocreateddate or a.publishedTo is null)")
                ->setParameter('tocreateddate', $now->format('Y-m-d H:i:s'))
                ->setMaxResults(5)
                ->orderBy('a.featured', 'desc')
                ->addOrderBy('a.ordering', 'asc')
                ->addOrderBy('a.publishedFrom', 'desc')
                ->addOrderBy('a.id', 'desc')
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-post-video-7')
                ->getResult();
        $datas['emagazines']['items'] = $this->em->getRepository(\AppEntity\AppPostEMagazine::class)->createQueryBuilder('a')
                ->select('a.id,a.name,a.alias,a.image image,c.id category,a.excerpt')
                ->leftJoin('a.category', 'c')
                ->where("a.status=3") //public
                ->andWhere("(a.publishedFrom <= :tocreateddate or a.publishedFrom is null)")
                ->andWhere("(a.publishedTo >= :tocreateddate or a.publishedTo is null)")
                ->setParameter('tocreateddate', $now->format('Y-m-d H:i:s'))
                ->setMaxResults(5)
                ->orderBy('a.featured', 'desc')
                ->addOrderBy('a.ordering', 'asc')
                ->addOrderBy('a.publishedFrom', 'desc')
                ->addOrderBy('a.id', 'desc')
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-post-video-7')
                ->getResult();
        if (count($datas['videos']['items']) + count($datas['photos']['items']) + count($datas['infographics']['items']) + count($datas['infographics']['items'])):
            return $datas;
        else:
            return [];
        endif;
    }

    public function getYoutubeFeedByCategory(\AppEntity\AppCategories $category, $limit = 10, $isCheckFetch = false) {
        if (!$category):
            return [];
        endif;
        $_blockconfig = $category->getAttr();
        $blockconfig = [];
        if ($_blockconfig):
            $blockconfig = json_decode($_blockconfig, true);
        endif;
        $channelId = $blockconfig['channelId'] ?? '';
        if (!$channelId):
            return [];
        endif;
        $urlRss = 'https://www.youtube.com/feeds/videos.xml?channel_id=' . $channelId;
        $lastUpdate = $blockconfig['lastUpdate'] ?? '';
        if ($lastUpdate):
            $lastTimeUpdated = new \DateTime();
            $lastTimeUpdated->sub(new \DateInterval('PT60M')); // 15p
            $lastUpdate = new \DateTime($lastUpdate);
            if ($lastUpdate > $lastTimeUpdated):
                if ($isCheckFetch):
                    return true;
                //return để không lấy dữ liệu nếu như đã có mới nhất
                endif;
                return $this->em->getRepository(\AppEntity\AppYoutubeFeeds::class)->createQueryBuilder('a')
                                ->select('a.id,a.title,a.url,a.ytId,a.image,a.published')->where('a.category = ' . $category->getId())
                                ->orderBy('a.published', 'desc')
                                ->addOrderBy('a.id', 'desc')
                                ->setMaxResults($limit)
                                ->getQuery()->getResult();
            endif;
        endif;
        $fetcher = new \AppKernel\Plugin\Rss\RssFetcher($urlRss);
        $items = [];
        $articles = $fetcher->youtubeFetch();

        if ($articles):
            foreach ($articles as $article):
                $item = $this->em->getRepository(\AppEntity\AppYoutubeFeeds::class)->findOneBy(['ytId' => $article['ytId']]);
                if (!$item):
                    $item = new \AppEntity\AppYoutubeFeeds();
                endif;
                $items[] = $article;
                $article['category'] = $category;
                foreach ($article as $key => $value):
                    $field = 'set' . ucfirst($key);
                    if (method_exists($item, $field)):
                        $item->$field($value);
                    endif;
                endforeach;
                $this->em->persist($item);

            endforeach;
        endif;

        try {

            $now = new \DateTime();
            $blockconfig['lastUpdate'] = $now->format('Y-m-d H:i:s');
            $category->setAttr(json_encode($blockconfig));
            $this->em->persist($category);
            $this->em->flush();
            if ($isCheckFetch):
                return true;
            //return để không lấy dữ liệu nếu như đã có mới nhất
            endif;
            $limited = array_slice($items, 0, $limit);
            return $limited;
        } catch (Exception $ex) {
            echo '<pre>';
            print_r($ex);
            echo '</pre>';
            exit;
        }
        exit;
    }

    public function getFeedByCategory(\AppEntity\AppCategories $category) {
        if (!$category):
            return [];
        endif;
        $_blockconfig = $category->getAttr();
        $blockconfig = [];
        if ($_blockconfig):
            $blockconfig = json_decode($_blockconfig, true);
        endif;
        $_urlRss = (array) ($blockconfig['urlrss'] ?? []);
        $totalLink = count($_urlRss);
        if (!$totalLink):
            return [];
        endif;
        $modulus = 50 % $totalLink; // dư
        $floor = floor(50 / $totalLink);
        $lastUpdate = $blockconfig['lastUpdate'] ?? '';
        if ($lastUpdate):
            $lastTimeUpdated = new \DateTime();
            $lastTimeUpdated->sub(new \DateInterval('PT60M')); // 15p
            $lastUpdate = new \DateTime($lastUpdate);
            if ($lastUpdate > $lastTimeUpdated):
                return $this->em->getRepository(\AppEntity\AppFeeds::class)->createQueryBuilder('a')
                                ->select('a.id,a.title,a.description,a.content,a.image,a.thumb,a.link,a.pubDate')
                                ->where('a.category = ' . $category->getId())
                                ->orderBy('a.pubDate', 'desc')
                                ->getQuery()->getResult();
            endif;
        endif;
        foreach ($_urlRss as $urlRss):
            $fetcher = new \AppKernel\Plugin\Rss\RssFetcher($urlRss);

            $articles = $fetcher->fetch();
            if ($articles):
                $this->em->createQueryBuilder()->delete(\AppEntity\AppFeeds::class, 'au')->where('au.category = ' . $category->getId())->getQuery()->execute();
                foreach ($articles as $counter => $article):
                    if ($modulus):
                        if ($floor == ($counter - 1)):
                            break; // mỗi url lấy đủ bài
                        endif;
                    else:
                        if ($floor == ($counter)):
                            break; // mỗi url lấy đủ bài
                        endif;
                    endif;
                    $article['content'] = $article['description'];
                    $article['description'] = html_entity_decode(strip_tags($article['description']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $article['pubDate'] = ($article['pubDate'] ?? false) ? new \DateTime($article['pubDate']) : null;
                    $article['updated'] = ($article['updated'] ?? false) ? new \DateTime($article['updated']) : null;
                    $article['category'] = $category;

                    $item = new \AppEntity\AppFeeds();
                    foreach ($article as $key => $value):
                        $field = 'set' . ucfirst($key);
                        if (method_exists($item, $field)):
                            $item->$field($value);
                        endif;
                    endforeach;
                    $this->em->persist($item);

                endforeach;
                if ($modulus):
                    $modulus--;
                endif;
            endif;
        endforeach;
        try {

            $now = new \DateTime();
            $blockconfig['lastUpdate'] = $now->format('Y-m-d H:i:s');
            $category->setAttr(json_encode($blockconfig));
            $this->em->persist($category);
            $this->em->flush();
        } catch (Exception $ex) {
            echo '<pre>';
            print_r($ex);
            echo '</pre>';
            exit;
        }
        return $this->em->getRepository(\AppEntity\AppFeeds::class)->createQueryBuilder('a')
                        ->select('a.id,a.title,a.description,a.content,a.image,a.thumb,a.link,a.pubDate')
                        ->where('a.category = ' . $category->getId())
                        ->orderBy('a.pubDate', 'desc')
                        ->getQuery()->getResult();
    }
}
