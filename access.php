<?php
require_once ("result.php");

abstract class LoginStatus {
	const LOGGED_OUT = 0;
	const NORMAL_USER = 1;
	const ADMIN = 2;
}

class Access {

	private $conn;
	private $uploadFolderPath = "uploaded-files/";

	function __construct() {
		try {
			$this->conn = new PDO("mysql:host=localhost;dbname=unibas;charset=utf8", 'root', '');
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
	}

	function __destruct() {
		$this->conn = null;
	}

	function hash($pw) {
		return password_hash($pw, PASSWORD_BCRYPT);
	}

	function register($user, $pw) {
		$prep = $this->conn->prepare("INSERT INTO dsm_user (user, password) VALUES(?,?)");
		$prep->execute(array($user, $this->hash($pw)));
		if ($prep->errorCode() == 0) {
			//todo
		} else {
			if ($prep->errorInfo()[0] == "23000") {
				return (new Result())->error("Please choose another user name.");
			} else {
				return (new Result())->error( "The registration failed." );
			}
		}
	}

	function login($user, $pw) {
		$query = "SELECT `id`, `user`, `password`, admin FROM dsm_user WHERE user = ?";
		$prep = $this->conn->prepare($query);
		$prep->execute([$user]);
		$userResult = $prep->fetch(PDO::FETCH_ASSOC);
		if( $prep->rowCount() != 1 ){
			return (new Result())->error( "Login failed. The user $user does not exist" );
		}
		if (password_verify($pw, $userResult['password'])) {
			$unique = uniqid();
			$secret = $this->hash($unique);
			$update = "UPDATE dsm_user SET secret = ? WHERE id = ?";
			$prep2 = $this->conn->prepare( $update );
			$prep2->execute( [$secret, $userResult["id"]] );
			return (new Result())->loginResult( $userResult["id"], $userResult["user"], $unique, $userResult["admin"] );
		} else {
			return (new Result())->error( "Login failed, wrong password." );
		}
	}

	/**
	 * Check whether a user is logged in or not.
	 * @return 0 if not logged in, 1 if normal user, 2 if admin user
	 */
	function isLoggedIn( $id, $secret ) {
		if (! isset($secret)) {
			return LoginStatus::LOGGED_OUT;
		}
		$query = "SELECT secret, admin FROM dsm_user WHERE id = ?";
		$prep = $this->conn->prepare( $query );
		$prep->execute( [$id] );
		if( $prep->rowCount() != 1){
			return LoginStatus::LOGGED_OUT;
		}
		$secret_db = $prep->fetch( PDO::FETCH_ASSOC );
		if (password_verify($secret, $secret_db["secret"])) {
			if( $secret_db["admin"] == 1 ){
				return LoginStatus::ADMIN;
			} else {
				return LoginStatus::NORMAL_USER;
			}
		} else {
			return LoginStatus::LOGGED_OUT;
		}
	}

	function logout( $secret ) {
		$update = "UPDATE dsm_user SET secret = NULL WHERE secret = ?";
		$prep2 = $this->conn->prepare( $update );
		$prep2->execute( [$secret] );
	}

	function changePassword ( $id, $secret, $old, $new1, $new2 ) {
		if( $this->isLoggedIn( $id, $secret ) != LoginStatus::LOGGED_OUT ){
			$query = "SELECT password from dsm_user WHERE id = ?";
			$prep = $this->conn->prepare($query);
			$prep->execute( [$id] );
			$user_row = $prep->fetch(PDO::FETCH_ASSOC);
			if( password_verify( $old, $user_row["password"] )){
				if( $new1 != $new2 ){
					return (new Result())->error("Could not change password: The new passwords do not match.");
				} else {
					$query2 = "UPDATE dsm_user SET password = :pw WHERE id = :id";
					$pw = $this->hash($new1);
					$prep2 = $this->conn->prepare($query2);
					$prep2->bindParam( ":pw", $pw );
					$prep2->bindParam( ":id", $id );
					$prep2->execute();
					return (new Result())->message("The password was changed.");
				}
			} else {
				return (new Result())->error("Could not change password: The old password was wrong");
			}
		}
		return (new Result())->error("Could not change password. Please log in again.");
	}

	function getUsers ( $id, $secret ) {
		if( $this->isLoggedIn( $id, $secret ) == LoginStatus::ADMIN ){
			$query = "SELECT id, user, admin FROM dsm_user";
			$prep = $this->conn->prepare( $query );
			$prep->execute();
			$result = $prep->fetchAll(PDO::FETCH_NUM);
			return (new Result())->header( ["id", "user", "admin"] )->data($result);
		}
	}

	function deleteUser( $userId, $secret, $deleteUser ){
		//todo delete all his private datasets
		if( $this->isLoggedIn( $userId, $secret ) == LoginStatus::ADMIN ){
			$query = "DELETE FROM dsm_user WHERE id = ?";
			$prep = $this->conn->prepare( $query );
			$prep->execute([$deleteUser]);
			return (new Result())->message( "The user was deleted." );
		} else {
			return (new Result())->error( "You don't have the rights to delete a user." );
		}
	}

	function createUser( $userId, $secret, $userName, $admin, $email ){
		if( $this->isLoggedIn($userId, $secret ) == LoginStatus::ADMIN ){
			$uniqid = uniqid();
			$pw = $this->hash($uniqid);
			$query = "INSERT INTO dsm_user (user, admin, email, password) VALUES(:user, :admin, :email, :password)";
			$prep = $this->conn->prepare( $query );
			$prep->bindParam( ":user", $userName );
			$prep->bindParam( ":admin", intval((bool)$admin) );
			$prep->bindParam( ":email", $email );
			$prep->bindParam( ":password", $pw );
			$prep->execute();
			// mail($email, "Welcome to Polypheny-DB Hub", "Hello $userName<br>Welcome to <a href='#'>Polypheny-DB Hub</a>. Your password is $pw");
			return (new Result())->message( "The new user $userName was created. Debug: pw: $uniqid" );
		} else {
			return (new Result())->error( "You don't have the rights to create a user." );
		}
	}

	function getDatasets( $id, $secret ){
		$result = null;
		$loginStatus = $this->isLoggedIn( $id, $secret );
		if( $loginStatus == LoginStatus::NORMAL_USER ){
			$query = "SELECT name, uploaded, public, id FROM dsm_dataset WHERE public = 1 OR owner = ?";
			$prep = $this->conn->prepare( $query );
			$prep->execute( [$id] );
			$result = $prep->fetchAll(PDO::FETCH_NUM);
		} else {
			if( $loginStatus == LoginStatus::ADMIN ){
				$query = "SELECT name, uploaded, public, id FROM dsm_dataset";
			} else {
				$query = "SELECT name, uploaded, public, id FROM dsm_dataset WHERE public = 1";
			}
			$prep = $this->conn->prepare( $query );
			$prep->execute();
			$result = $prep->fetchAll(PDO::FETCH_NUM);
		}
		return (new Result())->data( $result )->header( ["name", "uploaded"] );
	}

	function editDataset( $id, $name, $public ){
		//todo check if you have the rights to do so
		$query = "UPDATE dsm_dataset SET name = :name, public = :public WHERE id = :id";
		$prep = $this->conn->prepare( $query );
		$prep->bindParam( ":id", $id );
		$prep->bindParam( ":name", $name );
		$prep->bindParam( ":public", intval((bool)$public) );
		$prep->execute();
		return (new Result())->message( "$id, $name, $public" );
	}

	function uploadDataset( $userId, $secret, $name, $pub, $dataset ){
		//https://stackoverflow.com/questions/13490112/how-to-save-base64-encoded-binary-data-to-zip-using-php
		$loginStatus = $this->isLoggedIn($userId, $secret);
		if( $loginStatus === LoginStatus::LOGGED_OUT ){
			return (new Result())->error("File was not uploaded. Please log in first.");
		}

		$zip_Array = explode( ";base64,", $dataset );
		$zip_contents = base64_decode($zip_Array[1]);
		$uniqueName = uniqid(). '.zip';
		$file = $this->uploadFolderPath . $uniqueName;
		if( file_put_contents($file, $zip_contents) ){
			$query = "INSERT INTO dsm_dataset (name, file, public, owner, uploaded) VALUES (:name, :file, :pub, :owner, NOW())";
			$prep = $this->conn->prepare( $query );
			$prep->bindParam( ":name", $name );
			$prep->bindParam( ":file", $uniqueName );
			$prep->bindParam( ":pub", intval($pub) );
			$prep->bindParam( ":owner", $userId );
			$prep->execute();
			return (new Result())->message("Uploaded file.");
		} else {
			return (new Result())->error("Could not upload file");
		}
	}

	function deleteDataset( $userId, $secret, $dsId ){
		//todo check rights
		/*$loginStatus = $this->isLoggedIn($userId, $secret);
		if( $loginStatus == LoginStatus::NORMAL_USER ){

		}*/
		$query = "SELECT file FROM dsm_dataset WHERE id = :id";
		$prep = $this->conn->prepare( $query );
		$prep->bindParam( ":id", $dsId );
		$prep->execute();
		$fileName = $prep->fetch(PDO::FETCH_ASSOC);
		$file = $this->uploadFolderPath . $fileName["file"];
		if( file_exists( $file )){
			unlink( $file );
		}
		$query2 = "DELETE FROM dsm_dataset WHERE id = :id";
		$prep2 = $this->conn->prepare( $query2 );
		$prep2->bindParam( ":id", $dsId );
		$prep2->execute();
		return (new Result())->message("The dataset was removed.");
	}

}
