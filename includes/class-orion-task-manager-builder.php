<?php
if(is_admin())
{
    new Task_Manager_Builder();
}

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
            taches_tab();
        }
        if ( $active_tableau == 'o-worklog' ) {
            ?>
            <div id="worklog_card">
                <?php worklog_tab(); ?>
            </div>
            <?php
        }
        if ( $active_tableau == 'o-evaluation' ) {
            evaluation_tab();
        }
        if ( $active_tableau == 'o-active' ) {
            active_tab();
        }
        if ( $active_tableau == 'o-rapport' ) {
            rapport_tab();
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
		$sortir = save_new_templates($template_id, $data);
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
}