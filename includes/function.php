<?php
require 'file_modele/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
require_once('class-asana-orion-task.php');
require_once('class-evaluation-orion-task.php');

if (isset($_POST['tokens']) && !empty($_POST['tokens'])) {
	$data_post   = wp_unslash($_POST['tokens']);
	update_option('_asana_access_token', $data_post);
}

if( isset( $_POST['link_file'] ) ){
	$file_name = sanitize_text_field( $_POST['file_name'] );
	$url_file = sanitize_text_field( $_POST['link_file'] );
	$reader = IOFactory::createReader('Xlsx');
	$spreadsheet = $reader->load($url_file);

	header('Content-Type: application/vnd-openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="'. $file_name .'"');

	$writer = new Xlsx($spreadsheet);
	$writer->save('php://output');
	exit;
}

/**
 * Convertir un tableau en un select
 * @param array @array
 * @param null|string $default
 */
function option_select($array, $default=null)
{
	$option = '';
	if( $default != null ){
		foreach ($array as $key => $value) {
			if( is_array($default) ){
				if( in_array($key, $default) ) $option .= "<option value='$key' selected >". esc_html($value) . "</option>";
				else $option .= "<option value='$key'>". esc_html($value) . "</option>";
			}else{
				if( $key == $default ) $option .= "<option value='$key' selected >". esc_html($value) . "</option>";
				else $option .= "<option value='$key'>". esc_html($value) . "</option>";
			}
		}
	}else{
		foreach ($array as $key => $value) {
			$option .= "<option value='$key'>". esc_html($value) . "</option>";
		}
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
		return $wpdb->update($table, array('evaluation' => serialize($array), 'evaluation_date' => date('m-Y')), array('id_task' => $task_id), $format);
	} else {
		return false;
	}
}

/**
 * Save or Update new templates in bdd
 * @param array $data
 * @param int|null $template_id
 */
function save_new_templates(array $data, $template_id = '')
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

/**
 * Delete template in bdd
 * @param int $id_template
 */
function delete_template($id_template, $type)
{
	global $wpdb;
	if( $type == 'task' ){
		$ok = $wpdb->delete($wpdb->options, array('option_id' => $id_template));
	}else{
		$table = $wpdb->prefix . 'mails';
		$ok = $wpdb->delete($table, array('id' => $id_template));
	}
	return $ok;
}

/**
 * Fonction permettant de vider les tables après mise à jour de l'access token
 * 
 * @return bool
 */
function delete_all_(){
	global $wpdb;
	$out = array();
	$tables = array(
		$wpdb->prefix . 'worklog', 
		$wpdb->prefix . 'subtask',
		$wpdb->prefix . 'task',
		$wpdb->prefix . 'objectives',
		$wpdb->prefix . 'categories',
		$wpdb->prefix . 'sections',
		$wpdb->prefix . 'project'
	);
	foreach( $tables as $table ){
		$out = array( $wpdb->query("DELETE FROM $table") );
	}
	if( in_array(false, $out) ) return false;
	else return true;
}

/**
 * Save and update project
 * @param array $data
 * @param int|null $project_id
 */
function save_project($data, $project_id=null)
{
	global $wpdb;
	$table = $wpdb->prefix . 'project';
	if( $project_id != null ){
		$format = array('%s', '%s', '%s', '%s', '%d');
		return $wpdb->update($table, $data, array('id' => $project_id), $format);
	}else{
		$format = array('%d', '%s', '%s', '%s', '%s', '%d', '%s');
		return $wpdb->insert($table, $data, $format);
	}
}

/**
 * Archiver un project
 * @param int $projectId
 */
function archiveProject( $projectId, $archive ){
	global $wpdb;
	$table = $wpdb->prefix . 'project';
	return $wpdb->update($table, array('archive'=>$archive), array('id' => $projectId));
}

function getProjectStatus($projectId){
	return get_project_($projectId)->archive;
}

/**
 * Save project section in bdd
 * @param array $data
 */
function save_new_sections($data)
{
	global $wpdb;
	$table = $wpdb->prefix . 'sections';
	$format = array('%d', '%d', '%s');
	return $wpdb->insert($table, $data, $format);
}

/**
 * Save or Update categorie in bdd
 * @param array|string $datas
 * @param int|null $id
 * @param bool $syn
 */
function save_new_categories($datas, $id = null, $syn=false)
{
	global $wpdb;
	$table = $wpdb->prefix . 'categories';
	$format = array('%d', '%s', '%s', '%d');
	if ($id == null) {
		if( $syn ){
			$wpdb->insert($table, $datas, $format);
		}else{
			foreach ($datas['valeur'] as $data) {
				$form = str_replace(" ", "_", strtolower($data['categorie']));
				$id_categorie = create_tag( $form );
				if( $id_categorie != null ){
					$data_format = array('id' => $id_categorie, 'categories_key' => $form, 'categories_name' => $data['categorie'], 'evaluate' => $data['evaluate'] );
					$wpdb->insert($table, $data_format, $format);
				}
			}
		}
	} else {
		$format = array('%s');
		$d_format = array('categories_name' => $datas);
		$wpdb->update($table, $d_format, array('id' => $id), $format);
	}
	return;
}

/**
 * Update categorie evaluation status
 */
function updateEvaluateCategorie( $categorieId ){
	global $wpdb;
	$table = $wpdb->prefix . 'categories';
	$categorie = get_categories_task( $categorieId );
	if( $categorie->evaluate ) $data = array( 'evaluate'=> 0 );
	else $data = array( 'evaluate'=> 1 );
	return $wpdb->update($table, $data, array('id' => $categorieId));
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

function updateTaskFromAsana( $taskid, $userId, $duedate ){
	global $wpdb;
	$table = $wpdb->prefix . 'task';
	$data = array( 'assigne' => $userId, 'duedate' => $duedate );
	return $wpdb->update($table, $data, array( 'id' => $taskid ));
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

/**
 * Delete categorie in bdd
 * @param int $id
 */
function delete_categories_($id)
{
	global $wpdb;
	// Delte from asana
	$asana = connect_asana();
	$asana->deleteTag($id);
	$table = $wpdb->prefix . 'categories';
	return $wpdb->delete($table, array('id' => $id), array('%d'));
}

/**
 * Save ou update mail template in bdd
 * @param array $data
 * @param int|null $id_template
 */
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

/**
 * Save task template in bdd
 * @param array $data
 * @param array $worklog
 * @param array|null $subarray
 */
function save_new_task(array $data, array $worklog, $subarray = null)
{
	global $wpdb;
	$tabletask = $wpdb->prefix . 'task';
	$tableworklog = $wpdb->prefix . 'worklog';

	$formatworklog = array('%d', '%s', '%s', '%s', '%s', '%s');
	$formattask = array('%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s');

	$ok = $wpdb->insert($tabletask, $data, $formattask);
	$wpdb->insert($tableworklog, $worklog, $formatworklog);

	if ($subarray != null) {
		$tablesubtask = $wpdb->prefix . 'subtask';
		$formatsubtask = array('%d', '%d');
		$ok = $wpdb->insert($tablesubtask, $subarray, $formatsubtask);
	}
	return $ok;
}

/**
 * Save cp objectives in bdd
 * @param array $data
 */
function save_objective( $data ){
	global $wpdb;
	$table = $wpdb->prefix . 'objectives';
	$format = array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s');
	return $wpdb->insert($table, $data, $format);
}

/**
 * Update objective in bdd
 * @param array $data
 * @param int $id_objective
 */
function update_objective( $data, $id_objective ){
	global $wpdb;
	$table = $wpdb->prefix . 'objectives';
	$format = array( '%s' );
	$wpdb->update($table, array('objective_section' => serialize( $data )), array('id_objective' => $id_objective), $format);
}

/**
 * Ensemble des projects ou project spécifique
 * @param int $id_project
 */
function get_project_( $id_project = null )
{
	global $wpdb;
	$table = $wpdb->prefix . 'project';
	if( $id_project == null ){
		$sql = "SELECT * FROM $table WHERE id != " . get_option('_project_manager_id');
		return $wpdb->get_results($sql);
	}else if($id_project == -1){
		$sql = "SELECT * FROM $table WHERE id != " . get_option('_project_manager_id') . " AND archive != " . true;
		return $wpdb->get_results($sql);
	}
	else{
		$sql = "SELECT * FROM $table WHERE id = $id_project";
		return $wpdb->get_row($sql);
	}
}

/**
 * Update project collaborator
 * @param int $projectId
 * @param string $collaborators
 * 
 * @return bool
 */
function editCollaborateur( $projectId, $collaborators ){
	global $wpdb;
	$table = $wpdb->prefix . 'project';
	return $wpdb->update($table, array('collaborator' => $collaborators), array('id' => $projectId));
}

/**
 * Vérifier si la section d'un projet exist
 * @param string $section_name
 * @param int|null $project_id
 */
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

/**
 * Récupérer les worklogs
 * @param string $column
 * @param string $value
 */
function get_all_worklog( $column=null, $value=null )
{
	global $wpdb;
	$table = $wpdb->prefix . 'worklog';
	if( $column != null AND $value != null )
		$sql = "SELECT * FROM $table WHERE $column='$value'";
	else
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

/**
 * Récupération de template de mail
 * @param int|string $id_email
 * @param string $column
 */
function get_email_($id_email = null, $column=null)
{
	global $wpdb;
	$table = $wpdb->prefix . 'mails';
	if( $column == null ){
		if ($id_email == null)
			$sql = "SELECT * FROM $table";
		else
			$sql = "SELECT * FROM $table WHERE id = '$id_email'";
	}else 
		$sql = "SELECT * FROM $table WHERE type_task = '$column'";
	return $wpdb->get_results($sql);
}

/**
 * Mettre à jour les informations dans le worklog comme
 * mail_status, finaly_date, etc
 * 
 * Exemple: 
 * 
 * update_worklog( array( 'column'=> 'foo' ),array('ID' => 1), array('%s') );
 * 
 * @param array $data
 * @param array $where
 * @param array $format
 * 
 * @return bool
 * 
 */
function update_worklog( array $data, array $where, array $format=null){
	global $wpdb;
	$table = $wpdb->prefix . 'worklog';
	if( $format != null )
		return $wpdb->update($table, $data, $where, $format);
	else
		return $wpdb->update($table, $data, $where);
}

/**
 * Obtenir les tâches | Ou une tâche | une catégorie de tâche
 * 
 * @param string $specification
 * @param string|int|null $value
 * @param string|int|null $project
 */
function get_task_($specification = null, $value = null, $project = null, $date_evaluation=null)
{
	global $wpdb;
	$table = $wpdb->prefix . 'task';
	$table1 = $wpdb->prefix . 'worklog';
	if ($project == null || $project == 'worklog') {
		if ($specification != null && $value != null){
			$sql = "SELECT * FROM $table INNER JOIN $table1 ON id=id_task WHERE $specification = $value"; // Association avec le worklog
			if( $project == 'worklog' ){
				$sql .= " AND evaluation_date='$date_evaluation'";
			}
		}
		else if ($specification != null && $value == null)
			$sql = "SELECT * FROM $table WHERE assigne = $specification"; //Tâche assign à l'utilisateur x
		else
			$sql = "SELECT * FROM $table"; //Toutes les tâches par ordre de la date
	} else {
		$sql = "SELECT * FROM $table WHERE $specification = $value";  //Tâche avec une spéfication
	}
	$sql = $sql . " ORDER BY duedate DESC";
	return $wpdb->get_results($sql);
}

/**
 * Get main task
 * @param int $subtask_id
 */
function get_task_main($subtask_id)
{
	global $wpdb;
	$table = $wpdb->prefix . 'task';
	$tablesub = $wpdb->prefix . 'subtask';
	$sql = "SELECT id_task_parent FROM $tablesub WHERE $tablesub.id=$subtask_id";
	$parent = $wpdb->get_row($sql);
	if( $parent == null ) $task_main = null;
	else{
		if( $parent->id_task_parent == null ) $task_main=null;
		else{
			$task_main = get_task_( 'id', $parent->id_task_parent )[0]->title;
		}
	}
	return $task_main;
}

/**
 * Get projet for user current
 * @param int $id_user
 */
function get_user_current_project($id_user)
{
	$user_projects_id = array();
	if ($id_user == null) $id_user = get_current_user_id();
	$j = 0;
	foreach (get_project_(-1) as $value) {
		if( $value->collaborator != null ){
			if( unserialize($value->collaborator) != null ){
				if (in_array($id_user, unserialize($value->collaborator))) {
					$user_projects_id += array($j => array('id' => $value->id, 'title' => $value->title));
					$j++;
				}
			}
		}
	}
	return $user_projects_id;
}

/**
 * Get tempates
 * @param int|null $template_id
 */
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
 * @param string|int|null $month
 * @param string|int|null $year
 * 
 * @return object|array
 */
function get_objective_of_month( $month=null, $year=null ,$id_user = null){
	global $wpdb;
	$table = $wpdb->prefix . 'objectives';
	$table1 = $wpdb->prefix . 'worklog';
	if( $month == null and $year == null and $id_user == null ){
		$sql = "SELECT *FROM $table";
		return $wpdb->get_results( $sql );
	}else{
		if( $id_user != null ){
			$sql = "SELECT * FROM $table, $table1 WHERE id_task=id_objective AND id_user=$id_user AND month_section='$month' AND year_section='$year'";
			return $wpdb->get_row($sql);
		}else{
			$sql = "SELECT * FROM $table, $table1 WHERE id_task=id_objective AND month_section='$month' AND year_section='$year'";
			return $wpdb->get_results($sql);
		}
	}
}

/**
 * Get template title
 * @return array
 */
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

/**
 * Get user id and name or email only
 * @param string|int $keyif( get_user_meta( 1, 'workspace_id', true ) == null ) echo 'vide';
 *	else echo get_user_meta( 1, 'workspace_id', true );
 */
function get_all_users($key = null)
{
	$users = array();
		if ($key != null) {
			foreach (get_users() as $value) { 
				if( (get_user_meta( $value->ID, 'workspace_id', true ) != null) && (get_user_meta( $value->ID, 'workspace_id', true ) == get_workspace()) ){
					$users += array($value->ID => $value->display_name); 
				}
			}
		} else {
			foreach (get_users() as $value) { 
				if( (get_user_meta( $value->ID, 'workspace_id', true ) != null) && (get_user_meta( $value->ID, 'workspace_id', true ) == get_workspace()) ){
					$users += array($value->ID => $value->user_email); 
				}
			}
		}
	return $users;
}

/**
 * Vérifier si l'utilisateur est un project manager
 * si oui on return les projets dont il est project_manager
 * @param int|null $id_user
 */
function is_project_manager(int $id_user = null)
{
	global $wpdb, $current_user;
	$table = $wpdb->prefix . 'project';
	$projects = get_project_(-1);
	$user_project = array();
	if ($id_user == null) $id_user = $current_user->ID;
	$i = 0;
	foreach ($projects as $project) {
		if ($project->project_manager == $id_user) {
			$i++;
			$user_project += array($i => (array)$project);
		}
	}
	return $user_project;
}

/**
 * Get projet manager project id and title only
 * @return array
 */
function get_project_manager_project()
{
	$projects = array();
	foreach (is_project_manager() as $project) {
		$projects += array($project['id'] => $project['title']);
	}
	return $projects;
}

/**
 * Get project title
 * @param int $id_project
 */
function get_project_title($id_project)
{
	$projects = get_project_();
	foreach ($projects as $project) {
		if ($project->id == $id_project) {
			return $project->title;
		}
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


/**
 * Get task status or vérifier si tâche terminer
 * @param int $task_id
 * @param string|null $status
 * 
 */
function get_task_status($task_id, $status = null)
{
	if( $status != null ){
		if( isset( get_task_('id', $task_id)[0] ) ){
			$task = get_task_('id', $task_id)[0];
			return $task->status;
		}
	}else{
		if( isset( get_task_('id', $task_id)[0] ) ){
			$task = get_task_('id', $task_id)[0];
			$duedate = strtotime($task->duedate);
			if (!$task->status) {
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
}

/**
 * Récupérer les catégories de tâche ou une catégorie spécifique
 * 
 * @param int $id_categorie
 * @param string $key
 */
function get_categories_task($id_categorie=null, $key=null)
{
	global $wpdb;
	$table = $wpdb->prefix . 'categories';
	if( $id_categorie == null && $key == null ){
		$sql = "SELECT * FROM $table";
		return $wpdb->get_results($sql);
	}else{
		if( $id_categorie != null && $key == null )
			$sql = "SELECT * FROM $table WHERE id=$id_categorie";
		else
			$sql = "SELECT * FROM $table WHERE categories_key='$key'";
		return $wpdb->get_row( $sql );
	}
}

/**
 * Vérifier si une catégorie est évaluable
 * 
 * @param  string $keyCategorie
 * 
 * @return bool
 */
function isEvaluateCategorie( $keyCategorie ){
	$categorie = get_categories_task(null, $keyCategorie);
	if( $categorie->evaluate == 1 ) return true;
	else return false;
}

/**
 * Récupérer la tâche de revue d'une tâche principale
 * @param int $taskId
 * 
 */
function getReviewTaskForEvaluateTask( $taskId ){
	global $wpdb;
	$table = $wpdb->prefix . 'task';
	$sql = "SELECT * FROM $table WHERE dependancies = " . $taskId . " AND categorie = 'revue'";
	$reviewTask = $wpdb->get_row($sql);
	if( $reviewTask != null ) 
		return $reviewTask;
	else{
		return array();
	} 
}

/**
 * Get project manager for project
 * @param int $projectId
 * 
 * @return int
 */
function getTaskProjectManager( $projectId ){
	return get_project_( $projectId )->project_manager;
}

/**
 * Constitution d'un table de categorie key permettant de faire facilement des vérification
 */
function categorie_name(){
	return array( 'implementation', 'revue', 'integration', 'test' );
}

/**
 * Obtenir les taches d'un utilisateur d'un projet dont il est collaborateur
 * @param int $user_id
 * @param int $project_id
 * 
 * @return array
 */
function getUserTaskInProject($user_id, $project_id){
	global $wpdb;
	$table = $wpdb->prefix . 'task';
	$sql = "SELECT * FROM $table WHERE project_id = $project_id AND assigne = $user_id ORDER BY duedate DESC"; 
	return $wpdb->get_results($sql);
}

/**
 * Save userTemplate task add
 * @param array $array
 */
function useTemplate_save( $array ){
	$subtask = array();
	if( ! isset( $array['assign'] ) ) { $assigne = NULL; }
	else { $assigne = $array['assign']; }
	$task = array(
		'title' => $array['title'],
		'section_project' => $array['project_section'],
		'type_task' => $array['type_task'],
		'categorie' => NULL,
		'dependance' => NULL,
		'project' => $array['project'],
		'assign' => $assigne,
		'duedate' => $array['duedate'],
		'description' => $array['description']
	);
	if (isset($array['nbrechamp'])) {
		$nbrechamp = sanitize_text_field($array['nbrechamp']) -1 ;
		for ($l = 1; $l <= $nbrechamp; $l++) {
			$titre = 'title' .$l;
			$categorie = 'categorie'.$l;
			$assign = 'assign'.$l;
			$duedate = 'duedate'.$l;
			$description = 'description'.$l;
			$subtask += array($l => array(
				'title' => $array[$titre],
				'section_project' => $array['project_section'],
				'type_task' => $array['type_task'],
				'categorie' => $array[$categorie],
				'dependance' => '',
				'project' => $array['project'],
				'assign' => $array[$assign],
				'duedate' => $array[$duedate],
				'description' => $array[$description]
			));
		}
	}
	$parametre = array( 'parametre' => array( 'task' => $task, 'subtask' => $subtask ) );
	return saveTaskInAsanaAndBdd( $parametre );
}

/**
 * Save manuel task add
 * @param array $array
 */
function manuel_save($array)
{
	$subtask = array();
	$task = array(
		'title' => $array['title'],
		'section_project' => $array['project_section'],
		'type_task' => $array['type_task'],
		'categorie' =>  $array['categorie'],
		'dependance' => NULL,
		'project' => $array['project'],
		'assign' => $array['assign'],
		'duedate' => $array['duedate'],
		'description' => $array['description']
	);
	if (isset($array['show1'])) {
		if ($array['show1'] == 'userTemplate1') {
			$subtask += array(
				0 => array(
					'title' => $array['sub_title'],
					'section_project' => $array['project_section'],
					'type_task' => $array['type_task'],
					'categorie' => $array['sub_categorie'],
					'dependance' => '',
					'project' => $array['project'],
					'assign' => $array['sub_assign'],
					'duedate' => $array['sub_duedate'],
					'description' => $array['sub_description']
				)
			);
			if (isset($array['nbrechamp'])) {
				$nbrechamp = sanitize_text_field($array['nbrechamp']) - 1;
				for ($l = 1; $l <= $nbrechamp; $l++) {
					$titre = 'sub_title' . $l;
					$categorie = 'sub_categorie' . $l;
					$assign = 'sub_assign' . $l;
					$duedate = 'sub_duedate' . $l;
					$description = 'sub_description' . $l;
					$subtask += array($l => array(
						'title' => $array[$titre],
						'section_project' => $array['project_section'],
						'type_task' => $array['type_task'],
						'categorie' => $array[$categorie],
						'dependance' => '',
						'project' => $array['project'],
						'assign' => $array[$assign],
						'duedate' => $array[$duedate],
						'description' => $array[$description]
					));
				}
			}
		}
		if ($array['show1'] == 'manuelTemplate1') {
			$subtask += array(
				0 => array(
					'title' => $array['manuel_title'],
					'section_project' => $array['project_section'],
					'type_task' => $array['type_task'],
					'categorie' => $array['manuel_categorie'],
					'dependance' => '',
					'project' => $array['project'],
					'assign' => $array['manuel_assign'],
					'duedate' => $array['manuel_duedate'],
					'description' => $array['manuel_description']
				)
			);
			if( isset($array['nbresubtask']) ){
				$nbrechamp = sanitize_text_field($array['nbresubtask']);
				for ($l = 1; $l <= $nbrechamp; $l++) {
					$titre = 'manuel_title' . $l;
					$categorie = 'manuel_categorie' . $l;
					$assign = 'manuel_assign' . $l;
					$duedate = 'manuel_duedate' . $l;
					$description = 'manuel_description' . $l;
					$subtask += array($l => array(
						'title' => $array[$titre],
						'section_project' => $array['project_section'],
						'type_task' => $array['type_task'],
						'categorie' => $array[$categorie],
						'dependance' => '',
						'project' => $array['project'],
						'assign' => $array[$assign],
						'duedate' => $array[$duedate],
						'description' => $array[$description]
					));
				}
			}
		}
	}
	$parametre = array('parametre' => array('task' => $task, 'subtask' => $subtask));
	return saveTaskInAsanaAndBdd($parametre);
}

/**
 * Récupérer les collaborateurs d'un projet
 * @param int $id_project
 */
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

/**
 * Get project section
 * @param int $id_project
 */
function get_project_section(int $id_project)
{
	$sections = array();
	foreach (get_all_sections($id_project) as $section) {
		$sections += array($section->id => $section->section_name);
	}
	return $sections;
}

/**
 * Get categorie task id and title only
 */
function get_categorie_format()
{
	$cats = get_categories_task();
	$categorie = array();
	foreach ($cats as $cat) {
		$categorie += array($cat->categories_key => $cat->categories_name);
	}
	return $categorie;
}

/**
 * Appeler des fonctions pour la sauvegarde des tâches en fonction de certain critères comme le type ou le choix
 * @param array $array
 */
function traite_form_public($array){
	if ( $array['type_task'] == 'objective' ) {
		return saveProjectManagerObjection( $array );
	}
	else if (($array['type_task'] == 'normal') || ($array['type_task'] == 'developper')) {
		if ($array['show'] == 'userTemplate') {
			return useTemplate_save( $array );
		}
		else if( $array['show'] == 'manuelTemplate' ){
			return manuel_save( $array );
		}
		else return 'impossible';
	} 
	else return 'errorTypeTask';
}

/**
 * Fonction permettant de sauvegarder les objectifs du cp
 */
function saveProjectManagerObjection( $array ){
	$nbre = sanitize_text_field($array['nbreobj']);
	$mois = sanitize_text_field($array['mois']);
	$annee = date('Y');
	$workspace = get_workspace();

	if (get_objective_of_month($mois,  $annee, get_current_user_id()) != null ){ return 'objectiveExist'; }
	else {
		if ($nbre == 0) return 'noObjective';
		else {
			$id_section = section_exist(get_current_user_id());
			if ($id_section == null){ 
				$id_section = save_objective_section(get_current_user_id());
			} 
			
			//Sauvegarde du mois comme une tâche
			$month = date('F', mktime(0, 0, 0, $mois, 10)) . " ( $annee ) ";
			// $project_name = get_project_title($project);
			$string = 'last friday of ' . date('F', mktime(0, 0, 0, $mois, 10)) . ' this year';
			$duedate = gmdate('Y-m-d', strtotime($string)) . ' 23:59:00';
			$asana = connect_asana();
			$result = $asana->createTask(array(
				'workspace' => "$workspace", // a revoir
				'name' => $month,
				'notes' => "Objectives of the month ( $month )",
				'assignee_section' 	=> $id_section,
				'assignee' 			=> get_userdata(get_current_user_id())->user_email,
				'due_on' 			=> $duedate,
			));

			if ($asana->hasError()) {
				return 'errorAsanaObj';
			} else {
				$objective_id = $asana->getData()->gid;
				$asana->addProjectToTask($objective_id, get_option('_project_manager_id'));
				$task_asana = json_decode($result)->data;
				$permalink_objective = $task_asana->permalink_url;
				
				// Sauvegarde des subtask
				$objective_array = array();
				for ($k = 1; $k <= $nbre; $k++) {
					$ob = 'objective' . $k;
					$objective = sanitize_text_field($array[$ob]);
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
					'section_permalink'		=> $permalink_objective,
					'modify_date'			=> $task_asana->modified_at
				);
				//Sauvegarde du worklog
				$task_array = array('id' => $objective_id,'author_id' => get_current_user_id(),'project_id' => get_option('_project_manager_id'),'section_id' => $id_section,'title' => '','permalink_url' => $permalink_objective,'type_task' => 'objective','categorie' => NULL,'dependancies' => NULL,'description' => NULL,'assigne' => NULL,'duedate' => $duedate,'created_at' => $task_asana->created_at);
				$dataworklog = array('id_task' => $objective_id,'finaly_date' => $task_asana->completed_at,'status' => $task_asana->completed,'evaluation' => NULL,'evaluation_date' => NULL,'mail_status' => 'cp');
				save_objective($objective_tab_save);
				save_new_task($task_array, $dataworklog);
				return 'successObj';
			}
		}
	}
}

/**
 * Calendar
 * @param int|null $id_user
 */
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
			<h3><?= esc_html($monthName) ?></h3>
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
								$exist_Task = false; $k=0;
								$array = array();
								foreach ($tasks as $task) {
									if ((date('m', strtotime($task->duedate)) == $month) && (date('Y', strtotime($task->duedate)) == $year)) {
										if (date('d', strtotime($task->duedate)/1) == $d) {
											if( $task->title != '' ){
												$exist_Task = true;
												$array += array( $k => array( 'id' => $task->id,'title' => $task->title, 'assign' => $task->assigne ) );
												$k++;
											}
										}
									}
								}
								if ($exist_Task) {
								?>
									<button class="btn btn-link alert alert-info p-0 m-0 get_list_event" data-toggle="modal" data-target=".<?= $year . '-' . $month . '-' . $d ?>" id="<?= $year . '-' . $month . '-' . $d ?>">See Task List</button>
								<?php
								}
								?>
							</td>
							<?php
							modal_event_calendar($year . '-' . $month . '-' . $d, $array);
							$counter++; ?>
						<?php endfor; ?>
					</tr>
				<?php endfor; ?>
			</table>
		</div>
	</div>
<?php
}

function modal_event_calendar($date_event, $array)
{
?>
	<div class="modal fade <?= $date_event ?>" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Tasks : ( <?= $date_event ?> )</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<?php
						foreach( $array as $data ){
							if( $data['title'] != '' ){
								$task = get_task_( 'id', $data['id'] ); 
								?>
								<div class="alert alert-primary" role="alert">
								<p class="row text-center ml-3 mt-0 mb-1"><?php if( get_task_main( $data['id'] ) != null ) echo stripslashes(get_task_main( $data['id'] )) . '<--'; ?> <strong><?= stripslashes($data['title']) ?></strong> </p>
								<small id="emailHelp" class="form-text text-muted"><strong style="text-decoration: underline;">Status:</strong> <?= ' '.get_task_status( $data['id'] ) ?><?php if( get_userdata(  $data['assign'] ) ) { ?> | <strong style="text-decoration: underline;"> Assigne:</strong><?= ' '.get_userdata(  $data['assign'] )->display_name ?> <?php } ?></small>  
							</div>
							<?php
							}
						}
					?>
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
		<h3><?php _e('List Template') ?><button class="btn btn-outline-success btn_list_task" id="template_btn_list">List Template</button> </h3>
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
	<h3><?php _e('List Template') ?><button class="btn btn-outline-success btn_list_task" id="template_btn_list">List Template</button> </h3>
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

function project_form_add( $id_project=null ){
	if( $id_project != null ) $project = get_project_( $id_project );
	?>
		<h3><?php if( $id_project != null ) echo 'Update Project'; else echo 'New Project'; ?> <button <?php if( $id_project == null ) echo 'class="btn btn-outline-success collapsed" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree"'; else echo 'class="btn btn-outline-success btn_list_project" id="project_btn_list"'; ?>>List Projects</button> </h3>
		<p><strong class="text-warning">WARNING: </strong>Before creating a project, make sure that the project manager and collaborators have an account on ASANA and have been added to the Workspace. Thanks</p>
		<hr>
			<form id="create_new_projet" name="create_new_projet" action="" method="post">
			<?php if( $id_project != null ) { ?> <input type="hidden" name="project_id" id="project_id" value="<?= $id_project ?>"> <?php } ?>
				<div class="form-group">
					<label for="InputTitle">Project Name </label>
					<input type="text" name="titleproject" id="titleproject" class="form-control" <?php if( $id_project != null ) { ?> value="<?= $project->title ?>" <?php } ?> placeholder="Project Name" required>
				</div>
				<div class="form-group">
					<textarea class="form-control" id="description" name="description" rows="3" placeholder="Description ..."><?php if( $id_project != null ){ echo $project->description;}?></textarea>
				</div>
				<div class="form-group">
					<div class="form-row">
						<div class="col">
							<label for="InputTitle">Slug </label>
							<input type="text" name="slug" id="slug" <?php if( $id_project != null ) { ?> value="<?= $project->slug ?>" <?php } ?> class="form-control" placeholder="Slug">
						</div>
						<div class="col">
							<label for="inputState">Project Manager :</label>
							<select id="projectmanager" name="projectmanager" class="form-control" required>
								<?php 
								if( $id_project != null ) echo option_select(get_all_users(), $project->project_manager );
								else { 
									?> 
									<option value="">Choose...</option>
									<?php
									echo option_select(get_all_users()); 
								}
								?>
							</select>
						</div>
					</div>
				</div>
				<?php
					if( $id_project == null ){
						?>
						<div class="form-group">
							<label for="inputState">Collaborators :</label>
							<select class="selectpicker form-control" id="multichoix" name="multichoix" multiple data-live-search="true">
								<?= option_select(get_all_users()) ?>
							</select>
						</div>
						<?php
					}
				?>
				<hr>
				<h5>Sections</h5>
				<div id="addsectionchamp" class="pb-3">
					<?php
						if( $id_project != null ){
							$sections = get_project_section( $id_project );
							$i=1;
							foreach( $sections as $key_section => $section ){
								?>
								<div id="rm2<?= $i ?>">
									<div class="form-row pt-2">
										<div class="col-sm-12">
											<input type="text" name="section<?= $i ?>" id="section<?= $i ?>" readonly class="form-control" value="<?= $section ?>">
										</div>
									</div>
								</div>
								<?php
								$i++;
							}
							?>
							<input type="hidden" name="nbresection" id="nbresection" value="<?= $i ?>">
							<?php
						}
					?>
				</div>
				<div class="form-group">
					<span id="addsection" name="addsection" class="btn btn-outline-success">+ Add Section</span>
				</div>
				<div class="form-group">
					<button type="submit" name="valide" class="btn btn-primary btn-sm btn-block"> <?php if( $id_project != null ) echo 'UPDATE PROJECT'; else echo 'CREATE PROJECT'; ?> </button>
				</div>
			</form>
	<?php
}

function project_tab(){
	$projects = get_project_();
	?>
		<h3><?php _e('List Projects') ?><button class="btn btn-outline-success collapsed" data-toggle="collapse" data-target="#collapseFour1" aria-expanded="false" aria-controls="collapseFour1">Add New Project</button> </h3>
		<p><?php _e('The list of projects with managers and a brief description') ?></p>
		<hr>
		<table class="table table-hover table-responsive-lg">
			<thead class="thead-dark">
				<tr>
					<th>N°</th>
					<th>Title</th>
					<th>Project Manager</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$k = 0;
				foreach ($projects as $project) {
				?>
					<tr>
						<td class="m-2"><?= $k+1 ?></td>
						<td class="m-0 p-0">
							<span class="btn btn-link project_edit" id="<?= $project->id ?>"><?= $project->title ?></span><br>
							<span class="ml-3"><?= substr($project->description, 0,30) ?>... </span> 
						</td>
						<td class="m-0 p-0 pt-2">
							<?= get_userdata( $project->project_manager )->display_name ?><br>
							<button class="btn btn-link p-0 m-0 text-warning" data-toggle="modal" data-target="#<?=  $project->id ?>">Editer Collaborators</button>
						</td>
						<td class="m-0 p-0 text-center pt-3">
						<?php 
						if( !getProjectStatus($project->id) ){ 
							?>
							<span title="edit" class="btn btn-outline-primary project_edit" id="<?= $project->id ?>">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16">
									<path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
								</svg>
							</span> 
							<?php
						}
						?>
						<span class="<?php if( getProjectStatus($project->id) ){ echo 'btn-outline-warning'; } else { echo 'btn-outline-success'; } ?> btn project_archive" id="<?= $project->id ?>" >
						<?php 
						if( getProjectStatus($project->id) ){ 
							?> 
							<span title="unarchive">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-up" viewBox="0 0 16 16">
									<path fill-rule="evenodd" d="M3.5 6a.5.5 0 0 0-.5.5v8a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5v-8a.5.5 0 0 0-.5-.5h-2a.5.5 0 0 1 0-1h2A1.5 1.5 0 0 1 14 6.5v8a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 14.5v-8A1.5 1.5 0 0 1 3.5 5h2a.5.5 0 0 1 0 1h-2z"/>
									<path fill-rule="evenodd" d="M7.646.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 1.707V10.5a.5.5 0 0 1-1 0V1.707L5.354 3.854a.5.5 0 1 1-.708-.708l3-3z"/>
								</svg>
							</span>
							<?php
						} 
						else { 
							?> 
							<span title="archive">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-archive" viewBox="0 0 16 16">
									<path d="M0 2a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1v7.5a2.5 2.5 0 0 1-2.5 2.5h-9A2.5 2.5 0 0 1 1 12.5V5a1 1 0 0 1-1-1V2zm2 3v7.5A1.5 1.5 0 0 0 3.5 14h9a1.5 1.5 0 0 0 1.5-1.5V5H2zm13-3H1v2h14V2zM5 7.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/>
								</svg>
							</span>
							<?php
						} 
						?>
						</span>
						</td>
					</tr>
				<?php
					$k++;
					modalCollaborator($project->id, $project->title, $project->project_manager, unserialize($project->collaborator));
				}
				if ($k == 0) {
				?>
					<div class="alert alert-primary" role="alert">
						Project not found
					</div>
				<?php
				}
				?>
			</tbody>
		</table>
	<?php
}

function modalCollaborator( $projectId, $title, $cpId, $collaborators ){
	?>
	<!-- Modal -->
	<div class="modal fade" id="<?= $projectId ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
		<div class="modal-header">
			<h5 class="modal-title" id="exampleModalLabel"><?= $title ?></h5>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
			<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<form id="<?= $projectId ?>" class="editCollaborator" action="" method="post">
			<div class="modal-body">
				<h3>Edit Collaborators</h3>
				<span id="successCol"></span>
				<hr>
				<div class="form-group">
					<label for="inputState">Collaborators :</label>
					<select class="selectpicker form-control" required id="multichoix<?= $projectId ?>" name="multichoix" multiple data-live-search="true">
						<?= option_select(get_all_users(), $collaborators) ?>
					</select>
					<input type="hidden" name="project_manager" id="project_manager<?= $projectId ?>" value="<?= $cpId ?>">
					<small>Click here to edit the project collaborators</small>
					</div>
				</div>
				<div class="modal-footer">
					<button class="btn btn-secondary" id="btn_close<?= $projectId ?>" data-dismiss="modal">Close</button>
					<button type="submit" id="btn_submit<?= $projectId ?>" class="btn btn-primary">Update Collaborator</button>
				</div>
			</form>
		</div>
	</div>
	</div>
	<?php
}

function get_list_template()
{
	$tab_templates = get_templates_();
?>
	<h3>List Template <button class="btn btn-outline-success btn_list_task" id="template_btn_add">Add New Template</button> </h3>
	<table class="table table-hover table-responsive-lg">
		<thead class="thead-dark">
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
				<tr class="">
					<td class="m-0 p-0" style="height: 45px;"><?= $k + 1 ?></td>
					<td class="m-0 p-0" style="height: 45px;"><span class="btn btn-link template_edit" id="<?= $template->option_id ?>"><?= $titles['parametre']['template']['templatetitle'] ?></span></td>
					<td class="m-0 p-0" style="height: 45px;"><?= $titles['parametre']['template']['tasktitle'] ?></td>
					<td class="m-0 p-0" style="height: 45px;">
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

function add_task_form(){
	?>
	<form method="post" action="#" id="">
		<?php wp_nonce_field('create_new_task', 'verifier_new_task_form'); ?>
		<div id="">
			<div class="row text-center card-header">
				<div class="col-sm-6">
					<input type="radio" class="form-check-input" name="show" value="userTemplate" id="userTemplate">
					<label class="form-check-label" for="template"><strong>Use Template1</strong></label>
				</div>
				<div class="col-sm-6">
					<input type="radio" class="form-check-input" name="show" value="manuelTemplate" id="manuelTemplate">
					<label class="form-check-label" for="template"><strong>Create manually</strong></label>
				</div>
			</div>
			<span id="task_success"></span>
			<input type="hidden" name="nbre" id="nbre" value="0">
			<span id="first_choix"></span>
		</div>
		<div id="manuel_get" style="display:none ;">
			<div class="form-check">
				<input type="checkbox" class="form-check-input" name="AddSubtask" id="AddSubtask">
				<label class="form-check-label" for="exampleCheck1"><strong>Add subtasks</strong></label>
			</div>
			<div class="row text-center card-header" id="choix_check" style="display:none;">
				<div class="col-sm-6">
					<input type="radio" class="form-check-input" name="show1" id="userTemplate1" value="userTemplate1">
					<label class="form-check-label" for="exampleCheck1"><strong>Use Templates</strong></label>
				</div>
				<div class="col-sm-6">
					<input type="radio" class="form-check-input" name="show1" id="manuelTemplate1" value="manuelTemplate1">
					<label class="form-check-label" for="exampleCheck1"><strong>Create manually</strong></label>
				</div>
			</div>
			<span id="second_choix"></span>
			<div id="add_more_subtask"></div>
			<hr>
			<div id="subtaskmore" style="display:none;">
			<input type="hidden" value="0" id="nbresubtask" name="nbresubtask">
				<div class="form-group">
					<span id="more_subtask" name="more_subtask" class="btn btn-outline-primary add_more">+ Add Sub Task</span>
				</div>
			</div>
			</div>
		<div class="pt-1" id="hidden_submit" style="display:none">
			<button type="submit" class="btn btn-primary" name="validetash">Submit</button>
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
	if( $month == null ) $month = date('m')/1;
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
					<span id="addobject" name="addobject" class="btn btn-outline-success add_more">+ Add Goals</span>
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
					<span>
						<?php
						$download_cpEval = get_option('_worklog_authorized');
                        if ($download_cpEval == 'true') {
                            $debug_status  = get_option('_debug_authorized');
                            if ($debug_status == 'true') {
								$month = date('m');
                            }else{
								$nxtm = strtotime("previous month");
								$month = date('m', $nxtm);
                            }
                            $upload = wp_upload_dir();
                            $worklog_evaluation = $upload['basedir'];
							$date_eval = $month . '-' . date('Y') . '_cp_Evaluation';
                            $name_worklog = $date_eval . '/' . get_userdata(get_current_user_id())->display_name . '_cp.xlsx';
                            $worklog_evaluation_file = $worklog_evaluation . '/worklog_evaluation/' . $name_worklog;

                            if (file_exists($worklog_evaluation_file)) {
                            ?>
                                <form method="post" action="" id="sent_worklog_mail">
                                    <input type="hidden" name="link_file" id="link_file" value="<?= $worklog_evaluation_file ?>">
                                    <input type="hidden" name="file_name" id="file_name" value="<?= get_userdata(get_current_user_id())->display_name . '_worklog.xlsx' ?>">
                                    <button type="submit" class="btn btn-outline-success">Download CP Evaluation</button>
                                </form>
                            <?php
                            }
                        }
                        ?>
					</span>
				</div>
			</div>
		</div>
		<?php 
		$objectives_array = get_objective_of_month( $month, date('Y'), $id_user );
		if( $objectives_array != null ){
				?>
				<div class="card-body">
					<table class="table table-hover">
						<thead  class="thead-dark">
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
					No goals set at this time
				</div>
				<?php
			}
			?>	
		</div>
	<?php
}

function get_user_task()
{
	$user_current_projects = get_user_current_project(get_current_user_id());
	if ($user_current_projects != null) {
		$i = 1;
		?>
		<div id="accord">
			<?php
			foreach ($user_current_projects as $project) {
				$tasks = getUserTaskInProject(get_current_user_id(), $project['id']);
				?>
				<div class="card">
				<?php 
					if( $project['id'] != get_option( '_project_manager_id' ) ){
						?>
						<div class="card-header" id="heading<?= $project['id'] . $project['title'] ?>" data-toggle="collapse" data-target="#collapse<?= $project['id'] . $project['title'] ?>" aria-expanded="true" aria-controls="collapse<?= $project['id'] . $project['title'] ?>">
							<div class="row">
								<div class="col-sm-6">
									<h3 class="mb-0 ">
										<button class="btn btn-link">
											Project <?= $i ?>: <strong><?= $project['title'] ?></strong>
										</button>
									</h3>
								</div>
								<div class="col-sm-4"></div>
								<div class="col-sm-2">
									<form action="#" method="post">
										<?php wp_nonce_field('refreshProject', 'verifier_new_task_form'); ?>
										<input type="hidden" name="projectRefresh" value="<?= $project['id'] ?>">
										<button type="submit" title="refresh" id="<?= $project['id'] ?>" class="btn btn-outline-success refreshBtn">
											<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-repeat" viewBox="0 0 16 16">
												<path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
												<path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
											</svg> Refresh
										</button>
									</form>
								</div>
							</div>
						</div>
						<?php
					}
					?>
					<div id="collapse<?= $project['id'] . $project['title'] ?>" class="collapse <?php if ($i == 1) echo 'show'; ?>" aria-labelledby="heading<?= $project['id'] . $project['title'] ?>" data-parent="#accord">
						<div class="card-body">
							<?php
								if( $tasks != null ){
									?>
									<table class="table table-hover">
										<thead  class="thead-dark">
											<tr>
												<th>N°</th>
												<th>Task title</th>
												<th>Due Date</th>
												<th>Status</th>
											</tr>
										</thead>
										<tbody>
											<?php
											$k = 1;
											foreach ($tasks as $task) {
												$status = get_task_status($task->id);
												$main_task = "";
												if( in_array($task->categorie, categorie_name()) ) $main_task = get_task_main( $task->id ) . ' <-- ';
												if( date('m', strtotime($task->created_at)) == date('m') ){
													?>
													<tr>
														<td><?= $k ?></td>
														<td><?php if (get_task_main( $task->id ) != null) echo stripslashes($main_task); ?><a target="_blank" href="<?= $task->permalink_url ?>" class="btn-link"><?= stripslashes($task->title) ?></a></td>
														<?php 
														if( $task->duedate != NULL ){
															?>
															<td class="alert alert-primary"><?= $task->duedate ?></td>
															<td class="<?php if ($status == 'Not Completed' || $status == 'Completed Before Date') echo 'text-danger';
																		elseif ($status == 'Completed') echo 'text-success';
																		elseif( $status == 'In Progess' ) echo 'text-primary';
																		else echo 'text-warning';  ?>"><?= $status ?></td>
															<?php
														}else{
															?>
															<td class="alert alert-primary">Not define</td>
															<td class="<?php if( ! get_task_status( $task->id , 'yes') ) echo 'text-warning'; else echo 'text-success'; ?> "> <?php if( ! get_task_status( $task->id , 'yes') ) echo 'Not Completed'; else echo 'Completed'; ?> </td>
															<?php
														}
														?>
													</tr>
													<?php
													$k++;
												}
											}
											?>
										</tbody>
									</table>
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
					</div>
				</div>
			<?php
				$i++;
			}
			?>
		</div>
	<?php
	}
}



function get_categories_()
{
	$all_categories = get_categories_task();
	?>
	<div class="form-row pt-2">
		<div class="col-sm-2"><strong>Evaluate</strong></div>
		<div class="col-sm-5"><strong>Name</strong></div>
		<div class="col-sm-5"><strong>Key</strong></div>
	</div>
	<?php
	foreach ($all_categories as $categorie) {
		if (in_array($categorie->categories_key, categorie_name())) {
	?>
			<div class="form-row pt-2">
				<div class="col-sm-1"><div class="custom-control custom-checkbox my-1 mr-sm-2"><input type="checkbox" <?php if( $categorie->evaluate ) echo 'checked' ?> class="custom-control-input evaluateUpdata" id="<?= $categorie->id ?>"><label id="label<?= $categorie->id ?>" class="custom-control-label" for="<?= $categorie->id ?>"><?php if( $categorie->evaluate ) echo 'Yes'; else echo 'No'; ?></label></div></div>
				<div class="col-sm-6"><input type="text" readonly value="<?= $categorie->categories_name ?>" class="form-control text-dark"></div>
				<div class="col-sm-5"><input type="text" readonly value="<?= $categorie->categories_key ?>" class="form-control text-dark"></div>
			</div>
			<?php
		} else {
			?>
			<div class="form-row pt-2">
				<div class="col-sm-1"><div class="custom-control custom-checkbox my-1 mr-sm-2"><input type="checkbox" <?php if( $categorie->evaluate ) echo 'checked' ?> class="custom-control-input evaluateUpdata" id="<?= $categorie->id ?>"><label class="custom-control-label" id="label<?= $categorie->id ?>" for="<?= $categorie->id ?>"><?php if( $categorie->evaluate ) echo 'Yes'; else echo 'No'; ?></label></div></div>
				<div class="col-sm-5"><input type="text" id="name<?= $categorie->id ?>" readonly value="<?= $categorie->categories_name ?>" class="form-control text-dark"></div>
				<div class="col-sm-4"><input type="text" id="key<?= $categorie->id ?>" readonly value="<?= $categorie->categories_key ?>" class="form-control text-dark"></div>
				<div class="col-sm-1 btn btn-primary edit_categorie" id="<?= $categorie->id ?>"> <span id="edit_<?= $categorie->id ?>">Edit</span> </div>
				<div class="col-sm-1 btn btn-danger delete_categorie" id="<?= $categorie->id ?>">Delete</div>
			</div>
	<?php
		}
	}
	?>
	<form action="" method="post" id="create_categories"><hr>
		<label for="inputState">Add Other Categories :</label>
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

function get_email_task_tab($id_template = null)
{
	$vrai = false;
	if ($id_template != null) {
		$template_email = get_email_($id_template)[0];
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
																					else echo 'Evaluation developper';  ?>">
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
<?php
}


function get_project_manager_tab(){
	?>
	<div class="card-bdy">
		<span id="add_success_id"></span>
		<form id="project_manager_id" method="post" action="">
			<label for="project_manager_id">ASANA Project ID for CP evaluation</label>
			<div class="form-row">
				<div class="col-sm-8">
					<input type="text" name="id_project_manager" id="id_project_manager" class="form-control" placeholder="Project Id" value="<?= get_option( '_project_manager_id' ) ?>">
					<small id="emailHelp" class="form-text text-muted">Enter the ASANA ID of the project where the objectives will be saved</small>
				</div>
				<div class="col">
					<button type="submit" class="btn btn-outline-primary mb-2">UPDATE</button>
				</div>
			</div>
		</form>
		<hr>
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
			<div id="bg22222" class="col-sm-6 alert alert-info btn-link" onclick="open_sub_templaye(22222)">
				<h6>Normal</h6>
			</div>
		</div>
		<div id="11111" class="row" style="display:block">
			<h5>Developpment Criteria</h5>
			<div>
				<?php
				if( $criterias != null ){
					$u = 1;
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
					<?php
				} 
				?>
			</div>
			<div id="criteriaadd1" class="pb-3"></div>
			<div class="form-group">
				<span id="addcriteria1" name="addcriteria1" class="btn btn-outline-success">+ Add Criteria</span>
			</div>
		</div>
		<div id="22222" class="row" style="display:none">
			<h5>Normal Criteria</h5>
			<div>
				<?php 
				if( $criterias != null ){
					$v = 1;
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
					<?php
				}
				?>
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
	$emails = get_email_();
?>
	<table class="table table-hover table-responsive-lg">
		<thead class="thead-dark">
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
						<span class="text-primary btn btn-link email_edit m-0 p-0" id="<?= $email->id ?>">Edit</span> | <span class="text-danger btn btn-link email_remove m-0 p-0" id="<?= $email->id ?>">Delete</span>
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



function get_form(array $array, $istemplate)
{
	$template = $array['parametre']['template'];
	?>
	<div class="pb-3">
		<?php
		if ($istemplate) {
			?>
			<div class="form-group">
				<label for="title">Title</label>
				<input type="text" class="form-control" readonly name="sub_title" id="sub_title" value="<?= $template['tasktitle']  ?>">
			</div>
			<?php
		} else {
			?>
			<div class="row">
				<div class="col">
					<label for="title">Title</label>
					<input type="text" class="form-control" name="title" id="title" value="<?= $template['tasktitle'] ?>" required>
					<input type="hidden" class="form-control" name="type_task" id="type_task" value="<?= $template['type_task']  ?>">
				</div>
				<div class="col">
					<label for="proectlabel" id="label1" style="color:red">Select Project : </label>
					<select class="form-control project projectSection" id="project" name="project">
						<?= option_select(array('' => 'Choose project ...') + get_project_manager_project()) ?>
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
			<?php 
			if ($istemplate){
				?>
				<div class="col">
					<label for="categorie">Categorie</label>
					<select class="form-control" id="sub_categorie" name="sub_categorie">
						<?= option_select(get_categorie_format()) ?>
					</select>
				</div>
				<div class="col">
					<label for="assigne">Assign : </label>
					<select required class="form-control assign_option" id="<?php if ($istemplate) echo 'sub_'  ?>assign" name="<?php if ($istemplate) echo 'sub_'  ?>assign"><option value="" selected></option></select>
				</div>
				<?php
			}  
			?>
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
						<label for="title">Title</label>
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
						<label for="assigne">Assign : </label>
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
		<div id="template_select"></div>
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
							<?= option_select(array('' => 'Choose project ...') + get_project_manager_project()) ?>
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

function add_manuel_form( $id ){
?>
	<div class="form-group">
		<label for="title">Titre</label>
		<input type="text" class="form-control" name="manuel_title<?= $id ?>" required id="manuel_title<?= $id ?>">
	</div>
	<div class="form-group">
		<label for="exampleFormControlTextarea1">Description</label>
		<textarea class="form-control" id="manuel_description<?= $id ?>" name="manuel_description<?= $id ?>" placeholder="Description..." rows="3"></textarea>
	</div>
	<div class="row">
		<div class="col">
			<label for="assigne">Assigne : </label>
			<select class="form-control assign_option" id="manuel_assign<?= $id ?>" name="manuel_assign<?= $id ?>"><option value="" selected></option></select>
		</div>
		<div class="col">
			<label for="duedate">Due Date</label>
				<input type="datetime-local" name="manuel_duedate<?= $id ?>" class="form-control" id="manuel_duedate<?= $id ?>" aria-describedby="duedate">
		</div>
		<div class="col">
			<label for="categorie">Categorie</label>
			<select class="form-control" id="manuel_categorie<?= $id ?>" name="manuel_categorie<?= $id ?>">
				<?= option_select(array( '' => 'None' ) + get_categorie_format()) ?>
			</select>
		</div>
	</div>
<?php
}