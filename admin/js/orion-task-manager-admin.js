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

        var i = 0;
        $('#addchamp').click(function() {
            i++;
            $('#champadd').append('<div id="rm2' + i + '"><div class="form-row pt-2"><div class="col-sm-11"><input type="text" name="tasktitle' + i + '" id="tasktitle' + i + '" class="form-control" placeholder="Task Title"></div><div class="col-sm-1"><span name="remove" id="' + i + '" class="btn btn-outline-danger btn_remove_template">X</span></div></div></div>');
        });

        $(document).on('click', '.btn_list_task', function() {
            var action_template = $(this).attr('id');
            document.getElementById('add_success').innerHTML = '';
            if (action_template == 'template_btn_list') {
                document.getElementById('template_btn_list').innerHTML = 'Add New Template';
                $(this).attr('id', 'template_btn_add');
                document.getElementById('template_label').innerHTML = 'List Template';
            }
            if (action_template == 'template_btn_add') {
                document.getElementById('template_btn_add').innerHTML = 'List Template';
                $(this).attr('id', 'template_btn_list');
                document.getElementById('template_label').innerHTML = 'Add New Template';
            }
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'get_template_card',
                    'valeur': action_template
                },
                beforeSend: function() {
                    document.getElementById('template_card').innerHTML = '';
                    document.getElementById('create_template').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>';
                },
                success: function(response) {
                    if (action_template == 'template_btn_list') {
                        document.getElementById('create_template').innerHTML = '';
                        document.getElementById('template_card').innerHTML = response;
                    }
                    if (action_template == 'template_btn_add') {
                        document.getElementById('template_card').innerHTML = '';
                        document.getElementById('create_template').innerHTML = response;
                    }
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $(document).on('click', '.worklog_authorized', function() {
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'worklog_update',
                },
                beforeSend: function() {
                    document.getElementById('worklog_card').innerHTML = '<div class="alert alert-info mt-4 card" role="alert">Loading ... </div>';
                },
                success: function(response) {
                    document.getElementById('worklog_card').innerHTML = response;
                }
            });
        });

        $(document).on('click', '.template_remove', function() {
            var id_template = $(this).attr('id');
            //console.log(id_template);
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'delete_template_',
                    'id_template': id_template
                },
                success: function(response) {
                    document.getElementById('template_card').innerHTML = response;
                    document.getElementById('add_success').innerHTML = '<div class="alert alert-success" role="alert">Deletion completed successfully</div>';
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $(document).on('click', '.template_edit', function() {
            var id_template = $(this).attr('id');
            console.log(id_template);
            // $.ajax({
            //     url: ajaxurl,
            //     type: "POST",
            //     data: {
            //         'action': 'update_template',
            //         'id_template': id_template
            //     },
            //     success: function(response) {
            //         document.getElementById('template_card').innerHTML = response;
            //     },
            //     error: function(errorThrown) {
            //         console.log(errorThrown);
            //     }
            // });
        });


        $(document).on('click', '.btn_remove_template', function() {
            var button_id = $(this).attr("id");
            $('#rm2' + button_id + '').remove();
            i = i - 1
        });

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
                    document.getElementById('roledisabled').innerHTML = response;
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $('#user_role_asana').submit(function(e) {
            e.preventDefault();
            var select_user = document.getElementById('userasana').value;
            var select_role = document.getElementById('role_user').value;

            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'update_user_role',
                    'id_user': select_user,
                    'select_role': select_role,
                },
                success: function(response) {
                    document.getElementById('roledisabled').innerHTML = response;
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $('#create_template').submit(function(e) {
            e.preventDefault();
            console.log('Le clic sur le bouton a été pris en compte');
            var template = {};
            var subtemplate = {};
            var parametre = {};
            var subtitle = "";
            var templatetitle = $('#templatetitle').val();
            var tasktitle = $('#tasktitle').val();
            var role = $('#role').val();
            template = { templatetitle: templatetitle, tasktitle: tasktitle, role: role };
            if (i != 0) {
                for (var y = 1; y <= i; y++) {
                    subtitle = $('#tasktitle' + y).val();;
                    subtemplate[y] = { subtitle };
                }
            }
            parametre = { template: template, subtemplate: subtemplate }
                //console.log(parametre);
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'create_template',
                    'parametre': parametre
                },
                success: function(response) {
                    if (response)
                        document.getElementById('add_success').innerHTML = '<div class="alert alert-success" role="alert">New template created successfully</div>';
                    else
                        document.getElementById('add_success').innerHTML = '<div class="alert alert-danger" role="alert">Error occurred during template creation</div>';
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $('#create_new_projet').submit(function(e) {
            e.preventDefault();
            console.log('Le clic sur le bouton a été pris en compte');
            var multi_choix = $('#multichoix option:selected').toArray().map(item => item.value);
            var projectmanager = document.getElementById('projectmanager').value;
            var title = $('#titleproject').val();
            var slug = $('#slug').val();
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'create_new_projet',
                    'title': title,
                    'slug': slug,
                    'project_manager': projectmanager,
                    'collaborator': multi_choix,
                },
                success: function(response) {
                    console.log(response);
                    if (response)
                        document.getElementById('add_success1').innerHTML = '<div class="alert alert-success" role="alert">New project created successfully</div>';
                    else
                        document.getElementById('add_success1').innerHTML = '<div class="alert alert-danger" role="alert">Error occurred during project creation</div>';
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });
    });

})(jQuery);