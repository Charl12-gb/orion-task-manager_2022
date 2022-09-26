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
        var y = 0,
            z = 0,
            i = 1;
        $(document).on('submit', '#sent_worklog_mail', function(e) {
            setTimeout(function() { $('#sent_worklog_mail').toggle(); });
        });

        $(document).on('click', '.add_more', function() {
            var choix = $(this).attr("id");
            if (choix == 'addobject') {
                y++;
                if (document.getElementById('nbreobject')) {
                    val = $('#nbreobject').val();
                    if (y < val) {
                        y = val;
                        document.getElementById('nbre').value = y;
                    }
                }
                $('#addojectives').append('<div id="rm2' + y + '"><div class="form-row pt-2"><div class="col"><input type="text" name="objective' + y + '" id="objective' + y + '" class="form-control" placeholder="Enter Goal"></div><div class="col-sm-1"><span name="remove" id="' + y + '" class="btn btn-outline-danger btn_remove_objective">X</span></div></div></div>');
                document.getElementById('nbreobj').value = y;
            }
            if (choix == 'more_subtask') {
                z++;
                $.ajax({
                    url: task_manager.ajaxurl,
                    type: "POST",
                    data: {
                        'action': 'get_option_add',
                        'nbresubtask': z,
                    },
                    success: function(response) {
                        $('#add_more_subtask').append('<hr><div id="rm22' + z + '"><div class="form-row pt-2"><div class="col">' + response + ' <span name="remove" id="' + z + '" class="btn btn-outline-danger btn_remove_subtask">X</span></div></div></div>')
                        document.getElementById('nbresubtask').value = z;
                        if (document.getElementById('project'))
                            var project_id = document.getElementById('project').value;
                        $.ajax({
                            url: task_manager.ajaxurl,
                            type: "POST",
                            data: {
                                'action': 'get_option_add_template',
                                'project_id': project_id
                            },
                            success: function(response) {
                                for (var t = 0; t < 10; t++) {
                                    if (document.getElementById('manuel_assign' + t))
                                        document.getElementById('manuel_assign' + t).innerHTML = response;
                                }
                            }
                        });
                    },
                    error: function(errorThrown) {
                        console.log(errorThrown);
                    }
                });
            }
        });

        $(document).on('click', '.btn_remove_objective', function() {
            var button_id = $(this).attr("id");
            $('#rm2' + button_id + '').remove();
            y = y - 1
            document.getElementById('nbreobj').value = y;
        });
        $(document).on('click', '.btn_remove_subtask', function() {
            var button_id = $(this).attr("id");
            $('#rm22' + button_id + '').remove();
            z = z - 1
            document.getElementById('nbresubtask').value = z;
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

        $(document).on('change', '.user_calendar', function() {
            var user_id = document.getElementById('user_calendar').value;
            $.ajax({
                url: task_manager.ajaxurl,
                type: "POST",
                data: {
                    'action': 'get_calendar',
                    'id_user': user_id
                },
                beforeSend: function() {
                    document.getElementById('calendar_card').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>';
                },
                success: function(response) {
                    document.getElementById('calendar_card').innerHTML = response;
                },
                error: function(errorThrown) {
                    document.getElementById('calendar_card').innerHTML = '<div class="alert alert-danger mt-4" role="alert">Failed to get calendar. Try again</div>';
                }
            });
        });

        $(document).on('change', '.projectSection', function() {
            var project_id = document.getElementById('project').value;
            $.ajax({
                url: task_manager.ajaxurl,
                type: "POST",
                data: {
                    'action': 'get_option_section',
                    'project_id': project_id
                },
                success: function(response) {
                    document.getElementById('project_section').innerHTML = response;
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
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
                    if (document.getElementById('assign'))
                        document.getElementById('assign').innerHTML = response;
                    if (document.getElementById('sub_assign'))
                        document.getElementById('sub_assign').innerHTML = response;
                    if (document.getElementById('manuel_assign'))
                        document.getElementById('manuel_assign').innerHTML = response;
                    for (var t = 0; t < 10; t++) {
                        if (document.getElementById('sub_assign' + t))
                            document.getElementById('sub_assign' + t).innerHTML = response;
                    }
                    for (var t = 0; t < 10; t++) {
                        if (document.getElementById('assign' + t))
                            document.getElementById('assign' + t).innerHTML = response;
                    }
                    for (var t = 0; t < 20; t++) {
                        if (document.getElementById('manuel_assign' + t))
                            document.getElementById('manuel_assign' + t).innerHTML = response;
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
                beforeSend: function() {
                    document.getElementById('template_select').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>';
                },
                success: function(response) {
                    document.getElementById('template_select').innerHTML = response;
                    if (document.getElementById('project'))
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
                            for (var t = 0; t < 10; t++) {
                                if (document.getElementById('assign' + t))
                                    document.getElementById('assign' + t).innerHTML = response;
                            }
                            for (var t = 0; t < 10; t++) {
                                if (document.getElementById('manuel_assign' + t))
                                    document.getElementById('manuel_assign' + t).innerHTML = response;
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
                    document.getElementById('first_choix').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>';
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
                $('#subtaskmore').show(1000)
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
                    if (this.value == 'manuelTemplate1') {
                        $('#subtaskmore').show(1000)
                    }
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
                            for (var t = 0; t < 10; t++) {
                                if (document.getElementById('assign' + t))
                                    document.getElementById('assign' + t).innerHTML = response;
                            }
                            for (var t = 0; t < 10; t++) {
                                if (document.getElementById('manuel_assign' + t))
                                    document.getElementById('manuel_assign' + t).innerHTML = response;
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
        if (template == 98795) {
            document.getElementById("change" + template).innerHTML = ' - Close Goal Form';
        } else {
            document.getElementById("change" + template).innerHTML = ' - ';
        }
    } else {
        div.style.display = "none";
        if (template == 98795) {
            document.getElementById("change" + template).innerHTML = ' + Add Goals';
        } else {
            document.getElementById("change" + template).innerHTML = ' + ';
        }

    }
}

function block_(template) {
    var div = document.getElementById(template);
    if (div.style.display === "none") {
        div.style.display = "block";
    }
}

function hide_(template) {
    var div = document.getElementById(template);
    if (div.style.display === "block") {
        div.style.display = "none";
    }
}