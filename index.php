<?php
	$config = require('config.php');
	$connection = require('assets/php/db.php');

	if ($config['SteamAPIKey'] == '') {
		header("Location: error.php?error=nosteamkey");
        exit();
	}

	session_start();
	if(isset($_SESSION['logged_in']) && !empty($_SESSION['logged_in'])){
		$username = $_SESSION['userData']['name'];
		$avatar = $_SESSION['userData']['avatar'];
	}

	if (isset($_GET['page'])) {
		$page = $_GET['page'];
	} else {
		$page = 1;
	}

	if (isset($_GET['server'])) {
		$server = $_GET['server'];
	} else {
		$server = array_search(reset($config['servers']), $config['servers']);
	}

	if (isset($_GET['table'])) {
		$tablename = $_GET['table'];
	} else {
		$tablename = 'wipe';
	}

	if (isset($_GET['player'])) {
		$playername = $_GET['player'];
	} else {
		$playername = '';
	}

	// Defines
	$records_per_page = 15;
	$offset = ($page-1) * $records_per_page;
	$table = $config['servers'][$server][$tablename];

	// Column Sorting Defines
	$columns = array('name','playtime','kills', 'deaths', 'kdr', 'accuracy');
	$column = isset($_GET['column']) && in_array($_GET['column'], $columns) ? $_GET['column'] : $columns[0];
	$sort_order = isset($_GET['order']) && strtolower($_GET['order']) == 'desc' ? 'DESC' : 'ASC';
	$up_or_down = str_replace(array('ASC','DESC'), array('up','down'), $sort_order); 
	$asc_or_desc = $sort_order == 'ASC' ? 'desc' : 'asc';

	// Get Page Numbers
	if ($playername == '') {
		$total_pages_sql = "SELECT COUNT(*) FROM $table";
	} else {
		$total_pages_sql = "SELECT COUNT(*) FROM $table WHERE name LIKE \"%$playername%\"";
	}
	
	$result = mysqli_query($connection,$total_pages_sql);
	$total_rows = mysqli_fetch_array($result)[0];
	$total_pages = ceil($total_rows / $records_per_page);

	// Select Data
	if ($playername == '') {
		$sql = "SELECT * FROM $table ORDER BY $column $sort_order LIMIT $offset, $records_per_page";
	} else {
		$sql = "SELECT * FROM $table WHERE name LIKE \"%$playername%\" ORDER BY $column $sort_order LIMIT $offset, $records_per_page";
	}
	
	$res_data = mysqli_query($connection, $sql);
	$resultCheck = mysqli_num_rows($res_data);

	function getPlayTime($minutes) {
		$d = floor ($minutes / 1440);
		$h = floor (($minutes - $d * 1440) / 60);
		$m = $minutes - ($d * 1440) - ($h * 60);
		
		if ($d > 0) {
			return "{$d}d {$h}h {$m}m";
		} else if ($h > 0) {
			return "{$h}h {$m}m";
		} else {
			return "{$m}m";
		}
	}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<link rel="icon" href="favicon.ico">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta
			name="description"
			content=<?php echo $config['SiteDescription'] ?>
		>
		<title><?php echo ($config['ServerName'] . " - Official Stats") ?></title>
		<link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
		<link rel="stylesheet" href="styles.css">
		<script>
			function searchPlayer(value) {
				const queryString = window.location.search;
				const urlParams = new URLSearchParams(queryString);
				urlParams.set('page', 1);
				urlParams.set('player', value);
				window.location = 'index.php?' + urlParams.toString();

			}
		</script>
	</head>
	<body>
		<nav class='main-navbar-container'>
			<div class='navbar-textlogo'><a href='index.php'><?php echo $config['ServerName'] ?></a></div>
			<div class='navbar-links'>
				<?php 
					if (isset($_SESSION['logged_in']) && !empty($_SESSION['logged_in'])) {
						echo "<div class='user-profile'>";
							echo "<img class='user-profile-avatar' src=". $avatar ."></img>";
							echo "<h1 class='user-profile-name'>". $username ."</h1>";
							echo '';
						echo "</div>";
						echo "<div class='user-profile-dropdown'>";
							echo "<button class='user-profile-button' onclick='window.location = \"profile.php?server=$server&player=" . $_SESSION['userData']['steam_id'] ."\"'><i class=\"fa-solid fa-user\"></i>PROFILE</button>";
							echo "<button class='user-logout-button' onclick='window.location = \"assets/php/steam/logout.php\"'><i class=\"fa-solid fa-circle-xmark\"></i>LOGOUT</button>";
						echo "</div>";
					} else {
						echo "<a class='navbar-login-button' href='assets/php/steam/init-steam.php'><i class='fa-solid fa-arrow-right-to-bracket'></i>LOGIN</a>";
					}
				?>
			</div>
		</nav>

		<main class='leaderboards-wrapper'>
			<?php 
				echo "<img class='background-image' src=" . $config['backgroundImage'] . " />"; 
			?>
			
			<section class="server-selection">
				<div class='server-list'>
					<?php
						$index = 0;
						foreach($config['servers'] as $tempserver) 
						{
							$tServer = array_search($tempserver, $config['servers']);
							$button = "<button><a href='?server=$tServer&column=$column&order=$sort_order'>";
							$selected = ($tServer == $server) ? 'active' : '';
							$button .= "<img class='server-select $selected' src=" . $tempserver['serverIcon'] . " />";
							$button .= '</a></button>';
							echo $button;
							$index++;
						}
					?>
				</div>
			</section>
			<section class='server-leaderboard-wrapper'>
				<div class='server-leaderboard-header-container'>
					<h1 class='server-leaderboard-header'>Leaderboard Summary</h1>
					<div class='main-table-buttons'>
						<a class=<?php echo ($tablename == 'wipe' ? "active" : "''") ?> href=<?php echo "?server=$server&page=$page&column=$column&order=$sort_order&table=wipe&player=$playername"?>>WIPE</a>
						<a class=<?php echo ($tablename == 'overall' ? "active" : "''") ?> href=<?php echo "?server=$server&page=$page&column=$column&order=$sort_order&table=overall&player=$playername"?>>OVERALL</a>
					</div>
					<div>
						<input id='playerSearch' class='server-leaderboard-search' type="text" onkeyup="if (event.keyCode == 13) searchPlayer(this.value); return false;" placeholder='Player name...' value="<?php echo($playername != "" ?  $playername : "") ?>" />
						<i class="fa-solid fa-magnifying-glass"></i>
					</div>
				</div>
				<div class="server-leaderboard-container">
					<table class='server-leaderboard' id='server-leaderboard'>
						<tr>
							<th><a href=<?php echo "?server=$server&page=$page&column=name&order=$asc_or_desc&table=$tablename&player=$playername" ?>><i class='fa-solid fa-eye'></i> Name<i class="<?php echo $column == 'name' ? 'fa-solid fa-sort-' . $up_or_down : ''; ?>"></i></a></th>
							<th><a href=<?php echo "?server=$server&page=$page&column=playtime&order=$asc_or_desc&table=$tablename&player=$playername" ?>><i class='fa-solid fa-clock'></i> Playtime<i class="<?php echo $column == 'playtime' ? 'fa-solid fa-sort-' . $up_or_down : ''; ?>"></i></a></th>
							<th><a href=<?php echo "?server=$server&page=$page&column=kills&order=$asc_or_desc&table=$tablename&player=$playername" ?>><i class='fa-solid fa-gun'></i> Kills<i class="<?php echo $column == 'kills' ? 'fa-solid fa-sort-' . $up_or_down : ''; ?>"></i></a></th>
							<th><a href=<?php echo "?server=$server&page=$page&column=deaths&order=$asc_or_desc&table=$tablename&player=$playername" ?>><i class='fa-solid fa-cross'></i> Deaths<i class="<?php echo $column == 'deaths' ? 'fa-solid fa-sort-' . $up_or_down : ''; ?>"></i></a></th>
							<th><i class="fa-solid fa-crosshairs"></i> KDR</th>
							<th><i class="fa-solid fa-bullseye"></i> Accuracy</th>
							<th><i class="fa-solid fa-cubes"></i> Actions</th>
						</tr>
						<?php
							if ($resultCheck > 0) {
								while($row = mysqli_fetch_assoc($res_data)) {
									$kdr = $row['deaths'] > 0 ? round($row['kills'] / $row['deaths'], 2) : $row['kills'];
									$hits = $row['head_hits'] + $row['torso_hits'] + $row['leftarm_hits'] + $row['rightarm_hits'] + $row['leftleg_hits'] + $row['rightleg_hits'] + $row['leftfoot_hits'] + $row['rightfoot_hits'];
									$accuracy = $row['bfired'] < 1 ? "100" : (round(($hits / $row['bfired']) * 100));
									if ($accuracy > 100) $accuracy = 100;
									
									echo "<tr class='leaderboard-result'>";
									echo "<td><a class='steam-profile' target='#' href='https://steamcommunity.com/profiles/" .$row['steamid']. "'>" . $row['name'] . "</a></td>";
									echo "<td>" . getPlayTime($row['playtime']) . "</td>";
									echo "<td>" . $row['kills'] . "</td>";
									echo "<td>" . $row['deaths'] . "</td>";
									echo "<td>$kdr</td>";
									echo "<td>$accuracy%</td>";
									echo "
									<td> 							
										<button class='view-profile-button' onclick='window.location = \"profile.php?server=$server&table=$tablename&player=" . $row['steamid'] ."\"'>
											<i class='fa-solid fa-eye'></i>
											View Profile
										</button>
									</td></tr>";
								}
							}
						?>
					</table>
				</div>
				<ul class="pagination">
					<?php 
						if($page > 1) {
							echo "<a href='?server=$server&page=1&column=$column&order=$sort_order&table=$tablename&player=$playername' class='previous-button'><<</a>";
							echo "<a href='?server=$server&page=".($page - 1)."&column=$column&order=$sort_order&table=$tablename&player=$playername' class='previous-button'><</a>";
						}
						
						$index = 0;
						$startingPage = $page > $total_pages-1 ? $page-6 : $page-3;
						for ($i=$startingPage;$i<$total_pages+1;$i++) {
							if ($i < 1) continue;
							if ($index > 6) {
								break;
							}
							
							$class = $page == $i ? "active" : "";
							echo "<a href='?server=$server&page=".($i)."&column=$column&order=$sort_order&table=$tablename&player=$playername' class='number-button $class'>$i</a>";
							$index++;
						}
						if($page != $total_pages) {
							echo "<a href='?server=$server&page=".($page + 1)."&column=$column&order=$sort_order&table=$tablename&player=$playername' class='next-button'>></a>";
							echo "<a href='?server=$server&page=$total_pages&column=$column&order=$sort_order&table=$tablename&player=$playername' class='next-button'>>></a>";
						}
					?>
				</ul>
			</section>
		</main>
	</body>
</html>
