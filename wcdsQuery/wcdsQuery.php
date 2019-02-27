<?php
require_once './OCLC/Auth/WSKey.php';
require_once './OCLC/User.php';

/**
* A class that represents a pulllist
*/
class DiscoveryQuery {

  private $error_log = __DIR__.'/../search_error';
  private $logging = 'all'; //'none','errors','all' (not yet implemented

  //must be provided as parameters in $pulllist = new Pulllist($wskey,$secret,$ppid), see __construct
  private $wskey = null;
  private $secret = null;

  private $token_url = "https://authn.sd00.worldcat.org/oauth2/accessToken";
  private $token_method = 'POST';
  private $token_POST = TRUE;
  private $token_params = ['grant_type' => 'client_credentials',
                      'authenticatingInstitutionId' => '57439',
                      'contextInstitutionId' => '57439',
                      'scope' => "WorldCatDiscoveryAPI"
                      ];
  private $token_headers = ["Accept: application/json"];
  
  private $search_url = "https://beta.worldcat.org/discovery/bib/search";
  public $search_params = [];
  private $search_method = 'GET';
  private $search_headers = [];
  private $is_json = TRUE;
  public $search_result = [];
  public $list = null;

  public function __construct($wskey,$secret) {
    //oclc business

    $this->wskey = $wskey;
    $this->secret = $secret;
  }

  public function __toString(){
    //create an array and return json_encoded string
    $json = [
    'error_log' => $this->error_log,
    'logging '=> $this->logging,
    'wskey' => $this->wskey,
    'secret' => $this->secret,
    'token_url' => $this->token_url,
    'token_method' => $this->token_method,
    'token_POST' => $this->token_POST,
    'token_params' => $this->token_params,
    'token_headers' => $this->token_headers,
    'search_url' => $this->search_url,
    'search_params' => $this->search_params,
    'search_method' => $this->search_method,
    'search_headers' => $this->search_headers,
    'is_json' => $this->is_json,
    'search_result' => $this->search_result,
    ];
    return json_encode($json, JSON_PRETTY_PRINT);
  }

  public function log_entry($t,$c,$m) {
    $this->errors[] = date("Y-m-d H:i:s")." $t [$c] $m";
    $name = $this->error_log.'.'.date("Y-W").'.log';
    return file_put_contents($name, date("Y-m-d H:i:s")." $t [$c] $m\n", FILE_APPEND);
  }

  private function get_auth_header($url,$method) {
    //get an authorization header
    //  with wskey, secret and if necessary user data from $config
    //  for the $method and $url provided as parameters

    $authorizationHeader = '';
    if ($this->wskey && $this->secret) {
      $wskeyObj = new WSKey($this->wskey, $this->secret,null);
      $authorizationHeader = $wskeyObj->getHMACSignature($method, $url, null);
      $authorizationHeader = 'Authorization: '.$authorizationHeader;
    }
    else {
      $this->log_entry('Error','get_auth_header','No wskey and/or no secret!');
    }
    return $authorizationHeader;
  }

  private function get_access_token_authorization() {
    $token_authorization = "";
    $authorizationHeader = $this->get_auth_header($this->token_url,$this->token_method);
    if (strlen($authorizationHeader) > 0) {
      array_push($this->token_headers,$authorizationHeader);
    }
    else {
      $this->log_entry('Error','get_access_token_authorization','No authorization header created!');
    }

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $this->token_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $this->token_headers);
    curl_setopt($curl, CURLOPT_POST, $this->token_POST);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($this->token_params));
    //echo http_build_query($this->token_params);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt($curl, CURLOPT_, );
    //curl_setopt($curl, CURLOPT_, );

    $result = curl_exec($curl);
    $error_number = curl_errno($curl);
    $error_msg = curl_error($curl);
    curl_close($curl);

    if ($result === FALSE) {
      $this->log_entry('Error','get_access_token_authorization','No result on cUrl request!');
      if ($error_number) $this->log_entry('Error','get_access_token_authorization',"No result, cUrl error [$error_number]: $error_msg");
      return FALSE;
    }
    else {
      if (strlen($result) == 0) {
        $this->log_entry('Error','get_access_token_authorization','Empty result on cUrl request!');
        if ($error_number) {
          $this->log_entry('Error','get_access_token_authorization',"Empty result, cUrl error [$error_number]: $error_msg");
        }
        return FALSE;
      }
      else {
        if ($error_number) {
          $this->log_entry('Error','get_access_token_authorization',"Result but still cUrl error [$error_number]: $error_msg");
        }
        $token_array = json_decode($result,TRUE);
        $json_errno = json_last_error();
        $json_errmsg = json_last_error_msg();
        if ($json_errno == JSON_ERROR_NONE) {
          if (array_key_exists('access_token',$token_array)){
            $token_authorization = 'Authorization: Bearer '.$token_array['access_token'];
          }
          else {
            $this->log_entry('Error','get_access_token_authorization',"No access_token returned (curl result: ".$result.")");
            return FALSE;
          }
        }
        else {
          $this->log_entry('Error','get_access_token_authorization',"json_decode error [$json_errno]: $json_errmsg");
          return FALSE;
        }
      }
    }
    return $token_authorization;
  }

  public function wcds_search_request($headers,$params) {

    $token_authorization = $this->get_access_token_authorization();
    array_push($this->search_headers,$token_authorization);
    foreach ($headers as $header) array_push($this->search_headers,$header);
    
    
    foreach ($params as $k => $v) $this->search_params[$k] = $v;
    
    $urlparts = array();
    foreach ($this->search_params as $k => $v) {
      if (is_array($v)) {
        foreach ($v as $w) $urlparts[] = $k.'='.urlencode($w);
      }
      else {
        $urlparts[] = $k.'='.urlencode($v);
      }
    }
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $this->search_url.'?'.implode('&',$urlparts));
    
    echo '<pre>'.$this->search_url.'?'.implode('&',$urlparts).'</pre>';
    
    curl_setopt($curl, CURLOPT_HTTPHEADER, $this->search_headers);
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
      echo "Error: $result";
    }
    curl_close($curl);
    //file_put_contents("result.json",$result);
    if ($this->is_json) $result = json_decode($result,TRUE);
    $this->search_result = $result;
    
    //debug:
    
  }

  public function wcds_db_list() {

    $token_authorization = $this->get_access_token_authorization();
    array_push($this->search_headers,$token_authorization);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://beta.worldcat.org/discovery/database/list');
    curl_setopt($curl, CURLOPT_HTTPHEADER, $this->search_headers);
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
      echo "Error: $result";
    }
    curl_close($curl);
    file_put_contents("result.json",$result);
    if ($this->is_json) $result = json_decode($result,TRUE);
    $this->list = $result;
    
    //debug:
    
  }
}

