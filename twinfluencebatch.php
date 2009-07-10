<?php
/* Script to return an XML file from Twinfluence site
 * given user list as comma, tab, or line return seperated
 *
 * 2009-07-06 Written: Michael Chelen http://mikechelen.com
 * License: Creative Commons Public Domain CC0
 * http://creativecommons.org/publicdomain/zero/1.0/
 *
 */

// accepts input either through form GET or command line arguments
if ($argc > 1 || isset($_GET["user"])) {
    // use command line arguments if they exist
    if ($argc > 1) {
        // echo "first " . $argv[0];
        $user = $argv[1];
        $pwd = $argv[2];
        $cacheonly = $argv[3];
        $idListUrl = $argv[4];
        // initial delay
        // subsequent retries will increase * 10 until max delay is surpassed
        // in 1/1000000s of a second

        if(isset($argv[5])){
            $delay = $argv[5];
        }
        else {
            $delay = 1000; // initial retry will be delayed 1/1000 of a second
        }

        if(isset($argv[6])){
            $maxDelay = $argv[6];
        }
        else {
            $maxDelay = 1000000; // maximum retry delay before giving up
        }

    }
    // use form input if it exists
    elseif (isset($_GET["user"]) && isset($_GET["pwd"]) && isset($_GET["idlist"]) && isset($_GET["cacheonly"])) {
        header('Content-Type: text/xml');
        $user = $_GET["user"];
        $pwd = $_GET["pwd"];
        $cacheonly = $_GET["cacheonly"];
        $idListUrl = $_GET["idlist"];

        if(isset($_GET["delay"])){
            $delay = $_GET["delay"];
        }
        else {
            $delay = 1000; // initial retry will be delayed 1/1000 of a second
        }

        if(isset($_GET["maxdelay"])){
            $maxDelay = $_GET["maxdelay"];
        }
        else {
            $maxDelay = 1000000; // maximum retry delay before giving up
        }


    }
    $idlist = file_get_contents($idListUrl);

    // split the input at line returns, tabs, and commas
    $idlist = preg_split('/[\r\n\t,]+/', $idlist, -1, PREG_SPLIT_NO_EMPTY);

    for ($i=0;$i<count($idlist);$i++) {
        // build the query with current username
        $id = $idlist[$i];
        $data = array ('user' => $user, 'pwd' => $pwd, 'id' => $id, 'cacheonly' => $cacheonly);
        $data = http_build_query($data);
        $url = 'http://twinfluence.com/api_user.php';

        // send POST request and return the response
        $returned1 = do_post_request($url, $data, $delay, $maxDelay);

        // replace "&" symbol with html entity
        $returned = str_replace("&","%26",$returned1);

        // cut off the first line
        $result .= substr($returned, stripos($returned,"\n"));
    }
    print '<?xml version="1.0" encoding="UTF-8"?>
    <body>' . $result . '
    </body>';

}

//provide input form
else {
    $htmlform = '<html>
    <body>Warning: Password is sent insecurely! Use with a test account. </br >
    <form method="get">
    Username: <input type="text" name="user" /><br />
    Password: <input type="text" name="pwd" /><br />
    Cacheonly: <input type="text" name="cacheonly" value="TRUE"/><br />
    Username List URL (CSV): <input type="text" name="idlist"><br />
    Retry Delay (millionths of a second): <input type="text" name="delay" value="1000"/><br />
    Max Retry Delay (millionths of a second): <input type="text" name="maxdelay" value="1000000"/><br />
    <input type="submit" value="Submit" />
    </form>
    </body>
    </html>';
    print $htmlform;
}


// sends a POST
// retry with increasing delay
// original function by Wez Furlong http://netevil.org/blog/2006/nov/http-post-from-php-without-curl
function do_post_request($url, $data, $delay, $maxDelay, $optional_headers = null)
{
    usleep($delay);
    $params = array('http' => array(
                  'method' => 'POST',
                  'content' => $data
    ));
    if ($optional_headers !== null) {
        $params['http']['header'] = $optional_headers;
    }
    $ctx = stream_context_create($params);
    $fp = @fopen($url, 'rb', false, $ctx);
    if (!$fp) {
        if ($delay >= $maxDelay ) {
            return "Problem with $url, $data, $php_errormsg";
        }
        else {
            return do_post_request($url, $data, $delay*10, $maxDelay);
        }
    }
    $response = @stream_get_contents($fp);
    if ($response === false) {
        if ($delay >= $maxDelay) {
            return "Problem reading data from $url, $data, $php_errormsg";
        }
        else {
            return do_post_request($url, $data, $delay*10, $maxDelay);
        }
    }
    return $response;
}

?>