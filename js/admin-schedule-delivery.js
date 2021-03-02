(function($) {
    $('#bia-details-modal').dialog({
        autoOpen : false,
        maxHeight: 500,
        modal: true,
        buttons: [ 
            {
                text: "Schedule",
                click: function(){
        var foodRequestId = $("div.food_request_id").attr("data-request-id");                
        var dateTime = new Date($('#bia-datepicker').val()).toISOString();
        $.post({
            url: settings.ajaxurl,           
            data: {
                action: 'bia_schedule_time',
                food_request_id: foodRequestId,
                wp_user_id: settings.wpUserId,
                scheduled_time: dateTime
            },
            cache: false,      
            success: function(response) {
                if(response.success){
                    alert("Successfully updated");
                    location.reload();
                }
                else {
                    alert(response.data)
                }
            }
        });
                }
            },
            {
                text: "Delete",
                icon: "dashicons-trash",
                click: function(){
                    var foodRequestId = $("div.food_request_id").attr("data-request-id");
                    var deliveryIdArray = [];
                    $(".bia-delivery-checkbox input:checked").each(function(){
                        deliveryIdArray.push($(this).val());
                    });
                    console.log(JSON.stringify(deliveryIdArray));
                    $.post({
                        url: settings.ajaxurl,           
                        data: {
                            action: 'bia_delete_delivery',
                            food_request_id: foodRequestId,
                            id_array: JSON.stringify(deliveryIdArray)
                        },
                        cache: false,      
                        success: function(response) {
                            if(response.success){
                                alert("Successfully deleted");
                                location.reload();
                            }
                            else {
                                alert(response.data)
                            }
                        }
                    });
                }
            }
        ],
        classes: {
            "ui-button": "ui-icon"
        }         
    });
    
    $("#districts").change(function() {        
        var districtNum = $(this).children("option:selected").val();
        loadPeopleList(districtNum);
    });
    
    function loadPeopleList(districtNum){
        $('#bia-people-table tbody').empty();
        $.post({
            url: settings.ajaxurl,             
            data: {
                action: 'bia_get_people_table',
                district: districtNum
            },
            cache: false,      
            success: function(response) {              
                if(response.success){
                    $("#bia-people-table tbody").html(response.data);
                    $("#bia-people-table tbody").on('click', '.bia-view-btn', function(){
                        var foodRequestId = $(this).val();
                        viewPersonDetailModal(foodRequestId)
                    });
                    $('#bia-people-table').show();
                }
                else {
                    alert("Server error, please try again");
                }                        
            }
        });
    }

    function viewPersonDetailModal(foodRequestId){        
        $.post({
            url: settings.ajaxurl,             
            data: {
                action: 'bia_get_person_schedule_modal',
                food_request_id: foodRequestId
            },
            cache: false,      
            success: function(response) {
                if(response.success){
                    $('#bia-details-modal').html(response.data);                    
                    if (!$('#bia-details-modal').dialog("isOpen")){                        
                        $('#bia-details-modal').dialog("open");
                    }
                }
                else {
                    alert("Server error. Please refresh and try again");
                }                       
            }
        });
    }

})( jQuery );