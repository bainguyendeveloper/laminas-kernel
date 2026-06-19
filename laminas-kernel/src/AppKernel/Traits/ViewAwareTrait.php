<?php

namespace AppKernel\Traits;

use Laminas\View\Model\ViewModel;

trait ViewAwareTrait {

    public function replaceLinkFile($content) {
        if (!$content || !is_string($content)):
            return '';
        endif;
        $uri = $this->request->getUri();
        $scheme = $uri->getScheme();
        $host = $uri->getHost();
        $replacement = 'src="/filemanager/userfiles';
        $replacementlink = 'href="/';
        $patterns = [
            '/src=\"\.\.\/\.\.\/\.\.\/filemanager\/userfiles/',
            '/src=\"\.\.\/\.\.\/filemanager\/userfiles/',
            '/src=\"\.\.\/filemanager\/userfiles/',
            '/src=\"\/filemanager\/userfiles/',
            '/src=\"filemanager\/userfiles/',
            sprintf('/src=\"%s:\/\/%s\/filemanager\/userfiles/', $scheme, $host),
            sprintf('/href=\"%s:\/\/%s\//', $scheme, $host),
        ];
        $replacements = [
            $replacement,
            $replacement,
            $replacement,
            $replacement,
            $replacement,
            $replacement,
            $replacementlink
        ];
        return preg_replace($patterns, $replacements, $content);
    }
    public function getYoutubeId($url) {
        // Here is a sample of the URLs this regex matches: (there can be more content after the given URL that will be ignored)
// http://youtu.be/dQw4w9WgXcQ
// http://www.youtube.com/embed/dQw4w9WgXcQ
// http://www.youtube.com/watch?v=dQw4w9WgXcQ
// http://www.youtube.com/?v=dQw4w9WgXcQ
// http://www.youtube.com/v/dQw4w9WgXcQ
// http://www.youtube.com/e/dQw4w9WgXcQ
// http://www.youtube.com/user/username#p/u/11/dQw4w9WgXcQ
// http://www.youtube.com/sandalsResorts#p/c/54B8C800269D7C1B/0/dQw4w9WgXcQ
// http://www.youtube.com/watch?feature=player_embedded&v=dQw4w9WgXcQ
// http://www.youtube.com/?feature=player_embedded&v=dQw4w9WgXcQ
// It also works on the youtube-nocookie.com URL with the same above options.
// It will also pull the ID from the URL in an embed code (both iframe and object tags)
        $match = [];
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
        if (!$match):
            $youtubeRegExp = '/http(?:s?):\/\/(?:m\.|www\.)?(?:m\.)?youtu(?:be\.com\/(?:watch\?v=|live\/|embed\/|shorts\/)|\.be\/)([\w\-\_]*)(&(amp;)?[\w\?\=]*)?/';
            preg_match($youtubeRegExp, $url, $match);

        endif;
        if (!$match):
            return [];
        endif;
        $settings = $this->getSettings();
        $youtube_video_id = $match[1];
        $api_key = $settings['general']['gmapkey']; //'AIzaSyCFf7gHA22H3Rgx-uwzyb1eNjwipKHeUe8';
        
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://www.googleapis.com/youtube/v3/videos?key=' . $api_key . '&part=snippet&id=' . $youtube_video_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        $_response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($_response, true);
        if ($response && ($response['items']??false)):
            $item = $response['items'][0];
            $thumbs = isset($item['snippet']['thumbnails']) ? $item['snippet']['thumbnails'] : [];
            $thumb = isset($thumbs['high']) ? $thumbs['high'] : (isset($thumbs['standard']) ? $thumbs['standard'] : $thumbs['default']);
//            $statistics = isset($item['statistics']) ? $item['statistics'] : [];
//            $posted = date("Y-m-d H:i:s",$item['snippet']['publishedAt']);
//            $statistics['posted'] = $posted;
            return [
                'youtubeId' => $youtube_video_id,
                'youtubeThumbnail' => $thumb['url'],
                'youtubeRatio' => round($thumb['height'] / $thumb['width'], 2),
                'title' =>$item['snippet']['title']??'',
                'description' =>$item['snippet']['description']??''
//                'statistics' => json_encode($statistics),
            ];
        endif;
        return [];
    }

    public function getStatisticsYoutubeByPosts($posts = []) {
        if (!$posts):
            return $posts;
        endif;
        $ids = [];
        foreach ($posts as $postId => $post):
            if ($post['youtubeId']):
                $ids[] = $post['youtubeId'];
            endif;

        endforeach;
        $counter = count($ids);
        if (!$counter || $counter > 50):
            return $posts;
        endif;
         $settings = $this->getSettings();
        $api_key = $settings['general']['gmapkey']; //'AIzaSyCFf7gHA22H3Rgx-uwzyb1eNjwipKHeUe8';
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://www.googleapis.com/youtube/v3/videos?key=' . $api_key . '&part=statistics&id=' . implode(',', $ids),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        $_response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($_response, true);
        if ($response && $response['items']):
            $_items = [];
            foreach ($response['items'] as $item):
                $_items[$item['id']] = $item['statistics'];
            endforeach;
            foreach ($posts as &$post):
                if ($post['youtubeId'] && isset($_items[$post['youtubeId']])):
                    $post['statistics'] = $_items[$post['youtubeId']];
                endif;
            endforeach;
            return $posts;
        endif;
        return $posts;
    }

    

   
}
