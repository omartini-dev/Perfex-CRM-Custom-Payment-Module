<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Eft_gateway extends App_gateway
{
    protected $endpoint = 'https://eft.ppay.io/api/v1/payment-key';
    public function __construct()
    {
        /**
        * Call App_gateway __construct function
        */
        parent::__construct();

        /**
         * Gateway unique id - REQUIRED
	 * 
         * * The ID must be alphanumeric
         * * The filename (Example_gateway.php) and the class name must contain the id as ID_gateway
         * * In this case our id is "example"
         * * Filename will be Example_gateway.php (first letter is uppercase)
         * * Class name will be Example_gateway (first letter is uppercase)
         */
        $this->setId('eft');

        /**
         * REQUIRED
         * Gateway name
         */
        $this->setName('Instant EFT');

        /**
         * Add gateway settings
         * You can add other settings here 
         * to fit for your gateway requirements
         *
         * Currently only 3 field types are accepted for gateway
         *
         * 'type'=>'yes_no'
         * 'type'=>'input'
         * 'type'=>'textarea'
         *
         */
        $this->setSettings(array(
            array(
                'name'      => 'logourl',
                'label'     => 'Logo URL',
            ),
            array(
                'name'      => 'username',
                'encrypted' => true,
                'label'     => 'Username',
            ),
            array(
                'name'      => 'password',
                'encrypted' => true,
                'label'     => 'Password',
            ),
            array(
                'name'      => 'merchant_reference',
                'encrypted' => true,
                'label'     => 'Merchant reference',
            ),
            array(
                'name'          => 'description_dashboard',
                'label'         => 'settings_paymentmethod_description',
                'type'          => 'textarea',
                'default_value' => 'Payment for Invoice {invoice_number}',
            ),
            array(
                'name'          => 'currencies',
                'label'         => 'settings_paymentmethod_currencies',
                'default_value' => 'EUR,USD',
            ),
        ));

    }

    /**
     * Each time a customer click PAY NOW button on the invoice HTML area, the script will process the payment via this function.
     * You can show forms here, redirect to gateway website, redirect to Codeigniter controller etc..
     * @param  array $data - Contains the total amount to pay and the invoice information
     * @return mixed
     */
    public function process_payment($data)
    {
        $redUrl = site_url('eft_module/make_payment?invoiceid='
            . $data['invoiceid']
            . '&total=' . $data['amount']
            . '&hash=' . $data['invoice']->hash);

        redirect($redUrl);
    }
    public function generate_token($amount, $success_url, $error_url, $cancel_url){
        $data['amount'] = $amount;
        $data['success_url'] = $success_url;
        $data['error_url'] = $error_url;
        $data['cancel_url'] = $cancel_url;
        $data['merchant_reference'] = $this->decryptSetting('merchant_reference');
        $userdata['username'] = $this->decryptSetting('username');
        $userdata['password'] = $this->decryptSetting('password');
        $res = $this->api_call('', $data, $userdata);
        return [json_decode($res), $data['merchant_reference']];
    }
    public function getLogo() {
        return $this->getSetting('logourl');
    }
    private function api_call($path, array $data=null, $userdata) 
    {
        $path = (string) $path;
        $data = (array) $data;
        $headers = $this->build_curl_headers();
        $request_url = $this-> build_api_call_url($path);


        $options = array();
        $options[CURLOPT_HTTPHEADER] = $headers;
        $options[CURLOPT_RETURNTRANSFER] = true;
        $options[CURLOPT_POST] = 1;
        $options[CURLOPT_POSTFIELDS] = http_build_query($data);
        $options[CURLOPT_USERPWD] = $userdata['username'].':'.$userdata['password'];

        // $options[CURLOPT_VERBOSE] = true;
        $options[CURLOPT_URL] = $request_url;
        $options[CURLOPT_SSL_VERIFYPEER] = true;
        
        $this->curl = curl_init();
        $setopt = curl_setopt_array($this->curl, $options);
        $response = curl_exec($this->curl);
        $headers = curl_getinfo($this->curl);

        $error_number = curl_errno($this->curl);
        $error_message = curl_error($this->curl);

        if($error_number != 0){

            if($error_number == 60){
                throw new \Exception("Something went wrong. cURL raised an error with number: $error_number and message: $error_message. " .
                                    "Please check http://stackoverflow.com/a/21114601/846892 for a fix." . PHP_EOL);
            }
            else{
                throw new \Exception("Something went wrong. cURL raised an error with number: $error_number and message: $error_message." . PHP_EOL);
            }
        }

        curl_close($this->curl);
        return $response;
    }
    private function build_curl_headers() 
    {
        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        return $headers;        
    }

    /**
    * @param string $path
    * @return string adds the path to endpoint with.
    */
    private function build_api_call_url($path)
    {
        if (strpos($path, '/?') === false and strpos($path, '?') === false) {
            return $this->endpoint . $path ;
        }
        return $this->endpoint . $path;

    }
}