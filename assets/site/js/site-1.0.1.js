function logout() {
	//Delete token cookie
	document.cookie = "token=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/";
	window.location.reload(true);
}

function Token() {
	return Cookies.get("token");
}

function showDialog(dialog) {
	$("#dialog_"+dialog).modal("show");
}

function createPage() {
	module_ajax("newpage", {}, function (data){
		if (data == "FALSE") {
			window.alert("Couldn't create page.");
		} else {
			window.location = "?p="+data;
		}
	});
}

function createSecurePage() {
	module_ajax("newpage", {s: true}, function (data){
		if (data == "FALSE") {
			window.alert("Couldn't create page.");
		} else {
			window.location = "?p="+data;
		}
	});
}

function module_ajax(func, args, call) {
	args.token = Token();
	$.post("https://penderbus.org/assets/server/index.php?func="+func, args, call);
}