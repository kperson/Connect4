<?php
require_once('init.php');
if(!isset($_SESSION['member_id'])){
    redirect('login.php');
}
$users = findAllUsers($_SESSION['member_id']);
?>

<?php require_once('head.php'); ?>

<table>
    <tr>
        <th>User name</th>
        <th>Play</th>
    </tr>
    <?php foreach($users as $user) : ?>
    <tr>
        <td><?php echo $user['user_name'] ?></td>
        <td><a href="lobby.php?member=<?php echo $user['member_id'] ?>">Play</a></td>
    </tr>
    <?php endforeach; ?>
</table>

<?php require_once('foot.php'); ?>