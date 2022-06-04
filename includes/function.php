<?php
require_once('class-asana-orion-task.php');
require_once('class-evaluation-orion-task.php');
if (isset($_POST['tokens']) && !empty($_POST['tokens'])) {
	$data_post   = wp_unslash($_POST['tokens']);
	update_option('access_token', $data_post);
}
function _taitement_form(){
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
function option_select($array)
{
	$option = '';
	foreach ($array as $key => $value) {
		$option .= "<option value='$key'>$value</option>";
	}
	return $option;
}

/**
 * Obtenir l'id du dernier option
 */
function get_the_last_options_id()
{
	global $wpdb;
	$val = $wpdb->get_var("SELECT MAX( option_id ) FROM $wpdb->options");
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$key = '';
	for ($i = 0; $i < 5; $i++) {
		$key .= $characters[rand(0, $charactersLength - 1)];
	}
	return $val . $key;
}

/**
 * Récupération des informations d'évaluation
 * 
 * @param int $task_id
 * 
 */
function get_evaluation_info($task_id)
{
	global $wpdb;
	$table = $wpdb->prefix . 'worklog';
	$sql = "SELECT evaluation FROM $table WHERE id_task=$task_id";
	return $wpdb->get_row($sql);
}

/**
 * Fonction permettant la sauvegarde des notes d'évaluation
 * 
 * @param array $array
 * 
 * @return bool|int
 */
function save_evaluation_info($array, $task_id): bool
{
	global $wpdb;
	if (get_evaluation_info($task_id)->evaluation == null) {
		$table = $wpdb->prefix . 'worklog';
		$format = array('%s');
		return $wpdb->update($table, array('evaluation' => serialize($array)), array('id_task' => $task_id), $format);
	} else {
		return false;
	}
}

function save_new_templates($template_id = '', array $data)
{
	global $wpdb;
	$add_table = array(
		'option_name' => '_task_template_' . get_the_last_options_id(),
		'option_value' => serialize($data),
		'autoload' => 'no'
	);
	$format = array('%s', '%s', '%s');
	if (empty($template_id))
		return $wpdb->insert($wpdb->options, $add_table, $format);
	else
		return $wpdb->update($wpdb->options, $add_table, array('option_id' => $template_id), $format);
}

function delete_template($id_template)
{
	global $wpdb;
	$ok = $wpdb->delete($wpdb->options, array('option_id' => $id_template));
	return $ok;
}
function delete_email($id_template)
{
	global $wpdb;
	$table = $wpdb->prefix . 'mails';
	$ok = $wpdb->delete($table, array('id' => $id_template));
	return $ok;
}

function save_project($data)
{
	global $wpdb;
	$table = $wpdb->prefix . 'project';
	$format = array('%d', '%s', '%s', '%s', '%s', '%d', '%s');
	return $wpdb->insert($table, $data, $format);
}

function save_new_sections($data)
{
	global $wpdb;
	$table = $wpdb->prefix . 'sections';
	$format = array('%d', '%d', '%s');
	return $wpdb->insert($table, $data, $format);
}

function save_new_categories($datas, $id = null)
{
	global $wpdb;
	$table = $wpdb->prefix . 'categories';
	$format = array('%s', '%s');
	if ($id == null) {
		foreach ($datas['valeur'] as $data) {
			$form = str_replace(" ", "_", strtolower($data['categorie']));
			$data_format = array('categories_key' => $form, 'categories_name' => $data['categorie']);
			$wpdb->insert($table, $data_format, $format);
		}
	} else {
		$format = array('%s');
		$d_format = array('categories_name' => $datas);
		$wpdb->update($table, $d_format, array('id' => $id), $format);
	}
	return;
}

/**
 * Mettre à jour la dépendance des tâches après leur sync avec asana
 * 
 * @param int $id_parent
 * @param int $dependant
 * 
 * @return bool
 */
function update_task_dependance( $id_parent, $dependant ){
	global $wpdb;
	$table = $wpdb->prefix . 'task';
	$format = array('%d');
	$data = array( 'dependancies' => $id_parent );
	return $wpdb->update($table, $data, array( 'id' => $dependant ), $format);
}

/**
 * Mettre à jour le type des tâches lors de la synchonisation
 * 
 * @param array $datas
 * @param string $type_task
 */
function update_type_task( $datas, $type_task ){
	global $wpdb;
	$table = $wpdb->prefix . 'task';
	$format = array('%s');
	foreach( $datas as $data ){
		$data_add = array( 'type_task' => $type_task );
		$wpdb->update($table, $data_add, array( 'id' => $data ), $format);
	}
}

function delete_categories_($id)
{
	global $wpdb;
	$table = $wpdb->prefix . 'categories';
	return $wpdb->delete($table, array('id' => $id), array('%d'));
}

function save_new_mail_form(array $data, $id_template = null)
{
	global $wpdb;
	$table = $wpdb->prefix . 'mails';
	$format = array('%s', '%s', '%s');
	if ($id_template == null)
		$ok = $wpdb->insert($table, $data, $format);
	else
		$ok =  $wpdb->update($table, $data, array('id' => $id_template), $format);
	return $ok;
}
function save_new_task(array $data, array $worklog, $subarray = null)
{
	global $wpdb;
	$tabletask = $wpdb->prefix . 'task';
	$tableworklog = $wpdb->prefix . 'worklog';

	$formatworklog = array('%d', '%s', '%s', '%s');
	$formattask = array('%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s');

	$ok = $wpdb->insert($tabletask, $data, $formattask);
	$wpdb->insert($tableworklog, $worklog, $formatworklog);

	if ($subarray != null) {
		$tablesubtask = $wpdb->prefix . 'subtask';
		$formatsubtask = array('%d', '%d');
		$wpdb->insert($tablesubtask, $subarray, $formatsubtask);
	}

	return $ok;
}

function save_objective( $data ){
	global $wpdb;
	$table = $wpdb->prefix . 'objectives';
	$format = array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s');
	return $wpdb->insert($table, $data, $format);
}

function get_all_project()
{
	global $wpdb;
	$table = $wpdb->prefix . 'project';
	$sql = "SELECT * FROM $table";
	return $wpdb->get_results($sql);
}

function section_exist( $section_name, $project_id = null ){
	global $wpdb;
	$table = $wpdb->prefix . 'sections';
	if( $project_id != null ){
		$sql = "SELECT * FROM $table WHERE project_id=$project_id";
		$outputs = $wpdb->get_results( $sql );
		$trouver = false;
		foreach( $outputs as $section ){
			if( $section->section_name == $section_name ) $trouver = true;
		}
		return $trouver;
	}else{
		$name = get_userdata( $section_name )->display_name;
		$sql = "SELECT id FROM $table WHERE section_name='$name'";
		$outputs = $wpdb->get_row( $sql );
		if( $outputs != null )
			return $outputs->id;
		else return null;
	}
}


function objective_exist( $id_user, $mois, $annee ){
	global $wpdb;
	$table = $wpdb->prefix . 'objectives';
	$sql = "SELECT * FROM $table WHERE id_user=$id_user AND month_section=$mois AND year_section=$annee";
	$outputs = $wpdb->get_row( $sql );
	if( $outputs != null ) return true;
	else return false;
}

function get_all_worklog()
{
	global $wpdb;
	$table = $wpdb->prefix . 'worklog';
	$sql = "SELECT * FROM $table";
	return $wpdb->get_results($sql);
}

function get_all_sections($id_project = null)
{
	global $wpdb;
	$table = $wpdb->prefix . 'sections';
	if ($id_project == null)
		$sql = "SELECT * FROM $table";
	else
		$sql = "SELECT * FROM $table WHERE project_id = $id_project";
	return $wpdb->get_results($sql);
}

function get_all_email($id_email = null)
{
	global $wpdb;
	$table = $wpdb->prefix . 'mails';
	if ($id_email == null)
		$sql = "SELECT * FROM $table";
	else
		$sql = "SELECT * FROM $table WHERE id = $id_email";
	return $wpdb->get_results($sql);
}

function get_task_($specification = null, $value = null, $project = null)
{
	global $wpdb;
	$table = $wpdb->prefix . 'task';
	$table1 = $wpdb->prefix . 'worklog';
	if ($project == null) {
		if ($specification != null && $value != null)
			$sql = "SELECT * FROM $table INNER JOIN $table1 ON id=id_task WHERE $specification = $value"; // Association avec le worklog
		else if ($specification != null && $value == null)
			$sql = "SELECT * FROM $table WHERE assigne = $specification"; //Tâche assign à l'utilisateur x
		else
			$sql = "SELECT * FROM $table ORDER BY duedate"; //Toutes les tâches par ordre de la date
	} else {
		$sql = "SELECT * FROM $table WHERE $specification = $value";  //Tâche avec une spéfication
	}
	return $wpdb->get_results($sql);
}

function get_subtask($task_id)
{
	global $wpdb;
	$table = $wpdb->prefix . 'subtask';
	$sql = "SELECT id FROM $table WHERE id_task_parent = $task_id";
	return $wpdb->get_results($sql);
}

function get_user_current_project($id_user)
{
	$user_projects_id = array();
	if ($id_user == null) $id_user = get_current_user_id();
	$j = 0;
	foreach (get_all_project() as $value) {
		if( $value->collaborator != null ){
			if (in_array($id_user, unserialize($value->collaborator))) {
				$user_projects_id += array($j => array('id' => $value->id, 'title' => $value->title));
				$j++;
			}
		}
	}
	return $user_projects_id;
}
function get_templates_($template_id = null)
{
	global $wpdb;
	$type = '_task_template';
	if ($template_id != null)
		$sql = "SELECT * FROM $wpdb->options WHERE option_id = $template_id";
	else
		$sql = "SELECT * FROM $wpdb->options WHERE SUBSTR(option_name,1,14) = '$type'";
	return $wpdb->get_results($sql);
}

/**
 * Fonction permettant d'obtenir les objectifs d'un mois d'un project manager
 * 
 * @param int $id_user
 * @param string $month
 * 
 * @return object|array
 */
function get_objective_of_month( $id_user, $month, $year=null ){
	global $wpdb;
	$table = $wpdb->prefix . 'objectives';
	if( $year == null ) $year = date('Y');
	$sql = "SELECT * FROM $table WHERE id_user=$id_user AND month_section=$month AND year_section=$year";
	return $wpdb->get_row($sql);
}

function get_template_titles()
{
	$tab_templates = get_templates_();
	$title_array = array();
	foreach ($tab_templates as $template) {
		$titles = unserialize($template->option_value);
		$title_array += array($template->option_id => $titles['parametre']['template']['templatetitle']);
	}
	return $title_array;
}

function get_all_role( $not=null )
{
	global $wp_roles;
	$roles_get = $wp_roles->roles;
	$roles = array();
	if( $not == null ){
		foreach ($roles_get as $key => $value) {
			$roles = $roles + array($key => $value['name']);
		}
	}else{
		foreach ($roles_get as $key => $value) {
			if( $key != $not )
			$roles = $roles + array($key => $value['name']);
		}
	}
	return $roles;
}

function get_all_users($key = null)
{
	$users = array();
	
		if ($key != null) {
			foreach (get_users() as $value) {
				$users += array($value->ID => $value->display_name);
			}
		} else {
			foreach (get_users() as $value) {
				$users += array($value->ID => $value->user_email);
			}
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

function get_project_title($id_project)
{
	$projects = get_all_project();
	foreach ($projects as $project) {
		if ($project->id == $id_project) {
			return $project->title;
		}
	}
}

function get_task_status($task_id)
{
	if( isset( get_task_('id', $task_id)[0] ) ){
		$task = get_task_('id', $task_id)[0];
		$duedate = strtotime($task->duedate);
		if ($task->status == null) {
			$finaly_date = strtotime(date('Y-m-d H:i:s',  strtotime('+1 hours')));
			if ($duedate < $finaly_date) {
				return 'Not Completed';
			} elseif ($duedate == $finaly_date) {
				return 'Today deadline';
			} else {
				return 'In Progess';
			}
		} else {
			$finaly_date = strtotime($task->finaly_date);
			if ($duedate >= $finaly_date) {
				return 'Completed';
			} else {
				return 'Completed Before Date';
			}
		}
	}
}

function save_objective_project( $project ){
	$asana = connect_asana();
	$data = array(
		"workspace" => get_workspace(),
		"name" => $project['title'],
	);
	$asana->createProject( $data );
	$result = $asana->getData();
	if (isset($result->gid)){
		$array = array(
			'id' => $result->gid,
			'title' => $project['title'],
			'description' => $project['mois'],
			'slug' => $project['type_task'],
			'permalink'=> $result->permalink_url,
			'project_manager' => get_current_user_id(),
			'collaborator' => ''
		);
		save_project( $array );
		return $result->gid;
	}else return null;
}

function useTemplate_save( $array ){
	$subtask = array();
	$task = array(
		'title' => htmlentities($array['title']),
		'section_project' => htmlentities($array['project_section']),
		'type_task' => htmlentities($array['type_task']),
		'categorie' => NULL,
		'dependance' => NULL,
		'project' => htmlentities($array['project']),
		'assign' => htmlentities($array['assign']),
		'duedate' => htmlentities($array['duedate']),
		'description' => htmlentities($array['description'])
	);
	if (isset($array['nbrechamp'])) {
		$nbrechamp = htmlentities($array['nbrechamp']) -1 ;
		for ($l = 1; $l <= $nbrechamp; $l++) {
			$titre = 'title' .$l;
			$categorie = 'categorie'.$l;
			$assign = 'assign'.$l;
			$duedate = 'duedate'.$l;
			$description = 'description'.$l;
			$subtask += array($l => array(
				'title' => htmlentities($array[$titre]),
				'section_project' => htmlentities($array['project_section']),
				'type_task' => htmlentities($array['type_task']),
				'categorie' => htmlentities($array[$categorie]),
				'dependance' => '',
				'project' => htmlentities($array['project']),
				'assign' => htmlentities($array[$assign]),
				'duedate' => htmlentities($array[$duedate]),
				'description' => htmlentities($array[$description])
			));
		}
	}
	$parametre = array( 'parametre' => array( 'task' => $task, 'subtask' => $subtask ) );
	return traite_task_and_save( $parametre );
}

function manuel_save($array)
{
	$subtask = array();
	$task = array(
		'title' => htmlentities($array['title']),
		'section_project' => htmlentities($array['project_section']),
		'type_task' => htmlentities($array['type_task']),
		'categorie' => htmlentities( $array['categorie'] ),
		'dependance' => NULL,
		'project' => htmlentities($array['project']),
		'assign' => htmlentities($array['assign']),
		'duedate' => htmlentities($array['duedate']),
		'description' => htmlentities($array['description'])
	);
	if (isset($array['show1'])) {
		if ($array['show1'] == 'userTemplate1') {
			$subtask += array(
				0 => array(
					'title' => htmlentities($array['sub_title']),
					'section_project' => htmlentities($array['project_section']),
					'type_task' => htmlentities($array['type_task']),
					'categorie' => htmlentities($array['sub_categorie']),
					'dependance' => '',
					'project' => htmlentities($array['project']),
					'assign' => htmlentities($array['sub_assign']),
					'duedate' => htmlentities($array['sub_duedate']),
					'description' => htmlentities($array['sub_description'])
				)
			);
			if (isset($array['nbrechamp'])) {
				$nbrechamp = htmlentities($array['nbrechamp']) - 1;
				for ($l = 1; $l <= $nbrechamp; $l++) {
					$titre = 'sub_title' . $l;
					$categorie = 'sub_categorie' . $l;
					$assign = 'sub_assign' . $l;
					$duedate = 'sub_duedate' . $l;
					$description = 'sub_description' . $l;
					$subtask += array($l => array(
						'title' => htmlentities($array[$titre]),
						'section_project' => htmlentities($array['project_section']),
						'type_task' => htmlentities($array['type_task']),
						'categorie' => htmlentities($array[$categorie]),
						'dependance' => '',
						'project' => htmlentities($array['project']),
						'assign' => htmlentities($array[$assign]),
						'duedate' => htmlentities($array[$duedate]),
						'description' => htmlentities($array[$description])
					));
				}
			}
		}
		if ($array['show1'] == 'manuelTemplate1') {
			$subtask += array(
				0 => array(
					'title' => htmlentities($array['manuel_title']),
					'section_project' => htmlentities($array['project_section']),
					'type_task' => htmlentities($array['type_task']),
					'categorie' => htmlentities($array['manuel_categorie']),
					'dependance' => '',
					'project' => htmlentities($array['project']),
					'assign' => htmlentities($array['manuel_assign']),
					'duedate' => htmlentities($array['manuel_duedate']),
					'description' => htmlentities($array['manuel_description'])
				)
			);
		}
	}
	$parametre = array('parametre' => array('task' => $task, 'subtask' => $subtask));
	return traite_task_and_save($parametre);
}

function save_dev_normal_task( $array ){
	if ($array['show'] == 'userTemplate') {
		return useTemplate_save( $array );
	}
	if( $array['show'] == 'manuelTemplate' ){
		return manuel_save( $array );
	}else return false;
}


function traite_form_public($array)
{
	if (
		$array['type_task'] == 'objective'
	) {
		$nbre = htmlentities($array['nbreobj']);
		$mois = htmlentities($array['mois']);
		$annee = date('Y');
		$project = htmlentities($array['project_select']);
		if (objective_exist(get_current_user_id(), $mois, $annee)) return false;
		else {
			if ($nbre == 0) return false;
			else {
				$id_section = section_exist(get_current_user_id());
				if ($id_section == null) return false;
				else {
					//Savegarde du mois comme une tâche
					$month = date('F', mktime(0, 0, 0, $mois, 10)) . " ( $annee ) ";
					$project_name = get_project_title($project);
					$string = 'last friday of ' . date('F', mktime(0, 0, 0, $mois, 10)) . ' this year';
					$duedate = gmdate('Y-m-d', strtotime($string)) . ' 23:59:00';
					$asana = connect_asana();
					$result = $asana->createTask(array(
						'workspace' => get_workspace(),
						'name' => $month,
						'notes' => "Objectives of the month ( $month )",
						'assignee_section' 	=> $id_section,
						'assignee' 			=> get_userdata(get_current_user_id())->user_email,
						'due_on' 			=> $duedate,
					));
					$objective_id = $asana->getData()->gid;
					$asana->addProjectToTask($objective_id, get_option('_project_manager_id'));
					if ($asana->hasError()) {
						return false;
					} else {
						$task_asana = json_decode($result)->data;
						$permalink_objective = $task_asana->permalink_url;
						$objective_array = array();
						for ($k = 1; $k <= $nbre; $k++) {
							$ob = 'objective' . $k;
							$objective = htmlentities($array[$ob]);
							$resulat = $asana->createSubTask($objective_id, array(
								'name' => $objective,
								'assignee' 			=> get_userdata(get_current_user_id())->user_email,
								'due_on' 			=> $duedate,
							));
							$taskid = $asana->getData()->gid;
							$objective_array += array($taskid => array('objective' => $objective, 'status' => ''));
						}
						$objective_tab_save = array(
							'id_objective' 			=> $objective_id,
							'id_user' 				=> get_current_user_id(),
							'id_section'			=> $id_section,
							'month_section' 		=> $mois,
							'year_section'			=> $annee,
							'duedate_section'		=> $duedate,
							'objective_section'		=> serialize($objective_array),
							'section_permalink'		=> $permalink_objective
						);
						return save_objective($objective_tab_save);
					}
				}
			}
		}
	}
	if (($array['type_task'] == 'normal') || ($array['type_task'] == 'developper')) {
		return save_dev_normal_task($array);
	} else return false;
}

function page_task()
{
	$post_author = get_current_user_id();
	$download_worklog = get_option('_worklog_authorized');
	if( isset( $_GET['status'] ) ){
		if( $_GET['status'] == 'success' ){
			?> <div class="alert alert-success" role="alert">Successfuly ! </div> <?php }
	   else{ ?> <div class="alert alert-danger" role="alert">Error ! </div> <?php }
	}
	if ($post_author != 0) {
?>
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
											echo '<a class="btn btn-outline-success" href="' . download_worklog(get_current_user_id()) . '" download="' . get_userdata(get_current_user_id())->user_nicename . '.csv">Download Worklog</a>';
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
	} else {
	}
}

function get_task_calendar($id_user = null)
{
	if ($id_user == null) $tasks = get_task_();
	else $tasks = get_task_($id_user);
	?>
	<div id='jumbotron'>
		<div id='calendar_task'>
			<?php
			$days_count = date('t');
			$current_day = date('d');
			$week_day_first = date('N', mktime(0, 0, 0, date('m'), 1, date('Y')));
			$monthName = date('F', mktime(0, 0, 0, date('m'), 10));
			$month = date('m');
			$year = date('Y');
			?>
			<h3><?= $monthName ?></h3>
			<table class="table table-responsive-lg">
				<tr>
					<th>Monday</th>
					<th>Tuesday</th>
					<th>Wednesday</th>
					<th>Thursday</th>
					<th>Friday</th>
					<th style="color: red;">Saturday</th>
					<th style="color: red;">Sunday</th>
				</tr>
				<?php for ($w = 1 - $week_day_first + 1; $w <= $days_count; $w = $w + 7) : ?>
					<tr>
						<?php $counter = 0; ?>
						<?php for ($d = $w; $d <= $w + 6; $d++) : ?>
							<td style="<?php if ($counter > 4) : ?>color: red;<?php endif; ?><?php if ($current_day == $d) : ?>color:blue;font-weight:bold;<?php endif; ?>">
								<?php echo ($d > 0 ? ($d > $days_count ? '' : $d) : '') ?>
								<?php
								$exist_Task = false;
								foreach ($tasks as $task) {
									if ((date('m', strtotime($task->duedate)) == $month) && (date('Y', strtotime($task->duedate)) == $year)) {
										if (date('d', strtotime($task->duedate)) == $d) {
											$exist_Task = true;
										}
									}
								}
								if ($exist_Task) {
								?>
									<button class="btn btn-link alert alert-info p-0 m-0 get_list_event" data-toggle="modal" data-target=".<?= $year . '-' . $month . '-' . $d ?>" id="<?= $year . '-' . $month . '-' . $d ?>">Task List</button>
								<?php
								}
								?>
							</td>
							<?php
							modal_event_calendar($year . '-' . $month . '-' . $d);
							$counter++; ?>
						<?php endfor; ?>
					</tr>
				<?php endfor; ?>
			</table>
		</div>
	</div>
<?php
}

function modal_event_calendar($date_event)
{
?>
	<div class="modal fade <?= $date_event ?>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Task ( <?= $date_event ?> )</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>
	<?php
}

function get_form_template($id_template = null)
{
	if ($id_template != null) {
		$templates = get_templates_($id_template)[0]->option_value;
		$template = unserialize($templates)['parametre'];
	?>
		<div class="form-group">
			<div class="form-row">
				<label for="InputTitle">Title Template</label>
				<input type="text" name="templatetitle" id="templatetitle" class="form-control" value="<?= $template['template']['templatetitle'] ?>">
			</div>
			<div class="form-row">
				<div class="col">
					<label for="InputTitle">Main Task Title</label>
					<input type="text" name="tasktitle" id="tasktitle" class="form-control" value="<?= $template['template']['tasktitle'] ?>">
				</div>
				<div class="col">
					<label for="InputTitle">Type Task :</label>
					<select id="type_task" name="type_task" class="form-control">
						<option value="<?= $template['template']['type_task'] ?>"><?= ucfirst($template['template']['type_task']) ?></option>
						<option value="developper">Developper</option>
						<option value="normal">Normal</option>
					</select>
				</div>
			</div>
		</div>
		<label for="inputState">Task details :</label>
		<div id="champadd" class="pb-3">
			<?php
			$n = 1;
			foreach ($template['subtemplate'] as $subtemplate) {
			?>
				<div id="rm2<?= $n ?>">
					<div class="form-row pt-2">
						<div class="col">
							<input type="text" name="tasktitle<?= $n ?>" id="tasktitle<?= $n ?>" class="form-control" value="<?= $subtemplate['subtitle'] ?>">
						</div>
						<div class="col">
							<select id="categorie<?= $n ?>" name="categorie<?= $n ?>" class="form-control">
								<option value="<?= $subtemplate['categorie'] ?>"><?= ucfirst(str_replace("_", " ", $subtemplate['categorie'])) ?></option>
								<?= option_select(get_categorie_format()) ?>
							</select>
						</div>
						<div class="col-sm-1">
							<span name="remove" id="<?= $n ?>" class="btn btn-outline-danger btn_remove_template">X</span>
						</div>
					</div>
				</div>
			<?php
				$n++;
			}
			?>
			<input type="hidden" name="nbresubtask" id="nbresubtask" value="<?= $n ?>">
			<input type="hidden" name="updatetempplate_id" id="updatetempplate_id" value="<?= $id_template ?>">
		</div>
		<div class="form-group">
			<span id="addchamp" name="addchamp" class="btn btn-outline-success">+ Add SubTask</span>
		</div>
		<div class="form-group">
			<button type="submit" value="envoyer" class="btn btn-primary">UPDATE TEMPLATE</button>
		</div>
	<?php
	} else {
	?>
		<div class="form-group">
			<div class="form-row">
				<label for="InputTitle">Title Template</label>
				<input type="text" name="templatetitle" id="templatetitle" class="form-control" placeholder="Titre Template">
			</div>
			<div class="form-row">
				<div class="col">
					<label for="InputTitle">Main Task Title</label>
					<input type="text" name="tasktitle" id="tasktitle" class="form-control" placeholder="Ex: Dev">
				</div>
				<div class="col">
					<label for="InputTitle">Type Task :</label>
					<select id="type_task" name="type_task" class="form-control">
						<option value="developper">Developper</option>
						<option value="normal">Normal</option>
					</select>
				</div>
			</div>
		</div>
		<label for="inputState">Task details :</label>
		<div id="champadd" class="pb-3"></div>
		<div class="form-group">
			<span id="addchamp" name="addchamp" class="btn btn-outline-success">+ Add SubTask</span>
		</div>
		<div class="form-group">
			<button type="submit" value="envoyer" name="valideTemplate" class="btn btn-primary">SAVE TEMPLATE</button>
		</div>
	<?php
	}
	?>

<?php
}

function get_list_template()
{
	$tab_templates = get_templates_();
?>
	<table class="table table-hover table-responsive-lg">
		<thead>
			<tr>
				<th>N°</th>
				<th>Template name</th>
				<th>Main Task Title</th>
				<th>Action</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$k = 0;
			foreach ($tab_templates as $template) {
				$titles = unserialize($template->option_value);
			?>
				<tr class="alert alert-primary">
					<td><?= $k + 1 ?></td>
					<td><span class="btn btn-link template_edit" id="<?= $template->option_id ?>"><?= $titles['parametre']['template']['templatetitle'] ?></span></td>
					<td><?= $titles['parametre']['template']['tasktitle'] ?></td>
					<td>
						<span class="text-primary btn btn-link template_edit" id="<?= $template->option_id ?>">Edit</span> | <span class="text-danger btn btn-link template_remove" id="<?= $template->option_id ?>">Delete</span>
					</td>
				</tr>
			<?php
				$k++;
			}
			if ($k == 0) {
			?>
				<div class="alert alert-primary" role="alert">
					Template not found
				</div>
			<?php
			}
			?>
		</tbody>
	</table>
<?php

}

//Redirect users who arent logged in...
function login_redirect()
{
	//Current Page
	global $pagenow;

	if (!is_user_logged_in() && (is_page('orion-task') || is_page('task-evaluation') ))
		auth_redirect();
}

function add_task_form()
{
?>
	<form method="post" action="#" id="">
		<?php wp_nonce_field('create_new_task', 'verifier_new_task_form'); ?>
		<div id="">
			<div class="row text-center card-header">
				<div class="col-sm-6">
					<input type="radio" class="form-check-input" name="show" value="userTemplate" id="userTemplate">
					<label class="form-check-label" for="template">Use Template</label>
				</div>
				<div class="col-sm-6">
					<input type="radio" class="form-check-input" name="show" value="manuelTemplate" id="manuelTemplate">
					<label class="form-check-label" for="template">Create manually</label>
				</div>
			</div>
			<span id="task_success"></span>
			<input type="hidden" name="nbre" id="nbre" value="0">
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

/**
 * Table des objectifs du mois.
 * 
 * @param int $id_user
 * @param string $month
 * 
 * @return void
 */
function objective_tab( $id_user = null, $month = null ){
	if( $id_user == null ) $id_user = get_current_user_id();
	if( $month == null ) $month = date('m');
	$mois = date('F', mktime(0, 0, 0, $month, 10));
	?>
	<div id="98795" style="display:none">
		<div class="card-header">
				<h3 class="mb-0 ">Add Goals</h3>
		</div>
		<form method="post" action="#" >
			<?php wp_nonce_field('create_new_task', 'verifier_new_task_form'); ?>
			<div class="pb-3">
				<div class="row">
					<input type="hidden" class="form-control" name="type_task" id="type_task" value="objective">
					<div class="col">
						<label for="proectlabel">Select Month : </label>
						<select class="form-control" id="mois" name="mois">
							<?php for( $z=1; $z<=12; $z++ ){ ?> <option value="<?= $z ?>" <?php if( date('m') == $z ) echo 'selected'; ?> > <?= date('F', mktime(0, 0, 0, $z, 10)) .' '. date('Y') ?></option> <?php } ?>
						</select>
					</div>
				</div>
				<div id="addojectives" class="pb-3"></div>
				<input type="hidden" name="nbreobj" id="nbreobj" value="0">
				<div class="form-group">
					<span id="addobject" name="addobject" class="btn btn-outline-success">+ Add Goals</span>
				</div>
			</div>		
			<div id="hidden_submit" class="row" >
				<div class="col-sm-9"></div>
				<div class="col-sm-3"><button type="submit" class="btn btn-primary" name="validetash">Submit</button></div>
			</div>
		</form>
	</div>
	<hr>
	<div class="card">
		<div class="card-header">
			<div class="row">
				<div class="col-sm-6">
					<h3 class="mb-0 " style="text-align:left;">Goals of the <?= $mois ?></h3>
				</div>
				<div class="col-sm-6" style="text-align:right;">
					<span onclick="open_sub_templaye(98795)" class="btn btn-outline-primary"> <span id="change98795"> + Add Goals</span></span>
				</div>
			</div>
		</div>
		<?php 
		$objectives_array = get_objective_of_month( $id_user, $month );
		if( $objectives_array != null ){
				?>
				<div class="card-body">
					<table class="table table-hover">
						<thead>
							<tr>
								<th colspan="2">Goals</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$subobjective = unserialize( $objectives_array->objective_section );
							foreach ($subobjective as $objective) {
							?>
								<tr>
									<td colspan="2"><?= $objective['objective'] ?></td>
								</tr>
								<?php 
							} 
							?>
							<tr>
								<th>Due Date : <?= $objectives_array->duedate_section ?> </th>
								<th>Click <a href="<?= $objectives_array->section_permalink ?>">here</a> for details</th>
							</tr>
						</tbody>
					</table>
				</div>
				<?php

			}else{
				?>
				<div class="alert alert-primary" role="alert">
					No tasks for this project at the moment
				</div>
				<?php
			}
			?>	
		</div>
	<?php
	//print_r( $objectives );
}

function get_user_task()
{
	$user_current_tasks = get_user_current_project(get_current_user_id());
	if ($user_current_tasks != null) {
		$i = 1;
	?>
		<div id="accord">
			<?php
			foreach ($user_current_tasks as $project) {
			?>
				<div class="card">
					<div class="card-header" id="heading<?= $project['id'] . $project['title'] ?>" data-toggle="collapse" data-target="#collapse<?= $project['id'] . $project['title'] ?>" aria-expanded="true" aria-controls="collapse<?= $project['id'] . $project['title'] ?>">
						<h3 class="mb-0 ">
							<button class="btn btn-link">
								<?= $project['title'] ?>
							</button>
						</h3>
					</div>
					<div id="collapse<?= $project['id'] . $project['title'] ?>" class="collapse <?php if ($i == 1) echo 'show'; ?>" aria-labelledby="heading<?= $project['id'] . $project['title'] ?>" data-parent="#accord">
						<div class="card-body">
							<table class="table table-hover">
								<thead>
									<tr>
										<th>N°</th>
										<th>Task title</th>
										<th>Due Date</th>
										<th>Status</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$k = 0;
									foreach (get_task_() as $task) {
										if ($project['id'] == $task->project_id && $task->assigne == get_current_user_id()) {
											$status = get_task_status($task->id);
									?>
											<tr>
												<td><?= $k + 1 ?></td>
												<td><a href="<?= $task->permalink_url ?>" class="btn-link"><?= $task->title ?></a></td>
												<td class="alert alert-primary"><?= $task->duedate ?></td>
												<td class="<?php if ($status == 'Not Completed' || $status == 'Completed Before Date') echo 'text-danger';
															elseif ($status == 'In Progess' || $status == 'Completed') echo 'text-success';
															else echo 'text-warning';  ?>"><?= $status ?></td>
											</tr>
										<?php
											$k++;
										}
									}
									if ($k == 0) {
										?>
										<div class="alert alert-primary" role="alert">
											No tasks for this project at the moment
										</div>
									<?php
									}
									?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			<?php
				$i++;
			}
			?>
		</div>
	<?php
	} else {
	?>
		<div class="alert alert-primary" role="alert">
			You have no tasks at the moment
		</div>
	<?php
	}
}

/**
 * Short code de la page public
 * 
 * @return void
 */
function orion_task_shortcode()
{
	$var = wp_nonce_field('orion_task_manager', 'task_manager');
	return page_task();
}

/**
 * Short code de la page public
 * 
 * @return void
 */
function orion_task_evaluation_shortcode()
{
	$var = wp_nonce_field('orion_task_manager', 'task_manager');
	return evaluator_page();
}
function taches_tab()
{
	sync_tasks();
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
							<h3><span id="template_label">List Template</span> <button class="btn btn-outline-success btn_list_task" id="template_btn_add">Add New Template</button> </h3>
							<div id="add_success"></div>
							<hr>
							<form action="" method="post" id="create_template">
							</form>
							<div id="template_card">
								<?= get_list_template(); //get_form_template() 
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
					<div class="card-body">
						<div>
							<h3>New Project</h3>
							<div id="add_success1"></div>
							<hr>
							<form id="create_new_projet" name="create_new_projet" action="" method="post">
								<div class="form-group">
									<label for="InputTitle">Project Name </label>
									<input type="text" name="titleproject" id="titleproject" class="form-control" placeholder="Project Name">
								</div>
								<div class="form-group">
									<textarea class="form-control" id="description" name="description" rows="3" placeholder="Description ..."></textarea>
								</div>
								<div class="form-group">
									<div class="form-row">
										<div class="col">
											<label for="InputTitle">Slug </label>
											<input type="text" name="slug" id="slug" class="form-control" placeholder="Slug">
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

function get_all_categories()
{
	global $wpdb;
	$table = $wpdb->prefix . 'categories';
	$sql = "SELECT * FROM $table";
	return $wpdb->get_results($sql);
}

function get_categories_()
{
	$all_categories = get_all_categories();
	foreach ($all_categories as $categorie) {
		if ($categorie->categories_key == 'implementation' || $categorie->categories_key == 'test' || $categorie->categories_key == 'revue' || $categorie->categories_key == 'integration') {
	?>
			<div class="form-row pt-2">
				<div class="col-sm-6"><input type="text" readonly value="<?= $categorie->categories_name ?>" class="form-control text-dark"></div>
				<div class="col-sm-6"><input type="text" readonly value="<?= $categorie->categories_key ?>" class="form-control text-dark"></div>
			</div>
		<?php
		} else {
		?>
			<div class="form-row pt-2">
				<div class="col-sm-6"><input type="text" id="name<?= $categorie->id ?>" readonly value="<?= $categorie->categories_name ?>" class="form-control text-dark"></div>
				<div class="col-sm-4"><input type="text" id="key<?= $categorie->id ?>" readonly value="<?= $categorie->categories_key ?>" class="form-control text-dark"></div>
				<div class="col-sm-1 btn btn-primary edit_categorie" id="<?= $categorie->id ?>"> <span id="edit_<?= $categorie->id ?>">Edit</span> </div>
				<div class="col-sm-1 btn btn-danger delete_categorie" id="<?= $categorie->id ?>">Delete</div>
			</div>
	<?php
		}
	}
	?>
	<form action="" method="post" id="create_categories">
		<label for="inputState">Other Categories :</label>
		<div id="champadd" class="pb-3"></div>
		<div class="form-group">
			<span id="addcategorie" name="addcategorie" class="btn btn-outline-success">+ Add Categories</span>
		</div>
		<div class="form-group">
			<button type="submit" value="envoyer" name="valideTemplate" class="btn btn-primary">Save Categorie</button>
		</div>
	</form>
<?php
}

function worklog_tab()
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

function get_task_title()
{
	$templates = get_templates_();
	$title_array = array();
	foreach ($templates as $template) {
		$titles = unserialize($template->option_value)['parametre'];
		foreach ($titles as $title) {
			$title_array += array(strtolower($title['template']['tasktitle']) => $title['template']['tasktitle']);
			if (isset($title['subtemplate'])) {
				foreach ($title['subtemplate'] as $subtemplate) {
					$title_array += array(strtolower($subtemplate['subtitle']) => $subtemplate['subtitle']);
				}
			}
		}
	}
	return $title_array;
}

function get_email_task_tab($id_template = null)
{
	$vrai = false;
	if ($id_template != null) {
		$template_email = get_all_email($id_template)[0];
		$vrai = true;
	}
?>
	<form id="email_send_form" action="" method="post">
		<div class="form-row">
			<div class="form-group col-md-6">
				<label for="tasktitle">Type Task</label>
				<select id="task_name" name="task_name" class="form-control task_option">
					<option value="developper" <?php if ($vrai) {
													if ($template_email->type_task == 'developper') echo 'selected';
												} ?>>Developper</option>
					<option value="normal" <?php if ($vrai) {
												if ($template_email->type_task == 'normal') echo 'selected';
											} ?>>Normal</option>
				</select>
			</div>
			<div class="form-group col-md-6">
				<label for="subject_email">Subject</label>
				<input type="text" class="form-control" id="subject_mail" value="<?php if ($vrai) echo $template_email->subject;
																					else echo 'Evaluation de developper';  ?>">
			</div>
		</div>
		<div class="form-group">
			<label for="content_mail">Email content</label>
			<textarea class="form-control" id="content_mail" rows="4" placeholder="Content ..."><?php if ($vrai) echo $template_email->content; ?></textarea>
			<small id="contentHelp">Click <br>
				<span class="btn-link" id="project_name_msg">{{project_name}}</span> to add the project name<br>
				<span class="btn-link" id="task_name_msg">{{task_name}}</span> to add the task name <br>
				<span class="btn-link" id="task_link_msg">{{task_link }}</span> to add the link to the task <br>
				<span class="btn-link" id="form_link_msg">{{form_link}}</span> to add the link to the form <br>
				to the content of the form
			</small>
		</div>
		<?php
		if ($vrai) echo '<input type="hidden" name="id_template" id="id_template" value="' . $id_template . '">';
		?>
		<button type="submit" class="btn btn-outline-primary"><?php if ($vrai) echo 'Update Mail Template';
																else echo 'Save Mail Template'; ?></button>
	</form>
	<div class="pt-2">
		<form method="post" action="" id="test_send_mail">
			<div class="input-group mb-3">
				<input class="control-form" id="input_email" type="email">
				<div class="input-group-prepend">
					<input class="btn btn-outline-success" for="input_email" type="submit" value="Test">
				</div>
			</div>
		</form>
	</div>
<?php
}


function get_project_manager_tab(){
	?>
	<div class="card-bdy">
		<span id="add_success_id"></span>
		<form id="project_manager_id" method="post" action="">
			<label for="project_manager_id">Project manager evaluation</label>
			<div class="form-row">
				<div class="col-sm-8">
					<input type="text" name="id_project_manager" id="id_project_manager" class="form-control" placeholder="Project Id" value="<?= get_option( '_project_manager_id' ) ?>">
				</div>
				<div class="col">
					<button type="submit" class="btn btn-outline-primary mb-2">UPDATE</button>
				</div>
			</div>
		</form>
		<hr>
		<form id="add_project_manager" method="post" action="">
			<label for="add_project_manager">Select New Project Manager</label>
			<div class="form-row">
				<div class="col-sm-8">
					<select id="projectmanager" name="projectmanager" class="form-control">
						<option value="">Choose...</option>
						<?= option_select(get_all_users('name')) ?>
					</select>
				</div>
				<div class="col">
					<button type="submit" class="btn btn-outline-primary mb-2">SAVE</button>
				</div>
			</div>
		</form>
	</div>
	<?php
}

function evaluation_tab()
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

function create_task_criteria()
{
	$get_criteria = get_option('_evaluation_criterias');
	$criterias =  unserialize($get_criteria);
?>
	<h5>Define the evaluation criteria</h5>
	<span id="success_criteria_add"></span>
	<form id="evaluation_criteria" action="" method="post">
		<div class="row">
			<div id="bg11111" style="background:white" class="col-sm-6 alert alert-info btn-link" onclick="open_sub_templaye(11111)">
				<h6>Developpment</h6>
			</div>
			<div id="bg22222" style="background:" class="col-sm-6 alert alert-info btn-link" onclick="open_sub_templaye(22222)">
				<h6>Normal</h6>
			</div>
		</div>
		<div id="11111" class="row" style="display:block">
			<h5>Developpment Criteria</h5>
			<div>
				<?php $u = 1;
				foreach ($criterias['developper'] as $criteria_dev) {
				?>
					<div id="rmu2<?= $u ?>">
						<div class="form-row pt-2">
							<div class="col-sm-11">
								<div class="row">
									<div class="col-sm-3">
										<div class="form-group">
											<input type="text" class="form-control" id="critere1_<?= $u ?>" value="<?= $criteria_dev['criteria'] ?>">
										</div>
									</div>
									<div class="col-sm-2 p-0 m-0">
										<div class="form-group">
											<input type="number" min="0" max="100" class="form-control" id="note1_<?= $u ?>" value="<?= $criteria_dev['note'] ?>">
										</div>
									</div>
									<div class="col-sm-7">
										<div class="form-group">
											<textarea class="form-control" id="description1_<?= $u ?>" rows="1" placeholder="Description ..."><?= $criteria_dev['description'] ?></textarea>
										</div>
									</div>
								</div>
							</div>
							<div class="col-sm-1">
								<span name="remove" id="<?= $u ?>" class="btn btn-outline-danger btn_remove_criteria1">X</span>
							</div>
						</div>
					</div>
				<?php
					$u++;
				}
				?>
				<input type="hidden" id="nbre1" name="nbre1" value="<?= $u ?>">
			</div>
			<div id="criteriaadd1" class="pb-3"></div>
			<div class="form-group">
				<span id="addcriteria1" name="addcriteria1" class="btn btn-outline-success">+ Add Criteria</span>
			</div>
		</div>
		<div id="22222" class="row" style="display:none">
			<h5>Normal Criteria</h5>
			<div>
				<?php $v = 1;
				foreach ($criterias['normal'] as $criteria_normal) {
				?>
					<div id="rmv2<?= $v ?>">
						<div class="form-row pt-2">
							<div class="col-sm-11">
								<div class="row">
									<div class="col-sm-3">
										<div class="form-group">
											<input type="text" class="form-control" id="critere2_<?= $v ?>" value="<?= $criteria_normal['criteria'] ?>">
										</div>
									</div>
									<div class="col-sm-2 p-0 m-0">
										<div class="form-group">
											<input type="number" min="0" max="100" class="form-control" id="note2_<?= $v ?>" value="<?= $criteria_normal['note'] ?>">
										</div>
									</div>
									<div class="col-sm-7">
										<div class="form-group">
											<textarea class="form-control" id="description2_<?= $v ?>" rows="1" placeholder="Description ..."><?= $criteria_normal['description'] ?></textarea>
										</div>
									</div>
								</div>
							</div>
							<div class="col-sm-1">
								<span name="remove" id="<?= $v ?>" class="btn btn-outline-danger btn_remove_criteria2">X</span>
							</div>
						</div>
					</div>
				<?php $v++;
				}
				?>
				<input type="hidden" id="nbre2" name="nbre2" value="<?= $v ?>">
			</div>
			<div id="criteriaadd2" class="pb-3"></div>
			<div class="form-group">
				<span id="addcriteria2" name="addcriteria2" class="btn btn-outline-success">+ Add Criteria</span>
			</div>
		</div>
		<div class="form-group">
			<button type="submit" value="envoyer" class="btn btn-primary">UPDATE CRITERIA</button>
		</div>
	</form>
<?php
}

function list_email_sending()
{
	$emails = get_all_email();
?>
	<table class="table table-hover table-responsive-lg">
		<thead>
			<tr>
				<th>N°</th>
				<th>Subject</th>
				<th>Type Task</th>
				<th>Action</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$k = 0;
			foreach ($emails as $email) {
			?>
				<tr class="alert alert-primary">
					<td><?= $k + 1 ?></td>
					<td id="<?= $email->id ?>"><span class="btn btn-link email_edit p-0 m-0" id="<?= $email->id ?>"><?= $email->subject ?></span></td>
					<td><?= ucfirst($email->type_task) ?></td>
					<td>
						<span class="text-primary btn btn-link email_edit m-0 p-0" id="<?= $email->id ?>">Edit</span><span class="text-danger btn btn-link email_remove m-0 p-0" id="<?= $email->id ?>">Delete</span>
					</td>
				</tr>
			<?php
				$k++;
			}
			if ($k == 0) {
			?>
				<div class="alert alert-primary" role="alert">
					Template not found
				</div>
			<?php
			}
			?>
		</tbody>
	</table>
<?php

}

function active_tab()
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

function get_project_section(int $id_project)
{
	$sections = array();
	foreach (get_all_sections($id_project) as $section) {
		$sections += array($section->id => $section->section_name);
	}
	return $sections;
}
function get_dependancies($array)
{
	$tab = $array['parametre']['subtemplate'];
	$depend = array();
}

function get_categorie_format()
{
	$cats = get_all_categories();
	$categorie = array();
	foreach ($cats as $cat) {
		$categorie += array($cat->categories_key => $cat->categories_name);
	}
	return $categorie;
}

function get_form(array $array, $istemplate)
{
	$template = $array['parametre']['template'];
	?>
		<div class="pb-3">
			<?php
			if ($istemplate) {
			?>
				<div class="form-group">
					<label for="title">Titre</label>
					<input type="text" class="form-control" readonly name="sub_title" id="sub_title" value="<?= $template['tasktitle']  ?>">
				</div>
			<?php
			} else {
			?>
				<div class="row">
					<div class="col">
						<label for="title">Titre</label>
						<input type="text" class="form-control" name="title" id="title" value="<?= $template['tasktitle']  ?>">
						<input type="hidden" class="form-control" name="type_task" id="type_task" value="<?= $template['type_task']  ?>">
					</div>
					<div class="col">
						<label for="proectlabel" id="label1" style="color:red">Select Project : </label>
						<select class="form-control project projectSection" id="project" name="project">
							<?= option_select(array('' => 'Choose project ...') + get_project_manger_project()) ?>
						</select>
					</div>
					<div class="col">
						<label for="proectlabel">Select Section : </label>
						<select class="form-control assign_option" id="project_section" name="project_section">
							<option value="" selected></option>
						</select>
					</div>
				</div>
			<?php
			}
			?>
			<div class="form-group">
				<label for="exampleFormControlTextarea1">Description</label>
				<textarea class="form-control" id="<?php if ($istemplate) echo 'sub_'  ?>description" name="<?php if ($istemplate) echo 'sub_'  ?>description" placeholder="Description..." rows="3"></textarea>
			</div>
			<div class="row">
				<div class="col">
					<label for="duedate">Due Date</label>
					<input type="date" name="<?php if ($istemplate) echo 'sub_'  ?>duedate" class="form-control" id="<?php if ($istemplate) echo 'sub_'  ?>duedate" aria-describedby="duedate">
				</div>
				<?php if ($istemplate){
					?>
					<div class="col">
						<label for="categorie">Categorie</label>
						<select class="form-control" id="sub_categorie" name="sub_categorie">
							<?= option_select(get_categorie_format()) ?>
						</select>
					</div>
					<?php
				}  ?>
				<div class="col">
					<label for="assigne">Assigne : </label>
					<select class="form-control assign_option" id="<?php if ($istemplate) echo 'sub_'  ?>assign" name="<?php if ($istemplate) echo 'sub_'  ?>assign"><option value="" selected></option></select>
				</div>
			</div>
		</div>
		<?php
		if (isset($array['parametre']['subtemplate'])) {
			$tab = $array['parametre']['subtemplate'];
			$j = 1;

			foreach ($tab as $subtemplate) {
		?>
				<div class="row pl-5 pr-5 pb-4">
					<span onclick="open_sub_templaye(<?= $j ?>)" class="btn btn-outline-primary"><span id="change<?= $j ?>"> + </span> <?= $subtemplate['subtitle']  ?> </span>
				</div>
				<div id="<?= $j ?>" style="display:none;" class="pl-5 pr-5 pb-3">
					<div class="row">
						<div class="col">
							<label for="title">Titre</label>
							<input type="text" class="form-control" readonly name="<?php if ($istemplate) echo 'sub_'  ?>title<?= $j ?>" id="<?php if ($istemplate) echo 'sub_'  ?>title<?= $j ?>" value="<?= $subtemplate['subtitle']  ?>">
							<input type="hidden" class="form-control" readonly name="<?php if ($istemplate) echo 'sub_'  ?>categorie<?= $j ?>" id="<?php if ($istemplate) echo 'sub_'  ?>categorie<?= $j ?>" value="<?= $subtemplate['categorie']  ?>">
						</div>
					</div>
					<div class="form-group">
						<label for="exampleFormControlTextarea1">Description</label>
						<textarea class="form-control" id="<?php if ($istemplate) echo 'sub_'  ?>description<?= $j ?>" name="<?php if ($istemplate) echo 'sub_'  ?>description<?= $j ?>" placeholder="Description..." rows="3"></textarea>
					</div>
					<div class="row">
						<div class="col">
							<label for="assigne">Assigne : </label>
							<select class="form-control assign_option" id="<?php if ($istemplate) echo 'sub_'  ?>assign<?= $j ?>" name="<?php if ($istemplate) echo 'sub_'  ?>assign<?= $j ?>"><option value="" selected></option></select>
						</div>
						<div class="col">
							<label for="duedate">Due Date</label>
							<input type="date" name="<?php if ($istemplate) echo 'sub_'  ?>duedate<?= $j ?>" class="form-control" id="<?php if ($istemplate) echo 'sub_'  ?>duedate<?= $j ?>" aria-describedby="duedate">
						</div>
					</div>
				</div>
			<?php
				$j++;
			}
			?>
			<input type="hidden" name="nbrechamp" class="form-control" id="nbrechamp" value="<?= $j ?>">
		<?php
		}
}
function get_first_choose($type, $istemplate = false)
{
	if ($type == 'usertemplate') {
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
	if ($type == 'manueltemplate') {
	?>
		<div class="pb-3">
			<?php
			if ($istemplate) {
			?>
				<div class="form-group">
					<label for="title">Titre</label>
					<input type="text" class="form-control" name="manuel_title" required id="manuel_title">
				</div>
			<?php
			} else {
			?>
				<div class="row">
					<div class="col">
						<label for="title">Title Task : </label>
						<input type="text" class="form-control" required name="title" id="title">
					</div>
					<div class="col">
						<label for="type_task">Type Task : </label>
						<select class="form-control" id="type_task" name="type_task">
							<option value="developper">Developper</option>
							<option value="normal">Normal</option>
						</select>
					</div>
				</div>
				<div class="row">
					<div class="col">
						<label for="proectlabel" id="label1" style="color:red">Select Project : </label>
						<select class="form-control project projectSection" id="project" name="project">
							<?= option_select(array('' => 'Choose project ...') + get_project_manger_project()) ?>
						</select>
					</div>
					<div class="col">
						<label for="proectlabel">Select Section : </label>
						<select class="form-control assign_option" id="project_section" name="project_section">
						<option value="" selected></option>
						</select>
					</div>
				</div>
			<?php
			}
			?>
			<div class="form-group">
				<label for="exampleFormControlTextarea1">Description</label>
				<textarea class="form-control" id="<?php if ($istemplate) echo 'manuel_' ?>description" name="<?php if ($istemplate) echo 'manuel_' ?>description" placeholder="Description..." rows="3"></textarea>
			</div>
			<div class="row">
				<div class="col">
					<label for="assigne">Assigne : </label>
					<select class="form-control assign_option" id="<?php if ($istemplate) echo 'manuel_' ?>assign" name="<?php if ($istemplate) echo 'manuel_' ?>assign"><option value="" selected></option></select>
				</div>
				<div class="col">
					<label for="duedate">Due Date</label>
					<input type="datetime-local" name="<?php if ($istemplate) echo 'manuel_' ?>duedate" class="form-control" id="<?php if ($istemplate) echo 'manuel_' ?>duedate" aria-describedby="duedate">
				</div>
				<div class="col">
					<label for="categorie">Categorie</label>
					<select class="form-control" id="<?php if ($istemplate) echo 'manuel_'; ?>categorie" name="<?php if ($istemplate) echo 'manuel_'; ?>categorie">
						<?= option_select(array( '' => 'None' ) + get_categorie_format()) ?>
					</select>
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
function get_template_form(int $id, $istemplate = false)
{
	$all_templates = get_templates_();
	foreach ($all_templates as $templates) {
		if ($templates->option_id == $id)
			$template = $templates;
	}
	$templates_form = unserialize($template->option_value);
	get_form($templates_form, $istemplate);
}

function settings_function()
{
	$action = htmlspecialchars($_POST['action']);
	if ($action == 'get_user_role') {
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
		print_r($_POST);
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
		echo sync_new_project($post);
	}
	if ($action == 'create_template') {
		if (isset($_POST['updatetempplate_id']) && !empty($_POST['updatetempplate_id'])) {
			$template_id = htmlentities($_POST['updatetempplate_id']);
			$send = array_diff($_POST, array('action' => 'create_template', 'updatetempplate_id' => $template_id));
		} else {
			$send = array_diff($_POST, array('action' => 'create_template'));
			$template_id = '';
		}
		$data = wp_unslash($send);
		echo save_new_templates($template_id, $data);
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
	if ($action == 'get_option_section') {
		$id_project = htmlentities($_POST['project_id']);
		if (empty($id_project))
			echo '';
		else
			echo option_select(get_project_section($id_project));
	}
	if ($action == 'get_template_choose') {
		$id_template = htmlentities($_POST['template_id']);
		$istemplate = htmlentities($_POST['istemplate']);
		if (!empty($id_template)) {
			if ($istemplate == 'yes') echo get_template_form($id_template, true);
			else echo get_template_form($id_template);
		} else {
			echo '';
		}
	}
	if ($action == 'get_first_form') {
		$type = htmlentities($_POST['type']);
		$istemplate = htmlentities($_POST['istemplate']);
		if ($istemplate == 'yes')
			echo get_first_choose($type, true);
		else
			echo get_first_choose($type);
	}
	if ($action == 'create_new_task') {
		$send = array_diff($_POST, array('action' => 'create_new_task'));
		$data = wp_unslash($send);
		traite_task_and_save($data);
		//echo (save_new_task($data));
	}
	if ($action == 'get_template_card') {
		if ($_POST['valeur'] == 'template_btn_list')
			echo get_list_template();
		if ($_POST['valeur'] == 'template_btn_add')
			echo get_form_template();
	}
	if ($action == 'delete_template_') {
		$id_template = htmlentities($_POST['id_template']);
		delete_template($id_template);
		echo get_list_template();
	}
	if ($action == 'delete_email_') {
		$id_template = htmlentities($_POST['id_template']);
		delete_email($id_template);
		echo list_email_sending();
	}
	if ($action == 'worklog_update') {
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
		echo worklog_tab();
	}
	if ($action == 'get_calendar') {
		$user_id = htmlentities($_POST['id_user']);
		if (empty($user_id)) echo get_task_calendar();
		else echo get_task_calendar($user_id);
	}
	if ($action == 'update_template') {
		$id_template = htmlentities($_POST['id_template']);
		echo get_form_template($id_template);
	}
	if ($action == 'save_categories') {
		if (isset($_POST['get_categorie'])) {
			echo option_select(get_categorie_format());
		} else {
			$send = array_diff($_POST, array('action' => 'save_categories'));
			$data = wp_unslash($send);
			save_new_categories($data);
			echo get_categories_();
		}
	}
	if ($action == 'save_mail_form') {
		$update = htmlentities($_POST['update']);
		$id_template_email = htmlentities($_POST['id_template']);
		$send = array_diff($_POST, array('action' => 'save_mail_form', 'update' => $update, 'id_template' => $id_template_email));
		$data = wp_unslash($send);
		if ($update  === 'true')
			$ok = save_new_mail_form($data, $id_template_email);
		else
			$ok = save_new_mail_form($data);
		if ($ok)
			echo list_email_sending();
		else
			echo 'false';
	}
	if ($action == 'get_email_card') {
		$type = htmlentities($_POST['valeur']);
		if ($type == 'list_email')
			echo list_email_sending();
		else
			echo get_email_task_tab();
	}
	if ($action == 'save_criteria_evaluation') {
		$send = array_diff($_POST, array('action' => 'save_criteria_evaluation'));
		$data = wp_unslash($send);
		update_option('_evaluation_criterias', serialize($data['valeur']));
		echo  create_task_criteria();
	}
	if ($action == 'edit_template_mail') {
		$id_template_mail = htmlentities($_POST['id_template_mail']);
		if (!empty($id_template_mail))
			echo get_email_task_tab($id_template_mail);
		else
			echo get_email_task_tab();
	}
	if ($action == 'update_categorie_') {
		$id_categorie = htmlentities($_POST['id_categorie']);
		$valeur = htmlentities($_POST['valeur']);
		save_new_categories($valeur, $id_categorie);
		echo get_categories_();
	}
	if ($action == 'delete_categorie_') {
		$id_categorie = htmlentities($_POST['id_categorie']);
		$retour = delete_categories_($id_categorie);
		if ($retour)
			echo get_categories_();
		else
			echo 'error';
	}
	if ($action == 'send_mail_test') {

		$type_task = htmlentities($_POST['type_task']);
		$subject = htmlentities($_POST['subject']);
		$content = htmlentities($_POST['content']);
		$destinataire = htmlentities($_POST['email']);
		$id_task = 1202081187468663;
		$msg = content_msg($id_task, $type_task, $content);

		$retour = mail_sending_form($destinataire, $subject, $msg);
		echo $retour;
	}
	if( $action == 'synchronisation_time' ){
		if( isset( $_POST['projectmanager'] ) ){
			$projectmanager = htmlentities( $_POST['projectmanager'] );
			$output = save_objective_section( $projectmanager );
			print_r($output);
		}
		if( isset( $_POST['id_project_manager'] ) ){
			$id_project_manager = htmlentities( $_POST['id_project_manager'] );
			$output = update_option('_project_manager_id', $id_project_manager);
		}
		if( isset( $_POST['sync_time'] ) ){
			$time = htmlentities( $_POST['sync_time'] );
			$output = update_option('_synchronisation_time', $time);
		}else echo false;
		//echo $output;
	}
	wp_die();
}
