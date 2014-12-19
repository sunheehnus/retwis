<?
include("retwis.php");

if (!isLoggedIn()) {
    header("Location:".index.php);
    exit;
}

foreach ($_POST as $key => $value){
    if (empty($value)) {
        $value = "REPOST";
    }
    $cid = $key;
    $status = $value;
}
$r = redisLink();
$postid = $r->incr("next_post_id");
$r->hmset("post:$postid","user_id",$User['id'],"time",time(),"body",$status,"ref",$cid);
$followers = $r->zrange("followers:".$User['id'],0,-1);
$userID = $User['id'];
$followers[] = $userID; /* Add the post to our own posts too */

$r->lpush("selfPosts:$userID",$postid);
foreach($followers as $fid) {
    $r->lpush("posts:$fid",$postid);
}
# Push the post on the timeline, and trim the timeline to the
# newest 1000 elements.
$r->lpush("timeline",$postid);
$r->ltrim("timeline",0,1000);

$refferer = $_SERVER['HTTP_REFERER'];
header("Location:".$refferer);
?>

