<?php
$config = [
    'name' => "NLVRD",
    'institution' => "57439",
    'defaultBranch' => "262638",
    
    //$config['datacenter'] not used yet
    'datacenter' => "sd02",
    
    //wskey van worldcat discovery API moet per persoon aangevraagd
    //wskey FvL
    'wskey' => "7s3Bny4hZv9tE6XTK7LNYzSgnyIkYgU6WCRqqplgqQDefRIDk0cQDsDPWb2sM9KU9EswqCESZrXu29Xq",
    'secret' => "gucbgBJrhvX2EjEggXU/RnCcL1rPffV3",

    //patron id is niet nodig in de WorldCat Discovery API
    //'ppid_namespace' => "urn:oclc:platform:57439", 
    //'ppid' =>"3ad48a9e-0ee7-4eec-b303-189a8f4af886",
    
    //get a new token before every request
    'token_url' => "https://authn.sd00.worldcat.org/oauth2/accessToken",
    'token_method' => 'POST',
    'token_params' => ['grant_type' => 'client_credentials',
                      'authenticatingInstitutionId' => '57439',
                      'contextInstitutionId' => '57439',
                      'scope' => "WorldCatDiscoveryAPI"
                      ],
    'token_headers' => ["Accept: application/json"],
   

    //searches section
    'search_url' => "https://beta.worldcat.org/discovery/bib/search",
    'search_params' => ['q' => 'Vredespaleis',
                 'dbIds' => '638'
                 ],
    'search_method' => 'GET',
    'search_headers' => ["Accept: application/ld+json"],
    
    //read one ocn section the url must be: https://beta.worldcat.org/discovery/bib/data/{ocn}
    'read_url' => "https://beta.worldcat.org/discovery/bib/data",
    'read_ocn' => "66095588",
    'read_method' => 'GET',
    'read_headers' => ["Accept: application/json"],
	];
?>

