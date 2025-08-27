var loginFunction = {
	submitLogin: function() {
		var formData = {
			information: $('#information').val().trim(),
			password: $('#password').val().trim()
		};
		$.ajax({
			url: '../backend/login.php',
			type: 'POST',
			data: formData,
			dataType: 'json',
			success: function(response) {
				if (response.status) {
					window.location.href = response.data.redirect;
				} else {
					alert(response.message || 'Login failed.');
				}
			},
			error: function(xhr, status, error) {
				alert('An error occurred: ' + error);
			}
		});
	}
};

$(document).ready(function() {
	$('#login_form').on('submit', function(e) {
		e.preventDefault();
		loginFunction.submitLogin();
	});
});