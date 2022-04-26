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

function save_user_id_asana()
{
}

function page_task()
{
	$post_author = get_current_user_id();

	if ($post_author != 0) {
	?>
		<table class="responsive-table">
			<tr>
				<th>
					<a class="button" data-toggle="collapse" data-target="#collapse1" aria-expanded="true" aria-controls="collapse1" href="" class="nav-tab"><?php _e('Listes des tâches créées', 'task'); ?></a>
				</th>
				<th>
					<a class="button" data-toggle="collapse" data-target="#collapse2" aria-expanded="true" aria-controls="collapse2" href="" class="nav-tab"><?php _e('Listes des tâches assignées', 'task'); ?></a>
				</th>
				<th>
					<a class="button" data-toggle="collapse" data-target="#collapse3" aria-expanded="false" aria-controls="collapse3" href="" class="nav-tab"><?php _e('Créer une tâche (Dev)', 'task'); ?></a>
				</th>
				<th>
					<a class="button" data-toggle="collapse" data-target="#collapse4" aria-expanded="false" aria-controls="collapse4" href="" class="nav-tab"><?php _e('Créer une tâche (Simple)', 'task'); ?></a>
				</th>
				<th>
					<a class="button" data-toggle="collapse" data-target="#collapse5" aria-expanded="false" aria-controls="collapse5" href="" class="nav-tab"><?php _e('Calendar', 'task'); ?></a>
				</th>
			</tr>
		</table>
		<div id="accordion">
			<div class="card">
				<div id="collapse1" class="collapse show" aria-labelledby="heading1" data-parent="#accordion">
					<div class="card-body">
						<h3><?php _e('Listes des tâches créées', 'task'); ?></h3>
						<?php
						get_user_task();
						?>
					</div>
				</div>
			</div>
			<div class="card">
				<div id="collapse2" class="collapse" aria-labelledby="heading2" data-parent="#accordion">
					<div class="card-body">
						<h3><?php _e('Listes des tâches assignées', 'task'); ?></h3>
						<?php
						echo 'Taches';
						?>
					</div>
				</div>
			</div>
			<div class="card">
				<div id="collapse3" class="collapse" aria-labelledby="heading3" data-parent="#accordion">
					<div class="card-body">
						<h3><?php _e('Créer une tâche (Dev)', 'task'); ?></h3>
						<?php
						add_task_form('dev');
						?>
					</div>
				</div>
			</div>
			<div class="card">
				<div id="collapse4" class="collapse" aria-labelledby="heading4" data-parent="#accordion">
					<div class="card-body">
						<h3><?php _e('Créer une tâche (Simple)', 'task'); ?></h3>
						<?php
						add_task_form();
						?>
					</div>
				</div>
			</div>
			<div class="card">
				<div id="collapse5" class="collapse" aria-labelledby="heading5" data-parent="#accordion">
					<div class="card-body">
						<h3><?php _e('Calendar', 'task'); ?></h3>
						<?= get_json_calendar() ?>
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
add_action('wp', 'login_redirect');

function add_task_form($type = 'simple')
{
	$user_asana = get_user_for_asana();
	$tasks = get_all_task();
	?>
	<form method="post" action="" id="<?php if ($type == 'dev') echo 'create_new_task';
										else echo 'create_simple_task'; ?>">
		<div class="form-group">
			<label for="titre">Titre</label>
			<input type="text" class="form-control" name="titre" id="titre" aria-describedby="inputHelp" placeholder="Nom de la tâche">
		</div>
		<div class="row">
			<div class="col">
				<label for="assigne">Assigne : </label>
				<select class="form-control" id="assigne" name="assigne">
					<?= option_select($user_asana) ?>
				</select>
			</div>
			<div class="col">
				<label for="project">Project</label>
				<select class="form-control" name="project" id="project">
					<?= option_select(get_asana_projet()) ?>
				</select>
			</div>
		</div>
		<div class="row">
			<div class="col">
				<label for="subtask">Sub Task : </label>
				<select class="form-control" name="subtask" id="subtask">
					<option>Choise Sub Task</option>
					<?= option_select($tasks) ?>
				</select>
			</div>
			<div class="col">
				<label for="dependancies">Dependancies : </label>
				<select class="form-control" name="dependancies" id="dependancies">
					<option>Choise Dependancies</option>
					<?= option_select($tasks) ?>
				</select>
			</div>
		</div>
		<?php
		if ($type == 'dev') {
		?>
			<div class="row">
				<div class="col">
					<label for="assignecodage">Assigne Codage : </label>
					<select class="form-control" name="assignecodage" id="assignecodage">
						<?= option_select($user_asana) ?>
					</select>
				</div>
				<div class="col">
					<label for="assignesuivi">Assigne Suivi : </label>
					<select class="form-control" name="assignesuivi" id="assignesuivi">
						<?= option_select($user_asana) ?>
					</select>
				</div>
			</div>
			<div class="row">
				<div class="col">
					<label for="assignetest">Assigne Test : </label>
					<select class="form-control" name="assignetest" id="assignetest">
						<?= option_select($user_asana) ?>
					</select>
				</div>
				<div class="col">
					<label for="duedate">Due Date</label>
					<input type="datetime-local" name="duedate" class="form-control" id="duedate" aria-describedby="duedate">
				</div>
			</div>
		<?php
		} else {
		?>
			<div class="row">
				<div class="col-sm-6">
					<label for="duedate">Due Date</label>
					<input type="datetime-local" name="duedate" class="form-control" id="duedate" aria-describedby="duedate">
				</div>
			</div>
		<?php
		}
		?>

		<div class="pt-5">
			<button type="submit">Submit</button>
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
						<td><a href="https://app.asana.com/<?php _e($post_meta['project'], 'task'); ?>"><?php _e(get_projet_name($post_meta['project']), 'task'); ?></a></td>
						<td><?php _e($key->post_title, 'task'); ?></td>
						<td><?php _e(date("d/m/Y à H:i", strtotime($post_meta['date']))); ?></td>
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

add_shortcode('orion_task', 'orion_task_shortcode');

add_action('wp_ajax_create_new_task', 'create_new_task_manager');
add_action('wp_ajax_nopriv_create_new_task', 'create_new_task_manager');

function create_new_task_manager()
{
	$task = sanitize_text_field( $_POST['title'] );
	$assigne = sanitize_text_field( $_POST['assigne'] );
	$project = sanitize_text_field( $_POST['project'] );
	$subtask = sanitize_text_field( $_POST['subtask'] );
	$dependancies = sanitize_text_field( $_POST['dependancies'] );
	$codage = sanitize_text_field( $_POST['codage'] );
	$suivi = sanitize_text_field( $_POST['suivi'] );
	$test = sanitize_text_field( $_POST['test'] );
	$duedate = sanitize_text_field( $_POST['duedate'] );

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
	update_post_meta( $post_id, $meta_key, $tab );
	echo $post_id;
	echo 'ok';
	wp_die();
}
