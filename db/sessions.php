<?php
class SessionManager {

    var $life_time;

    function SessionManager() {
        // Read the maxlifetime setting from PHP
        $this->life_time = get_cfg_var("session.gc_maxlifetime");

        // Register this object as the session handler
        session_set_save_handler(
                array(&$this, "open"),
                array(&$this, "close"),
                array(&$this, "read"),
                array(&$this, "write"),
                array(&$this, "destroy"),
                array(&$this, "gc")
        );
    }

    function open($save_path, $session_name) {
        global $sess_save_path;
        $sess_save_path = $save_path;
        return true;
    }

    function close() {
        return true;
    }

    function read($id) {
        $con = Connector::getInstance();
        $st = $con->prepare('SELECT session_data FROM sessions WHERE session_id = ? AND expires > ?');
        $st->execute(array($id, time()));
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if($row){
            return $row['session_data'];
        }
        return '';
    }

    function write($id, $data) {
        $time = time() + $this->life_time;
        $con = Connector::getInstance();
        try{
            $con->beginTransaction();
            $st1 = $con->prepare('SELECT * FROM sessions WHERE session_id = ?');
            $st1->execute(array($id));
            if($st1->fetch(PDO::FETCH_ASSOC)){
                $st2 = $con->prepare('UPDATE sessions SET session_data = ?, expires = ? WHERE session_id = ?');
                $st2->execute(array($data, $time, $id));
            }
            else{
                $st3 = $con->prepare('INSERT INTO sessions (session_data, session_id, expires) VALUES (?, ?, ?)');
                $st3->execute(array($data, $id, $time));
            }
            $con->commit();
        }        
        catch(Exception $e){
            $con->rollback();
        }
        return true;
    }

    function destroy($id) {
        $con = Connector::getInstance();
        try{
            $con->beginTransaction();
            $st = $con->prepare('DELETE FROM sessions WHERE session_id = ?');
            $st->execute(array($id));
            $con->commit();
        }
        catch(Exception $e){
            $con->rollback();
        }
        return true;
    }

    function gc() {
        $con = Connector::getInstance();
        try{
            $con->beginTransaction();
            $st = $con->prepare('DELETE FROM sessions WHERE expires < ?');
            $st->execute(array(time()));
            $con->commit();
        }
        catch(Exception $e){
            $st->rollback();
        }
        return true;
    }
}
?>