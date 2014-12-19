<?
require 'Predis/Autoloader.php';
Predis\Autoloader::register();

function getrand() {
    $fd = fopen("/dev/urandom","r");
    $data = fread($fd,16);
    fclose($fd);
    return md5($data);
}

function isLoggedIn() {
    global $User, $_COOKIE;

    if (isset($User)) return true;

    if (isset($_COOKIE['auth'])) {
        $r = redisLink();
        $authcookie = $_COOKIE['auth'];
        if ($userid = $r->hget("auths",$authcookie)) {
            if ($r->hget("user:$userid","auth") != $authcookie) return false;
            loadUserInfo($userid);
            return true;
        }
    }
    return false;
}

function loadUserInfo($userid) {
    global $User;

    $r = redisLink();
    $User['id'] = $userid;
    $User['username'] = $r->hget("user:$userid","username");
    return true;
}

function redisLink() {
    static $r = false;

    if ($r) return $r;
    $r = new Predis\Client();
    return $r;
}

# Access to GET/POST/COOKIE parameters the easy way
function g($param) {
    global $_GET, $_POST, $_COOKIE;

    if (isset($_COOKIE[$param])) return $_COOKIE[$param];
    if (isset($_POST[$param])) return $_POST[$param];
    if (isset($_GET[$param])) return $_GET[$param];
    return false;
}

function gt($param) {
    $val = g($param);
    if ($val === false) return false;
    return trim($val);
}

function utf8entities($s) {
    return htmlentities($s,ENT_COMPAT,'UTF-8');
}

function goback($msg) {
    include("header.php");
    echo('<div id ="error">'.utf8entities($msg).'<br>');
    echo('<a href="javascript:history.back()">Please return back and try again</a></div>');
    include("footer.php");
    exit;
}

function strElapsed($t) {
    $d = time()-$t;
    if ($d < 60) return "$d seconds";
    if ($d < 3600) {
        $m = (int)($d/60);
        return "$m minute".($m > 1 ? "s" : "");
    }
    if ($d < 3600*24) {
        $h = (int)($d/3600);
        return "$h hour".($h > 1 ? "s" : "");
    }
    $d = (int)($d/(3600*24));
    return "$d day".($d > 1 ? "s" : "");
}

function showComment($id) {
    $r = redisLink();
    $comments = $r->lrange("comments:".$id, 0, -1);
    foreach($comments as $cid) {
        $content = $r->hgetall("comment:".$cid);
        $userid = $content['user_id'];
        $username = $r->hget("user:$userid","username");
        $elapsed = strElapsed($content['time']);
        $userlink = "<a class=\"username\" href=\"profile.php?u=".urlencode($username)."\">".utf8entities($username)."</a>";
        $comment = utf8entities($content['body']);
        echo("&nbsp&nbsp&nbsp".$userlink.' '.utf8entities($content['body'])."<br/>");
        echo('&nbsp&nbsp&nbsp<i>commented '.$elapsed.' ago via web</i><br/>');
    }
}

function showPost($id) {
    $r = redisLink();
    $post = $r->hgetall("post:$id");
    if (empty($post)) return false;

    $userid = $post['user_id'];
    $username = $r->hget("user:$userid","username");
    $elapsed = strElapsed($post['time']);
    $userlink = "<a class=\"username\" href=\"profile.php?u=".urlencode($username)."\">".utf8entities($username)."</a>";
    $status = utf8entities($post['body']);
    $ref = $post['ref'];

    echo('<div class="post">'.$userlink.' '.$status);
    while($ref != -1) {
        $refpost = $r->hgetall("post:$ref");
        if (empty($refpost)) break;
        $refuserid = $refpost['user_id'];
        $refusername = $r->hget("user:$refuserid","username");
        $refelapsed = strElapsed($refpost['time']);
        $refuserlink = "<a class=\"username\" href=\"profile.php?u=".urlencode($refusername)."\">".utf8entities($refusername)."</a>";
        $refstatus = utf8entities($refpost['body']);
        echo('&nbsp||&nbsp'.$refuserlink.' '.$refstatus);
        $ref = $refpost['ref'];
    }
    echo('<br/>');
    echo('<i>posted '.$elapsed.' ago via web</i>');
?>
<br/>
<form method="POST">
<table>
<tr><td><textarea cols="70" rows="2" name=<?echo "$id";?>></textarea></td></tr>
<tr><td align="right"><input type="submit" formaction="repost.php" value="repost">
                      <input type="submit" formaction="comment.php" value="comment"></td></tr>
</table>
</form>
<br/>
<?
    showComment($id);
    echo("</div>");
    return true;
}

function showUserPosts($userid,$start,$count,$means="ALL") {
    $r = redisLink();
    if($means != "ALL")
        $key = "selfPosts:$userid";
    else
        $key = ($userid == -1) ? "timeline" : "posts:$userid";
    $posts = $r->lrange($key,$start,$start+$count-1);
    foreach($posts as $p) {
        showPost($p);
    }
    return count($posts) == $count;
}

function showUserPostsWithPagination($username,$userid,$start,$count,$means="ALL") {
    global $_SERVER;
    $thispage = $_SERVER['PHP_SELF'];

    $navlink = "";
    $next = $start+10;
    $prev = $start-10;
    $nextlink = $prevlink = false;
    if ($prev < 0) $prev = 0;

    $u = $username ? "&u=".urlencode($username) : "";
    if (showUserPosts($userid,$start,$count,$means))
        $nextlink = "<a href=\"$thispage?start=$next".$u."\">Older posts &raquo;</a>";
    if ($start > 0) {
        $prevlink = "<a href=\"$thispage?start=$prev".$u."\">&laquo; Newer posts</a>".($nextlink ? " | " : "");
    }
    if ($nextlink || $prevlink)
        echo("<div class=\"rightlink\">$prevlink $nextlink</div>");
}

function showLastUsers() {
    $r = redisLink();
    $users = $r->zrevrange("users_by_time",0,9);
    echo("<div>");
    foreach($users as $u) {
        echo("<a class=\"username\" href=\"profile.php?u=".urlencode($u)."\">".utf8entities($u)."</a> ");
    }
    echo("</div><br>");
}

?>
