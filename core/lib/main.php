<?php
session_start();

if(!isset($_SESSION['authenticated'])) {
	header('Location: ../login.php');
}

require_once("../config.php");
require_once("blocklist.php");

// Table name => array of conditions
$tables = array();
$blocklist;

/* 
 * Entry point
 */
try {
	// Get connection
	$conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['database'], $config['port']);
	if($conn->connect_errno) {
	    throw new Exception(
	    	"MySQLi failed to connect:<br>". 
	    	"Error $conn->connect_errno: $conn->connect_error<br>".
	    	"Make sure the MySQL settings are correct in config.php"
	    );
	}

	// Run main code
	if(isset($_POST['actions'])) {
		global $blocklist;
		$blocklist = new BlockList('../blocks.txt');
		if(count($_POST['actions']) == 0)
			throw new Exception("No actions were selected.");
		getTables();
		getConds();
		getQueries();
		runQueries();
	} else { // If nothing posted, return worlds info as json
		$result = query("SELECT id, world FROM {$config['tablePrefix']}world");

		$worlds = array();
		while ($row = mysqli_fetch_array($result)){
			array_push($worlds, 
				'{"display": "'.ucwords(str_replace('_', ' ', $row["world"])).'", '.
				'"id": '.$row["id"].', '.
			 	'"selected": false}');
		}
		die("[".implode($worlds, ", ")."]");
	}
} catch(Exception $e) {
	die($e->getMessage());
}

/*
 * Run query string and return the results. 
 */
function query($query) {
	global $conn;
	$result = $conn->query($query);
	if(!$result || $conn->error) {
		throw new Exception("MySQL Error<br>Error message: {$conn->error}<br>Attempted query: $query");
	}
	return $result;
}

/*
 * Get names of tables that will be queried. 
 * Create key in global table array which will hold an array of conditions
 */
function getTables() {
	global $tables;
	$tables = array();
	
	foreach($_POST['actions'] as $action) {
		switch($action) {
			case 'blockbreak':
			case 'blockplace':
			case 'interact':
				$tables['block'] = array("conds" => array(1));
				break;
			case 'chat':
				$tables['chat'] = array("conds" => array(1));
				break;
			case 'chest':
				$tables['container'] = array("conds" => array(1));
				break;
			case 'command':
				$tables['command'] = array("conds" => array(1));
				break;
			case 'session':
				$tables['session'] = array("conds" => array(1));
				break;
			case 'sign':
				$tables['sign'] = array("conds" => array(1));
				break;
		}
	}
}

/*
 * Add conditions to the table array
 */
function getConds() {
	global $tables, $blocklist, $config;
	$p = $config['tablePrefix'];

	// action IN condition, for block table
	$blockActions = array();
	if(in_array('blockbreak', $_POST['actions'])) {
		array_push($blockActions, '0');
	}
	if(in_array('blockplace', $_POST['actions'])) {
		array_push($blockActions, '1');
	}
	if(in_array('interact', $_POST['actions'])) {
		array_push($blockActions, '2');
	}
	if(count($blockActions)) {
		pushCondToTable('(action IN (' . implode(',', $blockActions) . '))', 'block');
	}
	// NOT LIKE condition (to ignore environment)
	if($_POST['options']['ignoreEnv'] == "true") {
		pushCondToTable("({$p}user.user NOT LIKE '#%')", 'block');
	}
	// type IN condition
	if($_POST['texts']['blocks'][0]) {
		$blockIds = $blocklist->getBlockIdList($_POST['texts']['blocks']);
		if(sizeof($blockIds) > 0) {
			pushCondToTable('(type IN ('.implode(',', $blockIds).'))', 'block', 'container');
		}
	}

	// coordinates BETWEEN condition
	$coords = array();
	$radius = $_POST['texts']['radius'] ? $_POST['texts']['radius'] : 20;
	if($x = esc($_POST['texts']['x'])) {
		pushCondToTable('(x BETWEEN '.($x - $radius).' AND '.($x + $radius).')', "block", "container", "session", "sign");
	}
	if($y = esc($_POST['texts']['y'])) {
		pushCondToTable('(y BETWEEN '.($y - $radius).' AND '.($y + $radius).')', "block", "container", "session", "sign");
	}
	if($z = esc($_POST['texts']['z'])) {
		pushCondToTable('(z BETWEEN '.($z - $radius).' AND '.($z + $radius).')', "block", "container", "session", "sign");
	}

	// players LIKE condition
	$players = array();
	if($_POST['texts']['players'][0]) {
		foreach($_POST['texts']['players'] as $player) {
			array_push($players, "({$p}user.user LIKE '%".esc($player)."%')");
		}
		pushCondToTable('('.implode(' OR ', $players).')', 'block', 'chat', 'command', 'container', 'session', 'sign');
	}
	
	// time BETWEEN condition
	$from = esc($_POST['texts']['from']);
	$to = esc($_POST['texts']['to']);
	foreach($tables as $tableName => $conds) {
		pushCondToTable("({$p}{$tableName}.time BETWEEN $from and $to)", $tableName);
	}

	// world IN condition
	if(array_key_exists('worlds', $_POST)) {
		$worlds = array();
		foreach($_POST['worlds'] as $world) {
			array_push($worlds, esc($world));
		}
		pushCondToTable(esc("({$p}block.wid IN (".implode(',', $worlds)."))"), 'block');
		pushCondToTable(esc("({$p}container.wid IN (".implode(',', $worlds)."))"), 'container');
		pushCondToTable(esc("({$p}sign.wid IN (".implode(',', $worlds)."))"), 'sign');
		pushCondToTable(esc("({$p}session.wid IN (".implode(',', $worlds)."))"), 'session');
	}

	// LIMIT condition
	if($_POST['options']['limit'] != "-1") {
		$tables['LIMIT'] = " LIMIT " . esc($_POST['options']['limit']);
	} else {
		$tables['LIMIT'] = "";
	}
}

/*
 * Wrapper for escaping entries
 */
function esc($string) {
	global $conn;
	return $conn->escape_string(preg_replace('/\s+/', ' ', trim($string)));
}

/*
 * Input condition string and list of table names
 */
function pushCondToTable($cond) {
	global $tables;
	$tableNames = func_get_args();
	for($i = 1; $i < count($tableNames); $i++) {
		if(array_key_exists($tableNames[$i], $tables)) {
			array_push($tables[$tableNames[$i]]['conds'], $cond);
		}
	}
}

/*
 * For each table that we need to search, form the query and put it in $tables array
 */
function getQueries() {
	global $tables, $config;
	$p = $config['tablePrefix'];

	if(array_key_exists('block', $tables)) {
		$tables['block']['query'] = "SELECT {$p}block.time, {$p}user.user, {$p}world.world, x, y, z, type, action
			FROM {$p}block 
			JOIN {$p}user ON {$p}block.user = {$p}user.rowid
			JOIN {$p}world ON {$p}block.wid = {$p}world.id 
			WHERE " . implode(' AND ', $tables['block']['conds']) . $tables['LIMIT'];
	}
	if(array_key_exists('chat', $tables)) {
		$tables['chat']['query'] = 
		   "SELECT {$p}chat.time, {$p}user.user, message
			FROM {$p}chat 
			JOIN {$p}user ON {$p}chat.user = {$p}user.rowid 
			WHERE " . implode(' AND ', $tables['chat']['conds']) . $tables['LIMIT'];
	}
	if(array_key_exists('command', $tables)) {
		$tables['command']['query'] = 
		   "SELECT {$p}command.time, {$p}user.user, message
			FROM {$p}command 
			JOIN {$p}user ON {$p}command.user = {$p}user.rowid 
			WHERE " . implode(' AND ', $tables['command']['conds']) . $tables['LIMIT'];
	}
	if(array_key_exists('container', $tables)) {
		$tables['container']['query'] = 
		   "SELECT {$p}container.time, {$p}user.user, {$p}world.world, x, y, z, type, amount, action
			FROM {$p}container 
			JOIN {$p}user ON {$p}container.user = {$p}user.rowid 
			JOIN {$p}world ON {$p}container.wid = {$p}world.id 
			WHERE " . implode(' AND ', $tables['container']['conds']) . $tables['LIMIT'];
	}
	if(array_key_exists('session', $tables)) {
		$tables['session']['query'] = 
		   "SELECT {$p}session.time, {$p}user.user, {$p}world.world, x, y, z, action,
			CASE 
				WHEN {$p}session.time = {$p}user.time THEN \"First login\"
				ELSE \"\"
			END as data
			FROM {$p}session 
			JOIN {$p}user ON {$p}session.user = {$p}user.rowid 
			JOIN {$p}world ON {$p}session.wid = {$p}world.id 
			WHERE " . implode(' AND ', $tables['session']['conds']) . $tables['LIMIT'];
	}
	if(array_key_exists('sign', $tables)) {
		$tables['sign']['query'] = 
		   "SELECT {$p}sign.time, {$p}user.user, {$p}world.world, x, y, z, line_1, line_2, line_3, line_4 
			FROM {$p}sign 
			JOIN {$p}user ON {$p}sign.user = {$p}user.rowid 
			JOIN {$p}world ON {$p}sign.wid = {$p}world.id 
			WHERE " . implode(' AND ', $tables['sign']['conds']) . $tables['LIMIT'];
	}
}

/*
 * Run each query, collect the results and output
 */
function runQueries() {
	global $tables, $blocklist;
	$count = 0;
	$results = array();
	$limit = $_POST['options']['limit'];
	if($limit == -1)
		$limit = 100000;

	if(array_key_exists('sign', $tables)) {
		$res = query($tables['sign']['query']);
		while($res && ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) && ($count < $limit)) {
			array_push($results, array(
				$row["time"], 
				$row["user"], 
				"Sign Place",
				"{$row["line_1"]}<br>{$row["line_2"]}<br>{$row["line_3"]}<br>{$row["line_4"]}", 
				$row["x"], 
				$row["y"], 
				$row["z"], 
				$row["world"]
			));
			$count++;
		}
	}
	if(array_key_exists('block', $tables) && ($count < $limit)) {
		$blockAction = array(
			"Broke Block",
			"Placed Block",
			"Used"
		);
		$res = query($tables['block']['query']);
		while($res && ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) && ($count < $limit)) {
			array_push($results, array(
				$row["time"], 
				$row["user"], 
				$blockAction[$row["action"]],
				$blocklist->getBlockName($row["type"]), 
				$row["x"], 
				$row["y"], 
				$row["z"], 
				$row["world"]
			));
			$count++;
		}
	}
	if(array_key_exists('chat', $tables) && ($count < $limit)) {
		$res = query($tables['chat']['query']);
		while($res && ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) && ($count < $limit)) {
			array_push($results, array(
				$row["time"], 
				$row["user"], 
				"Chat",
				$row["message"], 
				"",
				"",
				"", 
				""
			));
			$count++;
		}
	}
	if(array_key_exists('command', $tables) && ($count < $limit)) {
		$res = query($tables['command']['query']);
		while($res && ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) && ($count < $limit)) {
			array_push($results, array(
				$row["time"], 
				$row["user"], 
				"Command",
				$row["message"], 
				"",
				"",
				"", 
				""
			));
			$count++;
		}
	}	
	if(array_key_exists('container', $tables) && ($count < $limit)) {
		$containerAction = array(
			"Took from chest",
			"Placed in chest"
		);
		$res = query($tables['container']['query']);
		while($res && ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) && ($count < $limit)) {
			array_push($results, array(
				$row["time"], 
				$row["user"], 
				$containerAction[$row["action"]],
				$row['amount'] . " " . $blocklist->getBlockName($row['type']), 
				$row["x"], 
				$row["y"], 
				$row["z"], 
				$row["world"]
			));
			$count++;
		}
	}
	if(array_key_exists('session', $tables) && ($count < $limit)) {
		$sessionAction = array(
			"Logout",
			"Login"
		);
		$res = query($tables['session']['query']);
		while($res && ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) && ($count < $limit)) {
			array_push($results, array(
				$row["time"], 
				$row["user"], 
				$sessionAction[$row["action"]],
				$row['data'], 
				$row["x"], 
				$row["y"], 
				$row["z"], 
				$row["world"]
			));
			$count++;
		}
	}

	echo toHTML($results);
}

/*
 * Formats results as html table
 */
function toHTML($results) {
	$columns = array(
		"time",
		"player",
		"action",
		"data",
		"x",
		"y",
		"z",
		"world"
	);
	$html = '<table id="results-table"><thead><tr>';
	foreach($columns as $column) {
		$html .= "<th>".ucfirst($column)."</th>";
	}
	$html .= '</tr></thead><tbody>';
	foreach($results as $row) {
		$html .= '<tr>';
		foreach($row as $i => $cell) {
			$html .= '<td class="'.$columns[$i].'">'.$cell.'</td>';
		}
		$html .= '</tr>';
	}
	$html .= "</tbody></table>";
	return $html;
}

?>