<?php

require './config/config.php';
require '../circulation/OCLC/Auth/WSKey.php';
require '../circulation/OCLC/User.php';

//TODO functions in een apart bestand

function get_auth_header($config) {
	if (array_key_exists('wskey',$config) && array_key_exists('secret',$config)) {
		$options = array();
		if (array_key_exists('institution',$config) && array_key_exists('ppid',$config) && array_key_exists('ppid_namespace',$config)) {
			//uses OCLC provided programming to get an autorization header
			$user = new User($config['institution'], $config['ppid'], $config['ppid_namespace']);
			$options['user'] = $user;
		}
		//echo "Options: ".json_encode($options, JSON_PRETTY_PRINT); 
		if (count($options) > 0) {
	   		$wskey = new WSKey($config['wskey'], $config['secret'], $options);
    		$authorizationHeader = $wskey->getHMACSignature($config['method'], $config['url'], $options);
    	}
    	else {
		    $wskey = new WSKey($config['wskey'], $config['secret'],null);
    		$authorizationHeader = $wskey->getHMACSignature($config['token_method'], $config['token_url'], null);
		}
		//check??
		array_push($config['token_headers'],'Authorization: '.$authorizationHeader);
	}
	return $config;
}

function get_access_token($config) {
	$curl = curl_init();
	
	curl_setopt($curl, CURLOPT_URL, $config['token_url']);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $config['token_headers']);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($config['token_params']));
	//echo http_build_query($config['token_params']);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	//curl_setopt($curl, CURLOPT_, );
	//curl_setopt($curl, CURLOPT_, );

	$result = curl_exec($curl);
	$error_number = curl_errno($curl);
	//echo $error_number;
	if ($error_number) {
		$result = '{"ErrorNo": "'.$error_number.'", "Error": "'.curl_error($curl).'"}';
	}
	$config['token'] = json_decode($result,TRUE);
	array_push($config['headers'],'Authorization: Bearer '.$config['token']['access_token']);
	curl_close($curl);
	return $config;
}

function API_request($config) {
	$curl = curl_init();
	
	curl_setopt($curl, CURLOPT_URL, $config['url'].'?'.http_build_query($config['params']));
	curl_setopt($curl, CURLOPT_HTTPHEADER, $config['headers']);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_VERBOSE, true);
	$verbose = fopen('stderr.txt', 'w+');
  curl_setopt($curl, CURLOPT_STDERR, $verbose);
	//curl_setopt($curl, CURLOPT_, );
	//curl_setopt($curl, CURLOPT_, );

	$result = curl_exec($curl);
	echo 'Result: '.$result;
	$error_number = curl_errno($curl);
    echo "Error: ".$error_number." - ".curl_error($curl);
	
	if ($error_number) {
		$result = "Error: ".$error_number.": ".curl_error($curl)."\n".$result;
	}
	curl_close($curl);
	return $result;
}

$config = get_auth_header($config);

?>
<html>
	<head>
	   
	</head>
	<body>

		<p>Config:
			<pre><?php echo json_encode($config, JSON_PRETTY_PRINT);?></pre>
		</p>
<?php $config = get_access_token($config); ?>
		<p>Config with token:
			<pre><?php echo json_encode($config, JSON_PRETTY_PRINT);?></pre>
		</p>
<?php $result = API_request($config); ?>
		<p>Search:
			<pre><?php echo $result;?></pre>
		</p>
	</body>
	
</html>