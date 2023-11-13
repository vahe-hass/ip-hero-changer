jQuery(document).ready(function($) {
    var elementWithClassB = $('.ihc-changed-to-b');
    if (elementWithClassB.length > 0) {
        var anchorElement = elementWithClassB.find('a');

        anchorElement.on('click', function() {
            $.ajax({
                url: '/wp-content/plugins/ip-hero-changer/counter-process-b.php',
                method: 'POST',
                success: function(response) {
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ' + status, error);
                }
            });
        });
    }
});
