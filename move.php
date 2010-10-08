<?php
require_once('init.php');
header('Cache-Control: no-cache');
header('Content-Type: text/javascript');
$game_id = $_GET['game'];
$move_num = $_GET['moveNum'];
$col_num = $_GET['col'];
if(makeMove($_SESSION['member_id'], $game_id, $col_num)){
    echo json_encode(array('stat' => 'ok'));
}
else{
    echo json_encode(array('stat' => 'failure'));
}
?>