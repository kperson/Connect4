<?php
require_once('init.php');
if(isset($_SESSION['member_id'])){
    redirect('members.php');
}
?>


<form method="post" action="">
    <label for="username">Username</label>
    <input type="text" name="username" value="" /><br />

    <label for="password">Password</label>
    <input type="text" name="password" value="" /><br />

    <label for="password2">Confirm Password</label>
    <input type="text" name="password2" value="" /><br />
</form>