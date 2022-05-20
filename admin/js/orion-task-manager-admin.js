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

        var i = 0,
            z = 0,
            v = 0,
            u = 0;
        var val;
        $(document).on('click', '#addchamp', function() {
            i++;
            if (document.getElementById('nbresubtask')) {
                val = $('#nbresubtask').val();
                if (i < val) {
                    i = val;
                }
            }
            $('#champadd').append('<div id="rm2' + i + '"><div class="form-row pt-2"><div class="col-sm-11"><input type="text" name="tasktitle' + i + '" id="tasktitle' + i + '" class="form-control" placeholder="Task Title"></div><div class="col-sm-1"><span name="remove" id="' + i + '" class="btn btn-outline-danger btn_remove_template">X</span></div></div></div>');
        });

        $(document).on('click', '#addcriteria1', function() {
            u++;
            if (document.getElementById('nbre1')) {
                val = $('#nbre1').val();
                if (u < val) {
                    u = val;
                }
            }
            $('#criteriaadd1').append('<div id="rmu2' + u + '"><div class="form-row pt-2"><div class="col-sm-11"><div class="row"><div class="col-sm-3"><div class="form-group"><input type="text" class="form-control" id="critere1_' + u + '" placeholder="Criteria"></div></div><div class="col-sm-2 p-0 m-0"><div class="form-group"><input type="number" min="0" max="100" class="form-control" id="note1_' + u + '" value="0"></div></div><div class="col-sm-7"><div class="form-group"><textarea class="form-control" id="description1_' + u + '" rows="1" placeholder="Description ..."></textarea></div></div></div></div><div class="col-sm-1"><span name="remove" id="' + u + '" class="btn btn-outline-danger btn_remove_criteria1">X</span></div></div></div>');
        });
        $(document).on('click', '#addcriteria2', function() {
            v++;
            if (document.getElementById('nbre2')) {
                val = $('#nbre2').val();
                if (v < val) {
                    v = val;
                }
            }
            $('#criteriaadd2').append('<div id="rmv2' + v + '"><div class="form-row pt-2"><div class="col-sm-11"><div class="row"><div class="col-sm-3"><div class="form-group"><input type="text" class="form-control" id="critere2_' + v + '" placeholder="Criteria"></div></div><div class="col-sm-2 p-0 m-0"><div class="form-group"><input type="number" min="0" max="100" class="form-control" id="note2_' + v + '" value="0"></div></div><div class="col-sm-7"><div class="form-group"><textarea class="form-control" id="description2_' + v + '" rows="1" placeholder="Description ..."></textarea></div></div></div></div><div class="col-sm-1"><span name="remove" id="' + v + '" class="btn btn-outline-danger btn_remove_criteria2">X</span></div></div></div>');
        });

        $(document).on('submit', '#create_categories', function(e) {
            e.preventDefault();
            var parametre = {};
            var categorie = "";
            if (z > 0) {
                for (var y = 1; y <= z; y++) {
                    categorie = $('#categorie' + y).val();
                    parametre[y] = { categorie };
                }
            }
            //console.log(parametre);
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'save_categories',
                    'valeur': parametre
                },
                beforeSend: function() {
                    document.getElementById('add_success_categories').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>';
                    document.getElementById('categories_card').innerHTML = '';
                },
                success: function(response) {
                    document.getElementById('categories_card').innerHTML = response;
                    document.getElementById('add_success_categories').innerHTML = '<div class="alert alert-success mt-4" role="alert">Successfully updated evaluation criteria</div>';
                    setTimeout(function() { $('#add_success_categories').hide(); }, 1000);
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $(document).on('click', '#addcategorie', function() {
            z++;
            if (document.getElementById('nbresubtask')) {
                val = $('#nbresubtask').val();
                if (z < val) {
                    z = val;
                }
            }
            $('#champadd').append('<div id="rm2' + z + '"><div class="form-row pt-2"><div class="col-sm-11"><input type="text" name="categorie' + z + '" id="categorie' + z + '" class="form-control" placeholder="Categorie Name"></div><div class="col-sm-1"><span name="remove" id="' + z + '" class="btn btn-outline-danger btn_remove_categorie">X</span></div></div></div>');
        });

        $(document).on('submit', '#evaluation_criteria', function(e) {
            e.preventDefault();
            var normal = {};
            var developper = {};
            var parametre = {};
            var criteria, note, description;
            var total1 = 0,
                total2 = 0;
            for (var r = 1; r <= u; r++) {
                if (($('#critere1_' + r).val() != undefined) && (($('#critere1_' + r).val() != ''))) {
                    criteria = $('#critere1_' + r).val();
                    note = $('#note1_' + r).val();
                    total1 += parseInt(note);
                    description = $('#description1_' + r).val();
                    developper[r] = { criteria: criteria, note: note, description: description };
                }
            }
            for (var r = 1; r <= v; r++) {
                if (($('#critere2_' + r).val() != undefined) && (($('#critere2_' + r).val() != ''))) {
                    criteria = $('#critere2_' + r).val();
                    note = $('#note2_' + r).val();
                    total2 += parseInt(note);
                    description = $('#description2_' + r).val();
                    normal[r] = { criteria: criteria, note: note, description: description };
                }
            }
            if (total1 > 100) {
                alert('Error : The total developmet task score is greater than 100');
            } else if (total2 > 100) {
                alert('Error : The total normal task score is greater than 100');
            } else {
                parametre = { normal: normal, developper: developper };
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        'action': 'save_criteria_evaluation',
                        'valeur': parametre
                    },
                    beforeSend: function() {
                        document.getElementById('criteria_evaluation_tab').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>';
                    },
                    success: function(response) {
                        document.getElementById('criteria_evaluation_tab').innerHTML = response;
                        document.getElementById('success_criteria_add').innerHTML = '<div class="alert alert-success mt-4" role="alert">Successfully updated evaluation criteria</div>';
                        setTimeout(function() { $('#success_criteria_add').hide(); }, 3000);
                    },
                    error: function(errorThrown) {
                        console.log(errorThrown);
                    }
                });
            }
        });

        $(document).on('click', '.btn_list_task', function() {
            document.getElementById('add_success').innerHTML = '';
            var action_template = $(this).attr('id');
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

        $(document).on('click', '.btn_emails', function() {
            var action_template = $(this).attr('id');
            if (action_template == 'new_email') {
                document.getElementById('new_email').innerHTML = 'List Email Template';
                $(this).attr('id', 'list_email');
            }
            if (action_template == 'list_email') {
                document.getElementById('list_email').innerHTML = 'New Email Template';
                $(this).attr('id', 'new_email');
            }
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'get_email_card',
                    'valeur': action_template
                },
                beforeSend: function() {
                    document.getElementById('evaluator_tab').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>';
                },
                success: function(response) {
                    document.getElementById('evaluator_tab').innerHTML = response;
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
            if (document.getElementById('add_success')) {
                document.getElementById('add_success').innerHTML = '';
            }
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
                    setTimeout(function() { $('#add_success').hide(); }, 3000);
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $(document).on('click', '.email_remove', function() {
            var id_template = $(this).attr('id');
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'delete_email_',
                    'id_template': id_template
                },
                beforeSend: function() {
                    document.getElementById('evaluator_tab').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>';
                },
                success: function(response) {
                    document.getElementById('evaluator_tab').innerHTML = response;
                    document.getElementById('add_success').innerHTML = '<div class="alert alert-success" role="alert">Deletion completed successfully</div>';
                    setTimeout(function() { $('#add_success').hide(); }, 1500);
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $(document).on('click', '.edit_categorie', function() {
            var id_categorie = $(this).attr('id');

            $('#name' + id_categorie).removeAttr("disabled");
            var valeur = $('#name' + id_categorie).val();

            if ($(this).attr('update' + id_categorie)) {
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        'action': 'update_categorie_',
                        'id_categorie': id_categorie,
                        'valeur': valeur
                    },
                    success: function(response) {
                        document.getElementById("categories_card").innerHTML = response;
                    },
                    error: function(errorThrown) {
                        console.log(errorThrown);
                    }
                });
            } else {
                $(this).attr('update' + id_categorie, 'update' + id_categorie);
                document.getElementById("edit_" + id_categorie).innerHTML = 'Update';
            }


        });

        $(document).on('click', '.delete_categorie', function() {
            var id_categorie = $(this).attr('id');
            console.log(id_categorie);
        });

        $(document).on('click', '.email_edit', function() {
            var id_mail_template = $(this).attr('id');
            document.getElementById('new_email').innerHTML = 'List Email Template';
            $('#new_email').attr('id', 'list_email');

            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'edit_template_mail',
                    'id_template_mail': id_mail_template
                },
                beforeSend: function() {
                    document.getElementById('evaluator_tab').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>';
                },
                success: function(response) {
                    document.getElementById('evaluator_tab').innerHTML = response;
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            })
        });

        $(document).on('click', '.template_edit', function() {
            var id_template = $(this).attr('id');
            document.getElementById('template_label').innerHTML = 'Edit Template';
            document.getElementById('template_btn_add').innerHTML = 'List Template';
            $('.btn_list_task').attr('id', 'template_btn_list');

            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'update_template',
                    'id_template': id_template
                },
                beforeSend: function() {
                    document.getElementById('template_card').innerHTML = '';
                    document.getElementById('create_template').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>';
                },
                success: function(response) {
                    document.getElementById('create_template').innerHTML = response;
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $(document).on('click', '.btn_remove_template', function() {
            var button_id = $(this).attr("id");
            $('#rm2' + button_id + '').remove();
            i = i - 1
        });

        $(document).on('click', '.btn_remove_categorie', function() {
            var button_id = $(this).attr("id");
            $('#rm2' + button_id + '').remove();
            z = z - 1
        });
        $(document).on('click', '.btn_remove_criteria1', function() {
            var button_id = $(this).attr("id");
            $('#rmu2' + button_id + '').remove();
        });
        $(document).on('click', '.btn_remove_criteria2', function() {
            var button_id = $(this).attr("id");
            $('#rmv2' + button_id + '').remove();
        });

        $(document).on('change', '#task_name', function() {
            var select = document.getElementById('task_name').value;
            document.getElementById('subject_mail').value = "Evaluation de " + select;
        });

        $(document).on('submit', '#email_send_form', function(e) {
            e.preventDefault();
            var task_name = $('#task_name').val();
            var subject = $('#subject_mail').val();
            var content = $('#content_mail').val();
            var update = false;
            var id_template = -1;
            if ($('#id_template').val()) {
                var id_template = $('#id_template').val();
                update = true;
            }
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'save_mail_form',
                    'type_task': task_name,
                    'subject': subject,
                    'content': content,
                    'update': update,
                    'id_template': id_template
                },
                beforeSend: function() {
                    document.getElementById('evaluator_tab').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>';
                },
                success: function(response) {
                    document.getElementById('evaluator_tab').innerHTML = response;
                    document.getElementById('add_success').innerHTML = '<div class="alert alert-success" role="alert">Save successfully</div>';
                    setTimeout(function() { $('#add_success').hide(); }, 1500);
                    document.getElementById('list_email').innerHTML = 'New Email Template';
                    $('#list_email').attr('id', 'new_email');
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
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
            var updatetempplate_id = "";
            var templatetitle = $('#templatetitle').val();
            var tasktitle = $('#tasktitle').val();
            var type_task = $('#type_task').val();
            if (document.getElementById('updatetempplate_id')) {
                updatetempplate_id = $('#updatetempplate_id').val();

            }
            if (document.getElementById('nbresubtask')) {
                val = $('#nbresubtask').val();
                if (i < val) {
                    i = val - 1;
                }
            }
            template = { templatetitle: templatetitle, tasktitle: tasktitle, type_task: type_task };
            if (i != 0) {
                for (var y = 1; y <= i; y++) {
                    subtitle = $('#tasktitle' + y).val();;
                    subtemplate[y] = { subtitle };
                }
            }
            parametre = { template: template, subtemplate: subtemplate };
            //console.log(parametre);
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'create_template',
                    'parametre': parametre,
                    'updatetempplate_id': updatetempplate_id
                },
                success: function(response) {
                    console.log(response);
                    if (updatetempplate_id != "") {
                        if (response)
                            document.getElementById('add_success').innerHTML = '<div class="alert alert-success" role="alert">Edit successfully</div>';
                        else
                            document.getElementById('add_success').innerHTML = '<div class="alert alert-danger" role="alert">Edit error</div>';
                    } else {
                        if (response)
                            document.getElementById('add_success').innerHTML = '<div class="alert alert-success" role="alert">New template created successfully</div>';
                        else
                            document.getElementById('add_success').innerHTML = '<div class="alert alert-danger" role="alert">Error occurred during template creation</div>';
                    }
                    setTimeout(function() { $('#add_success').hide(); }, 3000);
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
                    setTimeout(function() { $('#add_success1').hide(); }, 3000);
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
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
        document.getElementById("change" + template).innerHTML = ' > ';
    }
}