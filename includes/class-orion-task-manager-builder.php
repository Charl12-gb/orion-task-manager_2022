<?php
/**
 * Task_Manager_Builder class will create the page to load the table
 */
class Task_Manager_Builder
{

    /**
     * Menu item will allow us to load the page to display the table
     */
    public static function add_menu_Task_Table_List_page(){
        //Generate Task Admin Page
        add_menu_page( 
            'Task', 
            'Tasks Manager', 
            'manage_options', 
            'o_task_manager', 
            'Task_Manager_Builder::settings_page',
            'dashicons-welcome-write-blog',
            30 ,
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

    public static function settings_page(){
        ?>
        <h3 class="pt-2">
        <?php _e( 'Configuration Task Manager', 'task' ); ?>
        </h3>
            <?php
            $active_tableau = isset( $_GET[ 'set' ] ) ? $_GET[ 'set' ] : 'o_task_manager';
            ?>
        <div class="wrap woocommerce wc_addons_wrap">
            <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=o_task_manager' ) ); ?>" class="nav-tab <?php echo $active_tableau == 'o_task_manager' ? 'nav-tab-active' : ''; ?>"><?php _e( 'TASK', 'task' ); ?></a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=o_task_manager&set=o-worklog' ) ); ?>" class="nav-tab <?php echo $active_tableau == 'o-worklog' ? 'nav-tab-active' : ''; ?>"><?php _e( 'WORKLOG', 'task' ); ?></a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=o_task_manager&set=o-evaluation' ) ); ?>" class="nav-tab <?php echo $active_tableau == 'o-evaluation' ? 'nav-tab-active' : ''; ?>"><?php _e( 'EVALUATION', 'task' ); ?></a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=o_task_manager&set=o-rapport' ) ); ?>" class="nav-tab <?php echo $active_tableau == 'o-rapport' ? 'nav-tab-active' : ''; ?>"><?php _e( 'REPORT', 'task' ); ?></a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=o_task_manager&set=o-performance' ) ); ?>" class="nav-tab <?php echo $active_tableau == 'o-performance' ? 'nav-tab-active' : ''; ?>"><?php _e( 'PERFORMANCE', 'task' ); ?></a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=o_task_manager&set=o-active' ) ); ?>" class="nav-tab <?php echo $active_tableau == 'o-active' ? 'nav-tab-active' : ''; ?>"><?php _e( 'INTEGRATION', 'task' ); ?></a>
            </nav>
            <div class="o_task_manager addons-featured">
        <?php
        if ( $active_tableau == 'o_task_manager' ) {
            Task_Manager_Builder::taches_tab();
        }
        if ( $active_tableau == 'o-worklog' ) {
            ?>
            <div id="worklog_card">
                <?php Task_Manager_Builder::worklog_tab(); ?>
            </div>
            <?php
        }
        if ( $active_tableau == 'o-evaluation' ) {
            Task_Manager_Builder::evaluation_tab();
        }
        if ( $active_tableau == 'o-active' ) {
            Task_Manager_Builder::active_tab();
        }
        if ( $active_tableau == 'o-rapport' ) {
            Task_Manager_Builder::rapport_tab();
        }
        if ( $active_tableau == 'o-performance' ) {
            
        }
    }

    /**
     * Redirect users who arent logged in...
     */
    public static function login_redirect()
    {
        //Current Page
        global $pagenow;

        if (!is_user_logged_in() && (is_page('orion-task') || is_page('task-evaluation') ))
            auth_redirect();
    }

    /**
     * Verification des formulaire wp_nonce
     */
    public static function _taitement_form(){
        if (isset($_POST['verifier_new_task_form']) ) {
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

    /**
     * Créer les projets
     */
    public static function create_new_projet_(){
        $asana = connect_asana();
        if( isset( $_POST['project_id'] ) && !empty( $_POST['project_id'] ) ){
			$project_id = htmlentities( $_POST['project_id'] );
			$post = wp_unslash($_POST);
			$output =  sync_new_project($post, $project_id);
		}else{
			$post = wp_unslash($_POST);
			$project_id = sync_new_project($post);
            $output = $project_id;
		}
        $sections = $_POST['section'];
        foreach( $sections as $section ){
            $name_section = htmlentities( $section['section'] );
            if( ! section_exist( $name_section, $project_id) ){
                $asana->createSection( $project_id, array( "name" => $name_section ) );
                $result = $asana->getData();
                if( $result != null ){
                    $data = array(
                        'id' 		=> $result->gid,
                        'project_id' => $project_id,
                        'section_name'		=> $result->name
                    );
                    // Sauvegarde des sections inexistante dans la bdd
                    save_new_sections($data);
                }
            }
        }

		if( $output ) echo project_tab();
		else echo false;
        wp_die();
    }

    /**
     * Créer un template
     */
    public static function create_template_(){
        if (isset($_POST['updatetempplate_id']) && !empty($_POST['updatetempplate_id'])) {
			$template_id = htmlentities($_POST['updatetempplate_id']);
			$send = array_diff($_POST, array('action' => 'create_template', 'updatetempplate_id' => $template_id));
		} else {
			$send = array_diff($_POST, array('action' => 'create_template'));
			$template_id = '';
		}
		$data = wp_unslash($send);
		$sortir = save_new_templates($data, $template_id);
		if( $sortir ) echo  get_list_template();
		else echo false;
        wp_die();
    }

    /**
     * Optenir le formulaire du template choix par l'utilisateur
     */
    public static function get_template_choose_(){
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

    public static function sent_worklog_mail_( $filemane=null, $type=null, $month = null ){
        if( $type == 'report' ){
	        $m =  date("M", strtotime("previous month"));
            $subject = 'REPORT OF ' . $m;
            $sent_info = unserialize( get_option('_report_sent_info') );
            $to = $sent_info['email_manager'];
        }else{
            $filemane = htmlentities($_POST['link_file']);
            $user_id = htmlentities($_POST['user_id']);
            $name_user = get_userdata($user_id)->display_name;
            $to = get_userdata($user_id)->user_email;  
            $subject = 'WORKLOG ORION';
        }
        
        $sender_info = unserialize(get_option('_sender_mail_info'));      
        // clé aléatoire de limite
        $boundary = md5(uniqid(microtime(), TRUE));
        
        // Headers
        $headers = 'From: "' . $sender_info['sender_name'] . '"<' . $sender_info['sender_email'] . '>'."\r\n";
        $headers .= 'Mime-Version: 1.0'."\r\n";
        $headers .= 'Content-Type: multipart/mixed;boundary='.$boundary."\r\n";
        $headers .= "\r\n";
        
        // Message
        $msg = 'This is a multipart/mixed message.'."\r\n\r\n";
        
        // Texte
        $msg .= '--'.$boundary."\r\n";
        $msg .= 'Content-type:text/plain;charset=utf-8'."\r\n";
        $msg .= 'Content-transfer-encoding:8bit'."\r\n";
        if( $type == 'report' ) $msg .= 'Project Managers evaluation report for the month of .'. $m ."\r\n";
        else $msg .= 'Here is your Worklog.'."\r\n";;
        
        // Pièce jointe
        $file_name = $filemane;
        if (file_exists($file_name))
        {
            $file_type = filetype($file_name);
            $file_size = filesize($file_name);
        
            $handle = fopen($file_name, 'r') or die('File '.$file_name.'can t be open');
            $content = fread($handle, $file_size);
            $content = chunk_split(base64_encode($content));
            $f = fclose($handle);
        
            $msg .= '--'.$boundary."\r\n";
            if( $type == 'report' ) $file_name = 'Report_' . $m . '.xlsx';
            else $file_name = $name_user .'_worklog.xlsx';
            $msg .= 'Content-type:'.$file_type.';name='.$file_name."\r\n";
            $msg .= 'Content-transfer-encoding:base64'."\r\n";
            $msg .= $content."\r\n";
        }
        
        // Fin
        $msg .= '--'.$boundary."\r\n";
        
        // Function mail()
        echo mail($to, $subject, $msg, $headers);
        wp_die();
    }

    /**
     * Function permettant de mettre à jour les informations concernant l'envoi des rapports
     */
    public static function update_sender_mail(){
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
    public static function getOptionTemplate(){
        if( isset( $_POST['nbresubtask'] ) ){
			$id_cham = htmlentities( $_POST['nbresubtask'] );
			echo add_manuel_form( $id_cham );
		}else{
			echo option_select(get_template_titles());
		}
        wp_die();
    }

    /**
     * Liste des collaborateurs d'un projet ajouter au option d'un select
     * Et liste des section ajouter au option d'un select
     */
    public static function addCollaboratorOrSectionForOption(){
        $action = htmlspecialchars($_POST['action']);
        $id_project = htmlentities($_POST['project_id']);
        if ($action == 'get_option_section') {
            if (empty($id_project)) echo '';
            else echo option_select(get_project_section($id_project));
        }else{
            if (empty($id_project)) echo '';
            else echo option_select(array('' => 'Choose ...') + get_project_collaborator($id_project));
            wp_die();
        }
    }

    /**
     * Obtenir le premier choix de l'utilisateur
     * Choix entre utiliser un template ou créer manauellement
     */
    public static function getUserFirstChoose(){
        $type = htmlentities($_POST['type']);
		$istemplate = htmlentities($_POST['istemplate']);
		if ($istemplate == 'yes') echo get_first_choose($type, true);
		else echo get_first_choose($type);
        wp_die();
    }

    /**
     * Fonction permettant de créer une nouvelle tâche
     */
    public static function createNewTask(){
        $send = array_diff($_POST, array('action' => 'create_new_task'));
		$data = wp_unslash($send);
		traite_task_and_save($data);
        wp_die();
    }

    /**
     * Fonction permettant d'obtenir la liste ou le formulaire d'ajouter un templates ou des projets.
     */
    public static function getListOrFormTemplate(){
        $action = htmlspecialchars($_POST['action']);
        $type = htmlentities($_POST['valeur']);
        if ($action == 'get_email_card') {
            if ($type == 'list_email') echo list_email_sending();
            else echo get_email_task_tab();
        }elseif ($action == 'project_card') {
            if( isset( $_POST['update_id'] ) ){
                $id_project = htmlentities($_POST['update_id']);
                echo project_form_add( $id_project );
            }else{
                $type = htmlentities($_POST['valeur']);
                if ($type == 'project_btn_list') echo project_tab();
                else echo project_form_add();
            }
        }else{
            if ($type == 'template_btn_add') echo get_form_template();
            else echo get_list_template();
        }
        wp_die();
    }

    /**
     * Supprimer un template ou catégorie
     */
    public static function deleteTemplateOrCategorie(){
        $action = htmlspecialchars($_POST['action']);
        $id_template = htmlentities($_POST['id_template']);
        if ($action == 'delete_email_') {
            delete_template($id_template, 'email');
            echo list_email_sending();
        }else if ($action == 'delete_categorie_') {
            $id_categorie = htmlentities($_POST['id_categorie']);
            $retour = delete_categories_($id_categorie);
            if ($retour) echo get_categories_();
            else echo 'error';
        }else{
            delete_template($id_template, 'task');
            echo get_list_template();
        }
        wp_die();
    }

    /**
     * Obtenir le template à mettre à jour
     */
    public static function getTemplateHasUpdate(){
        $id_template = htmlentities($_POST['id_template']);
		echo get_form_template($id_template);
        wp_die();
    }

    /**
     * Fonction permettant d'activer ou non le téléchargement de worklog
     */
    public static function autorizedDonwloadWorklog(){
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
    public static function getUserCalendar(){
        $user_id = htmlentities($_POST['id_user']);
		if (empty($user_id)) echo get_task_calendar();
		else echo get_task_calendar($user_id);
        wp_die();
    }

    /**
     * Sauvegarder et mettre à jour les template de mail
     */
    public static function saveAndUpdateTemplateEmail(){
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
    public static function updateEvaluationCriteria(){
        $send = array_diff($_POST, array('action' => 'save_criteria_evaluation'));
		$data = wp_unslash($send);
		update_option('_evaluation_criterias', serialize($data['valeur']));
		echo  create_task_criteria();
        wp_die();
    }

    /**
     * Save les différentes catégories d'une tâche
     */
    public static function saveTaskCategorie(){
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
    public static function getEditTemplateEmailForm(){
        $id_template_mail = htmlentities($_POST['id_template_mail']);
		if (!empty($id_template_mail)) echo get_email_task_tab($id_template_mail);
		else echo get_email_task_tab();
        wp_die();
    }

    /**
     * Mettre à jour les catégorie de tâche
     */
    public static function updateTaskCategorie(){
        $id_categorie = htmlentities($_POST['id_categorie']);
		$valeur = htmlentities($_POST['valeur']);
		save_new_categories($valeur, $id_categorie);
		echo get_categories_();
        wp_die();
    }

    /**
     * Ajout des parametre d'envoi des rapports
     */
    public static function parameterSendTimeReport(){
        if( isset( $_POST['email_manager'] ) && !empty( $_POST['email_manager'] )){
			$email_manager = htmlentities( $_POST['email_manager'] );
			$date_report_sent = htmlentities( $_POST['date_report_sent'] );
			$sent_cp = htmlentities( $_POST['sent_cp'] );
			$array = serialize( array( 'email_manager' => $email_manager, 'send_date' => $date_report_sent, 'sent_cp' => $sent_cp) );
			echo update_option( '_report_sent_info', $array );
			
		}
		if( isset( $_POST['id_project_manager'] ) ){
			$id_project_manager = htmlentities( $_POST['id_project_manager'] );
			echo $output = update_option('_project_manager_id', $id_project_manager);
		}
		if( isset( $_POST['sync_time'] ) ){
			$time = htmlentities( $_POST['sync_time'] );
			echo $output = update_option('_synchronisation_time', $time);
		}else echo false;
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
                    <div class="card-header" id="headingOne">
                        <h5 class="mb-0">
                            <button class="btn btn-link" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                Template
                            </button>
                        </h5>
                        <p class="mt-0 mb-0">Create and modify templates to facilitate the creation of tasks for project managers</p>
                    </div>
                    <div class="card-header" id="headingThree">
                        <h5 class="mb-0">
                            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                Project
                            </button>
                        </h5>
                        <p class="mt-0 mb-0">Create and edit projects from here</p>
                    </div>
                    <div class="card-header" id="headingFour">
                        <h5 class="mb-0">
                            <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                Categories
                            </button>
                        </h5>
                        <p class="mt-0 mb-0">Add more task categories</p>
                    </div>
                </div>
                <div class="col-sm-8 card">
                    <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
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
                    <div id="collapseFour" class="collapse" aria-labelledby="headingFour" data-parent="#accordion">
                        <div class="card-body">
                            <div>
                                <h3><span id="template_label">List Categories</span> </h3>
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
                                            <input class="form-control" type="text" id="sender_name" value="<?= $sender_info['sender_name'] ?>">
                                        </div>
                                        <div class="form-group mb-2">
                                            <label for="staticEmail2" class="sr-only">Email Sender</label>
                                            <input class="form-control-plaintext" type="text" id="sender_email" value="<?= $sender_info['sender_email'] ?>">
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
        $token = get_option('access_token');
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
                                    _e('ASANA ACTIVE', 'task');
                                } else {
                                    $submit = 'SAVE';
                                    _e('Activated ASANA <a href="https://app.asana.com/" target="_blank">https://app.asana.com/</a>', 'task');
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
                    <div id="collapseInter2" class="collapse" aria-labelledby="headingInter2" data-parent="#accordion">
                        <div class="card-body">
                            <span id="add_success_time"></span>
                            <form id="synchronisation_asana" method="post" action="">
                                <label for="synchonisation">Synchronization frequency</label>
                                <div class="input-group mb-3">
                                    <select class="custom-select" id="synchonisation_time">
                                        <option value="daily" <?php if( get_option( '_synchronisation_time' ) == 'daily' ) echo 'selected' ?> >1 time / day</option>
                                        <option value="twicedaily" <?php if( get_option( '_synchronisation_time' ) == 'twicedaily' ) echo 'selected' ?>>2 times / day</option>
                                    </select>
                                    <div class="input-group-append">
                                        <button type="submit" class="input-group-text btn btn-outline-primary" for="synchonisation">UPDATE</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public static function rapport_tab(){
        $sent_info = unserialize( get_option('_report_sent_info') );
        if( $sent_info == null ) { $sent_info['email_manager'] = null; $sent_info['send_date'] = null; $sent_info['sent_cp'] = null;  }
        ?>
    <div class="container-fluid pt-3">
            <div class="row" id="accordion">
                <div class="col-sm-4 card bg-light">
                    <div class="card-header" id="headingEvaluation1">
                        <h5 class="mb-0">
                            <button class="btn btn-link" data-toggle="collapse" data-target="#collapseEvaluationRP" aria-expanded="true" aria-controls="collapseEvaluationRP">
                                Send Report
                            </button>
                        </h5>
                        <p class="mt-0 mb-0">Set the parameters for sending reports</p>
                    </div>
                </div>
                <div class="col-sm-8 card">
                    <div id="collapseEvaluationRP" class="collapse show" aria-labelledby="headingEvaluationRP" data-parent="#accordion">
                        <div class="card-body" id="criteria_evaluation_tab">
                        <span id="add_success_id"></span>
                        <form id="report_send_save" method="post" action="">
                            <div class="form-row">
                                <div class="col">
                                    <label for="">Email Manager</label>
                                    <input type="email" name="email_manager" id="email_manager" class="form-control" placeholder="Email Manager" value="<?= $sent_info['email_manager'] ?>" >
                                </div>
                                <div class="col">
                                    <label for="">Date reports sent</label>
                                    <select class="custom-select" id="date_report_sent">
                                        <option value="last_day_month" <?php if( $sent_info['send_date']  == 'last_day_month') echo 'selected' ?>>Last day of the month</option>
                                        <option value="last_friday_month" <?php if( $sent_info['send_date']  == 'last_friday_month') echo 'selected' ?>>Last friday of the month</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="custom-control custom-checkbox mt-4">
                                    <input type="checkbox" class="sent_cp" <?php if( $sent_info['sent_cp']  == 'on') echo 'checked' ?> id="sent_cp">
                                    <label class="" for="customControlValidation1">Send report to project manager</label>
                                </div>
                            </div><hr>
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

    public static function page_task()
    {
        $post_author = get_current_user_id();
        $download_worklog = get_option('_worklog_authorized');
        if( isset( $_GET['status'] ) ){
            if( $_GET['status'] == 'success' ){
                ?> <div class="alert alert-success" role="alert">Successfuly ! </div> <?php }
           else{ ?> <div class="alert alert-danger" role="alert">Failed operation try again ! </div> <?php }
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
                                            $name_worklog = $date_worklog. '/'.get_userdata(get_current_user_id())->display_name.'_worklog.xlsx';
                                            $url_worklog_file = __DIR__ . '/worklog_evaluation/'.$name_worklog;
                                            if( file_exists( $url_worklog_file ) ){
                                                ?>
                                                <form method="post" id="sent_worklog_mail" name="sent_worklog_mail">
                                                    <input type="hidden" name="link_file" id="link_file" value="<?= $url_worklog_file ?>">
                                                    <input type="hidden" name="user_id" id="user_id" value="<?= get_current_user_id() ?>">
                                                    <button type="submit" class="btn btn-outline-success">Download Worklog</button>
                                                </form>
                                                <?php
                                            }
                                            } ?></span>
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