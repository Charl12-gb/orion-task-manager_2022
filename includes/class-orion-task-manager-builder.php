<?php

/**
 * Task_Manager_Builder class will create the page to load the table
 */
class Task_Manager_Builder
{

    /**
     * Menu item will allow us to load the page to display the table
     */
    public static function add_menu_Task_Table_List_page()
    {
        //Generate Task Admin Page
        add_menu_page(
            'Task',
            'T&P Manager',
            'manage_options',
            'o_task_manager',
            'Task_Manager_Builder::settings_page',
            'dashicons-welcome-write-blog',
            30,
            '',
        );
    }

    /**
     * Short code de la page public
     * 
     * @return void
     */
    public static function orion_task_shortcode()
    {
        $var = wp_nonce_field('orion_task_manager', 'task_manager');
        return Task_Manager_Builder::page_task();
    }

    /**
     * Short code de la page public
     * 
     * @return void
     */
    public static function orion_task_evaluation_shortcode()
    {
        $var = wp_nonce_field('orion_task_manager', 'task_manager');
        return evaluator_page();
    }

    public static function independence_notice()
    {
        $token = get_option('_asana_access_token'); // Access token
        $projetIdCp = get_option('_project_manager_id'); //Project for cp objectif add
        $sender_info = get_option('_sender_mail_info'); // Mail and Name user for send evaluation message
        $sent_info = get_option('_report_sent_info'); // Send report
        $send_subperformance = get_option('_performance_parameters');  // Send Sub performance
        $get_criteria = get_option('_evaluation_criterias'); // Critère
        $emails = get_email_(); // Email
        $tab_templates = get_templates_(); // Template
        $using = get_option('_first_user_plugin');

        if ($using != 'on') {
            if (isset($_REQUEST['page'])) {
                if ($_REQUEST['page'] == 'o_task_manager') {
                    if (($token == null) || ($projetIdCp == null) || ($sender_info == null) || ($sent_info == null) || ($send_subperformance == null) || ($get_criteria == null) || ($emails == null) || ($tab_templates == null)) {
?>
                        <div class="notice notice-warning is-dismissible">
                            <h5 style="text-decoration: underline"><strong class="text-danger">Incomplete configuration</strong></h5>
                            <h6>
                                To use this plugins plainly, be sure to configure:
                                <ol>
                                    <?php
                                    if ($token == null) { ?> <li>The ASANA Access Token (in ASANA access token): <a href="<?php echo esc_url(admin_url('admin.php?page=o_task_manager&set=o-active')); ?>"><?php _e('Here', 'task'); ?></a></li> <?php }
                                                                                                                                                                                                                                                    if ($projetIdCp == null) { ?> <li>Adding the identifier of the project containing the CP objectives for evaluation (in Project Manager) : <a href="<?php echo esc_url(admin_url('admin.php?page=o_task_manager&set=o-evaluation')); ?>"><?php _e('Here', 'task'); ?></a></li> <?php }
                                                                                                                                                                                                                                                                                                                    if (($sender_info == null) || (unserialize($sender_info)['sender_name'] == '')) { ?> <li>Information for sending evaluation emails (in mail template) : <a href="<?php echo esc_url(admin_url('admin.php?page=o_task_manager&set=o-evaluation')); ?>"><?php _e('Here', 'task'); ?></a></li> <?php }
                                                                                                                                                                                                                                                                                                                                        if (($sent_info == null) || (unserialize($sent_info)['email_manager'] == '')) { ?> <li>The send report parameter : <a href="<?php echo esc_url(admin_url('admin.php?page=o_task_manager&set=o-rapport')); ?>"><?php _e('Here', 'task'); ?></a></li> <?php }
                                                                                                                                                                                                                                                                                                                                        if (($send_subperformance == null)) { ?> <li>Performance plan parameters : <a href="<?php echo esc_url(admin_url('admin.php?page=o_task_manager&set=o-performance')); ?>"><?php _e('Here', 'task'); ?></a></li> <?php }
                                                                                                                                                                                                                                                                                                                                        if ($get_criteria == null) { ?> <li>Adding evaluation criteria (in Task Evaluation Criteria) : <a href="<?php echo esc_url(admin_url('admin.php?page=o_task_manager&set=o-evaluation')); ?>"><?php _e('Here', 'task'); ?></a></li> <?php }
                                                                                                                                                                                                                                                                                                                                        if ($emails == null) { ?> <li> Adding mail sending templates (in Mail template) : <a href="<?php echo esc_url(admin_url('admin.php?page=o_task_manager&set=o-evaluation')); ?>"><?php _e('Here', 'task'); ?></a></li> <?php }
                                                                                                                                                                                                                                                                                                                                        if ($tab_templates == null) { ?> <li> Adding templates for creating tasks (in Template) : <a href="<?php echo esc_url(admin_url('admin.php?page=o_task_manager')); ?>"><?php _e('Here', 'task'); ?></a></li> <?php }
                                                                                                                                                                                                                                                        ?>
                                </ol>
                            </h6>
                        </div>
            <?php
                    }
                }
            }
        }
    }

    /**
     * Synchronisation automatique des données
     */
    public static function manuellySync_()
    {
        if ($_POST['valeur'] == 'tag') {
            syncEmployeesFromAsana();
            save_objective_section();
            echo sync_tag();
        }
        if ($_POST['valeur'] == 'projet')
            echo sync_projets();
        if ($_POST['valeur'] == 'objectif')
            echo sync_objectives_month();
        if ($_POST['valeur'] == 'task')
            echo sync_tasks();
        if ($_POST['valeur'] == 'duedate')
            echo sync_duedate_task();
        wp_die();
    }

    /**
     * Définition des premiers paramètre.
     */
    public static function set_first_parameter_plugin_()
    {
        if (isset($_POST['accessToken'])) {
            update_option('_asana_access_token', htmlentities($_POST['accessToken']));
            update_option('_asana_workspace_id', htmlentities($_POST['asana_workspace_id']));
            update_option('_project_manager_id', htmlentities($_POST['projetId']));
        }
        if (isset($_POST['sender_email'])) {
            $sender_name = htmlentities($_POST['sender_name']);
            $sender_email = htmlentities($_POST['sender_email']);
            $variable = serialize(array('sender_name' => $sender_name, 'sender_email' => $sender_email));
            update_option('_sender_mail_info', $variable);

            $email_manager = htmlentities($_POST['email_manager']);
            $date_report_sent = htmlentities($_POST['date_report_sent']);
            $array = serialize(array('email_manager' => $email_manager, 'send_date' => $date_report_sent, 'sent_cp' => ''));
            update_option('_report_sent_info', $array);
        }
        if (isset($_POST['email_rh'])) {
            $email_rh = htmlentities($_POST['email_rh']);
            $nbreSubPeroformance = htmlentities($_POST['nbreSubPeroformance']);
            $moyenne = htmlentities($_POST['moyenne']);
            $array = serialize(array('email_rh' => $email_rh, 'nbreSubPeroformance' => $nbreSubPeroformance, 'moyenne' => $moyenne));
            update_option('_performance_parameters', $array);
        }
    }

    public static function settings_page()
    {
        $using = get_option('_first_user_plugin');
        if ($using != 'on') {
            ?>
            <h3 class="pt-2">
                <?php _e('Configuration T&P Manager', 'task'); ?>
            </h3>
            <?php $active_tableau = isset($_GET['set']) ? $_GET['set'] : 'o_task_manager'; ?>
            <div class="wrap woocommerce wc_addons_wrap">
                <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=o_task_manager')); ?>" class="nav-tab <?php echo $active_tableau == 'o_task_manager' ? 'nav-tab-active' : ''; ?>"><?php _e('TASK', 'task'); ?></a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=o_task_manager&set=o-worklog')); ?>" class="nav-tab <?php echo $active_tableau == 'o-worklog' ? 'nav-tab-active' : ''; ?>"><?php _e('WORKLOG', 'task'); ?></a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=o_task_manager&set=o-evaluation')); ?>" class="nav-tab <?php echo $active_tableau == 'o-evaluation' ? 'nav-tab-active' : ''; ?>"><?php _e('EVALUATION', 'task'); ?></a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=o_task_manager&set=o-rapport')); ?>" class="nav-tab <?php echo $active_tableau == 'o-rapport' ? 'nav-tab-active' : ''; ?>"><?php _e('REPORT', 'task'); ?></a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=o_task_manager&set=o-performance')); ?>" class="nav-tab <?php echo $active_tableau == 'o-performance' ? 'nav-tab-active' : ''; ?>"><?php _e('PERFORMANCE', 'task'); ?></a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=o_task_manager&set=o-active')); ?>" class="nav-tab <?php echo $active_tableau == 'o-active' ? 'nav-tab-active' : ''; ?>"><?php _e('INTEGRATION', 'task'); ?></a>
                </nav>
                <div class="o_task_manager addons-featured">
                    <?php
                    if ($active_tableau == 'o_task_manager') {
                        Task_Manager_Builder::taches_tab();
                    }
                    if ($active_tableau == 'o-worklog') {
                    ?>
                        <div id="worklog_card">
                            <?php Task_Manager_Builder::worklog_tab(); ?>
                        </div>
                <?php
                    }
                    if ($active_tableau == 'o-evaluation') {
                        Task_Manager_Builder::evaluation_tab();
                    }
                    if ($active_tableau == 'o-active') {
                        Task_Manager_Builder::active_tab();
                    }
                    if ($active_tableau == 'o-rapport') {
                        Task_Manager_Builder::rapport_tab();
                    }
                    if ($active_tableau == 'o-performance') {
                        Task_Manager_Builder::performance_tab();
                    }
                } else {
                    include_once('configuration-first-active.php');
                }
            }

            /**
             * Redirect users who arent logged in...
             */
            public static function login_redirect()
            {
                //Current Page
                global $pagenow;

                if (!is_user_logged_in() && (is_page('orion-task') || is_page('task-evaluation')))
                    auth_redirect();
            }

            /**
             * Verification des formulaire wp_nonce
             */
            public static function _taitement_form()
            {
                if (isset($_POST['verifier_new_task_form'])) {
                    if (wp_verify_nonce($_POST['verifier_new_task_form'], 'create_new_task')) {
                        $retour =  traite_form_public($_POST);
                        if (!$retour) {
                            $url = add_query_arg('status', 'error', wp_get_referer());
                            wp_safe_redirect($url);
                            exit();
                        } else {
                            $url = add_query_arg('status', 'success', wp_get_referer());
                            wp_safe_redirect($url);
                            exit();
                        }
                    }
                }
            }

            public static function parameter_plugin_()
            {
                if (isset($_POST['verify_plugin_first_parameter'])) {
                    if (wp_verify_nonce($_POST['verify_plugin_first_parameter'], 'plugin_first_parameter')) {
                        // Create public task page with short code.
                        $task_page = array(
                            'post_title'    => 'T&P Manager',
                            'post_content'  => '
                        <!-- wp:paragraph -->
                        <p>[orion_task]</p>
                        <!-- /wp:paragraph -->',
                            'post_status'   => 'publish',
                            'post_name'   => 'task-manager',
                            'post_author'   => 1,
                            'post_type' => 'page'
                        );
                        wp_insert_post($task_page);

                        // Create public evaluation page with short code.
                        $evaluation_page = array(
                            'post_title'    => 'Evaluation T&P Manager',
                            'post_content'  => '
                        <!-- wp:paragraph -->
                        <p>[task_evaluation]</p>
                        <!-- /wp:paragraph -->',
                            'post_status'   => 'publish',
                            'post_name'   => 'task-evaluation',
                            'post_author'   => 1,
                            'post_type' => 'page'
                        );
                        wp_insert_post($evaluation_page);
                        update_option('_first_user_plugin', 'off');
                    }
                }
            }

            /**
             * Créer les projets
             */
            public static function create_new_projet_()
            {
                $asana = connect_asana();
                if (isset($_POST['project_id']) && !empty($_POST['project_id'])) {
                    $project_id = htmlentities($_POST['project_id']);
                    $post = wp_unslash($_POST);
                    $output =  sync_new_project($post, $project_id);
                } else {
                    $post = wp_unslash($_POST);
                    $project_id = sync_new_project($post);
                    $output = $project_id;
                }
                $sections = $_POST['section'];
                foreach ($sections as $section) {
                    $name_section = htmlentities($section['section']);
                    if (!section_exist($name_section, $project_id)) {
                        $asana->createSection($project_id, array("name" => $name_section));
                        $result = $asana->getData();
                        if ($result != null) {
                            $data = array(
                                'id'         => $result->gid,
                                'project_id' => $project_id,
                                'section_name'        => $result->name
                            );
                            // Sauvegarde des sections inexistante dans la bdd
                            save_new_sections($data);
                        }
                    }
                }

                if ($output) echo project_tab();
                else echo false;
                wp_die();
            }

            /**
             * Créer un template
             */
            public static function create_template_()
            {
                if (isset($_POST['updatetempplate_id']) && !empty($_POST['updatetempplate_id'])) {
                    $template_id = htmlentities($_POST['updatetempplate_id']);
                    $send = array_diff($_POST, array('action' => 'create_template', 'updatetempplate_id' => $template_id));
                } else {
                    $send = array_diff($_POST, array('action' => 'create_template'));
                    $template_id = '';
                }
                $data = wp_unslash($send);
                $sortir = save_new_templates($data, $template_id);
                if ($sortir) echo  get_list_template();
                else echo false;
                wp_die();
            }

            /**
             * Optenir le formulaire du template choisir par l'utilisateur
             */
            public static function get_template_choose_()
            {
                $id_template = htmlentities($_POST['template_id']);
                $istemplate = htmlentities($_POST['istemplate']);
                if (!empty($id_template)) {
                    if ($istemplate == 'yes') echo get_template_form($id_template, true);
                    else echo get_template_form($id_template);
                } else {
                    echo '';
                }
                wp_die();
            }

            /**
             * Envoi des rapports d'évaluation par mois et des plans de performance
             */
            public static function sent_worklog_mail_($filemane, $datas = null, $user_id = null)
            {
                $m =  date("M", strtotime("previous month"));

                $sender_info = unserialize(get_option('_sender_mail_info'));
                $sent_info = unserialize(get_option('_report_sent_info'));
                if ($user_id != null) {
                    $performanceParameter = unserialize(get_option('_performance_parameters'));
                    $to = $performanceParameter['email_rh'];
                    $snbreSubPeroformance = $performanceParameter['nbreSubPeroformance'];
                    $subject = 'Under performance plan';
                    $name_user = get_userdata($user_id)->display_name;

                    if (get_user_meta($user_id, '_nbreSubPeroformance') != null) {
                        $subperformance = get_user_meta($user_id, '_nbreSubPeroformance')[0];
                        $newSubperformance = $subperformance + 1;
                        update_user_meta($user_id, '_nbreSubPeroformance', $newSubperformance);
                    } else {
                        update_user_meta($user_id, '_nbreSubPeroformance', 1);
                        $newSubperformance = 1;
                    }
                } else {
                    if ($datas != null) $subject = 'Performance review of the month ' . $m;
                    else $subject = 'REPORT OF ' . $m;
                    $to = $sent_info['email_manager'];
                }
                $boundary = md5(uniqid(microtime(), TRUE));
                // Headers
                $headers = 'From: "' . $sender_info['sender_name'] . '"<' . $sender_info['sender_email'] . '>' . "\r\n";
                $headers .= 'Mime-Version: 1.0' . "\r\n";
                $headers .= 'Content-Type: multipart/mixed;boundary=' . $boundary . "\r\n";
                $headers .= "\r\n";

                // Message
                $msg = 'This is a multipart/mixed message.' . "\r\n\r\n";
                $msg .= '--' . $boundary . "\r\n";
                $msg .= 'Content-type:text/plain;charset=utf-8' . "\r\n";
                $msg .= 'Content-transfer-encoding:8bit' . "\r\n";
                if ($user_id != null) {
                    if ($newSubperformance == $snbreSubPeroformance) {
                        //Sous plan de performance
                        update_user_meta($user_id, '_nbreSubPeroformance', 0);
                        $msg .= 'Employee performance report.' . "\r\n";
                    } else {
                        //Avertissement
                        $msg .= 'For this month, employee ' . $name_user . ' is under performance plan ' . $newSubperformance . '/' . $snbreSubPeroformance . '. Warning.' . "\r\n";
                    }
                } else if ($datas != null) $msg .= 'Employee performance report.' . "\r\n";
                else $msg .= 'Project manager performance report.' . "\r\n";

                //Plan de performance
                if ($user_id != null) {
                    $file_url = $filemane;
                    if (file_exists($file_url)) {
                        $file_type = filetype($file_url);
                        $file_size = filesize($file_url);

                        $handle = fopen($file_url, 'r') or die('File ' . $file_url . 'can t be open');
                        $content = fread($handle, $file_size);
                        $content = chunk_split(base64_encode($content));
                        $f = fclose($handle);

                        $msg .= '--' . $boundary . "\r\n";
                        $filename = $name_user . '_Worklog.xlsx';
                        $msg .= 'Content-type:' . $file_type . ';name=' . $filename . "\r\n";
                        $msg .= 'Content-transfer-encoding:base64' . "\r\n";
                        $msg .= $content . "\r\n";
                    }
                } else {
                    // Pièce jointe
                    if ($datas != null) {
                        foreach ($datas as $filename => $file_url) {
                            if (file_exists($file_url)) {
                                $file_type = filetype($file_url);
                                $file_size = filesize($file_url);

                                $handle = fopen($file_url, 'r') or die('File ' . $file_url . 'can t be open');
                                $content = fread($handle, $file_size);
                                $content = chunk_split(base64_encode($content));
                                $f = fclose($handle);

                                $msg .= '--' . $boundary . "\r\n";
                                $msg .= 'Content-type:' . $file_type . ';name=' . $filename . "\r\n";
                                $msg .= 'Content-transfer-encoding:base64' . "\r\n";
                                $msg .= $content . "\r\n";
                            }
                        }
                    } else {
                        $file_url = $filemane;
                        if (file_exists($file_url)) {
                            $file_type = filetype($file_url);
                            $file_size = filesize($file_url);

                            $handle = fopen($file_url, 'r') or die('File ' . $file_url . 'can t be open');
                            $content = fread($handle, $file_size);
                            $content = chunk_split(base64_encode($content));
                            $f = fclose($handle);

                            $msg .= '--' . $boundary . "\r\n";
                            $filename = 'Report_' . $m . '.xlsx';
                            $msg .= 'Content-type:' . $file_type . ';name=' . $filename . "\r\n";
                            $msg .= 'Content-transfer-encoding:base64' . "\r\n";
                            $msg .= $content . "\r\n";
                        }
                    }
                }

                // Fin
                $msg .= '--' . $boundary . "\r\n";

                // Function mail()
                mail($to, $subject, $msg, $headers);
            }

            /**
             * Function permettant de mettre à jour les informations concernant l'envoi des rapports
             */
            public static function update_sender_mail()
            {
                if (isset($_POST['sender_name'])) {
                    $sender_name = htmlentities($_POST['sender_name']);
                    $sender_email = htmlentities($_POST['sender_email']);
                    $variable = serialize(array('sender_name' => $sender_name, 'sender_email' => $sender_email));
                    return update_option('_sender_mail_info', $variable);
                } else {
                    $user_id = htmlspecialchars($_POST['id_user']);
                    if (empty($user_id)) {
                        echo '';
                    } else {
                        $user_info = get_userdata($user_id);
                        $user_role = implode(', ', $user_info->roles);
                        echo ucfirst($user_role);
                    }
                }
                wp_die();
            }

            /**
             * Récupérer le nombre de sous tache et la liste des templates
             */
            public static function getOptionTemplate()
            {
                if (isset($_POST['nbresubtask'])) {
                    $id_cham = htmlentities($_POST['nbresubtask']);
                    echo add_manuel_form($id_cham);
                } else {
                    echo option_select(get_template_titles());
                }
                wp_die();
            }

            /**
             * Liste des collaborateurs d'un projet ajouter au option d'un select
             * Et liste des section ajouter au option d'un select
             */
            public static function addCollaboratorOrSectionForOption()
            {
                $action = htmlspecialchars($_POST['action']);
                $id_project = htmlentities($_POST['project_id']);
                if ($action == 'get_option_section') {
                    if (empty($id_project)) echo '';
                    else echo option_select(get_project_section($id_project));
                } else {
                    if (empty($id_project)) echo '';
                    else echo option_select(array('' => 'Choose ...') + get_project_collaborator($id_project));
                    wp_die();
                }
            }

            /**
             * Obtenir le premier choix de l'utilisateur
             * Choix entre utiliser un template ou créer manauellement
             */
            public static function getUserFirstChoose()
            {
                $type = htmlentities($_POST['type']);
                $istemplate = htmlentities($_POST['istemplate']);
                if ($istemplate == 'yes') echo get_first_choose($type, true);
                else echo get_first_choose($type);
                wp_die();
            }

            /**
             * Fonction permettant de créer une nouvelle tâche
             */
            public static function createNewTask()
            {
                $send = array_diff($_POST, array('action' => 'create_new_task'));
                $data = wp_unslash($send);
                traite_task_and_save($data);
                wp_die();
            }

            /**
             * Fonction permettant d'obtenir la liste ou le formulaire d'ajouter un templates ou des projets.
             */
            public static function getListOrFormTemplate()
            {
                $action = htmlspecialchars($_POST['action']);
                $type = htmlentities($_POST['valeur']);
                if ($action == 'get_email_card') {
                    if ($type == 'list_email') echo list_email_sending();
                    else echo get_email_task_tab();
                } elseif ($action == 'project_card') {
                    if (isset($_POST['update_id'])) {
                        $id_project = htmlentities($_POST['update_id']);
                        echo project_form_add($id_project);
                    } else {
                        $type = htmlentities($_POST['valeur']);
                        if ($type == 'project_btn_list') echo project_tab();
                        else echo project_form_add();
                    }
                } else {
                    if ($type == 'template_btn_add') echo get_form_template();
                    else echo get_list_template();
                }
                wp_die();
            }

            /**
             * Supprimer un template ou catégorie
             */
            public static function deleteTemplateOrCategorie()
            {
                $action = htmlspecialchars($_POST['action']);
                $id_template = htmlentities($_POST['id_template']);
                if ($action == 'delete_email_') {
                    delete_template($id_template, 'email');
                    echo list_email_sending();
                } else if ($action == 'delete_categorie_') {
                    $id_categorie = htmlentities($_POST['id_categorie']);
                    $retour = delete_categories_($id_categorie);
                    if ($retour) echo get_categories_();
                    else echo 'error';
                } else {
                    delete_template($id_template, 'task');
                    echo get_list_template();
                }
                wp_die();
            }

            /**
             * Obtenir le template à mettre à jour
             */
            public static function getTemplateHasUpdate()
            {
                $id_template = htmlentities($_POST['id_template']);
                echo get_form_template($id_template);
                wp_die();
            }

            /**
             * Fonction permettant d'activer ou non le téléchargement de worklog
             */
            public static function autorizedDonwloadWorklog()
            {
                $worklog_status  = get_option('_worklog_authorized');
                if (!isset($worklog_status)) {
                    $new_status = 'true';
                } else {
                    if ($worklog_status == 'true') {
                        $new_status = 'false';
                    } else {
                        $new_status = 'true';
                    }
                }
                update_option('_worklog_authorized', $new_status);
                echo Task_Manager_Builder::worklog_tab();
                wp_die();
            }

            /**
             * Fonction permettant d'obtenir le calendrier soit de tout le monde ou d'un utilisateur
             */
            public static function getUserCalendar()
            {
                $user_id = htmlentities($_POST['id_user']);
                if (empty($user_id)) echo get_task_calendar();
                else echo get_task_calendar($user_id);
                wp_die();
            }

            /**
             * Sauvegarder et mettre à jour les template de mail
             */
            public static function saveAndUpdateTemplateEmail()
            {
                $update = htmlentities($_POST['update']);
                $id_template_email = htmlentities($_POST['id_template']);
                $send = array_diff($_POST, array('action' => 'save_mail_form', 'update' => $update, 'id_template' => $id_template_email));
                $data = wp_unslash($send);
                if ($update  === 'true') $ok = save_new_mail_form($data, $id_template_email);
                else $ok = save_new_mail_form($data);
                if ($ok) echo list_email_sending();
                else echo 'false';
                wp_die();
            }

            /**
             * Mettre à jour les critère d'évaluation 
             */
            public static function updateEvaluationCriteria()
            {
                $send = array_diff($_POST, array('action' => 'save_criteria_evaluation'));
                $data = wp_unslash($send);
                update_option('_evaluation_criterias', serialize($data['valeur']));
                echo  create_task_criteria();
                wp_die();
            }

            /**
             * Save les différentes catégories d'une tâche
             */
            public static function saveTaskCategorie()
            {
                if (isset($_POST['get_categorie'])) {
                    echo option_select(get_categorie_format());
                } else {
                    $send = array_diff($_POST, array('action' => 'save_categories'));
                    $data = wp_unslash($send);
                    save_new_categories($data);
                    echo get_categories_();
                }
                wp_die();
            }

            /**
             * Formulaire de modification d'un template de mail
             */
            public static function getEditTemplateEmailForm()
            {
                $id_template_mail = htmlentities($_POST['id_template_mail']);
                if (!empty($id_template_mail)) echo get_email_task_tab($id_template_mail);
                else echo get_email_task_tab();
                wp_die();
            }

            /**
             * Mettre à jour les catégorie de tâche
             */
            public static function updateTaskCategorie()
            {
                $id_categorie = htmlentities($_POST['id_categorie']);
                $valeur = htmlentities($_POST['valeur']);
                save_new_categories($valeur, $id_categorie);
                echo get_categories_();
                wp_die();
            }

            /**
             * Ajout des parametre d'envoi des rapports
             */
            public static function parameterSendTimeReport()
            {
                if (isset($_POST['email_manager']) && !empty($_POST['email_manager'])) {
                    $email_manager = htmlentities($_POST['email_manager']);
                    $date_report_sent = htmlentities($_POST['date_report_sent']);
                    $sent_cp = htmlentities($_POST['sent_cp']);
                    $array = serialize(array('email_manager' => $email_manager, 'send_date' => $date_report_sent, 'sent_cp' => $sent_cp));
                    echo update_option('_report_sent_info', $array);
                }
                if (isset($_POST['email_rh']) && !empty($_POST['email_rh'])) {
                    $email_rh = htmlentities($_POST['email_rh']);
                    $nbreSubPeroformance = htmlentities($_POST['nbreSubPeroformance']);
                    $moyenne = htmlentities($_POST['moyenne']);
                    $array = serialize(array('email_rh' => $email_rh, 'nbreSubPeroformance' => $nbreSubPeroformance, 'moyenne' => $moyenne));
                    echo update_option('_performance_parameters', $array);
                }
                if (isset($_POST['id_project_manager']) && !empty($_POST['id_project_manager'])) {
                    $id_project_manager = htmlentities($_POST['id_project_manager']);
                    echo $output = update_option('_project_manager_id', $id_project_manager);
                }
                if (isset($_POST['sync_time'])) {
                    $time = htmlentities($_POST['sync_time']);
                    echo update_option('_synchronisation_time', $time);
                }
                if (isset($_POST['all_sync'])) {
                    if( $_POST['all_sync'] == 'categorie' ){
                        syncEmployeesFromAsana();
                        save_objective_section();
                        $output1 = sync_tag();
                        if( $output1 == 'tag' ) echo true; else echo false;
                    }
                    if( $_POST['all_sync'] == 'project' ){
                        $output2 = sync_projets();
                        if( $output2 == 'projet' ) echo true; else echo false;
                    }
                    if( $_POST['all_sync'] == 'objective' ){
                        $output3 = sync_objectives_month();
                        if( $output3 == 'objectif' ) echo true; else echo false;
                    }
                    if( $_POST['all_sync'] == 'task' ){
                        $output4 = sync_tasks();
                        if( $output4 == 'task' ) echo true; else echo false;
                    }
                    if( $_POST['all_sync'] == 'duedate' ){
                        $output5 = sync_duedate_task();
                        if( $output5 == 'duedate' ) echo true; else echo false;
                    }
                }

                if (isset($_POST['asana_workspace_id'])) {
                    $asana_workspace_id = htmlentities($_POST['asana_workspace_id']);
                    $out =  update_option('_asana_workspace_id', $asana_workspace_id);
                    $out1 =  update_option('_project_manager_id', htmlentities($_POST['id_project_manager']));
                    $out3 = delete_all();
                    if( ($out == true) and ($out1 == true) and ( $out3 == true ) ) echo true; else echo false;
                } else echo false;
                wp_die();
            }

            /**
             * Backend add param task
             */
            public static function taches_tab()
            {
                ?>
                <div class="container-fluid pt-3">
                    <div class="row" id="accordion">
                        <div class="col-sm-4 card bg-light">
                            <div class="card-header" id="headingFour">
                                <h5 class="mb-0">
                                    <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                        Categories
                                    </button>
                                </h5>
                                <p class="mt-0 mb-0">Add more task categories</p>
                            </div>
                            <div class="card-header" id="headingThree">
                                <h5 class="mb-0">
                                    <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        Project
                                    </button>
                                </h5>
                                <p class="mt-0 mb-0">Create and edit projects from here</p>
                            </div>
                            <div class="card-header" id="headingOne">
                                <h5 class="mb-0">
                                    <button class="btn btn-link" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        Template
                                    </button>
                                </h5>
                                <p class="mt-0 mb-0">Create and modify templates to facilitate the creation of tasks for project managers</p>
                            </div>
                        </div>
                        <div class="col-sm-8 card">
                            <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                                <div class="card-body">
                                    <div>
                                        <div id="add_success"></div>
                                        <form action="" method="post" id="create_template">
                                        </form>
                                        <div id="template_card">
                                            <?= get_list_template();
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="collapseFour" class="collapse show" aria-labelledby="headingFour" data-parent="#accordion">
                                <div class="card-body">
                                    <div>
                                        <h3><span id="template_label">List Categories</span> </h3>
                                        <span>The default categories are: implementation, revue, test and integration</span>
                                        <div id="add_success_categories"></div>
                                        <hr>
                                        <div id="categories_card">
                                            <?= get_categories_() ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordion">
                                <div id="add_success1"></div>
                                <div class="card-body" id="project_card">
                                    <?= project_tab() ?>
                                </div>
                            </div>
                            <div id="collapseFour1" class="collapse" aria-labelledby="headingFour1" data-parent="#accordion">
                                <div class="card-body">
                                    <?= create_new_project() ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            }

            public static function worklog_tab()
            {
                $value = get_option('_worklog_authorized');
                if (!isset($value)) $active = false;
                else {
                    if ($value == 'true') $active = true;
                    else $active = false;
                }
            ?>
                <div class="container-fluid pt-3">
                    <div class="card">
                        <h5 class="card-header">Enable Worklog</h5>
                        <div class="card-body">
                            <div class="custom-control custom-checkbox my-1 mr-sm-2 worklog_authorized">
                                <input type="checkbox" <?php if ($active) echo 'checked'; ?> class="custom-control-input" id="id_worklog_authorized">
                                <label class="custom-control-label <?php if ($active) echo 'text-success';
                                                                    else echo 'text-danger'; ?>" for="id_worklog_authorized">Check to allow downloading of the worklog file</label>
                                <?php
                                if ($active) {
                                ?>
                                    <div class="text-success">Download permission accept</div>
                                <?php
                                } else {
                                ?>
                                    <div class="text-danger">Download permission denied</div>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            }

            public static function evaluation_tab()
            {
                $sender_info = unserialize(get_option('_sender_mail_info'));
            ?>
                <div class="container-fluid pt-3">
                    <div class="row" id="accordion">
                        <div class="col-sm-4 card bg-light">
                            <div class="card-header" id="headingEvaluation1">
                                <h5 class="mb-0">
                                    <button class="btn btn-link" data-toggle="collapse" data-target="#collapseEvaluation1" aria-expanded="true" aria-controls="collapseEvaluation1">
                                        Task evaluation criteria
                                    </button>
                                </h5>
                                <p class="mt-0 mb-0">Add the evaluation criteria</p>
                            </div>
                            <div class="card-header" id="headingEvaluation2">
                                <h5 class="mb-0">
                                    <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseEvaluation2" aria-expanded="false" aria-controls="collapseEvaluation2">
                                        Mail Template
                                    </button>
                                </h5>
                                <p class="mt-0 mb-0">Create email submit form templates</p>
                            </div>
                            <div class="card-header" id="headingEvaluationPM">
                                <h5 class="mb-0">
                                    <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseEvaluationPM" aria-expanded="false" aria-controls="collapseEvaluationPM">
                                        Project Manager
                                    </button>
                                </h5>
                                <p class="mt-0 mb-0">Define the new project manager section</p>
                            </div>
                            <div class="card-header" id="headingEvaluation">
                                <p class="mt-0 mb-0">Create an evaluation page to add the following short code: [task_evaluation]</p>
                            </div>
                        </div>
                        <div class="col-sm-8 card">
                            <div id="collapseEvaluation1" class="collapse show" aria-labelledby="headingEvaluation1" data-parent="#accordion">
                                <div class="card-body" id="criteria_evaluation_tab">
                                    <?= create_task_criteria(); ?>
                                </div>
                            </div>
                            <div id="collapseEvaluationPM" class="collapse" aria-labelledby="headingEvaluationPM" data-parent="#accordion">
                                <div class="card-body" id="criteria_evaluation_tab">
                                    <?= get_project_manager_tab(); ?>
                                </div>
                            </div>
                            <div id="collapseEvaluation2" class="collapse" aria-labelledby="headingEvaluation2" data-parent="#accordion">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <span class="add_success" id="add_success"></span>
                                        <div class="container row">
                                            <h5 class="pb-2 pl-3">Email Sending Information</h5>
                                            <form class="form-inline" id="add_sender_info" method="POST" action="">
                                                <div class="form-group mx-sm-3 mb-2">
                                                    <label for="inputPassword2" class="sr-only">Name Sender</label>
                                                    <input class="form-control" type="text" id="sender_name" placeholder="Name Sender" value="<?php if ($sender_info != null) echo $sender_info['sender_name']; ?>" required>
                                                </div>
                                                <div class="form-group mb-2">
                                                    <label for="staticEmail2" class="sr-only">Email Sender</label>
                                                    <input class="form-control-plaintext" type="text" id="sender_email" placeholder="Email Sender" value="<?php if ($sender_info != null) echo $sender_info['sender_email']; ?>" required>
                                                </div>
                                                <button type="submit" class="btn btn-outline-primary mb-2">UPDATE</button>
                                            </form>
                                        </div>
                                    </div>
                                    <h5 class="card-header btn_evaluation_add" id="btn_evaluation_add">Mail Template <span class="btn btn-outline-success btn_emails" id="new_email">New Email Template</span> </h5>
                                    <div class="card-body" id="evaluator_tab">
                                        <?= list_email_sending(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            }

            public static function active_tab()
            {
                $token = get_option('_asana_access_token');
            ?>
                <div class="container-fluid pt-3">
                    <div class="row" id="accordion">
                        <div class="col-sm-4 card bg-light">
                            <div class="card-header" id="headingInter1">
                                <h5 class="mb-0">
                                    <button class="btn btn-link" data-toggle="collapse" data-target="#collapseInter1" aria-expanded="true" aria-controls="collapseInter1">
                                        ASANA access token
                                    </button>
                                </h5>
                                <p class="mt-0 mb-0">Add asana access token</p>
                            </div>
                            <div class="card-header" id="headingInter3">
                                <h5 class="mb-0">
                                    <button class="btn btn-link" data-toggle="collapse" data-target="#collapseInter3" aria-expanded="true" aria-controls="collapseInter3">
                                        Workspace
                                    </button>
                                </h5>
                                <p class="mt-0 mb-0">Add asana workspace id</p>
                            </div>
                            <div class="card-header" id="headingInter2">
                                <h5 class="mb-0">
                                    <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseInter2" aria-expanded="false" aria-controls="collapseInter2">
                                        Synchronization
                                    </button>
                                </h5>
                                <p class="mt-0 mb-0">Set Task Sync Frequency</p>
                            </div>
                        </div>
                        <div class="col-sm-8 card">
                            <div id="collapseInter1" class="collapse show" aria-labelledby="headingInter1" data-parent="#accordion">
                                <div class="card-body">
                                    <div class="container pt-2">
                                        <?php
                                        if ($token != '') {
                                            $submit = 'UPDATE';
                                            $token = 'XXXX-XXXX-XXXX-XXX';
                                        ?><div class="alert alert-success">ASANA ACTIVE <input type="checkbox" checked readonly></div><?php
                                                                                                                                } else {
                                                                                                                                    $submit = 'SAVE';
                                                                                                                                    ?><div class="alert alert-danger">Activated ASANA <a href="https://app.asana.com/" target="_blank">https://app.asana.com/</a></div><?php
                                                                                                                                                                    }
                                                                                                                                                                        ?>
                                    </div>
                                    <div class='block-form container pt-2'>
                                        <?php
                                        $begin = array(
                                            'type' => 'sectionbegin',
                                            'id' => 'task-datasource-container',
                                        );

                                        $tokens = array(
                                            'title' => __('ASANA access token', 'task'),
                                            'name' => 'tokens',
                                            'type' => 'text',
                                            'default' => $token,
                                        );

                                        $btn = array(
                                            'title' => __($submit, 'task'),
                                            'type' => 'button',
                                            'id'  => 'submit',
                                            'default' => '',
                                            'class' => ' btn btn-outline-primary'
                                        );

                                        $end = array('type' => 'sectionend');
                                        $details = array(
                                            $begin,
                                            $tokens,
                                            $btn,
                                            $end,
                                        );
                                        ?>
                                        <form method="post" action="">
                                            <?php
                                            echo o_admin_fields($details);
                                            ?>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div id="collapseInter3" class="collapse" aria-labelledby="headingInter3" data-parent="#accordion">
                                <div class="card-body">
                                    <span id="add_workspace_asana"></span>
                                    <form id="workspace_asana" class="config_asana" method="post" action="">
                                        <div class="form-row mt-4 mb-2">
                                            <div class="col">
                                                <label><strong>Asana Workspace Id</strong></label>
                                                <input class="form-control" type="text" name="asana_workspace_id" id="asana_workspace_id" placeholder="Asana Workspace Id" value="<?php if (get_option('_asana_workspace_id') != null) echo get_option('_asana_workspace_id'); ?>">
                                                <input type="hidden" name="asana_workspace_id_old" id="asana_workspace_id_old" value="<?= get_option('_asana_workspace_id')?>">
                                            </div>
                                        </div>

                                        <!-- -------------------------------------Modal start------------------------------------------------------ -->
                                        <!-- Button trigger modal -->
                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#staticBackdrop">
                                            UPDATE
                                        </button>

                                        <!-- Modal -->
                                        <div class="modal fade" id="staticBackdrop" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title text-warning" id="title_change">WARNING</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div id="card_warning1"></div>
                                                        <div id="card_warning">
                                                            <h6>Updating the access token results in deletion of all data like:</h6>
                                                            <h6 class="pl-4"><input type="checkbox" disabled>The categories,</h6>
                                                            <h6 class="pl-4"><input type="checkbox" disabled>Projects,</h6>
                                                            <h6 class="pl-4"><input type="checkbox" disabled>The sections</h6>
                                                            <h6 class="pl-4"><input type="checkbox" disabled>The goals and</h6>
                                                            <h6 class="pl-4"><input type="checkbox" disabled>Tasks and sub-tasks</h6><hr>
                                                            <h6>
                                                                ASANA Project ID for CP evaluation (<span class="text-danger">required</span>) <br>
                                                                <input class="form-control" onkeyup="myfunction(this.value)" type="text" name="id_project_manager" id="id_project_manager" placeholder="Project Id">
                                                                <small id="emailHelp" class="form-text text-muted">Enter the ASANA ID of the project where the objectives will be saved</small>
                                                            </h6>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <span class="text-center" id="msg_change"><strong>Are you sure you want to update the access token?</strong></span><br>
                                                        <button type="button" class="btn btn-outline-primary" id="close_btn" data-dismiss="modal"> ~ No ~ </button>
                                                        <button type="submit" class="btn btn-outline-danger" disabled id="yes_close" for="synchonisation"> ~ Yes ~ </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- -------------------------------------Modal end------------------------------------------------------ -->
                                    </form>
                                </div>
                            </div>
                            <div id="collapseInter2" class="collapse" aria-labelledby="headingInter2" data-parent="#accordion">
                                <div class="card-body">
                                    <span id="add_synchronisation_asana"></span>
                                    <form id="synchronisation_asana" class="config_asana" method="post" action="">
                                        <label for="synchonisation"><strong>Synchronization frequency</strong></label>
                                        <div class="form-row">
                                            <div class="col-sm-6">
                                                <select class="custom-select" id="synchonisation_time">
                                                    <option value="daily" <?php if (get_option('_synchronisation_time') == 'daily') echo 'selected' ?>>1 time / day</option>
                                                    <option value="twicedaily" <?php if (get_option('_synchronisation_time') == 'twicedaily') echo 'selected' ?>>2 times / day</option>
                                                </select>
                                            </div>
                                            <button type="submit" class="input-group-text btn btn-outline-primary" for="synchonisation">UPDATE</button>
                                        </div>
                                    </form>
                                    <hr>
                                    <span><button class="btn btn-outline-primary syncNow">Sync Now</button> <strong class="btn" title="Start data synchronisation manually">?</strong><span><br>
                                            <span id="msg_manuel_syn"></span>
                                            <hr>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            }

            public static function rapport_tab()
            {
                $sent_info = unserialize(get_option('_report_sent_info'));
                if ($sent_info == null) {
                    $sent_info['email_manager'] = null;
                    $sent_info['send_date'] = null;
                    $sent_info['sent_cp'] = null;
                }
            ?>
                <div class="container-fluid pt-3">
                    <div class="row" id="accordion">
                        <div class="col-sm-4 card bg-light">
                            <div class="card-header" id="headingEvaluation1">
                                <h5 class="mb-0">
                                    <button class="btn btn-link" data-toggle="collapse" data-target="#" aria-expanded="true" aria-controls="">
                                        Send Report
                                    </button>
                                </h5>
                                <p class="mt-0 mb-0">Set the parameters for sending reports</p>
                            </div>
                        </div>
                        <div class="col-sm-8 card">
                            <div id="" class="collapse show" aria-labelledby="" data-parent="#accordion">
                                <div class="card-body" id="">
                                    <span id="add_success_id"></span>
                                    <form id="report_send_save" class="reportPerformance" method="post" action="">
                                        <div class="form-row">
                                            <div class="col">
                                                <label for="">Email Manager</label>
                                                <input type="email" name="email_manager" id="email_manager" class="form-control" placeholder="Email Manager" value="<?= $sent_info['email_manager'] ?>">
                                            </div>
                                            <div class="col">
                                                <label for="">Date reports sent</label>
                                                <select class="custom-select" id="date_report_sent">
                                                    <option value="last_day_month" <?php if ($sent_info['send_date']  == 'last_day_month') echo 'selected' ?>>Last day of the month</option>
                                                    <option value="last_friday_month" <?php if ($sent_info['send_date']  == 'last_friday_month') echo 'selected' ?>>Last friday of the month</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="custom-control custom-checkbox mt-4">
                                                <input type="checkbox" class="sent_cp" <?php if ($sent_info['sent_cp']  == 'on') echo 'checked' ?> id="sent_cp">
                                                <label class="" for="customControlValidation1">Send report to project manager</label>
                                            </div>
                                        </div>
                                        <hr>
                                        <button class="btn btn-outline-primary mt-3" type="submit">Submit</button>
                                    </form>
                                    <hr>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            }

            public static function performance_tab()
            {
                $sent_info = unserialize(get_option('_performance_parameters'));
                if ($sent_info == null) {
                    $sent_info['email_rh'] = null;
                    $sent_info['nbreSubPeroformance'] = null;
                    $sent_info['moyenne'] = null;
                }
            ?>
                <div class="container-fluid pt-3">
                    <div class="row" id="accordion">
                        <div class="col-sm-4 card bg-light">
                            <div class="card-header" id="headingEvaluation1">
                                <h5 class="mb-0">
                                    <button class="btn btn-link" data-toggle="" data-target="#" aria-expanded="true" aria-controls="">
                                        Performance Parameter
                                    </button>
                                </h5>
                                <p class="mt-0 mb-0">Set performance parameters</p>
                            </div>
                        </div>
                        <div class="col-sm-8 card">
                            <div id="" class="collapse show" aria-labelledby="" data-parent="#accordion">
                                <div class="card-body" id="">
                                    <span id="add_success_id"></span>
                                    <form id="performance_parameter" class="reportPerformance" method="post" action="">
                                        <div class="form-row">
                                            <div class="col">
                                                <label for="">Human resources department email</label>
                                                <input type="email" name="email_rh" id="email_rh" class="form-control" placeholder="Human resources department email" value="<?= $sent_info['email_rh'] ?>" required>
                                            </div>
                                        </div>
                                        <div class="form-row mt-4">
                                            <div class="col">
                                                <label for="">Total allowed underperformance <strong data-toggle="tooltip" data-placement="top" title="The total number of times an employee must be underperformed during the year.">?</strong></label>
                                                <input type="number" min="3" max="6" name="nbreSubPeroformance" id="nbreSubPeroformance" class="form-control" placeholder="Number of underperformance" value="<?= $sent_info['nbreSubPeroformance'] ?>" required>
                                            </div>
                                            <div class="col">
                                                <label for="">Minimum average</label>
                                                <input type="number" min="50" max="100" name="moyenne" id="moyenne" class="form-control" placeholder="Minimum average" value="<?= $sent_info['moyenne'] ?>" required>
                                            </div>
                                        </div>
                                        <hr>
                                        <button class="btn btn-outline-primary" type="submit">Submit</button>
                                    </form>
                                    <hr>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }

            public static function page_task()
            {
                $post_author = get_current_user_id();
                $download_worklog = get_option('_worklog_authorized');
                if (isset($_GET['status'])) {
                    if ($_GET['status'] == 'success') {
                ?> <div class="alert alert-success" role="alert">Successfuly ! </div> <?php } else { ?> <div class="alert alert-danger" role="alert">Failed operation try again ! </div> <?php }
                                                                                                }
                                                                                                if ($post_author != 0) {
                                                                                                        ?>
                    <span id="worklog_msg"></span>
                    <div class="container card">
                        <div class="row text-center card-header">
                            <div class="col-sm-4"><a class="button text-dark" data-toggle="collapse" data-target="#collapse5" aria-expanded="false" aria-controls="collapse5" href="" class="nav-tab">
                                    <h5><?php _e('Calendar', 'task'); ?>
                                    </h5>
                                </a>
                            </div>
                            <div class="col-sm-4">
                                <a class="button text-dark" data-toggle="collapse" data-target="#collapse1" aria-expanded="true" aria-controls="collapse1" href="" class="nav-tab">
                                    <h5><?php _e('Task Lists', 'task'); ?> </h5>
                                </a>
                            </div>
                            <?php if (is_project_manager() != null) {
                            ?>
                                <div class="col-sm-4">
                                    <a class="button text-dark" data-toggle="collapse" data-target="#collapseOb" aria-expanded="true" aria-controls="collapseOb" href="" class="nav-tab">
                                        <h5><?php _e('Goals', 'task'); ?> </h5>
                                    </a>
                                </div>
                            <?php
                                                                                                    }
                            ?>
                        </div>
                        <div id="accordion" class="card-body">
                            <div id="collapse1" class="collapse" aria-labelledby="heading1" data-parent="#accordion">
                                <div>
                                    <div class="row">
                                        <div class="col-sm-6" style="text-align:left;">
                                            <h3>
                                                Task Lists
                                            </h3>
                                            <p>List of projects on which you collaborate. <br> Click on one of the projects, you see your tasks</p>
                                        </div>
                                        <div class="col-sm-6" style="text-align:right;">
                                            <span><?php if ($download_worklog == 'true') {
                                                                                                        $nxtm = strtotime("previous month");
                                                                                                        $date_worklog = date("M-Y", $nxtm);


                                                                                                        $upload = wp_upload_dir();
                                                                                                        $worklog_evaluation = $upload['basedir'];
                                                                                                        $name_worklog = $date_worklog . '/' . get_userdata(get_current_user_id())->display_name . '_worklog.xlsx';
                                                                                                        $worklog_evaluation_file = $worklog_evaluation . '/worklog_evaluation/' . $name_worklog;

                                                                                                        if (file_exists($worklog_evaluation_file)) {
                                                    ?>
                                                        <form method="post" action="" id="sent_worklog_mail">
                                                            <input type="hidden" name="link_file" id="link_file" value="<?= $worklog_evaluation_file ?>">
                                                            <input type="hidden" name="file_name" id="file_name" value="<?= get_userdata(get_current_user_id())->display_name . '_worklog.xlsx' ?>">
                                                            <button type="submit" class="btn btn-outline-success">Download Worklog</button>
                                                        </form>
                                                <?php
                                                                                                        }
                                                                                                    } ?>
                                            </span>
                                            <span>
                                                <?php if (is_project_manager() != null) {
                                                ?>
                                                    <button class="btn btn-outline-primary text-dark" data-toggle="collapse" data-target="#collapse3" aria-expanded="false" aria-controls="collapse3" href="" class="nav-tab">
                                                        <?php _e('Create a Task', 'task'); ?></button>
                                                <?php
                                                                                                    }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php
                                                                                                    get_user_task();
                                    ?>
                                </div>
                            </div>
                            <div id="collapse3" class="collapse" aria-labelledby="heading3" data-parent="#accordion">
                                <div>
                                    <h3>Create a Task</h3>
                                    <?php
                                                                                                    if (is_project_manager() != null) {
                                                                                                        add_task_form();
                                                                                                    }
                                    ?>
                                </div>
                            </div>
                            <div id="collapseOb" class="collapse" aria-labelledby="headingOb" data-parent="#accordion">
                                <div>
                                    <?php
                                                                                                    if (is_project_manager() != null) {
                                                                                                        objective_tab();
                                                                                                    }
                                    ?>
                                </div>
                            </div>
                            <div id="collapse5" class="collapse show" aria-labelledby="heading5" data-parent="#accordion">
                                <div>
                                    <div class="row">
                                        <div class="col-sm-6" style="text-align:left;">
                                            <h3>Calendar</h3>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="user_calendar">Filter calendar by name</label>
                                        <select id="user_calendar" <?php if (is_project_manager() == null) echo 'readonly'; ?> name="user_calendar" class="form-control user_calendar">
                                            <?php if (is_project_manager() != null) {
                                            ?>
                                                <option value="">Everyone</option>
                                                <?= option_select(get_all_users('name')) ?>
                                            <?php
                                                                                                    } else {
                                            ?>
                                                <option selected><?= get_userdata(get_current_user_id())->display_name ?></option>
                                            <?php
                                                                                                    }
                                            ?>
                                        </select>
                                    </div>
                                    <div id="calendar_card">
                                        <?php
                                                                                                    if (is_project_manager() != null)
                                                                                                        get_task_calendar();
                                                                                                    else
                                                                                                        get_task_calendar(get_current_user_id());
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
        <?php
                                                                                                }
                                                                                            }
                                                                                        }
