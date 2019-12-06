<?php
//header('Content-Type: application/json');
if (isset($_SERVER['HTTP_ORIGIN'])) {
  header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
  header('Access-Control-Allow-Credentials: true');
  header('Access-Control-Max-Age: 86400');    // cache for 1 day
}else{
  header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
	header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
	header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

  exit(0);
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once("access.php");

$action = $_POST["action"];
$access = new Access();

switch( $action ){
  case "login":
	echo $access->login($_POST["username"], $_POST["password"])->asJson();
	break;
	case "logout":
		$access->logout($_POST["userId"]);
		break;
	case "register":
		echo $access->register($_POST["username"], $_POST["password"])->asJson();
		break;
	case "changePassword":
		echo $access->changePassword($_POST["userId"], $_POST["secret"], $_POST["oldPw"], $_POST["newPw1"], $_POST["newPw2"])->asJson();
		break;
	case "checkLogin":
		echo json_encode($access->isLoggedIn( $_POST["userId"], $_POST["secret"], true ));
	break;
	case "getUsers":
		echo $access->getUsers( $_POST["userId"], $_POST["secret"] )->asJson();
		break;
	case "deleteUser":
		echo $access->deleteUser( $_POST["userId"], $_POST["secret"], $_POST["deleteUser"] )->asJson();
		break;
	case "createUser":
		echo  $access->createUser( $_POST["userId"], $_POST["secret"], $_POST["userName"], $_POST["admin"], $_POST["email"] )->asJson();
		break;
	case "updateUser":
		echo $access->editUser( $_POST["adminId"], $_POST["secret"], $_POST["userId"], $_POST["userName"], $_POST["userPw"], $_POST["userEmail"], $_POST["userIsAdmin"] )->asJson();
		break;
	case "getDatasets":
		echo $access->getDatasets( $_POST["userId"], $_POST["secret"] )->asJson();
		break;
	case "editDataset":
		echo $access->editDataset( $_POST["userId"], $_POST["secret"], $_POST["dsId"], $_POST["name"], $_POST["pub"] )->asJson();
		break;
	case "uploadDataset":
		echo $access->uploadDataset( $_POST["userId"], $_POST["secret"], $_POST["name"], $_POST["pub"], $_FILES["dataset"])->asJson();
		break;
	case "deleteDataset":
		echo $access->deleteDataset( $_POST["userId"], $_POST["secret"], $_POST["datasetId"] )->asJson();
		break;
  default:
	$r = (new Result())->error( "The action '$action' does not exsist." );
	echo $r->asJson();
}
