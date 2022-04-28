(function($) {
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */
    console.log("Admin");

    $(document).ready(function() {
        console.log('Le script JS a bien été chargé');
        $('#userasana').change(function() {
            var select = document.getElementById('userasana').value;

            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'get_user_role',
                    'id_user': select,
                },
                success: function(response) {
                    console.log("La requête est terminée !");
                    document.getElementById('roledisabled').value = response;
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });
        $(document).on('submit', '#user_role_asana', function(e) {
            e.preventDefault();
            console.log('Le clic sur le bouton a été pris en compte');
            var select_user = document.getElementById('userasana').value;
            var select_role = document.getElementById('role_user').value;
            //console.log(select_user + ' => ' + select_role);

            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'update_user_role',
                    'select_user': select_user,
                    'select_role': select_role
                },
                success: function(response) {
                    //console.log("La requête est terminée !");
                    console.log('data');
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });
    });

})(jQuery);