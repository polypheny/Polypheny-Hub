<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );

require_once( "access.php" );

if ( isset( $_POST[ "action" ] ) ) {
    $action = $_POST[ "action" ];
    $access = new Access();

    switch ( $action ) {
        case "login":
            echo $access->login( $_POST[ "username" ], $_POST[ "password" ] )->asJson();
            break;
        case "logout":
            $access->logout( $_POST[ "userId" ] );
            break;
        case "changePassword":
            echo $access->changePassword( $_POST[ "userId" ], $_POST[ "secret" ], $_POST[ "oldPw" ], $_POST[ "newPw1" ], $_POST[ "newPw2" ] )->asJson();
            break;
        case "checkLogin":
            echo json_encode( $access->isLoggedIn( $_POST[ "userId" ], $_POST[ "secret" ], true ) );
            break;
        case "getUsers":
            echo $access->getUsers( $_POST[ "userId" ], $_POST[ "secret" ] )->asJson();
            break;
        case "deleteUser":
            echo $access->deleteUser( $_POST[ "userId" ], $_POST[ "secret" ], $_POST[ "deleteUser" ] )->asJson();
            break;
        case "createUser":
            echo $access->createUser( $_POST[ "userId" ], $_POST[ "secret" ], $_POST[ "userName" ], $_POST[ "admin" ], $_POST[ "email" ] )->asJson();
            break;
        case "updateUser":
            echo $access->editUser( $_POST[ "adminId" ], $_POST[ "secret" ], $_POST[ "userId" ], $_POST[ "userName" ], $_POST[ "userPw" ], $_POST[ "userEmail" ], $_POST[ "userIsAdmin" ] )->asJson();
            break;
        case "getDatasets":
            echo $access->getDatasets( $_POST[ "userId" ], $_POST[ "secret" ] )->asJson();
            break;
        case "editDataset":
            echo $access->editDataset( $_POST[ "userId" ], $_POST[ "secret" ], $_POST[ "dsId" ], $_POST[ "name" ], $_POST[ "description" ], $_POST[ "pub" ] )->asJson();
            break;
        case "uploadDataset":
            $files = null;
            if (isset($_FILES["metaData"])) {
                $files = $_FILES["metaData"];
            }
            echo $access->uploadDataset($_POST[ "userId" ], $_POST[ "secret" ], $_POST[ "name" ], $_POST["description"], $_POST[ "pub" ], $_FILES[ "dataset" ], $files)->asJson();
            break;
        case "deleteDataset":
            echo $access->deleteDataset( $_POST[ "userId" ], $_POST[ "secret" ], $_POST[ "datasetId" ] )->asJson();
            break;
        default:
            $r = ( new Result() )->error( "The action '$action' does not exist." );
            echo $r->asJson();
    }
} else { ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>Polypheny Hub</title>
        <style>
            body {
                margin: 1rem;
                text-align: center;
            }

            img {
                width: 30%;
                margin: 0 auto;
                display: block;
            }

            .text {
                font-size: 1.5em;
            }

            .imgWrapper {
                margin-top: 1rem;
                padding: 5rem 0;
            }
        </style>
    </head>
    <body>
    <div class="imgWrapper">
        <img src="logo.png" alt="Polypheny Logo">
    </div>
    <p class="text">This API is intended to be used by the Polypheny UI. You can learn more about Polypheny on our <a href="https://polypheny.org/">homepage</a>.</p>
    </body>
    </html>
<?php } ?>
