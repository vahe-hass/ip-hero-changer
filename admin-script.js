//  handle form submission via AJAX
$(document).ready(function() {
    $("#location-form").submit(function(event) {
        event.preventDefault();
        var formData = {
            country: $("#country").val(),
            state: $("#region").val(),
            city: $("#city").val(),
            option: $("input[name='option']:checked").val()
        };
        $.ajax({
            type: "POST",
            url: "process_form.php", // The URL of the server-side script
            data: formData,
            success: function(response) {
                // Display the server's response in the response-message div
                $("#response-message").html(response);
            },
            error: function() {
                $("#response-message").html("An error occurred while processing your request.");
            }
        });
    });
});

// populate city options based on the selected state
var stateSelect = document.getElementById("region");
var citySelect = document.getElementById("city");

// Sample data for cities in each state
var citiesByState = {
    "AL": ["Birmingham", "Montgomery", "Mobile"],
    "AK": ["Anchorage", "Fairbanks", "Juneau"],
    "AZ": ["Phoenix", "Tucson", "Mesa"],
    "AR": ["Little Rock", "Fort Smith", "Fayetteville"],
    "CA": ["Los Angeles", "San Francisco", "San Diego"],
    // Add cities for all 50 states
    "WY": ["Cheyenne", "Casper", "Laramie"]
};

stateSelect.addEventListener("change", function() {
    var selectedState = stateSelect.value;

    // Clear city options
    citySelect.innerHTML = '<option value="" disabled selected>Select a city</option>';

    if (citiesByState[selectedState]) {
        // Populate cities for the selected state
        citiesByState[selectedState].forEach(function(city) {
            var option = document.createElement("option");
            option.value = city;
            option.textContent = city;
            citySelect.appendChild(option);
        });
    }
});
