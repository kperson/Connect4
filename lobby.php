<?php
require_once('init.php');
if(!isset($_SESSION['member_id'])){
    redirect('login.php');
}
$errors = array();
$member_id = $_GET['member'];
if($member_id != $_SESSION['member_id']){
    $member = findByMemberId($member_id);
    if(!$member){
        $errors[] = 'Member not found';
    }
}
else{
    $errors[] = 'You can not play against yourself';
}
if(empty($errors)){
    $game_data = connect($_SESSION['member_id'], $member_id);
    if($game_data['status'] == 'play'){
        redirect('play.php?game='.$game_data['game_id']);
    }
    else if($game_data['status'] == 'wait'){
        $code = $game_data['code'];
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

<?php else: ?>

    <h1>Connecting</h1>

    <script type="text/javascript">
     
        var checkCt = 0;

        function check(){
            $.get("wait_lobby.php", {member_one: '<?php echo $_SESSION['member_id']; ?>', member_two: '<?php echo $member['member_id'] ;?>', code: '<?php echo $code ;?>'}, function(data) {
                var obj = jQuery.parseJSON(data);
                if(obj.status == 'play'){
                    window.location = 'play.php?game=' + obj.game_id;
                }
                else{
                    checkCt++;
                    if(checkCt < 4){
                        check();
                    }
                }
            });
        }

        $(document).ready(function() {
            check();
        });
   
    </script>

<?php endif; ?>

<?php require_once('foot.php'); ?>