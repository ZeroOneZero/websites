<?php
session_start();
$_SESSION = array();

if(!file_exists('config.php')) {
	die('Rename config-rename-me.php to config.php');
}

require_once('config.php');

if(isset($_POST['pass'])) {
	if($_POST['pass'] == $config['interfacePass']) {
		$_SESSION['authenticated'] = true;
		header('Location: index.php');
	}
}
?><!doctype html>
<head>
	<meta charset="UTF-8"/>
	<title>CoreProtect Web Interface</title>
	<link href='http://fonts.googleapis.com/css?family=Ubuntu' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" src="http://normalize-css.googlecode.com/svn/trunk/normalize.css"/>
	<link id="theme" rel="stylesheet/less" type="text/css" href="css/main.less"/>

	<script src="js/lib/less.js"></script>
	<script src="js/lib/jquery.js"></script>
	<script src="js/lib/jquerycookie.js"></script>
	<script src="js/login.js"></script>
</head>

<body>
	<div id="main">
		<header>
			<h1>CoreProtect 2 Web Interface</h1>
			<select id="theme-select">
				<option value="light">Light</option>
				<option value="dark">Dark</option>
			</select>
		</header>

		<section class="login">
			<h3>Enter password</h3>
			<div>
				<?php if(isset($_POST['pass'])): ?>
				<div class="error message">Incorrect password</div>
				<?php endif; ?>
				<form action="login.php" method="post">
					<input type="password" name="pass">
					<input type="submit" value="Login">
				</form>
			</div>
		</section>
	</div>
</body>
</html>