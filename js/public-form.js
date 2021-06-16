(function ($) {

  $("#bia-form-phone").usPhoneFormat({
    format: 'xxx-xxx-xxxx'
  })

  new AutoNumeric("#bia-form-monthly-income", {
		currencySymbol: "$",
		roundingMethod: "S",
		decimalPlaces: 0,
		decimalPlacesRawValue: 0,
		minimumValue: 0,
  });

	$.validator.addMethod(
		"checkBirthday",
		function (value, element) {
			var userInput = new Date(value);
			var today = new Date();
			var eighteenYearsAgo = new Date();
			var oneHundredYearsAgo = new Date();
			eighteenYearsAgo.setFullYear(today.getFullYear() - 18);
			oneHundredYearsAgo.setFullYear(today.getFullYear() - 100);
			return (
				this.optional(element) ||
				(userInput < eighteenYearsAgo && userInput > oneHundredYearsAgo)
			);
		},
		"Please enter a valid birthday"
	);
	$("#bia-form").validate({
		rules: {
			first_name: {
				required: true,
				minlength: 1,
			},
			last_name: {
				required: true,
				minlength: 1,
			},
			date_of_birth: {
				required: true,
				checkBirthday: true,
			},
			phone: {
				required: true,
        minlength: 10,
        phoneUS: true,
			},
			email: {
				required: false,
				email: true,
				maxlength: 255,
				minlength: 1,
			},
			address: {
				required: true,
			},
			zip: {
				required: true,
				minlength: 5,
				maxlength: 5,
				digits: true,
			},
			num_in_house: {
				required: true,
				digits: true,
			},
			monthly_income: {
				required: true,
			},
		},
		messages: {
			first_name: "This field is required",
			last_name: "This field is required",
			phone: "Please enter a valid phone",
			email: "please enter a valid email",
			address: "Enter a valid address",
			zip: "Enter a valid zip",
			num_in_house: "Please Enter a Number 1-8",
		},
		submitHandler: function (form) {
			var params = $(form).serialize();
			console.log(params);
			$.ajax({
				url: settings.ajaxurl,
				type: "POST",
				data: params + "&action=bia_recieve_form",
				processData: false,
				success: function (response) {
					if (response.success) {
						alert("Your form has been submitted successfully!");
						location.reload();
					} else {
						alert(response.data);
						location.reload();
					}
				},
			});
		},
	});
})(jQuery);
