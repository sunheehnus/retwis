<?
include("retwis.php");
if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}
include("header.php");
echo("<h2>My Followers</h2>");
echo("<i>They are：</i><br>");
$r = redisLink();
$userid = $User['id'];
$followers = $r->zrange("followers:$userid",0,-1);
echo("<table>");
echo("<tr>");
$i = 0;
foreach($followers as $followerID) {
    if ($i % 8 == 0) {
        echo("</tr>");
        echo("<tr>");
    }
    $followerName = $r->hget("user:$followerID","username");
    echo("<td><a class=\"username\" href=\"profile.php?u=".urlencode($followerName)."\">".utf8entities($followerName)."</a></td>");
    $i ++;
}
echo("</table>");
include("footer.php")
?>
