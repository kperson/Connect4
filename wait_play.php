<?php
set_time_limit(0);
header('Cache-Control: no-cache');
header('Content-Type: text/javascript');
require_once('init.php');
$game_id = $_GET['game'];
$move_num = $_GET['moveNum'];
$game = gameData($_SESSION['member_id'], $game_id, false);
if($game){
    $i = 0;
    while($i < 8){
        $rs = wait($_SESSION['member_id'], $game_id, $move_num);
        if($rs){
            $game = gameData($_SESSION['member_id'], $game_id, false);
            $rs['stat'] = $game['status'];
            break;
        }
        else{
            $game = gameData($_SESSION['member_id'], $game_id, false);
            if($game['status'] == 'tie' || $game['status'] == 'won'){
                $game['stat'] = $game['status'];
                echo json_encode($game);
                exit();
            }
        }
        time_sleep_until(time() + 5);
        $i++;
    }
    if(!$rs){
        $rs['stat'] = 'not_found';
    }
    echo json_encode($rs);
}
?>