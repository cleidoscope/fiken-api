<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Fiken_Invoice
{
	protected $username = "XXXX";
	protected $password = "XXX";
	protected $headers = [];

	public function __construct()
	{
		$this->headers[] = 'Accept: application/hal+json, application/vnd.error+json';
		$this->headers[] = 'Content-Type: application/hal+json';
	}

	public function create_invoice($fields)
	{
		$customer = $this->customer();
		$field_string = json_encode($fields, true);

		$ch = curl_init("https://fiken.no/api/v1/companies/XXXXX/create-invoice-service"); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);
		curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($ch, CURLOPT_HEADER, true);
		$data = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		$headers = $this->get_headers_from_curl_response($data);
		$location = $headers['Location'];
		$this->send_invoice($location);
	}

	public function get_headers_from_curl_response($response)
	{
	    $headers = [];

	    $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

	    foreach (explode("\r\n", $header_text) as $i => $line)
	        if ($i === 0)
	            $headers['http_code'] = $line;
	        else
	        {
	            list ($key, $value) = explode(': ', $line);

	            $headers[$key] = $value;
	        }

	    return $headers;
	}

	public function send_invoice($location)
	{
		$fields = [
			'resource' => $location, 
			'method' => 'auto',
		];
		$field_string = json_encode($fields, true);

		$ch = curl_init("https://fiken.no/api/v1/companies/fiken-demo-nordisk-og-tidlig-rytme-enk/document-sending-service"); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);
		curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
		$data = curl_exec($ch);
	}


	public function customer()
	{
		$fields = [
			'name' => 'Clyde Escobidal', 
			'email' => 'clydewinux@gmail.com',
			'address' => [
				'country' => 'Philippines',
				'postalPlace' => 'Lipa City',
				'postalCode' => '4217',
				],
			'customer' => true,
		];
		return $this->get_customer($fields);
	}


	public function get_customer($fields)
	{
		$ch = curl_init("https://fiken.no/api/v1/companies/fiken-demo-nordisk-og-tidlig-rytme-enk/contacts"); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);;
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
		$data = curl_exec($ch);
		curl_close($ch);
		$data = json_decode($data);
		$contacts = $data->_embedded->{'https://fiken.no/api/v1/rel/contacts'};
		$item = null;
		foreach($contacts as $struct) {
		    if (
		    	isset($struct->name) && $fields['name'] == $struct->name &&
		    	isset($struct->email) && $fields['email'] == $struct->email &&
		    	isset($struct->address->country) && $fields['address']['country'] == $struct->address->country &&
		    	isset($struct->address->country) && $fields['address']['postalPlace'] == $struct->address->postalPlace &&
		    	isset($struct->address->country) && $fields['address']['postalCode'] == $struct->address->postalCode
		    	) {
		        $item = $struct;
		        break;
		    }
		}
		if($item) :
			return [
				'href' => $item->_links->self->href,
				'name' => $item->name,
			];
		else :
			return $this->create_customer($fields);
		endif;

	}
	public function create_customer($fields)
	{
		$field_string = json_encode($fields, true);
		$ch = curl_init("https://fiken.no/api/v1/companies/fiken-demo-nordisk-og-tidlig-rytme-enk/contacts"); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);
		curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);;
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
		$data = curl_exec($ch);
		curl_close($ch);
		$data = json_decode($data);
		return $this->get_customer($fields);
	}
}


$fields = [
	'issueDate' => date('Y-m-d'), 
	'dueDate' => '2017-07-08',
	'lines' => [[
		'unitNetAmount' => 3500,
		'description'=> 'Description',
		'productUrl' => 'https://fiken.no/api/v1/companies/fiken-demo-nordisk-og-tidlig-rytme-enk/products/315254434',
		]],
	'customer' => [
		'url' => $customer['href'],
		'name' => $customer['name'],
		],
	'bankAccountUrl' => 'https://fiken.no/api/v1/companies/fiken-demo-nordisk-og-tidlig-rytme-enk/bank-accounts/313581398',
];
$invoice = new Fiken_Invoice();
$invoice->create_invoice($fields);


?>
