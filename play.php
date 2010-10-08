<?php require_once('init.php');
$game = gameData($_SESSION['member_id'], $_GET['game']);
?>

<?php require_once('head.php'); ?>

<style type="text/css">
    #board{
        text-align: center;
    }
    #board tbody{
        background-color: #0033cc;
    }

    #board tbody tr{
        height:60px;
    }
    #board td{
        width:60px;
        border:1px solid black;
    }
</style>

<script type="text/javascript">
    //Create Board
    var BOARD = new Array(7);
    var GAME_STATUS = 'play';
    var GAME_ID = '<?php echo $game['game_id']?>';
    var WAIT_NUM = 1;
    var WHOSE_TURN = '<?php echo $game['whose_turn'] ?>';
    var YOUR_ID = '<?php echo $_SESSION['member_id']?>';
    var OPP_ID = (YOUR_ID == '<?php echo $game['member_one'] ?>') ? '<?php echo $game['member_two'] ?>' : '<?php echo $game['member_one'] ?>';
    var TIMER;
    var SECS;
    var INTERUPT = 0;

    for(i = 0; i < BOARD.length; i++){
        BOARD[i] = new Array(7);
    }

    //Populate Board
    for(r = 0; r < BOARD.length; r++){
        for(c = 0; c < BOARD[r].length; c++){
            BOARD[r][c] = -1;
        }
    }

    function updateBoard(r, column, playerId){
        BOARD[r][column] = playerId;
        WHOSE_TURN = (playerId == YOUR_ID) ? OPP_ID : YOUR_ID;
        updateMoveUI(playerId, r, column);
        WAIT_NUM++;
    }

    function winner(){
        for(var r = 0; r < 7; r++){
            for(var c = 0; c < 7; c++){
                var rs = checkDirection(r, c, 1, 1);
                if(rs != -1){
                    break;
                }
                rs = checkDirection(r, c, -1, -1);
                if(rs != -1){
                    break;
                }
                rs = checkDirection(r, c, 0, 1);
                if(rs != -1){
                    break;
                }
                rs = checkDirection(r, c, 1, 0);
                if(rs != -1){
                    break;
                }
            }
            if(rs != -1){
                break;
            }
        }
        return rs;
    }

    function runDate(){
        if(SECS >= 0){
            if(INTERUPT != 1){
                $('#clock').html(SECS);
                SECS--;
                if(TIMER){
                    clearTimeout(TIMER);
                }
                TIMER = setTimeout("runDate()", 1000);
            }
        }
        else{
            GAME_STATUS = 'timed_out';
            if(WHOSE_TURN != YOUR_ID){
                $.get('win.php', {game: GAME_ID}, function(data) {});
            }
            updateExpireUI();
        }
    }

    function checkDirection(r, c, x, y){
        if(BOARD[r][c] != -1){
            var ct = 0;
            while(ct < 4){
                var my_x = ct * x + r;
                var my_y = ct * y + c;
                if(my_x > 6 || my_y > 6 || my_y < 0 || my_x < 0){
                    return -1;
                }
                if(BOARD[my_x][my_y] != BOARD[r][c]){
                    return -1;
                }
                ct++;
            }
            return BOARD[r][c];
        }
        return -1;
    }


    //Zero Indexed
    function makeMove(playerId, column){
        for(var r = 0; r < 7; r++){
            if(BOARD[r][column] == -1){
                
                if(playerId == YOUR_ID){
                    INTERUPT = 1;
                    $.get('move.php', {game: GAME_ID , col: column, moveNum: WAIT_NUM}, function(data) {
                        var obj = jQuery.parseJSON(data);
                        if(obj.stat == 'ok'){
                            updateBoard(r, column, playerId);
                            updateMoveUI(playerId, r, column);
                            if(WAIT_NUM == 50){
                                sendEnd();
                            }
                            listen();
                        }
                        else{
                            updateFailureUI();
                        }
                    });
                    updateSendingUI();
                }
                else{
                    updateBoard(r, column, playerId);
                    updateMoveUI(playerId, r, column);
                    var win = winner();
                    if(win != -1 || WAIT_NUM == 50){
                        sendEnd();
                    }
                }
                break;
            }
        }
    }

    function sendEnd(){
       $.get('end.php', {game: GAME_ID}, function(data) {
           if(data != ''){
               console.log(data);
              var obj = jQuery.parseJSON(data);
              if(obj.stat == 0 || obj.stat != -1){
                   GAME_STATUS = 'OVER';
                   updateWinnerUI(obj.stat);
              }
           }
       });
    }

    function timeIt(secs){
        SECS = secs;
        INTERUPT = 0;
        runDate();
    }

    function listen(){
        $.get('wait_play.php', {game: GAME_ID , moveNum: WAIT_NUM}, function(data) {
            console.log(data);
            INTERUPT = 1;
            var obj = jQuery.parseJSON(data);
            if(obj.stat == 'not_found'){
                updateExpireUI();
            }
            else if(obj.stat == 'play'){
                makeMove(OPP_ID, parseInt(obj.column_num));
                timeIt(30);
            }
            else if(obj.stat == 'tie'){
                updateWinnerUI(0);
            }
            else if(obj.stat == 'won'){
                updateWinnerUI(obj.winner);
            }
        });
        timeIt(37);

    }

    function init(){
        if(WHOSE_TURN == YOUR_ID){
            $('#status').html("Your Turn");
            timeIt(30);
        }
        else{
            $('#status').html("Opponent's Turn");
            listen();
        }
    }

    function updateFailureUI(){
        $('#status').html("Unknown Error");
    }

    function updateExpireUI(){
        $('#status').html("Clock has expired");
    }

    function updateSendingUI(){
        $('#status').html("Sending");
    }

    function updateWinnerUI(winner){
        if(winner == YOUR_ID){
            $('#status').html('You Won!');
        }
        else if(winner == 0){
            $('#status').html('Tied Game');
        }
        else if(winner != -1){
            $('#status').html('You Lost');
        }
        $('#playagain').html('Play Again?');
        $('#playagain').attr('href', 'lobby.php?member=' + OPP_ID);


    }

    function updateMoveUI(playerId, row, column){
       var newrow = row + 1;
       var newcol = column + 1;
       var selector = '#' + newrow + '' + newcol + '';
        if(playerId == YOUR_ID){
            $(selector).html('<img src="images/black.jpg" width="100%" height="100%" />');
            $('#status').html("Opponent's Turn");
        }
        else{
            $(selector).html('<img src="images/red.jpg" width="100%" height="100%" />');
            $('#status').html("Your Turn");
        }        
    }

    $(document).ready(function() {
       init();
        //SetHandlers
        $('#board button').click(function() {
            if(WHOSE_TURN == YOUR_ID && GAME_STATUS == 'play'){
                var column = parseInt($(this).attr('id').substr(1));
                makeMove(YOUR_ID, column - 1);
            }
        });
    });
</script>

<h2><span id="status">&nbsp;</span> - <span id="clock"></span></h2>

<table id="board" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <td><button id="b1">Drop</button></td>
            <td><button id="b2">Drop</button></td>
            <td><button id="b3">Drop</button></td>
            <td><button id="b4">Drop</button></td>
            <td><button id="b5">Drop</button></td>
            <td><button id="b6">Drop</button></td>
            <td><button id="b7">Drop</button></td>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td id="71">&nbsp;</td>
            <td id="72">&nbsp;</td>
            <td id="73">&nbsp;</td>
            <td id="74">&nbsp;</td>
            <td id="75">&nbsp;</td>
            <td id="76">&nbsp;</td>
            <td id="77">&nbsp;</td>
        </tr>

        <tr>
            <td id="61">&nbsp;</td>
            <td id="62">&nbsp;</td>
            <td id="63">&nbsp;</td>
            <td id="64">&nbsp;</td>
            <td id="65">&nbsp;</td>
            <td id="66">&nbsp;</td>
            <td id="67">&nbsp;</td>
        </tr>

        <tr>
            <td id="51">&nbsp;</td>
            <td id="52">&nbsp;</td>
            <td id="53">&nbsp;</td>
            <td id="54">&nbsp;</td>
            <td id="55">&nbsp;</td>
            <td id="56">&nbsp;</td>
            <td id="57">&nbsp;</td>
        </tr>

        <tr>
            <td id="41">&nbsp;</td>
            <td id="42">&nbsp;</td>
            <td id="43">&nbsp;</td>
            <td id="44">&nbsp;</td>
            <td id="45">&nbsp;</td>
            <td id="46">&nbsp;</td>
            <td id="47">&nbsp;</td>
        </tr>

        <tr>
            <td id="31">&nbsp;</td>
            <td id="32">&nbsp;</td>
            <td id="33">&nbsp;</td>
            <td id="34">&nbsp;</td>
            <td id="35">&nbsp;</td>
            <td id="36">&nbsp;</td>
            <td id="37">&nbsp;</td>
        </tr>

        <tr>
            <td id="21">&nbsp;</td>
            <td id="22">&nbsp;</td>
            <td id="23">&nbsp;</td>
            <td id="24">&nbsp;</td>
            <td id="25">&nbsp;</td>
            <td id="26">&nbsp;</td>
            <td id="27">&nbsp;</td>
        </tr>

        <tr>
            <td id="11">&nbsp;</td>
            <td id="12">&nbsp;</td>
            <td id="13">&nbsp;</td>
            <td id="14">&nbsp;</td>
            <td id="15">&nbsp;</td>
            <td id="16">&nbsp;</td>
            <td id="17">&nbsp;</td>
        </tr>
    </tbody>
</table>

<a id="playagain" href="#">&nbsp;</a>

<?php require_once('foot.php'); ?>