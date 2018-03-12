#!/usr/bin/php
<?php
include("account.php");
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('logger.inc');
//private $con;
echo date('m/d/y h:i:s a' ,time()).PHP_EOL;
echo "Server running, awaiting messages from RABBIT ...".PHP_EOL;

$emailId;
$userId;
function doLogin($user,$pass){

	//logging variables
	$logClient = new rabbitMQClient('toLog.ini', 'testServer');
        $logger = new Logger();
		

	$con = mysqli_connect("localhost","root","12345","users");
	//check for connection error and logging
	if(!$con){
		$errorMessage = 'Connection Error:'.mysqli_connect_error();
                $sendLog = $logger->logArray('error',$errorMessage,__FILE__);
                $testVar = $logClient->publish($sendLog);
		die("Connection Error:".mysqli_connect_error());
	}

	
	//Event - connected to DB
	$eventMessage = 'Successfully Connected to Database';
	$sendLog = $logger->logArray('event',$eventMessage,__FILE__);
	$testVar = $logClient->publish($sendLog);

	echo date('m/d/y h:i:s a' ,time())." Successfully Connected to Database".PHP_EOL;
	echo date('m/d/y h:i:s a' ,time())." ".$user." is attempting to Login".PHP_EOL;
	$eventMessage = $user." is attempting to login";
        $sendLog = $logger->logArray('event',$eventMessage,__FILE__);
        $testVar = $logClient->publish($sendLog);
	
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
		//echo "success fetching array".PHP_EOL;

		if(($username == $dbusername) && (password_verify($password,$dbpassword))){
			//internal message
			echo date('m/d/y h:i:s a' ,time()). " username and password verified".PHP_EOL;		
			echo date('m/d/y h:i:s a' ,time())." ".$user." has logged in".PHP_EOL;
	

			//Logging - Logged in
			$eventMessage = $user.' has logged in.';
                	$sendLog = $logger->logArray('event',$eventMessage,__FILE__);
                	$testVar = $logClient->publish($sendLog);	
			
	
			//return array
			$emailId=$dbemail;
                        $userId=$dbusername; 
			$request = array();
			$request['valid']= true;
			$request['em']= "$emailId";
			$request['userName']= "$userId";
			return $request;
			//$response = $client->publish($request);		
			
			
			
		}
		else {
		//internal
		echo date('m/d/y h:i:s a' ,time()).$user."/'s  Password is invalid".PHP_EOL;
		//logging - Wrong password
		$eventMessage = $user.'\'s password is incorrect';
        	$sendLog = $logger->logArray('event',$eventMessage,__FILE__);
        	$testVar = $logClient->publish($sendLog);


                return array("valid" => false);

		}
	}
	else {
		echo date('m/d/y h:i:s a' ,time())." ".$user." Doesn't Exist ".PHP_EOL;	
		//logging - User doesnt exist
		$eventMessage = $user.' doesn\'t exist';
                $sendLog = $logger->logArray('event',$eventMessage,__FILE__);
                $testVar = $logClient->publish($sendLog);
		//return array
		return array("valid" => false);
	}
}

function doRegister($user,$pass,$email){
	//logger variables
	$logClient = new rabbitMQClient('toLog.ini', 'testServer');
        $logger = new Logger();


	//connect to db
	
	$con = mysqli_connect("localhost","root","12345","users");
	 //check for connection error and logging
        if(!$con){
                $errorMessage = 'Connection Error:'.mysqli_connect_error();
                $sendLog = $logger->logArray('error',$errorMessage,__FILE__);
                $testVar = $logClient->publish($sendLog);
                die("Connection Error:".mysqli_connect_error());
        }


	//local- Connected to DB
	echo date('m/d/y h:i:s a' ,time())." Successfully connected to the Database".PHP_EOL;
	//event - Connected to DB
	$eventMessage = 'Successfully Connected to Database';
        $sendLog = $logger->logArray('event',$eventMessage,__FILE__);
        $testVar = $logClient->publish($sendLog);
	
	$username=mysqli_real_escape_string($con,$user);
	$password=password_hash((mysqli_real_escape_string($con,$pass)), PASSWORD_DEFAULT);
	$mail=mysqli_real_escape_string($con,$email);

	$query=mysqli_query($con,"SELECT * FROM login where name='".$username."'");
	$numrows=mysqli_num_rows($query);
		#if the user isn't in the database add them
		if($numrows==0){
			$sql="INSERT INTO login(name, email, passwd) VALUES('$username','$mail', '$password')";
			$result=mysqli_query($con, $sql);
			if($result){
				//local message
				echo date('m/d/y h:i:s a' ,time())." ". $username. " has successfully Registered".PHP_EOL;
				//event
				
				$eventMessage = $username.' has successfully registered';
			        $sendLog = $logger->logArray('event',$eventMessage,__FILE__);
 			       	$testVar = $logClient->publish($sendLog);
	
				//return valid array
				$request = array();
				$request['valid']= true;
					return $request;
			}
			else{
				//error  registering ??
				//logging
				
				//return false array
				$request = array();
                       		$request['valid']= false;
                               	return $request;
			}
		}
		else {
			//local message - Already in DB
			echo date('m/d/y h:i:s a' ,time())." ".$username." is already in the Database".PHP_EOL;
			
			//event message - Already in DB
			$eventMessage = $username.' is already in the Database';
                        $sendLog = $logger->logArray('event',$eventMessage,__FILE__);
                        $testVar = $logClient->publish($sendLog);
			
			//return array: Valid = False
			$request = array();
			$request['valid']= false;
			return $request; 
		}
}
function requestProcessor($request){
	echo date('m/d/y h:i:s a' ,time())." received request".PHP_EOL;
	var_dump($request);
	if(!isset($request['type'])){
    		//logging
		$errorMessage = 'ERROR: unsupported message type';
                $sendLog = $logger->logArray('error',$errorMessage,__FILE__);
                $testVar = $logClient->publish($sendLog);
		//return
		return "ERROR: unsupported message type";
  	}
  switch ($request['type']){
    case "login":
      return doLogin($request['username'],$request['password']);
    case "register":
      return doRegister($request['username'],$request['password'],$request['email']);
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

