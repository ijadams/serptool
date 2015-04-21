<html>
<head>
<link rel="stylesheet" type="text/css" href="style.css">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
	<a href="http://previewchanges.com/~iadams/serptool/"><h1>SERP <span style="font-weight:100;">Tool™</span></h1></a>
	<h2>results:</h2>

<table>
<?php
ini_set('display_errors', 1);
$url = $_POST['url'];
//$keyword = $_POST['keyword'];
$keywords = split("\n", $_POST['keywords']);

/**
 * SEOstats Example - Get Google Serps
 *
 * @package    SEOstats
 * @author     Stephan Schmitz <eyecatchup@gmail.com>
 * @copyright  Copyright (c) 2010 - present Stephan Schmitz
 * @license    http://eyecatchup.mit-license.org/  MIT License
 * @updated    2013/12/17
 */

// Bootstrap the library / register autoloader
require_once realpath(__DIR__ . '/SEOstats/bootstrap.php');
#require_once realpath(__DIR__ . '/vendor/autoload.php');

use \SEOstats\Services\Google as Google;

try {
    foreach ($keywords as $key) {
    	if (!empty($key)) {
        $serps = Google::getSerps($key, 30, $url);
          echo "<tr><td>" . $key . "</td>";
          echo "<td>" . $serps[0]['position'] . "</td></tr>";
        }
    }
}

catch (\Exception $e) {
    echo 'Caught SEOstatsException: ' .  $e->getMessage();
}

?>


</table></center>
</body>
</html>
