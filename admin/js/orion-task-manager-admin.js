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
            var nbre_champs = $('#nbre_champs').val();
            var title_template = $('#titletemplaye').val();
            var templatefor = $('#templatefor').val();
            var subTemplate = $('#subTemplate').val();
            var template_form = document.forms['create_template'];

            var form_parametre = {};
            var info_template = {};
            var parametre = {};
            let name1 = "",
                name2 = "",
                value1 = "",
                value2 = "";

            info_template['template_info'] = { title_template: title_template, templatefor: templatefor, subTemplate: subTemplate };
            form_parametre = jQuery.extend(form_parametre, info_template);

            for (var i = 0; i < template_form.length; i++) {
                if (template_form.elements[i].name === 'nbre_champs')
                    nbre_champs = template_form.elements[i].value;
            }

            for (var i = 0; i < nbre_champs; i++) {
                name1 = "typechamps" + i;
                name2 = "placeholderchamps" + i;
                value1 = $('#' + name1).val();
                value2 = $('#' + name2).val();
                parametre[i + 1] = {
                    champtype: value1,
                    placeholderchamp: value2
                };
                form_parametre = jQuery.extend(form_parametre, parametre);
            }
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'create_template',
                    'parametre': form_parametre
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
            //console.log(form_parametre);
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
                    if (response)
                        document.getElementById('add_success').innerHTML = '<div class="alert alert-success" role="alert">New project created successfully</div>';
                    else
                        document.getElementById('add_success').innerHTML = '<div class="alert alert-danger" role="alert">Error occurred during project creation</div>';
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
        });
    });

})(jQuery);

function create_champ(i) {
    var i2 = i + 1;
    //console.log(i);
    document.getElementById('leschamps_' + i).innerHTML = '<div class="form-row"><div class="form-group col-md-5"> <input type="hidden" name="nbre_champs" value ="' + (i + 1) + '"> <select name="typechamps' + i + '" id="typechamps' + i + '" class="form-control"> <option value="">Choose Type Champs ...</option> <option value="text">Text</option> <option value="textarea">Textarea</option> <option value="email">Email</option> <option value="password">Password</option> <option value="select">Select</option> <option value="file">File</option> <option value="date">Date Local</option> <option value="radio">Radio</option> <option value="checkbox">CheckBox</option> </select> </div> <div class="form-group col-md-6"> <input type="text" class="form-control" name="placeholderchamps' + i + '" id="placeholderchamps' + i + '" placeholder="Placeholder Champs"> </div> <div class="form-group col-md-1"> <button class="btn btn-outline-danger">x</button> </div> </div> <div class="">';
    document.getElementById('leschamps_' + i).innerHTML += (i <= 10) ? '<span id="leschamps_' + i2 + '"><a href="javascript:create_champ(' + i2 + ')"><button type="button" class="btn btn-outline-primary">+</button></a></span> </div>' : '';
}