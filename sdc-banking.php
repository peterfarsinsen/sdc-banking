<?php
class SdcBanking {
	protected static $ENDPOINT = 'https://b.sdc.dk/sdc-mobile-bank/%d/app/2/';

	protected $cpr;
	protected $pinCode;
	protected $activationCode;
	protected $bankId;
	protected $lastNonce = null;

	public function __construct($cpr, $pinCode, $activationCode, $bankId) {
		$this->cpr = $cpr;
		$this->pinCode = $pinCode;
		$this->activationCode = $activationCode;
		$this->bankId = $bankId;
	}

	public function login() {
		$res = $this->post('login.json', array(
			'hashnonce' => $this->getHashNonce(),
			'username' => $this->cpr,
			'password' => $this->pinCode
		));

		return $res;
	}

	public function selectAgreement($agreementNo) {
		$res = $this->post('agreement.json', array(
			'hashnonce' => $this->getHashNonce(),
			'agreement_no' => $agreementNo
		));
	}

	public function getAccounts() {
		$res = $this->post('accounts.json', array(
			'hashnonce' => $this->getHashNonce()
		));

		return $res->accounts;
	}

	public function searchAccount($accountNo, $from, $to) {
		$res = $this->post('accountsearch.json', array(
			'hashnonce' => $this->getHashNonce(),
			'account_no' => $accountNo,
			'from' => $from,
			'to' => $to
		));

		return $res;
	}

	protected function getNonce() {
		$res = $this->get('noncegenerator.json');
		return $res->nonce;
	}

	protected function getHashNonce() {
		if(!$this->lastNonce) {
			$this->getNonce();
		}

		return sha1($this->lastNonce . ':' . $this->activationCode);
	}

	protected function getEndPoint() {
		return sprintf(self::$ENDPOINT, $this->bankId);
	}

	protected function get($url) {
		$context = stream_context_create(array(
			'http' => array(
				'method' => 'GET',
			)
		));

		$fp = fopen($this->getEndPoint() . $url, 'rb', false, $context);
		$response = stream_get_contents($fp);

		$this->cookies = $this->getCookiesFromStream($fp);

		$res = json_decode($response);

		$this->lastNonce = $res->nonce;

		return $res;
	}

	protected function getCookiesFromStream($fp) {
		$metaData = stream_get_meta_data($fp);
		$cookies = array();

		foreach($metaData['wrapper_data'] as $header) {
			if(strtolower(substr($header, 0, 10)) === 'set-cookie') {
				$line = substr($header, 0, stripos($header, ';'));
				$cookies[] = ltrim(substr($line, stripos($line, ':')), ': ');
			}
		}

		return $cookies;
	}

	protected function post($url, $params) {
		$data = http_build_query($params);
		$context = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'header' =>	"Content-Type: application/x-www-form-urlencoded; charset=UTF-8\r\n" .
							"Cookie:" . join(';', $this->cookies) . "\r\n",
							"Content-Length: " . strlen($data) . "\r\n",
							"User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_1) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.82 Safari/537.1",
				'content' => $data
			)
		));

		$fp = fopen($this->getEndPoint() . $url, 'rb', false, $context);
		$response = stream_get_contents($fp);

		$res = json_decode($response);

		$this->lastNonce = $res->nonce;

		return $res;
	}
}
?>