<?
include("retwis.php");
if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}
include("header.php");
echo("<h2>My Following</h2>");
echo("<i>They are</i><br>");
$r = redisLink();
$userid = $User['id'];
$following = $r->zrange("following:$userid",0,-1);
echo("<table>");
echo("<tr>");
$i = 0;
foreach($following as $followingID) {
    if ($i % 8 == 0) {
        echo("</tr>");
        echo("<tr>");
    }
    $followingName = $r->hget("user:$followingID","username");
    echo("<td><a class=\"username\" href=\"profile.php?u=".urlencode($followingName)."\">".utf8entities($followingName)."</a></td>");
    $i ++;
}
echo("</table>");
include("footer.php")
?>
