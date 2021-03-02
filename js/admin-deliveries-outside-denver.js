(function($) {
    $("#bia-details-modal").hide();

    $('#bia-details-modal').dialog({
        autoOpen : false,
        maxHeight: 500,
        modal: true,
        buttons: {
            "Complete Selected Delivery": function(){
                var foodRequestId = $("div.food_request_id").attr("data-request-id");
                //TODO: need the delivery id....
                var deliveryIdArray = [];
                $(".bia-delivery-checkbox input:checked").each(function(){
                    deliveryIdArray.push($(this).val());
                });
                $.post({
                    url: settings.ajaxurl,           
                    data: {
                        action: 'bia_complete_delivery_outside_denver',
                        food_request_id: foodRequestId,
                        id_array: JSON.stringify(deliveryIdArray)
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
        }       
    });
    
    $("#counties").change(function() {        
        var countyNum = $(this).children("option:selected").val();
        loadPeopleList(countyNum);
    });
    
    function loadPeopleList(countyNum){
        $('#bia-people-table tbody').empty();
        $.post({
            url: settings.ajaxurl,             
            data: {
                action: 'bia_get_people_table_outside_denver',
                county_id: countyNum
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
                action: 'bia_get_person_modal_outside_denver',
                food_request_id: foodRequestId
            },
            cache: false,      
            success: function(response){                                                    
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