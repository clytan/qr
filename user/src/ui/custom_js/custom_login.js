var loginFunction = {
	submitLogin: function () {
		// Get the return URL from current page's URL parameters
		const urlParams = new URLSearchParams(window.location.search);
		const returnUrl = urlParams.get('return');

		var formData = {
			information: $('#information').val().trim(),
			password: $('#password').val().trim()
		};

		// Add return URL to form data if it exists
		if (returnUrl) {
			formData.return_url = returnUrl;
		}

		$.ajax({
			url: '../backend/login.php',
			type: 'POST',
			data: formData,
			dataType: 'json',
			success: function (response) {
				if (response.status) {
					window.location.href = response.data.redirect;
				} else {
					showToast(response.message || 'Login failed.', 'error');
				}
			},
			error: function (xhr, status, error) {
				showToast('An error occurred: ' + error, 'error');
			}
		});
	}
};

$(document).ready(function () {
	$('#login_form').on('submit', function (e) {
		e.preventDefault();
		loginFunction.submitLogin();
	});
});