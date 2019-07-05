<?php

	require_once( plugin_dir_path(__FILE__).'../../forms/class-space-results-form.php' );

	$survey_db = SPACE_DB_SURVEY::getInstance();

	$survey_id = $_GET['post'];

	$totalGuests = $survey_db->totalGuests( $survey_id );

	_e( '<p>Total Forms that have been submitted: <b>'. $totalGuests .'</b></p>' );

	// RESULTS FORM
	$results_form = new SPACE_RESULTS_FORM();
	$results_form->display();

	_e( "<a href='".admin_url( 'admin.php?page=space-export&survey='.$survey_id )."'>Generate CSV</a>" );
