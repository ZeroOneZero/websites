// Teleport command. Use '/tp {x} {y} {z}' for vanilla teleport, '/tppos {x} {y} {z}' for Essentials.
// {x} {y} {z} will be replaced by each coordinate.
var COMMAND = '/tppos {x} {y} {z}';

// Stop editing here, unless you really want to.
var cpui = angular.module('cpui', [])
.directive('datepicker', function() {
    return function(scope, ele, attrs) {
       ele.datetimepicker({ 
			prevText: "<", 
			nextText: ">",
			onSelect: function(dateString) {
	            var props = $(this).attr('ng-model').split("."); // Ugly hack to turn the string "texts.to" into a property reference. Avert your gaze.
	            scope[props[0]][props[1]] = dateString;
	            scope.$apply();
			}
		});
    }
});

function process(data) {
	$('#results-container').html('');

	$.post('lib/main.php', {actions: data.actions, worlds: data.worlds, options: data.options, texts: data.texts}, function(data) {
		$('#results-container').html(data);
		$("#footer-info").html('');

		$("#results-table")
		.click(function(e) {
			if($(e.target).is('td')) {
				var x = $(e.target).parent('tr').children(".x").html();
				var y = $(e.target).parent('tr').children(".y").html();
				var z = $(e.target).parent('tr').children(".z").html();
				if(x != '' && x != undefined) {
					prompt("CTRL+C to copy, CTRL+V to paste into Minecraft\nTo change this command, change the COMMAND variable at the top of js/main.js", COMMAND.replace('{x}', x).replace('{y}', y).replace('{z}', z));
				}
			}
		})
		.dataTable({
		    "bJQueryUI": true,
		    "iDisplayLength": 25,
		    "aLengthMenu": [[25, 50, 100, -1], [25, 50, 100, "All"]],
		    "sPaginationType": "full_numbers",
		    "bAutoWidth": false,
		    "sDom": '<"top"lpf>rt<"bottom"i><"clear">',
		    "oLanguage": {
		    	"sSearch": "",
		    	"sLengthMenu": "_MENU_",
		    },
		    "aaSorting": [[0, "desc"]],
		    "aoColumns": [
				{
					"mRender": function(data, type, full) {
						if(type == "display") // Apparently mRender can modify raw data. This is here to make sure only he display data is modified.
							return new Date(data * 1000).format("M j, y, g:i:sa");
						return data;
					}
				},
				null,
				null,
				null,
				null,
				null, 
				null,
				null
		    ]
		});

		// Hack to move "showing x to y of z entries" to the footer. Maybe look for the proper way to do it?
		$("#results-table_info").appendTo("#footer-info");
	});
}

$(function() {
	$("#left-pane").show();
	
	// Theme management
	if($.cookie('core-theme')) {
		$('#theme-select option[value="'+$.cookie('core-theme')+'"]').attr('selected', 'selected');
		$.get('themes.json', function(data) {
			less.modifyVars(data[$.cookie('core-theme')]);
		});
	}
	$('#theme-select').change(function() {
		var themeName = $('#theme-select option:selected').attr('value'); // 'light' or 'dark'
		$.cookie('core-theme', themeName, {expires: 365});
		$.get('themes.json', function(data) {
			less.modifyVars(data[themeName]);
		});
	});

	$('input[type=text]').tooltip();
});

// Helper functions
function isValidTime(string) {
	if(!string || isNaN(Date.parse(string)))
		return false;
	return true;
}
function toUnix(string) {
	return Math.floor(Date.parse(string) / 1000);
}
function now() {
	return Math.floor(+new Date() / 1000);
}

$.fn.tooltip = function() {
	$(this).each(function() {
		var text = $(this).data('tooltip');
		if(!text)
			return;

		var tooltip = $('<span class="tooltip">'+text+'</span>');
		tooltip.appendTo('body');
		tooltip.position({
			my: 'left top', 
			at: 'right top', 
			of: $(this)
		})
		tooltip.hide();

		(function(parent, tooltip) {
			$(parent).hover(
				function() {
					tooltip.show();
				}, 
				function() {
					tooltip.hide();
				}
			)
		})(this, tooltip);
	})
}

Date.prototype.format=function(e){var t="";var n=Date.replaceChars;for(var r=0;r<e.length;r++){var i=e.charAt(r);if(r-1>=0&&e.charAt(r-1)=="\\"){t+=i}else if(n[i]){t+=n[i].call(this)}else if(i!="\\"){t+=i}}return t};Date.replaceChars={shortMonths:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],longMonths:["January","February","March","April","May","June","July","August","September","October","November","December"],shortDays:["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],longDays:["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],d:function(){return(this.getDate()<10?"0":"")+this.getDate()},D:function(){return Date.replaceChars.shortDays[this.getDay()]},j:function(){return this.getDate()},l:function(){return Date.replaceChars.longDays[this.getDay()]},N:function(){return this.getDay()+1},S:function(){return this.getDate()%10==1&&this.getDate()!=11?"st":this.getDate()%10==2&&this.getDate()!=12?"nd":this.getDate()%10==3&&this.getDate()!=13?"rd":"th"},w:function(){return this.getDay()},z:function(){var e=new Date(this.getFullYear(),0,1);return Math.ceil((this-e)/864e5)},W:function(){var e=new Date(this.getFullYear(),0,1);return Math.ceil(((this-e)/864e5+e.getDay()+1)/7)},F:function(){return Date.replaceChars.longMonths[this.getMonth()]},m:function(){return(this.getMonth()<9?"0":"")+(this.getMonth()+1)},M:function(){return Date.replaceChars.shortMonths[this.getMonth()]},n:function(){return this.getMonth()+1},t:function(){var e=new Date;return(new Date(e.getFullYear(),e.getMonth(),0)).getDate()},L:function(){var e=this.getFullYear();return e%400==0||e%100!=0&&e%4==0},o:function(){var e=new Date(this.valueOf());e.setDate(e.getDate()-(this.getDay()+6)%7+3);return e.getFullYear()},Y:function(){return this.getFullYear()},y:function(){return(""+this.getFullYear()).substr(2)},a:function(){return this.getHours()<12?"am":"pm"},A:function(){return this.getHours()<12?"AM":"PM"},B:function(){return Math.floor(((this.getUTCHours()+1)%24+this.getUTCMinutes()/60+this.getUTCSeconds()/3600)*1e3/24)},g:function(){return this.getHours()%12||12},G:function(){return this.getHours()},h:function(){return((this.getHours()%12||12)<10?"0":"")+(this.getHours()%12||12)},H:function(){return(this.getHours()<10?"0":"")+this.getHours()},i:function(){return(this.getMinutes()<10?"0":"")+this.getMinutes()},s:function(){return(this.getSeconds()<10?"0":"")+this.getSeconds()},u:function(){var e=this.getMilliseconds();return(e<10?"00":e<100?"0":"")+e},e:function(){return"Not Yet Supported"},I:function(){var e=null;for(var t=0;t<12;++t){var n=new Date(this.getFullYear(),t,1);var r=n.getTimezoneOffset();if(e===null)e=r;else if(r<e){e=r;break}else if(r>e)break}return this.getTimezoneOffset()==e|0},O:function(){return(-this.getTimezoneOffset()<0?"-":"+")+(Math.abs(this.getTimezoneOffset()/60)<10?"0":"")+Math.abs(this.getTimezoneOffset()/60)+"00"},P:function(){return(-this.getTimezoneOffset()<0?"-":"+")+(Math.abs(this.getTimezoneOffset()/60)<10?"0":"")+Math.abs(this.getTimezoneOffset()/60)+":00"},T:function(){var e=this.getMonth();this.setMonth(0);var t=this.toTimeString().replace(/^.+ \(?([^\)]+)\)?$/,"$1");this.setMonth(e);return t},Z:function(){return-this.getTimezoneOffset()*60},c:function(){return this.format("Y-m-d\\TH:i:sP")},r:function(){return this.toString()},U:function(){return this.getTime()/1e3}}