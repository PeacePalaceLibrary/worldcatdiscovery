<?php
/**
 * OCLC-Auth
 * Copyright 2013 OCLC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @package OCLC/Auth
 * @copyright Copyright (c) 2013 OCLC
 * @license http://www.opensource.org/licenses/Apache-2.0
 * @author Karen A. Coombs <coombsk@oclc.org>
*/

/* Namespaces are not used in PPL's implementation
namespace OCLC\Auth;

use OCLC\User as User;
use OCLC\Auth\AccessToken;
use OCLC\Auth\AuthCode;
*/

/**
 * A class that represents a clients OCLC Web Service Key.
 * The WSKey has a key and secret and has access to one or more OCLC Web Services.
 * Optionally, it can include principal information in the form of a principal ID and IDNS that represent an user and a redirect URI to be used in the OAuth 2 login flows.
 * The WSKey class is used to
 * - generate HMAC signatures
 * - return the login URL for the OCLC Explicit Authorization flow for requesting an AuthCode
 * - redeem an authorization code for an access token
 * - request an access token via a client credentials grant flow
 *
 * @author Karen A. Coombs <coombsk@oclc.org>
 *
 *         See the OCLC/Auth documentation for examples.
 *
 */
class WSKey
{   
	/**
	 * Whether or not the application is interacting with a test server
	 * @var binary static $testServer
	 */
    public static $testServer = FALSE;
    
	/**
	 * The user agent to use when sending the request 
	 * @var string static $userAgent
	 */
	public static $userAgent = 'oclc-auth-php';
    
	/**
	 * An array of options values valid for constructor
	 * @var array static $validOptions 
	 */
    protected static $validOptions = array(
        'redirectUri',
        'services',
        'logger',
    	'logFormat',
        'testMode'
    );
    
    /**
     * The string that represents your API key
     * @var string $key
     */ 
    private $key;
    
    /**
     * The secret used when generating digital signatures
     * @var string $secret
     */
    private $secret;
    
    /**
     * the redirect URI associated with the WSKey that will 'catch' the redirect back to your app after login
     * @var string $redirectUri
     */
    private $redirectUri;
    
    /**
     * An array of one or more OCLC web services, examples: WorldCatMetadataAPI, WMS_NCIP
     * @var array $services
     */
    private $services;
    
    /**
     * a timestamp for debug purposes
     * @var string $debugTimestamp
     */ 
    private $debugTimestamp = null;
    
    /**
     * a nonce for debug purposes
     * @var string $debugNonce
     */
    private $debugNonce = null;
    
    /**
     * Whether or not to run in test mode
     * @var boolean $testMode
     */
    private $testMode = false;
    
    /**
     * The logger object
     * @var unknown
     */
    private $logger = null;
    
    /**
     * The format to log in
     * @var string
     */
    private $logFormat = null;
    
    /**
     * User object used when building an HMAC signature of using client credentials grant
     * @var OCLC\User $user
     */
    private $user = null;
    
    /**
     * bodyHash of the request
     * @var string $bodyHash 
     */
    private $bodyHash = null;
    
    /**
     * an array of Authentication name/value pairs example username/testuser
     * @var array $authParams
     */
    private $authParams = null;
    
    /**
     * The signed request
     * @var string $signedRequest
     */
    private $signedRequest = null;

    /**
     * Construct a new Web Service key for use when authenticating to OCLC Web Services.
     *
     * @param string $key
     *            the hashed string that represents your API key
     * @param string $secret
     *            a string which is the secret used when generating digital signatures
     * @param array $options
     *            an array of three possible name/value pairs
     *            - redirect_uri: a string which is the redirect URI associated with the WSKey that will 'catch' the redirect back to your app after login
     *            - services: an array of one or more OCLC web services, examples: WorldCatMetadataAPI, WMS_NCIP
     *            - logger: an object that support a Psr\Log\LoggerInterface
     *            - logFormat: a string which is the log format
     */
    public function __construct($key, $secret, $options = null)
    {
        if (empty($key) || empty($secret)) {
            Throw new \BadMethodCallException('You must pass a valid key and secret to construct a WSKey');
        } elseif (isset($options) && (empty($options) || ! is_array($options))) {
            Throw new \BadMethodCallException('You must pass a valid array of options');
        }
        $this->key = $key;
        $this->secret = $secret;
        
        if (! empty($options)) {
            if (isset($options['redirectUri']) && filter_var($options['redirectUri'], FILTER_VALIDATE_URL) === FALSE) {
                Throw new \BadMethodCallException('You must pass a valid redirectUri');
            } elseif (isset($options['services']) && (empty($options['services']) || ! (is_array($options['services'])))) {
                Throw new \BadMethodCallException('You must pass an array of at least one service');
            }elseif (isset($options['logger']) && !is_a($options['logger'], 'Psr\Log\LoggerInterface')){
            	Throw new \BadMethodCallException('The logger must be an object that uses a valid Psr\Log\LoggerInterface interface');
            }
            
            foreach ($options as $name => $value) {
                if (in_array($name, static::$validOptions)) {
                    $this->{$name} = $value;
                }
            }
        }
    }

    /**
     * getKey
     *
     * @return string the Hashed string that represents your API key
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * getSecret
     *
     * @return string the secret used when generating digital signatures
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * getRedirect_uri
     *
     * @return the redirect URI associated with the WSKey that will 'catch' the redirect back to your app after login
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * getServices
     *
     * @return an array of one or more OCLC web services, examples: WorldCatMetadataAPI, WMS_NCIP
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Set a timestamp for debugging
     *
     * @param string $timestamp            
     *
     */
    public function setDebugTimestamp($timestamp)
    {
        $this->debugTimestamp = $timestamp;
    }

    /**
     * Set a nonce for debugging
     *
     * @param string $nonce            
     *
     */
    public function setDebugNonce($nonce)
    {
        $this->debugNonce = $nonce;
    }
    
    /**
     * getSignedRequest
     * Get a signed request
     * 
     * @return string of signed request
     */
    
    public function getSignedRequest()
    {
        return $this->signedRequest;
    }

    /**
     * Return the login URL used with OCLC's OAuth 2 implementation of the Explicit Authorization Flow.
     *
     * @link http://www.oclc.org/developer/platform/explicit-authorization-code Explicit Auth Documentation on the OCLC Developer Network.
     * @param integer $authenticating_institution_id            
     * @param integer $context_institution_id            
     * @return string The Login URL used with OCLC's OAuth 2 implementation of the Explicit Authorization Flow
     */
    public function getLoginURL($authenticating_institution_id = null, $context_institution_id = null)
    {
        if ($this->testMode == false && empty($this->services)){
            Throw new \BadMethodCallException('You must pass an array of at least one service');
        }
        
        $options = array();
        $options['testMode'] = $this->testMode;
        if (isset ($authenticating_institution_id)){
            $options['authenticatingInstitutionId'] = $authenticating_institution_id; 
        }
        if (isset($context_institution_id)){
            $options['contextInstitutionId'] = $context_institution_id;
        }
        
        $auth_code = new AuthCode($this->key, $this->redirectUri, $this->services, $options);
        return $auth_code->getLoginURL();
    }

    /**
     * Returns an OCLC/Auth/AccessToken object
     *
     * @param string $authCode authorization code returned as a query parameter
     * @param integer $authenticatingInstitutionId the WorldCat Registry ID of the institution that will login the user
     * @param integer $contextInstitutionId the WorldCat Registry ID of the institution whose data will be accessed
     * @return OCLC/Auth/AccessToken Returns an /OCLC/Auth/AccessToken object when given
     */
    public function getAccessTokenWithAuthCode($authCode, $authenticatingInstitutionId, $contextInstitutionId)
    {
        if ($this->testMode == false && empty($authCode)) {
            throw new \BadMethodCallException('You must pass an authorization code');
        } elseif ($this->testMode == false && empty($authenticatingInstitutionId)) {
            throw new \BadMethodCallException('You must pass an authenticating_institution_id');
        } elseif ($this->testMode == false && empty($contextInstitutionId)) {
            throw new \BadMethodCallException('You must pass a context_institution_id');
        }
        $options = array(
            'authenticatingInstitutionId' => $authenticatingInstitutionId,
            'contextInstitutionId' => $contextInstitutionId,
            'code' => $authCode,
            'redirectUri' => $this->redirectUri
        );
        
        return $this->getAccessToken('authorization_code', $options);
    }

    /**
     * Returns an OCLC/Auth/AccessToken object
     *
     * @param integer $authenticatingInstitutionId the WorldCat Registry ID of the institution that will login the user
     * @param integer $contextInstitutionId the WorldCat Registry ID of the institution whose data will be accessed
     * @param OCLC/User $user an /OCLC/User object which contains a valid principalID, principalIDNS and insitution ID for a user
     * @return OCLC/Auth/AccessToken Returns an /OCLC/Auth/AccessToken object when given
     */
    public function getAccessTokenWithClientCredentials($authenticatingInstitutionId, $contextInstitutionId, $user = null)
    {
        if ($this->testMode == false && empty($authenticatingInstitutionId)) {
            throw new \BadMethodCallException('You must pass an authenticating_institution_id');
        } elseif ($this->testMode == false && $this->testMode == false && empty($contextInstitutionId)) {
            throw new \BadMethodCallException('You must pass a context_institution_id');
        } elseif ($this->testMode == false && empty($this->services)) {
            Throw new \BadMethodCallException('You must pass an array of at least one service');
        }
        
        $options = array(
            'authenticatingInstitutionId' => $authenticatingInstitutionId,
            'contextInstitutionId' => $contextInstitutionId,
            'scope' => $this->services
        );
        
        return $this->getAccessToken('client_credentials', $options, $user);
    }

    /**
     *
     *
     *
     * Generates a digital signature for a given request according to the OAuth HMAC specification
     *
     * @param string $method the HTTP method, GET, POST, PUT, DELETE
     * @param string $request_url the URL the request will be made to
     * @param array $options
     *            - User - OCLC/User User an /OCLC/User object which contains a valid principalID, principalIDNS and insitution ID for a user
     *            - BodyHash - bodyHash of the request this is optional
     *            - AuthParams - an array of Authentication name/value pairs example username/testuser
     * @return string The HMAC Signature that should be sent in the Authorization Header
     */
    public function getHMACSignature($method, $request_url, $options = null)
    {
        if (empty($this->secret)) {
            // Throw an error if secret is missing
            Throw new \BadMethodCallException('You must construct a WSKey with a secret to build an HMAC Signature');
        } elseif (empty($method)) {
            // Throw an error if method or request_url are missing
            Throw new \BadMethodCallException('You must pass an HTTP Method to build an HMAC Signature');
        } elseif (empty($request_url)) {
            // Throw an error if method or request_url are missing
            Throw new \BadMethodCallException('You must pass a Request URL to build an HMAC Signature');
        }
        
        // check options for extra parameters
        if (! empty($options)) {
            foreach ($options as $optionName => $optionValue) {
                $this->{$optionName} = $optionValue;
            }
        }
        
        if (empty($this->debugTimestamp)) {
            $timestamp = time();
        } else {
            $timestamp = $this->debugTimestamp;
        }
        
        if (empty($this->debugNonce)) {
            $nonce = sprintf("%08x", mt_rand(0, 0x7fffffff));
        } else {
            $nonce = $this->debugNonce;
        }
        
        $this->signedRequest = static::signRequest($this->key, $this->secret, $method, $request_url, $this->bodyHash, $timestamp, $nonce);
        
        $auth_header = "http://www.worldcat.org/wskey/v2/hmac/v1" . " clientId=\"" . $this->key . "\"" . ", timestamp=\"" . $timestamp . "\"" . ", nonce=\"" . $nonce . "\"" . ", signature=\"" . $this->signedRequest . "\"";
        // If present Add PrincipalID and PrincipalIDNS and any extra parameters on end
        if (isset($this->user) || isset($this->authParams)) {
            $auth_header .= static::AddAuthParams($this->user, $this->authParams);
        }
        
        return $auth_header;
    }

    /**
     *
     *
     *
     * Create a Signature for a request using
     *
     * @param string $key            
     * @param string $secret            
     * @param string $method            
     * @param string $request_url            
     * @param string $bodyHash            
     * @param string $timestamp            
     * @param string $nonce            
     * @return string
     */
    private static function signRequest($key, $secret, $method, $request_url, $bodyHash, $timestamp, $nonce)
    {
        $signature = base64_encode(hash_hmac("sha256", self::normalizeRequest($key, $method, $request_url, $bodyHash, $timestamp, $nonce), $secret, True));
        return $signature;
    }

    /**
     * Normalize the Request by breaking apart the URL
     *
     * @param string $key            
     * @param string $method            
     * @param string $request_url            
     * @param string $bodyHash            
     * @param string $timestamp            
     * @param string $nonce            
     * @return string
     */
    private static function normalizeRequest($key, $method, $request_url, $bodyHash, $timestamp, $nonce)
    {
        $signatureUrl = 'https://www.oclc.org/wskey';
        
        $parsedUrl = parse_url($request_url);
        $parsedSigUrl = parse_url($signatureUrl);
        
        $host = $parsedSigUrl["host"];
        if (isset($parsedSigUrl["port"])) {
            $port = $parsedSigUrl["port"];
        } else 
            if ($parsedSigUrl["scheme"] == "http") {
                $port = 80;
            } else 
                if ($parsedSigUrl["scheme"] == "https") {
                    $port = 443;
                }
        $path = $parsedSigUrl["path"];
        
        $normalizedRequest = $key . "\n" . $timestamp . "\n" . $nonce . "\n" . $bodyHash . "\n" . $method . "\n" . $host . "\n" . $port . "\n" . $path . "\n";
        
        if (isset($parsedUrl["query"])) {
            $params = array();
            foreach (explode('&', $parsedUrl["query"]) as $pair) {
                list ($key, $value) = explode('=', $pair);
                $params[] = array(
                    urldecode($key),
                    urldecode($value)
                );
            }
            sort($params);
            
            foreach ($params as $param) {
                $name = urlencode($param[0]);
                $value = urlencode($param[1]);
                $nameAndValue = "$name=$value";
                $nameAndValue = str_replace("+", "%20", $nameAndValue);
                $nameAndValue = str_replace("*", "%2A", $nameAndValue);
                $nameAndValue = str_replace("%7E", "~", $nameAndValue);
                $normalizedRequest .= $nameAndValue . "\n";
            }
        }
        
        return $normalizedRequest;
    }

    /**
     * Add the PrincipalID, PrincipalIDNS and any other Authentication Parameters to the Authorization Header
     *
     * @param OCLC/User $user            
     * @param array $authParams            
     * @return string
     */
    private static function AddAuthParams($user, $authParams)
    {                   
        $authValuePairs = null;
        if ( !(empty($authParams) && empty($user))) {   //if (count($authParams) > 0 || ! empty($user)) { 
            if (empty($authParams)) {
                $authParams = array();
            }
            
            if (isset($user)) {
                $userParams = array(
                    'principalID' => $user->getPrincipalId(),
                    'principalIDNS' => $user->getPrincipalIDNS()
                );
                $authParams = array_merge($userParams, $authParams);
            }
            
            $authValuePairs .= ', ';
            $prefix = '';
            foreach ($authParams as $key => $value) {
                $authValuePairs .= $prefix . $key . "=\"" . $value . "\"";
                $prefix = ', ';
            }
        }
        return $authValuePairs;
    }
    
    /**
     * Get an Access Token
     * @param string $grant_type
     * @param array $options
     * @param OCLC/User $user 
     */
    protected function getAccessToken($grant_type, $options, $user = null)
    {
        AccessToken::$userAgent = static::$userAgent;
        AccessToken::$testServer = static::$testServer;
        if (isset($this->logger)){
        	$options['logger'] = $this->logger;
        	if (isset($this->logFormat)){
        		$options['logFormat'] = $this->logFormat;
        	}
        }
        
        if (isset($this->testMode)){
            $options['testMode'] = $this->testMode;
        }
        
        $accessToken = new AccessToken($grant_type, $options);
        $accessToken->create($this, $user);
        return $accessToken;
    }
}