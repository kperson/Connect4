<?php
require_once('init.php');
if(isset($_SESSION['member_id'])){
    redirect('members.php');
}
?>
<?php $errors = array(); ?>
<?php
if(!empty($_POST)){
    $username = trim($_POST['username']);
    if(empty($username)){
        $errors[] = 'Please enter a username.';
    }
    $password = trim($_POST['password']);
    if(empty($password)){
        $errors[] = 'Please enter a password';
    }
    if(empty($_POST['password2'])){
        $errors[] = 'Please confirm a password';
    }

    if(empty($errors) && $_POST['password'] != $_POST['password2']){
        $errors[] = 'Your passwords do not match';
    }
    else if(strlen($password) < 5){
        $errors[] = 'Password must be at least 5 characters.';
    }
    else{
        $member = registerUser($username, $password);
        if($member){
            $_SESSION['member_id'] = $member['member_id'];
            redirect('members.php');
        }
        else{
            $errors[] = 'This username already exists';
        }
    }
}
?>

<?php require_once('head.php'); ?>

<?php if(!empty($errors)) : ?>
    <ul>
        <?php foreach($errors as $error) : ?>
            <li><?php echo $error ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" action="">
    <label for="username">Username</label>
    <input type="text" name="username" value="<?php if(isset($_POST['username'])) echo trim($_POST['username']) ?>" /><br />

    <label for="password">Password</label>
    <input type="password" name="password" value="" /><br />

    <label for="password2">Confirm Password</label>
    <input type="password" name="password2" value="" /><br />

    <input type="submit" name="submitbutton" id="submitbutton" value="Register" />

</form>

<?php require_once('foot.php'); ?>