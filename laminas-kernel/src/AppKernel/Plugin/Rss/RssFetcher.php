<?php

namespace AppKernel\Plugin\Rss;

use Laminas\Feed\Reader\Reader;
use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Exception\RuntimeException;

class RssFetcher {

    private string $feedUrl;

    public function __construct(string $feedUrl) {
        $this->feedUrl = $feedUrl;
        // Khởi tạo Reader chỉ 1 lần
        Reader::registerExtension('DublinCore');
    }

    /**
     * Lấy danh sách bài viết từ RSS
     *
     * @return array
     */
    public function fetch(): array {
        $client = new Client($this->feedUrl, [
            'timeout' => 10,
            'adapter' => \Laminas\Http\Client\Adapter\Curl::class,
        ]);

        try {
            $response = $client->send();
            if (!$response->isSuccess()) :
                throw new \RuntimeException("Không fetch được RSS: " . $response->getStatusCode());
            endif;

            $feed = Reader::importString($response->getBody());

            $items = [];
            foreach ($feed as $entry) {
                $xml = $entry->saveXml();
                $dom = new \DOMDocument();
                $dom->loadXML($xml);
                $root = $dom->documentElement; // <item>
                $item = [];
                foreach ($root->childNodes as $node):

                    if ($node->nodeType === XML_ELEMENT_NODE):
                        if (!in_array($node->nodeName, ['atom:link'])):
                            $item[$node->nodeName] = $node->nodeValue;
                        endif;

                    endif;
                endforeach;
                if (!isset($item['image']) || !$item['image']):
                    $html = ($item['description'] ?? $item['content'] ?? '');
                    if ($html):
                        $doc = new \DOMDocument();
                        @$doc->loadHTML($html);
                        $tags = $doc->getElementsByTagName('img');

                        $src = null;
                        if ($tags->length > 0):
                            $src = $tags->item(0)->getAttribute('src');
                        endif;
                        $item['image'] = $src;
                    endif;
                endif;
                $items[] = $item;
            }
//            foreach ($feed as $entry) {
////                title,description,pubDate,updated,guid,atom:link,link,category,thumb,image
//                
//                
//                $dom = $entry->getDomDocument();
//                $root = $dom->documentElement;
//                $image = $root?->getElementsByTagName('thumb')?->item(0)??null;
//                
//                $items[] = [
//                    'title' => $entry->getTitle(),
//                    'link' => $entry->getLink(),
//                    'description' => $entry->getDescription(),
//                    'content' => $entry->getContent(), // nếu feed có <content:encoded>
//                    'date' => $entry->getDateCreated()?->format('Y-m-d H:i:s'),
//                    'authors' => $entry->getAuthor(), // mảng
//                    'thumb' => $image?->nodeValue??'',
//                ];
//            }

            return $items;
        } catch (RuntimeException $e) {
            return [];
//            throw new \RuntimeException("RSS fetch error: " . $e->getMessage());
        }
    }

    public function youtubeFetch(): array {
        try {
            $feed = simplexml_load_file($this->feedUrl);
            $items = [];
            foreach ($feed->entry as $entry):
                $item = [];
                $item['url'] = (string) $entry->link['href'];
                if (strpos($item['url'], '/shorts/') !== false):
                    continue; // Bỏ qua short
                endif;
                $item['ytId'] = (string) $entry->children('yt', true)->videoId;
                $item['image'] = "https://img.youtube.com/vi/{$item['ytId']}/hqdefault.jpg";
                $item['title'] = (string) $entry->title;
                $item['url'] = (string) $entry->link['href'];
                $item['published'] = new \DateTime((string) $entry->published);
                $items[] = $item;
            endforeach;

            return $items;
        } catch (RuntimeException $e) {
            return [];
//            throw new \RuntimeException("RSS fetch error: " . $e->getMessage());
        }
    }
}
