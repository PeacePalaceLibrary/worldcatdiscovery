<?php

require './config/config.php';
require './OCLC/Auth/WSKey.php';
require './OCLC/User.php';
//require './OCLC/Auth/AccessToken.php';

//TODO functions in een apart bestand

function get_auth_header($config) {
	if (array_key_exists('wskey',$config) && array_key_exists('secret',$config)) {
		$options = array();
		if (array_key_exists('institution',$config) && array_key_exists('ppid',$config) && array_key_exists('ppid_namespace',$config)) {
			//uses OCLC provided programming to get an autorization header
			$user = new User($config['institution'], $config['ppid'], $config['ppid_namespace']);
			$options['user'] = $user;
		}
		if (count($options) > 0) {
	   		$wskey = new WSKey($config['wskey'], $config['secret'], $options);
    		$authorizationHeader = $wskey->getHMACSignature('GET', $config['hmac_url'], $options);
    	}
    	else {
		    $wskey = new WSKey($config['wskey'], $config['secret'],null);
    		$authorizationHeader = $wskey->getHMACSignature('GET', $config['hmac_url'], null);
		}
		//check??
		array_push($config['auth_headers'],'Authorization: '.$authorizationHeader);
	}
	return $config;
}

function get_access_token($config) {
	$curl = curl_init();
	
	curl_setopt($curl, CURLOPT_URL, $config['auth_url']);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $config['auth_headers']);
	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($config['auth_params']));
	//echo http_build_query($config['auth_params']);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	//curl_setopt($curl, CURLOPT_, );
	//curl_setopt($curl, CURLOPT_, );

	$result = curl_exec($curl);
	$error_number = curl_errno($curl);
	//echo $error_number;
	if ($error_number) {
		$result = "Error: ".$error_number.": ".curl_error($curl)."\n".$result;
	}
	curl_close($curl);
	return $result;
}

function API_request($config) {
	$curl = curl_init();
	
	curl_setopt($curl, CURLOPT_URL, $config['url']);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $config['headers']);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	//curl_setopt($curl, CURLOPT_, );
	//curl_setopt($curl, CURLOPT_, );

	$result = curl_exec($curl);
	$error_number = curl_errno($curl);
	//echo $error_number;
	if ($error_number) {
		$result = "Error: ".$error_number.": ".curl_error($curl)."\n".$result;
	}
	curl_close($curl);
	return $result;
}

if (array_key_exists('wskey',$config) && array_key_exists('secret',$config)) {
    $config = get_auth_header($config);
    $token_response = get_access_token($config);
    $token = json_decode ($token_response, TRUE);
}

?>
<html>
	<head>
	   
	</head>
	<body>

		<p>Config:
			<pre><?php echo json_encode($config, JSON_PRETTY_PRINT);?></pre>
		</p>

		<p>Token:
			<pre><?php echo json_encode($token, JSON_PRETTY_PRINT);?></pre>
		</p>
	</body>
	
</html>