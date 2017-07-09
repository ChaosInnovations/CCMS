$(document).ready(function(){
	changeToView("default");
});

function changeToView(viewName) {
	if (viewName != "") {
		module_ajax("transitmanager|drivers_display_getview", {view:viewName, token:Cookies.get("token")}, function (data) {
			if (data != "FALSE") {
				dat = data.split("§§§");
				head = dat[0];
				body = dat[1];
				foot = dat[2];
				$("#transitmanager-drivershift-display-container > .panel-heading").html(head);
				$("#transitmanager-drivershift-display-container > .panel-body").html(body);
				$("#transitmanager-drivershift-display-container > .panel-footer").html(foot);
			}
		});
	}
}