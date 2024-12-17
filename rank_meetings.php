<?php

function rank_meetings($meetings, $user_id, $conn) {
    $stmt = $conn->prepare('SELECT * FROM user_index_weights WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $weights = $result->fetch_assoc();
    $stmt->close();

    if (!$weights) {
        $weights = [
            'author_weight' => 2,
            'date_weight' => 3,
            'popularity_weight' => 2,
            'subject_weight' => 3
        ];
    }

    $ranked_meetings = [];

    foreach ($meetings as $meeting) {
        $author_score = get_author_score($meeting['ID'], $user_id, $conn);
        $date_score = get_date_score($meeting['start_time']);
        $popularity_score = get_popularity_score($meeting['ID'], $conn);
        $subject_score = get_subject_score($meeting['ID'], $user_id, $conn);

        $total_score = ($author_score * $weights['author_weight']) +
            ($date_score * $weights['date_weight']) +
            ($popularity_score * $weights['popularity_weight']) +
            ($subject_score * $weights['subject_weight']);

        $ranked_meetings[] = [
            'meeting' => $meeting,
            'total_score' => $total_score
        ];
    }

    usort($ranked_meetings, function ($a, $b) {
        return $b['total_score'] - $a['total_score'];
    });

    return $ranked_meetings;
}

function get_author_score($meeting_id, $user_id, $conn) {
    $stmt = $conn->prepare('
        SELECT creator_id
        FROM meetings
        WHERE ID = ?
    ');
    $stmt->bind_param('i', $meeting_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $author_id = $row['creator_id'];

    $stmt = $conn->prepare('
        SELECT COUNT(*) AS course_count
        FROM user_meetings um
        JOIN meetings m ON um.meeting_id = m.ID
        WHERE m.creator_id = ? AND um.user_id = ?
    ');
    $stmt->bind_param('ii', $author_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $author_score = min($row['course_count'], 5); // Ограничиваем балл (например, 5)

    $stmt->close();
    return $author_score;
}

function get_date_score($start_time) {
    $current_time = time();
    $meeting_time = strtotime($start_time);
    $time_difference = $meeting_time - $current_time;

    // Если встреча в прошлом, то балл равен 0
    if ($time_difference < 0) {
        return 0;
    }

    $days_to_meeting = floor($time_difference / (60 * 60 * 24));

    if ($days_to_meeting <= 1) {
        return 4;
    } elseif ($days_to_meeting <= 3) {
        return 3;
    } elseif ($days_to_meeting <= 7) {
        return 2;
    } else {
        return 1;
    }
}

function get_popularity_score($meeting_id, $conn) {
    $stmt = $conn->prepare('SELECT COUNT(*) as participants FROM user_meetings WHERE meeting_id = ?');
    $stmt->bind_param('i', $meeting_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $popularity_score = $row['participants'];

    $stmt->close();
    return min($popularity_score, 10);
}

function get_subject_score($meeting_id, $user_id, $conn) {
    $stmt = $conn->prepare('
        SELECT s.title
        FROM meetings m
        JOIN meeting_subjects ms ON m.ID = ms.meeting_id
        JOIN subjects s ON ms.subject_id = s.ID
        LEFT JOIN user_meetings um ON m.ID = um.meeting_id
        WHERE m.ID = ? AND um.user_id = ?
    ');
    $stmt->bind_param('ii', $meeting_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $subject_score = 0;

    while ($row = $result->fetch_assoc()) {
        $subject_score++;
    }

    $stmt->close();
    return $subject_score;
}

?>
