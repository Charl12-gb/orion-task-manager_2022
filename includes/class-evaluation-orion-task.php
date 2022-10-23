<?php
require 'file_modele/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
$upload = wp_upload_dir();
$worklog_evaluation = $upload['basedir'];
$worklog_evaluation_file = $worklog_evaluation . '/worklog_evaluation';

/**
 * Page d'évaluation des tâches
 * 
 * @return mixed
 */
function evaluator_page()
{
	if (isset($_GET['task_id'], $_GET['type_task']) && (!empty($_GET['task_id']) && !empty($_GET['type_task']))) {
		$task_id = sanitize_text_field(htmlentities($_GET['task_id']));
		$type_task = sanitize_text_field(htmlentities($_GET['type_task']));
		$reviewTask = getReviewTaskForEvaluateTask($task_id);
		$user_id = get_current_user_id();
		$taskEvaluate = get_task_('id', $task_id);
		
		if( $taskEvaluate != null ){
			$task = $taskEvaluate[0];
			if( isEvaluateCategorie( $task->categorie ) ){
				$assign = getTaskProjectManager( $task->project_id );
				if( $reviewTask != null ){
					if( $reviewTask->assigne != null ) 
						$assign = $reviewTask->assigne; 
				}
				if( $user_id == $assign){
					if ($type_task == 'normal' || $type_task == 'developper') {
						if (get_evaluation_info($task_id) == null) {
							?>
							<div class="alert alert-danger" role="alert">
								<?php _e('Sorry !', '') ?><br>
								<?php _e('Task Not Found', '') ?>
							</div>
							<?php
						} else {
							if (get_evaluation_info($task_id)->evaluation == null) {
								get_evaluation_form($task_id, $type_task);
							} else {
								?>
								<div class="alert alert-danger" role="alert">
									<?php _e('Sorry !', '') ?><br>
									<?php _e('Task already evaluated', '') ?>
								</div>
								<?php
							}
						}
					} else {
						?>
						<div class="alert alert-danger" role="alert">
							<?php _e('Sorry !', '') ?><br>
							<?php _e('Invalid type', '') ?>
						</div>
						<?php
					}
				}else{
					?>
					<div class="alert alert-danger" role="alert">
						<?php _e('Sorry !', '') ?><br>
						<?php _e('You are not allowed to evaluate this task', '') ?>
					</div>
					<?php
				}
			}else{
				?>
				<div class="alert alert-danger" role="alert">
					<?php _e('Sorry !', '') ?><br>
					<?php _e('Impossible to evaluate this task because it is not to be evaluated.', '') ?>
				</div>
				<?php
			}
		}else{
			?>
			<div class="alert alert-danger" role="alert">
				<?php _e('Sorry !', '') ?><br>
				<?php _e('Task not found', '') ?>
			</div>
			<?php
		}
		
	} else {
		if (!isset($_POST['verifier_nonce_evaluation']) || !wp_verify_nonce($_POST['verifier_nonce_evaluation'], 'save_evaluation_form')) {
		?>
			<div class="alert alert-danger" role="alert">
				<?php _e('Sorry !', '') ?><br>
				<?php _e('No task to evaluate.', '') ?>
			</div>
			<?php
		} else {
			$data = wp_unslash($_POST);
			$n = $data['nbrecriteria'];
			$array = array();
			$task_id = $data['task_id'];
			for ($k = 1; $k <= ($n - 1); $k++) {
				$make = 'make-radio' . $k;
				$note = 'note' . $k;
				$description = 'description' . $k;
				if (isset($data[$make])) {
					if ($data[$make] == ('makeYes' . $k)) {
						$array += array($k => array('note' => htmlentities($data[$note]), 'description' => htmlentities($data[$description])));
					} else {
						$array += array($k => array('note' => 0, 'description' => 'Not make'));
					}
				}
			}
			$save = save_evaluation_info($array, $task_id);
			if ($save) {
			?>
				<div class="alert alert-success" role="alert">
					<?php _e('Task evaluate successfully', '') ?>
				</div>
			<?php
			} else {
			?>
				<div class="alert alert-danger" role="alert">
					<?php _e('Sorry !', '') ?> <br>
				</div>
			<?php
			}
		}
	}
}

/**
 * Evaluation des projects manager à la fin de chaque mois
 */
function evaluation_project_manager($periode=null)
{
	$string = 'last friday of ' . date('F', mktime(0, 0, 0, date('m'), 10)) . ' this year';
	$last_friday = gmdate('Y-m-d', strtotime($string));
	$string2 = 'next day ' . $last_friday;
	$date1 = gmdate('Y-m-d', strtotime($string2));
	$date2 = date('Y-m-d');
	$evaluation_date = strtotime($date1);
	$today_date = strtotime($date2);

	if( $periode != null ){
		evaluateCpTask();
	}else{
		if ($evaluation_date == $today_date) {
			evaluateCpTask();
		}
	}
}

function evaluateCpTask(){
	$objectives = get_objective_of_month(date('m')/1, date('Y'));
	$tab_rapport_month = array();
	foreach ($objectives as $objective) {
		$total = 0;
		$completed = 0;
		$reste = 0;
		$moyenne = 0;
		$month_objectives = unserialize($objective->objective_section);
		foreach ($month_objectives as $key => $month_objective) {
			$total++;
			if ($month_objective['status']) $completed++;
		}
		$reste = $total - $completed;
		$moyenne = ($completed / $total) * 100;
		$array_evaluation = array(
			'objectives' => $month_objectives,
			'evaluation' => array(
				'total' => $total,
				'completed' => $completed,
				'reste' => $reste,
				'moyenne' => $moyenne
			)
		);
		$output = save_evaluation_info($array_evaluation, $objective->id_objective);
		if ($output) array_push($tab_rapport_month, $objective->id_objective);
	}
}


function worklog_file($month=null){
	$users = get_all_users();
	$report = array();
	foreach( $users as $user_id => $user ){
		if( $month != null ){
			$output = download_worklog( $user_id, $month );
		}else{
			$output = download_worklog( $user_id );
		}
		$report += $output;
	}
	if( $report != null ){
		Task_Manager_Builder::sent_worklog_mail_( 'report', $report );
	}
}


/**
 * Function permettant de télécharger le worklog d'un membre
 * 
 * @param int $user_id
 * @param string $month
 * 
 */
function download_worklog($user_id, $month=null)
{
	$upload = wp_upload_dir();
	$worklog_evaluation = $upload['basedir'];
	$worklog_evaluation_file = $worklog_evaluation . '/worklog_evaluation';
	$url_file = plugin_dir_path(__FILE__) . 'file_modele/template-worklog.xlsx';
	$url_save_file = $worklog_evaluation_file .'/';
	
	$reader = IOFactory::createReader('Xlsx');
	$spreadsheet = $reader->load( $url_file );
	$name_user = get_userdata($user_id)->display_name;
	if( $month == null ){
		$nxtm = strtotime("previous month");
		$date_evaluation =  date("m-Y", $nxtm);
		$date_worklog = date("M-Y", $nxtm);
	}else{
		$strMont = mktime(0, 0, 0, $month, 1, date('Y'));
		$date_evaluation = date("m-Y", $strMont);
		$date_worklog = date("M-Y", $strMont);
	}
	$tasks = get_task_('assigne', $user_id, 'worklog', $date_evaluation);
	
	//Worklog
	$spreadsheet->setActiveSheetIndex(0);
	$spreadsheet->getActiveSheet()->setCellValue('C2', $name_user);
	$spreadsheet->getActiveSheet()->setCellValue('C3', $date_worklog);	

	$nemberRow=5;
	$numberFiels = 0; $numberFielsNormal=0;
	$custom = 0;
	$chaine = 0; $criteria1 = 0; $criteria2 = 0; $criteria3 = 0; $criteria4 = 0; $criteria5 = 0;

	if( $tasks != null ){
		foreach( $tasks as $task ){
			$numberFiels++;
			$status='NO';
			if( get_task_status($task->id, 'yes') ) $status = 'YES';
	
			if( get_task_main( $task->id ) != null ) $task_title = $task->title . '( ' . get_task_main( $task->id ) . ' )';
			else $task_title = $task->title;
	
			$spreadsheet->getActiveSheet()->insertNewRowBefore($nemberRow);
			$spreadsheet->getActiveSheet()->mergeCells('C'. $nemberRow .':D'. $nemberRow .'');
	
			$this_task = unserialize( $task->evaluation );
			if( $task->type_task == 'developper' ){
				$spreadsheet->getActiveSheet()
					->setCellValue('B'.$nemberRow, $numberFiels)
					->setCellValue('C'.$nemberRow, $task_title)
					->setCellValue('E'.$nemberRow, '=(G'. $nemberRow .'+H'. $nemberRow .'+I'. $nemberRow .'+J'. $nemberRow .'+K'. $nemberRow .')')
					->setCellValue('F'.$nemberRow, $status)
					->setCellValue('G'.$nemberRow, $this_task[1]['note'])
					->setCellValue('H'.$nemberRow, $this_task[2]['note'])
					->setCellValue('I'.$nemberRow, $this_task[3]['note'])
					->setCellValue('J'.$nemberRow, $this_task[4]['note'])
					->setCellValue('K'.$nemberRow, $this_task[5]['note'])
					->setCellValue('E1', $numberFiels);
				$chaine += (($this_task[1]['note'])+($this_task[2]['note'])+($this_task[3]['note'])+($this_task[4]['note'])+($this_task[5]['note']));
				$criteria1 += $this_task[1]['note']; $criteria2 += $this_task[2]['note']; $criteria3 += $this_task[3]['note']; $criteria4 += $this_task[4]['note']; $criteria5 += $this_task[5]['note'];
			}
			if( $task->type_task == 'normal' ){
				$spreadsheet->getActiveSheet()
					->setCellValue('B'.$nemberRow, $numberFiels)
					->setCellValue('C'.$nemberRow, $task_title)
					->setCellValue('E'.$nemberRow, '=(G'. $nemberRow .'+H'. $nemberRow . '+K'. $nemberRow .')')
					->setCellValue('F'.$nemberRow, $status)
					->setCellValue('G'.$nemberRow, $this_task[3]['note'])
					->setCellValue('H'.$nemberRow, $this_task[1]['note'])
					->setCellValue('I'.$nemberRow, '-')
					->setCellValue('J'.$nemberRow, '-')
					->setCellValue('K'.$nemberRow, $this_task[2]['note'])
					->setCellValue('E1', $numberFiels);
				$chaine += (($this_task[1]['note'])+($this_task[2]['note'])+($this_task[3]['note']));
				$numberFielsNormal++;
				$criteria1 += $this_task[1]['note']; $criteria2 += $this_task[2]['note']; $criteria3 += $this_task[3]['note'];
			}
			$nemberRow++;
			$custom += ( (($this_task[1]['note']) + ( $this_task[2]['note'] ) + ( $this_task[3]['note'] )) );
		}
		
		$performance = $chaine/$numberFiels;
		$spreadsheet->getActiveSheet()->setCellValue('K1', $performance);
	
		$customs_job = $custom / $numberFiels;
	
		$good_performance = '';
		$bad_performance = '';
		if( ($criteria1/$numberFiels) >= 35 ) $good_performance .= ' Work quality | ';
		else $bad_performance .= ' Work quality | ';
	
		if( ($criteria2/$numberFiels) >= 10 ) $good_performance .= 'Deadline | ';
		else $bad_performance .= 'Deadline | ';
	
		if( ($criteria3/$numberFiels) >= 4 ) $good_performance .= 'Commit | ';
		else $bad_performance .= 'Commit | ';
	
		if( ($numberFiels-$numberFielsNormal) > 0 ){
			$perfo_coll = ($criteria4/($numberFiels-$numberFielsNormal));
		}else{
			$perfo_coll = 10;
		}

		if( ($numberFiels-$numberFielsNormal) > 0 ){
			$perfo_cons = ($criteria5/($numberFiels-$numberFielsNormal));
		}else{
			$perfo_cons = 10;
		}

		if( $perfo_coll >= 10 ) $good_performance .= 'Collaboration | ';
		else $bad_performance .= 'Collaboration | ';

		if( $perfo_cons >= 10 ) $good_performance .= 'Work consistency | ';
		else $bad_performance .= 'Work consistency | ';
	
		//Rapport d'évaluation
	
		$spreadsheet->setActiveSheetIndex(1);
		$spreadsheet->getActiveSheet()->setCellValue('C1', $date_worklog);
		$spreadsheet->getActiveSheet()->setCellValue('C2', $name_user);
		
		//General Peformance
		if( $performance >= 0 && $performance <= 39 ) $spreadsheet->getActiveSheet()->setCellValue('D6', $performance);
		else if( $performance >= 40 && $performance <= 60 ) $spreadsheet->getActiveSheet()->setCellValue('E6', $performance);
		else if( $performance >= 61 && $performance <= 85 ) $spreadsheet->getActiveSheet()->setCellValue('F6', $performance);
		else $spreadsheet->getActiveSheet()->setCellValue('G6', $performance);
	
		//Initiative & Creativity
		if( $customs_job >= 0 && $customs_job <= 39 ) $spreadsheet->getActiveSheet()->setCellValue('D8', $customs_job);
		else if( $customs_job >= 40 && $customs_job <= 60 ) $spreadsheet->getActiveSheet()->setCellValue('E8', $customs_job);
		else if( $customs_job >= 61 && $customs_job <= 85 ) $spreadsheet->getActiveSheet()->setCellValue('F8', $customs_job);
		else $spreadsheet->getActiveSheet()->setCellValue('G8', $customs_job);
	
		$spreadsheet->getActiveSheet()->setCellValue('B20', $good_performance);
		$spreadsheet->getActiveSheet()->setCellValue('C20', $bad_performance);
	

		$url_ = $worklog_evaluation_file . '/'. $date_worklog;
		if( ! file_exists( $url_ ) ) {
			mkdir( $url_ );
		}
		
		$file_name = $url_save_file . $date_worklog . '/' . $name_user .'_worklog.xlsx';
		if( file_exists( $file_name ) ){
			unlink( $file_name );
		}
		$writer = new Xlsx($spreadsheet);
		$writer->save($file_name);

		//Plan de perdormance
		$minMoyenne = unserialize( get_option('_performance_parameters') )['moyenne'];
		if( $performance < $minMoyenne ){
			Task_Manager_Builder::sent_worklog_mail_( $file_name, array(), $user_id );
		}
		return array( $name_user .'_worklog.xlsx' => $file_name );
	}
	return array();
}

function evaluation_cp( $month=null, $id_cp=null ){
	$nxtm = strtotime("previous month");
	if( $month != null ){  $month = $month/1; }
	else { $month =  date("m", $nxtm)/1; }
	$url_file = plugin_dir_path(__FILE__) . 'file_modele/template-cp-report.xlsx';

	$upload = wp_upload_dir();
	$worklog_evaluation = $upload['basedir'];
	$worklog_evaluation_file = $worklog_evaluation . '/worklog_evaluation';
	$url_save_file = $worklog_evaluation_file .'/';
	$date_eval = $month . '-' . date('Y') . '_cp_Evaluation';

	$url_ = $worklog_evaluation_file . '/'. $date_eval;
	if( ! file_exists( $url_ ) ) {
		mkdir( $url_ );
	}
	
	$reader = IOFactory::createReader('Xlsx');
	$spreadsheet = $reader->load( $url_file );
	
	$cellValues = $spreadsheet->getActiveSheet()->rangeToArray('A2:K3');

	if( $id_cp == null ){
		$users = get_all_users();
		$nemberRow=4;
		foreach( $users as $id => $user ){
			$numberFiels = 1;
			$name_user = get_userdata($id)->display_name;
			$objective_month = get_objective_of_month($month, date('Y'), $id);

			if( $objective_month != null ){
				$info_evaluation = unserialize( $objective_month->evaluation )['evaluation'];
				$minMoyenne = unserialize( get_option('_performance_parameters') )['moyenne'];
				$completed = $info_evaluation['completed'];
				$moyenne = $info_evaluation['moyenne'];
				if( $nemberRow > 4 ){
					$spreadsheet->getActiveSheet()->setCellValue('C' . ($nemberRow+1), $name_user)
						->setCellValue('E' . ($nemberRow+1), $completed)
						->setCellValue('K' . ($nemberRow+1), $moyenne);

					$spreadsheet->getActiveSheet()->fromArray($cellValues, null, 'A'. ($nemberRow+1));
					$spreadsheet->getActiveSheet()->getStyle('B'. ($nemberRow+1) . ':K'. ($nemberRow+1))->getFill()
							->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
							->getStartColor()->setARGB('F0B27A');
					$spreadsheet->getActiveSheet()->getStyle('B'. ($nemberRow+2) . ':K'. ($nemberRow+2))->getFill()
							->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
							->getStartColor()->setARGB('FDFEFE');
					$spreadsheet->getActiveSheet()->mergeCells('F'. ($nemberRow+1) . ':J'. ($nemberRow+1));
					$spreadsheet->getActiveSheet()->mergeCells('C'. ($nemberRow+2) . ':D'. ($nemberRow+2));
	
					$spreadsheet->getActiveSheet()->getStyle('B'. ($nemberRow+1) . ':K'. ($nemberRow+2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
					$spreadsheet->getActiveSheet()->getStyle('B'. ($nemberRow+1) . ':K'. ($nemberRow+2))->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
	
					$borders = [
						'borders' => [
							'allBorders' => [
								'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
								'color' => ['argb' => '000'],
							],
						],
					];
	
					$styleArray = array(
						'font'  => array(
							'bold' => true,
							'size'  => 12,
							'name' => 'Roboto'
						)
					);
	
					$spreadsheet->getActiveSheet()->getStyle('B'. ($nemberRow+1) . ':K'. ($nemberRow+2))->applyFromArray($borders);
					$spreadsheet->getActiveSheet()->getStyle('B'. ($nemberRow+1) . ':K'. ($nemberRow+2))->applyFromArray($styleArray);
					
					if( $moyenne < $minMoyenne ){
						$spreadsheet->getActiveSheet()->getStyle('F' . ($nemberRow+1) . ':K' . ($nemberRow+1))->getFill()
							->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
							->getStartColor()->setARGB('FFFF0000');
					}else{
						$spreadsheet->getActiveSheet()->getStyle('F' . ($nemberRow+1) . ':K' . ($nemberRow+1))->getFill()
							->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
							->getStartColor()->setARGB('ABEBC6');
					}

					$nemberRow += 3;
				}
				else{
					$spreadsheet->getActiveSheet()->setCellValue('C1', $month . '-' . date('Y') )
						->setCellValue('C2', $name_user)
						->setCellValue('E2', $completed)
						->setCellValue('K2', $moyenne);
					if( $moyenne < $minMoyenne ){
						$spreadsheet->getActiveSheet()->getStyle('F2:K2')->getFill()
							->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
							->getStartColor()->setARGB('FFFF0000');
					}else{
						$spreadsheet->getActiveSheet()->getStyle('F2:K2')->getFill()
							->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
							->getStartColor()->setARGB('ABEBC6');
					}		
				}
				$objectives = unserialize( $objective_month->objective_section );
				foreach( $objectives as $objective ){
					if( $objective['status'] ) $status = 'YES';
					else $status = 'NO';
					
					$spreadsheet->getActiveSheet()->insertNewRowBefore($nemberRow);
					$spreadsheet->getActiveSheet()->mergeCells('C'. $nemberRow . ':D'. $nemberRow);
					$spreadsheet->getActiveSheet()->setCellValue('B'.$nemberRow, $numberFiels)
						->setCellValue('C'.$nemberRow, $objective['objective'])
						->setCellValue('E'.$nemberRow, '-')
						->setCellValue('F'.$nemberRow, $status)
						->setCellValue('G'.$nemberRow, '-')
						->setCellValue('H'.$nemberRow, '-')
						->setCellValue('I'.$nemberRow, '-')
						->setCellValue('J'.$nemberRow, '-')
						->setCellValue('K'.$nemberRow, '-');
					
					$nemberRow++;
					$numberFiels++;
				}
				cpEvaluateFile($id, $month, $date_eval, $objectives, $info_evaluation);
			}
		}
	}
	$file_name = $url_save_file . $month. '-' . date('Y') .'_evaluation_cp.xlsx';
	if( file_exists( $file_name ) ){
		unlink( $file_name );
	}
	$writer = new Xlsx($spreadsheet);
	$writer->save($file_name);
	Task_Manager_Builder::sent_worklog_mail_( $file_name );
}

function cpEvaluateFile( $id , $month, $date_eval, $objectives, $info_evaluation ){
	$upload = wp_upload_dir();
	$worklog_evaluation = $upload['basedir'];
	$worklog_evaluation_file = $worklog_evaluation . '/worklog_evaluation';
	$url_save_file = $worklog_evaluation_file .'/';
	
	$url_file = plugin_dir_path(__FILE__) . 'file_modele/template-cp-evaluation.xlsx';
	$reader = IOFactory::createReader('Xlsx');
	$spreadsheet = $reader->load( $url_file );
	$spreadsheet->setActiveSheetIndex(0);

	$nemberRow=4;
	$numberFiels = 1;
	$name_user = get_userdata($id)->display_name;

	$minMoyenne = unserialize( get_option('_performance_parameters') )['moyenne'];
	$completed = $info_evaluation['completed'];
	$moyenne = $info_evaluation['moyenne'];

	$spreadsheet->getActiveSheet()->setCellValue('C1', $month . '-' . date('Y') )
		->setCellValue('C2', $name_user)
		->setCellValue('D1', "PROJECT MANAGER EVALUATION")
		->setCellValue('E2', $completed)
		->setCellValue('K2', $moyenne);

	if( $moyenne < $minMoyenne ){
		$spreadsheet->getActiveSheet()->getStyle('F2:K2')->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setARGB('FFFF0000');
	}else{
		$spreadsheet->getActiveSheet()->getStyle('F2:K2')->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setARGB('ABEBC6');
	}		
				
	foreach( $objectives as $objective ){
		if( $objective['status'] ) $status = 'YES';
		else $status = 'NO';
					
		$spreadsheet->getActiveSheet()->insertNewRowBefore($nemberRow);
		$spreadsheet->getActiveSheet()->mergeCells('C'. $nemberRow . ':D'. $nemberRow);
		$spreadsheet->getActiveSheet()->setCellValue('B'.$nemberRow, $numberFiels)
			->setCellValue('C'.$nemberRow, $objective['objective'])
			->setCellValue('E'.$nemberRow, '-')
			->setCellValue('F'.$nemberRow, $status)
			->setCellValue('G'.$nemberRow, '-')
			->setCellValue('H'.$nemberRow, '-')
			->setCellValue('I'.$nemberRow, '-')
			->setCellValue('J'.$nemberRow, '-')
			->setCellValue('K'.$nemberRow, '-');			
		$nemberRow++;
		$numberFiels++;
	}

	// Evaluation
	$spreadsheet->setActiveSheetIndex(1);
	$spreadsheet->getActiveSheet()->setCellValue('C1', $month . '-' . date('Y') );
	$spreadsheet->getActiveSheet()->setCellValue('C2', $name_user);
		
	//General Peformance
	if( $moyenne >= 0 && $moyenne <= 39 ) $spreadsheet->getActiveSheet()->setCellValue('D6', $moyenne);
	else if( $moyenne >= 40 && $moyenne <= 60 ) $spreadsheet->getActiveSheet()->setCellValue('E6', $moyenne);
	else if( $moyenne >= 61 && $moyenne <= 85 ) $spreadsheet->getActiveSheet()->setCellValue('F6', $moyenne);
	else $spreadsheet->getActiveSheet()->setCellValue('G6', $moyenne);
	
	$file_name = $url_save_file . $date_eval . '/' . $name_user .'_cp.xlsx';
	if( ! file_exists( $file_name ) ){
		$writer = new Xlsx($spreadsheet);
		$writer->save($file_name);
	}
}

/**
 * Fonction permettant de traiter les variables contenue dans le texte d'envoi
 * 
 * @param int $id_task
 * @param string $type_task
 * @param string $content
 * 
 * @return string
 */
function content_msg($id_task, $title_main_task, $type_task, $content)
{
	$task = get_task_('id', $id_task, 'yes')[0];
	$variable_table = array('task_name', 'project_name', 'task_link', 'form_link');
	preg_match_all('/{{(.*?)}}/', $content, $outputs);
	foreach ($outputs[1] as $output) {
		$output . '=> ' . in_array($output, $variable_table) . '<br>';
		if (in_array($output, $variable_table)) {
			if ($output == 'task_name')
				$val = "<strong style='color:blue'>" . $task->title . "</strong>";
			else if ($output == 'project_name')
				$val = "<strong style='color:blue'>" . get_project_title($task->project_id) . "</strong>";
			else if ($output == 'task_link')
				$val = "<a class='btn-link' href='" . $task->permalink_url . "'>" . $task->permalink_url . "</a>";
			else if ($output == 'form_link')
				$val = "<a style='text-align:center' href='" . get_site_url() . "/task-evaluation?task_id=" . $id_task . "&type_task=" . $type_task . "'>here</a>";
			else
				$val = 'inconnu';
			$content = preg_replace("/{{" . $output . "}}/", "$val", $content);
		} else {
			$content = preg_replace("/{{" . $output . "}}/", "inconnu", $content);
		}
	}
	$content .= "<br><hr><h5><span style='text-decoration:underline; color:blue;'>Main Task : </span>$title_main_task</h5><hr>";
	return  nl2br($content);
}

/**
 * Fonction d'envoi d'email
 * 
 * @param string $destinataire
 * @param string $subject
 * @param string $message
 * 
 * @return bool
 */
function  mail_sending_form($destinataire, $subject, $message)
{
	$sender_info = unserialize(get_option('_sender_mail_info'));
	// Pour les champs $expediteur / $copie / $destinataire, séparer par une virgule s'il y a plusieurs adresses
	$expediteur = $sender_info['sender_email'];
	$copie = $expediteur;
	$copie_cachee = $expediteur;
	$headers  = 'MIME-Version: 1.0' . "\n"; // Version MIME
	$headers .= 'Content-type: text/html; charset=UTF-8' . "\n"; // l'en-tete Content-type pour le format HTML
	$headers .= 'Reply-To: ' . $expediteur . "\n"; // Mail de reponse
	$headers .= 'From: "' . $sender_info['sender_name'] . '"<' . $expediteur . '>' . "\n"; // Expediteur
	$headers .= 'Delivered-to: ' . $destinataire . "\n"; // Destinataire
	$headers .= 'Cc: ' . $copie . "\n"; // Copie Cc
	$headers .= 'Bcc: ' . $copie_cachee . "\n\n"; // Copie cachée Bcc        
	$message = '<div style="width: 100%;text-align: center;color:black"><h3 style="text-align: center; font-weight: bold;">' . $subject . '</h3><p>' . $message . '</p></div>';

	return (wp_mail($destinataire, $subject, $message, $headers));
}

/**
 * Formulaire d'évaluation à la fin d'une tâche
 */
function get_evaluation_form($task_id, $type_task)
{
	$task = get_task_('id', $task_id)[0];
	$get_criteria = get_option('_evaluation_criterias');
	$criterias =  unserialize($get_criteria);
	?>
	<div class="container card">
		<?php
			if ($type_task == 'normal' || $type_task == 'developper') {
					?>
					<div class="row">
						<div class="col-sm-6 alert alert-success">
							<div style="width: 100%;text-align: center;color:black">
								<h6 class="pt-2" style="text-align: center; font-weight: bold;"><?= esc_html($task->title) ?></h6>
								<p>Find task details <a href="<?= esc_url($task->permalink_url) ?>" class="text-primary">here</a></p>
							</div>
						</div>
						<div class="col-sm-6 alert alert-primary">
							<div class="row pb-2 pt-2 text-center">
								<div class="col-sm-3"><strong style="text-decoration: underline;">Status: <br></strong> <?= esc_html(get_task_status($task_id)) ?> </div>
								<div class="col-sm-4"><strong style="text-decoration: underline;">Due Date: <br></strong> <?= esc_html($task->duedate) ?></div>
								<div class="col-sm-5"><strong style="text-decoration: underline;">Date Completed: <br></strong> <?php if (!get_task_status($task_id, 'yes')) echo '--- -- --';
																																else echo esc_html($task->finaly_date); ?></div>
							</div>
						</div>
					</div>
					<?php
					if (!get_task_status($task_id, 'yes')) {
						?>
						<small id="emailHelp" class="form-text text-muted text-center"><?= esc_html('The task being evaluated is not yet marked as complete. ') ?><br> <?= esc_html('Make sure of that or take that into account. ') ?></small>
						<?php
					}
					?>
					<button class="btn btn-outline-primary" data-toggle="modal" data-target="#detail_criteria">Readme before review </button>
					<?php
					if ($type_task == 'normal') get_form_evaluation($criterias['normal'], $task_id);
					else get_form_evaluation($criterias['developper'], $task_id);
			} else {
				?>
				<div class="alert alert-danger" role="alert">
					<?= esc_html('Error !') ?>
				</div>
				<?php
			}
		?>
	</div>
<?php
}

function get_form_evaluation($criterias, $id_task)
{
	$task = get_task_('id', $id_task);
	$get_criteria = get_option('_evaluation_criterias');
	$criterias_all =  unserialize($get_criteria);
?>
	<div class="container row">

	</div>
	<div class=" text-center" role="alert">

	</div>
	<hr>
	<form method="POST" action="<?= get_site_url() . "/task-evaluation" ?>" class="alert alert-secondary">
		<h4 class="pl-5">Evaluation</h4>
		<hr>
		<?php
		wp_nonce_field('save_evaluation_form', 'verifier_nonce_evaluation');
		$i = 1;
		foreach ($criterias as $criteria) {
		?>
			<div>
				<div class="form-row pb-1 card-body ">
					<div class="col-sm-5">
						<div class="form-check mb-2">
							<div class="row card-header">
								<div class="col-sm-8">
									<strong><?= esc_html($criteria['criteria']) ?> : </strong>
								</div>
								<div class="col-sm-2">
									<input class="form-check-input" type="radio" onclick="block_(<?= $i ?>)" id="makeYes" required value="makeYes<?= $i ?>" name="make-radio<?= $i ?>">
									<label class="form-check-label" for="make">
										Yes
									</label>
								</div>
								<div class="col-sm-2">
									<input class="form-check-input" onclick="hide_(<?= $i ?>)" type="radio" id="makeNo" required value="makeNo<?= $i ?>" name="make-radio<?= $i ?>">
									<label class="form-check-label" for="autoSizingCheck">
										No
									</label>
								</div>
							</div>
						</div>
					</div>
					<div class="col-sm-7" id="<?= $i ?>" style="display: none;">
						<div class="row">
							<div class="col-sm-4">
								<input type="number" min="0" max="<?= esc_html($criteria['note']) ?>" name="note<?= $i ?>" name="note<?= $i ?>" class="form-control" placeholder="0">
							</div>
							<div class="col-sm-8">
								<textarea class="form-control m-0" id="description<?= $i ?>" name="description<?= $i ?>" rows="1" placeholder="Description ..."></textarea>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php $i++;
		}
		?>
		<hr>
		<input type="hidden" value="<?= esc_html($id_task) ?>" name="task_id" id="task_id">
		<input type="hidden" value="<?= $i ?>" name="nbrecriteria" id="nbrecriteria">
		<button class="btn btn-outline-primary" type="submit">Submit</button>
		<hr>
	</form>

	<div class="modal fade" id="detail_criteria" tabindex="-1" role="dialog" aria-labelledby="detail_criteriaTitle" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLongTitle">Readme before review</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="row">
						<h5 class=""><span style="text-decoration: underline; color:darkgoldenrod">Criteria for a development task</span> : </h5>
						<?php $k = 1;
						foreach ($criterias_all['developper'] as $dev) {
						?>
							<h6> <span>Criteria <?= $k ?> : </span> <?= esc_html($dev['criteria']) ?> <span class="text-danger"> ( <?= esc_html($dev['note']) ?> )</span> <br></h6><br>
							<p><?= esc_html(nl2br($dev['description'])) ?></p>
						<?php
							$k++;
						}
						?>
					</div>
					<hr>
					<div class="row">
						<h5><span style="text-decoration: underline; color:darkgoldenrod">Criteria for a research task</span> : </h5><br>
						<?php $k = 1;
						foreach ($criterias_all['normal'] as $normal) {
						?>
							<h6> <span>Criteria <?= $k ?> : </span> <?= esc_html($normal['criteria']) ?> <span class="text-danger"> ( <?= esc_html($normal['note']) ?> )</span><br> </h6><br>
							<p><?= esc_html(nl2br($normal['description'])) ?></p>
						<?php
							$k++;
						}
						?>
					</div>
					<hr>
					<div class="row alert alert-info p-2">
						<h6 class="pl-5 m-0" style="text-decoration: underline; color:darkgoldenrod">NB : </h6>
						<ul>
							<li>Click on <strong class="text-primary">yes</strong> if the criterion is met.</li>
							<li>If not click on <strong class="text-danger">no</strong> </li>
							<li>Set the correspondind <strong class="text-success">note</strong> </li>
							<li>Give a <strong class="text-success">description</strong> if possible </li>
							<li>Double check before submitting as you will not be able to edit once submitted. Thanks</li>
							<li>Chaque aller retour effectué dans Work Consistency enlève 5 points en dehors de la première revue de code. Code quality est prit en compte lors du premier codage.</li>
						</ul>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>
<?php
}
