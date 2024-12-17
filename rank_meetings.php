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
            'author_weight' => 0,
            'date_weight' => 0,
            'popularity_weight' => 0,
            'subject_weight' => 0
        ];
    }

    $ranked_meetings = [];

    foreach ($meetings as $meeting) {
        $author_score = get_author_score($meeting['ID'], $conn);
        $date_score = get_date_score($meeting['start_time']);
        $popularity_score = get_popularity_score($meeting['ID'], $conn);
        $subject_score = get_subject_score($meeting['ID'], $conn);

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


function get_author_score($meeting_id, $conn) {
    $stmt = $conn->prepare('
        SELECT um.user_id, u.role
        FROM user_meetings um
        JOIN users u ON um.user_id = u.ID
        WHERE um.meeting_id = ?
    ');
    $stmt->bind_param('i', $meeting_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $author_score = 0;

    while ($user = $result->fetch_assoc()) {
        if ($user['role'] == 'admin' || $user['role'] == 'teacher') {
            $author_score = 1;
            break;
        }
    }

    $stmt->close();
    return $author_score;
}

function get_date_score($start_time) {
    $current_time = time();
    $meeting_time = strtotime($start_time);
    $time_difference = $meeting_time - $current_time;

    if ($time_difference < 0) {
        return 0;
    }

    $days_to_meeting = floor($time_difference / (60 * 60 * 24));
    if ($days_to_meeting <= 1) {
        return 10;
    } elseif ($days_to_meeting <= 3) {
        return 7;
    } elseif ($days_to_meeting <= 7) {
        return 5;
    } else {
        return 3;
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

function get_subject_score($meeting_id, $conn) {
    $stmt = $conn->prepare('
        SELECT s.title 
        FROM meetings m
        JOIN meeting_subjects ms ON m.ID = ms.meeting_id
        JOIN subjects s ON ms.subject_id = s.ID
        WHERE m.ID = ?
    ');
    $stmt->bind_param('i', $meeting_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $subject_score = 0;

    while ($subject = $result->fetch_assoc()) {
        $subject_score = 1;
    }

    $stmt->close();
    return $subject_score;
}
?>
