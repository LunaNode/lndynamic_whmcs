<?php

class LNDynamic {
	static $LNDYNAMIC_URL = 'https://dynamic.lunanode.com/api/{CATEGORY}/{ACTION}/';

	function __construct($api_id, $api_key) {
		if(strlen($api_id) != 16) {
			throw new LNDAPIException('supplied api_id incorrect length, must be 16');
		}

		if(strlen($api_key) != 128) {
			throw new LNDAPIException('supplied api_key incorrect length, must be 128');
		}

		$this->api_id = $api_id;
		$this->api_key = $api_key;
		$this->partial_api_key = substr($api_key, 0, 64);
	}

	public function request($category, $action, $params = array()) {
		$url = str_replace(array('{CATEGORY}', '{ACTION}'), array($category, $action), self::$LNDYNAMIC_URL);
		$request_array = $params;
		$request_array['api_id'] = $this->api_id;
		$request_array['api_partialkey'] = $this->partial_api_key;
		$request_raw = json_encode($request_array);
		$nonce = time();
		$handler = "$category/$action/";
		$signature = hash_hmac('sha512', $handler . '|' . $request_raw . '|' . $nonce, $this->api_key);

		if($signature === false) {
			throw new LNDAPIException('hash_hmac with sha512 failed');
		}

		$data = array(
			'req' => $request_raw,
			'signature' => $signature,
			'nonce' => $nonce
		);

		$options = array(
			'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => http_build_query($data),
			)
		);
		$context  = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
		if($result === false) {
			throw new LNDAPIException('failed to perform HTTP request');
		}
		$response = json_decode($result, true);
		if(!$response) {
			throw new LNDAPIException('server gave invalid response (could not decode)');
		} else if(!isset($response['success'])) {
			throw new LNDAPIException('server gave invalid response (missing success key)');
		} else if($response['success'] !== 'yes') {
			if(isset($response['error'])) {
				throw new LNDAPIException('API error: ' . $response['error']);
			} else {
				throw new LNDAPIException('Unknown API error');
			}
		}
		return $response;
	}
}

class LNDAPIException extends Exception {}

?>
