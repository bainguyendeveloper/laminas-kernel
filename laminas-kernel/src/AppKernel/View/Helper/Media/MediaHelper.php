<?php

namespace AppKernel\View\Helper\Media;

use Laminas\View\Helper\AbstractHelper;

class MediaHelper extends AbstractHelper {

    private $em;
    private $container;
    private $config;
    private $webpBrowserSupport;
    private $translator;

    public function __construct($em, $translator, $config, $container) {
        $this->em = $em;
        $this->translator = $translator;
        $this->config = $config;
        $this->container = $container;
        // refer at here https://caniuse.com/webp
        $this->webpBrowserSupport = [
            'Firefox' => 65,
            'Safari' => 14,
            'Chrome' => 9,
            'Edge' => 18,
            'IE' => 1000,
            'Opera' => 0,
            'Android' => 4,
            'UCBrowser' => 12,
            'Samsung' => 4,
            'QQBrowser' => 10.4,
            'Baidu' => 7.12,
            'KaiOS' => 1000,
            'Netscape' => 1000,
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

    public function getThumb($filename = '', $resizeOptions = ['width' => 400, 'height' => 300, 'extension' => '','quantity'=>100]) {
        if (!is_file(PATH_ROOT . $filename) && is_file(PATH_ROOT . DS . $filename)):
            $filename = DS . $filename;
        endif;
         $settings = $this->getSettings('media');
        if(!(bool)$settings['media']['towebp']):
            return $filename;
        endif;
        $sourceInfo = pathinfo($filename);
       

        $fileConfig = ["source" => $settings['media']['mediapath']];
        $fileManager = new \GuillermoMartinez\Filemanager\Filemanager($fileConfig);

        $path = $sourceInfo['basename'];
        $_mediaFolders = explode($settings['media']['mediapath'], $sourceInfo['dirname'], 2);
        $thumbFolder = ($_mediaFolders[1] ?? '');
//        $thumb = $f->firstThumb($file, $path);
        $resize = [$resizeOptions['width'] ?? 400, $resizeOptions['height'] ?? 300, true, true];
        $image_info = $fileManager->imageSizeName($path, $path, $resize);
        $_p = pathinfo($image_info['name']);
        if ($resizeOptions['extension'] && ($settings['media']['towebp']==1)):
            $extention = $resizeOptions['extension'];
            $image_info['name'] = "{$_p['filename']}.{$resizeOptions['extension']}";
        else:
            $extention = $_p['extension'];
        endif;
        $fullpaththumb = $fileManager->getFullPath() . DS . '_thumbs' . $thumbFolder . DS;
        if (file_exists($fullpaththumb . $image_info['name']) === false):
            set_error_handler(function ($errno, $errstr) {
                if (str_contains($errstr, 'Implicit conversion from float')) {
                    return true; // bỏ qua cảnh báo float -> int
                }
                return false; // xử lý các lỗi khác bình thường
            });
            \Gregwar\Image\Image::open(PATH_ROOT . $filename)->zoomCrop((int) $image_info['width'], (int) $image_info['height'])->save($fullpaththumb . $image_info['name'], $extention, $resizeOptions['quantity']??100);
            restore_error_handler();
        endif;
        $filename_new_first = $image_info['name'];
        return ($_mediaFolders[0] ?? DS) . $settings['media']['mediapath'] . DS . '_thumbs' . $thumbFolder . DS . $filename_new_first;
    }

    public function resize($file = []) {
        $towebp = !!($file['towebp'] ?? false);

        $settings = $this->getSettings('media');

        if ($towebp):
            $browser = $this->getBrowser();
            $bname = $browser['name'] ?? '';
            $bversion = $browser['version'] ?? '0';
            $towebp = (array_key_exists($bname, $this->webpBrowserSupport)) && version_compare($bversion, $this->webpBrowserSupport[$bname], '>');
        endif;
        if (isset($file['source'])):
            if (!is_file(PATH_ROOT . $file['source'])):
                return $file['source'];
            endif;
        else:
            return '';
        endif;
        $width = isset($file['width']) ? (float) $file['width'] : 0;
        if (!$width):
            $height = isset($file['height']) ? (float) $file['height'] : 0;
            if (!$height):
                return $file['source'];
            endif;
            $sourceInfo = pathinfo($file['source']);
            $_paths = explode($settings['media']['mediapath'], $sourceInfo['dirname'], 2);
            $thumbFolder = '';
            if (count($_paths) == 2):
                $thumbFolder = $_paths[0] . $settings['media']['mediapath'] . '/_thumbs' . $_paths[1];
            else:
                $thumbFolder = $settings['media']['mediapath'] . '/_thumbs' . $_paths[0];
            endif;
            if (!file_exists(PATH_ROOT . $thumbFolder)):
                mkdir(PATH_ROOT . $thumbFolder, 0755, true);
            endif;
            $resizedFile = $thumbFolder . '/resized_h' . $height . '_' . $sourceInfo['basename'];
            if (is_file(PATH_ROOT . $resizedFile)):
                if ($towebp):
                    $imagewebp = str_replace($ext, 'webp', $resizedFile);
                    if (is_file(PATH_ROOT . $imagewebp)):
                        return $imagewebp;
                    endif;
                    $ext = strtolower($sourceInfo['extension']);
                    if ($ext == 'png'):
                        $resizedImg = imagecreatefrompng(PATH_ROOT . $resizedFile);
                    elseif ($ext == 'jpg' || $ext == 'jpeg'):
                        $resizedImg = imagecreatefromjpeg(PATH_ROOT . $resizedFile);
                    elseif ($ext == 'bmp'):
                        $resizedImg = imagecreatefrombmp(PATH_ROOT . $resizedFile);
                    elseif ($ext == 'gif'):
                        $resizedImg = imagecreatefromgif(PATH_ROOT . $resizedFile);
                    endif;

// get dimens of image

                    $w = imagesx($resizedImg);
                    $h = imagesy($resizedImg);
// create a canvas
                    $im = imagecreatetruecolor($w, $h);
                    imageAlphaBlending($im, false);
                    imageSaveAlpha($im, true);
// By default, the canvas is black, so make it transparent
                    $trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
                    imagefilledrectangle($im, 0, 0, $w - 1, $h - 1, $trans);

// copy png to canvas

                    imagecopy($im, $resizedImg, 0, 0, 0, 0, $w, $h);
// lastly, save canvas as a webp

                    imagewebp($im, PATH_ROOT . $imagewebp);
// don
                    imagedestroy($im);
                    return $imagewebp;
                endif;
                return $resizedFile;
            else:
                $ext = strtolower($sourceInfo['extension']);
                if (!in_array($ext, ['png', 'jpeg', 'jpg', 'gif', 'bmp'])):
                    return '';
                endif;
                $imagine = new \Imagine\Gd\Imagine();

                $_img = $imagine->open(PATH_ROOT . $file['source']);
                $ow = $_img->getSize()->getWidth();
                $oh = $_img->getSize()->getHeight();
                $w = $h = 0;
                if ($height >= $oh): // maxheight > origin height
                    copy(PATH_ROOT . $file['source'], PATH_ROOT . $resizedFile);
                    return $resizedFile;
                else:
                    $h = $height; // height tam
                    $w = $ow * $h / $oh; // weight tam
                endif;
                //outbound=>crop, null=>resize
                $_img->resize(new \Imagine\Image\Box($w, $h))->save(PATH_ROOT . $resizedFile, array('jpeg_quality' => 100, 'png_compression_level' => 9));
                if ($towebp):
                    $imagewebp = str_replace($ext, 'webp', $resizedFile);
                    if (is_file(PATH_ROOT . $imagewebp)):
                        return $imagewebp;
                    endif;
                    if ($ext == 'png'):
                        $resizedImg = imagecreatefrompng(PATH_ROOT . $resizedFile);
                    elseif ($ext == 'jpg' || $ext == 'jpeg'):
                        $resizedImg = imagecreatefromjpeg(PATH_ROOT . $resizedFile);
                    elseif ($ext == 'bmp'):
                        $resizedImg = imagecreatefrombmp(PATH_ROOT . $resizedFile);
                    elseif ($ext == 'gif'):
                        $resizedImg = imagecreatefromgif(PATH_ROOT . $resizedFile);
                    endif;

// get dimens of image
//                        $w = imagesx($pngimg);
//                        $h = imagesy($pngimg);
// create a canvas
                    $im = imagecreatetruecolor($w, $h);
                    imageAlphaBlending($im, false);
                    imageSaveAlpha($im, true);
// By default, the canvas is black, so make it transparent
                    $trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
                    imagefilledrectangle($im, 0, 0, $w - 1, $h - 1, $trans);

// copy png to canvas

                    imagecopy($im, $resizedImg, 0, 0, 0, 0, $w, $h);
// lastly, save canvas as a webp
                    imagewebp($im, PATH_ROOT . $imagewebp);
// don
                    imagedestroy($im);

                    return $imagewebp;
                endif;
                return $resizedFile;
            endif;
        else:
            $sourceInfo = pathinfo($file['source']);
            $_paths = explode($settings['media']['mediapath'], $sourceInfo['dirname'], 2);
            $thumbFolder = '';
            if (count($_paths) == 2):
                $thumbFolder = $_paths[0] . $settings['media']['mediapath'] . '/_thumbs' . $_paths[1];
            else:
                $thumbFolder = $settings['media']['mediapath'] . '/_thumbs' . $_paths[0];
            endif;

            if (!file_exists(PATH_ROOT . $thumbFolder)):
                mkdir(PATH_ROOT . $thumbFolder, 0755, true);
            endif;
            $resizedFile = $thumbFolder . '/resized_w' . $width . '_' . $sourceInfo['basename'];
            if (is_file(PATH_ROOT . $resizedFile)):
                $ext = strtolower($sourceInfo['extension']);
                if ($towebp):
                    $imagewebp = str_replace($ext, 'webp', $resizedFile);
                    if (is_file(PATH_ROOT . $imagewebp)):
                        return $imagewebp;
                    endif;
                    try {
                        if ($ext == 'png'):
                            $resizedImg = imagecreatefrompng(PATH_ROOT . $resizedFile);
                        elseif ($ext == 'jpg' || $ext == 'jpeg'):
                            $resizedImg = imagecreatefromjpeg(PATH_ROOT . $resizedFile);
                        elseif ($ext == 'bmp'):
                            $resizedImg = imagecreatefrombmp(PATH_ROOT . $resizedFile);
                        elseif ($ext == 'gif'):
                            $resizedImg = imagecreatefromgif(PATH_ROOT . $resizedFile);
                        endif;
                    } catch (\Imagine\Utils\ErrorHandling $ex) {
                        echo '<pre>';
                        print_r('21');
                        echo '</pre>';
                        exit;
                    }


// get dimens of image

                    $w = imagesx($resizedImg);
                    $h = imagesy($resizedImg);
// create a canvas
                    $im = imagecreatetruecolor($w, $h);
                    imageAlphaBlending($im, false);
                    imageSaveAlpha($im, true);
// By default, the canvas is black, so make it transparent
                    $trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
                    imagefilledrectangle($im, 0, 0, $w - 1, $h - 1, $trans);

// copy png to canvas

                    imagecopy($im, $resizedImg, 0, 0, 0, 0, $w, $h);
// lastly, save canvas as a webp
                    imagewebp($im, PATH_ROOT . $imagewebp);
// don
                    imagedestroy($im);
                    return $imagewebp;
                endif;
                return $resizedFile;
            else:
                $ext = strtolower($sourceInfo['extension']);
                if (!in_array($ext, ['png', 'jpeg', 'jpg', 'gif', 'bmp'])):
                    return $file['source'];
                endif;
                $imagine = new \Imagine\Gd\Imagine();
                $_img = $imagine->open(PATH_ROOT . $file['source']);
                $ow = $_img->getSize()->getWidth();
                $oh = $_img->getSize()->getHeight();
                $w = $h = 0;
                if ($width >= $ow): // maxwidth > or width
                    copy(PATH_ROOT . $file['source'], PATH_ROOT . $resizedFile);
                    return $resizedFile;
                else:
                    $w = $width; // width tam
                    $h = $oh * $w / $ow; // chieu cao tam
                endif;
                //outbound=>crop, null=>resize
                try {

                    $_img->resize(new \Imagine\Image\Box($w, $h))->save(PATH_ROOT . $resizedFile, array('jpeg_quality' => 100, 'png_compression_level' => 9));
                } catch (\Imagine\Utils\ErrorHandling $ex) {
                    echo '<pre>';
                    print_r($ex->getMessage());
                    echo '</pre>';
                    exit;
                } catch (\Imagine\Exception\RuntimeException $ex) {
                    echo '<pre>';
                    print_r($ex->getMessage());
                    echo '</pre>';
                    exit;
                }

                if ($towebp):
                    $imagewebp = str_replace($ext, 'webp', $resizedFile);
                    if (is_file(PATH_ROOT . $imagewebp)):
                        return $imagewebp;
                    endif;
                    if ($ext == 'png'):
                        $resizedImg = imagecreatefrompng(PATH_ROOT . $resizedFile);
                    elseif ($ext == 'jpg' || $ext == 'jpeg'):
                        $resizedImg = imagecreatefromjpeg(PATH_ROOT . $resizedFile);
                    elseif ($ext == 'bmp'):
                        $resizedImg = imagecreatefrombmp(PATH_ROOT . $resizedFile);
                    elseif ($ext == 'gif'):
                        $resizedImg = imagecreatefromgif(PATH_ROOT . $resizedFile);
                    endif;

// get dimens of image
//                        $w = imagesx($pngimg);
//                        $h = imagesy($pngimg);
// create a canvas
                    $im = imagecreatetruecolor($w, $h);
                    imageAlphaBlending($im, false);
                    imageSaveAlpha($im, true);
// By default, the canvas is black, so make it transparent
                    $trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
                    imagefilledrectangle($im, 0, 0, $w - 1, $h - 1, $trans);

// copy png to canvas

                    imagecopy($im, $resizedImg, 0, 0, 0, 0, $w, $h);
// lastly, save canvas as a webp
                    imagewebp($im, PATH_ROOT . $imagewebp);
// don
                    imagedestroy($im);
                    return $imagewebp;
                endif;
                return $resizedFile;
            endif;

        endif;
    }

    public function getBrowser() {
        $u_agent = $_SERVER['HTTP_USER_AGENT'];
        $bname = 'Unknown';
        $platform = 'Unknown';
        $version = "";

        //First get the platform?
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'linux';
        } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'mac';
        } elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'windows';
        }
        $ub = '';
        // Next get the name of the useragent yes seperately and for good reason
        if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
            $bname = 'IE';
            $ub = "IE";
        } elseif (preg_match('/Edge/i', $u_agent)) {
            $bname = 'Edge';
            $ub = "Edge";
        } elseif (preg_match('/Firefox/i', $u_agent)) {
            $bname = 'Firefox';
            $ub = "Firefox";
        } elseif (preg_match('/Chrome/i', $u_agent)) {
            $bname = 'Chrome';
            $ub = "Chrome";
        } elseif (preg_match('/Safari/i', $u_agent)) {
            $bname = 'Safari';
            $ub = "Safari";
        } elseif (preg_match('/Opera/i', $u_agent)) {
            $bname = 'Opera';
            $ub = "Opera";
        } elseif (preg_match('/Android/i', $u_agent)) {
            $bname = 'Android';
            $ub = "Android";
        } elseif (preg_match('/Samsung/i', $u_agent)) {
            $bname = 'Samsung';
            $ub = "Samsung";
        } elseif (preg_match('/QQBrowser/i', $u_agent)) {
            $bname = 'QQBrowser';
            $ub = "QQBrowser";
        } elseif (preg_match('/Baidu/i', $u_agent)) {
            $bname = 'Baidu';
            $ub = "Baidu";
        } elseif (preg_match('/KaiOS/i', $u_agent)) {
            $bname = 'KaiOS';
            $ub = "KaiOS";
        } elseif (preg_match('/UCBrowser/i', $u_agent)) {
            $bname = 'UCBrowser';
            $ub = "UCBrowser";
        } elseif (preg_match('/Netscape/i', $u_agent)) {
            $bname = 'Netscape';
            $ub = "Netscape";
        }

        // finally get the correct version number
        $known = array('Version', $ub, 'other');
        $pattern = '#(?<browser>' . join('|', $known) .
                ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $u_agent, $matches)) {
            // we have no matching number just continue
        }

        // see how many we have
        $i = count($matches['browser']);
        if ($i != 1) {
            //we will have two since we are not using 'other' argument yet
            //see if version is before or after the name
            if (strripos($u_agent, "Version") < strripos($u_agent, $ub)) {
                $version = $matches['version'][0];
            } else {
                $version = $matches['version'][1];
            }
        } else {
            $version = $matches['version'][0];
        }

        // check if we have a number
        if ($version == null || $version == "") {
            $version = "?";
        }

        return array(
            'userAgent' => $u_agent,
            'name' => $bname,
            'version' => $version,
            'platform' => $platform,
            'pattern' => $pattern
        );
    }
}
