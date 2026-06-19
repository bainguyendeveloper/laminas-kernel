<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace AppKernel\Service;

/**
 * Description of Kms
 *
 * @author bainguyen
 */
class Kms {
    private $masterKey;
    private $cipher = "aes-256-gcm";

    public function __construct($rawMasterKey) {
        // Chuyển chuỗi text thành key 256-bit chuẩn
        $this->masterKey = hash('sha256', $rawMasterKey, true);
    }

    /**
     * Bước 1: Tạo Data Key mới
     * Trả về: ['dk' => '...', 'edk' => '...']
     */
    public function generateDataKey() {
        // 1. Tạo một Data Key (DK) ngẫu nhiên 32 bytes
        $dk = openssl_random_pseudo_bytes(32);

        // 2. Mã hóa DK này bằng Master Key để tạo ra EDK
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher));
        $tag='';
        $edkRaw = openssl_encrypt($dk, $this->cipher, $this->masterKey, OPENSSL_RAW_DATA, $iv, $tag);

        // 3. Đóng gói EDK (bao gồm IV, Tag và Dữ liệu mã hóa) dưới dạng base64 để lưu trữ
        $edkSafe = base64_encode($iv . $tag . $edkRaw);

        return [
            'key'  => base64_encode($dk), // Trả về để App dùng mã hóa data
            'secret_key' => $edkSafe            // Trả về để App lưu vào Database
        ];
    }
    
    /**
     * Bước 2: Giải mã EDK để lấy lại DK
     */
    public function decryptDataKey($edkSafe) {
        $data = base64_decode($edkSafe);
        
        $ivLen = openssl_cipher_iv_length($this->cipher);
        $tagLen = 16; // Độ dài mặc định của GCM tag
        
        // Tách các thành phần ra khỏi chuỗi EDK
        $iv = substr($data, 0, $ivLen);
        $tag = substr($data, $ivLen, $tagLen);
        $edkRaw = substr($data, $ivLen + $tagLen);

        // Giải mã dùng Master Key
        $dk = openssl_decrypt($edkRaw, $this->cipher, $this->masterKey, OPENSSL_RAW_DATA, $iv, $tag);

        if ($dk === false) {
            throw new Exception("Decryption failed! Incorrect Master Key or modified Secret Key");
        }

        return base64_encode($dk);
    }
    public function getMasterKey(){
        return $this->masterKey;
    }
}