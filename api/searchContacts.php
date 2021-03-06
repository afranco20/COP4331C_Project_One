<?php

require "supportFunctions.php";
require "Contact.php";
require  "dbInfo.cfg.php";

//headers to be used for SQL queries to specific row headers (needed in variable format for php parsing
$firstNameHeader = ContactFields::FIRST_NAME;
$lastNameHeader = ContactFields::LAST_NAME;
$ownerHeader = ContactFields::OWNER_ID;



//get and sanitize the xhr data
$xhrRequest = getXhrRequestInfo();

//
if (is_null($xhrRequest)){
    returnError(null);
} else {

    // get and prepare fields for SQL query
    $requestFirstName = '%' . $xhrRequest[ContactFields::FIRST_NAME] . '%';
    $requestLastName = '%' . $xhrRequest[ContactFields::LAST_NAME] . '%';
    $requestOwnerID = $xhrRequest[ContactFields::OWNER_ID];

    // search database for contacts based on info provided

    $sqlConnection  = new mysqli('localhost' ,dbinfo::$dbUser, dbInfo::$dbPass, dbInfo::$db);
    if ($sqlConnection->connect_error) {
        error_log( $sqlConnection->connect_error );
        returnError(null);
    }

    //log any error that occurs
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);

    //connection established, begin query and grab the data
    try {
        /** @var mysqli_result $sqlResult */
        $sqlQuery = "SELECT * FROM `contacts` where $firstNameHeader LIKE ? and $lastNameHeader LIKE ?  AND $ownerHeader = ? ";
        $sqlStmt = $sqlConnection->prepare($sqlQuery);
        $sqlStmt->bind_param('ssi', $requestFirstName, $requestLastName, $requestOwnerID);
        $sqlStmt->execute();
        $sqlResult = $sqlStmt->get_result();

        if (is_bool($sqlResult)) {
            error_log("sql query failed : $sqlQuery");
            exit();
        }
        $contacts = Contact::convertFromSQL($sqlResult);
        $returnArr = array();
        /** @var Contact $contact */
        foreach ($contacts as $contact) {
//        error_log($contact->convertToArray());
            $returnArr[] = $contact->convertToArray();
        }

        returnXhrRequestAsJson($returnArr);
    } catch (Exception $exception){
        error_log("Exception occurred during Request handling: " . $exception->getMessage());
        returnError(null);
    }
}


function returnError($errorCode){
    //todo return error? no difference between valid search with 0 and failed search
    returnXhrRequestAsJson(array("error occurred"));
    exit();
}