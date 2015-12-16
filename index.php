<?php
// Urban Terror PHP Stats
//
// https://github.com/firefly2442/phpurbanterror

// ---------------------------------------------------------------------
// Portions of code adapted from "systates"
// http://systates.sourceforge.net
// ---------------------------------------------------------------------


////////////////////////////////////////////////////////////////////////////////
// Edit this -->
$host = "127.0.0.1";			//IP address or hostname of Urban Terror server ("127.0.0.1" is local computer)
$port = 27960;				//port that server is running on (default 27960)
$website = "";				//Your website, leave blank "" if none
// End here.
////////////////////////////////////////////////////////////////////////////////



// Do not edit anything below this line unless you know what you are doing!
// ----------------------------------------------------------------------

$version = 0.6;
$timeout = 15;                                // Default timeout for the php socket (seconds)
$length = 2048;                               // Packet length (should this be larger?)
$protocol = 'udp';                            // Default protocol for sending query
$magic = "\377\377\377\377";                  // Magic string to send via UDP
$pattern = "/$magic" . "print\n/";
$pattern2 = "/$magic" . "statusResponse\n/";

$players = array(); // List of players
$params = array();  // Game parameters

// color parser (^0 to ^9)
function colorParse($colorize) {
	//set color based on Quake color alias
	//http://www.computerhope.com/issues/ch000658.htm
	//http://wolfwiki.anime.net/index.php/Color_Codes
    static $colors = array('black', '#DD2020', '#00CC00', '#DDCC00', '#3377EE', '#00EEEE', '#DD55DD', 'white', 'orange', '#888888');
    return "<span style='color:{$colors[$colorize[1]]}'>{$colorize[2]}</span>";
}

//Add ?devmode=1 to the URL to see warnings
//e.g.: http://yourwebsite.com/phpurbanterror/index.php?devmode=1
isset($_GET['devmode']) ? error_reporting(E_ALL) : error_reporting(!E_WARNING);

if(!function_exists("socket_create")) die("<font color=red>socket support missing!</font>");

// Create the UDP socket
$socket = socket_create (AF_INET, SOCK_DGRAM, getprotobyname ($protocol));
if ($socket)
{
	if (socket_set_nonblock ($socket))
	{
		$time = time();
		$error = "";
		while (!@socket_connect ($socket, $host, $port ))
		{
			$err = socket_last_error ($socket);
			if ($err == 115 || $err == 114)
			{
				if ((time () - $time) >= $timeout)
				{
					socket_close ($socket);
					echo "Error! Connection timed out.";
				}
				sleep(1);
				continue;
			}
		}

		// Verify if an error occured
		if( strlen($error) == 0 )
		{
			socket_write ($socket, $magic . "getstatus\n");
			$read = array ($socket);
			$out = "";

			while (socket_select ($read, $write = NULL, $except = NULL, 1))
			{
				$out .= socket_read ($socket, $length, PHP_BINARY_READ);
			}

			if ($out == "")
				echo "<font color=red><h2>Unable to connect to server...</h2></font>\n";

			socket_close ($socket);
			$out = preg_replace ($pattern, "", $out);
			$out = preg_replace ($pattern2, "", $out);
				$all = explode( "\n", $out );
			$params = explode( "\\", $all[0] );
			array_shift( $params );
			$temp = count($params);
			for( $i = 0; $i < $temp; $i++ )
			{
				$params[ strtolower($params[$i]) ] = $params[++$i];
			}

			for( $i = 1; $i < count($all) - 1; $i++ )
			{
				$pos = strpos( $all[$i], " " );
				$score = substr( $all[$i], 0, $pos );
				$pos2 = strpos( $all[$i], " ", $pos + 1 );
				$ping = substr( $all[$i], $pos + 1, $pos2 - $pos - 1 );
				$name = substr( $all[$i], $pos2 + 2 );
				$name = substr( $name, 0, strlen( $name ) - 1);

				$player = array( $name, $score, $ping );
				$players[] = $player;
			}
			//sort by player score
			foreach ($players as $key => $row) {
				$scores[$key] = $row[1];
			}
			array_multisort($scores, SORT_DESC, $players);
		}
		else
		{
			echo "Unable to connect to server.";
		}
	}
	else
	{
		echo "Error! Unable to set nonblock on socket.";
	}
}
else
{
	echo "The server is DOWN!";
}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
<link rel="stylesheet" type="text/css" href="./stylesheets/default.css">
<link rel="shortcut icon" href="favicon.ico" />

<script type="text/javascript">
function changeCSS(cssFile, cssLinkIndex) {

    var oldlink = document.getElementsByTagName("link").item(cssLinkIndex);
    var newlink = document.createElement("link");
    newlink.setAttribute("rel", "stylesheet");
    newlink.setAttribute("type", "text/css");
    newlink.setAttribute("href", cssFile);

    document.getElementsByTagName("head").item(0).replaceChild(newlink, oldlink);
}
</script>

<title>Urban Terror Server Status</title>
</head>
<body>
<div class="flex-center">
<img src="urbanterror.jpg" alt="Server Status" title="Server Status">
</div>
<hr>
<br>

<div class="flex-center">
<table>
<tr class="box_titles">
<td><b>
<?php echo preg_replace_callback('~\^(\d)(.*?)(?=\^|$)~', 'colorParse', $params['sv_hostname']) . " - " . $host . ":" . $port; ?>
</b></td>
<td><b>Players</b></td>
</tr>
<tr>
<td>
<?php

//map information
echo "<b>Map: </b>" . $params['mapname'] . "<br>";
echo count($players) . " / " . $params['sv_maxclients'] . " currently playing<br><br>\n";
if (file_exists("./levelshots/" . $params['mapname'] . ".jpg"))
{
	echo "<img src='./levelshots/" . $params['mapname'] . ".jpg' alt='Map: " . $params['mapname'] . "' title='Map: " . $params['mapname'] . "'>\n";
}
else
{
	echo "<img src='./levelshots/no_image.jpg' alt='Map: " . $params['mapname'] . " (no image)' title='Map: " . $params['mapname'] . " (no image)'>\n";
}

?>
</td>
<td class="align-top">
<table>
<tr class="box_titles">
<td><b>Player</b></td>
<td><b>Score</b></td>
<td><b>Ping</b></td>
</tr>
<?php //players information
for ($j = 0; $j < count($players); $j++)
{
	echo "<tr class='general_row'>\n";
	echo "<td>" . preg_replace_callback('~\^(\d)(.*?)(?=\^|$)~', 'colorParse', $players[$j][0]) . "</td>\n";
	echo "<td>" . $players[$j][1] . "</td>\n";
	if ($players[$j][2] == 999)
		echo "<td>Connecting...</td>\n";
	else
		echo "<td>" . $players[$j][2] . "</td>\n";
	echo "</tr>";
}
echo "</table><br>\n";

?>
</td>
</tr>
</table>
</div>

<br>
<div class="flex-center">
<table>
<tr>
<td>
<table>
<tr class="box_titles">
<td><b>Rules</b></td>
<td><b>Setting</b></td>
<?php //server information
echo "<tr class='general_row'>\n";
echo "<td>Urban Terror Version</td>";
echo "<td>" . $params['g_modversion'] . "</td>";
echo "</tr>";
echo "<tr class='general_row'>\n";
echo "<td>Urban Terror Server Version</td>";
echo "<td>" . preg_replace_callback('~\^(\d)(.*?)(?=\^|$)~', 'colorParse', $params['version']) . "</td>";
echo "</tr>";
echo "<tr class='general_row'>\n";
echo "<td>GameType</td>";
if ($params['g_gametype'] == 0 || $params['g_gametype'] == 1 || $params['g_gametype'] == 2)
	echo "<td>FreeForAll</td>\n";
if($params['g_gametype'] == 1)
	echo "<td>Last Man Standing</td>\n";
if($params['g_gametype'] == 3)
	echo "<td>Team Deathmatch</td>\n";
if($params['g_gametype'] == 4)
	echo "<td>Team Survivor</td>\n";
if($params['g_gametype'] == 5)
	echo "<td>Follow the Leader</td>\n";
if($params['g_gametype'] == 6)
	echo "<td>Capture and Hold</td>\n";
if($params['g_gametype'] == 7)
	echo "<td>Capture the Flag</td>\n";
if($params['g_gametype'] == 8)
	echo "<td>Bomb and Defuse</td>\n";
if($params['g_gametype'] == 9)
	echo "<td>Jump</td>\n";
if($params['g_gametype'] == 10)
	echo "<td>Freeze Tag</td>\n";
if($params['g_gametype'] == 11)
	echo "<td>Gun Game</td>\n";
echo "</tr>";
echo "<tr class='general_row'>\n";
echo "<td>Friendly Fire</td>";
echo "<td>" . $params['g_friendlyfire'] . "</td>";
echo "</tr>";
echo "<tr class='general_row'>\n";
echo "<td>Maximum Ping</td>";
echo "<td>" . $params['sv_maxping'] . "</td>";
echo "</tr>";
echo "<tr class='general_row'>\n";
echo "<td>Minimum Ping</td>";
echo "<td>" . $params['sv_minping'] . "</td>";
echo "</tr>";
echo "<tr class='general_row'>\n";
echo "<td>Password Protected</td>";
echo "<td>" . $params['g_needpass'] . "</td>";
echo "</tr>";
echo "<tr class='general_row'>\n";
echo "<td>Warmup Time</td>";
echo "<td>" . $params['g_warmup'] . " seconds </td>";
echo "</tr>";
echo "<tr class='general_row'>\n";
echo "<td>Swap Roles</td>";
echo "<td>" . $params['g_swaproles'] . "</td>";
echo "</tr>";
// calculate voting options based on g_allowvote
echo "<tr class='general_row'>\n";
echo "<td>Voting Allowed On<br><i>(" . $params['g_allowvote'] . ")</i></td>";
echo "<td>";

$base_convert = base_convert($params['g_allowvote'], 10, 2);

$vote_values = array ("reload", "restart", "map", "nextmap", "kick/clientKick",
                     "swapTeams", "shuffleTeams", "g_friendlyFire", "g_followStrict",
                     "g_gameType", "g_waveRespawns", "timelimit", "fragLimit",
                     "captureLimit", "g_respawnDelay", "g_redWaveRespawnDelay",
                     "g_blueWaveRespawnDelay", "g_bombExplodeTime", "g_bombDefuseTime",
                     "g_survivorRoundTime", "g_caputureScoreTime", "g_warmup",
                     "g_matchMode", "g_timeouts", "g_timeoutLength", "exec",
                     "g_swapRoles", "g_maxRounds", "g_gear", "cyclemap");

$index_value = 0;
for ($i = count($vote_values)-1; $i >= 0; $i--)
{
	if (substr($base_convert, $i, 1) == "1")
		echo $vote_values[$index_value] . "<br>";
	$index_value++;
}

if ($params['g_allowvote'] == "0")
	echo "No voting allowed.";

echo "</td>";
echo "</tr>";
echo "<tr class='general_row'>\n";
echo "<td>Website</td>";
echo "<td>";
if ($website == "")
	echo "None";
else
	echo "<a class=\"general_row_link\" href=" . $website . " target=_blank>" . $website . "</a>\n";
echo "</td></tr>";
?>
</table>
</td>
<td class="align-top">
<?php
if (substr_count($params['version'], "win") > 0)
	echo "<img class=\"img-circle\" src=\"./images/windows_logo.jpg\" alt=\"Server Runs Windows\" title=\"Server Runs Windows\">\n";
if (substr_count($params['version'], "linux") > 0)
	echo "<img class=\"img-circle\" src=\"./images/linux_logo.jpg\" alt=\"Server Runs Linux\" title=\"Server Runs Linux\">\n";
?>
</td>
</tr>
</table>
</div>

<br>

<div class="flex-center">
<p>
<?php echo "<a href='https://github.com/firefly2442/phpurbanterror' target='_blank'>Version: " . $version . " - phpUrbanTerror</a>"; ?> <span class="bull">&bull;</span>
<a href="#" onclick="changeCSS('stylesheets/dark.css', 0);">Grayscale</a> <span class="bull">&bull;</span>
<a href="#" onclick="changeCSS('stylesheets/default.css', 0);">Default Style</a>
</p>
</div>

</body>
</html>

<?php
//uncomment to show ALL server variables
//for( $i = 0; $i < count($params); $i++ )
//	echo $params[$i] . "<br>";
//?>
