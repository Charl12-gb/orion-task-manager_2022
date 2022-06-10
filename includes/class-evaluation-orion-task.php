<?php

/**
 * Page d'évaluation des tâches
 */
function evaluator_page()
{
	if (isset($_GET['task_id'], $_GET['type_task']) && (!empty($_GET['task_id']) && !empty($_GET['type_task']))) {
		$task_id = htmlentities($_GET['task_id']);
		$type_task = htmlentities($_GET['type_task']);
		if ($type_task == 'normal' || $type_task == 'developper') {
			if (get_evaluation_info($task_id) == null) {
?>
				<div class="alert alert-danger" role="alert">
					Sorry ! <br>
					Task Not Found
				</div>
				<?php
			} else {
				if (get_evaluation_info($task_id)->evaluation == null) {
					get_evaluation_form($task_id, $type_task);
				} else {
				?>
					<div class="alert alert-danger" role="alert">
						Sorry ! <br>
						Task already evaluated
					</div>
			<?php
				}
			}
		} else {
			?>
			<div class="alert alert-danger" role="alert">
				Sorry ! <br>
				Invalid type
			</div>
		<?php
		}
	} else {
		if (!isset($_POST['verifier_nonce_evaluation']) || !wp_verify_nonce($_POST['verifier_nonce_evaluation'], 'save_evaluation_form')) {
		?>
			<div class="alert alert-danger" role="alert">
				Error ! <br>
				No task to evaluate.
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
					Task evaluate successfully
				</div>
			<?php
			} else {
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
 * Evaluation des projects manager à la fin de chaque mois
 */
function evluation_project()
{
	$string = 'last friday of ' . date('F', mktime(0, 0, 0, date('m'), 10)) . ' this year';
	$last_friday = gmdate('Y-m-d', strtotime($string));
	$string2 = 'next day ' . $last_friday;
	$date1 = gmdate('Y-m-d', strtotime($string2));
	$date2 = date('Y-m-d');
	$evaluation_date = strtotime($date1);
	$today_date = strtotime($date2);

	if ($evaluation_date == $today_date) {
		$objectives = get_objective_of_month((date('m') / 1), date('Y'));
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
}

/**
 * Function permettant de télécharger le worklog d'un membre
 * 
 * @param int $user_id
 * @return string
 */
function download_worklog($user_id, $month=null, $year=null)
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
function content_msg($id_task, $title_main_task, $type_task, $content)
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
	return $content . "<br><hr><h5><span style='text-decoration:underline; color:blue;'>Main Task : </span>$title_main_task</h5><hr>";
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
	$task = get_task_('id', $task_id, 'yes')[0];
	$get_criteria = get_option('_evaluation_criterias');
	$criterias =  unserialize($get_criteria);
	?>
	<div class="container card">
		<?php
		if ($type_task == 'normal' || $type_task == 'developper') {
			if ($type_task !=  $task->type_task) {
		?>
				<div class="alert alert-danger" role="alert">
					Error Type Task! <br>
				</div>
			<?php
			} else {
			?>
				<div class="row">
					<div class="col-sm-6 alert alert-success">
						<div style="width: 100%;text-align: center;color:black">
							<h6 class="pt-2" style="text-align: center; font-weight: bold;"><?= $task->title ?></h6>
							<p>Find task details <a href="<?= $task->permalink_url ?>" class="text-primary">here</a></p>
						</div>
					</div>
					<div class="col-sm-6 alert alert-primary">
						<div class="row pb-2 pt-2 text-center">
							<div class="col-sm-3"><strong style="text-decoration: underline;">Status: <br></strong> <?= get_task_status($task_id) ?> </div>
							<div class="col-sm-4"><strong style="text-decoration: underline;">Due Date: <br></strong> <?= $task->duedate ?></div>
							<div class="col-sm-5"><strong style="text-decoration: underline;">Date Completed: <br></strong> <?php if (!get_task_status($task_id, 'yes')) echo '--- -- --';
																															else echo $task->finaly_date; ?></div>
						</div>
					</div>
				</div>
				<?php
				if (!get_task_status($task_id, 'yes')) {
				?>
					<small id="emailHelp" class="form-text text-muted text-center">The task being evaluated is not yet marked as complete. <br>Make sure of that or take that into account. </small>
					<button class="btn btn-outline-primary" data-toggle="modal" data-target="#detail_criteria">Readme before review </button>
				<?php
				}
				?>
				<hr>
			<?php
			}
		} else {
			?>
			<div class="alert alert-danger" role="alert">
				Error ! <br>
			</div>
		<?php
		}
		if ($type_task ==  $task->type_task) {
			if ($type_task == 'normal') get_form_evaluation($criterias['normal'], $task_id);
			else get_form_evaluation($criterias['developper'], $task_id);
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
									<strong><?= $criteria['criteria'] ?> : </strong>
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
								<input type="number" min="0" max="<?= $criteria['note'] ?>" name="note<?= $i ?>" name="note<?= $i ?>" class="form-control" placeholder="0">
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
		<input type="hidden" value="<?= $id_task ?>" name="task_id" id="task_id">
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
							<h6> <span>Criteria <?= $k ?> : </span> <?= $dev['criteria'] ?> <span class="text-danger"> ( <?= $dev['note'] ?> )</span> <br></h6><br>
							<p><?= nl2br($dev['description']) ?></p>
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
							<h6> <span>Criteria <?= $k ?> : </span> <?= $normal['criteria'] ?> <span class="text-danger"> ( <?= $normal['note'] ?> )</span><br> </h6><br>
							<p><?= nl2br($normal['description']) ?></p>
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
