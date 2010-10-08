<?php
require_once('init.php');
header('Cache-Control: no-cache');
header('Content-Type: text/javascript');
$data = generateGrid($_SESSION['member_id'], $_GET['game']);
if(!empty($data)){
    $winner = winner($data);
    if($winner != -1){
        setWinner($winner, $_GET['game']);
    }
    else if($winner == 0){
        setTie($_GET['game']);
    }
    echo json_encode(array('stat' => $winner));
}
?>