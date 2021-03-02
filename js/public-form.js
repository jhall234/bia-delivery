(function($) {
	
    $("#bia-form").validate({
        rules: {
            first_name: {
                required: true,
                minlength: 1
            },
            last_name: {
                required: true,
                minlength: 1
            },
            date_of_birth: {
                required: true
            },
            phone: {
                required: true,
                minlength: 10
            },
            email: {
                required: false,
                email: true,
                maxlength: 255
            },
            address: {
                required: true
            },
            zip: {
                required: true,
                minlength: 5,
                maxlength: 5,
                digits: true
            },
            num_in_house: {
                required: true,
                digits: true
            }
        },
        messages: {
            first_name: 'This field is required',
            last_name: 'This field is required',
            date_of_birth: 'This field is required',
            phone: 'Enter a valid phone',
            email: 'Enter a valid email',
            address: 'Enter a valid address',
            zip: 'Enter a valid zip',
            num_in_house: 'Please Enter a Number 1-8'
        },
        submitHandler: function(form) {
            var params = $(form).serialize();
            console.log(params);
            $.ajax({
                url: settings.ajaxurl, 
                type: "POST",             
                data: params + "&action=bia_recieve_form",            
                processData: false,      
                success: function(response) {                    
                    if(response.success){
                        alert("Your form has been submitted sucessfully!");
                        location.reload();

                    }
                    else {
                        alert(response.data);
                        location.reload();
                    }                        
                }
            });
        }
    });   

})( jQuery );

