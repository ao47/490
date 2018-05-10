#!/usr/bin/php
<?php
//include("account.php");
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('logger.inc');
//private $con;
echo date('m/d/y h:i:s a' ,time()).PHP_EOL;
echo "Listener running, awaiting messages from RABBIT ...".PHP_EOL;

$emailId;
$userId;

function doLogin($user,$pass){

	//logging variables
	$logClient = new rabbitMQClient('toLog.ini', 'testServer');
	$logger = new Logger();
		

	$con = mysqli_connect("localhost","root","12345","users");
	//check for connection error and logging
	if(!$con){
		$errorMessage = 'Connection Error: '.mysqli_connect_error();
                $sendLog = $logger->logArray('error',$errorMessage,__FILE__);
                $testVar = $logClient->publish($sendLog);
		die("Connection Error: ".mysqli_connect_error());
	}

	
	//Event - connected to DB
	$eventMessage = 'Successfully Connected to Database';
	$sendLog = $logger->logArray('event',$eventMessage,__FILE__);
	$testVar = $logClient->publish($sendLog);

	echo date('m/d/y h:i:s a' ,time())." Successfully Connected to Database".PHP_EOL;
	echo date('m/d/y h:i:s a' ,time())." Username: ".$user." is attempting to Login".PHP_EOL;
	$eventMessage = "Username: ".$user." is attempting to login";
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
			echo date('m/d/y h:i:s a' ,time())." ".$user. "'s password has been verified".PHP_EOL;		
			echo date('m/d/y h:i:s a' ,time())." Username:".$user." has logged in".PHP_EOL;
	

			//Logging - Logged in
			$eventMessage = 'Username: '.$user.' has logged in.';
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
		echo date('m/d/y h:i:s a' ,time())." Username: ".$user."'s Password is invalid".PHP_EOL;
		//logging - Wrong password
		$eventMessage = 'Username: '.$user.' \'s password is incorrect';
        	$sendLog = $logger->logArray('event',$eventMessage,__FILE__);
        	$testVar = $logClient->publish($sendLog);


                return array("valid" => false);

		}
	}
	else {
		echo date('m/d/y h:i:s a' ,time())." Username: ".$user." Doesn't Exist ".PHP_EOL;	
		//logging - User doesnt exist
		$eventMessage = 'Username: '.$user.' doesn\'t exist';
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
                $errorMessage = 'Connection Error: '.mysqli_connect_error();
                $sendLog = $logger->logArray('error',$errorMessage,__FILE__);
                $testVar = $logClient->publish($sendLog);
                die("Connection Error: ".mysqli_connect_error());
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
				echo date('m/d/y h:i:s a' ,time())." Username: ".$username." has successfully Registered".PHP_EOL;
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
			echo date('m/d/y h:i:s a' ,time())." Username: ".$username." is already in the Database".PHP_EOL;
			
			//event message - Already in DB
			$eventMessage = 'Username: '.$username.' is already in the Database';
                        $sendLog = $logger->logArray('event',$eventMessage,__FILE__);
                        $testVar = $logClient->publish($sendLog);
			
			//return array: Valid = False
			$request = array();
			$request['valid']= false;
			return $request; 
		}
}

//Add comment to forum page
function doAddComment($commentError,$commentName,$commentContent,$parentCommentId){
	//logging variables
	$logClient = new rabbitMQClient('toLog.ini', 'testServer');
	$logger = new Logger();


	try{
		echo date('m/d/y h:i:s a' ,time()).'comment error:'.$commentError .PHP_EOL.date('m/d/y h:i:s a' ,time()).' CommentName: '.$commentName.PHP_EOL.date('m/d/y h:i:s a' ,time()).' commentContent: '.$commentContent.PHP_EOL.date('m/d/y h:i:s a' ,time()).' CommentID: '.$parentCommentId.PHP_EOL;
		$connect = new PDO('mysql:host=localhost;dbname=comments', 'root', '12345');
		$comment_error = '';

		//event
		$eventMessage = 'Successfully Connected to Database while trying to add a comment';
		$sendLog = $logger->logArray('event',$eventMessage,__FILE__);
		$testVar = $logClient->publish($sendLog);
	
	
		$valid = true;
		$query = "INSERT INTO comment (parent_comment_id, comment, comment_sender_name) VALUES (:parent_comment_id, :comment, :comment_sender_name)";
		$statement = $connect->prepare($query);
		$statement->execute(
		array(':parent_comment_id' => $parentCommentId,':comment' => $commentContent,':comment_sender_name' => $commentName));
		$comment_error = '<label class="text-success">Comment Added</label>';
		echo "Insterted into DB Successfully".PHP_EOL;
		echo "Comment error is : ".$comment_error.PHP_EOL;
	//}
	}
	catch(PDOException $e)
	{
		echo "Error: ". $e->getMessage();
		$errorMessage = $e->getMessage();
		$sendLog = $logger->logArray('error',$errorMessage,__FILE__);
        $testVar = $logClient->publish($sendLog);
	}	
	
	$data = array(
		'error'  => $comment_error);
	echo date('m/d/y h:i:s a' ,time())." data: ".json_encode($data).PHP_EOL;
	$request = array();
	array_push($request,$data);
	return $request;
}		
// Gets the existing comments, and new comments on forum page

function doGetComment(){
	//logging
	$logClient = new rabbitMQClient('toLog.ini', 'testServer');
	$logger = new Logger();

	global $output;
	$connect = new PDO('mysql:host=localhost;dbname=comments', 'root', '12345');
	$query = "SELECT * FROM comment WHERE parent_comment_id = '0' ORDER BY comment_id DESC";
	$statement = $connect->prepare($query);
	$statement->execute();

	$result = $statement->fetchAll();
	$output = '';
	foreach($result as $row){
		error_reporting(0);
		$output .= '<div class="panel panel-default"><div class="panel-heading">By <b>'.$row["comment_sender_name"].'</b> on <i>'.$row["date"].'</i></div><div class="panel-body">'.$row["comment"].'</div><div class="panel-footer" align="right"><button type="button" class="btn btn-default reply" id="'.$row["comment_id"].'">Reply</button></div></div>';
		$output .= get_reply_comment($connect, $row["comment_id"]);
		//echo date('m/d/y h:i:s a' ,time())." ".$output.PHP_EOL;
	}
		$request =array();
		$request['output'] = $output;
		//echo to the command line
		echo "Output : ".$output.PHP_EOL;
		//event logging
		$eventMessage = 'Successfully retrieved the output. The output is : '.$output;
		$sendLog = $logger->logArray('event',$eventMessage,__FILE__);
		$testVar = $logClient->publish($sendLog);		
		//return request array	
		return $request;
}

	
	function get_reply_comment($connect, $parent_id = 0, $marginleft = 0){
		global $output;
		$query = "SELECT * FROM comment WHERE parent_comment_id = '".$parent_id."'";
		//$output = '';
		$statement = $connect->prepare($query);
		$statement->execute();
		$result = $statement->fetchAll();
		$count = $statement->rowCount();
		if($parent_id == 0){
			$marginleft = 0;
		}
		else{
			$marginleft = $marginleft + 48;
		}
		if($count > 0){
			foreach($result as $row){
				$output .= '<div class="panel panel-default" style="margin-left:'.$marginleft.'px"><div class="panel-heading">By <b>'.$row["comment_sender_name"].'</b> on <i>'.$row["date"].'</i></div><div class="panel-body">'.$row["comment"].'</div><div class="panel-footer" align="right"><button type="button" class="btn btn-default reply" id="'.$row["comment_id"].'">Reply</button></div></div>';
				$output .= get_reply_comment($connect, $row["comment_id"], $marginleft);
			}
		}
	}




function requestProcessor($request){
	$logClient = new rabbitMQClient('toLog.ini', 'testServer');
        $logger = new Logger();

	echo date('m/d/y h:i:s a' ,time())." received request".PHP_EOL;
	var_dump($request);
	if(!isset($request['type'])){
		echo "Error: Type not set".PHP_EOL;
		$errorMessage = 'ERROR: type not Set';
        $sendLog = $logger->logArray('error',$errorMessage,__FILE__);
        $testVar = $logClient->publish($sendLog);
		//return
		return "ERROR: Type not Set";
	}

  switch ($request['type']){
  	case "login":
		return doLogin($request['username'],$request['password']);
	case "register":
		return doRegister($request['username'],$request['password'],$request['email']);
	case "addComment":
		return doAddComment($request['error'],$request['commentName'],$request['commentContent'],$request['parentCommentId']);
	case "getComment":
		return doGetComment();
	default:{
		echo "Error: Incorrect Type. Expecting Types login, register, addComment, getComment. Type is ".$request['type'].PHP_EOL;
		$errorMessage = "Error: Incorrect Type. Expecting Types login, register, addComment, getComment. Type is ".$request['type'];
        $sendLog = $logger->logArray('error',$errorMessage,__FILE__);
        $testVar = $logClient->publish($sendLog);
            }
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
