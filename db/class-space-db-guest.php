<?php
/*
* GUEST MODEL
*/

class SPACE_DB_GUEST extends SPACE_DB_BASE{

	var $response_db;
	var $question_db;

	function __construct(){

		$this->setTableSlug( 'guest' );
		parent::__construct();

		require_once( 'class-space-db-response.php' );
		$this->setResponseDB( SPACE_DB_RESPONSE::getInstance() );

		require_once('class-space-db-question.php');
		$this->setQuestionDB( SPACE_DB_QUESTION::getInstance() );

	}

	/* GETTER AND SETTER FUNCTIONS */
	function getResponseDB(){ return $this->response_db; }
	function setResponseDB( $response_db ){ $this->response_db = $response_db; }

	function getQuestionDB(){ return $this->question_db; }
	function setQuestionDB( $question_db ){ $this->question_db = $question_db; }
	/* GETTER AND SETTER FUNCTIONS */

	function create(){

		$table = $this->getTable();
		$charset_collate = $this->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table (
			ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			ipaddress VARCHAR(30),
			meta VARCHAR(255),
			created_on DATETIME DEFAULT CURRENT_TIMESTAMP,
			survey_id BIGINT(20),
			PRIMARY KEY(ID)
		) $charset_collate;";

		return $this->query( $sql );
	}

	function sanitize( $data ){
		$guestData = array(
			'ipaddress' 	=> $this->getClientIP(),
			'meta'			=> $_SERVER['HTTP_USER_AGENT'],
			'survey_id'		=> absint( $data['survey_id'] ),
		);
		return $guestData;
	}

	// RETURNS THE LIST OF ASSOCIATED RESPONSES
	function getResponses( $guest_id ){

		return $this->getResponseDB()->filter(
			array(
				'guest_id'	=> '%d'
			),
			array( (int) $guest_id )
		);
	}

	function deleteResponses( $guest_id ){
		$this->getResponseDB()->deleteResponsesForGuest( $guest_id );
	}

	function saveResponses( $data ){

		$responses = array();

		// CHECK IF GUEST ID AND QUESTION WITH RESPONSES HAS BEEN PASSED
		if( isset( $data['guest_id'] ) && isset( $data['quest'] ) && is_array( $data['quest'] ) ){

			foreach( $data['quest'] as $quest_id => $quest ){

				if( is_array( $quest ) && isset( $quest['type'] ) && isset( $quest['val'] ) ){

					switch( $quest['type'] ){

						case 'dropdown':

						case 'radio':

							$partialResponse = $this->getResponseDB()->sanitize( array(
								'question_id'	=> $quest_id,
								'guest_id'		=> $data['guest_id'],
								'choice_id'		=> $quest['val']
							) );
							array_push( $responses, $partialResponse );

						break;

						case 'checkbox-other':
							// save other text
							if( isset( $quest['other'] ) &&  $quest['other'] ){
								$partialResponse = $this->getResponseDB()->sanitize( array(
									'question_id'	=> $quest_id,
									'guest_id'		=> $data['guest_id'],
									'choice_text'	=> $quest['other']
								) );
								array_push( $responses, $partialResponse );
							}


						case 'checkbox':
							if( is_array( $quest['val'] ) ){

								foreach( $quest['val'] as $choice_id ){

									$partialResponse = $this->getResponseDB()->sanitize( array(
										'question_id'	=> $quest_id,
										'guest_id'		=> $data['guest_id'],
										'choice_id'		=> $choice_id
									) );



									array_push( $responses, $partialResponse );
								}



							}
							break;

						case 'text':
							$partialResponse = $this->getResponseDB()->sanitize( array(
								'question_id'	=> $quest_id,
								'guest_id'		=> $data['guest_id'],
								'choice_text'	=> $quest['val']
							) );

							array_push( $responses, $partialResponse );

							break;



					}

				}

			}

			// DELETE ALL RESPONSES FOR THE PARTICULAR GUEST
			$this->deleteResponses( $data['guest_id'] );

			//echo "<pre>";
			//print_r( $responses );
			//echo "<pre>";

			// INSERT MULTIPLE RESPONSES FOR THE GUEST AT ONCE USING SINGLE QUERY
			$this->getResponseDB()->insert_rows( $responses );

		}

	}

	// GET CLIENT IP ADDRESS
	function getClientIP() {
		$ipaddress = '';
		if (isset($_SERVER['HTTP_CLIENT_IP']))
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_X_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		else if(isset($_SERVER['REMOTE_ADDR']))
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		else
			$ipaddress = 'UNKNOWN';
		return $ipaddress;
	}

	function listForSurvey( $survey_id ){


		$guests = $this->filter(
			array(
				'survey_id'	=> '%d'
			),
			array( (int) $survey_id )
		);

		return $guests;
	}

	function listIDsForSurvey( $survey_id, $choices = array(), $page = 1, $per_page = 10 ){

		$data = array( 'results' => array(), 'num_rows' => 0 );

		$table = $this->getTable();
		$responseTable = $this->getResponseDB()->getTable();

		$query = "SELECT guest_id FROM $responseTable WHERE guest_id IN ( SELECT ID FROM $table WHERE survey_id = %d )";

		// FILTER THE GUESTS WITH THE CHOICES THAT THEY HAVE SELECTED
		if( is_array( $choices ) && count( $choices ) ){

			$sub_query = "";
			$i = 0;
			// ITERATE THROUGH EACH CHOICES AND CREATED A NESTED QUERY
			foreach( $choices as $choice_id ){
				if( $choice_id ){
					$unique_choice_query = "SELECT DISTINCT guest_id FROM $responseTable WHERE choice_id = $choice_id";
					if( $i > 0 ){
						$sub_query = "$sub_query AND guest_id IN ( $unique_choice_query )";
					}
					else{
						$sub_query = $unique_choice_query;
					}
					$i++;
				}
			}

			// ONLY IF THE SUB QUERY IS PRESENT THEN ADD TO THE MAIN QUERY
			if( $sub_query ){
				$query .= " AND guest_id IN (" . $sub_query . ")";
			}
		}
		$query .= " GROUP BY guest_id";
		$query = $this->prepare( $query, array( $survey_id ) );

		//echo $query;

		// FIND THE TOTAL NUMBER OF ROWS
		$count_query = "SELECT count(*) FROM (" . $query . ") AS NEWTABLE";
		$data['num_rows'] = $this->get_var( $count_query );

		$query .= $this->_limit_query( $page, $per_page );
		$results = $this->get_results( $query );
		foreach( $results as $row ){
			array_push( $data['results'], $row->guest_id );
		}



		return $data;
	}

}

SPACE_DB_GUEST::getInstance();
