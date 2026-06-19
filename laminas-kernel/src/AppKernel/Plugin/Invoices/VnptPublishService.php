<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of Vnpt
 *
 * @author bainguyen
 */

namespace AppKernel\Plugin\Invoices;

class VnptPublishService {

    //put your code here
    public $options;
    public $endpoint;
    public $client;
    public $accAdmin;
    public $passwordAdmin;
    public $acc;
    public $password;
    public $pattern; //Mẫu số 
    public $serial; //Ký hiệu 

    public function __construct() {

        $this->options = [
            'soap_version' => SOAP_1_2,
            'trace' => true,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
        ];
        $this->endpoint = 'https://dnitest-tt78admindemo.vnpt-invoice.com.vn/PublishService.asmx';
//        $this->client = new \SoapClient($this->endpoint, $this->options);
        $this->accAdmin = 'dnitest1';
        $this->passwordAdmin = 'TTCNTT@vasc2025';
        $this->acc = 'vnptsv';
        $this->password = '123456aA@';
        $this->pattern = '1/001';
        $this->serial = 'C25TTQ';
    }

    public function call(string $method = '', $data = null) {
        if (method_exists($this, $method)):
            return $this->$method($data);
        else:
            return $this->signIn();
        endif;
    }

    public function AdjustReplaceInvWithToken() {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <AdjustReplaceInvWithToken xmlns="http://tempuri.org/">
      <Account>{$this->accAdmin}</Account>
      <ACpass>{$this->passwordAdmin}</ACpass>
      <xmlInvData>string</xmlInvData>
      <username>{$this->acc}</username>
      <password>{$this->password}</password>
      <type>int</type>
      <pattern>{$this->pattern}</pattern>
      <serial>{$this->serial}</serial>
    </AdjustReplaceInvWithToken>
  </soap12:Body>
</soap12:Envelope>
XML;
        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/soap+xml; charset=utf-8",
            "Content-Length: " . strlen($xml),
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo "Lỗi: " . curl_error($ch);
        } else {
            $xml = simplexml_load_string($response);

// Đăng ký namespace để có thể truy cập đúng node
            $namespaces = $xml->getNamespaces(true);
            $body = $xml->children($namespaces['soap'])->Body;

// Lấy phần tử `signinresponse` trong namespace `http://tempuri.org/`
            $signinResponse = $body->children('http://tempuri.org/')->AdjustReplaceInvWithTokenResponse;
// Lấy kết quả bên trong thẻ <signinresult>
            $result = (string) $signinResponse->AdjustReplaceInvWithTokenResult;
        }
        curl_close($ch);
        return $result;
    }

    public function AppClientSyncInfoSystem() {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <AppClientSyncInfoSystem xmlns="http://tempuri.org/">
      <Account>{$this->accAdmin}</Account>
      <ACpass>{$this->passwordAdmin}</ACpass>
      <req>string</req>
    </AppClientSyncInfoSystem>
  </soap12:Body>
</soap12:Envelope>
XML;
        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/soap+xml; charset=utf-8",
            "Content-Length: " . strlen($xml),
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo "Lỗi: " . curl_error($ch);
        } else {
            $xml = simplexml_load_string($response);

// Đăng ký namespace để có thể truy cập đúng node
            $namespaces = $xml->getNamespaces(true);
            $body = $xml->children($namespaces['soap'])->Body;

// Lấy phần tử `signinresponse` trong namespace `http://tempuri.org/`
            $signinResponse = $body->children('http://tempuri.org/')->AppClientSyncInfoSystemResponse;
// Lấy kết quả bên trong thẻ <signinresult>
            $result = (string) $signinResponse->AppClientSyncInfoSystemResult;
        }
        curl_close($ch);
        return $result;
    }

    public function CancelInvoiceWithToken() {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <CancelInvoiceWithToken xmlns="http://tempuri.org/">
       <Account>{$this->accAdmin}</Account>
      <ACpass>{$this->passwordAdmin}</ACpass>
      <xmlData>string</xmlData>
      <username>{$this->acc}</username>
      <password>{$this->password}</password>
       <pattern>{$this->pattern}</pattern>
    </CancelInvoiceWithToken>
  </soap12:Body>
</soap12:Envelope>
XML;
        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/soap+xml; charset=utf-8",
            "Content-Length: " . strlen($xml),
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo "Lỗi: " . curl_error($ch);
        } else {
            $xml = simplexml_load_string($response);

// Đăng ký namespace để có thể truy cập đúng node
            $namespaces = $xml->getNamespaces(true);
            $body = $xml->children($namespaces['soap'])->Body;

// Lấy phần tử `signinresponse` trong namespace `http://tempuri.org/`
            $signinResponse = $body->children('http://tempuri.org/')->CancelInvoiceWithTokenResponse;
// Lấy kết quả bên trong thẻ <signinresult>
            $result = (string) $signinResponse->CancelInvoiceWithTokenResult;
        }
        curl_close($ch);
        return $result;
    }

    public function CancelPublishInvoice() {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <CancelPublishInvoice xmlns="http://tempuri.org/">
      <Account>{$this->accAdmin}</Account>
      <ACpass>{$this->passwordAdmin}</ACpass>
       <username>{$this->acc}</username>
      <password>{$this->password}</password>
      <Pattern>{$this->pattern}</Pattern>
      <Serial>{$this->serial}</Serial>
    </CancelPublishInvoice>
  </soap12:Body>
</soap12:Envelope>
XML;
        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/soap+xml; charset=utf-8",
            "Content-Length: " . strlen($xml),
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo "Lỗi: " . curl_error($ch);
        } else {
            $xml = simplexml_load_string($response);

// Đăng ký namespace để có thể truy cập đúng node
            $namespaces = $xml->getNamespaces(true);
            $body = $xml->children($namespaces['soap'])->Body;

// Lấy phần tử `signinresponse` trong namespace `http://tempuri.org/`
            $signinResponse = $body->children('http://tempuri.org/')->CancelPublishInvoiceResponse;
// Lấy kết quả bên trong thẻ <signinresult>
            $result = (string) $signinResponse->CancelPublishInvoiceResult;
        }
        curl_close($ch);
        return $result;
    }
    public function ConvertForVerify() {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <ConvertForVerify xmlns="http://tempuri.org/">
      <Account>{$this->accAdmin}</Account>
      <Pass>{$this->passwordAdmin}</Pass>
      <Id>int</Id>
    </ConvertForVerify>
  </soap12:Body>
</soap12:Envelope>
XML;
        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/soap+xml; charset=utf-8",
            "Content-Length: " . strlen($xml),
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo "Lỗi: " . curl_error($ch);
        } else {
            $xml = simplexml_load_string($response);

// Đăng ký namespace để có thể truy cập đúng node
            $namespaces = $xml->getNamespaces(true);
            $body = $xml->children($namespaces['soap'])->Body;

// Lấy phần tử `signinresponse` trong namespace `http://tempuri.org/`
            $signinResponse = $body->children('http://tempuri.org/')->ConvertForVerifyResponse;
// Lấy kết quả bên trong thẻ <signinresult>
            $result = (string) $signinResponse->ConvertForVerifyResult;
        }
        curl_close($ch);
        return $result;
    }
    public function DeleteCertificate() {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <DeleteCertificate xmlns="http://tempuri.org/">
      <Account>{$this->accAdmin}</Account>
      <ACpass>{$this->passwordAdmin}</ACpass>
      <id>int</id>
    </DeleteCertificate>
  </soap12:Body>
</soap12:Envelope>
XML;
        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/soap+xml; charset=utf-8",
            "Content-Length: " . strlen($xml),
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo "Lỗi: " . curl_error($ch);
        } else {
            $xml = simplexml_load_string($response);

// Đăng ký namespace để có thể truy cập đúng node
            $namespaces = $xml->getNamespaces(true);
            $body = $xml->children($namespaces['soap'])->Body;

// Lấy phần tử `signinresponse` trong namespace `http://tempuri.org/`
            $signinResponse = $body->children('http://tempuri.org/')->DeleteCertificateResponse;
// Lấy kết quả bên trong thẻ <signinresult>
            $result = (string) $signinResponse->DeleteCertificateResult;
        }
        curl_close($ch);
        return $result;
    }
    public function GetCertificates() {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <GetCertificates xmlns="http://tempuri.org/">
      <userName>{$this->accAdmin}</userName>
      <password>{$this->passwordAdmin}</password>
    </GetCertificates>
  </soap12:Body>
</soap12:Envelope>
XML;
        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/soap+xml; charset=utf-8",
            "Content-Length: " . strlen($xml),
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo "Lỗi: " . curl_error($ch);
        } else {
            $xml = simplexml_load_string($response);

// Đăng ký namespace để có thể truy cập đúng node
            $namespaces = $xml->getNamespaces(true);
            $body = $xml->children($namespaces['soap'])->Body;

// Lấy phần tử `signinresponse` trong namespace `http://tempuri.org/`
            $signinResponse = $body->children('http://tempuri.org/')->GetCertificatesResponse;
// Lấy kết quả bên trong thẻ <signinresult>
            $result = (string) $signinResponse->GetCertificatesResult;
        }
        curl_close($ch);
        return $result;
    }

    public function signIn() {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <SignIn xmlns="http://tempuri.org/">
      <userName>{$this->accAdmin}</userName>
      <pass>{$this->passwordAdmin}</pass>
    </SignIn>
  </soap12:Body>
</soap12:Envelope>
XML;
        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/soap+xml; charset=utf-8",
            "Content-Length: " . strlen($xml),
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo "Lỗi: " . curl_error($ch);
        } else {
            $xml = simplexml_load_string($response);

// Đăng ký namespace để có thể truy cập đúng node
            $namespaces = $xml->getNamespaces(true);
            $body = $xml->children($namespaces['soap'])->Body;

// Lấy phần tử `signinresponse` trong namespace `http://tempuri.org/`
            $signinResponse = $body->children('http://tempuri.org/')->SignInResponse;
// Lấy kết quả bên trong thẻ <signinresult>
            $result = (string) $signinResponse->SignInResult;
        }
        curl_close($ch);
        return ($result == 'INFO_LOGIN_SUCCESS');
    }

    public function ImportAndPublishInv() {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <ImportAndPublishInv xmlns="http://tempuri.org/">
      <Account>{$this->accAdmin}</Account>
      <ACpass>{$this->passwordAdmin}</ACpass>
      <xmlInvData>string</xmlInvData>
      <username>{$this->acc}</username>
      <password>{$this->password}</password>
      <pattern>{$this->pattern}</pattern>
      <serial>{$this->serial}</serial>
      <convert>0</convert>
    </ImportAndPublishInv>
  </soap12:Body>
</soap12:Envelope>
XML;

        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/soap+xml; charset=utf-8",
            "Content-Length: " . strlen($xml),
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        $response = curl_exec($ch);
        echo '<pre>';
        print_r($response);
        echo '</pre>';
        exit;
        if (curl_errno($ch)) {
            echo "Lỗi: " . curl_error($ch);
        } else {
            echo "Phản hồi: \n" . $response;
        }
        curl_close($ch);
    }
}
