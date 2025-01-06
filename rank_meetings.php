<?php

function get_user_subject_ids($user_id, $conn) {
    $stmt = $conn->prepare('
        SELECT DISTINCT ms.subject_id
        FROM user_meetings um
        JOIN meetings m ON um.meeting_id = m.ID
        JOIN meeting_subjects ms ON m.ID = ms.meeting_id
        WHERE um.user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $subject_ids = [];
    while ($row = $result->fetch_assoc()) {
        $subject_ids[] = $row['subject_id'];
    }
    $stmt->close();

    return $subject_ids;
}

function get_meeting_scores($meeting_id, $user_id, $conn, $subject_ids) {
    $author_score = get_author_score($meeting_id, $user_id, $conn);
    $date_score = get_date_score($meeting_id);
    $popularity_score = get_popularity_score($meeting_id, $conn);
    $subject_score = get_subject_score($meeting_id, $subject_ids, $conn);

    return [
        'author_score' => $author_score,
        'date_score' => $date_score,
        'popularity_score' => $popularity_score,
        'subject_score' => $subject_score
    ];
}

function rank_meetings($meetings, $user_id, $conn) {
    $subject_ids = get_user_subject_ids($user_id, $conn);

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
        $scores = get_meeting_scores($meeting['ID'], $user_id, $conn, $subject_ids);

        $total_score = ($scores['author_score'] * $weights['author_weight']) +
            ($scores['date_score'] * $weights['date_weight']) +
            ($scores['popularity_score'] * $weights['popularity_weight']) +
            ($scores['subject_score'] * $weights['subject_weight']);

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
    $stmt = $conn->prepare('SELECT creator_id FROM meetings WHERE ID = ?');
    $stmt->bind_param('i', $meeting_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $author_id = $row['creator_id'];

    $stmt = $conn->prepare('
        SELECT COUNT(*) AS course_count
        FROM user_meetings um
        JOIN meetings m ON um.meeting_id = m.ID
        WHERE m.creator_id = ? AND um.user_id = ?');
    $stmt->bind_param('ii', $author_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $author_score = min($row['course_count'], 5);
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

function get_subject_score($meeting_id, $subject_ids, $conn) {
    $stmt = $conn->prepare('
        SELECT ms.subject_id
        FROM meeting_subjects ms
        WHERE ms.meeting_id = ?');
    $stmt->bind_param('i', $meeting_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $meeting_subject_ids = [];
    while ($row = $result->fetch_assoc()) {
        $meeting_subject_ids[] = $row['subject_id'];
    }
    $stmt->close();

    foreach ($meeting_subject_ids as $subject_id) {
        if (in_array($subject_id, $subject_ids)) {
            return 10;
        }
    }

    return 0;
}


?>
