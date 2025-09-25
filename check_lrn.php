<?php
include('db_connection.php');

if(isset($_POST['lrn'])){
    $lrn = $_POST['lrn'];

    $stmt = $conn->prepare("SELECT year, status FROM students_registration WHERE lrn = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $lrn);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        $lastYear = $row['year'];
        $status   = $row['status'] ?? 'Passed';

        // Compute next grade
        function getNextGrade($current){
            $levels = ["Pre-Prime 1","Pre-Prime 2","Kindergarten","Grade 1","Grade 2","Grade 3","Grade 4","Grade 5","Grade 6",
                       "Grade 7","Grade 8","Grade 9","Grade 10","Grade 11","Grade 12"];
            $i = array_search($current, $levels);
            return ($i !== false && isset($levels[$i+1])) ? $levels[$i+1] : $current;
        }

        $nextYear = ($status === 'Passed') ? getNextGrade($lastYear) : $lastYear;

        echo json_encode(["exists" => true, "lastYear" => $lastYear, "nextYear" => $nextYear]);
    } else {
        echo json_encode(["exists" => false]);
    }
}
?>
