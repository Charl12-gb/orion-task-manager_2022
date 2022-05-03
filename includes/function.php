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

function get_all_task()
{
	$asana = connect_asana();
	$tasks = array();

	$asana->getWorkspaces();
	foreach ($asana->getData() as $workspace) {

		// Get all projects in the current workspace (all non-archived projects)
		$asana->getProjectsInWorkspace($workspace->gid, $archived = false);

		foreach ($asana->getData() as $project) {

			// Get all tasks in the current project
			$asana->getProjectTasks($project->gid);
			foreach ($asana->getData() as $task) {
				$tasks = $tasks + array($task->gid => $task->name);
			}
		}
	}
	return $tasks;
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

if (isset($_POST['valideTemplate'])) {
	update_option($_POST['titlechamps'], array(3 => 'Test 3', 4 => 'Test 4'), '', 'no');
}

/**
 * Obtenir l'id du dernier option
 */
function get_the_last_options_id()
{
	global $wpdb;
	return $wpdb->get_var("SELECT MAX( option_id ) FROM $wpdb->options");
}

function save_new_templates(string $title_template, array $data)
{
	global $wpdb;
	$add_table = array(
		'option_name' => $title_template . get_the_last_options_id(),
		'option_value'=> serialize( $data ),
		'autoload' => 'no'
	);
	$format = array('%s','%s', '%s');
	return $wpdb->insert($wpdb->options, $add_table, $format);
}

function save_new_project($id_user, $data)
{
	global $wpdb;
	$table = $wpdb->prefix . 'project';
	$id_project = new_project_asana();
	$data = array('id' => $id_project) + $data;
	$format = array('%s','%s', '%d', '%s');
	return $wpdb->insert( $wpdb->prefix . 'project', $data, $format );
}

function get_all_template()
{
	global $wpdb;
	$type = '_task_template';
	return $wpdb->get_results("SELECT * FROM $wpdb->options WHERE SUBSTR(option_name,1,14) = '$type'");
}
function get_json_calendar()
{
	$args = array(
		'post_type'   => 'o_task_manager',
	);
	$post_type = get_posts($args);

	$cal = "";
?>
	<table class="table table-hover">
		<thead>
			<tr>
				<th scope="col">Task</th>
				<th scope="col">Due Date</th>
				<th scope="col">Assigne</th>
				<th scope="col">Others</th>
			</tr>
		</thead>
		<tbody>
			<?php
			if ($post_type != null) {
				$user_name = get_user_for_asana();
				foreach ($post_type as $key) {

					$post_meta_br = get_post_meta($key->ID);
					$post_meta = unserialize($post_meta_br['o_task_manager'][0]);
					$val = (array('task' => $key->post_title, 'date' => $post_meta['date'], 'assigne' => $post_meta['assigne'], 'code' => $post_meta['assignecodage'], 'suivi' => $post_meta['assignesuivi'], 'test' => $post_meta['assignetest']));

					$projet = get_projet_name($post_meta['project']);
					$assigne  = $user_name[($post_meta['assigne'])];
					$codage  = $user_name[($post_meta['assignecodage'])];
					$suivi  = $user_name[($post_meta['assignesuivi'])];
					$test  = $user_name[($post_meta['assignetest'])];
					$duedate = date("d/m/Y à H:i", strtotime($post_meta['date']));
			?>
					<tr>
						<td><?php _e("$key->post_title <br> ( $projet )", 'task'); ?></td>
						<td><?php _e($duedate, 'task'); ?></td>
						<td><?php _e($assigne, 'task'); ?></td>
						<td><?php _e("Codage: $codage <br> Suivi: $suivi <br> Test: $suivi", 'task'); ?></td>
					</tr>
				<?php
				}
			} else {
				?>
				<tr>
					<td colspan="2"><?php _e('Auncune tâche', 'task'); ?></td>
				</tr>
			<?php
			}
			?>
		</tbody>
	</table>
	<?php

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
		$users = $users + array($value->ID => $value->user_email);
	}
	return $users;
}

function page_task()
{
	$post_author = get_current_user_id();

	if ($post_author != 0) {
	?>
		<div class="container card">
			<div class="row text-center card-header">
				<div class="col-sm-4"><a class="button text-dark" data-toggle="collapse" data-target="#collapse1" aria-expanded="true" aria-controls="collapse1" href="" class="nav-tab">
						<h5><?php _e('Listes des tâches', 'task'); ?></h5>
					</a></div>
				<div class="col-sm-4"><a class="button text-dark" data-toggle="collapse" data-target="#collapse3" aria-expanded="false" aria-controls="collapse3" href="" class="nav-tab">
						<h5><?php _e('Créer une tâche', 'task'); ?>
					</a></h5>
				</div>
				<div class="col-sm-4"><a class="button text-dark" data-toggle="collapse" data-target="#collapse5" aria-expanded="false" aria-controls="collapse5" href="" class="nav-tab">
						<h5><?php _e('Calendar', 'task'); ?>
					</a></h5>
				</div>
			</div>
			<div id="accordion" class="card-body">
				<div id="collapse1" class="collapse show" aria-labelledby="heading1" data-parent="#accordion">
					<div>
						<h3>Listes des tâches</h3>
						<?php
						get_user_task();
						?>
					</div>

				</div>

				<div id="collapse3" class="collapse" aria-labelledby="heading3" data-parent="#accordion">
					<div>
						<h3>Créer une tâche</h3>
						<?php
						if (isset($_POST['validetash'])) {
							echo 'Element envoyé';
							var_dump($_POST);
						}
						add_task_form();
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
	$user_asana = get_user_for_asana();
	$tasks = get_all_task();
	?>
	<form method="post" action="" id="create_new_task">
		<div class="form-group">
			<label for="titre">Titre</label>
			<input type="text" class="form-control" name="titre" id="titre" aria-describedby="inputHelp" placeholder="Nom de la tâche">
		</div>
		<div class="form-group">
			<label for="exampleFormControlTextarea1">Description</label>
			<textarea class="form-control" id="exampleFormControlTextarea1" rows="3"></textarea>
		</div>
		<div class="row">
			<div class="col">
				<label for="assigne">Assigne : </label>
				<select class="form-control" id="assigne" name="assigne">
					<?= option_select($user_asana) ?>
				</select>
			</div>
			<div class="col">
				<label for="duedate">Due Date</label>
				<input type="datetime-local" name="duedate" class="form-control" id="duedate" aria-describedby="duedate">
			</div>
		</div>
		<div class="form-check text-primary">
			<input type="checkbox" class="form-check-input" name="AddSubtask" id="AddSubtask">
			<label class="form-check-label" for="exampleCheck1">Ajouter des sous tâches</label>
		</div>
		<div class="row text-center card-header" id="choix_check" style="display:none;">
			<div class="col-sm-4">
				<input type="checkbox" class="form-check-input" name="show1" id="show1">
				<label class="form-check-label" for="exampleCheck1">User Templates</label>
			</div>
			<div class="col-sm-4">
				<input type="checkbox" class="form-check-input" name="show2" id="show2">
				<label class="form-check-label" for="exampleCheck1">Create manuellement</label>
			</div>
		</div>
		<div class="choix_1" style="display: none;">
			<ol>
				<li>JavaScript</li>
				<li>HTML</li>
				<li>CSS</li>
				<li>JQuery</li>
			</ol>
		</div>
		<div class="choix_2" style="display: none;">
			<div class="form-group">
				<label for="titre">Titre</label>
				<input type="text" class="form-control" name="titre_manuel" id="titre_manuel" aria-describedby="inputHelp" placeholder="Nom de la tâche">
			</div>
			<div class="form-group">
				<label for="exampleFormControlTextarea1">Description</label>
				<textarea class="form-control" id="description_manuel" name="description_manuel" rows="3"></textarea>
			</div>
			<div class="row">
				<div class="col">
					<label for="assigne">Assigne : </label>
					<select class="form-control" id="assigne_manuel" name="assigne_manuel">
						<?= option_select($user_asana) ?>
					</select>
				</div>
				<div class="col">
					<label for="duedate">Due Date</label>
					<input type="datetime-local" name="duedate_manuel" class="form-control" id="duedate_manuel" aria-describedby="duedate">
				</div>
			</div>
		</div>

		<div class="pt-5">
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
?>
	<table class="table table-hover">
		<thead>
			<tr>
				<th scope="col">Project</th>
				<th scope="col">Task</th>
				<th scope="col">Due Date</th>
				<th scope="col">Status</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$post_types = get_posts($args);
			if ($post_types != null) {
				foreach ($post_types as $key) {
					$post_meta_bruite = get_post_meta($key->ID);
					$post_meta = unserialize($post_meta_bruite['o_task_manager'][0]);
			?>
					<tr>
						<td><a href="https://app.asana.com/<?php _e($post_meta['project'], 'task'); ?>"><?php _e(get_projet_name($post_meta['project']), 'task'); ?></a>
						</td>
						<td><?php _e($key->post_title, 'task'); ?></td>
						<td><?php _e(date("d/m/Y à H:i", strtotime($post_meta['date']))); ?></td>
						<td><?php _e('Status'); ?></td>
					</tr>
				<?php
				}
			} else {
				?>
				<tr>
					<td colspan="2"><?php _e('Auncune tâche', 'task'); ?></td>
				</tr>
			<?php
			}
			?>
		</tbody>
	</table>
<?php
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
				<div class="card-header" id="headingOne">
					<h5 class="mb-0">
						<button class="btn btn-link" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
							Ajouter Templates
						</button>
					</h5>
				</div>
				<div class="card-header" id="headingTwo">
					<h5 class="mb-0">
						<button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
							Définir Les Rôles
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
				<div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
					<div class="card-body">
						<div>
							<h3>New Template</h3>
							<hr>
							<form action="" method="post" id="create_template">
								<div class="form-group">
									<div class="form-row">
										<label for="InputTitle">Titre Template</label>
										<input type="text" name="titlechamps" id="titlechamps" class="form-control" placeholder="Titre template">
									</div>
									<div class="form-row">
										<div class="col">
											<label for="InputTitle">Template For :</label>
											<select id="inputRole" name="inputRole" class="form-control">
												<option value="">Choose...</option>
												<?= option_select(get_all_role()) ?>
											</select>
										</div>
										<div class="col">
											<label for="inputSub">Sous Templates</label>
											<select id="subTemplate" name="subTemplate" class="form-control">
												<option value="">Choose...</option>
												<?= option_select(get_all_role()) ?>
											</select>
										</div>
									</div>
								</div>
								<label for="inputState">Template info :</label>

								<div class="form-row">
									<div class="form-group col-md-5">
										<input type="hidden" name="nbre_champs" value="1">
										<select name="typechamps[0]" id="typechamps[0]" class="form-control">
											<option>Choose Type Champs ...</option>
											<option value="text">Text</option>
											<option value="textarea">Textarea</option>
											<option value="email">Email</option>
											<option value="password">Password</option>
											<option value="file">File</option>
											<option value="radio">Radio</option>
											<option value="checkbox">CheckBox</option>
										</select>
									</div>
									<div class="form-group col-md-6">
										<input type="text" class="form-control" name="placeholderchamps[0]" id="placeholderchamps[0]" placeholder="Placeholder Champs">
									</div>
									<div class="form-group col-md-1">
										<button class="btn btn-outline-danger">x</button>
									</div>
								</div>
								<div class="form-group">
									<span id="leschamps_1"><a href="javascript:create_champ(1)"><button type="button" class="btn btn-outline-primary">+</button></a></span>
								</div>

								<div class="form-group">
									<button type="submit" value="envoyer" name="valideTemplate" class="btn btn-primary btn-sm btn-block">SAVE TEMPLATE</button>
								</div>
							</form>
						</div>
						<?php
						// echo 'Voila le résultat du formulaire<br/>';
						// var_dump($_POST);
						// echo '<br/>et voila le résultat des champs en affichage<br/>';
						// foreach ($_POST['typechamps'] as $value) {
						// 	echo $value . '<br/>';
						// 
						?>
					</div>
				</div>
				<div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
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
								'title' => __('Choise User', 'task'),
								'name' => 'user',
								'id' => 'userasana',
								'type' => 'select',
								'desc' => __('Select user', 'task'),
								'default' => '',
								'options' => array('' => 'Choise email User') + get_all_users()
							);

							$user_choise = array(
								'id' => 'roledisabled',
								'type' => 'affiche',
								'default' => '',
								//'class' => ' form-control'
							);

							$role = array(
								'title' => __('Choise role', 'task'),
								'name' => 'role_user',
								'id' => 'role_user',
								'type' => 'select',
								'desc' => __('Select user role', 'task'),
								'default' => '',
								'class' => ' form-control',
								'options' => array('' => 'Choise role User') + get_all_role()
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
				<div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordion">
					<div class="card-body">
						<div>
							<h3>New Projet</h3>
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
												<option>Choose...</option>
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
			'title' => __('Token Access', 'task'),
			'name' => 'tokens',
			'type' => 'text',
			'desc' => __('Enter the token', 'task'),
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

function get_user_role_()
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
		$user_new_role = htmlspecialchars($_POST['select_role']);
		$user_id = wp_update_user(array('ID' => $user_id, 'role' => $user_new_role));
		echo 'ok';
	}
	if ($action == 'create_template') {
		echo 'template';
	}
	if ($action == 'create_new_projet') {
		echo 'Project';
	}
	wp_die();
}


add_action('wp_ajax_nopriv_get_user_role', 'get_user_role_');
add_action('wp_ajax_get_user_role', 'get_user_role_');
add_shortcode('orion_task', 'orion_task_shortcode');
add_action('wp_ajax_create_new_task', 'create_new_task_manager');
add_action('wp_ajax_nopriv_create_new_task', 'create_new_task_manager');
add_action('wp', 'login_redirect');
