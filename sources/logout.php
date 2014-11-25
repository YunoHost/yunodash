<?php
require('app.php');

/* Clean up */
unset($_SESSION['state']);
unset($_SESSION['access_token']);

/* Redirect to index */
header('Location: index.php');
die();

?>