<?php

/**
 * Page d'évaluation des tâches
 */
function evaluator_page()
{
	if (isset($_GET['task_id'], $_GET['type_task']) && (!empty($_GET['task_id']) && !empty($_GET['type_task']))) {
		$task_id = htmlentities($_GET['task_id']);
		$type_task = htmlentities($_GET['type_task']);
		if( get_evaluation_info( $task_id )->evaluation == null ){
			get_evaluation_form($task_id, $type_task);
		}else{
			?>
			<div class="alert alert-danger" role="alert">
				Sorry ! <br>
				Task already evaluated
			</div>
			<?php
		}
	} else {
		if( ! isset( $_POST['verifier_nonce_evaluation'] ) || ! wp_verify_nonce( $_POST['verifier_nonce_evaluation'], 'save_evaluation_form' ) ){
			?>
			<div class="alert alert-danger" role="alert">
				Error ! <br>
				No task to evaluate.
			</div>
			<?php
		}else{
			$data = wp_unslash( $_POST );
			$n = $data['nbrecriteria'];
			$array = array();
			$task_id = $data['task_id'];
			for( $k=1; $k<=($n-1); $k++ ){
				$make = 'make-radio' . $k;
				$note = 'note' . $k;
				$description = 'description' . $k;
				if( isset( $data[$make] ) ){
					if( $data[$make] == ('makeYes'.$k)) {
						$array += array( $k => array( 'note' => htmlentities( $data[$note]), 'description' => htmlentities( $data[$description] )) );
					}else{
						$array += array( $k => array( 'note' => 0, 'description' => 'Not make' ) );
					}
				}
			}
			$save = save_evaluation_info( $array, $task_id );
			if( $save ){
				?>
			<div class="alert alert-success" role="alert">
				Task evaluate successfully
			</div>
			<?php
			}else{
				?>
				<div class="alert alert-danger" role="alert">
				Error ! <br>
			</div>
			<?php
			}
		}
	}
}

/**
 * Function permettant de télécharger le worklog d'un membre
 * 
 * @param int $user_id
 * @return string
 */
function download_worklog($user_id)
{
	$alldata = "";
	$tasks = get_task_('assigne', $user_id);
	$m = 1;
	foreach ($tasks as $task) {
		$project_manager = get_userdata($task->author_id)->display_name;
		$project_title = get_project_title($task->project_id);
		$duedate = strtotime($task->duedate);
		if ($task->status == null) {
			$finaly_date = strtotime(date('Y-m-d H:i:s',  strtotime('+1 hours')));
			if ($duedate < $finaly_date) {
				$status = 'Not Completed';
			} elseif ($duedate == $finaly_date) {
				$status = 'Today deadline';
			} else {
				$status = 'Progess';
			}
		} else {
			$finaly_date = strtotime($task->finaly_date);
			if ($duedate >= $finaly_date) {
				$status = 'Render';
			} else {
				$status = 'Completed Before Date';
			}
		}
		$alldata .= "$m,$task->title,$project_title,$project_manager,$task->duedate,$status,$task->evaluation\n";

		$m++;
	}
	$response = "data:text/csv;charset=utf-8,N°,Task Title,Project Title,Responsable,Due Date,Status,Note\n";
	return $response .= $alldata;
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
function content_msg($id_task, $type_task, $content)
{
	$task = get_task_('id', $id_task, 'yes')[0];
	$variable_table = array('task_name', 'project_name', 'task_link', 'form_link');
	preg_match_all('/{{(.*?)}}/', $content, $outputs);
	foreach ($outputs[1] as $output) {
		echo $output . '=> ' . in_array($output, $variable_table) . '<br>';
		if (in_array($output, $variable_table)) {
			if ($output == 'task_name')
				$val = "<strong style='color:blue'>" . $task->title . "</strong>";
			else if ($output == 'project_name')
				$val = "<strong style='color:blue'>" . get_project_title($task->project_id) . "</strong>";
			else if ($output == 'task_link')
				$val = "<a class='btn-link' href='" . $task->permalink_url . "'>" . $task->permalink_url . "</a>";
			else if ($output == 'form_link')
				$val = "<a class='btn-outline-primary' href='" . get_site_url() . "/task-evaluation?task_id=" . $id_task . "&type_task=" . $type_task . "'><button>" . get_site_url() . "/task-evaluation</button></a>";
			else
				$val = 'inconnu';
			$content = preg_replace("/{{" . $output . "}}/", "$val", $content);
		} else {
			$content = preg_replace("/{{" . $output . "}}/", "inconnu", $content);
		}
	}
	return $content;
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
	$copie = $sender_info['sender_email'];
	$copie_cachee = $sender_info['sender_email'];
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
	$task = get_task_('id', $task_id, 'yes')[0];
	$get_criteria = get_option('_evaluation_criterias');
	$criterias =  unserialize($get_criteria);
	?>
	<div class="container">
		<?php
		if ($type_task == 'normal' || $type_task == 'developper') {
		?>
			<div style="width: 100%;text-align: center;color:black">
				<h3 style="text-align: center; font-weight: bold;"><?= $task->title ?></h3>
				<p>Le détails se trouve ici : <a href="<?= $task->permalink_url ?>" class="text-primary"><?= $task->permalink_url ?></a></p>
			</div>
		<?php
		} else {
		?>
			<div class="alert alert-danger" role="alert">
				Error ! <br>
			</div>
		<?php
		}
		if ($type_task == 'normal') get_form_evaluation($criterias['normal'], $task_id);
		else get_form_evaluation($criterias['developper'], $task_id);
		?>
	</div>
<?php
}

function get_form_evaluation($criterias, $id_task)
{
?>
	<div class="alert alert-primary p-2" role="alert">
		Double check before submitting as you will not be able to edit once submitted. Thanks
	</div>
	<hr>
	<form method="POST" action="<?= get_site_url() . "/task-evaluation" ?>" >
		<?php
		wp_nonce_field('save_evaluation_form', 'verifier_nonce_evaluation');
		$i =1;
		foreach ($criterias as $criteria) {
		?>
			<div>
				<div class="form-row">
					<div class="col">
						<div class="alert alert-secondary p-2" role="alert">
							<?= $criteria['criteria'] ?>
						</div>
					</div>
				</div>
				<div class="form-row pb-1">
					<div class="col-sm-4">
						<div class="form-check mb-2">
							<div class="row">
								<div class="col-sm-4">
									<strong>Make : </strong>
								</div>
								<div class="col-sm-4">
									<input class="form-check-input" type="radio" onclick="block_(<?= $i ?>)" id="makeYes" value="makeYes<?= $i ?>" name="make-radio<?= $i ?>">
									<label class="form-check-label" for="make">
										Yes
									</label>
								</div>
								<div class="col-sm-4">
									<input class="form-check-input" onclick="hide_(<?= $i ?>)" type="radio" id="makeNo" value="makeNo<?= $i ?>" name="make-radio<?= $i ?>">
									<label class="form-check-label" for="autoSizingCheck">
										No
									</label>
								</div>
							</div>
						</div>
					</div>
					<div class="col-sm-8" id="<?= $i ?>" style="display: none;">
						<div class="row">
							<div class="col-sm-5">
								<input type="number" min="0" max="<?= $criteria['note'] ?>" name="note<?= $i ?>" name="note<?= $i ?>" class="form-control" placeholder="0">
							</div>
							<div class="col-sm-7">
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
		<input type="hidden" value="<?= $id_task ?>" name="task_id" id="task_id">
		<input type="hidden" value="<?= $i ?>" name="nbrecriteria" id="nbrecriteria">
		<button class="btn btn-outline-primary" type="submit">Submit</button>
	</form>
<?php
}
