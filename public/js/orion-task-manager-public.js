(function($) {
    'use strict';

    /**
     * All of the code for your public-facing JavaScript source
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

    $(document).ready(function() {
        console.log('Le script JS a bien été chargé');

        $('#AddSubtask').click(function() {
            if (this.checked) {
                $('#choix_check').show(1000)
            } else {
                $('#choix_check').hide(1000);
                $('.choix_1').hide(1000);
                $('.choix_2').hide(1000);
            }
        });
        $('#show1').click(function() {
            if (this.checked) {
                $('.choix_1').show(1000);
            } else {
                $('.choix_1').hide(1000);
            }
        });
        $('#show2').click(function() {
            console.log(this.val);
            if (this.checked) {
                $('.choix_2').show(1000);
            } else {
                $('.choix_2').hide(1000);
            }
        });


        $(document).on('submit', '#create_new_task', function(e) {
            e.preventDefault();
            console.log('Le clic sur le bouton a été pris en compte');
            var title = $('#titre').val();
            var assigne = $('#assigne').val();
            var project = $('#project').val();
            var subtask = $('#subtask').val();
            var dependancies = $('#dependancies').val();
            var codage = $('#codage').val();
            var suivi = $('#suivi').val();
            var test = $('#test').val();
            var duedate = $('#duedate').val();
            //console.log(task_manager.ajaxurl);

            $.ajax({
                url: task_manager.ajaxurl,
                type: "POST",
                data: {
                    'action': 'create_new_task',
                    'title': title,
                    'assigne': assigne,
                    'project': project,
                    'subtask': subtask,
                    'dependancies': dependancies,
                    'codage': codage,
                    'suivi': suivi,
                    'test': test,
                    'duedate': duedate,
                },
                success: function(response) {
                    //console.log("La requête est terminée !");
                    console.log(response);
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });
        $(document).on('submit', '#create_simple_task', function(e) {
            e.preventDefault();
            console.log('Le clic sur le bouton a été pris en compte');
            var title = $('#titre').val();
            var assigne = $('#assigne').val();
            var project = $('#project').val();
            var subtask = $('#subtask').val();
            var dependancies = $('#dependancies').val();
            var duedate = $('#duedate').val();
            //console.log(task_manager.ajaxurl);

            $.ajax({
                url: task_manager.ajaxurl,
                type: "POST",
                data: {
                    'action': 'create_simple_task',
                    'title': title,
                    'assigne': assigne,
                    'project': project,
                    'subtask': subtask,
                    'dependancies': dependancies,
                    'codage': codage,
                    'suivi': suivi,
                    'test': test,
                    'duedate': duedate,
                },
                success: function(response) {
                    //console.log("La requête est terminée !");
                    console.log(response);
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });
    });
})(jQuery);