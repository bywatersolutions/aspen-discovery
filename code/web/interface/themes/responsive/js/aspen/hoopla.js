AspenDiscovery.Hoopla = (function(){
	return {
		checkOutHooplaTitle: function (hooplaId, patronId, hooplaType) {
			if (Globals.loggedIn) {
				if (typeof patronId === 'undefined') {
					patronId = $('#patronId', '#pickupLocationOptions').val();
				}
				var url = Globals.path + '/Hoopla/'+ hooplaId + '/AJAX';
				var params = {
					'method' : 'checkOutHooplaTitle',
					patronId : patronId,
					hooplaType : hooplaType
				};
				if ($('#stopHooplaConfirmation').prop('checked')){
					params['stopHooplaConfirmation'] = true;
				}
				$.getJSON(url, params, function (data) {
					if (data.buttons) {
						AspenDiscovery.showMessageWithButtons(data.title, data.message, data.buttons);
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
					if (data.success) {
						AspenDiscovery.Account.loadMenuData();
					}
				}).fail(function() {
					AspenDiscovery.ajaxFail();
				})
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.Hoopla.checkOutHooplaTitle(hooplaId, patronId, hooplaType);
				}, false);
			}
			return false;
		},

		getCheckOutPrompts(hooplaId, hooplaType) {
			if (Globals.loggedIn) {
				const $checkoutButton = $('.btn-checkout[onclick*="' + hooplaId + '"]');
				if ($checkoutButton.length > 0) {
					AspenDiscovery.toggleButtonSpinner($checkoutButton, true);
				}

				const url = Globals.path + "/Hoopla/" + hooplaId + "/AJAX?method=getCheckOutPrompts";
				const params = {
					'method': 'getCheckOutPrompts',
					hooplaType: hooplaType
				};
				$.getJSON(url, params, function (data) {
					if ($checkoutButton.length > 0) {
						AspenDiscovery.toggleButtonSpinner($checkoutButton, false);
					}

					// noinspection JSUnresolvedReference
					if (data.flexDirectCheckout) {
						AspenDiscovery.Hoopla.checkOutHooplaTitle(hooplaId, data.patronId, data.hooplaType);
					} else {
						AspenDiscovery.showMessageWithButtons(data.title, data.body, data.buttons);
					}
				}).fail(function() {
					if ($checkoutButton.length > 0) {
						AspenDiscovery.toggleButtonSpinner($checkoutButton, false);
					}
					AspenDiscovery.ajaxFail();
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Hoopla.getCheckOutPrompts(hooplaId, hooplaType);
				}, false);
			}
			return false;
		},

		returnCheckout: function (patronId, hooplaId) {
			if (Globals.loggedIn) {
				if (confirm('Are you sure you want to return this title?')) {
					AspenDiscovery.showMessage("Returning Title", "Returning your title in Hoopla.");
					var url = Globals.path + "/Hoopla/" + hooplaId + "/AJAX",
							params = {
								'method': 'returnCheckout'
								,patronId: patronId
							};
					$.getJSON(url, params, function (data) {
						AspenDiscovery.showMessage(data.success ? 'Success' : 'Error', data.message, data.success, data.success);
					}).fail(AspenDiscovery.ajaxFail);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function () {
					AspenDiscovery.Hoopla.returnCheckout(patronId, hooplaId);
					AspenDiscovery.Account.loadMenuData();
				}, false);
			}
			return false;
		},

		getLargeCover: function (id){
			var url = Globals.path + '/Hoopla/' + id + '/AJAX?method=getLargeCover';
			$.getJSON(url, function (data){
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		getHoldPrompts: function(id) {
			var url = Globals.path + "/Hoopla/" + id + "/AJAX?method=getHoldPrompts";
			var result = false;
			$.ajax({
				url: url,
				cache: false,
				success: function(data) {
					result = data;
					if (data.promptNeeded) {
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					} else if (!data.success) {
						AspenDiscovery.showMessageWithButtons(data.title, data.body, data.buttons);
					}
				},
				dataType: 'json',
				async: false,
				error: function() {
					AspenDiscovery.showMessage("Error", "An error occurred processing your request in Hoopla. Please try again in a few minutes.");
				}
			});
			return result;
		},

		placeHold: function(id) {
			if (Globals.loggedIn) {
				var promptInfo = AspenDiscovery.Hoopla.getHoldPrompts(id);
				if (promptInfo.success && !promptInfo.promptNeeded){
					AspenDiscovery.Hoopla.doHold(promptInfo.patronId, id);
				}
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function() {
					AspenDiscovery.Hoopla.placeHold(id);
				});
			}
			return false;
		},

		doHold: function(patronId, id) {
			if (Globals.loggedIn) {
				var url = Globals.path + "/Hoopla/AJAX";
				var params = {
					method: 'placeHold',
					patronId: patronId,
					id: id
				};
				if ($('#stopHooplaHoldConfirmation').prop('checked')){
					params['stopHooplaHoldConfirmation'] = true;
				}
				$.ajax({
					url: url,
					data: params,
					cache: false,
					success: function(data) {
						AspenDiscovery.closeLightbox(function() {
							if (data.buttons) {
								AspenDiscovery.showMessageWithButtons(data.title, data.message, data.buttons);
							} else {
								AspenDiscovery.showMessage("Error", data.message);
							}
							if (data.success) {
								AspenDiscovery.Account.loadMenuData();
							}
						});
					},
					dataType: 'json',
					error: function() {
						AspenDiscovery.showMessage("Error", "An error occurred placing your hold. Please try again in a few minutes.");
					}
				});
			} else {
				AspenDiscovery.Account.ajaxLogin(null, function() {
					AspenDiscovery.Hoopla.doHold(patronId, id);
				});
			}
			return false;
		},

		cancelHold: function(patronId, recordId) {
			if (confirm('Are you sure you want to cancel this hold?')) {
				var url = Globals.path + "/Hoopla/AJAX?method=cancelHold&patronId=" + patronId + "&recordId=" + recordId;
				$.ajax({
					url: url,
					cache: false,
					success: function(data) {
						if (data.success) {
							AspenDiscovery.showMessage("Hold Cancelled", data.message, true);
							$(".hooplaHold_" + recordId + "_" + patronId).hide();
							AspenDiscovery.Account.loadMenuData();
						} else {
							AspenDiscovery.showMessage("Error Cancelling Hold", data.message, true);
						}
					},
					dataType: 'json',
					async: false,
					error: function() {
						AspenDiscovery.showMessage("Error Cancelling Hold", "An error occurred processing your request in Hoopla. Please try again in a few minutes.", false);
					}
				});
			}
			return false;
		},
	}
}(AspenDiscovery.Hoopla || {}));
