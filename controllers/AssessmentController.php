
<?php

require_once __DIR__ . '/../config/database.php';

class AssessmentController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Membuat sesi assessment baru

    public function startAssessment(int $userId): int
    {
        // Cari assessment yang masih berlangsung.
        $stmt = $this->pdo->prepare("
        SELECT id
        FROM assessment_sessions
        WHERE user_id = ?
          AND status = 'in_progress'
        ORDER BY id DESC
        LIMIT 1
    ");

        $stmt->execute([$userId]);

        $existingSession = $stmt->fetch(PDO::FETCH_ASSOC);

        // Jika masih ada, gunakan session tersebut.
        if ($existingSession) {
            return (int) $existingSession['id'];
        }

        // Jika tidak ada, buat session baru.
        $stmt = $this->pdo->prepare("
        INSERT INTO assessment_sessions (user_id, status)
        VALUES (?, 'in_progress')
    ");

        $stmt->execute([$userId]);

        return (int) $this->pdo->lastInsertId();
    }


    // Mengambil semua soal tanpa membocorkan kunci jawaban
    public function getQuestions(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                id,
                competency_id,
                type,
                title,
                question_text,
                difficulty,
                points,
                programming_language,
                starter_code
            FROM questions
            ORDER BY id ASC
        ");

        $questions = $stmt->fetchAll();

        foreach ($questions as &$question) {
            $question['options'] = [];

            if ($question['type'] === 'multiple_choice') {
                $optionStmt = $this->pdo->prepare("
                    SELECT id, option_text
                    FROM question_options
                    WHERE question_id = ?
                    ORDER BY id ASC
                ");

                $optionStmt->execute([$question['id']]);
                $question['options'] = $optionStmt->fetchAll();
            }
        }

        unset($question);

        return $questions;
    }
}
