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
            var firstchoose = $("input[name='show']:checked").val();
            var task = {},
                subtask = {},
                parametre = {};

            if (firstchoose == 'userTemplate') {
                if ($('#nbrechamp').val() != undefined) {
                    var nbrechamps = $('#nbrechamp').val() - 1;
                    for (var t = 1; t <= nbrechamps; t++) {
                        subtask[t] = { title: $('#title' + t).val(), assign: $('#assign' + t).val(), duedate: $('#duedate' + t).val(), description: $('#description' + t).val(), commentaire: $('#commentaire' + t).val() }
                    }
                }
                task = { title: $('#title').val(), project: $('#project').val(), assign: $('#assign').val(), duedate: $('#duedate').val(), description: $('#description').val(), commentaire: $('#commentaire').val() };
                parametre = { task: task, subtask: subtask };
                $.ajax({
                    url: task_manager.ajaxurl,
                    type: "POST",
                    data: {
                        'action': 'create_new_task',
                        'parametre': parametre,
                    },
                    success: function(response) {

                    },
                    error: function(errorThrown) {
                        console.log(errorThrown);
                    }
                });
            } else {
                var secondchoose = $("input[name='show1']:checked").val();
                var task1 = {},
                    subtask1 = {},
                    parametre = {};
                task1 = { title: $('#title').val(), project: $('#project').val(), assign: $('#assign').val(), duedate: $('#duedate').val(), description: $('#description').val(), commentaire: $('#commentaire').val() };
                if ($('#AddSubtask').is(":checked")) {
                    if (secondchoose == 'userTemplate1') {
                        if ($('#nbrechamp').val() != undefined) {
                            var subnbrechamps = $('#nbrechamp').val() - 1;
                            for (var t = 1; t <= subnbrechamps; t++) {
                                subtask1[t] = { title: $('sub_#title' + t).val(), assign: $('#sub_assign' + t).val(), duedate: $('#sub_duedate' + t).val(), description: $('#sub_description' + t).val(), commentaire: $('#sub_commentaire' + t).val() }
                            }
                        }
                        subtask1[0] = { title: $('#sub_title').val(), assign: $('#sub_assign').val(), duedate: $('#sub_duedate').val(), description: $('#sub_description').val(), commentaire: $('#sub_commentaire').val() }
                    } else {
                        subtask1[0] = { title: $('#manuel_title').val(), assign: $('#manuel_assign').val(), duedate: $('#manuel_duedate').val(), description: $('#manuel_description').val(), commentaire: $('#manuel_commentaire').val() };
                    }
                }
                parametre = { task: task1, subtask: subtask1 };
                $.ajax({
                    url: task_manager.ajaxurl,
                    type: "POST",
                    data: {
                        'action': 'create_new_task',
                        'parametre': parametre,
                    },
                    success: function(response) {

                    },
                    error: function(errorThrown) {
                        console.log(errorThrown);
                    }
                });
                //console.log(parametre);
            }
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
                    document.getElementById('assign').innerHTML = response;
                    if (document.getElementById('sub_assign'))
                        document.getElementById('sub_assign').innerHTML = response;
                    if (document.getElementById('manuel_assign'))
                        document.getElementById('manuel_assign').innerHTML = response;
                    for (var t = 0; t < 10; t++) {
                        if (document.getElementById('sub_assign' + t))
                            document.getElementById('sub_assign' + t).innerHTML = response;
                    }
                    for (var t = 0; t < 20; t++) {
                        if (document.getElementById('assign' + t))
                            document.getElementById('assign' + t).innerHTML = response;
                    }
                    document.getElementById('label1').style.color = 'black';
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $(document).on('click', '.assign_option', function() {
            var project_id = document.getElementById('project').value;
            if (project_id == '')
                alert('First choose the project associated with your task');
        });

        $(document).on('change', '#selectTemplate', function() {
            var select = document.getElementById('selectTemplate').value;
            var radioValue = $("input[name='show1']:checked").val();
            var istemp;
            if (radioValue == undefined) {
                istemp = 'no';
            } else {
                istemp = 'yes';
            }
            $.ajax({
                url: task_manager.ajaxurl,
                type: "POST",
                data: {
                    'action': 'get_template_choose',
                    'template_id': select,
                    'istemplate': istemp
                },
                success: function(response) {
                    document.getElementById('template_select').innerHTML = response;
                    var project_id = document.getElementById('project').value;
                    $.ajax({
                        url: task_manager.ajaxurl,
                        type: "POST",
                        data: {
                            'action': 'get_option_add_template',
                            'project_id': project_id
                        },
                        success: function(response) {
                            if (document.getElementById('sub_assign'))
                                document.getElementById('sub_assign').innerHTML = response;
                            if (document.getElementById('manuel_assign'))
                                document.getElementById('manuel_assign').innerHTML = response;
                            for (var t = 0; t < 10; t++) {
                                if (document.getElementById('sub_assign' + t))
                                    document.getElementById('sub_assign' + t).innerHTML = response;
                            }
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

        $('input[type=radio][name=show]').change(function() {
            if (this.value == 'userTemplate') {
                var type = "usertemplate";
                $('#manuel_get').hide(1000);
            }
            if (this.value == 'manuelTemplate') {
                var type = "manueltemplate";
                $('#manuel_get').show(1000);
            }
            $.ajax({
                url: task_manager.ajaxurl,
                type: "POST",
                data: {
                    'action': 'get_first_form',
                    'type': type,
                    'istemplate': 'no'
                },
                beforeSend: function() {
                    //first_choix
                    document.getElementById('first_choix').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading choose ... </div>';
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
        $('input[type=radio][name=show1]').change(function() {
            if (this.value == 'userTemplate1') {
                var type = "usertemplate";
            }
            if (this.value == 'manuelTemplate1') {
                var type = "manueltemplate";
            }
            $.ajax({
                url: task_manager.ajaxurl,
                type: "POST",
                data: {
                    'action': 'get_first_form',
                    'type': type,
                    'istemplate': 'yes'
                },
                success: function(response) {
                    document.getElementById("second_choix").innerHTML = response;
                    var project_id = document.getElementById('project').value;
                    $.ajax({
                        url: task_manager.ajaxurl,
                        type: "POST",
                        data: {
                            'action': 'get_option_add_template',
                            'project_id': project_id
                        },
                        success: function(response) {
                            if (document.getElementById('sub_assign'))
                                document.getElementById('sub_assign').innerHTML = response;
                            if (document.getElementById('manuel_assign'))
                                document.getElementById('manuel_assign').innerHTML = response;
                            for (var t = 0; t < 10; t++) {
                                if (document.getElementById('sub_assign' + t))
                                    document.getElementById('sub_assign' + t).innerHTML = response;
                            }
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
        });

        $('#AddSubtask').click(function() {
            if (this.checked) {
                $('#choix_check').show(1000)
            } else {
                $('#choix_check').hide(1000);
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