<?php
$config = [
    'name' => "NLVRD",
    'institution' => "57439",
    'defaultBranch' => "262638",
    'datacenter' => "sd02",
    
    //wskey van worldcat discovery API moet per persoon aangevraagd
    //wskey FvL
    'wskey' => "7s3Bny4hZv9tE6XTK7LNYzSgnyIkYgU6WCRqqplgqQDefRIDk0cQDsDPWb2sM9KU9EswqCESZrXu29Xq",
    'secret' => "gucbgBJrhvX2EjEggXU/RnCcL1rPffV3",

    //patron id is niet nodig in deze API
    //'ppid_namespace' => "urn:oclc:platform:57439", 
    //'ppid' =>"3ad48a9e-0ee7-4eec-b303-189a8f4af886",
    
    'token_url' => "https://authn.sd00.worldcat.org/oauth2/accessToken",
    'token_method' => 'POST',
    'token_params' => ['grant_type' => 'client_credentials',
                      'authenticatingInstitutionId' => '57439',
                      'contextInstitutionId' => '57439',
                      'scope' => "WorldCatDiscoveryAPI"
                      ],
    'token_headers' => ["Accept: application/json"],
   


    'url' => "https://beta.worldcat.org/discovery/bib/search",
    'params' => ['q' => 'Vredespaleis',
                 'dbIds' => '638'
                 ],
    'method' => 'GET',
    'headers' => ["Accept: application/json"]
	];
?>

