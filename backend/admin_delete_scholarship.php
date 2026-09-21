<?php

require_once 'admin_guard.php';


$data = json_decode(
    file_get_contents('php://input'),
    true
);


$scholarship_id =
    (int) ($data['scholarship_id'] ?? 0);


if ($scholarship_id <= 0) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid scholarship.'
    ]);

    exit;
}


$conn->begin_transaction();


try {

    /*Remove dependent records first.*/

    $tables = [
        'scholarship_benefits',
        'saved_scholarships',
        'applications'
    ];


    foreach ($tables as $table) {

        $stmt = $conn->prepare(
            "DELETE FROM $table
             WHERE scholarship_id = ?"
        );

        $stmt->bind_param(
            'i',
            $scholarship_id
        );

        $stmt->execute();

        $stmt->close();
    }


    $stmt = $conn->prepare(
        "DELETE FROM scholarships
         WHERE scholarship_id = ?"
    );

    $stmt->bind_param(
        'i',
        $scholarship_id
    );

    $stmt->execute();


    if ($stmt->affected_rows === 0) {

        throw new Exception(
            'Scholarship not found.'
        );
    }


    $stmt->close();

    $conn->commit();


    echo json_encode([
        'success' => true,
        'message' =>
            'Scholarship deleted successfully.'
    ]);


} catch (Throwable $e) {

    $conn->rollback();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
            'Could not delete scholarship.'
    ]);
}