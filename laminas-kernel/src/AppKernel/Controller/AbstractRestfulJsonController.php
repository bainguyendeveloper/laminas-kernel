<?php

namespace AppKernel\Controller;

use Laminas\Mail\Message;
use Laminas\Mail\Protocol\Exception\RuntimeException as MailException;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part as MimePart;
use Laminas\Mvc\Controller\AbstractRestfulController;

/**
 * 400 BAD REQUEST: The request was invalid or cannot be otherwise served. An accompanying error message will explain further. For security reasons, requests without authentication are considered invalid and will yield this response.
 * 401 UNAUTHORIZED: The authentication credentials are missing, or if supplied are not valid or not sufficient to access the resource.
 * 403 FORBIDDEN: The request has been refused. See the accompanying message for the specific reason (most likely for exceeding rate limit).
 * 404 NOT FOUND: The URI requested is invalid or the resource requested does not exists.
 * 406 NOT ACCEPTABLE: The request specified an invalid format.
 * 410 GONE: This resource is gone. Used to indicate that an API endpoint has been turned off.
 * 429 TOO MANY REQUESTS: Returned when a request cannot be served due to the application’s rate limit having been exhausted for the resource.
 * 500 INTERNAL SERVER ERROR: Something is horribly wrong.
 * 502 BAD GATEWAY: The service is down or being upgraded. Try again later.
 * 503 SERVICE UNAVAILABLE: The service is up, but overloaded with requests. Try again later.
 * 504 GATEWAY TIMEOUT: Servers are up, but the request couldn’t be serviced due to some failure within our stack. Try again later.
 */
class AbstractRestfulJsonController extends AbstractRestfulController {

    protected function methodNotAllowed() {
        $this->response->setStatusCode(405);
        throw new \Exception('Method Not Allowed');
    }

    protected function accessDenined() {
        $this->response->setStatusCode(401);
        throw new \Exception('Connect refused');
    }

    public function __construct() {
        
    }
   
    # Override default actions as they do not return valid JsonModels

    public function create($data = null) {
        return $this->methodNotAllowed();
    }

    public function delete($id = null) {
        return $this->methodNotAllowed();
    }

    public function get($id = null) {
        return $this->methodNotAllowed();
    }

    public function getList() {
        return $this->methodNotAllowed();
    }

    public function head($id = null) {
        return $this->methodNotAllowed();
    }

    public function options() {
        return $this->methodNotAllowed();
    }

    public function patch($id = null, $data = null) {
        return $this->methodNotAllowed();
    }

    public function replaceList($data = null) {
        return $this->methodNotAllowed();
    }

    public function patchList($data = null) {
        return $this->methodNotAllowed();
    }

    public function update($id = null, $data = null) {
        return $this->methodNotAllowed();
    }

    public function checkHeader() {

        $settings = $this->getSettings('all');
        $header = $this->request->getHeaders()->toArray();
        $authorization = $header['Authorization'] ?? '';
        $token = str_replace('Bearer ', '', $authorization);
        if (!$token || !$settings):
            return $this->accessDenined();
        endif;
        $data = $this->decodeAccessToken($token);
        if ($data):
            $iss = $data['iss'];
            if (!in_array($iss, ['freelancer', 'minasoft.vn'])):
                return $this->accessDenined();
            endif;
        endif;
        $data['token'] = $token;
        return $data;
    }

    public function getRequestDatas($checkHeader = false, $withheader = false) {
        $tokenData = null;
        if ($checkHeader):
            $tokenData = $this->checkHeader();
        endif;
        $method = strtolower($this->request->getMethod());
        $data = [];
        $_header = $this->request->getHeaders()->toArray();
        $header = array_change_key_case($_header, CASE_LOWER);
        $contentType = $header['content-type'] ?? '';
        switch ($method):
            case 'get':
                $data = $this->request->getQuery()->toArray();
                break;
            case 'post':


                if ((strpos($contentType, 'application/json') !== false) || (strpos($contentType, 'text/plain') !== false)):
                    $contentData = $this->request->getContent();
                    try {
                        $data = json_decode($contentData, true);
                    } catch (\Exception $exc) {
                        unset($exc);
                        $data = [];
                    }
                else:
                    $data = $this->request->getPost()->toArray();
                endif;
                if ($header['debug'] ?? ''):
                    echo '<pre>';
                    print_r($header);
                    echo '</pre>';
                    exit;
                endif;
                break;
            default:
                
                $contentData = $this->request->getContent();
                try {
                    $data = json_decode($contentData, true);
                } catch (\Exception $exc) {
                    unset($exc);
                    $data = $this->request->getPost()->toArray();
                }

                break;
        endswitch;

        return ($withheader) ? [$data, $tokenData] : $data;
    }
    public function apcuStore(string $key, $value,int $time=600 ){
        if(function_exists('apcu_store')):
            apcu_store($key, $value, $time);
            return true;
        else:
            return false;
        endif;
    }
    public function apcuFetch(string $key ){
        if(function_exists('apcu_fetch')):
            $success = false;
            $value= apcu_fetch($key,$success);
            if($success):
                return $value;
            else:
                return null;
            endif;
        else:
            return null;
        endif;
    }
    public function display($val = []) {
        if (!isset($val['code'])):
            $val['code'] = 200;
        endif;
        if (!isset($val['msg'])):
            $val['msg'] = '';
        endif;
        $this->response->setStatusCode($val['code']);
//        $val['method'] = __METHOD__;
        return new \Laminas\View\Model\JsonModel($val);
    }

    public function flush() {
        try {
            $this->em->flush();
            return true;
        } catch (\Doctrine\Common\DataFixtures\Exception\CircularReferenceException $exc) {
            echo '<pre>';
            print_r('CircularReferenceException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\Common\Annotations\AnnotationException $exc) {
            echo '<pre>';
            print_r('AnnotationException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\Common\CommonException $exc) {
            echo '<pre>';
            print_r('CommonException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\Common\Persistence\Mapping\MappingException $exc) {
            echo '<pre>';
            print_r('MappingException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            print_r('<hr/><br>');
            echo '</pre>';
            exit;
        } catch (\Doctrine\ORM\TransactionRequiredException $exc) {
            echo '<pre>';
            print_r('TransactionRequiredException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\ORM\Query\QueryException $exc) {
            echo '<pre>';
            print_r('QueryException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\ORM\Query\AST\ASTException $exc) {
            echo '<pre>';
            print_r('ASTException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\ORM\Mapping\MappingException $exc) {
            echo '<pre>';
            print_r('MappingException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\ORM\UnexpectedResultException $exc) {
            echo '<pre>';
            print_r('UnexpectedResultException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\ORM\PessimisticLockException $exc) {
            echo '<pre>';
            print_r('PessimisticLockException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\ORM\OptimisticLockException $exc) {
            echo '<pre>';
            print_r('OptimisticLockException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\ORM\Internal\Hydration\HydrationException $exc) {
            echo '<pre>';
            print_r('HydrationException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\ORM\ORMException $exc) {
            echo '<pre>';
            print_r('ORMException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\ORM\NonUniqueResultException $exc) {
            echo '<pre>';
            print_r('NonUniqueResultException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\ORM\EntityNotFoundException $exc) {
            echo '<pre>';
            print_r('EntityNotFoundException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\ORM\Tools\ToolsException $exc) {
            echo '<pre>';
            print_r('ToolsException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\ORM\Tools\Export\ExportException $exc) {
            echo '<pre>';
            print_r('ExportException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\ORM\Proxy\ProxyException $exc) {
            echo '<pre>';
            print_r('ProxyException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\ORM\NoResultException $exc) {
            echo '<pre>';
            print_r('NoResultException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\ORM\ORMInvalidArgumentException $exc) {
            echo '<pre>';
            print_r('ORMInvalidArgumentException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\DBAL\Query\QueryException $exc) {
            echo '<pre>';
            print_r('QueryException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\DBAL\Types\ConversionException $exc) {
            echo '<pre>';
            print_r('ConversionException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\DBAL\Schema\SchemaException $exc) {
            echo '<pre>';
            print_r('SchemaException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\DBAL\Sharding\ShardingException $exc) {
            echo '<pre>';
            print_r('ShardingException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\DBAL\ConnectionException $exc) {
            echo '<pre>';
            print_r('ConnectionException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\DBAL\Cache\CacheException $exc) {
            echo '<pre>';
            print_r('CacheException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\DBAL\Driver\OCI8\OCI8Exception $exc) {
            echo '<pre>';
            print_r('OCI8Exception');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\DBAL\Driver\SQLSrv\SQLSrvException $exc) {
            echo '<pre>';
            print_r('SQLSrvException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\DBAL\Driver\Mysqli\MysqliException $exc) {
            echo '<pre>';
            print_r('MysqliException');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\DBAL\Driver\IBMDB2\DB2Exception $exc) {
            echo '<pre>';
            print_r('DB2Exception');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        } catch (\Doctrine\DBAL\DBALException $exc) {
            echo '<pre>';
            print_r('DBALException');
            print_r('<hr/><br>');
            print_r($exc->getFile() . '(' . $exc->getLine() . ')');
            print_r($exc->getMessage() . ')');
            print_r('<hr/><br>');
            print_r($exc->getTraceAsString());
            echo '</pre>';
            exit;
        }
    }

    /**
     * @desc get setting values in database
     * @param type string $type
     */
    public function getSettings($type = 'general') {
        $settings = $this->config['settings'];
        if ($type == 'all'):
            $settings['general'] = $settings['general' . CURRENT_SYSTEM] ?? $settings['general'] ?? [];
            unset($settings['general' . CURRENT_SYSTEM]);
            $settings['media'] = $settings['media' . CURRENT_SYSTEM] ?? $settings['media'] ?? [];
            unset($settings['media' . CURRENT_SYSTEM]);
            unset($settings['email']);
            unset($settings['customer_banner_cate']);
            unset($settings['pathpdf']);
            unset($settings['page-showtypes']);
            unset($settings['post-statuses']);
            unset($settings['post-features']);
            unset($settings['banner-templates']);
            unset($settings['block-templates']);
            unset($settings['page-templates']);
            unset($settings['colors']);
            unset($settings['source-systems']);
            unset($settings['sitemap-types']);
            unset($settings['pathmysqldump']);
            unset($settings['languages']);
            unset($settings['medianongsan']);
            unset($settings['security']);
            unset($settings['social']);
            unset($settings['themes']);
            return $settings;
        elseif ($settings[$type . CURRENT_SYSTEM] ?? $settings[$type]):
            return [$type => $settings[$type . CURRENT_SYSTEM] ?? $settings[$type]];
        else:
            return [];
        endif;
    }

    public function sendEmail($info = array(), $template = '', $emailto = '', $subject = '') {
        if (!filter_var($emailto, FILTER_VALIDATE_EMAIL)):
            // khong phai email thi ko gui nua
            return false;
        endif;
        $loggerManager = new \AppKernel\Service\LoggerManager('sendemail/' . __METHOD__);
        $logger = $loggerManager->baseLogger;
        $config = $this->config['settings']['email'];
//        $pathLog = realpath(__DIR__ . '/../../../../../data/logs');
//        $sendEmailPath = $pathLog . '/' . date('d_m_Y') . '/sendemail';
//        if (!file_exists($sendEmailPath)):
//            mkdir($sendEmailPath, 0755, true);
//        endif;

        $settings = $this->getSettings('all');
        $default_bcc = ($settings['general']['default_bcc']) ? explode(',', $settings['general']['default_bcc']) : [];
        if (isset($settings['general']['sendemail']) && $settings['general']['sendemail'] == 2):// testing
            if ($default_bcc):
                $emailto = $default_bcc;
                if (is_array($emailto)):
                    $emailto = $emailto[0];
                endif;
                if (!filter_var($emailto, FILTER_VALIDATE_EMAIL)) :
                    return false;
                endif;
                $default_bcc = [];
            else:
                return false;
            endif;

        endif;
        $message = new Message();
        $message->addTo($emailto)
                ->addBcc($default_bcc)
                ->addFrom($settings['general']['default_from'])
                ->setSubject($subject . ' - ' . CURRENT_DOMAIN);
        $transport = new SmtpTransport();
        $smtpoption = $config['config'];
        $smtpoption['host'] = $settings['general']['smtp_host'];
        $smtpoption['connection_config']['username'] = $settings['general']['smtp_username'];
        $smtpoption['connection_config']['password'] = $settings['general']['smtp_password'];
        $smtpoption['connection_config']['ssl'] = $settings['general']['smtp_type'];
        $smtpoption['port'] = $settings['general']['smtp_port'];

        $options = new SmtpOptions($smtpoption);
        $renderer = $this->container->get('ViewRenderer');
        $basePath = realpath(PATH_ROOT . '/themes/frontend/default/_partials');
        $basePathDomain = realpath(PATH_ROOT . '/themes/frontend/' . FOLDER_DOMAIN . '/_partials');
        $resolver = new \Laminas\View\Resolver\TemplatePathStack();
        $resolver->addPath($basePath);
        $resolver->addPath($basePathDomain);
        $renderer->setResolver($resolver);
        $info['sitename'] = $settings['general']['sitename'];
        $content = $renderer->render($template, array('info' => $info));
        $html = new MimePart($content);
        $html->type = "text/html; charset = UTF-8";
        $body = new MimeMessage();
        $body->addPart($html);
        $message->setBody($body);
        $transport->setOptions($options);

        try {
            $transport->send($message);
//            $logger = new \Laminas\Log\Logger;
//            $writer = new \Laminas\Log\Writer\Stream($sendEmailPath . '/info.log');
//            $logger->addWriter($writer);
            $logger->info(
                    sprintf(
                            "Subject: %s \nEmail:%s\n----------------------------------------------------------------------\n", $subject, $emailto
                    )
            );
            return true;
        } catch (MailException $exception) {
            $logger->crit(
                    sprintf(
                            "%s:%d %s (%d) [%s] \n %s \n----------------------------------------------------------------------\n", $exception->getFile(), $exception->getLine(), $exception->getMessage(), $exception->getCode(), get_class($exception), $exception->getTraceAsString()
                    )
            );
            return false;
        }
    }

    public function uploadImage($imageData = '', $folder = '', $settings = [], $addwatermask = true, $watermasktext = '') {
        if (!$settings):
            $settings = $this->getSettings('media');
        endif;
        $path = PATH_ROOT . '/' . $settings['media']['mediapath'] . '/' . $folder . '/';
        if (!file_exists($path)):
            @mkdir($path, 0755, true);
        endif;
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)):
            $data = substr($imageData, strpos($imageData, ',') + 1);
            $ext = strtolower($type[1]); // jpg, png, gif


            if (!in_array($ext, explode(',', $settings['media']['allow']))):
                return $this->display(
                                [
                                    'code' => 201,
                                    'msg' => 'Không thể upload file. Vui lòng upload file ' . $settings['media']['allow']
                                ]
                        );
            endif;
            $data = base64_decode($data);
            if ($data === false):
                return $this->display(
                                [
                                    'code' => 201,
                                    'msg' => 'File không đúng'
                                ]
                        );
            else:
                $fileName = md5(time() . $folder) . '.' . $ext;
                file_put_contents($path . $fileName, $data);

                // add watermask
//                if ($addwatermask):
//                    if ($ext == 'png'):
//                        $jpg_image = imagecreatefromstring($data);
//                        // Allocate A Color For The Text
//                        $white = imagecolorallocate($jpg_image, 255, 255, 255);
//
//                        // Set Path to Font File
//                        $font_path = PATH_ROOT . '/themes/webapp/default/assets/fonts/Montserrat-Regular.ttf';
//
//                        
//
//                        // Set Text to Be Printed On Image
//                         $text = ($watermasktext)?:date('d-m-y H:i:s');
//                        $height = imagesy($jpg_image);
//                        // Print Text On Image
//                        imagettftext($jpg_image, 18, 0, 30, $height - 30, $white, $font_path, $text);
//
//                        // Send Image to Browser
//                        imagepng($jpg_image, $path . $fileName);
//
//                        // Clear Memory
//                        imagedestroy($jpg_image);
//                    else:
//                        $jpg_image = imagecreatefromstring($path . $fileName);
//                        // Allocate A Color For The Text
//                        $white = imagecolorallocate($jpg_image, 255, 255, 255);
//
//                        // Set Path to Font File
//                        $font_path = PATH_ROOT . '/themes/webapp/default/assets/fonts/Montserrat-Regular.ttf';
//
//
//                        // Set Text to Be Printed On Image
//                        $text = ($watermasktext)?:date('d-m-y H:i:s');
//                        $height = imagesy($jpg_image);
//                        // Print Text On Image
//                        imagettftext($jpg_image, 18, 0, 30,$height - 30, $white, $font_path, $text);
//
//                        // Send Image to Browser
//                        imagecreatefromjpeg($jpg_image, $path . $fileName);
//
//                        // Clear Memory
//                        imagedestroy($jpg_image);
//                    endif;
//                endif;
                //
                return $settings['media']['mediapath'] . DS . ($folder ? $folder . DS : '') . $fileName;
            endif;
        else:
            return $this->display(
                            [
                                'code' => 201,
                                'msg' => 'File không đúng'
                            ]
                    );
        endif;
    }

    public function uploadFile($item = null, $size = [], $allowed = [], $autodelete = true, $folders = [], $fileNames = [], $textwatermask = '', $createdBy = null) {
        if (!$item):
            return false;
        endif;
        $adapter = new \Laminas\File\Transfer\Adapter\Http();
        $_files = $adapter->getFileInfo();
        if (!empty($_files)):
            $mediaSettings = $this->getSettings('all');
            if (!$mediaSettings || !$mediaSettings['media']['allow'] || !$mediaSettings['media']['mediapath']):
                $this->flashMessenger()->addErrorMessage('Please configurate media first');
                false;
            endif;
            if (!$allowed):
                $_allowed = (isset($mediaSettings['media']['allow']) && $mediaSettings['media']['allow']) ? explode(',', $mediaSettings['media']['allow']) : ['jpg', 'jpeg', 'png'];
                $allowed = array_map('strtolower', $_allowed);
            endif;
            $mediaPath = (isset($mediaSettings['media']['mediapath']) && $mediaSettings['media']['mediapath']) ? $mediaSettings['media']['mediapath'] : 'uploads';
            $dist = PATH_ROOT . DS . $mediaPath;
            $itemid = $item->getId();
            $now = new \DateTime();
            $dateFolder = $now->format('Y-m-d');
            foreach ($_files as $key => $files):
                $getFunction = 'get' . ucfirst($key);
                $context = str_replace('DoctrineORMModule\\Proxy\\__CG__\\', '', get_class($item));
                $context = strtolower(str_replace('AppEntity\\', '', $context)) . '_' . $key;
                $_folder = (isset($folders[$key]) && $folders[$key]) ? $folders[$key] : $context . '/' . $dateFolder . '/' . $itemid;
                $folder = strtolower($_folder);
                if ($files['error'] == 0):
                    $ext = pathinfo($files['name'], PATHINFO_EXTENSION);
                    $fileName = (isset($fileNames[$key]) && $fileNames[$key]) ? $fileNames[$key] . '.' . $ext : $this->makeSafe($files['name']) . '_' . time() . '.' . $ext;
                    if (!in_array(strtolower($ext), $allowed)):
                        return false;
                    endif;
                    $file = sprintf('%s/%s', $folder, $fileName);
                    $path = $dist . DS . $folder;
                    if (!file_exists($path)):
                        mkdir($path, 0755, true);
                    endif;
                    $adapter->addFilter('Rename', array(
                        'target' => $dist . DS . $file,
                        'overwrite' => true), $files['name']
                    );

                    if (isset($mediaSettings[$context])):
                        list($width, $height) = explode('x', $mediaSettings[$context], 2);
                        if ((int) $width <= 0):
                            $width = 300;
                        else:
                            $width = (int) $width;
                        endif;
                        if ((int) $height <= 0):
                            $height = 300;
                        else:
                            $height = (int) $height;
                        endif;
                    elseif (isset($size[$key])):
                        $width = $size[$key][0];
                        $height = $size[$key][1];
                    endif;

                    if ($adapter->receive($files['name'])):

                        if (($image = $item->$getFunction()) && $autodelete):
                            $oldFile = $path . DS . $image;
                            if (is_file($oldFile)):
                                unlink($oldFile);
                            endif;
                        endif;
                        if (in_array($ext, ['jpg', 'jpeg', 'png'])):
                            $imagine = new \Imagine\Gd\Imagine();

                            try {
                                $_img = $imagine->open($dist . DS . $file);
                                $ow = $_img->getSize()->getWidth();
                                $oh = $_img->getSize()->getHeight();
                                if (isset($width) && isset($height)):
                                    $contextsetting = ['width' => $width, 'height' => $height];

                                    $w = $h = 0;
                                    if ($contextsetting['width'] >= $ow): // maxwidth > or width
                                        if ($oh <= $contextsetting['height']): // neu nho hon w va h thi giu nguyen kich thuoc
                                            $w = $ow;
                                            $h = $oh;
                                        else: // neu height lon hon width nho hon thi resize theo height
                                            $h = $contextsetting['height'];
                                            $w = $ow * $h / $oh;
                                        endif;
                                    else:
                                        $tmpW = $contextsetting['width']; // width tam
                                        $tmpH = $oh * $tmpW / $ow; // chieu cao tam
                                        if ($tmpH <= $contextsetting['height']): // neu chieu cao tam nho hon chieu cao max => chon chieu cao tam va width = width max
                                            $h = $tmpH;
                                            $w = $contextsetting['width'];
                                        else://neu chieu cao tam lon hon chieu cao max => chon chieu cao = chieu cao max va kich thuoc = kich thuoc tinh toan lai
                                            $h = $contextsetting['height'];
                                            $w = $ow * $h / $oh;
                                        endif;
                                    endif;
                                    //outbound=>crop, null=>resize
                                    $_img->resize(new \Imagine\Image\Box($w, $h))->save($dist . DS . $file, array('jpeg_quality' => 100, 'png_compression_level' => 9));
                                    if ($textwatermask):
                                        $jpg_image = imagecreatefromstring(file_get_contents($dist . DS . $file));

                                        // Allocate A Color For The Text
                                        $white = imagecolorallocate($jpg_image, 60, 60, 60);
                                        $whitetran = imagecolorallocatealpha($jpg_image, 255, 255, 255, 10);
                                        $bgc = imagecolorallocatealpha($jpg_image, 220, 220, 220, 50);

                                        $height = imagesy($jpg_image);

                                        // Set Path to Font File
                                        $font_path = PATH_ROOT . '/themes/webapp/default/assets/fonts/Montserrat-Regular.ttf';

                                        // Set Text to Be Printed On Image
                                        $text = $textwatermask;
                                        $fontsize = (int) $ow / strlen($text) + 2;
                                        $_bgheight = $height - $fontsize - 20;
//                                        imagefilledrectangle($jpg_image, 0, $height, $w, $_bgheight, $bgc);
                                        // Print Text On Image
                                        $bbox = imagettfbbox($fontsize, 0, $font_path, $textwatermask);
                                        $center1 = (imagesx($jpg_image) / 2) - (($bbox[2] - $bbox[0]) / 2);
//                                        imagettftext($jpg_image, $fontsize, 0, 10, $height - 10, $white, $font_path, $text);
                                        imagettftext($jpg_image, $fontsize, 0, $center1, $height / 3, $whitetran, $font_path, $text);

                                        // Send Image to Browser
                                        imagepng($jpg_image, $dist . DS . $file);

                                        // Clear Memory
                                        imagedestroy($jpg_image);

                                    endif;
                                else:
                                    if ($textwatermask):
                                        $jpg_image = imagecreatefromstring(file_get_contents($dist . DS . $file));

                                        // Allocate A Color For The Text
                                        $white = imagecolorallocate($jpg_image, 60, 60, 60);
                                        $whitetran = imagecolorallocatealpha($jpg_image, 255, 255, 255, 10);
                                        $bgc = imagecolorallocate($jpg_image, 220, 220, 220);

                                        $height = imagesy($jpg_image);

                                        // Set Path to Font File
                                        $font_path = PATH_ROOT . '/themes/webapp/default/assets/fonts/Montserrat-Regular.ttf';

                                        // Set Text to Be Printed On Image
                                        $text = $textwatermask;

                                        $fontsize = (int) $ow / strlen($text) + 2;
                                        $_bgheight = $height - $fontsize - 20;
//                                        imagefilledrectangle($jpg_image, 0, $height, $ow, $_bgheight, $bgc);
                                        // Print Text On Image
                                        $bbox = imagettfbbox($fontsize, 0, $font_path, $textwatermask);
                                        $center1 = (imagesx($jpg_image) / 2) - (($bbox[2] - $bbox[0]) / 2);
//                                        imagettftext($jpg_image, $fontsize, 0, $center1, $height - 10, $white, $font_path, $text);
                                        imagettftext($jpg_image, $fontsize, 0, $center1, $height / 3, $whitetran, $font_path, $text);

                                        // Send Image to Browser
                                        imagepng($jpg_image, $dist . DS . $file);

                                        // Clear Memory
                                        imagedestroy($jpg_image);

                                    endif;
                                endif;
                            } catch (\Imagine\Exception\RuntimeException $exc) {
                                
                            }


                        endif;

                        return $mediaPath . DS . $file;
                    else:
                        return false;
                    endif;
                endif;
            endforeach;
            return false;
        else:
            return false;
        endif;
    }

    public function uploadFiles($item = null, $size = [], $allowed = [], $autodelete = true, $folders = [], $fileNames = [], $textwatermask = '', $createdBy = null) {
        if (!$item):
            return false;
        endif;
        $adapter = new \Laminas\File\Transfer\Adapter\Http();
        $_files = $adapter->getFileInfo();
        if (!empty($_files)):
            $mediaSettings = $this->getSettings('all');
            if (!$mediaSettings || !$mediaSettings['media']['allow'] || !$mediaSettings['media']['mediapath']):
                $this->flashMessenger()->addErrorMessage('Please configurate media first');
                false;
            endif;
            if (!$allowed):
                $_allowed = (isset($mediaSettings['media']['allow']) && $mediaSettings['media']['allow']) ? explode(',', $mediaSettings['media']['allow']) : ['jpg', 'jpeg', 'png'];
                $allowed = array_map('strtolower', $_allowed);
            endif;
            $mediaPath = (isset($mediaSettings['media']['mediapath']) && $mediaSettings['media']['mediapath']) ? $mediaSettings['media']['mediapath'] : 'uploads';
            $dist = PATH_ROOT . DS . $mediaPath;
            $itemid = $item->getId();
            $now = new \DateTime();
            $dateFolder = $now->format('Y-m-d');
            $fileNames = [];
            foreach ($_files as $key => $files):
                $getFunction = 'get' . ucfirst($key);
                $context = str_replace('DoctrineORMModule\\Proxy\\__CG__\\', '', get_class($item));
                $context = strtolower(str_replace('AppEntity\\', '', $context)) . '_' . $key;
                $_folder = (isset($folders[$key]) && $folders[$key]) ? $folders[$key] : $context . '/' . $dateFolder . '/' . $itemid;
                $folder = strtolower($_folder);
                if ($files['error'] == 0):
                    $ext = pathinfo($files['name'], PATHINFO_EXTENSION);
                    $fileName = (isset($fileNames[$key]) && $fileNames[$key]) ? $fileNames[$key] . '.' . $ext : $this->makeSafe($files['name']) . '_' . time() . '.' . $ext;
                    if (!in_array(strtolower($ext), $allowed)):
                        return false;
                    endif;
                    $file = sprintf('%s/%s', $folder, $fileName);
                    $path = $dist . DS . $folder;
                    if (!file_exists($path)):
                        mkdir($path, 0755, true);
                    endif;
                    $adapter->addFilter('Rename', array(
                        'target' => $dist . DS . $file,
                        'overwrite' => true), $files['name']
                    );

                    if (isset($mediaSettings[$context])):
                        list($width, $height) = explode('x', $mediaSettings[$context], 2);
                        if ((int) $width <= 0):
                            $width = 300;
                        else:
                            $width = (int) $width;
                        endif;
                        if ((int) $height <= 0):
                            $height = 300;
                        else:
                            $height = (int) $height;
                        endif;
                    elseif (isset($size[$key])):
                        $width = $size[$key][0];
                        $height = $size[$key][1];
                    endif;

                    if ($adapter->receive($files['name'])):

//                        if (($image = $item->$getFunction()) && $autodelete):
//                            $oldFile = $path . DS . $image;
//                            if (is_file($oldFile)):
//                                unlink($oldFile);
//                            endif;
//                        endif;
                        if (in_array($ext, ['jpg', 'jpeg', 'png'])):
                            $imagine = new \Imagine\Gd\Imagine();

                            try {
                                $_img = $imagine->open($dist . DS . $file);
                                $ow = $_img->getSize()->getWidth();
                                $oh = $_img->getSize()->getHeight();
                                if (isset($width) && isset($height)):
                                    $contextsetting = ['width' => $width, 'height' => $height];

                                    $w = $h = 0;
                                    if ($contextsetting['width'] >= $ow): // maxwidth > or width
                                        if ($oh <= $contextsetting['height']): // neu nho hon w va h thi giu nguyen kich thuoc
                                            $w = $ow;
                                            $h = $oh;
                                        else: // neu height lon hon width nho hon thi resize theo height
                                            $h = $contextsetting['height'];
                                            $w = $ow * $h / $oh;
                                        endif;
                                    else:
                                        $tmpW = $contextsetting['width']; // width tam
                                        $tmpH = $oh * $tmpW / $ow; // chieu cao tam
                                        if ($tmpH <= $contextsetting['height']): // neu chieu cao tam nho hon chieu cao max => chon chieu cao tam va width = width max
                                            $h = $tmpH;
                                            $w = $contextsetting['width'];
                                        else://neu chieu cao tam lon hon chieu cao max => chon chieu cao = chieu cao max va kich thuoc = kich thuoc tinh toan lai
                                            $h = $contextsetting['height'];
                                            $w = $ow * $h / $oh;
                                        endif;
                                    endif;
                                    //outbound=>crop, null=>resize
                                    $_img->resize(new \Imagine\Image\Box($w, $h))->save($dist . DS . $file, array('jpeg_quality' => 100, 'png_compression_level' => 9));
                                    if ($textwatermask):
                                        $jpg_image = imagecreatefromstring(file_get_contents($dist . DS . $file));

                                        // Allocate A Color For The Text
                                        $white = imagecolorallocate($jpg_image, 60, 60, 60);
                                        $whitetran = imagecolorallocatealpha($jpg_image, 255, 255, 255, 10);
                                        $bgc = imagecolorallocatealpha($jpg_image, 220, 220, 220, 50);

                                        $height = imagesy($jpg_image);

                                        // Set Path to Font File
                                        $font_path = PATH_ROOT . '/themes/webapp/default/assets/fonts/Montserrat-Regular.ttf';

                                        // Set Text to Be Printed On Image
                                        $text = $textwatermask;
                                        $fontsize = (int) $ow / strlen($text) + 2;
                                        $_bgheight = $height - $fontsize - 20;
//                                        imagefilledrectangle($jpg_image, 0, $height, $w, $_bgheight, $bgc);
                                        // Print Text On Image
                                        $bbox = imagettfbbox($fontsize, 0, $font_path, $textwatermask);
                                        $center1 = (imagesx($jpg_image) / 2) - (($bbox[2] - $bbox[0]) / 2);
//                                        imagettftext($jpg_image, $fontsize, 0, 10, $height - 10, $white, $font_path, $text);
                                        imagettftext($jpg_image, $fontsize, 0, $center1, $height / 3, $whitetran, $font_path, $text);

                                        // Send Image to Browser
                                        imagepng($jpg_image, $dist . DS . $file);

                                        // Clear Memory
                                        imagedestroy($jpg_image);

                                    endif;
                                else:
                                    if ($textwatermask):
                                        $jpg_image = imagecreatefromstring(file_get_contents($dist . DS . $file));

                                        // Allocate A Color For The Text
                                        $white = imagecolorallocate($jpg_image, 60, 60, 60);
                                        $whitetran = imagecolorallocatealpha($jpg_image, 255, 255, 255, 10);
                                        $bgc = imagecolorallocate($jpg_image, 220, 220, 220);

                                        $height = imagesy($jpg_image);

                                        // Set Path to Font File
                                        $font_path = PATH_ROOT . '/themes/webapp/default/assets/fonts/Montserrat-Regular.ttf';

                                        // Set Text to Be Printed On Image
                                        $text = $textwatermask;

                                        $fontsize = (int) $ow / strlen($text) + 2;
                                        $_bgheight = $height - $fontsize - 20;
//                                        imagefilledrectangle($jpg_image, 0, $height, $ow, $_bgheight, $bgc);
                                        // Print Text On Image
                                        $bbox = imagettfbbox($fontsize, 0, $font_path, $textwatermask);
                                        $center1 = (imagesx($jpg_image) / 2) - (($bbox[2] - $bbox[0]) / 2);
//                                        imagettftext($jpg_image, $fontsize, 0, $center1, $height - 10, $white, $font_path, $text);
                                        imagettftext($jpg_image, $fontsize, 0, $center1, $height / 3, $whitetran, $font_path, $text);

                                        // Send Image to Browser
                                        imagepng($jpg_image, $dist . DS . $file);

                                        // Clear Memory
                                        imagedestroy($jpg_image);

                                    endif;
                                endif;
                            } catch (\Imagine\Exception\RuntimeException $exc) {
                                
                            }


                        endif;

                        $fileNames[$key] = $mediaPath . DS . $file;
                    else:
                        return false;
                    endif;
                endif;
            endforeach;
            return $fileNames;
        else:
            return false;
        endif;
    }

    public function uploadImageTest($imageData = '', $folder = '', $settings = [], $addwatermask = true, $watermasktext = '') {
        if (!$settings):
            $settings = $this->getSettings('media');
        endif;
        $path = PATH_ROOT . '/' . $settings['media']['mediapath'] . '/' . $folder . '/';
        if (!file_exists($path)):
            @mkdir($path, 0755, true);
        endif;
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)):


            $data = substr($imageData, strpos($imageData, ',') + 1);
            $ext = strtolower($type[1]); // jpg, png, gif


            if (!in_array($ext, explode(',', $settings['media']['allow']))):
                return $this->display(
                                [
                                    'code' => 201,
                                    'msg' => 'Không thể upload file. Vui lòng upload file ' . $settings['media']['allow']
                                ]
                        );
            endif;
            $data = base64_decode($data);
            if ($data === false):
                return $this->display(
                                [
                                    'code' => 201,
                                    'msg' => 'File không đúng'
                                ]
                        );
            else:
                $fileName = md5(time() . $folder) . '.' . $ext;
                file_put_contents($path . $fileName, $data);
                // add watermask
                if ($addwatermask):
                    if ($ext == 'png'):
                        $jpg_image = imagecreatefrompng($path . $fileName);
                        // Allocate A Color For The Text
                        $white = imagecolorallocate($jpg_image, 255, 255, 255);

                        // Set Path to Font File
                        $font_path = PATH_ROOT . '/themes/webapp/default/assets/fonts/Montserrat-Regular.ttf';

                        // Set Text to Be Printed On Image
                        $text = ($watermasktext) ?: date('d-m-y H:i:s');
//                        $height = imagesy($jpg_image);
                        echo '<pre>';
                        print_r($height);
                        echo '</pre>';
                        exit;

                        // Print Text On Image
                        imagettftext($jpg_image, 25, 0, 30, 30, $white, $font_path, $text);

                        // Send Image to Browser
                        imagepng($jpg_image, $path . $fileName);

                        // Clear Memory
                        imagedestroy($jpg_image);

                    else:
                        $jpg_image = imagecreatefromjpeg($path . $fileName);
                        // Allocate A Color For The Text
                        $white = imagecolorallocate($jpg_image, 255, 255, 255);

                        // Set Path to Font File
                        $font_path = PATH_ROOT . '/themes/webapp/default/assets/fonts/Montserrat-Regular.ttf';

                        // Set Text to Be Printed On Image
                        $text = ($watermasktext) ?: date('d-m-y H:i:s');
                        $height = imagesy($jpg_image);
                        // Print Text On Image
                        imagettftext($jpg_image, 25, 0, 30, 30, $white, $font_path, $text);

                        // Send Image to Browser
                        imagecreatefromjpeg($jpg_image, $path . $fileName);

                        // Clear Memory
                        imagedestroy($jpg_image);

                    endif;
                endif;

                //
                return $fileName;
            endif;
        else:
            return $this->display(
                            [
                                'code' => 201,
                                'msg' => 'File không đúng'
                            ]
                    );
        endif;
    }
}
