var BASE_URL = SERVER_HTTPS + "://" + SERVER_NAME;

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
	module_ajax("page/new", {}, function (data){
		if (data == "FALSE") {
			window.alert("Couldn't create page.");
		} else {
			window.location = "./"+data;
		}
	});
}

function createSecurePage() {
	module_ajax("page/new", {s: true}, function (data){
		if (data == "FALSE") {
			window.alert("Couldn't create page.");
		} else {
			window.location = "./"+data;
		}
	});
}

function module_ajax(func, args, call, fail) {
	if (fail === undefined) {
		fail = function(){};
	}
	args.token = Token();
	$.post(BASE_URL + "/api/"+func, args, call).fail(fail);
}