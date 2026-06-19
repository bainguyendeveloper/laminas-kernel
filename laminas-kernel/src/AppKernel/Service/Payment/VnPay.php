<?php

namespace AppKernel\Service\Payment;

/**
 * Description of PayPal
 *
 * @author bainguyen
 */
class VnPay {

    protected $client;
    protected string $clientId;
    protected string $secret;
    protected string $baseUrl;

    public function __construct(string $clientId, string $secret, string $baseUrl) {
        $this->clientId = $clientId;
        $this->secret = $secret;

        $this->baseUrl = $baseUrl;

        $this->client = new \GuzzleHttp\Client();
    }

    public function getAccessToken(): string {
        $response = $this->client->post(
                $this->baseUrl . '/v1/oauth2/token',
                [
                    'auth' => [
                        $this->clientId,
                        $this->secret
                    ],
                    'form_params' => [
                        'grant_type' => 'client_credentials'
                    ]
                ]
        );

        $data = json_decode(
                $response->getBody()->getContents(),
                true
        );

        return $data['access_token'];
    }

    public function createUrl($params = []): array {
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
        date_default_timezone_set('Asia/Ho_Chi_Minh');

        $vnp_Url = $this->baseUrl;

        $vnp_TmnCode = $this->clientId; //Mã website tại VNPAY 
        $vnp_HashSecret = $this->secret; //Chuỗi bí mật

        $requestId = trim($params['requestId'] ?? '');
        $vndAmount = (float) ($params['amount'] ?? 0);

        $vnp_Returnurl = trim($params['returnUrl'] ?? "https://localhost/vnpay_php/vnpay_return.php");
//        $cancelUrl = trim($params['cancelUrl'] ?? '');
//        $now = new \DateTime();
        $exp = new \DateTime('+10mins');

        $vnp_TxnRef = $requestId; //Mã đơn hàng. Trong thực tế Merchant cần insert đơn hàng vào DB và gửi mã này 
//        sang VNPAY
//        $vnp_OrderInfo = $params['order_desc'] ?? 'test don hang';
//        $vnp_OrderType = 'other'; //$params['order_type']??'Loại đơn hàng';
//        $vnp_Amount = $vndAmount * 100;
//        $vnp_Locale = 'vn';
        $vnp_BankCode = 'VNBANK';
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        //Add Params of 2.0.1 Version
        $vnp_ExpireDate = $exp->format('YmdHis');
        //Billing
//        $vnp_Bill_Mobile = $params['txt_billing_mobile'] ?? '09';
//        $vnp_Bill_Email = $params['txt_billing_email'] ?? 'bai@yahoo.com';
//        $fullName = trim($params['txt_billing_fullname'] ?? 'Nguyen Van Bai');
//        if (isset($fullName) && trim($fullName) != '') {
//            $name = explode(' ', $fullName);
//            $vnp_Bill_FirstName = array_shift($name);
//            $vnp_Bill_LastName = array_pop($name);
//        }
//        $vnp_Bill_Address = $params['txt_inv_addr1'] ?? 'dia chi';
//        $vnp_Bill_City = $params['txt_bill_city'] ?? 'ho chi minh';
//        $vnp_Bill_Country = $params['txt_bill_country'] ?? 'viet nam';
//        $vnp_Bill_State = $params['txt_bill_state'] ?? 'ho chi minh';
        // Invoice
//        $vnp_Inv_Phone = $params['txt_inv_mobile'] ?? 'inv dia chi';
//        $vnp_Inv_Email = $params['txt_inv_email'] ?? 'inv@yahoo.com';
//        $vnp_Inv_Customer = $params['txt_inv_customer'] ?? 'bai nguyen c';
//        $vnp_Inv_Address = $params['txt_inv_addr1'] ?? 'addres 1';
//        $vnp_Inv_Company = $params['txt_inv_company'] ?? 'com 1';
//        $vnp_Inv_Taxcode = $params['txt_inv_taxcode'] ?? '012312312';
//        $vnp_Inv_Type = $params['cbo_inv_type'] ?? '';
        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_Command" => "pay",
            "vnp_TmnCode" => trim($vnp_TmnCode),
            "vnp_Amount" => (int) ($vndAmount * 100),
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => "Thanh toan don hang",
            "vnp_OrderType" => "other",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_ExpireDate" => $vnp_ExpireDate,
            "vnp_TxnRef" => $vnp_TxnRef,
        ];

        if (isset($vnp_BankCode) && $vnp_BankCode != ""):
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        endif;
//        if (isset($vnp_Bill_State) && $vnp_Bill_State != "") {
//            $inputData['vnp_Bill_State'] = $vnp_Bill_State;
//        }
        //var_dump($inputData);
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value):
            if ($i == 1):
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            else:
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            endif;
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        endforeach;

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)):
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret); //  
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        endif;
        return ['href' => $vnp_Url];
    }

    public function captureToken(array $params = []): array {
        $_datas = [];
        $vnp_SecureHash = $params['vnp_SecureHash'];
        $inputData = [];
        foreach ($params as $key => $value):
            if (substr($key, 0, 4) == "vnp_") :
                $inputData[$key] = $value;
                $_datas[$key] = $value;
            endif;
        endforeach;

        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value):
            if ($i == 1):
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            else:
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            endif;
        endforeach;

        $secureHash = hash_hmac('sha512', $hashData, $this->secret);
        if ($secureHash == $vnp_SecureHash):
            $_datas['status'] = $params['vnp_ResponseCode'];
            if ($params['vnp_ResponseCode'] == '00') :
                $_datas['msg'] = 'Giao dịch thành công';
            else:
                $_datas['error'] = 'Giao dịch không thành công';
            endif;
        else:
            $_datas['error'] = 'Giao dịch không thành công';
        endif;
         return $_datas;
    }
}
