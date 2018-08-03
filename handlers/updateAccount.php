<?php

if($_SERVER['REQUEST_METHOD'] != 'POST') {
	header('Location: ../register.php');
	die();
}

require "../include/db.php";
require "../include/functions.php";

new Session($conn);

if(!isset($_SESSION['loggedin'])) {
	die();
}

$userID = $_SESSION['id'];
$currentPassword = getUserPassword($conn, $userID);

// get POST variables
$firstName = isset($_POST['firstName']) ? $_POST['firstName'] : '';
$lastName = isset($_POST['lastName']) ? $_POST['lastName'] : '';
$oldPassword = isset($_POST['oldPassword']) ? $_POST['oldPassword'] : '';
$newPassword = isset($_POST['newPassword']) ? $_POST['newPassword'] : '';
$newPasswordConfirm = isset($_POST['newPasswordConfirm']) ? $_POST['newPasswordConfirm'] : '';

// initialize JSON variables
$errors = validate($conn, $firstName, $lastName, $currentPassword, $oldPassword, $newPassword, $newPasswordConfirm, $userID);
$data = array();

if(empty($errors)) {
	updateUser($conn, $firstName, $lastName, $currentPassword, $newPassword, $userID);
	$data['success'] = true;
} else {
	$data['success'] = false;
	$data['errors'] = $errors;
}

echo json_encode($data);

function validate(mysqli $conn, $firstName, $lastName, $currentPassword, $oldPassword, $newPassword, $newPasswordConfirm, $userID) {
	$errors = array();
	if(empty($firstName)) { // first name is empty
		$errors["firstName"] = "Please enter a first name.";
	}
	if(strlen($firstName) > 63) { // first name is too long
		$errors["firstName"] = "Name cannot exceed 63 characters.";
	}
	if(empty($lastName)) { // last name is empty
		$errors["lastName"] = "Please enter a last name.";
	}
	if(strlen($lastName) > 63) { // last name is too long
		$errors["lastName"] = "Name cannot exceed 63 characters.";
	}
	if(!empty($oldPassword) || !empty($newPassword)) {
		if(!password_verify($oldPassword, $currentPassword)) {
			$errors["oldPassword"] = "Old password does not match our records.";
		}
		if($newPassword != $newPasswordConfirm) { // passwords are different
			$errors["newPasswordConfirm"] = "Passwords are not the same.";
		}
		if(strlen($newPassword) > 72) {
			$errors["newPassword"] = "Password length cannot exceed 72 characters.";
		}
		if(empty($newPassword)) {
			$errors["newPassword"] = "Please enter a new password.";
		}
	}
	return $errors;
}

function getUserPassword(mysqli $conn, $memberID) {
	$out = "";
    $stmt = $conn->prepare("SELECT password FROM members WHERE memberID = ?");
	$stmt->bind_param("i", $memberID);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($password);
    while($stmt->fetch()) {
        $out = $password;
    }
    $stmt->close();
    return $out;
}
