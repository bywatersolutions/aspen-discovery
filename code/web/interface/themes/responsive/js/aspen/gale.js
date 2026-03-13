AspenDiscovery.Gale = (function () {
	return {
		trackGaleUsage: function (id) {
			var ajaxUrl = Globals.path + "/Gale/JSON?method=trackGaleUsage&id=" + id;
			$.getJSON(ajaxUrl);
		}
	}
}(AspenDiscovery.Gale || {}));