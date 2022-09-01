<?php
/**
 * Connexion à l'api de asana
 */
function connect_asana()
{
	$token_asana  = get_option('_asana_access_token');
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
function task_cron_sync()
{
	sync_tag();
	sync_projets();
	sync_objectives_month();
	sync_tasks();
	sync_duedate_task();
}

if (!wp_next_scheduled('task_cron_hook')) {
	$time_def = get_option('_synchronisation_time');
	wp_schedule_event(time(), $time_def, 'task_cron_hook');
}

add_action('objective_cron_hook', 'objective_cron_sync');
function objective_cron_sync(){
	automatique_send_mail();
	syncEmployeesFromAsana();
	save_objective_section();
	evaluation_project_manager();
	if( date('m-Y') == '01-'. date('Y') ){
		evaluation_cp();
		worklog_file();
	}
}

if (!wp_next_scheduled('objective_cron_hook')) {
	wp_schedule_event(time(), 'daily', 'objective_cron_hook');
}

// add_action('report_cron_hook', 'report__cron_sync');
// if (!wp_next_scheduled('report__cron_hook')) {
// 	wp_schedule_event(time(), 'daily', 'report_cron_hook');
// }

// function report__cron_sync(){
// 	$sent_info = unserialize( get_option('_report_sent_info') );
// 	$today = date('Y-m-d');
// 	if( $sent_info['last_day_month'] ){
// 		$date_send_report = gmdate('Y-m-d', strtotime('last day of this month'));
// 		if( strtotime( $today ) == strtotime( $date_send_report ) ){
// 			//call function
// 		}
// 	}
// 	if( $sent_info['last_friday_month'] ){
// 		$mois = date('m');
// 		$string = 'last friday of ' . date('F', mktime(0, 0, 0, $mois, 10)) . ' this year';
// 		$date_send_report = gmdate('Y-m-d', strtotime($string));
// 		if( strtotime( $today ) == strtotime( $date_send_report ) ){
// 			//call function
// 		}
// 	}
// }

//***************************************************************************************** */

/**
 * Obtenir l'espace de travail depuis asana
 */
function get_workspace()
{
	$workspace = get_option('_asana_workspace_id');
	if( $workspace == null ){
		$asana = connect_asana();
		$asana->getWorkspaces();
		$workspace = $asana->getData()[0]->gid;
	}
	return $workspace;
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
function sync_new_project($data, $project_id=null)
{
	global $wpdb;
	$asana = connect_asana();
	$array_asana = array(
		'workspace' => get_workspace(),
		'name' => $data['title'],
		'notes' => $data['description']
	);
	if( $project_id != null ){
		$asana->updateProject(
			$project_id,
			$array_asana
		);
	}else
		$asana->createProject($array_asana);

	$result = $asana->getData();
	if (isset($result->gid)) {
		if( $project_id != null ){
			$array = array(
				'title'		=> $data['title'],
				'description' => $data['description'],
				'permalink'	=> $result->permalink_url,
				'slug' => $data['slug'],
				'project_manager' => $data['project_manager']
			);
			return save_project($array, $project_id);
		}else{
			$array = array(
				'id'		=> $result->gid,
				'title'		=> $data['title'],
				'description' => $data['description'],
				'permalink'	=> $result->permalink_url,
				'slug' => $data['slug'],
				'project_manager' => $data['project_manager'],
				'collaborator' => serialize($data['collaborator'])
			);
			$output = save_project($array);
			if( $output ) return $result->gid;
			else return false;
		}
	} else return false;
}

/**
 * Function permettant de faire la synchronisation des sections d'un projet.
 */
function sync_project_section( $project_id ){
	$asana = connect_asana();
	$sections_all = get_all_sections();
	$sections_asana = $asana->getProjectSections($project_id);
	if ($asana->getData() != null) {
		foreach ($asana->getData() as $sections) {
			$sync = true;
			foreach ($sections_all as $section) {
				if ($sections->gid == $section->id) {
					$sync = false;
				}
			}
			if ($sync) {
				$data2 = array(
					'id' 		=> $sections->gid,
					'project_id' => $project_id,
					'section_name'		=> $sections->name
				);
				// Sauvegarde des sections inexistante dans la bdd
				save_new_sections($data2);
			}
		}
	}
}

/**
 * Sync des tags en catégorie
 */
function sync_tag(){
	$asana = connect_asana();
	$categories = get_categories_task();
	$asana->getWorkspaceTags(get_workspace());
	if( $asana->getData() != null ){
		foreach ($asana->getData() as $categorie_asana) {
			$sync = true;
			if ($categories == null) $sync = true;
			else{
				foreach ($categories as $categorie) {
					$key_asana = str_replace(" ", "_", strtolower($categorie_asana->name));
					if ( $key_asana == $categorie->categories_key) { $sync = false; }
				}
			}
			if( $sync ){
				$key_asana = str_replace(" ", "_", strtolower($categorie_asana->name));
				$datas = array('id' => $categorie_asana->gid, 'categories_key' => $key_asana, 'categories_name' => ucfirst( $categorie_asana->name ));
				save_new_categories( $datas, null, true );
			}
		}
	}
	return 'tag';
}

/**
 * Sync des projects et sections
 */
function sync_projets()
{
	// See class comments and Asana API for full info
	$projects = get_project_();
	$asana = connect_asana();
	$asana->getProjects();
	if ($asana->getData() != null) {
		foreach ($asana->getData() as $project_asana) {
			$sync = true;
			if ($projects == null) $sync = true;
			else {
				if( $project_asana->gid == get_option('_project_manager_id') ) $sync = false;
				foreach ($projects as $project) {
					if ($project_asana->gid == $project->id) { $sync = false; }
				}
			}
			if ($sync) {
				$asana->getProjectStories($project_asana->gid);
				if( isset( $asana->getData()[0]->created_by->gid ) ){
					$created = $asana->getData()[0]->created_by->gid;
				}else $created = null;
				$asana->getProject($project_asana->gid);
				$project_asana_info = $asana->getData();
				if( ( $project_asana_info->workspace->gid == get_option('_asana_workspace_id') ) || ( $project_asana->gid == get_option('_project_manager_id') ) ){
					if ($project_asana->gid == get_option('_project_manager_id')) $project_manager = NULL;
					else $project_manager = get_user_asana_id($created);

					if( isset( $project_asana_info->notes ) ) $description_project = $project_asana_info->notes;
					else $description_project = null;
					$data1 = array(
						'id' => $project_asana->gid,
						'title' => $project_asana->name,
						'description' => $description_project,
						'permalink' => $project_asana_info->permalink_url,
						'slug' => str_replace(" ", ",", strtolower($project_asana->name)),
						'project_manager' => $project_manager,
						'collaborator' => get_asana_collaborator($project_asana->gid)
					);
					// Sauvegarde des projets inexistant dans la bdd
					save_project($data1);
				}
			}
			sync_project_section( $project_asana->gid );
		}
	}
	return 'projet';
}


/**
 * Fonction permettant d'envoyer automatiquement 
 * les mails si la tache est terminée.
 */
function automatique_send_mail( ){
	$worklogs = get_all_worklog('mail_status', 'no'); 	
	$tandp_date = get_option('_orion_tandp_date');								// récupération des tâches dont mail n'est pas encore send
	$emails = get_email_(); // Email
	if( $emails != null ){
		if( $worklogs != null ){
			foreach( $worklogs as $worklog ){ 										//parcourir la list
				$task = get_task_('id', $worklog->id_task);	   						// on récupère les infor de la tâche 
				if( $task[0]->categorie == 'revue' ){ 								// si categorie = revue
					if( strtotime( $task[0]->created_at ) >= $tandp_date ){
						if( $task[0]->dependancies != NULL ){ 							// si dependance est != null
							$dependant = get_task_('id', $task[0]->dependancies); 		//On récupere la dependance
							if( $dependant[0]->categorie == 'implementation' ){
								if( $task[0]->assigne != NULL ){
									$mail_template = get_email_( "0", $task[0]->type_task )[0];
									if( $mail_template != null ){
										$mail_content = $mail_template->content;
										$subject = $mail_template->subject;
										
										$destinataire = get_userdata( $task[0]->assigne )->user_email ;
										$title_main_task = get_task_main( $worklog->id_task );
										$msg = content_msg($dependant[0]->id, $title_main_task, $task[0]->type_task, $mail_content);
										mail_sending_form($destinataire, $subject, $msg);
										update_worklog( array( 'mail_status'=> 'yes' ),array('id_task' => $worklog->id_task), array('%s') );
									}
								}else update_worklog( array( 'mail_status'=> 'unable' ),array('id_task' => $worklog->id_task), array('%s') );
							}else update_worklog( array( 'mail_status'=> 'unable' ),array('id_task' => $worklog->id_task), array('%s') );
						}else update_worklog( array( 'mail_status'=> 'unable' ),array('id_task' => $worklog->id_task), array('%s') );
					}else update_worklog( array( 'mail_status'=> 'unable' ),array('id_task' => $worklog->id_task), array('%s') );
				}else update_worklog( array( 'mail_status'=> 'unable' ),array('id_task' => $worklog->id_task), array('%s') );
			}
		}
	}
}

/**
 * Synchronisation le status des tâches. (Completed or No)
 */
function sync_duedate_task()
{
	$worklog_all = get_all_worklog();
	$asana = connect_asana();
	$mail_status = NULL;
	foreach ($worklog_all as $worklog) {
		$asana->getTask($worklog->id_task);
		$detail_task = $asana->getData();
		if ($worklog->status != $detail_task->completed){
			if( $detail_task->completed ) $mail_status = 'no';
			else $mail_status = NULL;			
			update_worklog( array('finaly_date' => $detail_task->completed_at, 'status' => $detail_task->completed, 'mail_status' => $mail_status), array('id_task' => $worklog->id_task), array('%s', '%s') );
		}
	}

	//Synch objectives status
	$users = get_all_users();
	foreach( $users as $id => $user ){
		$objective_array = array();
		$objective_month = get_objective_of_month(date('m')/1, date('Y'), $id);
		if( $objective_month != null ){
			$objectives = unserialize( $objective_month->objective_section );
			foreach( $objectives as $taskid => $objective ){
				echo 1;
				$asana->getTask($taskid);
				$detail_task = $asana->getData();
				$objective_array += array($taskid => array('objective' => $objective['objective'], 'status' => $detail_task->completed ));
			}
			update_objective( $objective_array, $objective_month->id_objective  );
		}
	}
	return 'duedate';
}

/**
 * Creation de tag pour la catégorisation des tâches
 * 
 * @param string $name
 * 
 * @return int|null
 */
function create_tag( $name ){
	$asana = connect_asana();
	$asana->createTag( $name, array( "workspace" => get_workspace() ) );
	$result = $asana->getData();
	if( $result != null ) return  $result->gid;
	else return null;
}

/**
 * Fonction de synchronisation des objectives du mois
 */
function sync_objectives_month(){
	$asana = connect_asana();
	$objectives = get_objective_of_month();
	$asana->getProjectTasks( get_option('_project_manager_id') );
	$tasks = $asana->getData();
	if( $tasks != null ){
		foreach( $tasks as $task ){
			$asana->getTask($task->gid);
			$task_detail = $asana->getData();
			if( $task_detail->parent == NULL ){
				$sync = true; $exist = false;
				if( $objectives != null ){
					foreach( $objectives as $objective ){
						if( $objective->id_objective == $task->gid ){ $sync = false; $exist = true;
							if( $objective->modify_date != $task_detail->modified_at ){ $sync = true; }
						}
					}
				}
				if( $sync ){
					$objective_array = array();
					$asana->getSubTasks($task->gid);
					$subtasks = $asana->getData();
					foreach( $subtasks as $subtask ){
						$objective_array += array($subtask->gid => array('objective' => $subtask->name, 'status' => ''));
					}
					if( $exist ){ update_objective( $objective_array, $task->gid ); }
					else{
						$id_user = get_user_asana_id( $task_detail->assignee->gid );
						$id_section = $task_detail->memberships[0]->section->gid;
						$objective_tab_save = array(
							'id_objective' 			=> $task->gid,
							'id_user' 				=> $id_user,
							'id_section'			=> $id_section,
							'month_section' 		=> (date('m', strtotime($task_detail->due_on))/1),
							'year_section'			=> date('Y'),
							'duedate_section'		=> $task_detail->due_on,
							'objective_section'		=> serialize($objective_array),
							'section_permalink'		=> $task_detail->permalink_url,
							'modify_date'			=> $task_detail->modified_at
						);
						//Sauvegarde du worklog
						$task_array = array(
							'id' => $task->gid,
							'author_id' => $id_user,
							'project_id' => get_option('_project_manager_id'),
							'section_id' => $id_section,
							'title' => '',
							'permalink_url' => $task_detail->permalink_url,
							'type_task' => 'objective',
							'categorie' => NULL,
							'dependancies' => NULL,
							'description' => NULL,
							'assigne' =>  NULL,
							'duedate' => $task_detail->due_on,
							'created_at' => $task_detail->created_at);
						
							$dataworklog = array(
							'id_task' => $task->gid,
							'finaly_date' => $task_detail->completed_at,
							'status' => $task_detail->completed,
							'evaluation' => NULL,
							'evaluation_date' => NULL,
							'mail_status' => 'cp');
						save_objective($objective_tab_save);
						save_new_task($task_array, $dataworklog);
					}
				}
			}
		}
	}
	return 'objectif';
}

function task_id_only(){
	$array_id = array();
	$tasks = get_task_();
	foreach( $tasks as $task ){
		array_push($array_id, $task->id);
	}
	return $array_id;
}


/**
 * Synchronisation des tâches depuis Asana
 */
function sync_tasks()
{
	$array_task_id = task_id_only();
	$asana = connect_asana();
	$asana->getProjectsInWorkspace(get_option('_asana_workspace_id'));
	if ($asana->getData() != null) {
		foreach ($asana->getData() as $project_asana) {
			if( ! in_array( $project_asana->gid, $array_task_id ) ){
				if (($project_asana->gid) != (get_option('_project_manager_id'))) {
					$asana->getProjectTasks($project_asana->gid);
					$task_asana = $asana->getData();
					if ($task_asana != null) {
						foreach ($task_asana as $task_as) {
							$sync = true;
							//si la task n'est pas dans la bdd, on recupère ces information
							if ($sync) {
								$asana->getTask($task_as->gid);
								$task_info = $asana->getData();
								if( isset( $asana->getData()->assignee->gid ) ) $assig = $asana->getData()->assignee->gid;
								else $assig = null;
								$assigne = get_user_asana_id($assig);
								$section_id = $task_info->memberships[0]->section->gid;
								$array_tab_sub = array();
								if ($task_info->parent != NULL) {
								} else {
									$id_implementation = NULL;
									$id_revue = NULL;
									$id_test = NULL;
									$id_integration = NULL;
									$sub_categorie = NULL;
	
									if (isset($task_info->tags[0]->gid)) {
										$sub_categorie = $task_info->tags[0]->name;
										if ($task_info->tags[0]->gid == get_categories_task(null,'implementation')->id) {
											$sub_categorie == "implementation";
											$id_implementation = $task_as->gid;
										}
										if ($task_info->tags[0]->gid == get_categories_task(null,'revue')->id) {
											$sub_categorie == "revue";
											$id_revue = $task_as->gid;
										}
										if ($task_info->tags[0]->gid == get_categories_task(null,'test')->id) {
											$sub_categorie == "test";
											$id_test = $task_as->gid;
										}
										if ($task_info->tags[0]->gid == get_categories_task(null,'integration')->id) {
											$sub_categorie == "integration";
											$id_integration = $task_as->gid;
										}
									}
	
									$asana->getTaskStories($task_as->gid);
									$task_info1 = $asana->getData()[0];
	
									//savegarde de la tache principale + update de table save
									$array_tab_sub[] = $task_as->gid;//+= array($task_as->gid);
									$data = array(
										'id' => $task_as->gid,
										'author_id' => get_user_asana_id($task_info1->created_by->gid),
										'project_id' => $project_asana->gid,
										'section_id' => $section_id,
										'title' => $task_as->name,
										'permalink_url' => $task_info->permalink_url,
										'type_task' => NULL,
										'categorie' => $sub_categorie,
										'dependancies' => NULL,
										'description' => $task_info->notes,
										'assigne' => $assigne,
										'duedate' => $task_info->due_on,
										'created_at' => $task_info1->created_at
									);
									if( $task_info->completed ) $mail_status = 'no';
									else $mail_status = NULL;
									$dataworklog = array(
										'id_task' => $task_as->gid,
										'finaly_date' => $task_info->completed_at,
										'status' => $task_info->completed,
										'evaluation' => NULL,
										'evaluation_date' => NULL,
										'mail_status' => $mail_status
									);
									save_new_task($data, $dataworklog);
	
									//Partons à la recherche de ces enfants
									$enfant = 0;
									$asana->getSubTasks($task_as->gid);
									$subtask_info = $asana->getData();
									if ($subtask_info != null) {
										//Recherche des enfants de la tâche
										foreach ($subtask_info as $sub_task) {
											$array_tab_sub[] = $sub_task->gid; //+= array($sub_task->gid);
											$asana->getTask($sub_task->gid);
											$info_subtask = $asana->getData();
											if( isset( $asana->getData()->assignee->gid ) ) $sub_assigne = get_user_asana_id($asana->getData()->assignee->gid);
											else $sub_assigne = null;
											if (isset($info_subtask->tags[0]->gid)) {
												$sub_categorie = $info_subtask->tags[0]->name;
												if ($info_subtask->tags[0]->gid == get_categories_task(null,'implementation')->id) {
													$sub_categorie == "implementation";
													$id_implementation = $sub_task->gid;
												}
												if ($info_subtask->tags[0]->gid == get_categories_task(null,'revue')->id) {
													$sub_categorie == "revue";
													$id_revue = $sub_task->gid;
												}
												if ($info_subtask->tags[0]->gid == get_categories_task(null,'test')->id) {
													$sub_categorie == "test";
													$id_test = $sub_task->gid;
												}
												if ($info_subtask->tags[0]->gid == get_categories_task(null,'integration')->id) {
													$sub_categorie == "integration";
													$id_integration = $sub_task->gid;
												}
											}
											$asana->getTaskStories($sub_task->gid);
											$sub_task_info1 = $asana->getData()[0];
											$sub_data = array(
												'id' => $sub_task->gid,
												'author_id' => get_user_asana_id($sub_task_info1->created_by->gid),
												'project_id' => $project_asana->gid,
												'section_id' => $section_id,
												'title' => $info_subtask->name,
												'permalink_url' => $info_subtask->permalink_url,
												'type_task' => NULL,
												'categorie' => $sub_categorie,
												'dependancies' => NULL,
												'description' => $info_subtask->notes,
												'assigne' => $sub_assigne,
												'duedate' => $info_subtask->due_on,
												'created_at' => $sub_task_info1->created_at
											);
											if( $info_subtask->completed ) $mail_status = 'no';
											else $mail_status = NULL;
	
											$sub_dataworklog = array(
												'id_task' => $sub_task->gid,
												'finaly_date' => $info_subtask->completed_at,
												'status' => $info_subtask->completed,
												'evaluation' => NULL,
												'evaluation_date' => NULL,
												'mail_status' => $mail_status
											);
											$subarray = array('id' => $sub_task->gid, 'id_task_parent' => $task_as->gid);
											save_new_task($sub_data, $sub_dataworklog, $subarray);
										}
									}
	
									if ($id_implementation != NULL) {
										if ($id_revue != NULL) {
											update_task_dependance($id_implementation, $id_revue);
											$enfant = $enfant + 1;
										}
										if ($id_test != NULL) {
											update_task_dependance($id_implementation, $id_test);
											$enfant = $enfant + 1;
										}
										if ($id_integration != NULL) {
											update_task_dependance($id_implementation, $id_integration);
										}
									}
									if ($enfant == 2) $type_task = 'developper';
									else $type_task = 'normal';
									update_type_task($array_tab_sub, $type_task);
								}
							}
						}
					}
				}
			}
		}
	}
	return 'task';
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
 * Fonction permettant de synchroniser les employes depuis asana
 */
function syncEmployeesFromAsana(){
	$asana = connect_asana();
	$asana->getUsers();
	if( $asana->getData() != null ){
		foreach( $asana->getData() as $employe ){
			get_user_asana_id( $employe->gid );
		}
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
		'assignee' 			=> get_userdata($array['assign'])->user_email,
		'due_on' 			=> $array['duedate'],
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
			'categorie' => NULL,
			'dependancies' => NULL,
			'description' => $array['description'],
			'assigne' => $array['assign'],
			'duedate' => $task_asana->due_on,
			'created_at' => $task_asana->created_at
		);

		$dataworklog = array(
			'id_task' => $taskId,
			'finaly_date' => $task_asana->completed_at,
			'status' => $task_asana->completed,
			'evaluation' => NULL,
			'evaluation_date' => NULL,
			'mail_status' => NULL
		);
		$output = save_new_task($data_add, $dataworklog);
		if (!$output) return false;
	}
	if (isset($data['parametre']['subtask'])) {
		return save_new_subtask($data['parametre']['subtask'], $taskId);
	} else return true;
}

/**
 * Fonction permettant de créer une nouvelle section d'un project manager en utilisant son nom 
 * 
 * @param int $projectmanager
 * 
 * @return bool
 */
function save_objective_section()
{
	$users = get_users(array( 'fields' => array( 'id' ) ));
	foreach( $users as $user ){
		if( is_project_manager( $user->id ) != null ){
			$section_name =  get_userdata($user->id)->display_name;
			$project = get_option('_project_manager_id');
			$sectionExist = section_exist($section_name, $project);
			if ($sectionExist) return false;
			else {
				$asana = connect_asana();
				$asana->createSection(
					$project,
					array("name" => $section_name)
				);
				$asana_output = $asana->getData();
				if (isset($asana_output->gid)) {
					$data2 = array(
						'id' 		=> $asana_output->gid,
						'project_id' => $project,
						'section_name'		=> $asana_output->name
					);
				} else return false;
				return save_new_sections($data2);
			}
		}
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
	$id_implementation = NULL;
	$id_revue = NULL;
	$id_test = NULL;
	$id_integration = NULL;
	foreach ($data as $array) {
		if ($array['categorie'] == 'implementation') {
			$tags = get_categories_task(null,'implementation')->id;
		}
		if ($array['categorie'] == 'revue') {
			$tags = get_categories_task(null,'revue')->id;
		}
		if ($array['categorie'] == 'test') {
			$tags = get_categories_task(null,'test')->id;
		}
		if ($array['categorie'] == 'integration') {
			$tags = get_categories_task(null,'integration')->id;
		}
		$result = $asana->createSubTask(
			$parent_id,
			array(
				'name' 				=>	$array['title'],
				'assignee_section' 	=> $array['section_project'],
				'notes' 			=> $array['description'],
				'assignee' 			=> get_userdata($array['assign'])->user_email,
				'due_on' 			=> $array['duedate'],
				'tags'				=> [$tags]
			)
		);
		$output = $asana->getData();
		if (isset($output->gid)) {
			$taskId = $output->gid;
			if ($array['categorie'] == 'implementation') {
				$id_implementation = $taskId;
			}
			if ($array['categorie'] == 'revue') {
				$id_revue = $taskId;
			}
			if ($array['categorie'] == 'test') {
				$id_test = $taskId;
			}
			if ($array['categorie'] == 'integration') {
				$id_integration = $taskId;
			}

			$data_add = array(
				'id' => $taskId,
				'author_id' => get_current_user_id(),
				'project_id' => $array['project'],
				'section_id' => $array['section_project'],
				'title' => $array['title'],
				'permalink_url' => $output->permalink_url,
				'type_task' => $array['type_task'],
				'categorie' => $array['categorie'],
				'dependancies' => NULL,
				'description' => $array['description'],
				'assigne' => $array['assign'],
				'duedate' => $output->due_on,
				'created_at' => $output->created_at
			);

			$dataworklog = array(
				'id_task' => $taskId,
				'finaly_date' => $output->completed_at,
				'status' => $output->completed,
				'evaluation' => NULL,
				'evaluation_date' => NULL,
				'mail_status' => NULL
			);
			$subarray = array('id' => $taskId, 'id_task_parent' => $parent_id);
			$sortir = save_new_task($data_add, $dataworklog, $subarray);
			if (!$sortir) return false;
		} else {
			return 'noadd';
		}
	}
	if ($id_implementation != NULL) {
		if ($id_revue != NULL) update_task_dependance($id_implementation, $id_revue);
		if ($id_test != NULL) update_task_dependance($id_implementation, $id_test);
		if ($id_integration != NULL) update_task_dependance($id_implementation, $id_integration);
	}
	return 'success';
}
