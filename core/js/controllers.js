function inputsCtrl($scope, $http) {
	$scope.actions = {
		"blockbreak": {
			"display": "Block Break",
			"selected": true
		},
		"blockplace": {
			"display": "Block Place",
			"selected": false
		},
		"interact": {
			"display": "Interact",
			"selected": false
		},
		"sign": {
			"display": "Sign Place",
			"selected": false
		},
		"chest": {
			"display": "Chest Use",
			"selected": false
		},
		"chat": {
			"display": "Chat",
			"selected": false
		},
		"command": {
			"display": "Command",
			"selected": false
		},
		"session": {
			"display": "Login/Logout",
			"selected": false
		}
	};

	$scope.worlds = [];
	$http.post('lib/main.php').success(function(data) {
		if(typeof data == "string") {
			$('#loading-worlds').html('Error loading worlds from database.<br><br>Check your connection info in config/config.php');
			$('#results-container').html(data);
		} else {
			$scope.worlds = data;
		}
	});

	$scope.texts = {
		players: '',
		x: '',
		y: '',
		z: '',
		radius: '',
		blocks: '',
		from: '',
		to: ''
	}

	$scope.options = {
		ignoreEnv: {
			display: "Ignore Environment", 
			def: false,
			val: false
		},
		limit: {
			display: "Limit",
			def: 1000,
			val: 1000
		}
	}

	$scope.process = function() {
		var actions = [], 
			worlds = [], 
			options = {}, 
			texts = {};

		$.each($scope.actions, function(actionName, action) {
			if(action.selected)
				actions.push(actionName);
		});
		$.each($scope.worlds, function(i, world) {
			if(world.selected)
				worlds.push(world.id);
		});
		$.each($scope.options, function(optionName, option) {
			options[optionName] = option.val;
		});

		texts.players = ($scope.texts.players || '').replace(/\s*,\s*/g, ',').replace(/^\s*|\s*$/g, '').split(',');
		texts.blocks = ($scope.texts.blocks || '').replace(/\s*,\s*/g, ',').replace(/^\s*|\s*$/g, '').split(',');
		texts.radius = $scope.texts.radius;
		texts.from = isValidTime($scope.texts.from) ? toUnix($scope.texts.from) : 0;
		texts.to = isValidTime($scope.texts.to) ? toUnix($scope.texts.to) : now();
		texts.x = $scope.texts.x;
		texts.y = $scope.texts.y;
		texts.z = $scope.texts.z;
		texts.radius = $scope.texts.radius;

		process({actions: actions, worlds: worlds, options: options, texts: texts});
	}

	$scope.clear = function() {
		for(var action in $scope.actions) {
			$scope.actions[action].selected = false;
		}
		for(var world in $scope.worlds) {
			$scope.worlds[world].selected = false;
		}
		for(var option in $scope.options) {
			$scope.options[option].val = $scope.options[option].def;
		}
		for(var text in $scope.texts) {
			$scope.texts[text] = '';
		}
		$("#date-from, #date-to").val('');

		angular.resetForm($scope, "optionsForm");
	}

	$scope.formIsInvalid = function() {
		if($scope.fromDateIsInvalid() || $scope.toDateIsInvalid() || $scope.actionNotSelected())
			return true
	}

	$scope.actionNotSelected = function() {
		for(var action in $scope.actions) {
			if($scope.actions[action].selected) {
				return false;
			}
		}
		return "Select an action.";
	}

	$scope.fromDateIsInvalid = function() {
		var from = $scope.texts.from;
		var to = $scope.texts.to;
		if(from) {
			if(isNaN(Date.parse(from)))
				return "From date is invalid";
			if(Date.parse(from) > new Date())
				return "From date is set in the future";
			if(to && Date.parse(from) > Date.parse(to))
				return "From date is after To date";
		}
	}

	$scope.toDateIsInvalid = function() {
		var from = $scope.texts.from;
		var to = $scope.texts.to;
		if(to) {
			if(isNaN(Date.parse(to))) {
				return "To date is invalid";
			}
		}
	}
}

angular.resetForm = function (scope, formName, defaults) {
    $('form, form .ng-dirty').removeClass('ng-dirty').removeClass('ng-invalid').removeClass('ng-invalid-integer').addClass('ng-pristine');
    var form = scope[formName];
    form.$dirty = false;
    form.$pristine = true;
    form.$invalid = false;
    for(var field in form) {
		if(form[field].$pristine == false) {
			form[field].$pristine = true;
		}
		if(form[field].$dirty == true) {
			form[field].$dirty = false;
		}
		if(form[field].$valid == false) {
			form[field].$valid = true;
		}
		if(form[field].$invalid == true) {
			form[field].$invalid = false;
		}
    }
};