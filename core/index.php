<?php
session_start();

if(!isset($_SESSION['authenticated'])) {
	header('Location: login.php');
}
?><!doctype html>
<html>
<head>
	<!--meta charset="UTF-8"/-->
	    <META http-equiv="Content-type" content="text/html; charset=iso-8859-1">
	<title>CoreProtect Web Interface</title>
	<link href='http://fonts.googleapis.com/css?family=Ubuntu' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" src="http://normalize-css.googlecode.com/svn/trunk/normalize.css"/>
	<link id="theme" rel="stylesheet/less" type="text/css" href="css/main.less"/>

	<script src="js/lib/less.js"></script>
	<script src="js/lib/jquery.js"></script>
	<script src="js/lib/jqueryui.js"></script>
	<script src="js/lib/jquerycookie.js"></script>
	<script src="js/lib/datatables.js"></script>
	<script src="js/lib/datetimepicker.js"></script>
	<script src="js/lib/angular.js"></script>
	<script src="js/lib/angularui.js"></script>
	<script src="js/controllers.js"></script>
	<script src="js/main.js"></script>
</head>

<body ng-app="cpui">
	<div id="main" ng-controller="inputsCtrl">

		<header>
			<h1>CoreProtect 2 Web Interface</h1>
			<select id="theme-select">
				<option value="light">Light</option>
				<option value="dark">Dark</option>
			</select>
		</header>

		<section id="left-pane" style="display: none">
			<form name="optionsForm" ng-submit="search">

				<section id="options-top">
					<div class="option-column">

						<menu id="actions-container" ng-model="actions">
							<h3>Actions</h3>
							<label class="custom-check clickable" ng-repeat="action in actions">
								<input type="checkbox" ng-model="action.selected">
								<div>
									<span></span>
									{{action.display}}
								</div>
							</label>
						</menu>

						<menu id="worlds-container" ng-model="worlds">
							<h3>Worlds</h3>
							<div id="loading-worlds" class="message" ng-show="!worlds.length">Loading worlds from database...</div>
							<label class="custom-check clickable" ng-repeat="world in worlds">
								<input type="checkbox" ng-model="world.selected">
								<div>
									<span></span>
									{{world.display}}
								</div>
							</label>
						</menu>
					</div> <!-- option-column -->

					<div class="option-column">
						<menu id="players-container">
							<h3>Players</h3>
							<input name="players" type="text" ng-model="texts.players" class="text-full" ng-pattern="/^[A-Za-z0-9,#_ ]*$/" 
								placeholder="Player Names" data-tooltip="Player names.<br>Accepts full or partial names.<br>Separate names with ,<br>e.g. lex,notch,xx_">
						</menu>

						<menu id="coords-container">
							<h3>Coordinates</h3>
							<input type="text" name="x" ng-model="texts.x" class="text-third" placeholder="X" data-tooltip="X coordinate.<br>Whole number only" ng-pattern="/^\-?\d*$/"><!--
						 --><input type="text" name="y" ng-model="texts.y" class="text-third" placeholder="Y" data-tooltip="Y coordinate<br>Whole number only" ng-pattern="/^\-?\d*$/"><!--
						 --><input type="text" name="z" ng-model="texts.z" class="text-third" placeholder="Z" data-tooltip="Z coordinate.<br>Whole number only" ng-pattern="/^\-?\d*$/"><br><!--
						 --><input type="text" name="radius" ng-model="texts.radius" class="text-full" placeholder="Radius" data-tooltip="Radius.<br>Whole number only" ng-pattern="/^\-?\d*$/">
						</menu>

						<menu id="blocks-container">
							<h3>Blocks</h3>
							<input name="blocks" type="text" data-name="blocks" ng-model="texts.blocks" class="text-full" ng-pattern="/^[A-Za-z0-9,_ ]*$/" 
							placeholder="Block Names or IDs" data-tooltip="Blocks list.<br>Accepts full names, partial names, or block ids.<br>Separate names/ids with ,<br>e.g. 46,diam,lava">
						</menu>

						<menu id="dates-container">
							<h3>Date and Time</h3>
							<input name="from" type="text" ng-model="texts.from" date class="date text-full" id="date-from" ng-class="{error: fromDateIsInvalid()}" 
								placeholder="Date From" readonly="readonly" data-tooltip="Date from.<br>Click to open the date and time picker." datepicker>
							<input name="to" type="text" ng-model="texts.to" class="date text-full" id="date-to" ng-class="{error: toDateIsInvalid()}" 
								placeholder="Date To" readonly="readonly" data-tooltip="Date to.<br>Click to open the date and time picker." datepicker>
						</menu>

						<menu id="limit-container">
							<h3>Limit Results</h3>
							<select id="limit" ng-model="options.limit.val" class="text-full">
								<option value="200">200</option>
								<option value="500">500</option>
								<option value="1000">1000</option>
								<option value="2000">2000</option>
								<option value="4000">4000</option>
								<option value="-1">Show All Results</option>
							</select>
						</menu>

						<menu id="options-container">
							<h3>Options</h3>
							<label class="custom-check clickable">
								<input type="checkbox" ng-model="options.ignoreEnv.val">
								<div>
									<span></span>
									{{options.ignoreEnv.display}}
								</div>
							</label>
						</menu>

						<div class="error message" ng-show="actionNotSelected()">{{actionNotSelected()}}</div>
						<div class="error message" ng-show="fromDateIsInvalid()">{{fromDateIsInvalid()}}</div>
						<div class="error message" ng-show="toDateIsInvalid()">{{toDateIsInvalid()}}</div>
						<div class="error message" ng-show="textIsInvalid()">{{textIsInvalid()}}</div>
						<div class="error message" ng-show="optionsForm.players.$invalid">Invalid player names.</div>
						<div class="error message" ng-show="optionsForm.x.$invalid || optionsForm.y.$invalid || optionsForm.z.$invalid || optionsForm.radius.$invalid">Invalid coordinates.</div>
						<div class="error message" ng-show="optionsForm.blocks.$invalid">Invalid block names/ids.</div>

						<menu id="buttons-container">
						 	<button name="search" ng-class="{disabled: formIsInvalid() || optionsForm.$invalid}" ng-click="process()" ng-disabled="formIsInvalid() || optionsForm.$invalid">Search</button><!--
						 --><button ng-click="clear()">Clear</button>
						</menu>
					</div> <!-- option-column -->
				</section>

			</form>
		</section>

		<section id="right-pane">
			<div id="results-container">
				<!-- Result table goes here -->
			</div>
			<br class="clearfix">
		</section>
		<br class="clearfix">
	</div>

	<div id="footer">
		<div id="footer-container">
			<div id="footer-info">
				<!-- Footer data goes here -->
			</div>
		</div>
	</div>
</body>
</html>