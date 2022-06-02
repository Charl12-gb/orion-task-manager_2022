<?php
/**
 * Connexion à l'api de asana
 */
function connect_asana()
{
	$token_asana  = get_option('access_token');
	$asana = new Asana(array('personalAccessToken' => $token_asana));
	return $asana;
}
//******************************************************************************************* */
//Ajouter ces propres heure de modification
// add_filter( 'cron_schedules', 'moose_add_cron_interval' );
// function moose_add_cron_interval( $schedules ) { 
//     $schedules['ten_seconds'] = array(
//         'interval' => 10,
//         'display'  => esc_html__( 'Every Ten Seconds' ), );
//     return $schedules;
// }


add_action('task_cron_hook', 'task_cron_sync');
function task_cron_sync(){
	sync_projets();
	sync_tasks();
	sync_duedate_task();
}

if( ! wp_next_scheduled( 'task_cron_hook' ) ){
	$time_def = get_option( '_synchronisation_time' );
	wp_schedule_event( time(), $time_def, 'task_cron_hook' );
}

//***************************************************************************************** */

/**
 * Obtenir l'espace de travail depuis asana
 */
function get_workspace()
{
	$asana = connect_asana();
	$asana->getWorkspaces();
	return $asana->getData()[0]->gid;
}

/**
 * Get collaborator for asana
 * @param int $project_id
 * @return string
 */
function get_asana_collaborator($project_id)
{
	$asana = connect_asana();
	$asana->getProject($project_id);
	$collaborator = array();
	$project_asana_info = $asana->getData();
	$collaborator_asana = $project_asana_info->members;

	foreach ($collaborator_asana as $collaborator_as) {
		array_push($collaborator,  get_user_asana_id($collaborator_as->gid));
	}
	return serialize($collaborator);
}

/**
 * Fonction permettant de synchroniser les new projects dans asana.
 * @param array $data
 * @return bool
 */
function sync_new_project($data)
{
	global $wpdb;
	$asana = connect_asana();
	$array_asana = array(
		'workspace' => get_workspace(),
		'name' => $data['title'],
		'notes' => $data['description']
	);
	$asana->createProject($array_asana);
	$result = $asana->getData();
	if (isset($result->gid)) {
		$array = array(
			'id'		=> $result->gid,
			'title'		=> $data['title'],
			'description' => $data['description'],
			'permalink'	=> $result->permalink_url,
			'slug' => $data['slug'],
			'project_manager' => $data['project_manager'],
			'collaborator' => serialize($data['collaborator'])
		);
		return save_project( $array );
	} else return false;
}

/**
 * Sync des projects et sections
 */
function sync_projets()
{
	// See class comments and Asana API for full info
	$projects = get_all_project();
	$sections_all = get_all_sections();
	$asana = connect_asana();
	$asana->getProjects();
	if ($asana->getData() != null) {
		foreach ($asana->getData() as $project_asana) {
			$Exist = true;
			foreach ($projects as $project) {
				if ($project_asana->gid == $project->id) {
					$Exist = false;
				}
			}
			if ($Exist) {
				$asana->getProjectStories($project_asana->gid);
				$created = $asana->getData()[0]->created_by->gid;
				$asana->getProject($project_asana->gid);
				$project_asana_info = $asana->getData();
				$project_manager = get_user_asana_id($created);
				$data1 = array(
					'id' => $project_asana->gid,
					'title' => $project_asana->name,
					'description' => $project_asana_info->notes,
					'permalink' => $project_asana_info->permalink_url,
					'slug' => str_replace(" ", ",", strtolower($project_asana->name)),
					'project_manager' => $project_manager,
					'collaborator' => get_asana_collaborator($project_asana->gid)
				);
				// Sauvegarde des projets inexistant dans la bdd
				save_project($data1);
			}

			$sections_asana = $asana->getProjectSections($project_asana->gid);
			if ($asana->getData() != null) {
				foreach ($asana->getData() as $sections) {
					$SectionExist = true;
					foreach ($sections_all as $section) {
						if ($sections->gid == $section->id) {
							$SectionExist = false;
						}
					}
					if ($SectionExist) {
						$data2 = array(
							'id' 		=> $sections->gid,
							'project_id' => $project_asana->gid,
							'section_name'		=> $sections->name
						);
						// Sauvegarde des sections inexistante dans la bdd
						save_new_sections($data2);
					}
				}
			}
		}
	}
}

/**
 * Synchronisation le status des tâches. (Completed or No)
 * @return void
 */
function sync_duedate_task()
{
	global $wpdb;
	$table = $wpdb->prefix . 'worklog';
	$worklog_all = get_all_worklog();
	$asana = connect_asana();
	foreach ($worklog_all as $worklog) {
		$asana->getTask($worklog->id_task);
		$detail_task = $asana->getData();
		if ($worklog->status != $detail_task->completed)
			$wpdb->update($table, array('finaly_date' => $detail_task->completed_at, 'status' => $detail_task->completed), array('id_task' => $worklog->id_task), array('%s', '%s'));
	}
}

/**
 * Synchronisation des tâches depuis Asana
 * @return void
 */
function sync_tasks()
{
	$task_all = get_task_();
	$asana = connect_asana();
	$asana->getProjects();
	if ($asana->getData() != null) {
		foreach ($asana->getData() as $project_asana) {
			$asana->getProjectTasks($project_asana->gid);
			$task_asana = $asana->getData();
			if ($task_asana != null) {
				foreach ($task_asana as $task_as) {
					$TaskExist = true;
					foreach ($task_all as $task) {
						if ($task_as->gid == $task->id) {
							$TaskExist = false;
						}
					}
					//si la task n'est pas dans la bdd, on recupère ces information
					if ($TaskExist) {
						$asana->getTask($task_as->gid);
						$task_info = $asana->getData();
						$asana->getTaskStories($task_as->gid);
						$task_info1 = $asana->getData()[0];
						$assigne = get_user_asana_id($task_info->assignee->gid);
						$section_id = $task_info->memberships[0]->section->gid;
						$data = array(
							'id' => $task_as->gid,
							'author_id' => get_user_asana_id($task_info1->created_by->gid),
							'project_id' => $project_asana->gid,
							'section_id' => $section_id,
							'title' => $task_as->name,
							'permalink_url' => $task_info->permalink_url,
							'type_task' => 'developper',
							'categorie' => NULL,
							'dependancies' => $task_info->parent,
							'description' => $task_info->notes,
							'assigne' => $assigne,
							'duedate' => $task_info->due_on,
							'created_at' => $task_info1->created_at
						);

						$dataworklog = array(
							'id_task' => $task_as->gid,
							'finaly_date' => $task_info->completed_at,
							'status' => $task_info->completed,
							'evaluation' => NULL
						);
						save_new_task($data, $dataworklog);
					}
				}
			}
		}
	}
}

/**
 * Obtenir l'id d'un membre à travers son email
 * Si son mail n'existe pas dans la bdd, on recupère ces 
 * informations depuis asana afin de l'ajouter à la bdd
 * 
 * @param int $id_asana
 */
function get_user_asana_id($id_asana)
{
	$asana = connect_asana();
	$asana->getUserInfo($id_asana);
	if (!isset($asana->getData()->email)) return null;
	$user_email = $asana->getData()->email;
	$user = get_user_by('email', $user_email);
	if ($user) return $user->ID;
	else {
		$userdata = array(
			'user_login' 	=> $asana->getData()->name,
			'user_nicename'	=> strtolower($asana->getData()->name),
			'user_email'  	=> $asana->getData()->email,
			'display_name'	=> $asana->getData()->name,
			'user_pass'  	=>  NULL
		);
		$user_id = wp_insert_user($userdata);
		return $user_id;
	}
}

/**
 * Fonction permettant de récupérer une tâche créer et 
 * ses sous tâches possible afin de la save dans asana et ensuite
 * appeler la fonction save_new_task() et save_new_subtask() 
 * pour save respectivement les task et les subtask
 * 
 * @param array $data
 */
function traite_task_and_save($data)
{
	$asana = connect_asana();
	$array = $data['parametre']['task'];
	$result = $asana->createTask(array(
		'workspace'			=> get_workspace(),
		'name' 				=>	$array['title'],
		'assignee_section' 	=> $array['section_project'],
		'notes' 			=> $array['description'],
		'parent'			=> NULL,
		'assignee' 			=> get_userdata($array['assign'])->user_email,
		'due_on' 			=> $array['duedate']
	));
	$taskId = $asana->getData()->gid;
	$asana->addProjectToTask($taskId, $array['project']);
	if ($asana->hasError()) {
		return false;
	} else {
		$task_asana = json_decode($result)->data;
		$data_add = array(
			'id' => $taskId,
			'author_id' => get_current_user_id(),
			'project_id' => $array['project'],
			'section_id' => $array['section_project'],
			'title' => $array['title'],
			'permalink_url' => $task_asana->permalink_url,
			'type_task' => $array['type_task'],
			'categorie' => $array['categorie'],
			'dependancies' => $array['dependance'],
			'description' => $array['description'],
			'assigne' => $array['assign'],
			'duedate' => $task_asana->due_on,
			'created_at' => $task_asana->created_at
		);

		$dataworklog = array(
			'id_task' => $taskId,
			'finaly_date' => $task_asana->completed_at,
			'status' => $task_asana->completed,
			'evaluation' => NULL
		);
		save_new_task($data_add, $dataworklog);
	}
	if (isset($data['parametre']['subtask'])) {
		return save_new_subtask($data['parametre']['subtask'], $taskId);
	}
	return true;
}

/**
 * Fonction permettant de créer une nouvelle section d'un project manager en utilisant son nom 
 * 
 * @param int $projectmanager
 * 
 * @return bool
 */
function save_objective_section( $projectmanager_id ){
	$section_name =  get_userdata($projectmanager_id)->display_name;
	$project = get_option('_project_manager_id');
	$sectionExist = section_exist( $section_name, $project  );
	if( $sectionExist ) return false;
	else {
		$asana = connect_asana();
		$asana->createSection(
			$project,
			array("name" => $section_name )
		);
		$asana_output = $asana->getData();
		if( isset( $asana_output->gid ) ){
			$data2 = array(
				'id' 		=> $asana_output->gid,
				'project_id' => $project,
				'section_name'		=> $asana_output->name
			);
		}else return false;
		return save_new_sections($data2);
	}
}

/**
 * Save subtask in Asana and bdd
 * 
 * @param array $data
 * @param int $parent_id
 */
function save_new_subtask($data, $parent_id)
{
	$asana = connect_asana();
	foreach ($data as $array) {
		if( !empty( $array['assign'] ) && !empty( $array['duedate'] ) ){
			$result = $asana->createTask(array(
				'workspace'			=> get_workspace(),
				'name' 				=>	$array['title'],
				'assignee_section' 	=> $array['section_project'],
				'notes' 			=> $array['description'],
				'parent'			=> NULL,
				'assignee' 			=> get_userdata($array['assign'])->user_email,
				'due_on' 			=> $array['duedate']
			));
			$taskId = $asana->getData()->gid;
			$asana->addProjectToTask($taskId, $array['project']);
			if ($asana->hasError()) {
				return false;
			} else {
				$task_asana = json_decode($result)->data;
				$data_add = array(
					'id' => $taskId,
					'author_id' => get_current_user_id(),
					'project_id' => $array['project'],
					'section_id' => $array['section_project'],
					'title' => $array['title'],
					'permalink_url' => $task_asana->permalink_url,
					'type_task' => $array['type_task'],
					'categorie' => $array['categorie'],
					'dependancies' => $array['dependance'],
					'description' => $array['description'],
					'assigne' => $array['assign'],
					'duedate' => $task_asana->due_on,
					'created_at' => $task_asana->created_at
				);
	
				$dataworklog = array(
					'id_task' => $taskId,
					'finaly_date' => $task_asana->completed_at,
					'status' => $task_asana->completed,
					'evaluation' => NULL
				);
				$subarray = array('id' => $taskId, 'id_task_parent' => $parent_id);
				$sortir = save_new_task($data_add, $dataworklog, $subarray);
				if( ! $sortir ) return false;
			}
		}
	}
	return true;
}