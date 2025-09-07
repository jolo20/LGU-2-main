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
                'keywords' => ['draft'],
                'descriptions' => [
                    'draft' => 'Initial document draft',
                ]
            ],
            'accomplished' => [
                'keywords' => ['pending', '1st reading', '2nd reading', '3rd reading'],
                'descriptions' => [
                    'pending' => 'Awaiting review',
                    '1st reading' => 'First reading in progress',
                    '2nd reading' => 'Second reading in progress',
                    '3rd reading' => 'Final reading completed'
                ]
            ],
            'enacted' => [
                'keywords' => ['enacted'],
                'descriptions' => [
                    'enacted' => 'Document has been enacted'
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
                'requirements' => ['checking_remarks']
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

        // Get assigned departments from the database
        $stmt = $this->conn->prepare("SELECT assigned_to FROM m6_measures WHERE measure_id = ?");
        $stmt->bind_param("s", $doc['m6_MD_ID']);
        $stmt->execute();
        $result = $stmt->get_result();
        $assigned_depts = [];

        if ($row = $result->fetch_assoc()) {
            if (!empty($row['assigned_to'])) {
                $assigned_depts = explode(',', $row['assigned_to']);
            }
        }

        return [
            'status' => $status,
            'progress' => $progress,
            'next_actions' => $nextActions,
            'estimated_completion' => $timeEstimate,
            'assigned_to' => $assigned_depts,
        ];
    }

    private function determineStatus($doc)
    {
        // If measure_status is null or empty, default to 'draft'
        $status = !empty($doc['measure_status']) ? strtolower($doc['measure_status']) : 'draft';

        // Map the measure status to our tracking statuses
        $statusMap = [
            'draft' => 'under_review',
            'pending' => 'accomplished',
            '1st reading' => 'accomplished',
            '2nd reading' => 'accomplished',
            '3rd reading' => 'accomplished',
            'enacted' => 'enacted'
        ];

        // Return the mapped status or 'under_review' as default
        return isset($statusMap[$status]) ? $statusMap[$status] : 'under_review';
    }

    private function calculateProgress($doc)
    {
        // If measure_status is null or empty, default to 'draft'
        $status = !empty($doc['measure_status']) ? strtolower($doc['measure_status']) : 'draft';

        // Define progress percentages for each status
        $progressMap = [
            'draft' => 16.67,      // 1/6
            'pending' => 33.33,    // 2/6
            '1st reading' => 50,   // 3/6
            '2nd reading' => 66.67, // 4/6
            '3rd reading' => 83.33, // 5/6
            'enacted' => 100       // 6/6
        ];

        // Return the corresponding progress percentage or 0 if status not found
        return isset($progressMap[$status]) ? $progressMap[$status] : 16.67; // Default to draft progress
    }

    private function predictNextActions($doc)
    {
        $actions = [];
        $current_status = $this->determineStatus($doc);

        switch ($current_status) {
            case 'under_review':
                if (empty($doc['record_remarks'])) {
                    $actions[] = 'Needs initial review';
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
        // Get current date for comparison
        $currentDate = new DateTime();
        $createdDate = new DateTime($doc['date_created']);

        // Set base timeline based on measure type
        $measureType = strtolower($doc['measure_type']);
        if ($measureType === 'ordinance') {
            $baseWeeks = 1; // 1 week base for ordinances (3 readings + hearings)
            $maxMonths = 3; // Maximum 3 months for ordinances
        } else {
            // Resolution or other types
            $baseWeeks = 2; // 2 weeks base for resolutions
            $maxMonths = 2; // Maximum 2 months for resolutions
        }

        // Set initial estimate
        $initialEstimate = clone $createdDate;
        $initialEstimate->modify("+{$baseWeeks} weeks");

        // If initial estimate has passed, add weeks based on how many periods have passed
        if ($currentDate > $initialEstimate) {
            $interval = $currentDate->diff($initialEstimate);
            $weeksOverdue = ceil($interval->days / 7);

            // Add extension weeks based on measure type
            if ($measureType === 'ordinance') {
                $additionalWeeks = min($weeksOverdue, 2) * 2; // Maximum 4 additional weeks for ordinances
            } else {
                $additionalWeeks = min($weeksOverdue, 4) * 1; // Maximum 4 additional weeks for resolutions
            }

            // Add the additional weeks to current date
            $newEstimate = clone $currentDate;
            $newEstimate->modify("+{$additionalWeeks} weeks");

            // Ensure estimate doesn't go beyond max months from now
            $maxDate = clone $currentDate;
            $maxDate->modify("+{$maxMonths} months");

            if ($newEstimate > $maxDate) {
                return $maxDate->format('Y-m-d');
            }

            return $newEstimate->format('Y-m-d');
        }

        // If initial estimate hasn't passed yet, use it
        return $initialEstimate->format('Y-m-d');
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
            'has_feedback' => !empty($doc['MFL_Feedback'])
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
