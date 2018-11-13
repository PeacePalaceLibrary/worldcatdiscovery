<?php

require './config/config.php';
require './OCLC/Auth/WSKey.php';
require './OCLC/User.php';

//TODO functions in een apart bestand

function get_auth_header($config,$url,$method) {
  //get an authorization header 
  //  with wskey, secret and if necessary user data from $config
  //  for the $method and $url provided as parameters 

  $authorizationHeader = '';
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
    		$authorizationHeader = $wskey->getHMACSignature($method, $url, $options);
    	}
    	else {
		    $wskey = new WSKey($config['wskey'], $config['secret'],null);
    		$authorizationHeader = $wskey->getHMACSignature($method, $url, null);
		}
		//check??
		$authorizationHeader = 'Authorization: '.$authorizationHeader;
	}
	return $authorizationHeader;
}

function get_access_token($config) {
  $token_authorization = "";
  $authorizationHeader = get_auth_header($config,$config['token_url'],$config['token_method']);
  array_push($config['token_headers'],$authorizationHeader);

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
	$token_array = json_decode($result,TRUE);
	$token_authorization = 'Authorization: Bearer '.$token_array['access_token'];
	curl_close($curl);
	return $token_authorization;
}

function wcds_search_request($config) {
  
  $token_authorization = get_access_token($config);
  array_push($config['search_headers'],$token_authorization);
  
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $config['search_url'].'?'.http_build_query($config['search_params']));
	curl_setopt($curl, CURLOPT_HTTPHEADER, $config['search_headers']);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	
	/*
	curl_setopt($curl, CURLOPT_VERBOSE, true);
	$verbose = fopen('stderr.txt', 'w+');
	*/
	//curl_setopt($curl, CURLOPT_, );
	//curl_setopt($curl, CURLOPT_, );

	$result = curl_exec($curl);
	//echo 'Result: '.$result;
	$error_number = curl_errno($curl);
	
	if ($error_number) {
		$result = "Error: ".$error_number.": ".curl_error($curl)."\n".$result;
	}
	curl_close($curl);
	$result = json_decode($result,TRUE);
	return $result;
}

function wcds_read_request($config) {
  
  $token_authorization = get_access_token($config);
  array_push($config['search_headers'],$token_authorization);
  
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $config['read_url'].'/'.$config['read_ocn']);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $config['search_headers']);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	
	/*
	curl_setopt($curl, CURLOPT_VERBOSE, true);
	$verbose = fopen('stderr.txt', 'w+');
	*/
	//curl_setopt($curl, CURLOPT_, );
	//curl_setopt($curl, CURLOPT_, );

	$result = curl_exec($curl);
	//echo 'Result: '.$result;
	$error_number = curl_errno($curl);
  //echo "Error: ".$error_number." - ".curl_error($curl);
	
	if ($error_number) {
		$result = "Error: ".$error_number.": ".curl_error($curl)."\n".$result;
	}
	curl_close($curl);
	$result = json_decode($result,TRUE);
	return $result;
}

?>
<html>
	<head>
	   
	</head>
	<body>

		<p>Config:
			<pre><?php echo json_encode($config, JSON_PRETTY_PRINT);?></pre>
		</p>
    <?php $result = wcds_read_request($config); ?>
		<p>Read ocn: <?php echo $config['read_ocn']; ?> 
			<pre><?php echo json_encode($result, JSON_PRETTY_PRINT);?></pre>
		</p>
    <?php $result = wcds_search_request($config); ?>
		<p>Search:
			<pre><?php echo json_encode($result, JSON_PRETTY_PRINT);?></pre>
		</p>
	</body>
	
</html> 