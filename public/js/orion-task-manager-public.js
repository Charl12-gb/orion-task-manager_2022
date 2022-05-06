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

        var i = 1;
        $('#add_template').click(function() {
            i++;
            $('#choix_1').append('<div id="rm' + i + '"><div class="form-row mt-3"><div class="form-group col-md-10"><select name="' + i + '" id="selectTemplate' + i + '" class="form-control choose_template"><option value="">Choose Type Champs... </option></select></div><div class="form-group col-md-1"><button type="button" name="remove" id="' + i + '" class="btn btn-outline-danger btn_remove">X</button></div></div><span id="see_template' + i + '"></span></div>');
            $.ajax({
                url: task_manager.ajaxurl,
                type: "POST",
                data: {
                    'action': 'get_option_add',
                },
                success: function(response) {
                    $('#selectTemplate' + i).append(response);
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $(document).on('click', '.btn_remove', function() {
            var button_id = $(this).attr("id");
            $('#rm' + button_id + '').remove();
        });

        $(document).on('change', '.project', function() {
            var project_id = document.getElementById('project').value;
            $.ajax({
                url: task_manager.ajaxurl,
                type: "POST",
                data: {
                    'action': 'get_option_add_template',
                    'project_id': project_id
                },
                success: function(response) {
                    document.getElementById('assign0').innerHTML = response;
                    //$('#assign0').append(response);
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $(document).on('change', '.choose_template', function() {
            var i = $(this).attr("name");
            var select = document.getElementById('selectTemplate' + i).value;

            $.ajax({
                url: task_manager.ajaxurl,
                type: "POST",
                data: {
                    'action': 'get_template_choose',
                    'template_id': select
                },
                success: function(response) {
                    document.getElementById('see_template' + i).innerHTML = response;
                    var project_id = document.getElementById('project').value;
                    $.ajax({
                        url: task_manager.ajaxurl,
                        type: "POST",
                        data: {
                            'action': 'get_option_add_template',
                            'project_id': project_id
                        },
                        success: function(response) {
                            document.getElementById('assign0').innerHTML = response;
                        },
                        error: function(errorThrown) {
                            console.log(errorThrown);
                        }
                    });
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        })

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
            if (this.checked) {
                $('.choix_2').show(1000);
            } else {
                $('.choix_2').hide(1000);
            }
        });

    });
})(jQuery);

function open_sub_templaye(valeur) {
    var div = document.getElementById(valeur);
    if (div.style.display === "none") {
        div.style.display = "block";
        document.getElementById("change" + valeur).innerHTML = ' - ';
    } else {
        div.style.display = "none";
        document.getElementById("change" + valeur).innerHTML = ' + ';
    }
}