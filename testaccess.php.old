#!/usr/bin/php
<?php
include("account.php");
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('logger.inc');
//private $con;
//$con = mysqli_connect($hostname, $username, $password, "users") or die (mysqli_error());
//$clientLog= new rabbitMQClient("logging.ini","testServer");
//$logger = new Logger();
echo "Server running, awaiting messages from RABBIT ...".PHP_EOL;


function doLogin($username,$password){
//	$con = mysqli_connect($hostname, $username, $password, "users") or die (mysqli_error());
	$con = mysqli_connect("localhost","root","12345","users") or die(mysqli_error());
	//need to log error

	echo "connected to db".PHP_EOL;
	echo $username.PHP_EOL;
	
	//
	$query=mysqli_query($con,"SELECT * FROM login where name='$username'");
	$numrows=mysqli_num_rows($query);
	if($numrows!=0){
		while($row=mysqli_fetch_assoc($query)){
			$dbusername=$row['name'];
			$dbpassword=$row['passwd'];
			$dbemail=$row['email'];
	}
		echo "success fetching array".PHP_EOL;
		if(($username == $dbusername) && (password_verify($password,$dbpassword))
){

		echo "username and password verified".PHP_EOL;		

	//return email in array
			$request = array();
			$request['valid']= 'true';
			$request['email']= $dbemail;
			$response = $client->publish($request);		
		}
	}
	else {
	
//			$request = array();
//                      $request['valid']= 'true';
//                      $request['email']= 'email';
//                      $response = $client->publish($request);
	return false;
	}
}

function doRegister($username,$password){
	return true;
}


function requestProcessor($request){
	echo "received request".PHP_EOL;
	var_dump($request);
	if(!isset($request['type'])){
    		return "ERROR: unsupported message type";
  	}
  switch ($request['type']){
    case "loginTest":
      return doLogin($request['username'],$request['password']);
    case "register":
      return doRegister($request['username'],$request['password']);
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

//Which queue am I accessing?
$server = new rabbitMQServer("testLocal.ini", "testServer");


$server->process_requests('requestProcessor');
exit();
?>

