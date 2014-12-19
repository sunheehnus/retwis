<?
include("retwis.php");

if (!isLoggedIn()) {
    header("Location:".index.php);
    exit;
}

foreach ($_POST as $key => $value){
    if (empty($value)) {
        $refferer = $_SERVER['HTTP_REFERER'];
        header("Location:".$refferer);
        exit;
    }
    $cid = $key;
    $comment = $value;
}

$r = redisLink();
$commentid = $r->incr("next_comment_id");
$comment = str_replace("\n"," ",$comment);
$r->hmset("comment:$commentid","user_id",$User['id'],"time",time(),"body",$comment);
$r->lpush("comments:$cid", $commentid);

$refferer = $_SERVER['HTTP_REFERER'];
header("Location:".$refferer);
?>

