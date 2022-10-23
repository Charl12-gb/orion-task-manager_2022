<?php
    $sent_info = unserialize( get_option('_report_sent_info') );
    $sender_info = unserialize(get_option('_sender_mail_info'));
    if( $sent_info == null ) { $sent_info['email_manager'] = null; $sent_info['send_date'] = null; $sent_info['sent_cp'] = null;  }
    $perf_info = unserialize( get_option('_performance_parameters') );
    if( $perf_info == null ) { $perf_info['email_rh'] = null; $perf_info['nbreSubPeroformance'] = null; $perf_info['moyenne'] = null;  }

    $token = get_option('_asana_access_token'); // Access token
    $projetIdCp = get_option( '_project_manager_id' ); //Project for cp objectif add
    $set1 = false; $set2 = false; $set3 = false;
    if( ($token != null) && ( $projetIdCp != null ) ) $set1 = true;
    if( ( $sent_info != null ) && ( $sender_info != null ) ) $set2 = true;
    if( ( $perf_info != null ) && ($perf_info['email_rh'] != null) ) $set3 = true;
?>
<main>
    <div class="stepper">
        <div class="stepper">
        <div class="step--1 <?php if( !$set1 ) echo 'step-active'; ?>">Step 1</div>
        <div class="step--2 <?php if( ( $set1 == true ) && ( $set2 == false ) ) echo 'step-active'; ?>">Step 2</div>
        <div class="step--3 <?php if( ( $set1 == true ) && ( $set2 == true ) && $set3 == false ) echo 'step-active'; ?>">Step 3</div>
        <div class="step--4 <?php if( ( $set1 == true ) && ( $set2 == true ) && $set3 == true ) echo 'step-active'; ?>">Finish</div>
    </div>
    </div>
    <form class="form <?php if( !$set1 ) echo 'form-active'; ?>">
        <div class="form--header-container">
            <h1 class="form--header-title">
                Synchronization information
            </h1>
            <p class="form--header-text">
                Add synchronization information with ASANA
            </p>
        </div>
       <div class="pr-5 pl-5">
           <div class="form-group mt-3">
                <label for="exampleInputText">ASANA access token</label>
                <input type="text" class="form-control" id="accessToken" <?php if( get_option('_asana_access_token') != null ) echo get_option('_asana_access_token'); ?> name="accessToken" placeholder="Enter access token" required>
                <small id="emailHelp" class="form-text text-muted">The access token will sync data with ASANA.</small>
            </div>
           <div class="form-group mt-3">
                <label for="exampleInputText">Asana Workspace Id</label>
                <input class="form-control" type="text" name="asana_workspace_id" id="asana_workspace_id" placeholder="Asana Workspace Id" required value="<?php if( get_option('_asana_workspace_id') != null ) echo get_option('_asana_workspace_id'); ?>">
                <small id="emailHelp" class="form-text text-muted">The Asana workspace you want to sync tasks to</small>
            </div>
            <div class="form-group">
                <label for="exampleInputText1">ASANA Project ID for CP evaluation</label>
                <input type="text" class="form-control" id="projetId" name="projetId" placeholder="Enter ID">
                <small id="emailHelp" class="form-text text-muted">ASANA ID of the project where the objectives will be saved.</small>
            </div>
        </div><hr>
        <button class="form__btn btn btn-outline-primary ml-5 mb-2" id="btn-1">Next</button>

    </form>
    <form class="form <?php if( ( $set1 == true ) && ( $set2 == false ) ) echo 'form-active'; ?>">
        <div class="form--header-container">
            <h1 class="form--header-title">
                Information for sending mail and reporting
            </h1>
            <p class="form--header-text">
                Add information for sending evaluation emails and report sending parameters
            </p>
        </div>
        <div class="pr-5 pl-5">
            <div class="form-row mt-3">
                <h5>Sending Information</h5>
            </div>
            <div class="form-row">
                <div class="col">
                    <label for="inputName" class="">Name Sender</label>
                    <input class="form-control" type="text" id="sender_name" placeholder="Name Sender" value="<?php if( $sender_info != null ) echo $sender_info['sender_name']; ?>" required>
                </div>
                <div class="col">
                    <label for="staticEmail" class="">Email Sender</label>
                    <input class="form-control" type="text" id="sender_email" placeholder="Email Sender" value="<?php if( $sender_info != null ) echo $sender_info['sender_email']; ?>" required>
                </div>
            </div><hr>
            <div class="form-row">
                <h5>Send Report <strong class="btn-link" title="Set the parameters for sending reports">?</strong></h5>
            </div>
            <div class="form-row">
                <div class="col">
                    <label for="">Email Manager</label>
                    <input type="email" name="email_manager" id="email_manager" class="form-control" placeholder="Email Manager" value="<?= $sent_info['email_manager'] ?>" >
                </div>
                <div class="col">
                    <label for="">Date reports sent</label>
                    <select class="custom-select" id="date_report_sent">
                        <option value="last_day_month" <?php if( $sent_info['send_date']  == 'last_day_month') echo 'selected' ?>>Last day of the month</option>
                        <option value="last_friday_month" <?php if( $sent_info['send_date']  == 'last_friday_month') echo 'selected' ?>>Last friday of the month</option>
                    </select>
                </div>
            </div>
        </div><hr>
        <button class="form__btn btn btn-outline-warning ml-5 mb-2" id="btn-2-prev">Previous</button>
        <button class="form__btn btn btn-outline-primary mb-2" id="btn-2-next">Next</button>
    </form>
    <form class="form <?php if( ( $set1 == true ) && ( $set2 == true ) && $set3 == false ) echo 'form-active'; ?>">
        <div class="form--header-container">
            <h1 class="form--header-title">
                Performance info
            </h1>

            <p class="form--header-text">
                Add performance parameters
            </p>
        </div>
        <div class="pr-5 pl-5">
            <div class="form-row mt-3">
                <div class="col">
                    <label for="">Human resources department email</label>
                    <input type="email" name="email_rh" id="email_rh" class="form-control" placeholder="Human resources department email" value="<?= $perf_info['email_rh'] ?>" required>
                </div>
            </div>
            <div class="form-row mt-4">
                <div class="col">
                    <label for="">Total allowed underperformance <strong data-toggle="tooltip" data-placement="top" title="The total number of times an employee must be underperformed during the year.">?</strong></label>
                    <input type="number" min="3" max="6" name="nbreSubPeroformance" id="nbreSubPeroformance" class="form-control" placeholder="Number of underperformance" value="<?php if( $perf_info['nbreSubPeroformance'] != null ) echo $perf_info['nbreSubPeroformance']; else echo 3; ?>" required >
                </div>
                <div class="col">
                    <label for="">Minimum average</label>
                    <input type="number" min="50" max="100" name="moyenne" id="moyenne" class="form-control" placeholder="Minimum average" value="<?php if( $perf_info['moyenne'] != null ) echo $perf_info['moyenne']; else echo 80; ?>" required>
                </div>
            </div>
        </div><hr>
        <button class="form__btn btn btn-outline-primary ml-5 mb-2" id="btn-3">Submit</button>
    </form>
    <div class="form--message" id="msg_first_config" style="border-radius: 5%; <?php if( ( $set1 == true ) && ( $set2 == true ) && $set3 == true ) echo 'display:block;'; else echo 'display:none;' ?>">
        <form  action="" method="POST">
            <?php wp_nonce_field('plugin_first_parameter', 'verify_plugin_first_parameter'); ?>
            <div class="form--header-container">
                <h1 class="form--header-title">
                    End of setup
                </h1>
            </div>
            <div class="pr-5 pl-5 pt-3">
                <span>
                    You are at the end of your configuration. <br>
                    By clicking on done two pages are set up.<br>
                    <strong>
                        <em class="ml-5">1 - For calendar display, task management, and more <br></em>
                        <em class="ml-5">2 - For task evaluation.<br></em>
                    </strong>
                </span>
            </div><hr>
            <button class="btn btn-outline-primary" type="submit">Finish</button>
        </form>
    </div>
</main>
<hr>


<script>
    const formBtn1 = document.querySelector("#btn-1")
    const formBtnPrev2 = document.querySelector("#btn-2-prev")
    const formBtnNext2 = document.querySelector("#btn-2-next")
    const formBtn3 = document.querySelector("#btn-3")
    // Button listener of form 1
    formBtn1.addEventListener("click", function(e) {
        gotoNextForm(formBtn1, formBtnNext2, 1, 2)
        e.preventDefault()
    })

    // Next button listener of form 2
    formBtnNext2.addEventListener("click", function(e) {
        gotoNextForm(formBtnNext2, formBtn3, 2, 3)
        e.preventDefault()
    })

    // Previous button listener of form 2
    formBtnPrev2.addEventListener("click", function(e) {
        gotoNextForm(formBtnNext2, formBtn1, 2, 1)
        e.preventDefault()
    })

    // Button listener of form 3
    formBtn3.addEventListener("click", function(e) {
        document.querySelector(`.step--3`).classList.remove("step-active")
        document.querySelector(`.step--4`).classList.add("step-active")
        formBtn3.parentElement.style.display = "none"
        e.preventDefault()
    })

    const gotoNextForm = (prev, next, stepPrev, stepNext) => {
        // Get form through the button
        const prevForm = prev.parentElement
        const nextForm = next.parentElement
        const nextStep = document.querySelector(`.step--${stepNext}`)
        const prevStep = document.querySelector(`.step--${stepPrev}`)
        // Add active/inactive classes to both previous and next form
        nextForm.classList.add("form-active")
        nextForm.classList.add("form-active-animate")
        prevForm.classList.add("form-inactive")
        // Change the active step element
        prevStep.classList.remove("step-active")
        nextStep.classList.add("step-active")
        // Remove active/inactive classes to both previous an next form
        setTimeout(() => {
            prevForm.classList.remove("form-active")
            prevForm.classList.remove("form-inactive")
            nextForm.classList.remove("form-active-animate")
        }, 1000)
    }
</script>

<style>
   * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background: #f5f6f7;
    }

    main {
        height: 100vh;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        position: relative;
    }

    .stepper {
        width: 20rem;
        display: flex;
        justify-content: center;
        align-items: center;
        position: absolute;
        top: 5%;
    }

    .step--1,
    .step--2,
    .step--3,
    .step--4 {
        width: 5rem;
        padding: 0.5rem 0;
        background: #B833FF;
        color: black;
        text-align: center;
    }

    .step--1,
    .step--2,
    .step--3 {
        border-right: 1px solid #666;
    }

    .form {
        background: #fff;
        position: absolute;
        width: 40rem;
        box-shadow: 0.2rem 0.2rem 0.5rem rgba(51, 51, 51, 0.2);
        display: none;
        border-radius: 1rem;
        overflow: hidden;
    }

    .form--header-container {
        background: linear-gradient(to right, rgb(51, 51, 51), blue);
        color: #fff;
        text-align: center;
        height: 6rem;
        padding: 1rem 0;
        margin-bottom: 0rem;
    }

    .form--header-title {
        font-size: 1.4rem;
    }

    .form-active {
        z-index: 1000;
        display: block;
    }

    .form-active-animate {
        animation: moveRight 1s;
    }

    .form-inactive {
        display: block;
        animation: moveLeft 1s;
    }

    .step-active {
        background: #666;
        color: #fff;
        border: 1px solid #666;
    }

    @keyframes moveRight {
        0% {
            transform: translateX(-27rem) scale(0.9);
            opacity: 0;
        }

        100% {
            transform: translateX(0rem) scale(1);
            opacity: 1;
        }
    }

    @keyframes moveLeft {
        0% {
            transform: translateX(0rem) scale(1);
            opacity: 1;
        }

        100% {
            transform: translateX(27rem) scale(0.9);
            opacity: 0;
        }
    }

    @keyframes fadeIn {
        0% {
            opacity: 0;
        }

        100% {
            opacity: 1;
        }
    }
</style>