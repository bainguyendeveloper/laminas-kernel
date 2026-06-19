<?php

namespace AppKernel\Traits;

trait UsersAwareTrait {

    public function generatorAccessToken($leng = 64) {
        return bin2hex(openssl_random_pseudo_bytes($leng));
    }

    public function generatorToken($data = '') {
        $now = new \DateTime();
//        $expired->add(new \DateInterval('P' . $dateExpired . 'D'));
        return $this->encrypt_decrypt('encrypt', $data . '|' . $now->getTimestamp());
//        return bin2hex(openssl_random_pseudo_bytes($leng));
    }

    function encrypt_decrypt($action = 'encrypt', $string = '') {
        $output = '';
        $encrypt_method = "AES-256-CBC";
        $secret_key = 'HOMEANDLIFE';
        $secret_iv = '0969801381';
        // hash
        $key = hash('sha256', $secret_key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secret_iv), 0, 16);
        if ($action == 'encrypt'):
            $output = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
        elseif ($action == 'decrypt'):
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        endif;
        return $output;
    }

    function encrypt_kms_data($action = 'encrypt', $string = '', $dk_from_kms = '') {
        $encrypt_method = "aes-256-gcm";
        // Key phải là chuỗi nhị phân từ DK của KMS
        $key = base64_decode($dk_from_kms);

        if ($action == 'encrypt'):
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($encrypt_method));
            // GCM cần biến tag để xác thực (pass by reference)
            $tag = '';
            $ciphertext = openssl_encrypt($string, $encrypt_method, $key, OPENSSL_RAW_DATA, $iv, $tag);

            // Lưu trữ cả IV và Tag cùng với dữ liệu mã hóa
            return base64_encode($iv . $tag . $ciphertext);
        elseif ($action == 'decrypt'):
            $data = base64_decode($string);
            $iv_len = openssl_cipher_iv_length($encrypt_method);
            $tag_len = 16;

            $iv = substr($data, 0, $iv_len);
            $tag = substr($data, $iv_len, $tag_len);
            $ciphertext = substr($data, $iv_len + $tag_len);

            return openssl_decrypt($ciphertext, $encrypt_method, $key, OPENSSL_RAW_DATA, $iv, $tag);
        endif;
    }

    public function generatorAccessTokenUser($data = '', $dateExpired = 7, $leng = 32) {
        $expired = new \DateTime();
        $expired->add(new \DateInterval('P' . $dateExpired . 'D'));
        return $this->encrypt_decrypt('encrypt', $data . '|' . $expired->getTimestamp());
//        return bin2hex(openssl_random_pseudo_bytes($leng));
    }

    public function getUserByAccessToken($data = []) {
        if (!isset($data['accessToken']) ||
                !($data['accessToken']) ||
                !(is_string($data['accessToken']))
        ):
            return $this->display(
                            [
                                'code' => 100,
                                'reLogin' => 1,
                                'msg' => 'User information not found.'
                            ]
                    );
        endif;
        $dataUserDecrypt = $this->encrypt_decrypt('decrypt', $data['accessToken']);
        $dataUser = ['id' => 0];
        if ($dataUserDecrypt):
            $_dataUserDecrypt = explode('|', $dataUserDecrypt);
            $dataUser = json_decode($_dataUserDecrypt[0], true);
        endif;
        $user = $this->em->getRepository(\AppEntity\AppCustomers::class)
                        ->createQueryBuilder('a')
//                        ->where('a.accessToken = :accessToken')
                        ->where('a.id=' . $dataUser['id'])
//                        ->setParameters([
//                            'accessToken' => trim($data['accessToken']),
//                        ])
                        ->andWhere('a.state=1')
                        ->setMaxResults(1)
                        ->getQuery()->getOneOrNullResult();
        if (!$user):
            return $this->display(
                            [
                                'code' => 201,
                                'reLogin' => 1,
                                'msg' => 'Your login session has expired, please log in again.'
                            ]
                    );

        endif;
        return ['user' => $user, 'id' => $dataUser['id']];
    }

    public function sendNotification($users = [], $heading = '', $content = '', $reflink = '', $sentfrom = null) {
        if (!$users):
            return false;
        endif;
        foreach ($users as $user):
            $notification = new \AppEntity\AppNotifications();
            $notification->setContent($content)
                    ->setCreated(new \DateTime())
                    ->setReflink($reflink)
                    ->setHeading($heading)
                    ->setSentfrom($sentfrom)
                    ->setUser($user);
            $this->em->persist($notification);
        endforeach;

        return $this->em->flush();
    }

    public function updateSubcription(\AppEntity\AppCustomers $customer) {
        $now = new \DateTime();
        $cid = $customer->getId();
        $order = $this->em->getRepository(\AppEntity\AppBusinessPackageOrder::class)->createQueryBuilder('a', 'a.id')
                        ->where('a.status=1 and a.customer=' . $cid)
                        ->andWhere('a.fromDate <=:now')
                        ->andWhere('(a.toDate is null or a.toDate >=:now or a.toDate is null)')
                        ->setParameter('now', $now->format('Y-m-d H:i:s'))
                        ->setMaxResults(1)->getQuery()->getOneOrNullResult();
        $subscription = $this->em->getRepository(\AppEntity\AppBusinessPackageSubscriptions::class)->createQueryBuilder('a', 'a.id')
                        ->where('a.customer=' . $cid)
                        ->setMaxResults(1)->getQuery()->getOneOrNullResult();
        $lastId = ($subscription) ? $subscription->id : 0;
        if ($subscription):
            $qb = $this->em->createQueryBuilder()->delete(\AppEntity\AppBusinessPackageSubscriptions::class, 'c');
            $qb->where('c.customer=' . $cid)->getQuery()->execute();
        endif;

        if ($order):
            $item = new \AppEntity\AppBusinessPackageSubscriptions();
            if ($lastId):
                $item->setId($lastId);
            endif;
            $item->setCustomer($customer);
            $item->setFromDate($order->fromDate);
            $item->setToDate($order->toDate);
            $item->setMaxNumberBranch((int) $order->maxNumberBranch);
            $item->setMaxNumberCompany((int) $order->maxNumberCompany);
            $item->setMaxNumberStaff((int) $order->maxNumberStaff);
            $item->setMaxNumberVehicles((int) $order->maxNumberVehicles);
            $item->setPackageOrder($order);
            $item->setPackage($order->package);
            $this->em->persist($item);
            $this->em->flush();
        endif;
    }

    public function getCustomerSubcription(\AppEntity\AppCustomers $customer) {
        $cid = $customer->getId();
        $now = new \DateTime();
        return $this->em->getRepository(\AppEntity\AppBusinessPackageSubscriptions::class)->createQueryBuilder('a', 'a.id')
                        ->where('a.customer=' . $cid)
                        ->andWhere('a.fromDate <=:now')
                        ->andWhere('(a.toDate is null or a.toDate >=:now or a.toDate is null)')
                        ->setParameter('now', $now->format('Y-m-d H:i:s'))
                        ->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    public function decodeAccessToken(string $accessToken = '') {
        if (!$accessToken):
            throw new \Exception('Sai accessToken');
        endif;
        $rawMasterKey = $this->config['settings']['security']['masterKey'] ?? '';
        if (!$rawMasterKey):
            throw new \Exception('Chưa setup Master Key');
        endif;
        $kms = new \AppKernel\Service\Kms($rawMasterKey);

        $masterKey = $kms->getMasterKey();
        $response = \Firebase\JWT\JWT::decode($accessToken, new \Firebase\JWT\Key($masterKey, 'HS256'));
        $data = $response?->data ?: '';
        $edk = $response?->edk ?: '';

        $key = $kms->decryptDataKey($edk);
        $_payload = $this->encrypt_kms_data('decrypt', $data, $key);
        return json_decode($_payload, true);
    }

    public function rotateToken($payload = [], $exp = 3600) {
        $rawMasterKey = $this->config['settings']['security']['masterKey'] ?? '';
        if (!$rawMasterKey):
            throw new \Exception('Chưa setup Master Key');
        endif;
        $kms = new \AppKernel\Service\Kms($rawMasterKey);
        $keys = $kms->generateDataKey();
        $masterKey = $kms->getMasterKey();
        $time = time();
        $payload['iss'] = 'freelancer';
        $payload['iat'] = $time;
        $payload['exp'] = $time + $exp;
        $payload['jti'] = bin2hex(random_bytes(16)); // Token ID duy nhất để chống Replay Attack
        $_payload = $this->encrypt_kms_data('encrypt', json_encode($payload), $keys['key']);
        return \Firebase\JWT\JWT::encode(['data' => $_payload, 'edk' => $keys['secret_key']], $masterKey, 'HS256');
    }
}
