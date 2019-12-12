<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );

require_once( "access.php" );
$access = new Access();
if ( $access->isLoggedIn( 2, "5dd3bec530274" ) ) {
    echo "Welcome.<br>";
} else {
    echo "Not logged in.<br>";
}

?>

<h5>Upload file</h5>
<form action="rest.php" method="post" enctype="multipart/form-data">
    <input type="file" name="file[]" multiple>
    <input type="hidden" name="action" value="file-upload">
    <input type="submit" name="submit">
</form>

<hr>
login
<form action="rest.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="login">
    <input type="text" name="username" placeholder="username">
    <input type="password" name="password" placeholder="password">
    <input type="submit" name="submit">
</form>


<hr>
register
<form action="rest.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="register">
    <input type="text" name="username" placeholder="username">
    <input type="password" name="password" placeholder="password">
    <input type="submit" name="submit">
</form>
<hr>

check login
<form action="rest.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="checkLogin">
    <input type="text" name="id" placeholder="id">
    <input type="text" name="secret" placeholder="secret">
    <input type="submit" name="submit">
</form>
<hr>

get Datasets
<form action="rest.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="getDatasets">
    <input type="text" name="id" placeholder="id">
    <input type="text" name="secret" placeholder="secret">
    <input type="submit" name="submit">
</form>
<hr>

<form action="rest.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="logout">
    <input type="hidden" name="id" value="2">
    <input type="submit" name="submit" value="logout">
</form>

<hr>
<form action="rest.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="get-stores">
    <input type="submit" name="submit" value="getStores">
</form>
