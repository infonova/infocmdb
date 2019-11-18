<?php

define("APPLICATION_ENV", "production");

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library/composer/diablomedia'),
    get_include_path(),
)));

require_once APPLICATION_PATH . '/../library/composer/autoload.php'; // loading library with composer autoloader

$individualizationConfig = new Zend_Config_Ini(realpath(APPLICATION_PATH.'/configs/individualization.ini'), APPLICATION_ENV);

$shortName = $individualizationConfig->homelink->string->partA . $individualizationConfig->homelink->string->partB;
$description = $_SERVER['SERVER_NAME'];
$favicon = 'images/favicon.ico';

$searchUrl = curPageURL()."/search/index?searchstring=%22{searchTerms}%22";

header( 'Content-type: text/xml' );

echo <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
<ShortName>$shortName</ShortName>
<Description>$description</Description>
<Url type="text/html" method="get" template="$searchUrl" />
<Image height="16" width="16" type="image/x-icon">$favicon</Image>
</OpenSearchDescription>
EOT;


function curPageURL() {
    if (php_sapi_name() != "cli") {
        $pageURL = 'http';

        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"];
        }

        return $pageURL;
    } else {
        return "";
    }
}

?>
