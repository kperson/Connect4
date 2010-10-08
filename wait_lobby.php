<?php
set_time_limit(0);
header('Cache-Control: no-cache');
header('Content-Type: text/javascript');
require_once('init.php');
$member_one = $_GET['member_one'];
$member_two = $_GET['member_two'];
$code = $_GET['code'];

$i = 0;
while($i < 8){
    $rs = waitToConnect($member_one, $member_two, $code);
    if($rs['status'] == 'play'){
        echo json_encode($rs);
        exit();
    }
    time_sleep_until(time() + 5);
    $i++;
}
echo json_encode($rs);
?>