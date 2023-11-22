jQuery(document).ready(function($) {
    var elementWithClassB = $('.ihc-changed-to-b');
    if (elementWithClassB.length > 0) {
        var anchorElement = elementWithClassB.find('a');
        var ajaxSent = false;

        anchorElement.on('click', function() {
            if (ajaxSent) {
                return;
            }

            $.ajax({
                url: '/wp-content/plugins/ip-hero-changer/counter-process-b.php',
                method: 'POST',
                success: function(response) {
                    ajaxSent = true;
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ' + status, error);
                }
            });
        });
    }
});
