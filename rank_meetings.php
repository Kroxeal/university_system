<?php

function rank_meetings($meetings, $user_id, $conn) {
    // Получаем веса для различных критериев
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

    // Получаем все subject_id, на которые пользователь записан
    $subject_ids = get_user_subject_ids($user_id, $conn);

    // Массив для хранения встреч
    $ranked_meetings = [];

    foreach ($meetings as $meeting) {
        $author_score = get_author_score($meeting['ID'], $user_id, $conn);
        $date_score = get_date_score($meeting['start_time']);
        $popularity_score = get_popularity_score($meeting['ID'], $conn);
        $subject_score = get_subject_score($meeting['ID'], $user_id, $conn);

        // Проверяем наличие ключа "subject_id" в текущей встрече
        $is_subject_relevant = isset($meeting['subject_id']) && in_array($meeting['subject_id'], $subject_ids) ? 1 : 0;

        // Если ключ subject_id существует, используем его для расчета
        $total_score = ($author_score * $weights['author_weight']) +
            ($date_score * $weights['date_weight']) +
            ($popularity_score * $weights['popularity_weight']) +
            ($subject_score * $weights['subject_weight']);

        $ranked_meetings[] = [
            'meeting' => $meeting,
            'total_score' => $total_score,
            'is_subject_relevant' => $is_subject_relevant // Добавляем метку о значимости предмета
        ];
    }

    // Сортируем встречи:
    // 1. Сначала те, которые связаны с предметами, на которые пользователь записан
    // 2. Затем по убыванию общего балла
    usort($ranked_meetings, function ($a, $b) {
        // Сначала сортируем по важности предмета
        if ($a['is_subject_relevant'] != $b['is_subject_relevant']) {
            return $b['is_subject_relevant'] - $a['is_subject_relevant'];
        }
        // Если важность предмета одинаковая, сортируем по общему баллу
        return $b['total_score'] - $a['total_score'];
    });

    return $ranked_meetings;
}

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
        $subject_ids[] = $row['subject_id']; // Собираем все subject_id, на которые записан пользователь
    }
    $stmt->close();

    return $subject_ids;
}

function get_author_score($meeting_id, $user_id, $conn) {
    // Получаем ID создателя встречи
    $stmt = $conn->prepare('SELECT creator_id FROM meetings WHERE ID = ?');
    $stmt->bind_param('i', $meeting_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $author_id = $row['creator_id'];

    // Подсчитываем количество встреч пользователя с этим автором
    $stmt = $conn->prepare('
        SELECT COUNT(*) AS course_count
        FROM user_meetings um
        JOIN meetings m ON um.meeting_id = m.ID
        WHERE m.creator_id = ? AND um.user_id = ?');
    $stmt->bind_param('ii', $author_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $author_score = min($row['course_count'], 5); // Ограничиваем максимальный баллом 5
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
    return min($popularity_score, 10); // Ограничиваем максимальный балл 10
}

function get_subject_score($meeting_id, $user_id, $conn) {
    // Получаем все subject_id, на которые пользователь записан
    $subject_ids = get_user_subject_ids($user_id, $conn);

    if (empty($subject_ids)) {
        return 0; // Если нет предметов, возвращаем 0
    }

    // Проверяем, есть ли эта встреча среди тех, на которые пользователь записан
    $stmt = $conn->prepare('
        SELECT ms.subject_id
        FROM meeting_subjects ms
        JOIN meetings m ON ms.meeting_id = m.ID
        WHERE m.ID = ?');
    $stmt->bind_param('i', $meeting_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $meeting_subjects = [];
    while ($row = $result->fetch_assoc()) {
        $meeting_subjects[] = $row['subject_id'];
    }
    $stmt->close();

    // Если хотя бы один предмет совпадает, даём 5 баллов
    foreach ($meeting_subjects as $subject_id) {
        if (in_array($subject_id, $subject_ids)) {
            return 5;
        }
    }

    return 1; // Если предметов нет или они не совпадают, даём 1 балл
}

?>
