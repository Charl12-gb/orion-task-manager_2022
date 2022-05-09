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
        console.log('Le script public JS a bien été chargé');
        var i = 1;
        $('#create_new_task').submit(function(e) {
            e.preventDefault();
            console.log('Le clic sur le bouton a été pris en compte');
            var template_form = document.forms['create_new_task'];
            var task_form_parametre = {};
            var principal_task = {};
            var manuel_task = {};
            var sub_task = {};
            let title1 = "titre",
                description1 = "description",
                duedate1 = "duedate",
                assign1 = "assign",
                email1 = "email ",
                password1 = "password",
                multiselect = "multiselect";


            //Tâche Principale
            var title = $('#title').val();
            var project = $('#project').val();
            var description = $('#description').val();
            var assign = $('#assign').val();
            var duedate = $('#duedate').val();
            principal_task['principal_task'] = { title: title, project: project, description: description, assign: assign, duedate: duedate };
            task_form_parametre = jQuery.extend(task_form_parametre, principal_task);

            if ($('#AddSubtask').is(":checked")) {
                if ($('#show1').is(":checked")) {
                    console.log('show1');
                    for (var t = 0; t < template_form.length; t++) {
                        for (var x = 0; x <= i; x++) {
                            for (var y = 0; y <= 6; y++) {
                                //if ($('#' + title1 + "" + x + "-" + y))
                                console.log($('#' + title1 + "" + x + "-" + y));
                            }
                        }
                        console.log(template_form.elements[i].name + " => " + template_form.elements[i].value);
                    }
                }
                if ($('#show2').is(":checked")) {
                    //Tâche manuel
                    var title_manuel = $('#title_manuel').val();
                    var project = $('#project').val();
                    var description_manuel = $('#description_manuel').val();
                    var assign_manuel = $('#assign_manuel').val();
                    var duedate_manuel = $('#duedate_manuel').val();
                    manuel_task['principal_manuel'] = { title: title_manuel, project: project, description: description_manuel, assign: assign_manuel, duedate: duedate_manuel };
                    task_form_parametre = jQuery.extend(task_form_parametre, manuel_task);
                }
            }

            console.log(task_form_parametre);
        });

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
                    var radioValue = $("input[name='show']:checked").val();
                    if (radioValue == 'userTemplate') {
                        for (var t = 0; t < 10; t++) {
                            if (document.getElementById('assign' + t))
                                document.getElementById('assign' + t).innerHTML = response;
                        }
                    } else {
                        document.getElementById('assign').innerHTML = response;
                        for (var l = 0; l < 30; l++) {
                            for (var t = 0; t < 6; t++) {
                                if (document.getElementById('assign' + l + '-' + t))
                                    document.getElementById('assign' + l + '-' + t).innerHTML = response;
                            }
                        }
                    }
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $(document).on('change', '#selectTemplate', function() {
            var select = document.getElementById('selectTemplate').value;
            $.ajax({
                url: task_manager.ajaxurl,
                type: "POST",
                data: {
                    'action': 'get_template_choose',
                    'template_id': select,
                },
                success: function(response) {
                    document.getElementById('template_select').innerHTML = response;
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        })

        $('input[type=radio][name=show]').change(function() {
            if (this.value == 'userTemplate') {
                var type = "usertemplate";
                $('#manuel_get').hide(1000)
            }
            if (this.value == 'manuelTemplate') {
                var type = "manueltemplate";
                $('#manuel_get').show(1000)
            }
            $.ajax({
                url: task_manager.ajaxurl,
                type: "POST",
                data: {
                    'action': 'get_first_form',
                    'type': type
                },
                success: function(response) {
                    document.getElementById("first_choix").innerHTML = response;
                    $('#hidden_submit').show(1000)
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $('#AddSubtask').click(function() {
            if (this.checked) {
                $('#choix_check').show(1000)
            } else {
                $('#choix_check').hide(1000);
                $('.choix_1').hide(1000);
                $('.choix_2').hide(1000);
            }
        });

    });
})(jQuery);

function open_sub_templaye(template) {
    var div = document.getElementById(template);
    if (div.style.display === "none") {
        div.style.display = "block";
        document.getElementById("change" + template).innerHTML = ' - ';
    } else {
        div.style.display = "none";
        document.getElementById("change" + template).innerHTML = ' + ';
    }
}