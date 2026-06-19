<?php

namespace AppKernel\Controller;

use Laminas\Mvc\Controller\AbstractActionController;

class AbstractApplicationController extends AbstractActionController {

    use \AppKernel\Traits\Caches\ApcuTrait;

    public function addAction() {
        $variables = $this->form();
        if ($variables instanceof \Laminas\Http\PhpEnvironment\Response):
            return $variables;
        endif;
        return $this->display($variables);
    }

    public function editAction() {
        $id = $this->params()->fromRoute('id', '');
        if (!$id && $this->request->isGet()):
            $rm = $this->getEvent()->getRouteMatch();
            $matched = $rm->getMatchedRouteName();
            $routers = explode('/', $matched);
            array_pop($routers);
            $router = implode('/', $routers);
            $this->flashMessenger()->addErrorMessage('Item do not exist');
            return $this->redirect()->toRoute($router);
        endif;
        $variables = $this->form($id);
        if ($variables instanceof \Laminas\Http\PhpEnvironment\Response):
            return $variables;
        endif;
        return $this->display($variables);
    }

    public function viewAction() {
        $id = $this->params()->fromRoute('id', '');
        if (!$id && $this->request->isGet()):
            $rm = $this->getEvent()->getRouteMatch();
            $matched = $rm->getMatchedRouteName();
            $routers = explode('/', $matched);
            array_pop($routers);
            $router = implode('/', $routers);
            $this->flashMessenger()->addErrorMessage('Item do not exist');
            return $this->redirect()->toRoute($router);
        endif;
        $variables = $this->form($id);
        if ($variables instanceof \Laminas\Http\PhpEnvironment\Response):
            return $variables;
        endif;
        return $this->display($variables);
    }

    public function editOwnAction() {
        $id = $this->params()->fromRoute('id', '');
        if (!$id && $this->request->isGet()):
            $rm = $this->getEvent()->getRouteMatch();
            $matched = $rm->getMatchedRouteName();
            $routers = explode('/', $matched);
            array_pop($routers);
            $router = implode('/', $routers);
            $this->flashMessenger()->addErrorMessage('Item do not exist');
            return $this->redirect()->toRoute($router);
        endif;
        $variables = $this->form($id, true);
        if ($variables instanceof \Laminas\Http\PhpEnvironment\Response):
            return $variables;
        endif;
        return $this->display($variables);
    }

    public function deleteEntity($entity = '') {
        $backUrl = $_SERVER['HTTP_REFERER'] ?? '';
        $rm = $this->getEvent()->getRouteMatch();
        $params = $this->params();
        if (!$backUrl):

            $matched = $rm->getMatchedRouteName();
            $routers = explode('/', $matched);
            array_pop($routers);
            $router = implode('/', $routers);
        endif;

        if (!$entity):
            $controller = $rm->getParam('controller');
            $entity = substr(strrchr($controller, "\\"), 1);
            if (!$entity):
                $this->flashMessenger()->addErrorMessage('Entity do not exist');
                return $backUrl ? $this->redirect()->toUrl($backUrl) : $this->redirect()->toRoute($router);
            endif;
            $entity = 'AppEntity\App' . $entity;

        endif;
        if (!class_exists($entity)):
            $this->flashMessenger()->addErrorMessage('Entity do not exist');
            return $backUrl ? $this->redirect()->toUrl($backUrl) : $this->redirect()->toRoute($router);
        endif;
        $query = $this->request->getQuery()->toArray();
        $cid = (array) $params->fromQuery('cid', []) ?: (array) $params->fromPost('cid', []);
        if (empty($cid)):
            $cid = (array) $params->fromRoute('id', []);
        endif;
        if (empty($cid)):
            $this->flashMessenger()->addErrorMessage('Please select at least an item');
            return $backUrl ? $this->redirect()->toUrl($backUrl) : $this->redirect()->toRoute($router);
        endif;
        $qb = $this->em->createQueryBuilder()->delete($entity, 'a');

        try {
            $result = $qb->where('a.id IN (' . implode(',', $cid) . ')')->getQuery()->execute();
            if ($result):
                $this->flashMessenger()->addSuccessMessage(sprintf('%d ' . $this->translator->translate('items successfully delete'), count($cid)));
            endif;
        } catch (\Doctrine\DBAL\DBALException $ex) {
            if ($ex->getErrorCode() == 1451):
                $this->flashMessenger()->addErrorMessage('Can not delete these items.');
            endif;
        }
        if (isset($query['cid'])):
            unset($query['cid']);
        endif;
        return $backUrl ? $this->redirect()->toUrl($backUrl) : $this->redirect()->toRoute($router, [], ['query' => $query]);
    }

    public function flushViewCache() {
        $viewCacheFolder = PATH_DATA . '/view-cache';
        if (file_exists($viewCacheFolder)):
            $cacheDriver = new \AppKernel\View\Cache\ViewCache($viewCacheFolder);
            $cacheDriver->flushAll();
        endif;
    }

    public function flushConfig() {
        // xoa config
        $configFolder = PATH_DATA . '/config';
        @array_map("unlink", glob($configFolder . '/*.php'));
    }

    public function flushCache() {
        $paths = [
            PATH_DATA . '/cache',
            PATH_DATA . '/DoctrineModule/cache',
            PATH_ROOT . '/cache'
        ];
        foreach ($paths as $path):
            if (is_dir($path)):
                $this->deleteFolder($path);
            endif;
            @mkdir($path, 0777, true);
        endforeach;

        $this->apcuflush();
    }

    protected function deleteFolder($folder) {
        try {
            $_folders = scandir($folder);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
            exit;
        }


        if ($_folders):
            $files = array_diff($_folders, ['.', '..']);
            foreach ($files as $file):
                $path = $folder . DIRECTORY_SEPARATOR . $file;
                if (is_dir($path)):
                    $this->deleteFolder($path);
                else:
                    unlink($path);
                endif;
            endforeach;
            @rmdir($folder);
        endif;
    }

//    public function flushCache() {
//        $cacheFolder = PATH_DATA . '/DoctrineModule/cache';
//        if (file_exists($cacheFolder)):
////            \Common\Cache\FilesystemCache($cacheFolder)
//            $cacheDriver = new \Doctrine\Common\Cache\
//            $cacheDriver->flushAll();
//        endif;
//
//        
//    }

    public function flushAll() {
        $this->flushCache();
        $this->flushViewCache();
        $this->flushConfig();
    }

    public function flush($regions = [], $clearCacheKeys = []) {
        try {
            $this->em->flush();

            $this->flushCache();
            $this->flushViewCache();
            if (!empty($regions) && ($cacheFactory = $this->em->getConfiguration()->getSecondLevelCacheConfiguration()->getCacheFactory())):

                foreach ($regions as $region):
                    $cacheFactory->getRegion(['region' => $region, 'usage' => 1])->evictAll();
//                    $cacheFactory->getRegion(['region' => 'app_visitor_ip', 'usage' => 1])->evictAll();
                endforeach;
            endif;
            if (!empty($clearCacheKeys)):
//                $apcu = new \AppKernel\Service\Caches\Apcu();
                foreach ($clearCacheKeys as $key):
                    $this->deleteByKey($key);
                endforeach;
            endif;
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
            return $settings;
        else:
            return [$type => $settings[$type . CURRENT_SYSTEM] ?? $settings[$type] ?? []];
        endif;
    }

    public function sendEmail($info = array(), $template = '', $emailto = '', $subject = '') {
        
        $pathLog = realpath(__DIR__ . '/../../../../../data/logs');
        $sendEmailPath = $pathLog . '/' . date('d_m_Y') . '/sendemail';
        if (!file_exists($sendEmailPath)):
            mkdir($sendEmailPath, 0755, true);
        endif;

        if (!filter_var($emailto, FILTER_VALIDATE_EMAIL)):
            // khong phai email thi ko gui nua
            return false;
        endif;
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
        $message = new \Laminas\Mail\Message();
        $message->addTo($emailto)
                ->addBcc($default_bcc)
                ->addFrom($settings['general']['default_from'])
                ->setSubject($subject . ' - ' . CURRENT_DOMAIN);
        $message->setEncoding('UTF-8');
        $transport = new \Laminas\Mail\Transport\Smtp();
        $smtpoption = $settings['email']['config'];
        $smtpoption['host'] = $settings['general']['smtp_host'];
        $smtpoption['connection_config']['username'] = $settings['general']['smtp_username'];
        $smtpoption['connection_config']['password'] = $settings['general']['smtp_password'];
        $smtpoption['connection_config']['ssl'] = $settings['general']['smtp_type'];
        $smtpoption['port'] = $settings['general']['smtp_port'];

        $options = new \Laminas\Mail\Transport\SmtpOptions($smtpoption);
        $renderer = $this->container->get('ViewRenderer');
        $info['sitename'] = $settings['general']['sitename'];
        $content = $renderer->render($template, array('info' => $info, 'settings' => $settings));
        $html = new \Laminas\Mime\Part($content);
        $html->type = "text/html; charset = UTF-8";
        $body = new \Laminas\Mime\Message();
        $body->addPart($html);
        $message->setBody($body);
        $transport->setOptions($options);
        $logger = $this->container->build('Logger', [
            'name' => __FUNCTION__
        ]);
        try {
            $transport->send($message);
            $logger->info(
                    sprintf(
                            "Subject: %s \nEmail:%s\n----------------------------------------------------------------------\n", $subject, $emailto
                    )
            );
            return true;
        } catch (\Laminas\Mail\Protocol\Exception\RuntimeException $exception) {
            $logger->error(
                    sprintf(
                            "%s:%d %s (%d) [%s] \n %s \n----------------------------------------------------------------------\n", $exception->getFile(), $exception->getLine(), $exception->getMessage(), $exception->getCode(), get_class($exception), $exception->getTraceAsString()
                    )
            );
            return false;
        }
    }

    public function export($datas, $sheetName = 'Export Data') {
        if (function_exists('mb_internal_encoding')) {
            $oldEncoding = mb_internal_encoding();
            mb_internal_encoding('latin1');
        }
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $Excel_writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $spreadsheet->setActiveSheetIndex(0);
        $activeSheet = $spreadsheet->getActiveSheet();

        $activeSheet->setTitle($sheetName);
        $fileName = 'export_' . date('YmdHis') . '.xlsx';
        $header = $datas[0];
        $char = 'A';
        $line = 1;
        $fields = [];
        foreach ($header as $key => $title):
            $activeSheet->setCellValueExplicit($char . $line, $title, 'str');
            $fields[$char] = $key;
            $activeSheet->getColumnDimension($char)->setAutoSize(true);
            $char++;
        endforeach;
        $keys = array_keys($fields);
        $charEnd = end($keys);
        $activeSheet->getStyle('A' . $line . ':' . $charEnd . $line)->applyFromArray(['font' => ['size' => 16, 'bold' => true]]);
        //$activeSheet->freezePane('A1','O1');       
        if ($datas):
            foreach ($datas as $data):
                $line++;
                foreach ($fields as $char => $field):
                    if (!isset($data[$field])):
                        $data[$field] = ' - ';
                    endif;
                    if (is_numeric($data[$field])):
                        $activeSheet->setCellValueExplicit($char . $line, $data[$field], 'n');
                    else:
                        $activeSheet->setCellValue($char . $line, $data[$field]);
                    endif;

                endforeach;
            endforeach;
        endif;
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); //mime type
        header('Content-Disposition: attachment;filename="' . $fileName . '"'); //tell browser what's the file name
        header('Cache-Control: max-age=0'); //no cache
        //save it to Excel5 format (excel 2003 .XLS file), change this to 'Excel2007' (and adjust the filename extension, also the header mime type)
        //if you want to save it as .XLSX Excel 2007 format
        //$objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        //$objWriter->setPreCalculateFormulas(true);
        //$objWriter->setIncludeCharts(true);
        //force user to download the Excel file without writing it to server's HD
        setlocale(LC_ALL, 'en_US');
        $Excel_writer->save('php://output');
        exit;
    }

    function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    public function upload($item = null, $size = [], $allowed = [], $autodelete = true, $folders = [], $fileNames = [], $textwatermask = '') {
        if (!$item):
            return false;
        endif;
        $adapter = new \Laminas\File\Transfer\Adapter\Http();
        $_files = $adapter->getFileInfo();
        if (!empty($_files)):
            $settings = $this->getSettings('all');
            $mediaSettings = $settings['media'];
            if (!$mediaSettings || !$mediaSettings['allow'] || !$mediaSettings['mediapath']):
                $this->flashMessenger()->addErrorMessage('Please configurate media first');
                false;
            endif;
            if (!$allowed):
                $_allowed = (isset($mediaSettings['allow']) && $mediaSettings['allow']) ? explode(',', $mediaSettings['allow']) : ['jpg', 'jpeg', 'png'];
                $allowed = array_map('strtolower', $_allowed);
            endif;

            $dist = (isset($mediaSettings['mediapath']) && $mediaSettings['mediapath']) ? PATH_ROOT . DS . $mediaSettings['mediapath'] : PATH_ROOT . DS . 'uploads';
            $itemid = $item->getId();
            $now = new \DateTime();
            $dateFolder = $now->format('Y-m-d');
            foreach ($_files as $key => $files):
                $setFunction = 'set' . ucfirst($key);
//                $getFunction = 'get' . ucfirst($key);
                $context = strtolower(str_replace('AppEntity\\', '', get_class($item))) . '_' . $key;
                $folder = (isset($folders[$key]) && $folders[$key]) ? $folders[$key] : $context . '/' . $dateFolder . '/' . $itemid;

                if ($files['error'] == 0):
                    $ext = pathinfo($files['name'], PATHINFO_EXTENSION);
                    $fileName = (isset($fileNames[$key]) && $fileNames[$key]) ? $fileNames[$key] : $this->makeSafe($files['name']) . '_' . time() . '.' . $ext;
                    if (!in_array(strtolower($ext), $allowed)):
                        $this->flashMessenger()->addErrorMessage('File extension is not allowed. Please select the picture has extionsion as ' . implode(', ', $allowed));
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

//                        if (!($image = $item->$getFunction())):
//                            $image = new \AppEntity\AppMedia();
//                        elseif ($autodelete):
//                            $oldFile = $path . DS . $image->getName();
//                            if (is_file($oldFile)):
//                                unlink($oldFile);
//                            endif;
//                        endif;
                        $fullFileName = $settings['media']['mediapath'] . '/' . $file;
                        $item->$setFunction($fullFileName);
                    else:
                        $this->flashMessenger()->addErrorMessage('Have error when uploading. Please submit again');
                        return false;
                    endif;
                endif;
            endforeach;
            $this->em->persist($item);
            $this->flush();
            return true;
        else:
            return true;
        endif;
    }

    public function uploadMultiEntity($items = [], $size = [], $keys = []) {
        if (!$items):
            return false;
        endif;
        $adapter = new \Laminas\File\Transfer\Adapter\Http();
        $_files = $adapter->getFileInfo();
        if (!empty($_files)):


            $mediaSettings = $this->getSettings('all');
            if (!$mediaSettings || !$mediaSettings['allow'] || !$mediaSettings['mediapath']):
                $this->flashMessenger()->addErrorMessage('Please configurate media first');
                false;
            endif;
            $_allowed = (isset($mediaSettings['allow']) && $mediaSettings['allow']) ? explode(',', $mediaSettings['allow']) : ['jpg', 'jpeg', 'png'];
            $allowed = array_map('strtolower', $_allowed);
            $dist = (isset($mediaSettings['mediapath']) && $mediaSettings['mediapath']) ? PATH_ROOT . DS . $mediaSettings['mediapath'] : PATH_ROOT . DS . 'uploads';

            $i = 0;
            foreach ($_files as $files):
                $item = $items[$i];
                $key = $keys[$i];
                $i++;
                $setFunction = 'set' . ucfirst($key);
                $getFunction = 'get' . ucfirst($key);
                $context = strtolower(str_replace('AppEntity\\', '', get_class($item))) . '_' . $key;
                if ($files['error'] == 0):
                    $ext = pathinfo($files['name'], PATHINFO_EXTENSION);
                    $fileName = $this->makeSafe($files['name']) . '_' . time() . '.' . $ext;
                    if (!in_array(strtolower($ext), $allowed)):
                        $this->flashMessenger()->addErrorMessage('File extension is not allowed. Please select the picture has extionsion as ' . implode(', ', $allowed));
                        return false;
                    endif;
                    $folder = sprintf('%s/%s', $context, $item->getId());
                    $file = sprintf('%s/%s', $folder, $fileName);
                    $path = $dist . DS . $context . DS . $item->getId();
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
                        if (!($image = $item->$getFunction())):
                            $image = new \AppEntity\AppMedia();
                        else:
                            $oldFile = $path . DS . $image->getName();
                            if (is_file($oldFile)):
                                unlink($oldFile);
                            endif;
                        endif;
                        if (in_array($ext, ['png', 'jpg', 'jpeg'])):
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
                                    $thumb = $_img->resize(new \Imagine\Image\Box($w, $h))->save($dist . DS . $file, array('jpeg_quality' => 100, 'png_compression_level' => 9));
                                    $thumbsize = $thumb->getSize();
                                    $image->setWidth($thumbsize->getWidth());
                                    $image->setHeight($thumbsize->getHeight());
                                else:
                                    $image->setWidth($ow);
                                    $image->setHeight($oh);
                                endif;
                            } catch (\Imagine\Exception\RuntimeException $exc) {
                                $image->setWidth(0);
                                $image->setHeight(0);
                            }
                        endif;
                        $image->setName($fileName);
                        $image->setContentSize($files['size']);
                        $image->setContentType($files['type']);
                        $image->setContext($context);
                        $image->setFolder($folder);
                        $image->setEnabled(1);

                        $this->em->persist($image);
                        $item->$setFunction($image);
                    else:
                        $this->flashMessenger()->addErrorMessage('Have error when uploading. Please submit again');
                        return false;
                    endif;
                endif;
            endforeach;
            $this->em->persist($item);
            $this->flush();
            return true;
        else:
            return true;
        endif;
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

    public function saveMedia($item = null, $data = [], $settings = []) {
        if (!$settings):
            $settings = $this->getSettings('media');
        endif;
        if (isset($data['mediafilename']) && $data['mediafilename']):

            foreach ($data['mediafilename']['filename'] as $_key => $file):
                $field = $data['mediafilename']['name'][$_key];
                if ($field == 'gallery'):
                    continue;
                endif;
                $_files = json_decode($file, true);
                if ($_files):
                    continue; // bỏ list image
                endif;
//                $field = $data['mediafilename']['name'][$_key];
                if (isset($data['mediafilename']['remove'][$_key]) && $data['mediafilename']['remove'][$_key]):
                    $method = 'set' . ucfirst($field);
                    if ($item && method_exists($item, $method)):
                        $item->$method(null);
                        $this->em->persist($item);
                    endif;
                    continue;
                endif;

                if (!$file):
                    continue;
                endif;
                $_path = ltrim(parse_url($file)['path'], '/');
                $fullPathFile = PATH_ROOT . '/' . $_path;
                $path = str_replace($settings['media']['mediapath'], '', $_path);
                $_paths = explode('/', $path);
                $paths = array_filter($_paths);
                $filename = end($paths);
                array_pop($paths);
                $folder = implode('/', $paths);

//                $ext = pathinfo($fullPathFile, PATHINFO_EXTENSION);
                $media = $this->em->getRepository('AppEntity\AppMedia')
                        ->findOneBy(['folder' => $folder, 'filename' => $filename]);

                if (!$media):
                    // echo '<pre>';
                    // print_r($fullPathFile);
                    // echo '</pre>';
                    // exit;
                    $media = new \AppEntity\AppMedia();
                    $media->setFilename($filename);
                    $media->setContentType(mime_content_type($fullPathFile));
                    $media->setContentSize(filesize($fullPathFile));
                    $media->setFolder($folder);
                    $context = '';
                    $name = $filename;
                    if ($item):
                        $_context = strtolower(str_replace('AppEntity\\', '', get_class($item))) . '_' . $field;
                        $context = str_replace('doctrineormmodule\\proxy\\__cg__\\', '', $_context);
                        if ($item && method_exists($item, 'getName')):
                            $name = $item->getName();
                        endif;
                    endif;
                    $media->setName($name);
                    $media->setContext($context);
                    $media->setEnabled(1);
                    $this->em->persist($media);
                endif;
                $method = 'set' . ucfirst($field);
                if ($item && method_exists($item, $method)):
                    $item->$method($media);
                    $this->em->persist($item);
                endif;
            endforeach;
            $this->flush();
        endif;
    }

    public function saveImage($item = null, $data = [], $settings = []) {
        if (!$settings):
            $settings = $this->getSettings('media');
        endif;
        if (isset($data['mediafilename']) && $data['mediafilename']):
            $hasFlush = false;
            foreach ($data['mediafilename']['filename'] as $_key => $file):
                $field = $data['mediafilename']['name'][$_key];
                if (($field == 'gallery') || $field == 'images'):
                    continue;
                endif;
                $_files = json_decode($file, true);
                if ($_files):
                    continue; // bỏ list image
                endif;
//                $field = $data['mediafilename']['name'][$_key];
                if (isset($data['mediafilename']['remove'][$field]) && $data['mediafilename']['remove'][$field]):
                    $method = 'set' . ucfirst($field);
                    if ($item):
                        if (is_object($item) && method_exists($item, $method)):
                            $item->$method(null);
                            $this->em->persist($item);
                            $hasFlush = true;
                        elseif (is_array($item)):
                            unset($item[$field]);
                        else:
                            $item = null;
                        endif;

                    endif;
                    continue;
                endif;
                if (!$file):
                    continue;
                endif;

                $_path = ltrim(parse_url($file)['path'], '/');
//                $fullPathFile = PATH_ROOT . '/' . $_path;
                $path = str_replace($settings['media']['mediapath'], '', $_path);
                $_paths = explode('/', $path);
                $paths = array_filter($_paths);
                $filename = end($paths);
                array_pop($paths);
                $folder = implode('/', $paths);

                $fullFileName = $settings['media']['mediapath'] . '/' . (($folder) ? $folder . '/' . $filename : $filename);
                if ($item):
                    $method = 'set' . ucfirst($field);
                    if (is_object($item) && (method_exists($item, $method))):
                        $item->$method($fullFileName);
                        $this->em->persist($item);
                        $hasFlush = true;
                    elseif (is_array($item)):
                        $item[$field] = $fullFileName;
                    else:
                        $item = $fullFileName;
                    endif;
                else:
                    $item = $fullFileName;
                endif;
            endforeach;
            if ($hasFlush):
                return $this->flush();

            else:
                return $item;
            endif;

        endif;
    }

    public function saveGallery($item = null, $data = [], $settings = []) {
        if (!$settings):
            $settings = $this->getSettings('media');
        endif;
        if (isset($data['mediafilename']) && $data['mediafilename']):
            foreach ($data['mediafilename']['filename'] as $_key => $_file):
                $_files = json_decode($_file, true);
                if (!$_files):
                    continue;
                endif;
                $_files = array_unique($_files);
                $field = $data['mediafilename']['name'][$_key];

                if (isset($data['mediafilename']['remove'][$_key]) && $data['mediafilename']['remove'][$_key]):
                    $method = 'set' . ucfirst($field);
                    if ($item && method_exists($item, $method)):
                        $item->$method(null);
                        $this->em->persist($item);
                    endif;
                    continue;
                endif;
                $method = 'get' . ucfirst($field);
                $gallery = $item->$method();
                if (!$gallery):
                    $gallery = new \AppEntity\AppGallery();
                    $gallery->setContext('');
                    $gallery->setEnabled(1);
                    $name = method_exists($item, 'getName') ? $item->getName() : '';
                    if (!$name):
                        $name = method_exists($item, 'getCode') ? $item->getCode() : '';
                    endif;
                    if (!$name):
                        $name = 'gallery';
                    endif;
                    $gallery->setName($name);
                    $this->em->persist($gallery);
                    $item->setGallery($gallery);
                    $this->em->persist($item);
                    $this->flush();
                else:
                    if (!($gallery instanceof \AppEntity\AppGallery)):
                        continue;
                    endif;
                endif;
                $gallery->getMedias()->clear();
                if (!$_file):
                    continue;
                endif;

                if ($_files):
                    foreach ($_files as $file):
                        $path = ltrim(parse_url($file)['path'], '/');
                        $fullPathFile = PATH_ROOT . '/' . $path;

                        $path = str_replace($settings['media']['mediapath'], '', $path);
                        $paths = explode('/', $path);
                        $paths = array_filter($paths);
                        $filename = end($paths);
                        array_pop($paths);
                        $folder = implode('/', $paths);

                        $ext = pathinfo($fullPathFile, PATHINFO_EXTENSION);
                        $media = $this->em->getRepository('AppEntity\AppMedia')
                                ->findOneBy(['folder' => $folder, 'filename' => $filename]);

                        if (!$media):
                            $media = new \AppEntity\AppMedia();
                            $media->setFilename($filename);
                            $media->setContentType(mime_content_type($fullPathFile));
                            $media->setContentSize(filesize($fullPathFile));
                            $media->setFolder($folder);
                            $context = '';
                            $name = $filename;
                            if ($item):
                                $_context = strtolower(str_replace('AppEntity\\', '', get_class($item))) . '_' . $field;
                                $context = str_replace('doctrineormmodule\\proxy\\__cg__\\', '', $_context);
                                if ($item && method_exists($item, 'getName')):
                                    $name = $item->getName();
                                endif;
                            endif;
                            $media->setName($name);
                            $media->setContext($context);
                            $media->setEnabled(1);
                            $this->em->persist($media);
                        endif;
                        $mediaGallery = new \AppEntity\AppMediaGallery();
                        $mediaGallery->setEnabled(1);
                        $mediaGallery->setGallery($gallery);
                        $mediaGallery->setMedia($media);
                        $this->em->persist($mediaGallery);
                    endforeach;
                endif;

            endforeach;
            $this->flush();
        endif;
    }

    public function saveImages($item = null, $data = [], $settings = []) {
        if (!$settings):
            $settings = $this->getSettings('media');
        endif;
        if (isset($data['mediafilename']) && $data['mediafilename']):
            foreach ($data['mediafilename']['filename'] as $_key => $_file):
                $__files = json_decode($_file, true);
                if (!$__files):
                    continue;
                endif;
                $_files = array_unique($__files);
                $field = $data['mediafilename']['name'][$_key];

                if (isset($data['mediafilename']['remove'][$field]) && $data['mediafilename']['remove'][$field]):
                    $method = 'set' . ucfirst($field);
                    if ($item && method_exists($item, $method)):
                        $item->$method(null);
                        $this->em->persist($item);
                    endif;
                    continue;
                endif;
//                $methodGet = 'get' . ucfirst($field);
                $methodSet = 'set' . ucfirst($field);
//                $gallery = $item->$methodGet();
                $_gallery = [];
                if (!$_file):
                    continue;
                endif;

                if ($_files):
                    foreach ($_files as $file):
                        $_path = ltrim(parse_url($file)['path'], '/');
                        $path = str_replace($settings['media']['mediapath'], '', $_path);
                        $paths = array_filter(explode('/', $path));
                        $filename = end($paths);
                        array_pop($paths);
                        $folder = implode('/', $paths);
                        $fullFileName = $settings['media']['mediapath'] . '/' . (($folder) ? $folder . '/' . $filename : $filename);
                        $_gallery[] = $fullFileName;
                    endforeach;
                endif;

                $_gallery = array_unique($_gallery);
                $item->$methodSet(json_encode($_gallery));
                $this->em->persist($item);
            endforeach;
            $this->flush();
        endif;
    }

    public function translate($entityClass = '', $id = 0, $lang = 'vi', $listRoute = '') {
        if ($entityClass[0] != '\\'):
            $entityClass = '\\' . $entityClass;
        endif;
        $entity = $this->em->getRepository($entityClass)->find($id);
        $isRoot = true;
        $parent = null;
        $entityChild = null;
        if ($entity):

            $parent = $entity->getTranslateOf();
            if ($parent):// ko phải root
                if ($parent->getLang() == $lang):
                    return $this->redirect()->toRoute("$listRoute/edit", ['id' => $parent->getId()]);
                endif;
                $isRoot = false;
                $entityChild = $this->em->getRepository($entityClass)->findOneBy([
                    'translateOf' => $parent->getId(),
                    'lang' => $lang,
                ]); // tìm category dịch từ category gốc với ngôn ngữ = lang
                if ($entityChild):// nếu đã dịch
                    return $this->redirect()->toRoute("$listRoute/edit", ['id' => $entityChild->getId()]);
                else:
                    $entityChild = new $entityClass();
                    $entityChild->setName('');
                    $entityChild->setLang($lang);
                    $parentFields = [];
                    if (method_exists($parent, 'getExtendFields')):
                        $parentFields = $parent->getExtendFields();
                    endif;

                    if ($parentFields):
                        foreach ($parentFields as $parentField):
                            $methodSet = 'set' . ucfirst($parentField);
                            $methodGet = 'get' . ucfirst($parentField);
                            if (method_exists($parent, $methodGet)):
                                $entityChild->$methodSet($parent->$methodGet());
                            endif;
                        endforeach;
                    endif;
                    $entityChild->setTranslateOf($parent);
                    $this->em->persist($entityChild);
                    $this->flush();
                    return $this->redirect()->toRoute("$listRoute/edit", ['id' => $entityChild->getId()]);
                endif;
            else: // là entity root
                $entityChild = $this->em->getRepository($entityClass)->findOneBy([
                    'translateOf' => $entity->getId(),
                    'lang' => $lang,
                ]); // tìm category dịch từ category gốc với ngôn ngữ = lang
                if ($entityChild):// nếu đã dịch
                    return $this->redirect()->toRoute("$listRoute/edit", ['id' => $entityChild->getId()]);
                else:
                    $entityChild = new $entityClass();
                    $entityChild->setName('');
                    $entityChild->setLang($lang);
                    $parentFields = [];
                    if (method_exists($entity, 'getExtendFields')):
                        $parentFields = $entity->getExtendFields();
                    endif;
                    if ($parentFields):
                        foreach ($parentFields as $parentField):
                            $methodSet = 'set' . ucfirst($parentField);
                            $methodGet = 'get' . ucfirst($parentField);
                            if (method_exists($entity, $methodGet)):
                                $entityChild->$methodSet($entity->$methodGet());
                            endif;
                        endforeach;
                    endif;
                    $entityChild->setTranslateOf($entity);
                    $this->em->persist($entityChild);
                    $this->flush();
                    return $this->redirect()->toRoute("$listRoute/edit", ['id' => $entityChild->getId()]);
                endif;
            endif;
        else:
            $this->flashMessenger()->addErrorMessage(sprintf($this->translator->translate('Item do not exist')));
            return $this->redirect()->toRoute($listRoute);
        endif;
    }

    public function display($variables = [], $terminal = false, $viewjson = false) {
        if (!$viewjson):
            $params = $this->params();
            $controllers = explode('\\', $params->fromRoute('controller'));
            $template = $variables['forceTemplate'] ?? '';
            if (!$template):
                if (!isset($variables['template'])):
                    $action = $params->fromRoute('action');
                    $controller = str_replace('Controller', '', end($controllers));
                    $template = strtolower($controllers[1] . '/' . $controller . '/' . $action . '.phtml');
                else:
                    $template = strtolower($controllers[1] . '/') . $variables['template'];
                    unset($variables['template']);
                endif;
            endif;

            if (isset($variables['layout']) && $variables['layout']):
                $this->layout()->setTemplate($variables['layout']);
            endif;
            $application = $this->container->get('Application');

            $routeMatch = $application->getMvcEvent()->getRouteMatch();
            $currentRoute = $routeMatch->getMatchedRouteName();
            $_rmParams = $routeMatch->getParams() ?: [];
            if (!($variables['title'] ?? false)):


                try {
                    $menuConfig = $application->getServiceManager()->get('menuConfig');
                    $variables['title'] = $this->translator->translate($menuConfig[$currentRoute] ?? '');
                    if (!$variables['title']):
                        $variables['title'] = $this->translator->translate($_rmParams['label'] ?? ucfirst($_rmParams['action']) ?? '');
                    endif;
                } catch (\Exception $exc) {
                    $variables['title'] = $this->translator->translate($_rmParams['label'] ?? ucfirst($_rmParams['action']) ?? '');
                }


            endif;
            $viewModel = new \Laminas\View\Model\ViewModel($variables);
            if ($terminal):
                $viewModel->setTerminal(true);
            endif;
//            $viewModel->setVariable('breadcrumb', 1);
            $viewModel->setVariable('currentRoute', $currentRoute);
            $viewModel->setVariable('currentRouteParams', $_rmParams);
            $viewModel->setTemplate($template);
            return $viewModel;
        else:
            if (!isset($variables['code'])):
                $variables['code'] = 200;
            endif;
            if (!isset($variables['msg'])):
                $variables['msg'] = '';
            endif;
//        $val['method'] = __METHOD__;
            return new \Laminas\View\Model\JsonModel($variables);
        endif;
    }

    public function displayJSON($val = []) {
        if (!isset($val['code'])):
            $val['code'] = 200;
        endif;
        if (!isset($val['msg'])):
            $val['msg'] = '';
        endif;
//        $val['method'] = __METHOD__;
        return new \Laminas\View\Model\JsonModel($val);
    }

    public function checkVoucher($voucherCode, $date = null) {
        if (!$date):
            $date = new \DateTime();
        endif;
        if (!$voucherCode):
            return ['msg' => $this->translator->translate('Invalid promo code')];
        endif;
        $voucher = $this->em->getRepository(\AppEntity\AppVoucher::class)->createQueryBuilder('a', 'a.id')
                        ->where("a.status=1 and a.code='$voucherCode'")
                        ->andWhere('(a.fromDate is null or a.fromDate <= :currentDate)')
                        ->andWhere('(a.toDate is null or a.toDate >= :currentDate)')
                        ->andWhere('a.mainSystem = :mainsystem')->setParameter('mainsystem', CURRENT_SYSTEM)
                        ->setParameter('currentDate', $date->format('Y-m-d H:i:s'))
                        ->setMaxResults(1)
                        ->getQuery()->getOneOrNullResult();
        return $voucher;
    }

    public function getDiscountAmount(\AppEntity\AppVoucher $voucher, $total = 0) {
        $applied = (int) $voucher->getApplied();
        $available = (int) $voucher->getAvailable();
        $discountAmount = 0;
        if (($applied + 1) <= $available):
            $tmpDiscount = (float) $voucher->getDiscount();
            $maxDiscount = (float) $voucher->getMaxDiscount();
            $amountDiscount = $voucher->getDiscountType() ? $tmpDiscount : ($tmpDiscount * $total / 100); //1 là giảm trực tiếp 0 là giảm theo %
            if ($maxDiscount):
                $totalDiscount = (($amountDiscount < $maxDiscount)) ? $amountDiscount : $maxDiscount;
            else:
                $totalDiscount = $amountDiscount;
            endif;
            $discountAmount = round($totalDiscount);
        endif;
        return $discountAmount;
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

    public function getCitiesOptions() {
        if (!$this->em):
            return [];
        endif;
        // $cacheSettings = $this->config['settings']['caches'];
        $qb = $this->em->getRepository('AppEntity\AppLocationCity')
                ->createQueryBuilder('a', 'a.id')->select('a.name,a.id')
                ->andWhere("a.status=1")
                ->orderBy('a.ordering', 'desc')
                ->addOrderBy('a.id', 'desc');
        $datas = $qb->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-location-city-list-options')
                ->getResult();
        $result = [];
        if ($datas):
            $result = [];
            foreach ($datas as $data):
                $result[] = ['value' => $data['id'], 'label' => $data['name']];
            endforeach;
        endif;

        return $result;
    }

    public function getDistrictsOptions() {
        if (!$this->em):
            return ['' => 'Choose cities'];
        endif;
        // $cacheSettings = $this->config['settings']['caches'];
        $qb = $this->em->getRepository('AppEntity\AppLocationDistrict')
                ->createQueryBuilder('a', 'a.id')->select('a.name,a.id,identity(a.city) city')
                ->andWhere("a.status=1")
                ->orderBy('a.name', 'desc');
        $datas = $qb->getQuery()
                ->useQueryCache(true)
                ->useResultCache($this->cacheEnable, $this->cacheTime, 'app-location-district-list-options')
                ->getResult();
        $result = ['' => 'Choose cities'];
        if ($datas):
            $result = [];
            foreach ($datas as $data):
                $result[] = ['value' => $data['id'], 'label' => $data['name'], 'city' => $data['city']];
            endforeach;
        endif;

        return $result;
    }

    public function checkShippingFee($sid = 0, $cityId = 0, $districtId = 0) {
        $shippingMethod = ($sid) ? $this->em->getRepository(\AppEntity\AppShippingMethod::class)->findOneBy([
                    'id' => $sid,
                    'status' => 1
                ]) : null;
        if (!$shippingMethod):
            return ['status' => 201, 'msg' => $this->translator->translate('Shipping method does not exist')];
        else:
            $type = $shippingMethod->getType();
            if (!$type):
                return ['status' => 200, 'msg' => $this->translator->translate('Success'), 'price' => 0];
            elseif ($type == 1):
                return ['status' => 200, 'msg' => $this->translator->translate('Success'), 'price' => $shippingMethod->getPrice()];
            else:

                $details = ($_details = $shippingMethod->getDetails()) ? json_decode($_details, true) : [];
                $defaultPrice = -1;
                $price = -1;
                if ($details):
                    foreach ($details as $detail):
                        $_cityId = (int) $detail['cityId'];
                        if ($_cityId):
                            if ($_cityId === $cityId):
                                if ($detail['districts']):
                                    if (in_array($districtId, $detail['districts'])):
                                        $_price = (float) $detail['price'];
                                        if ($price == -1):
                                            $price = $_price; // nếu chưa set giá
                                        else:
                                            if ($_price < $price):
                                                $price = $_price; // nếu đã set giá thì lấy giá nhỏ nhơn
                                            endif;
                                        endif;
                                    endif;
                                else:
                                    if ($price == -1):
                                        $price = (float) $detail['price']; // nếu chưa set giá
                                    endif;
                                endif;
                            endif;
                        else:
                            $_price = (float) $detail['price'];
                            if ($defaultPrice == -1):
                                $defaultPrice = $_price; // nếu chưa set giá
                            else:
                                if ($_price < $defaultPrice):
                                    $defaultPrice = $_price; // nếu đã set giá thì lấy giá nhỏ nhơn
                                endif;
                            endif;
                        endif;
                    endforeach;
                endif;
                if (($price == -1) && ($defaultPrice != -1)):
                    $price = $defaultPrice;
                endif;

                if ($price == -1):
                    return ['status' => 201, 'msg' => $this->translator->translate('Delivery is not supported at your address'), 'price' => 0];
                else:
                    return ['status' => 200, 'msg' => $this->translator->translate('Success'), 'price' => $price];
                endif;

            endif;
        endif;
    }

    public function getProductsByCates($categoryIds, $limit = 12) {
        $data = [];
        if ($categoryIds) :
            $data['categories'] = $this->em->getRepository('AppEntity\AppProductCategories')->createQueryBuilder('a', 'a.id')
                    ->where('a.status=1 and a.id IN (:categories)')->setParameter('categories', $categoryIds)
                    ->select('a.id,a.name')->setMaxResults(12)
                    ->orderBy('a.ordering', 'desc')
                    ->addOrderBy('a.id', 'desc')
                    ->getQuery()
                    ->getResult();
            $_categoryIds = ($data['categories']) ? array_keys($data['categories']) : [];
            $qb = $this->em->getRepository('AppEntity\AppProducts')->createQueryBuilder('a', 'a.id');
            $qb->where('a.status = 1')->join('a.categories', 'c');
            $products = [];
            foreach ($_categoryIds as $cat) :
                $newQb = clone $qb;
                $newQb->andWhere('c.id = ' . $cat);
                $products[$cat] = $newQb->setMaxResults($limit)
                        ->orderBy('c.ordering', 'desc')
                        ->addOrderBy('a.ordering', 'desc')
                        ->addOrderBy('a.id', 'desc')
                        ->getQuery()
                        ->getResult();

            endforeach;
            $data['products'] = $products;
        else :
            return '';
        endif;

        return $data;
    }

    public function insertChild(\AppEntity\AppCategories $parent, \AppEntity\AppCategories $child): void {
        $right = (int) $parent->getRgt();

        // Dời các node bên phải

        $this->em->createQueryBuilder()
                ->update('AppEntity\AppCategories', 'a')
                ->where("c.rgt >= :right") // bao gồm cả parent
                ->setParameter('right', $right)
                ->set('a.rgt', "a.right + 2")
                ->getQuery()->execute();
        $this->em->createQueryBuilder()
                ->update('AppEntity\AppCategories', 'a')
                ->where("a.lft > :right")
                ->setParameter('right', $right)
                ->set('a.lft', "a.lft + 2")
                ->getQuery()->execute();

        // Gán giá trị cho node mới
        $child->setLft($right);
        $child->setRgt($right + 1);
        $child->setParent($parent);

        $this->em->persist($child);
        $this->flush();
    }

    public function deleteNodeAndDescendants(\AppEntity\AppCategories $node): void {
        $left = $node->getLft();
        $right = $node->getRgt();
        $width = $right - $left + 1;

        // Xoá node và con cháu
        $qb = $this->em->createQueryBuilder()->delete('AppEntity\AppCategories', 'c');
        $qb->where('c.lft BETWEEN :left AND :right')
                ->setParameters(['left' => $left, 'right' => $right])
                ->getQuery()
                ->execute();
        // Dời các node bên phải
        $this->em->createQueryBuilder()
                ->update('AppEntity\AppCategories', 'a')
                ->where("a.lft > :right")
                ->setParameter('right', $right)
                ->set('a.lft', "a.lft - {$width}")
                ->getQuery()->execute();
        $this->em->createQueryBuilder()
                ->update('AppEntity\AppCategories', 'a')
                ->where("a.rgt > :right")
                ->setParameter('right', $right)
                ->set('a.rgt', "a.rgt - {$width}")
                ->getQuery()->execute();
    }

    public function deleteNodeButKeepChildren(\AppEntity\AppCategories $node): void {
//        $left = $node->getLft();
        $right = $node->rgt;
        $width = 2;

        $parent = $node->getParent();

        // 1. Lấy các con trực tiếp và gán lại parent
        $children = $this->em->getRepository('\AppEntity\AppCategories')->createQueryBuilder('c')
                ->where('c.parent = :node')
                ->setParameter('node', $node->id)
                ->getQuery()
                ->getResult();
        if ($children):
            foreach ($children as $child):
                $child->setParent($parent);
                $this->em->persist($child);
            endforeach;
        endif;

        // 2. Xoá node hiện tại
        $this->em->remove($node);
        $this->flush();

        // 3. Cập nhật lft/rgt: dời mọi node bên phải
        // Dời các node bên phải
        $this->em->createQueryBuilder()
                ->update('AppEntity\AppCategories', 'a')
                ->where("a.lft > :right")
                ->setParameter('right', $right)
                ->set('a.lft', "a.lft - {$width}")
                ->getQuery()->execute();
        $this->em->createQueryBuilder()
                ->update('AppEntity\AppCategories', 'a')
                ->where("a.rgt > :right")
                ->setParameter('right', $right)
                ->set('a.rgt', "a.rgt - {$width}")
                ->getQuery()->execute();
    }

    public function rebuildTree(string $class = \AppEntity\AppCategories::class, $params = ['parent' => null, 'contenttype' => 'post']): void {
        $repo = $this->em->getRepository($class);

        $rootNodes = $repo->findBy($params);
        $index = 1;
        if ($rootNodes):
            foreach ($rootNodes as $root):
                $index = $this->rebuildSubtree($root, $index, 0);
            endforeach;
        endif;

        $this->flush(); // Lưu tất cả
    }

    private function rebuildSubtree($node, int $index, int $level = 0): int {
        $node->setLft($index++);
        $node->setLevel($level);
        $className = ($node instanceof \Doctrine\Persistence\Proxy) ? get_parent_class($node) : get_class($node);
        $children = $this->em->getRepository($className)->findBy(['parent' => $node->id], ['id' => 'ASC']); // Hoặc order tùy ý
        if ($children):
            foreach ($children as $child):
                $index = $this->rebuildSubtree($child, $index, $level + 1);
            endforeach;
        endif;

        $node->setRgt($index++);
        $this->em->persist($node);

        return $index;
    }

    public function checkContactSpam($data) {
        $phone = $data['phone'];
        $email = $data['email'];
        $fnameH = $data['fnameh'] ?? '';
        if ($fnameH) :// nếu có nhập cái này thì là spam
            $this->flashMessenger()->addSuccessMessage('Thank you for contacting us, we will contact you soon');
            return false;
//            $this->redirect()->toRoute('home/contact');
        endif;
        if (!$phone) :
            $this->flashMessenger()->addErrorMessage('Please enter the your phone number');
            return false;
//            $this->redirect()->toRoute('home/contact');
        endif;
        if ($this->em->getRepository(\AppEntity\AppContact::class)->findOneBy(['phone' => $phone, 'status' => -1])):
            $this->flashMessenger()->addSuccessMessage('Thank you for contacting us, we will contact you soon');
            return false;
//            return $this->redirect()->toRoute('home');
        endif;
        $delaytime = new \DateTime();
        $delaytime->sub(new \DateInterval('PT10M'));
        $checkSpam = (int) $this->em->getRepository(\AppEntity\AppContact::class)->createQueryBuilder('a', 'a.id')
                        ->where("a.phone='$phone'")
                        ->andWhere('a.created >=:created')->setParameter('created', $delaytime->format('Y-m-d H:i:s'))
                        ->select('count(a.id)')
                        ->getQuery()->getSingleScalarResult();
        if ($checkSpam):// moi submit cách đây 10p
            $this->flashMessenger()->addSuccessMessage('Thank you for contacting us, we will contact you soon');
            return false;
//            return $this->redirect()->toRoute('home');
        endif;
        if ($email):
            $checkSpam = (int) $this->em->getRepository(\AppEntity\AppContact::class)->createQueryBuilder('a', 'a.id')
                            ->where("a.email='$email'")
                            ->andWhere('a.created >=:created')->setParameter('created', $delaytime->format('Y-m-d H:i:s'))
                            ->select('count(a.id)')
                            ->getQuery()->getSingleScalarResult();
            if ($checkSpam):// moi submit cách đây 10p
                $this->flashMessenger()->addSuccessMessage('Thank you for contacting us, we will contact you soon');
                return false;
//            return $this->redirect()->toRoute('home');
            endif;
            if ($this->em->getRepository(\AppEntity\AppContact::class)->findOneBy(['email' => $email, 'status' => -1])):
                $this->flashMessenger()->addSuccessMessage('Thank you for contacting us, we will contact you soon');
                return false;
//            return $this->redirect()->toRoute('home');
            endif;
        endif;
        return true;
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

    public function writeSystemLog($entityParams = [], $changed_fields = []) {
        $entity = $entityParams['entity'] ?? '';
        $entityId = $entityParams['entityId'] ?? 0;
        $user = $this->identity();
        $action = $changed_fields['action'] ?? '';
        if (!$action):
            $params = $this->params();
            $router = $params->fromRoute();
            $action = $router['action'] ?? '';
        endif;
        $now = new \DateTime();
        $systemLog = $this->em->getRepository(\AppEntity\AppSystemLog::class)->findOneBy(['entity' => $entity, 'entityIdentity' => $entityId]);
        $content = [];
        if (!$systemLog):
            $content[] = [
                'userId' => $user->id,
                'time' => $now->format('Y-m-d H:i:s'),
                'action' => $action,
                'changed_fields' => []
            ];

            $systemLog = new \AppEntity\AppSystemLog();
            $systemLog->setName($entityParams['name'] ?? '');
            $systemLog->setEntity($entity);
            $systemLog->setEntityIdentity($entityId);
        else:
            $_content = $systemLog->getContent();
            if ($_content):
                $content = json_decode($_content, true);
            endif;
        endif;
        if ($action != 'add'):
            if (!$changed_fields):
                return false;
            endif;
            unset($changed_fields['action']);
            $content[] = [
                'userId' => $user->id,
                'time' => $now->format('Y-m-d H:i:s'),
                'action' => $action,
                'changed_fields' => $changed_fields
            ];
        endif;
        $systemLog->setContent(json_encode($content));
        $this->em->persist($systemLog);
        $this->flush();
        return true;
    }
}
