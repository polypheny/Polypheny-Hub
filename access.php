<?php
require_once( "result.php" );
require_once( "config.php" );

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
            $this->conn = new PDO( "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASSWORD );
        } catch ( PDOException $e ) {
            echo $e->getMessage();
        }
    }

    function __destruct() {
        $this->conn = null;
    }

    function hash( $pw ) {
        return password_hash( $pw, PASSWORD_BCRYPT );
    }

    function register( $user, $pw ) {
        $prep = $this->conn->prepare( "INSERT INTO `dsm_user` ( `user`, `password` ) VALUES(?,?)" );
        $prep->execute( array( $user, $this->hash( $pw ) ) );
        if ( $prep->errorCode() == 0 ) {
            //todo
        } else {
            if ( $prep->errorInfo()[ 0 ] == "23000" ) {
                return ( new Result() )->error( "Please choose another user name." );
            } else {
                return ( new Result() )->error( "The registration failed." );
            }
        }
    }

    function login( $user, $pw ) {
        $query = "SELECT `id`, `user`, `password`, `admin` FROM `dsm_user` WHERE `user` = ?";
        $prep = $this->conn->prepare( $query );
        $prep->execute( [ $user ] );
        $userResult = $prep->fetch( PDO::FETCH_ASSOC );
        if ( $prep->rowCount() != 1 ) {
            return ( new Result() )->error( "Login failed. The user $user does not exist" );
        }
        if ( password_verify( $pw, $userResult[ 'password' ] ) ) {
            $unique = uniqid();
            $secret = $this->hash( $unique );
            $insert = "INSERT INTO `dsm_session` ( `user_id`, `agent`, `secret`, `last_active` ) VALUES ( ?, ?, ?, NOW() )";
            $prep2 = $this->conn->prepare( $insert );
            $prep2->execute( [ $userResult[ "id" ], $this->getUserAgent(), $secret ] );
            return ( new Result() )->loginResult( $userResult[ "id" ], $userResult[ "user" ], $unique, $userResult[ "admin" ] );
        } else {
            return ( new Result() )->error( "Login failed, wrong password." );
        }
    }

    //see: https://www.codexworld.com/how-to/get-user-ip-address-php/
    function getUserAgent() {
        if ( !empty( $_SERVER[ 'HTTP_CLIENT_IP' ] ) ) {
            //ip from share internet
            $ip = $_SERVER[ 'HTTP_CLIENT_IP' ];
        } elseif ( !empty( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) ) {
            //ip pass from proxy
            $ip = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
        } else {
            $ip = $_SERVER[ 'REMOTE_ADDR' ];
        }
        $user_agent = $_SERVER[ "HTTP_USER_AGENT" ];
        preg_match( "/\((.*?)\)/", $user_agent, $regex );
        $computer = $regex[ 1 ];
        return $ip . ";" . $computer;
    }

    /**
     * Check whether a user is logged in or not.
     *
     * @param int $id user id
     * @param string $secret session secret stored in localStorage
     * @param boolean $refresh When true, the last_active field will be set to true. It makes sense to update the last_active field when the application is started and this method is called from rest.php
     * @param boolean $fromSpark True if request comes from java Spark server
     * @return int 0 if not logged in, 1 if normal user, 2 if admin user
     */
    function isLoggedIn( $id, $secret, $refresh = false, $fromSpark = false ) {
        if ( !isset( $secret ) ) {
            return LoginStatus::LOGGED_OUT;
        }
        if ( $fromSpark == false ) {
            $query = "SELECT `dsm_session`.`secret`, `admin` FROM `dsm_user` JOIN `dsm_session` ON `dsm_user`.`id` = `dsm_session`.`user_id`
				WHERE `dsm_session`.`user_id` = :id AND `dsm_session`.`agent` = :agent;";
        } else {
            $query = "SELECT `dsm_session`.`secret`, `admin` FROM `dsm_user` JOIN `dsm_session` ON `dsm_user`.`id` = `dsm_session`.`user_id`
				WHERE `dsm_session`.`user_id` = :id";
        }
        if ( $refresh && $fromSpark == false ) {
            $query .= "UPDATE `dsm_session` SET `last_active` = NOW() WHERE `user_id` = :id AND `agent` = :agent;";
        }
        $prep = $this->conn->prepare( $query );
        $prep->bindParam( ":id", $id );
        if ( $fromSpark == false ) {
            $agent = $this->getUserAgent();
            $prep->bindParam( ":agent", $agent );
        }
        $prep->execute();
        if ( $prep->rowCount() < 1 ) {
            return LoginStatus::LOGGED_OUT;
        }
        //else: iterate all secrets and see if one matches
        while ( $secret_db = $prep->fetch( PDO::FETCH_ASSOC ) ) {
            if ( password_verify( $secret, $secret_db[ "secret" ] ) ) {
                if ( $secret_db[ "admin" ] == 1 ) {
                    return LoginStatus::ADMIN;
                } else {
                    return LoginStatus::NORMAL_USER;
                }
            }
        }
        return LoginStatus::LOGGED_OUT;
    }

    function logout( $id ) {
        $delete = "DELETE FROM `dsm_session` WHERE `user_id` = ? AND `agent` = ?";
        $prep2 = $this->conn->prepare( $delete );
        $prep2->execute( [ $id, $this->getUserAgent() ] );
    }

    /**
     * Should be executed once a month to delete inactive sessions
     */
    function cronjob() {
        $delete = "DELETE FROM `dsm_session` WHERE `last_active` < DATE_SUB( NOW(), INTERVAL 1 MONTH )";
        $prep = $this->conn->prepare( $delete );
        $prep->execute();
    }

    function changePassword( $id, $secret, $old, $new1, $new2 ) {
        if ( $this->isLoggedIn( $id, $secret ) != LoginStatus::LOGGED_OUT ) {
            $query = "SELECT `password` from `dsm_user` WHERE `id` = ?";
            $prep = $this->conn->prepare( $query );
            $prep->execute( [ $id ] );
            $user_row = $prep->fetch( PDO::FETCH_ASSOC );
            if ( password_verify( $old, $user_row[ "password" ] ) ) {
                if ( $new1 != $new2 ) {
                    return ( new Result() )->error( "Could not change password: The new passwords do not match." );
                } else {
                    $query2 = "UPDATE `dsm_user` SET `password` = :pw WHERE `id` = :id";
                    $pw = $this->hash( $new1 );
                    $prep2 = $this->conn->prepare( $query2 );
                    $prep2->bindParam( ":pw", $pw );
                    $prep2->bindParam( ":id", $id );
                    $prep2->execute();
                    return ( new Result() )->message( "The password was changed." );
                }
            } else {
                return ( new Result() )->error( "Could not change password: The old password was wrong" );
            }
        }
        return ( new Result() )->error( "Could not change password. Please log in again." );
    }

    function getUsers( $id, $secret ) {
        if ( $this->isLoggedIn( $id, $secret ) == LoginStatus::ADMIN ) {
            $query = "SELECT `id`, `user`, `email`, `admin` FROM `dsm_user`";
            $prep = $this->conn->prepare( $query );
            $prep->execute();
            $result = $prep->fetchAll( PDO::FETCH_NUM );
            return ( new Result() )->header( [ "id", "user", "email", "admin" ] )->data( $result );
        } else {
            return ( new Result() )->error( "Please log in to view all users." );
        }
    }

    function deleteUser( $userId, $secret, $deleteUser ) {
        // todo delete all private datasets owned by this user
        if ( $this->isLoggedIn( $userId, $secret ) == LoginStatus::ADMIN ) {
            $query = "DELETE FROM `dsm_user` WHERE `id` = ?";
            $prep = $this->conn->prepare( $query );
            $prep->execute( [ $deleteUser ] );
            return ( new Result() )->message( "The user was deleted." );
        } else {
            return ( new Result() )->error( "You don't have the rights to delete a user." );
        }
    }

    function createUser( $userId, $secret, $userName, $admin, $email ) {
        if ( $this->isLoggedIn( $userId, $secret ) == LoginStatus::ADMIN ) {
            $uniqid = uniqid();
            $pw = $this->hash( $uniqid );
            $query = "INSERT INTO `dsm_user` ( `user`, `admin`, `email`, `password` ) VALUES ( :user, :admin, :email, :password )";
            $prep = $this->conn->prepare( $query );
            $prep->bindParam( ":user", $userName );
            $prep->bindParam( ":admin", intval( (bool)$admin ) );
            $prep->bindParam( ":email", $email );
            $prep->bindParam( ":password", $pw );
            $prep->execute();
            // mail($email, "Welcome to Polypheny-DB Hub", "Hello $userName<br>Welcome to <a href='#'>Polypheny-DB Hub</a>. Your password is $pw");
            return ( new Result() )->message( "The new user $userName was created. His password is: $uniqid" );
        } else {
            return ( new Result() )->error( "You don't have the rights to create a user." );
        }
    }

    function editUser( $adminId, $secret, $userId, $userName, $userPw, $userEmail, $userIsAdmin ) {
        if ( $this->isLoggedIn( $adminId, $secret ) == LoginStatus::ADMIN ) {
            $updatePw = "";
            if ( $userPw != "null" ) {
                $updatePw = ", `password` = :pw";
            }
            $query = "UPDATE `dsm_user` SET `user` = :name, `email` = :email, `admin` = :admin $updatePw WHERE `id` = :id";
            $prep = $this->conn->prepare( $query );
            if ( $userPw != "null" ) {
                $hash = $this->hash( $userPw );
                $prep->bindParam( ":pw", $hash );
            }
            $prep->bindParam( ":name", $userName );
            $prep->bindParam( ":email", $userEmail );
            $userIsAdmin = (int)filter_var( $userIsAdmin, FILTER_VALIDATE_BOOLEAN );
            $prep->bindParam( ":admin", $userIsAdmin );
            $userId = (int)$userId;
            $prep->bindParam( ":id", $userId );
            if ( $prep->execute() ) {
                return ( new Result() )->message( "The user was updated." );
            } else {
                return ( new Result() )->error( $prep->errorInfo() );
            }
        } else {
            return ( new Result() )->error( "You don't have the rights to update a user." );
        }
    }

    function getDatasets( $id, $secret ) {
        $result = null;
        $loginStatus = $this->isLoggedIn( $id, $secret );
        if ( $loginStatus == LoginStatus::NORMAL_USER ) {
            $query = "SELECT `name`, `uploaded`, `public`, `id`, `file`, `owner` FROM `dsm_dataset` WHERE `public` = 1 OR `owner` = ? ORDER BY `uploaded` DESC";
            $prep = $this->conn->prepare( $query );
            $prep->execute( [ $id ] );
            $result = $prep->fetchAll( PDO::FETCH_NUM );
        } else {
            if ( $loginStatus == LoginStatus::ADMIN ) {
                $query = "SELECT `name`, `uploaded`, `public`, `id`, `file`, `owner` FROM `dsm_dataset` ORDER BY `uploaded` DESC";
            } else {
                $query = "SELECT `name`, `uploaded`, `public`, `id`, `file`, `owner` FROM `dsm_dataset` WHERE `public` = 1 ORDER BY `uploaded` DESC";
            }
            $prep = $this->conn->prepare( $query );
            $prep->execute();
            $result = $prep->fetchAll( PDO::FETCH_NUM );
        }
        return ( new Result() )->data( $result )->header( [ "name", "uploaded" ] );
    }

    function editDataset( $userId, $secret, $dsId, $name, $public ) {
        //todo check if you have the rights to do so
        $loginStatus = $this->isLoggedIn( $userId, $secret );
        if ( $loginStatus == LoginStatus::NORMAL_USER && $userId == $dsId || $loginStatus == LoginStatus::ADMIN ) {
            $query = "UPDATE `dsm_dataset` SET `name` = :name, `public` = :public WHERE `id` = :id";
            $prep = $this->conn->prepare( $query );
            $prep->bindParam( ":id", $dsId );
            $prep->bindParam( ":name", $name );
            $prep->bindParam( ":public", intval( (bool)$public ) );
            $prep->execute();
            return ( new Result() )->message( "Updated dataset" );
        } else {
            return ( new Result() )->error( "You don't have the rights to update this dataset." );
        }
    }

    function uploadDataset( $userId, $secret, $name, $pub, $dataset ) {
        $loginStatus = $this->isLoggedIn( $userId, $secret, false, true );
        if ( $loginStatus === LoginStatus::LOGGED_OUT ) {
            return ( new Result() )->error( "File was not uploaded. Please log in first. user $userId secret $secret" );
        }

        $uniqueName = uniqid() . '.zip';
        $file = $this->uploadFolderPath . $uniqueName;
        if ( move_uploaded_file( $dataset[ "tmp_name" ], $file ) ) {
            $query = "INSERT INTO `dsm_dataset` ( `name`, `file`, `public`, `owner`, `uploaded` ) VALUES ( :name, :file, :pub, :owner, NOW() )";
            $prep = $this->conn->prepare( $query );
            $prep->bindParam( ":name", $name );
            $prep->bindParam( ":file", $uniqueName );
            $prep->bindParam( ":pub", intval( $pub ) );
            $prep->bindParam( ":owner", $userId );
            $prep->execute();
            return ( new Result() )->message( "Uploaded file." . mb_detect_encoding( $name ) );
        } else {
            return ( new Result() )->error( "Could not upload file" );
        }
    }

    function deleteDataset( $userId, $secret, $dsId ) {
        $loginStatus = $this->isLoggedIn( $userId, $secret );
        if ( $loginStatus == LoginStatus::LOGGED_OUT ) {
            return ( new Result() )->error( "Please log in to delete a dataset." );
        }
        $query = "SELECT `file`, `owner` FROM `dsm_dataset` WHERE `id` = :id";
        $prep = $this->conn->prepare( $query );
        $prep->bindParam( ":id", $dsId );
        $prep->execute();
        $dataset = $prep->fetch( PDO::FETCH_ASSOC );
        if ( $dataset[ "owner" ] != $userId && $loginStatus != LoginStatus::ADMIN ) {
            return ( new Result() )->error( "You don't have the rights to delete this dataset." );
        }
        $file = $this->uploadFolderPath . $dataset[ "file" ];
        if ( file_exists( $file ) ) {
            unlink( $file );
        }
        $query2 = "DELETE FROM `dsm_dataset` WHERE `id` = :id";
        $prep2 = $this->conn->prepare( $query2 );
        $prep2->bindParam( ":id", $dsId );
        $prep2->execute();
        return ( new Result() )->message( "The dataset was removed." );
    }

}
