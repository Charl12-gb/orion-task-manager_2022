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

        $(document).on('submit', '#user_role_asana', function(e) {
            e.preventDefault();
            console.log('Le clic sur le bouton a été pris en compte');
            var select_user = document.getElementById('userasana').value;
            var select_role = document.getElementById('role_user').value;
            console.log(select_user + ' => ' + select_role);

            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'update_user_role',
                    'id_user': select_user,
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

        $(document).on('submit', '#create_template', function(e) {
            e.preventDefault();
            console.log('Le clic sur le bouton a été pris en compte');
            //var nbre_champs = $('#nbre_champs').val();
            var ton_form = document.forms['create_template'];
            var tes_param = "";
            for (var i = 0; i < ton_form.length; i++)
                tes_param += "'" + ton_form.elements[i].name + "':'" + ton_form.elements[i].value + "',";

            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    'action': 'create_template',
                    tes_param
                },
                success: function(response) {
                    //console.log("La requête est terminée !");
                    console.log(response);
                },
                error: function(errorThrown) {
                    console.log(errorThrown);
                }
            });
            //console.log(tes_param);
        });

        $(document).on('submit', '#create_new_projet', function(e) {
            e.preventDefault();
            console.log('Le clic sur le bouton a été pris en compte');
            //var multi_choix = $('#multichoix').val();
            //var multi_choix = document.getElementById('multichoix').value;
            var multi_choix = $('#multichoix option:selected').toArray().map(item => item.value);
            console.log(multi_choix);
        });
    });

})(jQuery);

function create_champ(i) {
    var i2 = i + 1;
    //console.log(i);
    document.getElementById('leschamps_' + i).innerHTML = '<div class="form-row"><div class="form-group col-md-5"> <input type="hidden" name="nbre_champs" value ="' + (i + 1) + '"> <select name="typechamps[' + i + ']" id="typechamps[' + i + ']" class="form-control"> <option selected>Choose Type Champs ...</option> <option>Text</option> <option>Textarea</option> <option>Email</option> <option>Password</option> <option>File</option> <option>Radio</option> <option>CheckBox</option> </select> </div> <div class="form-group col-md-6"> <input type="text" class="form-control" name="placeholderchamps[' + i + ']" id="placeholderchamps[' + i + ']" placeholder="Placeholder Champs"> </div> <div class="form-group col-md-1"> <button class="btn btn-outline-danger">x</button> </div> </div> <div class="">';
    document.getElementById('leschamps_' + i).innerHTML += (i <= 10) ? '<span id="leschamps_' + i2 + '"><a href="javascript:create_champ(' + i2 + ')"><button type="button" class="btn btn-outline-primary">+</button></a></span> </div>' : '';
}