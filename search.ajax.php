<?php

require './wcdsQuery/key.php';
require './wcdsQuery/wcdsQuery.php';

/*
function tries to make a string out of something of an unknown type


*/
function something2string($something,$glue = ', ',$sep = ':',$key = null,$subkey = null) {
  //echo ' - '.json_encode($something);
  if (is_string($something)) {
    return $something;
  }
  else if (is_array($something)) {
    if (array_keys($something) !== range(0, count($something) - 1)) {
      //this is an assoc array
      if ($key) {
        //echo ' - '.$something[$key];
        return something2string($something[$key]);
      }
      else {
        $non_assoc = array();
        foreach ($something as $k => $v) {
          $non_assoc[] = $k.$sep.something2string($v);
        }
        return implode($glue,$non_assoc);
      }
    }
    else {
      $clean = array();
      foreach ($something as $v) $clean[] = $subkey ? something2string($v, ', ', ':', $subkey) : something2string($v);
      return implode($glue,$clean);
    }
  }
  else {
    return (string)$something;
  }
}


$params = array();
$headers = array();

//extract the accept header from $_GET
foreach ($_GET as $key => $value) {
  if ($key == 'responseType') {
    if (strlen($value) > 0) {
      $headers[] = "Accept: ".$value;
    }
    else {
      //default:
      $headers[] = "Accept: application/json";
    }
  }
  else {
    //the others are real parameters
    if (strlen($value) > 0) {
      $parts = explode('&',$value);
      if (count($parts) == 1) {
        $params[$key] = trim($parts[0]);
      }
      else if (count($parts) > 1){
        $params[$key] = array();
        foreach ($parts as $p) {
          $params[$key][] = trim($p);
        }
      }
    }
  }
}

$query = new DiscoveryQuery($config['wskey'],$config['secret']);
$query->wcds_search_request($headers,$params);

if ($query->search_result ) {
  //the search request has a response
  if (array_key_exists("@graph",$query->search_result)) {
    //we have some result
    if (count($query->search_result["@graph"]) > 0) {
      //$query->search_result["@graph"] seems to be an array

      //all results in a file:
      file_put_contents('allresults.json',json_encode($query->search_result["@graph"][0],JSON_PRETTY_PRINT));

      if (array_key_exists("discovery:errorMessage",$query->search_result["@graph"][0])) {
        //error!
        echo '<p>'.$query->search_result["@graph"][0]["discovery:errorMessage"].'</p>';
      }
      if (array_key_exists("discovery:totalResults",$query->search_result["@graph"][0])) {

        echo '<p>Totaal aantal resultaten: '.$query->search_result["@graph"][0]["discovery:totalResults"]["@value"].'</p>';
      }
      if (array_key_exists("discovery:hasPart",$query->search_result["@graph"][0])){
        //we have a list of publications
        if (count($query->search_result["@graph"][0]["discovery:hasPart"]) > 0) {
          //with at least one publication

          //first record in a file:
          //file_put_contents('firstresult.json',json_encode($query->search_result["@graph"][0]["discovery:hasPart"][0],JSON_PRETTY_PRINT));

          foreach ($query->search_result["@graph"][0]["discovery:hasPart"] as $result) {
            //https://peacepalace.on.worldcat.org/oclc/317402401
            if (array_key_exists("schema:about",$result)) {
              $about = $result["schema:about"];

              //now make an output line, this can also be done with Twig

              //first the title as a link:
              $line = '<a href="https://peacepalace.on.worldcat.org/oclc/'.$about["library:oclcnum"].'">';

              //the values on a key can be strings, arrays [..,..,..] or objects {x:y,...,p:q}
              //its is probably wise to program a general function
              $line .= something2string($about["schema:name"],'','',"@value");
              /*
              if (is_string($about["schema:name"])) {
              $line .= $about["schema:name"];
              }
              else if (is_array($about["schema:name"])) {
              $line .= $about["schema:name"]["@value"];
              }*/
              $line .= '</a>';

              if (array_key_exists("schema:creator",$about) &&
              array_key_exists("schema:name",$about["schema:creator"]))
              $line .= ';&nbsp;'.something2string($about["schema:creator"]["schema:name"]);
              if (array_key_exists("schema:contributor",$about) &&
              array_key_exists("schema:name",$about["schema:contributor"]))
              $line .= ';&nbsp;'.something2string($about["schema:contributor"]["schema:name"]);

              if (array_key_exists("schema:datePublished",$about)) {
                $line .= ' - &nbsp;'.something2string($about["schema:datePublished"]);
              }

              $line .= '<br/>';
              echo $line;
            }
            else {
              echo "=========== No about -&nbsp;";
              if (array_key_exists("@id",$result)) echo $result["@id"];
              echo "<br/>";
            }
          }
        }
        else {
          //search_result["@graph"][0]["discovery:hasPart"] exists but has 0 elements
        }
      }
    }
    else {
      //search_result["@graph"] exists but has 0 elements
    }
  }
  else {
    //search_result["@graph"] does not exist
  }
}
else {
  echo "No query result...";
}
?>
