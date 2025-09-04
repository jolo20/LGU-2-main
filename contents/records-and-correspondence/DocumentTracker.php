<?php
class DocumentTracker
{
    private $conn;
    private $documentVectors = [];
    private $statusPatterns = [];
    private $documentFlow = [];

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initializePatterns();
        $this->buildDocumentFlow();
    }

    private function initializePatterns()
    {
        // Status pattern recognition based on document content and metadata
        $this->statusPatterns = [
            'under_review' => [
                'keywords' => ['pending', 'for review', 'submitted', 'draft', 'initial'],
                'conditions' => [
                    'no_docket' => true,
                    'recent_submission' => true
                ]
            ],
            'accomplished' => [
                'keywords' => ['approved', 'processed', 'completed', 'docketed'],
                'conditions' => [
                    'has_docket' => true,
                    'has_feedback' => true
                ]
            ],
            'enacted' => [
                'keywords' => ['enacted', 'approved', 'signed', 'implemented'],
                'conditions' => [
                    'has_sp_number' => true,
                    'fully_processed' => true
                ]
            ]
        ];
    }

    private function buildDocumentFlow()
    {
        // Document lifecycle tracking
        $this->documentFlow = [
            'submission' => [
                'next' => ['review', 'return'],
                'requirements' => ['title', 'content', 'type']
            ],
            'review' => [
                'next' => ['docketing', 'revision'],
                'requirements' => ['checking_remarks', 'checked_by']
            ],
            'docketing' => [
                'next' => ['committee', 'return'],
                'requirements' => ['docket_no', 'category']
            ],
            'committee' => [
                'next' => ['enactment', 'revision'],
                'requirements' => ['committee_action', 'feedback']
            ],
            'enactment' => [
                'next' => ['publication', 'archive'],
                'requirements' => ['sp_number', 'approval_date']
            ]
        ];
    }

    public function analyzeDocument($doc)
    {
        $status = $this->determineStatus($doc);
        $progress = $this->calculateProgress($doc);
        $nextActions = $this->predictNextActions($doc);
        $timeEstimate = $this->estimateCompletionTime($doc);

        return [
            'status' => $status,
            'progress' => $progress,
            'next_actions' => $nextActions,
            'estimated_completion' => $timeEstimate,
            'confidence' => $this->calculateConfidence($doc)
        ];
    }

    private function determineStatus($doc)
    {
        // Determine document status based on patterns and conditions
        $scores = [];

        foreach ($this->statusPatterns as $status => $pattern) {
            $score = 0;

            // Check keywords in content
            foreach ($pattern['keywords'] as $keyword) {
                if (stripos($doc['measure_content'] . ' ' . $doc['measure_title'], $keyword) !== false) {
                    $score += 1;
                }
            }

            // Check conditions
            foreach ($pattern['conditions'] as $condition => $required) {
                switch ($condition) {
                    case 'no_docket':
                        if ($required === empty($doc['docket_no'])) $score += 2;
                        break;
                    case 'has_docket':
                        if ($required === !empty($doc['docket_no'])) $score += 2;
                        break;
                    case 'has_sp_number':
                        if ($required === !empty($doc['sp_no'])) $score += 3;
                        break;
                    case 'has_feedback':
                        if ($required === !empty($doc['MFL_Feedback'])) $score += 2;
                        break;
                    case 'recent_submission':
                        $submission_date = strtotime($doc['date_created']);
                        $is_recent = (time() - $submission_date) < (30 * 24 * 60 * 60); // 30 days
                        if ($required === $is_recent) $score += 1;
                        break;
                }
            }

            $scores[$status] = $score;
        }

        arsort($scores);
        return array_key_first($scores);
    }

    private function calculateProgress($doc)
    {
        $stages = ['submission', 'review', 'docketing', 'committee', 'enactment'];
        $currentStage = 0;
        $totalStages = count($stages);

        if (!empty($doc['sp_no'])) return 100;
        if (!empty($doc['MFL_Feedback'])) $currentStage = 4;
        elseif (!empty($doc['docket_no'])) $currentStage = 3;
        elseif (!empty($doc['checked_by'])) $currentStage = 2;
        elseif (!empty($doc['measure_content'])) $currentStage = 1;

        return ($currentStage / $totalStages) * 100;
    }

    private function predictNextActions($doc)
    {
        $actions = [];
        $current_status = $this->determineStatus($doc);

        switch ($current_status) {
            case 'under_review':
                if (empty($doc['checking_remarks'])) {
                    $actions[] = 'Needs initial review';
                }
                if (empty($doc['checked_by'])) {
                    $actions[] = 'Pending reviewer assignment';
                }
                break;

            case 'accomplished':
                if (empty($doc['MFL_Feedback'])) {
                    $actions[] = 'Awaiting committee feedback';
                }
                if (empty($doc['category']) && strtolower($doc['measure_type']) === 'ordinance') {
                    $actions[] = 'Requires categorization';
                }
                break;

            case 'enacted':
                if (empty($doc['sp_no'])) {
                    $actions[] = 'SP Number assignment pending';
                }
                break;
        }

        return $actions;
    }

    private function estimateCompletionTime($doc)
    {
        // Calculate average completion time based on historical data
        $query = "SELECT AVG(TIMESTAMPDIFF(DAY, date_created, date_enacted)) as avg_days 
                  FROM m6_measuredocketing_fromresearch 
                  WHERE measure_type = ? AND sp_no IS NOT NULL";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $doc['measure_type']);
        $stmt->execute();
        $result = $stmt->get_result();
        $avg = $result->fetch_assoc()['avg_days'];

        // Adjust estimate based on complexity and current progress
        $progress = $this->calculateProgress($doc);
        $remaining_days = $avg * (1 - ($progress / 100));

        return date('Y-m-d', strtotime("+{$remaining_days} days"));
    }

    private function calculateConfidence($doc)
    {
        $confidence = 0;
        $status = $this->determineStatus($doc);

        // Calculate confidence based on available information
        $factors = [
            'has_title' => !empty($doc['measure_title']),
            'has_content' => !empty($doc['measure_content']),
            'has_type' => !empty($doc['measure_type']),
            'has_docket' => !empty($doc['docket_no']),
            'has_feedback' => !empty($doc['MFL_Feedback']),
            'has_reviewer' => !empty($doc['checked_by'])
        ];

        $weights = [
            'has_title' => 0.15,
            'has_content' => 0.20,
            'has_type' => 0.15,
            'has_docket' => 0.20,
            'has_feedback' => 0.15,
            'has_reviewer' => 0.15
        ];

        foreach ($factors as $factor => $value) {
            if ($value) {
                $confidence += $weights[$factor];
            }
        }

        return $confidence;
    }

    public function searchDocuments($searchTerm)
    {
        $searchTerm = '%' . $searchTerm . '%';

        $query = "SELECT *, 
                  MATCH(measure_title, measure_content) AGAINST(?) as relevance
                  FROM m6_measuredocketing_fromresearch 
                  WHERE MATCH(measure_title, measure_content) AGAINST(?) 
                  OR measure_title LIKE ? 
                  OR measure_content LIKE ? 
                  OR docket_no LIKE ?
                  ORDER BY relevance DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('sssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();

        return $stmt->get_result();
    }
}
