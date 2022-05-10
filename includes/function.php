<?php

if (isset($_POST['tokens']) && !empty($_POST['tokens'])) {
	$data_post   = wp_unslash($_POST['tokens']);
	update_option('access_token', $data_post);
}

function connect_asana()
{
	// See class comments and Asana API for full info
	$token_asana  = get_option('access_token');
	$asana = new Asana(array('personalAccessToken' => $token_asana));
	// Create a personal access token in Asana or use OAuth

	return $asana;
}

function get_asana_projet()
{
	// See class comments and Asana API for full info
	$asana = connect_asana();

	$asana->getProjects();
	$arrayProjet = array();
	if ($asana->getData() != null) {
		foreach ($asana->getData() as $project) {
			$arrayProjet = $arrayProjet + array($project->gid => $project->name);
		}
	}
	return $arrayProjet;
}

function getWorkspace_asana()
{
	// See class comments and Asana API for full info
	$asana = connect_asana();

	// Get all workspaces
	$asana->getWorkspaces();
	$arryWorkspaces = array();
	foreach ($asana->getData() as $workspace) {
		$arryWorkspaces = $arryWorkspaces + array($workspace->gid);
	}
	return $arryWorkspaces;
}

function get_user_for_asana()
{
	// See class comments and Asana API for full info
	$asana = connect_asana();
	$users = array();
	foreach (getWorkspace_asana() as $workspace) {
		if ($workspace != null) {
			$asana->getUsersInWorkspace($workspace);
			foreach ($asana->getData() as $user) {
				$users = $users + array($user->gid => $user->name);
			}
		}
	}
	return $users;
}


function option_select($array)
{
	$option = '';
	foreach ($array as $key => $value) {
		$option .= "<option value='$key'>$value</option>";
	}
	return $option;
}

function get_user_asana_name($number)
{
	$user_name = get_user_for_asana();
	return $user_name[$number];
}

function get_projet_name($number)
{
	$projet = get_asana_projet();
	return $projet[$number];
}

/**
 * Obtenir l'id du dernier option
 */
function get_the_last_options_id()
{
	global $wpdb;
	return $wpdb->get_var("SELECT MAX( option_id ) FROM $wpdb->options");
}

function save_new_templates(array $data)
{
	global $wpdb;
	$add_table = array(
		'option_name' => '_task_template_' . get_the_last_options_id(),
		'option_value' => serialize($data),
		'autoload' => 'no'
	);
	$format = array('%s', '%s', '%s');
	return $wpdb->insert($wpdb->options, $add_table, $format);
}

function new_project_asana()
{
	return 5;
}

function save_new_project($data)
{
	global $wpdb;
	$table = $wpdb->prefix . 'project';
	$format = array('%d', '%s', '%s', '%d', '%s');
	return $wpdb->insert($table, $data, $format);
}

function get_all_project()
{
	global $wpdb;
	$table = $wpdb->prefix . 'project';
	return $wpdb->get_results("SELECT * FROM $table ");
}

function get_all_task(){
	global $wpdb;
	$table = $wpdb->prefix . 'task';
	return $wpdb->get_results("SELECT * FROM $table ");
}

function get_user_current_task(){
	return null;
}

function get_all_templates()
{
	global $wpdb;
	$type = '_task_template';
	return $wpdb->get_results("SELECT * FROM $wpdb->options WHERE SUBSTR(option_name,1,14) = '$type'");
}

function get_template_titles()
{
	$tab_templates = get_all_templates();
	$title_array = array();
	foreach ($tab_templates as $template) {
		$titles = unserialize($template->option_value);
		foreach ($titles as $title) {
			$title_array += array($template->option_id => $title['template']['templatetitle']);
		}
	}
	return $title_array;
}

function get_all_role()
{
	global $wp_roles;
	$roles_get = $wp_roles->roles;
	$roles = array();
	foreach ($roles_get as $key => $value) {
		$roles = $roles + array($key => $value['name']);
	}
	return $roles;
}

function get_all_users()
{
	$users = array();
	foreach (get_users() as $value) {
		$users += array($value->ID => $value->user_email);
	}
	return $users;
}

function is_project_manager(int $id_user = null)
{
	global $wpdb, $current_user;
	$table = $wpdb->prefix . 'project';
	$projects = get_all_project();
	$user_project = array();
	if ($id_user == null) {
		$id_user = $current_user->ID;
	}
	$i = 0;
	foreach ($projects as $project) {
		if ($project->project_manager == $id_user) {
			$i++;
			$user_project += array($i => (array)$project);
		}
	}
	return $user_project;
}

function get_project_manger_project()
{
	$projects = array();
	foreach (is_project_manager() as $project) {
		$projects += array($project['id'] => $project['title']);
	}
	return $projects;
}

function page_task()
{
	$post_author = get_current_user_id();

	if ($post_author != 0) {
?>
		<div class="container card">
			<div class="row text-center card-header">
				<div class="col-sm-4"><a class="button text-dark" data-toggle="collapse" data-target="#collapse1" aria-expanded="true" aria-controls="collapse1" href="" class="nav-tab">
						<h5><?php _e('Task lists', 'task'); ?></h5>
					</a>
				</div>
				<?php
				if (is_project_manager() != null) {
				?>
					<div class="col-sm-4"><a class="button text-dark" data-toggle="collapse" data-target="#collapse3" aria-expanded="false" aria-controls="collapse3" href="" class="nav-tab">
							<h5><?php _e('Create a Task', 'task'); ?>
							</h5>
						</a>
					</div>
				<?php
				}
				?>
				<div class="col-sm-4"><a class="button text-dark" data-toggle="collapse" data-target="#collapse5" aria-expanded="false" aria-controls="collapse5" href="" class="nav-tab">
						<h5><?php _e('Calendar', 'task'); ?>
						</h5>
					</a>
				</div>
			</div>
			<div id="accordion" class="card-body">
				<div id="collapse1" class="collapse show" aria-labelledby="heading1" data-parent="#accordion">
					<div>
						<h3>Task lists</h3>
						<?php
						get_user_task();
						?>
					</div>

				</div>

				<div id="collapse3" class="collapse" aria-labelledby="heading3" data-parent="#accordion">
					<div>
						<h3>Create a Task</h3>
						<?php
						// if (isset($_POST['validetash'])) {
						// 	echo 'Element envoyé';
						// 	var_dump($_POST);
						// }
						if (is_project_manager() != null) {
							add_task_form();
						}
						?>
					</div>
				</div>
				<div id="collapse5" class="collapse" aria-labelledby="heading5" data-parent="#accordion">
					<div>
						<h3>Calendar</h3>
						<?php require_once('calendar.php'); //get_json_calendar() 
						?>
					</div>
				</div>
			</div>
		</div>
		</div>
	<?php
	} else {
	}
}

//Redirect users who arent logged in...
function login_redirect()
{
	//Current Page
	global $pagenow;

	//Check to see if user in not logged in and not on the login page
	if (!is_user_logged_in() && $pagenow != 'wp-login.php')
		//If user is, Redirect to Login form.
		auth_redirect();
}
//if( get_permalink() === home_url() . '/index.php/orion-task/' ){
//add_action( 'wp', 'login_redirect' );		
//}


function add_task_form()
{
	?>
	<form method="post" action="" id="create_new_task">
		<div id="">
			<div class="row text-center card-header">
				<div class="col-sm-6">
					<input type="radio" class="form-check-input" name="show" value="userTemplate" id="userTemplate">
					<label class="form-check-label" for="template">Use Templates</label>
				</div>
				<div class="col-sm-6">
					<input type="radio" class="form-check-input" name="show" value="manuelTemplate" id="manuelTemplate">
					<label class="form-check-label" for="template">Create manually</label>
				</div>
			</div>
			<span id="first_choix"></span>
		</div>	
		<div id="manuel_get" style="display:none ;">
			<div class="form-check">
				<input type="checkbox" class="form-check-input" name="AddSubtask" id="AddSubtask">
				<label class="form-check-label" for="exampleCheck1">Add subtasks</label>
			</div>
			<div class="row text-center card-header" id="choix_check" style="display:none;">
				<div class="col-sm-6">
					<input type="radio" class="form-check-input" name="show1" id="userTemplate1" value="userTemplate1">
					<label class="form-check-label" for="exampleCheck1">Use Templates</label>
				</div>
				<div class="col-sm-6">						
					<input type="radio" class="form-check-input" name="show1" id="manuelTemplate1" value="manuelTemplate1">
					<label class="form-check-label" for="exampleCheck1">Create manually</label>
				</div>
			</div>
			<span id="second_choix"></span>
		</div>
		<div class="pt-5" id="hidden_submit" style="display:none">
			<button type="submit" name="validetash">Submit</button>
		</div>
	</form>
<?php
}

function get_user_task()
{

	$args = array(
		'author' => get_current_user_id(),
		'post_type'   => 'o_task_manager',
	);
	$user_current_tasks = get_user_current_task();
	if( $user_current_tasks != null ){
		?>

		<?php
	}else{
		?>
		<div class="alert alert-primary" role="alert">
			You have no tasks at the moment
		</div>
		<?php
	}
}

// Add Shortcode
function orion_task_shortcode()
{
	$var = wp_nonce_field('orion_task_manager', 'task_manager');
	return page_task();
}



function create_new_task_manager()
{
	$task = sanitize_text_field($_POST['title']);
	$assigne = sanitize_text_field($_POST['assigne']);
	$project = sanitize_text_field($_POST['project']);
	$subtask = sanitize_text_field($_POST['subtask']);
	$dependancies = sanitize_text_field($_POST['dependancies']);
	$codage = sanitize_text_field($_POST['codage']);
	$suivi = sanitize_text_field($_POST['suivi']);
	$test = sanitize_text_field($_POST['test']);
	$duedate = sanitize_text_field($_POST['duedate']);

	$post_author_id = get_current_user_id();
	$new_post = array(
		'post_title' => $task,
		'comment_status' => 'closed',
		'ping_status' => 'closed',
		'post_status' => 'publish',
		'post_date' => date('Y-m-d H:i:s'),
		'post_author' => $post_author_id,
		'post_type' => 'o_task_manager',
		'comment_count' => 1,
		'post_category' => array(0)
	);
	$post_id = wp_insert_post($new_post);


	$tab = array(
		'assigne'		=> $assigne,
		'project'		=> $project,
		'subproject'		=> $subtask,
		'dependancies'	=> $dependancies,
		'assignecodage'	=> $codage,
		'assignesuivi'	=> $suivi,
		'assignetest'	=> $test,
		'date' 			=> $duedate
	);
	$meta_key = 'o_task_manager';
	update_post_meta($post_id, $meta_key, $tab);
	echo $post_id;
	echo 'ok';
	wp_die();
}

function taches_tab()
{
?>
	<div class="container-fluid pt-3">
		<div class="row" id="accordion">
			<div class="col-sm-4 card bg-light">
				<div class="card-header" id="headingTwo">
					<h5 class="mb-0">
						<button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
							Définir Les Rôles
						</button>
					</h5>
				</div>
				<div class="card-header" id="headingOne">
					<h5 class="mb-0">
						<button class="btn btn-link" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
							Ajouter Templates
						</button>
					</h5>
				</div>
				<div class="card-header" id="headingThree">
					<h5 class="mb-0">
						<button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
							Create Projet
						</button>
					</h5>
				</div>
			</div>
			<div class="col-sm-8 card">
				<div id="collapseTwo" class="collapse show" aria-labelledby="headingTwo" data-parent="#accordion">
					<div class="card-body">

						<div class='block-form'>

							<?php
							$begin = array(
								'type' => 'sectionbegin',
								'id' => 'task-datasource-container',
							);
							$title = array(
								'title' => __('Définir le rôle des utilisateurs', 'task'),
								'type' => 'title',
								'id' => 'title',
							);

							$user = array(
								'title' => __('Choose User', 'task'),
								'name' => 'user',
								'id' => 'userasana',
								'type' => 'select',
								'desc' => __('Select user', 'task'),
								'default' => '',
								'options' => array('' => 'Choose email User') + get_all_users()
							);

							$user_choise = array(
								'id' => 'roledisabled',
								'type' => 'affiche',
								'default' => '',
								//'class' => ' form-control'
							);

							$role = array(
								'title' => __('Choose role', 'task'),
								'name' => 'role_user',
								'id' => 'role_user',
								'type' => 'select',
								'desc' => __('Select user role', 'task'),
								'default' => '',
								'class' => ' form-control',
								'options' => array('' => 'Choose role User') + get_all_role()
							);
							$btn = array(
								'title' => __('Update Role', 'task'),
								'name' => 'submit_role',
								'type' => 'submit',
								'default' => '',
								'options' => '',
								'class' => ' btn btn-primary'
							);
							$end = array('type' => 'sectionend');
							$details = array(
								$begin,
								$title,
								$user,
								$user_choise,
								$role,
								$btn,
								$end,
							);
							?>
							<form method="post" action="" id="user_role_asana">
								<?php
								echo o_admin_fields($details);
								?>
							</form>
						</div>
					</div>
				</div>
				<div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
					<div class="card-body">
						<div>
							<h3>New Template</h3>
							<div id="add_success"></div>
							<hr>
							<form action="" method="post" id="create_template">
								<div class="form-group">
									<div class="form-row">
										<label for="InputTitle">Titre Template</label>
										<input type="text" name="templatetitle" id="templatetitle" class="form-control" placeholder="Titre Template">
									</div>									
									<div class="form-row">
										<div class="col">
											<label for="InputTitle">Task Title</label>
											<input type="text" name="tasktitle" id="tasktitle" class="form-control" placeholder="Ex: Dev">
										</div>
										<div class="col">
											<label for="InputTitle">Role :</label>
											<select id="role" name="role" class="form-control">
												<option value="">Choose...</option>
												<?= option_select(get_all_role()) ?>
											</select>
										</div>
									</div>
								</div>
								<label for="inputState">Task details :</label>
								<div id="champadd" class="pb-3"></div>
								<div class="form-group">
									<span id="addchamp" name="addchamp" class="btn btn-outline-success">+ Add Task</span>
								</div>
								<div class="form-group">
									<button type="submit" value="envoyer" name="valideTemplate" class="btn btn-primary btn-sm">SAVE TEMPLATE</button>
								</div>
							</form>
						</div>
					</div>
				</div>
				<div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordion">
					<div class="card-body">
						<div>
							<h3>New Projet</h3>
							<div id="add_success"></div>
							<hr>
							<form id="create_new_projet" name="create_new_projet" action="" method="post">
								<div class="form-group">
									<label for="InputTitle">Project Name </label>
									<input type="text" name="titleproject" id="titleproject" class="form-control" placeholder="Titre template">
								</div>
								<div class="form-group">
									<div class="form-row">
										<div class="col">
											<label for="InputTitle">Slug </label>
											<input type="text" name="slug" id="slug" class="form-control" placeholder="Titre template">
										</div>
										<div class="col">
											<label for="inputState">Project Manager :</label>
											<select id="projectmanager" name="projectmanager" class="form-control">
												<option value="">Choose...</option>
												<?= option_select(get_all_users()) ?>
											</select>
										</div>
									</div>
								</div>

								<div class="form-group">
									<label for="inputState">Collaborators :</label>
									<select class="selectpicker form-control" id="multichoix" name="multichoix" multiple data-live-search="true">
										<?= option_select(get_all_users()) ?>
									</select>
								</div>
								<div class="form-group">
									<button type="submit" name="valide" class="btn btn-primary btn-sm btn-block">CREATE PROJECT</button>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
}

function worklog_tab()
{
	print_r(get_all_users());
}

function evaluation_tab()
{
	echo '3';
}

function active_tab()
{
	$token = get_option('access_token');
?>
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
<?php
}

function get_project_collaborator(int $id_project)
{
	$collaborators = array();
	foreach (is_project_manager() as $project) {
		if ($project['id'] == $id_project) {
			foreach (unserialize($project['collaborator']) as $collaborator) {
				$the_user = get_user_by('ID', $collaborator);
				$collaborators += array($the_user->ID => $the_user->user_email);
			}
		}
	}
	return $collaborators;
}

function get_form(array $array, $istemplate){
	$template = $array['parametre']['template'];
	?>
	<div class="pb-3">
		<?php
			if( $istemplate ){
				?>
				<div class="form-group">
				<label for="title">Titre</label>
						<input type="text" class="form-control" disabled name="title" id="title" value="<?= $template['tasktitle']  ?>">
				</div>
				<?php
			}else{
				?>
				<div class="row">
					<div class="col">
						<label for="title">Titre</label>
						<input type="text" class="form-control" disabled name="title" id="title" value="<?= $template['tasktitle']  ?>">
					</div>
					<div class="col">
						<label for="proectlabel" id="label1" style="color:red">Select Project : </label>
						<select class="form-control project" id="project" name="project">
							<?= option_select(array('' => 'Choose project ...') + get_project_manger_project()) ?>
						</select>
					</div>
				</div>
				<?php
			}
		?>
		<div class="form-group">
			<label for="exampleFormControlTextarea1">Description</label>
			<textarea class="form-control" id="description" name="description" placeholder="Description..." rows="3"></textarea>
		</div>
		<div class="form-group">
			<label for="exampleFormControlTextarea1">Commentaire</label>
			<textarea class="form-control" id="commentaire" name="commentaire" placeholder="Commentaire..." rows="3"></textarea>
		</div>
		<div class="row">
			<div class="col">
				<label for="assigne">Assigne : </label>
				<select class="form-control" id="assign" name="assign"></select>
			</div>
			<div class="col">
				<label for="duedate">Due Date</label>
				<input type="datetime-local" name="duedate" class="form-control" id="duedate" aria-describedby="duedate">
			</div>
		</div>
	</div>
	<?php
	if( isset( $array['parametre']['subtemplate'] ) ){
		$tab = $array['parametre']['subtemplate'];
		$j=1;
		foreach ($tab as $subtemplate) {
			?>
			<div class="row pl-5 pr-5 pb-4">
				<span onclick="open_sub_templaye(<?= $j ?>)" class="btn btn-outline-primary"><span id="change<?= $j ?>"> + </span> <?= $subtemplate['subtitle']  ?> </span>
			</div>
			<div  id="<?= $j ?>" style="display:none;" class="pl-5 pr-5 pb-3">
				<div class="form-group">
					<label for="title">Titre</label>
					<input type="text" class="form-control" disabled name="title<?= $j ?>" id="title<?= $j ?>" value="<?= $subtemplate['subtitle']  ?>">
				</div>
				<div class="row">
					<div class="col">
						<label for="assigne">Assigne : </label>
						<select class="form-control" id="assign<?= $j ?>" name="assign<?= $j ?>"></select>
					</div>
					<div class="col">
						<label for="duedate">Due Date</label>
						<input type="datetime-local" name="duedate<?= $j ?>" class="form-control" id="duedate<?= $j ?>" aria-describedby="duedate">
					</div>
				</div>
			</div>
			<?php
			$j++;
		}
	}
}

function get_first_choose( $type, $istemplate = false ){
	if( $type == 'usertemplate' ){
	?>
		<div class="form-group col-md-10 pt-3">
			<select name="selectTemplate" id="selectTemplate" class="form-control">
				<option value="">Choose Template ...</option>
				<?= option_select(get_template_titles()) ?>
			</select>
		</div>
		<div id="template_select">
		</div>
	<?php
	}
	if( $type == 'manueltemplate' ){
		?>
		<div class="pb-3">
			<?php 
				if( $istemplate ){
					?>
					<div class="form-group">
						<label for="title">Titre</label>
						<input type="text" class="form-control" name="manuel_title" id="manuel_title">
					</div>
					<?php
				}else{
					?>
					<div class="row">
						<div class="col">
							<label for="title">Titre</label>
							<input type="text" class="form-control" name="title" id="title">
						</div>
						<div class="col">
							<label for="proectlabel" id="label1" style="color:red">Select Project : </label>
							<select class="form-control project" id="project" name="project">
								<?= option_select(array('' => 'Choose project ...') + get_project_manger_project()) ?>
							</select>
						</div>
					</div>
					<?php
				}
			?>
			<div class="form-group">
				<label for="exampleFormControlTextarea1">Description</label>
				<textarea class="form-control" id="<?php if($istemplate) echo 'manuel_' ?>description" name="<?php if($istemplate) echo 'manuel_' ?>description" placeholder="Description..." rows="3"></textarea>
			</div>
			<div class="form-group">
				<label for="exampleFormControlTextarea1">Commentaire</label>
				<textarea class="form-control" id="<?php if($istemplate) echo 'manuel_' ?>commentaire" name="<?php if($istemplate) echo 'manuel_' ?>commentaire" placeholder="Commentaire..." rows="3"></textarea>
			</div>
			<div class="row">
				<div class="col">
					<label for="assigne">Assigne : </label>
					<select class="form-control" id="<?php if($istemplate) echo 'manuel_' ?>assign" name="<?php if($istemplate) echo 'manuel_' ?>assign"></select>
				</div>
				<div class="col">
					<label for="duedate">Due Date</label>
					<input type="datetime-local" name="<?php if($istemplate) echo 'manuel_' ?>duedate" class="form-control" id="<?php if($istemplate) echo 'manuel_' ?>duedate" aria-describedby="duedate">
				</div>
			</div>
		</div>
		<?php
	}
}
/**
 * 
 * @param int $id id template
 */
function get_template_form(int $id, $istemplate=false)
{
	$all_templates = get_all_templates();
	foreach ($all_templates as $templates) {
		if ($templates->option_id == $id)
			$template = $templates;
	}
	$templates_form = unserialize($template->option_value);
	get_form( $templates_form, $istemplate );
}

function settings_function()
{
	$action = htmlspecialchars($_POST['action']);
	if ($action == 'get_user_role') {
		$user_id = htmlspecialchars($_POST['id_user']);
		if (empty($user_id)) {
			echo '';
		} else {
			$user_info = get_userdata($user_id);
			$user_role = implode(', ', $user_info->roles);
			echo ucfirst($user_role);
		}
	}
	if ($action == 'update_user_role') {
		$user_id = htmlspecialchars($_POST['id_user']);
		$user_info = get_userdata($user_id);
		$user_role = implode(', ', $user_info->roles);
		if ($user_role != 'administrator') {
			$user_new_role = htmlspecialchars($_POST['select_role']);
			$user_id = wp_update_user(array('ID' => $user_id, 'role' => $user_new_role));
			echo ucfirst($user_new_role) . ' (New Role)';
		} else
			echo 'Sorry you can\'t change the roles of this user';
	}
	if ($action == 'create_new_projet') {
		$post = wp_unslash($_POST);
		$id_project = new_project_asana();
		$data = array(
			'id' => $id_project,
			'title' => $post['title'],
			'slug' => $post['slug'],
			'project_manager' => $post['project_manager'],
			'collaborator' => serialize($post['collaborator'])
		);
		echo save_new_project($data);
	}
	if ($action == 'create_template') {
		$send = array_diff($_POST, array('action' => 'create_template'));
		$data = wp_unslash($send);
		echo save_new_templates($data);
	}
	if ($action == 'get_option_add') {
		echo option_select(get_template_titles());
	}
	if ($action == 'get_option_add_template') {
		$id_project = htmlentities($_POST['project_id']);
		if (empty($id_project))
			echo '';
		else
			echo option_select(array('' => 'Choose ...') + get_project_collaborator($id_project));
	}
	if ($action == 'get_template_choose') {
		$id_template = htmlentities($_POST['template_id']);
		$istemplate = htmlentities($_POST['istemplate']);
		if( $istemplate == 'yes' ) echo get_template_form($id_template, true);
		else echo get_template_form($id_template);
	}
	if ($action == 'get_first_form') {
		$type = htmlentities($_POST['type']);
		$istemplate = htmlentities($_POST['istemplate']);
		if( $istemplate == 'yes' )
			echo get_first_choose( $type, true );
		else
			echo get_first_choose( $type );
	}
	wp_die();
}


add_action('wp_ajax_nopriv_get_user_role', 'settings_function');
add_action('wp_ajax_get_user_role', 'settings_function');

add_action('wp_ajax_nopriv_update_user_role', 'settings_function');
add_action('wp_ajax_update_user_role', 'settings_function');

add_action('wp_ajax_nopriv_create_new_projet', 'settings_function');
add_action('wp_ajax_create_new_projet', 'settings_function');

add_action('wp_ajax_nopriv_create_template', 'settings_function');
add_action('wp_ajax_create_template', 'settings_function');

add_action('wp_ajax_nopriv_get_option_add', 'settings_function');
add_action('wp_ajax_get_option_add', 'settings_function');

add_action('wp_ajax_nopriv_get_template_choose', 'settings_function');
add_action('wp_ajax_get_template_choose', 'settings_function');

add_action('wp_ajax_nopriv_get_option_add_template', 'settings_function');
add_action('wp_ajax_get_option_add_template', 'settings_function');

add_action('wp_ajax_nopriv_get_first_form', 'settings_function');
add_action('wp_ajax_get_first_form', 'settings_function');

add_shortcode('orion_task', 'orion_task_shortcode');
add_action('wp_ajax_create_new_task', 'create_new_task_manager');

add_action('wp_ajax_nopriv_create_new_task', 'create_new_task_manager');
add_action('wp', 'login_redirect');
