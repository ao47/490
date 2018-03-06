#!/usr/bin/php
<?php
include("account.php");
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
//require_once('logger.inc');
//private $con;
//$con = mysqli_connect($hostname, $username, $password, "users") or die (mysqli_error());
//$clientLog= new rabbitMQClient("logging.ini","testServer");
//$logger = new Logger();
echo "Server running, awaiting messages from RABBIT ...".PHP_EOL;

$emailId;
$userId;
function doLogin($user,$pass){
//	$con = mysqli_connect($hostname, $username, $password, "users") or die (mysqli_error());
	$con = mysqli_connect("localhost","root","12345","users") or die(mysqli_error());
	//need to log error

	echo "connected to db".PHP_EOL;
	echo $user.PHP_EOL;
	
	//
	global $emailId, $userId;
	$username=mysqli_real_escape_string($con,$user);
	$password=mysqli_real_escape_string($con,$pass);
	
	$query=mysqli_query($con,"SELECT * FROM login where name='".$username."'");
	$numrows=mysqli_num_rows($query);
	if($numrows!=0){
		while($row=mysqli_fetch_assoc($query)){
			$dbusername=$row['name'];
			$dbpassword=$row['passwd'];
			$dbemail=$row['email'];
		}
		echo "success fetching array".PHP_EOL;

		if(($username == $dbusername) && (password_verify($password,$dbpassword))){

			echo "username and password verified".PHP_EOL;		
			
			//return email in array
			

			$emailId=$dbemail;
                        $userId=$dbusername;
			
			//return array
			 
			$request = array();
		//	$request['type']= "processedLogin";
			$request['valid']= true;
			$request['em']= "$emailId";
			$request['userName']= "$userId";
			return $request;
			//$response = $client->publish($request);		
			
			//return true, email
			//return array("returnCode" => '0', 'em' => "$emailId",'userName'=>"$userId" ,'message'=>"Server received request and processed");
			
		}
	}
	else {
	
//			$request = array();
//          $request['valid']= 'true';
//          $request['email']= 'email';
//          $response = $client->publish($request);
		return array("returnCode" => '1');
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
    case "login":
      return doLogin($request['username'],$request['password']);
    case "register":
      return doRegister($request['username'],$request['password']);
  }
  return array("returnCode" => '1', 'message'=>"Server received request and processed");
}

//Which queue am I accessing?

//localtesting
//$server = new rabbitMQServer("testLocal.ini", "testServer");

//original
$server = new rabbitMQServer("db.ini", "testServer");


$server->process_requests('requestProcessor');
exit();
?>

