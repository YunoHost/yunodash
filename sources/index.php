<?php
// https://gist.github.com/aaronpk/3612742
require('app.php');

if ( !is_loggued() )
{
  require('header.php');

?>

<div class="container">

  <h3>Not logged in</h3>
  <p>
    <a href="login.php">Log In with GitHub</a>
  </p>

</div>

<?php
  require('footer.php');
}
else
{
  header('Location: main.php');
}

?>
