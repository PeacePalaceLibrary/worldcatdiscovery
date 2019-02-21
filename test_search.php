<?php

require './wcdsQuery/key.php';
require './wcdsQuery/wcdsQuery.php';
//require './OCLC/Auth/WSKey.php';
//require './OCLC/User.php';

//TODO functions in een apart bestand

$query = new DiscoveryQuery($config['wskey'],$config['secret']);

?>
<html>
	<head>
	   
	</head>
	<body>

		<p>Config:
			<pre><?php echo json_encode($config, JSON_PRETTY_PRINT);?></pre>
		</p>
    <?php $query->wcds_search_request(); ?>
		<p>Search:
			<pre><?php echo $query;?></pre>
		</p>
	</body>
	
</html> 