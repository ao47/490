#!/usr/bin/php
<?php
include("account.php");
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('logger.inc');

echo "Server running, awaiting messages from RABBIT ...".PHP_EOL;


function doLogin($username,$password)
{
    // lookup username in database
    // check password
    return true;

    //return false if not valid
}

function doRegister($username,$password)
{
	return true;
}


function requestProcessor($request)
{
  echo "received request".PHP_EOL;
  var_dump($request);
  if(!isset($request['type']))
  {
    return "ERROR: unsupported message type";
  }
  switch ($request['type'])
  {
    case "login":
      return doLogin($request['username'],$request['password']);
    case "register":
      return doRegister($request['username'],$request['password']);
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

//Which queue am I accessing?
$server = new rabbitMQServer("authentication.ini", "testServer");


$server->process_requests('requestProcessor');
exit();
?>

