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

    $(document).ready(function() {
        var i = 0,
            z = 0,
            y = 0,
            v = 0,
            u = 0;
        var val;
        var sortir = 0;

        $(document).on('click', '.syncNow', function() {
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'manuellySync',
                    'valeur': 'tag'
                },
                beforeSend: function() {
                    document.getElementById('msg_manuel_syn').innerHTML = '<div class="alert alert-info mt-4" id="msg_first" role="alert">Synchronization in progress ... </div>';
                },
                success: function(response) {
                    $('#msg_manuel_syn').append('<h5><input type="checkbox"checked >Category synchronization completed</h5>');
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            'action': 'manuellySync',
                            'valeur': 'projet'
                        },
                        success: function(response) {
                            $('#msg_manuel_syn').append('<h5><input type="checkbox"checked >Synchronization of projects completed</h5>');
                            $.ajax({
                                url: ajaxurl,
                                type: "POST",
                                data: {
                                    'action': 'manuellySync',
                                    'valeur': 'objectif'
                                },
                                success: function(response) {
                                    $('#msg_manuel_syn').append('<h5><input type="checkbox"checked >Synchronization of goals completed </h5>');
                                    $.ajax({
                                        url: ajaxurl,
                                        type: "POST",
                                        data: {
                                            'action': 'manuellySync',
                                            'valeur': 'task'
                                        },
                                        success: function(response) {
                                            $('#msg_manuel_syn').append('<h5><input type="checkbox"checked >Synchronization of tasks completed</h5>');
                                            $.ajax({
                                                url: ajaxurl,
                                                type: "POST",
                                                data: {
                                                    'action': 'manuellySync',
                                                    'valeur': 'duedate'
                                                },
                                                success: function(response) {
                                                    $('#msg_manuel_syn').append('<h5><input type="checkbox"checked >Synchronization of due dates completed</h5>');
                                                    document.getElementById('msg_first').innerHTML = '<h5 class="text-success">Synchronization completed. Thanks!</h5>';
                                                    setTimeout(function() { $('#msg_manuel_syn').hide(); }, 10000);
                                                }
                                            });
                                        }
                                    });
                                }
                            });
                        }
                    });
                }
            });
        });

        $(document).on('click', '.form__btn', function() {
            var action = $(this).attr('id');
            var isAction = false;
            if (action == 'btn-1') {
                isAction = true;
                var data = {
                    'action': 'set_first_parameter_plugin',
                    'accessToken': $('#accessToken').val(),
                    'asana_workspace_id': $('#asana_workspace_id').val(),
                    'projetId': $('#projetId').val()
                }
            }
            if (action == 'btn-2-next') {
                isAction = true;
                var data = {
                    'action': 'set_first_parameter_plugin',
                    'sender_name': $('#sender_name').val(),
                    'sender_email': $('#sender_email').val(),
                    'email_manager': $('#email_manager').val(),
                    'date_report_sent': $('#date_report_sent').val()
                }
            }
            if (action == 'btn-3') {
                isAction = true;
                var data = {
                    'action': 'set_first_parameter_plugin',
                    'email_rh': $('#email_rh').val(),
                    'nbreSubPeroformance': $('#nbreSubPeroformance').val(),
                    'moyenne': $('#moyenne').val()
                }
                document.getElementById('msg_first_config').style.display = 'block';
            }
            if (isAction) {
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: data,
                    success: function(response) {}
                });
            }
        })

        $(document).on('click', '#addchamp', function() {
            i++;
            if (document.getElementById('nbresubtask')) {
                val = $('#nbresubtask').val();
                if (i < val) {
                    i = val;
                }
            }
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'save_categories',
                    'get_categorie': ''
                },
                success: function(response) {
                    $('#champadd').append('<div id="rm2' + i + '"><div class="form-row pt-2"><div class="col"><input type="text" name="tasktitle' + i + '" id="tasktitle' + i + '" class="form-control" placeholder="Task Title"></div><div class="col"><select id="categorie' + i + '" name="categorie' + i + '" class="form-control">' + response + '</select></div><div class="col-sm-1"><span name="remove" id="' + i + '" class="btn btn-outline-danger btn_remove_template">X</span></div></div></div>');
                }
            });
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
            var evaluate = false;
            if (z > 0) {
                for (var y = 1; y <= z; y++) {
                    categorie = $('#categorie' + y).val();
                    if ($('#evaluate' + y + ':checked').val() == 'on')
                        evaluate = 1;
                    else
                        evaluate = 0;
                    parametre[y] = { categorie, evaluate };
                }
            }
            // console.log(parametre);
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
                    document.getElementById('add_success_categories').innerHTML = '<div class="alert alert-success mt-4" role="alert">Successfully updated or saved categories</div>';
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $(document).on('click', '.evaluateUpdata', function() {
            var categorieId = $(this).attr('id');
            console.log();
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'project_card',
                    'categorieIdUpdate': categorieId
                },
                success: function(response) {
                    if (response) {
                        if ($('#' + categorieId + ':checked').val() == 'on')
                            document.getElementById('label' + categorieId).innerHTML = 'Yes';
                        else
                            document.getElementById('label' + categorieId).innerHTML = 'No';
                        document.getElementById('add_success_categories').innerHTML = '<div class="alert alert-success mt-4" role="alert">Operation successfully completed !</div>';
                    } else
                        document.getElementById('add_success_categories').innerHTML = '<div class="alert alert-danger mt-4" role="alert">Problem occurred. Try again !</div>';
                    setTimeout(function() { document.getElementById('add_success_categories').innerHTML = ''; }, 4000);
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
            $('#champadd').append('<div id="rm2' + z + '"><div class="form-row pt-2"><div class="col-sm-7"><input type="text" name="categorie' + z + '" id="categorie' + z + '" class="form-control" placeholder="Categorie Name"></div><div class="col-sm-"><div class="custom-control custom-checkbox my-1 mr-sm-2"><input type="checkbox" class="custom-control-input" id="evaluate' + z + '"><label class="custom-control-label" for="evaluate' + z + '">Evaluate (Yes/No) </label></div></div><div class="col-sm-1"><span name="remove" id="' + z + '" class="btn btn-outline-danger btn_remove_categorie">X</span></div></div></div>');
        });

        $(document).on('click', '#addsection', function() {
            y++;
            if (document.getElementById('nbresection')) {
                val = $('#nbresection').val();
                if (y < val) {
                    y = val;
                }
            }
            $('#addsectionchamp').append('<div id="rm2' + y + '"><div class="form-row pt-2"><div class="col-sm-11"><input type="text" name="section' + y + '" id="section' + y + '" class="form-control" placeholder="Section Name"></div><div class="col-sm-1"><span name="remove" id="' + y + '" class="btn btn-outline-danger btn_remove_section">X</span></div></div></div>');
        });

        $(document).on('submit', '#evaluation_criteria', function(e) {
            e.preventDefault();
            var normal = {};
            var developper = {};
            var parametre = {};
            var criteria, note, description;
            var total1 = 0,
                total2 = 0;
            var val1 = $('#nbre1').val();
            if (u < val1) {
                u = val1;
            }
            var val2 = $('#nbre2').val();
            if (v < val2) {
                v = val2;
            }
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
                        setTimeout(function() { document.getElementById('success_criteria_add').innerHTML = ''; }, 10000);
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

        $(document).on('click', '.btn_list_project', function() {
            document.getElementById('add_success').innerHTML = '';
            document.getElementById('add_success1').innerHTML = '';
            var action_template = $(this).attr('id');
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'project_card',
                    'valeur': action_template
                },
                beforeSend: function() {
                    document.getElementById('project_card').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>'
                },
                success: function(response) {
                    if (action_template == 'project_btn_list') {
                        document.getElementById('project_card').innerHTML = response;
                    }
                    if (action_template == 'project_btn_add') {
                        document.getElementById('project_card').innerHTML = response;
                    }
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $(document).on('click', '.project_edit', function() {
            document.getElementById('add_success1').innerHTML = '';
            var id_project = $(this).attr('id');
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'project_card',
                    'update_id': id_project
                },
                beforeSend: function() {
                    document.getElementById('project_card').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>';
                },
                success: function(response) {
                    document.getElementById('project_card').innerHTML = response;
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $(document).on('submit', '.editCollaborator', function(e) {
            e.preventDefault();
            document.getElementById('add_success1').innerHTML = '';
            var id_project = $(this).attr('id');
            var project_manager = $('#project_manager' + id_project).val();
            var multi_choix = $('#multichoix' + id_project + ' option:selected').toArray().map(item => item.value);
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'editCollaborator',
                    'project_id': id_project,
                    'project_manager': project_manager,
                    'collaborators': multi_choix
                },
                success: function(response) {
                    document.getElementById('successCol').innerHTML = '<div class="alert alert-success mt-4" role="alert">Updated collaborators successfully completed</div>';
                    $('#btn_submit' + id_project).hide();
                    $('#btn_close' + id_project).removeClass("btn-secondary");
                    $('#btn_close' + id_project).addClass("btn-success");
                    document.getElementById('btn_close' + id_project).innerHTML = 'Finish';
                    setTimeout(function() {
                        $('#btn_submit' + id_project).show();
                        $('#btn_close' + id_project).removeClass("btn-success");
                        $('#btn_close' + id_project).addClass("btn-secondary");
                        document.getElementById('btn_close' + id_project).innerHTML = 'Close';
                    }, 5000);
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $(document).on('click', '.project_archive', function() {
            document.getElementById('add_success1').innerHTML = '';
            var id_project = $(this).attr('id');
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'project_card',
                    'archive_project': id_project
                },
                beforeSend: function() {
                    document.getElementById('project_card').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>';
                },
                success: function(response) {
                    document.getElementById('project_card').innerHTML = response;
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

        $(document).on('click', '.worklog_authorized,.debug_authorized', function() {
            var operation = $(this).attr('id');
            console.log(operation);
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': operation + '_update',
                },
                beforeSend: function() {
                    document.getElementById(operation + '_card').innerHTML = '<div class="alert alert-info mt-4 card" role="alert">Loading ... </div>';
                },
                success: function(response) {
                    document.getElementById(operation + '_card').innerHTML = response;
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
                    setTimeout(function() { document.getElementById('add_success').innerHTML = '' }, 10000);
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $(document).on('click', '.edit_categorie', function() {
            var id_categorie = $(this).attr('id');

            $('#name' + id_categorie).removeAttr("readonly");
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
            document.getElementById(id_categorie).setAttribute("disabled", "");
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'delete_categorie_',
                    'id_categorie': id_categorie
                },
                success: function(response) {
                    if (response != false) {
                        document.getElementById("categories_card").innerHTML = response;
                        document.getElementById("add_success_categories").innerHTML = '<div class="alert alert-success mt-4" role="alert">Deletion completed successfully !</div>';
                    } else {
                        document.getElementById("add_success_categories").innerHTML = '<span class="alert alert-danger">Error !</span>';
                    }
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
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

        $(document).on('submit', '.config_asana', function(e) {
            e.preventDefault();
            var operation = $(this).attr('id');
            if (operation == 'synchronisation_asana') {
                var sync_time = document.getElementById('synchonisation_time').value;
                var data = {
                    'action': 'synchronisation_time',
                    'sync_time': sync_time,
                }
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: data,
                    beforeSend: function() {
                        document.getElementById('add_' + operation).innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>';
                    },
                    success: function(response) {
                        if (response)
                            document.getElementById('add_' + operation).innerHTML = '<div class="alert alert-success mt-4" role="alert"> Successfully ! </div>';
                        else
                            document.getElementById('add_' + operation).innerHTML = '<div class="alert alert-danger mt-4" role="alert"> Error ! </div>';
                        setTimeout(function() { $('#add_' + operation).hide(); }, 7000);
                    }
                });
            }
            if (operation == 'workspace_asana') {
                var asana_workspace_id = document.getElementById('asana_workspace_id').value;
                var id_project_manager = document.getElementById('id_project_manager').value;
                var asana_workspace_id_old = document.getElementById('asana_workspace_id_old').value;
                if (asana_workspace_id == asana_workspace_id_old) {
                    document.getElementById('close_btn').innerHTML = 'Close';
                    document.getElementById('title_change').innerHTML = 'OPERATION MESSAGE';
                    document.getElementById('card_warning1').innerHTML = '<div class="alert alert-success mt-4" role="alert"> You cannot change the access token because it already exists. ! </div>';

                    $('#yes_close').hide();
                    $('#msg_change').hide();
                    $('#card_warning').hide();
                    $("#title_change").removeClass("text-warning");
                    setTimeout(function() {
                        $('#yes_close').show();
                        $('#msg_change').show();
                        $('#card_warning').show();
                        document.getElementById('close_btn').innerHTML = ' ~ No ~ ';
                        document.getElementById('title_change').innerHTML = 'WARNING';
                        $("#title_change").addClass("text-warning");
                        document.getElementById('card_warning1').innerHTML = '';
                    }, 5000);
                } else {
                    document.getElementById('card_warning1').innerHTML = '<div class="alert alert-primary mt-4" role="alert">Deletion in progress ...</div>';
                    $('#card_warning').hide();
                    $('#yes_close').hide();
                    $('#msg_change').hide();
                    document.getElementById('close_btn').innerHTML = 'Close';
                    var data = {
                        'action': 'synchronisation_time',
                        'asana_workspace_id': asana_workspace_id,
                        'id_project_manager': id_project_manager,
                    }
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: data,
                        success: function(response) {
                            if (response) {
                                document.getElementById('card_warning1').innerHTML = '<div class="alert alert-success mt-4" role="alert">Synchronization in progress ...<br><strong><h6>Operation almost complete...</h6></strong></div>';
                                $('#card_warning').show();
                                document.getElementById('card_warning').innerHTML = '<h5 class="pl-4 text-success"><input type="checkbox" checked>Delete completed</h5>';
                                $('#card_warning').append('<hr><h5 class="pl-4">Synhronization : </h5>');
                                ajaxSync('categorie').success(function(data) {
                                    if (data) {
                                        $('#card_warning').append('<h5 class="pl-4"><input type="checkbox" checked>Category synchronization completed</h5>');
                                        ajaxSync('project').success(function(data) {
                                            if (data) {
                                                $('#card_warning').append('<h5 class="pl-4"><input type="checkbox" checked>Synchronization of projects completed</h5>');
                                                ajaxSync('objective').success(function(data) {
                                                    if (data) {
                                                        $('#card_warning').append('<h5 class="pl-4"><input type="checkbox" checked>Synchronization of goals completed</h5>');
                                                        ajaxSync('task').success(function(data) {
                                                            if (data) {
                                                                $('#card_warning').append('<h5 class="pl-4"><input type="checkbox" checked>Synchronization of tasks completed</h5>');
                                                                ajaxSync('categorie').success(function(data) {
                                                                    if (data) {
                                                                        $('#card_warning').append('<h5 class="pl-4"><input type="checkbox" checked>Synchronization of due dates completed</h5>');
                                                                        document.getElementById('card_warning1').innerHTML = '<div class="alert alert-primary mt-4" role="alert"><strong><h5>Operation completed</h5></strong></div>';
                                                                        $('#card_warning').append('<h5 class="pl-4 text-success">Synchronization completed</h5>');
                                                                        document.getElementById('close_btn').innerHTML = 'Finish';
                                                                    } else {
                                                                        $('#card_warning').append('<h5 class="pl-4 text-danger"><input type="checkbox" >Synchronization of due dates error</h5>');
                                                                        document.getElementById('card_warning1').innerHTML = '<div class="alert alert-danger mt-4" role="alert">Error try again !';
                                                                    }
                                                                });
                                                            } else {
                                                                $('#card_warning').append('<h5 class="pl-4 text-danger"><input type="checkbox" >Synchronization of tasks error</h5>');
                                                                document.getElementById('card_warning1').innerHTML = '<div class="alert alert-danger mt-4" role="alert">Error try again !';

                                                            }
                                                        });
                                                    } else {
                                                        $('#card_warning').append('<h5 class="pl-4 text-danger"><input type="checkbox" >Synchronization of goals error</h5>');
                                                        document.getElementById('card_warning1').innerHTML = '<div class="alert alert-danger mt-4" role="alert">Error try again !';
                                                    }
                                                });
                                            } else {
                                                $('#card_warning').append('<h5 class="pl-4 text-danger"><input type="checkbox" >Synchronization of projects error</h5>');
                                                document.getElementById('card_warning1').innerHTML = '<div class="alert alert-danger mt-4" role="alert">Error try again !';
                                            }
                                        });
                                    } else {
                                        $('#card_warning').append('<h5 class="pl-4 text-danger"><input type="checkbox" >Category synchronization error</h5>');
                                        document.getElementById('card_warning1').innerHTML = '<div class="alert alert-danger mt-4" role="alert">Error try again !';
                                    }
                                });
                            } else {
                                document.getElementById('card_warning1').innerHTML = '<div class="alert alert-danger mt-4" role="alert">Error try again !';
                            }
                        }
                    });
                }
            }
        });

        function ajaxSync(element) {
            return $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'synchronisation_time',
                    'all_sync': element
                }
            });
        }

        $(document).on('submit', '#project_manager_id', function(e) {
            e.preventDefault();
            var id_project_manager = document.getElementById('id_project_manager').value;
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'synchronisation_time',
                    'id_project_manager': id_project_manager
                },
                beforeSend: function() {
                    document.getElementById('add_success_id').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>';
                },
                success: function(response) {
                    if (response)
                        document.getElementById('add_success_id').innerHTML = '<div class="alert alert-success mt-4" role="alert"> Successfully ! </div>';
                    else
                        document.getElementById('add_success_id').innerHTML = '<div class="alert alert-danger mt-4" role="alert"> Error ! </div>';
                    setTimeout(function() { document.getElementById('add_success_id').innerHTML = '' }, 10000);
                }
            });
        });

        $(document).on('submit', '.reportPerformance', function(e) {
            e.preventDefault();
            var operation = $(this).attr('id');
            if (operation == 'report_send_save') {
                var email_manager = $('#email_manager').val();
                var date_report_sent = $('#date_report_sent').val();
                var sent_cp = $('#sent_cp:checked').val();
                if (sent_cp == undefined) sent_cp = false;
                var data = {
                    'action': 'synchronisation_time',
                    'email_manager': email_manager,
                    'date_report_sent': date_report_sent,
                    'sent_cp': sent_cp,
                }
            }
            if (operation == 'performance_parameter') {
                var email_rh = $('#email_rh').val();
                var nbreSubPeroformance = $('#nbreSubPeroformance').val();
                var moyenne = $('#moyenne').val();
                var data = {
                    'action': 'synchronisation_time',
                    'email_rh': email_rh,
                    'nbreSubPeroformance': nbreSubPeroformance,
                    'moyenne': moyenne,
                }
            }
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: data,
                beforeSend: function() {
                    document.getElementById('add_success_id').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>';
                },
                success: function(response) {
                    if (response)
                        document.getElementById('add_success_id').innerHTML = '<div class="alert alert-success mt-4" role="alert"> Successfully ! </div>';
                    else
                        document.getElementById('add_success_id').innerHTML = '<div class="alert alert-danger mt-4" role="alert"> Error ! </div>';
                    setTimeout(function() { $('#add_success_id').hide(); }, 10000);
                }
            });
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

        $(document).on('click', '.btn_remove_section', function() {
            var button_id = $(this).attr("id");
            $('#rm2' + button_id + '').remove();
            y = y - 1
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
            if (select == 'performance') document.getElementById('subject_mail').value = "Performance plan email";
            else if (select == 'subperformance') document.getElementById('subject_mail').value = "Performance sub-plan email";
            else document.getElementById('subject_mail').value = "Evaluation de " + select;
        });

        $(document).on('click', '#project_name_msg', function() {
            $('#content_mail').val($("#content_mail").val() + " {{project_name}} ");
        });
        $(document).on('click', '#task_name_msg', function() {
            $('#content_mail').val($("#content_mail").val() + " {{task_name}} ");
        });
        $(document).on('click', '#task_link_msg', function() {
            $('#content_mail').val($("#content_mail").val() + " {{task_link}} ");
        });
        $(document).on('click', '#form_link_msg', function() {
            $('#content_mail').val($("#content_mail").val() + " {{form_link}} ");
        });

        $(document).on('submit', '#email_send_form', function(e) {
            e.preventDefault();
            var type_task = $('#task_name').val();
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
                    'type_task': type_task,
                    'subject': subject,
                    'content': content,
                    'update': update,
                    'id_template': id_template
                },
                beforeSend: function() {
                    document.getElementById('add_success').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>';
                },
                success: function(response) {
                    if (response == 'false')
                        document.getElementById('add_success').innerHTML = '<div class="alert alert-danger" role="alert">Error while saving</div>';
                    else {
                        document.getElementById('evaluator_tab').innerHTML = response;
                        document.getElementById('add_success').innerHTML = '<div class="alert alert-success" role="alert">Save successfully</div>';
                        document.getElementById('list_email').innerHTML = 'New Email Template';
                        $('#list_email').attr('id', 'new_email');
                    }
                    setTimeout(function() { document.getElementById('add_success').innerHTML = ''; }, 10000);
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $(document).on('submit', '#add_sender_info', function(e) {
            e.preventDefault();
            var sender_name = $('#sender_name').val();
            var sender_email = $('#sender_email').val();
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'get_user_role',
                    'sender_email': sender_email,
                    'sender_name': sender_name,
                },
                success: function(response) {
                    if (response) {
                        document.getElementById('add_success').innerHTML = '<div class="alert alert-success" role="alert">Successfully</div>';
                    } else {
                        document.getElementById('add_success').innerHTML = '<div class="alert alert-danger" role="alert">Error</div>';
                    }
                    setTimeout(function() { document.getElementById('add_success').innerHTML = ''; }, 10000);
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $(document).on('submit', '#create_template', function(e) {
            e.preventDefault();
            var template = {};
            var subtemplate = {};
            var parametre = {};
            var subtitle = "";
            var categorie = "";
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
                    subtitle = $('#tasktitle' + y).val();
                    categorie = $('#categorie' + y).val();
                    subtemplate[y] = { subtitle, categorie };
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
                    //console.log(response);
                    if (updatetempplate_id != "") {
                        if (response) {
                            document.getElementById('add_success').innerHTML = '<div class="alert alert-success" role="alert">Edit successfully</div>';
                            document.getElementById('create_template').innerHTML = '';
                            document.getElementById('template_card').innerHTML = response;
                        } else
                            document.getElementById('add_success').innerHTML = '<div class="alert alert-danger" role="alert">Edit error</div>';
                    } else {
                        if (response) {
                            document.getElementById('add_success').innerHTML = '<div class="alert alert-success" role="alert">New template created successfully</div>';
                            document.getElementById('create_template').innerHTML = '';
                            document.getElementById('template_card').innerHTML = response;
                        } else
                            document.getElementById('add_success').innerHTML = '<div class="alert alert-danger" role="alert">Error occurred during template creation</div>';
                    }
                    setTimeout(function() { document.getElementById('add_success').innerHTML = ''; }, 10000);
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });

        $(document).on('submit', '#create_new_projet', function(e) {
            e.preventDefault();
            document.getElementById('add_success1').innerHTML = '';
            var multi_choix = $('#multichoix option:selected').toArray().map(item => item.value);
            var projectmanager = document.getElementById('projectmanager').value;
            var project_id = "";
            var parametre = {};
            if (document.getElementById('project_id')) {
                project_id = document.getElementById('project_id').value;
                var section = "";
            } else {
                var section = "Untitled section";
                parametre[0] = { section };
            }

            if (document.getElementById('nbresection')) {
                val = $('#nbresection').val();
                if (y < val) {
                    y = val - 1;
                }
            }

            if (y > 0) {
                for (var p = 1; p <= y; p++) {
                    section = $('#section' + p).val();
                    parametre[p] = { section };
                }
            }
            y = 0;

            var title = $('#titleproject').val();
            var slug = $('#slug').val();
            var description = $('#description').val();
            $("#collapseFour1").removeClass("show");
            $("#collapseThree").addClass("show");
            //console.log(project_id);
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'create_new_projet',
                    'title': title,
                    'slug': slug,
                    'description': description,
                    'project_manager': projectmanager,
                    'collaborator': multi_choix,
                    'project_id': project_id,
                    'section': parametre
                },
                beforeSend: function() {
                    document.getElementById('add_success1').innerHTML = '<div class="alert alert-info mt-4" role="alert">Loading ... </div>';
                },
                success: function(response) {
                    if (response) {
                        document.getElementById('add_success1').innerHTML = '<div class="alert alert-success" role="alert">New project created successfully</div>';
                        document.getElementById('project_card').innerHTML = response;
                    } else
                        document.getElementById('add_success1').innerHTML = '<div class="alert alert-danger" role="alert">Error occurred during project creation</div>';
                    setTimeout(function() { document.getElementById('add_success1').innerHTML = ''; }, 10000);
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
        if (template === 11111) {
            document.getElementById(22222).style.display = "none";
            document.getElementById('bg' + 22222).style.background = "";
        } else {
            document.getElementById(11111).style.display = "none";
            document.getElementById('bg' + 11111).style.background = "";

        }
        div.style.display = "block";
        document.getElementById('bg' + template).style.background = "white";
    }
}

function myfunction(value) {
    if (value.length > 10) {
        document.getElementById("yes_close").removeAttribute("disabled");
    } else {
        document.getElementById("yes_close").setAttribute("disabled", "");
    }
    console.log();
}