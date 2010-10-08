<?php
function gameData($member_id, $game_id, $status = 'play'){
    $con = Connector::getInstance();
    if($status == false){
        $st = $con->prepare('SELECT * FROM game WHERE (member_one = ? OR member_two = ?) AND game_id = ?');
        $st->execute(array($member_id, $member_id, $game_id));
    }
    else{
        $st = $con->prepare('SELECT * FROM game WHERE (member_one = ? OR member_two = ?) AND status = ? AND game_id = ?');
        $st->execute(array($member_id, $member_id, $status, $game_id));
    }
    return $st->fetch(PDO::FETCH_ASSOC);
}

function wait($member_id, $game_id, $move_num){
    $con = Connector::getInstance();
    $st = $con->prepare('SELECT * FROM game_move WHERE player != ? AND game_id = ? AND move_num = ?');
    $st->execute(array($member_id, $game_id, $move_num));
    return $st->fetch(PDO::FETCH_ASSOC);
}

function makeMove($member_id, $game_id, $col_num){
    $con = Connector::getInstance();
    $st = $con->prepare('SELECT COUNT(*) AS ct FROM game_move, game WHERE game_move.game_id = ? AND game_move.game_id = game.game_id AND column_num = ? AND (game.member_one = ? OR game.member_two = ?)');
    $st->execute(array($game_id, $col_num, $member_id, $member_id));
    $tmp = $st->fetch(PDO::FETCH_ASSOC);
    $ct = $tmp['ct'];
    if($ct + 1 < 8){
        $game = gameData($member_id, $game_id);
        if($game['whose_turn'] == $member_id){
            try{
                $con->beginTransaction();
                $st1 = $con->prepare('SELECT COUNT(*) AS ct FROM game_move WHERE game_id = ?');
                $st1->execute(array($game_id));
                $tmp = $st1->fetch(PDO::FETCH_ASSOC);
                $ct2 = $tmp['ct'];
                if($ct2 < 49){
                    $timestamp = date('Y-m-d H:i:s');
                    $whose_turn = ($member_id == $game['member_one']) ? $game['member_two'] : $game['member_one'];
                    $st3 = $con->prepare('UPDATE game SET whose_turn = ?, last_move = ? WHERE game_id = ?');
                    $st3->execute(array($whose_turn, $timestamp, $game_id));
                    $st2 = $con->prepare('INSERT INTO game_move (column_num, move_num, game_id, player) VALUES (?, ?, ?, ?)');
                    $st2->execute(array($col_num, $ct2 + 1, $game_id, $member_id));
                    $con->commit();
                    return true;
                }
            }
            catch(Exception $e){
                echo $e->getMessage();
                $con->rollback();
            }
        }
    }
    return false;
}

function findByUsernamePassword($user_name, $password){
    $con = Connector::getInstance();
    $st = $con->prepare('SELECT * FROM member WHERE user_name = ?');
    $st->execute(array($user_name));
    $member = $st->fetch(PDO::FETCH_ASSOC);
    if($member){
        if(sha1($member['password_salt'].$password) == $member['password']){
            unset($member['password_salt'], $member['password']);
            return $member;
        }
    }
    return false;
}

function findByMemberId($member_id){
    $con = Connector::getInstance();
    $st = $con->prepare('SELECT user_name, created_at, member_id FROM member WHERE member_id = ?');
    $st->execute(array($member_id));
    return $st->fetch(PDO::FETCH_ASSOC);
}

function findByUserName($user_name){
    $con = Connector::getInstance();
    $st = $con->prepare('SELECT user_name, created_at, member_id FROM member WHERE user_name = ?');
    $st->execute(array($user_name));
    return $st->fetch(PDO::FETCH_ASSOC);
}

function registerUser($user_name, $password){
    $con = Connector::getInstance();
    try{
        $con->beginTransaction();
        if(!findByUserName($user_name)){
            $salt = substr(sha1(mt_rand(0, mt_getrandmax()).fgjifdo), 0, 10);
            $password = sha1($salt.$password);
            $timestamp = date('Y-m-d H:i:s');
            $go = true;

            $st2 = $con->prepare('SELECT * FROM member WHERE member_id = ?');
            while(true){
                $member_id = sha1(mt_rand(0, mt_getrandmax()).'jifojdfd');
                $st2->execute(array($member_id));
                if(!$st2->fetch(PDO::FETCH_ASSOC)){
                    break;
                }
            }
            $st = $con->prepare('INSERT INTO member (user_name, password, password_salt, created_at, member_id) VALUES (?, ?, ?, ?, ?)');
            $st->execute(array($user_name, $password, $salt, $timestamp, $member_id));
            $con->commit();
            return findByUserName($user_name);
        }
        return false;
    }
    catch(Exception $e){
        return false;
    }
}

function generateGrid($member_id, $game_id){
    $con = Connector::getInstance();
    $game = gameData($member_id, $game_id);
    if($game['member_one'] == $member_id || $game['member_two'] == $member_id){
        $arr = array();
        for($r = 0; $r < 7; $r++){
            for($c = 0; $c < 7; $c++){
                $arr[$r][$c] = -1;
            }
        }
        $st = $con->prepare('SELECT * FROM game_move WHERE game_id = ? ORDER BY move_num');
        $st->execute(array($game_id));
        $ct = 0;
        while($row = $st->fetch(PDO::FETCH_ASSOC)){
            $i = 0;
            while($arr[$i][$row['column_num']] != -1){
                $i++;
            }
            $arr[$i][$row['column_num']] = $row['player'];
            $ct++;
        }
        return array('grid' => $arr, 'size' => $ct);
    }
    return null;
}

function findAllUsers($exclude){
    $con = Connector::getInstance();
    $st = $con->prepare('SELECT user_name, member_id FROM member WHERE member_id != ? ORDER BY user_name');
    $st->execute(array($exclude));
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function waitToConnect($member_one, $member_two, $code){
    $con = Connector::getInstance();
    $timestamp = date('Y-m-d H:i:s', time() - 120);
    $st = $con->prepare('SELECT * FROM game WHERE member_one = ? AND member_two = ? AND code = ? AND created_at >= ? AND status = ?');
    $st->execute(array($member_one, $member_two, $code, $timestamp, 'play'));
    $game = $st->fetch(PDO::FETCH_ASSOC);
    if($game){
        return array('game_id' => $game['game_id'], 'status' => 'play', 'whose_turn' => $game['whose_turn']);
    }
    return array('status' => 'wait', 'code' => $code);
}

function connect($member_one, $member_two){
    $con = Connector::getInstance();
    try{
        $con->beginTransaction();
        $timestamp = date('Y-m-d H:i:s', time() - 120);
        $st = $con->prepare('SELECT * FROM game WHERE member_one = ? AND member_two = ? AND status = ? AND created_at >= ? ORDER BY created_at DESC LIMIT 1');
        $st->execute(array($member_two, $member_one, 'open', $timestamp));
        $game = $st->fetch(PDO::FETCH_ASSOC);
        if($game){
            $st2 = $con->prepare('UPDATE game SET status = ? WHERE game_id = ?');
            $st2->execute(array('play', $game['game_id']));
            $con->commit();
            return array('game_id' => $game['game_id'], 'status' => 'play', 'whose_turn' => $game['whose_turn']);
        }
        else{
            $timestamp = date('Y-m-d H:i:s');
            $code = sha1(mt_rand(0, mt_getrandmax()));

            $st4 = $con->prepare('SELECT * FROM game WHERE game_id = ?');
            while(true){
                $game_id = sha1(mt_rand(0, mt_getrandmax()).'jifzzdfd');
                $st4->execute(array($game_id));
                if(!$st4->fetch(PDO::FETCH_ASSOC)){
                    break;
                }
            }
            
            $whose_turn = (mt_rand(0, 1) == 0) ? $member_one : $member_two;
            $st3 = $con->prepare('INSERT INTO game (member_one, member_two, created_at, whose_turn, status, code, game_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $st3->execute(array($member_one, $member_two, $timestamp, $whose_turn, 'open', $code, $game_id));
            $con->commit();
            return array('status' => 'wait', 'code' => $code);
        }
    }
    catch(Exception $e){
        echo $e->getMessage();
        $con->rollback();
    }
}

function setWinner($winner, $game_id){
    $con = Connector::getInstance();
    $st = $con->prepare('UPDATE game SET winner = ?, status = ? WHERE game_id = ?');
    $st->execute(array($winner, 'won', $game_id));
}

function setTie($game_id){
    $con = Connector::getInstance();
    $st = $con->prepare('UPDATE game SET winner = ?, status = ? WHERE game_id = ?');
    $st->execute(array($winner, 'tie', $game_id));
}

function requestTimeWin($member_id, $game_id){
    $game = gameData($member_id, $game_id);
    if(time() - strtotime($game['last_move']) > 30){
        if($game['whose_turn'] != $member_id){
            setWinner($member_id, $game_id);
        }
    }
}
?>