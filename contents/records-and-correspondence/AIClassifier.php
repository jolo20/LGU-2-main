<?php
class AIClassifier
{
    private $conn;
    private $documentVectors = [];
    private $idfValues = [];
    private $categories = [];
    private $categoryVectors = [];
    private $totalDocs = 0;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initializeFromDatabase();
    }

    private function initializeFromDatabase()
    {
        // Get all documents with known categories
        $stmt = $this->conn->prepare("SELECT measure_title, measure_content, measure_type, category FROM m6_measuredocketing_fromresearch WHERE category IS NOT NULL");
        $stmt->execute();
        $result = $stmt->get_result();

        $documents = [];
        $this->categories = [];

        while ($row = $result->fetch_assoc()) {
            $text = $this->preprocessText($row['measure_title'] . ' ' . $row['measure_content']);
            $documents[] = [
                'text' => $text,
                'category' => $row['category']
            ];
            if (!in_array($row['category'], $this->categories)) {
                $this->categories[] = $row['category'];
                $this->categoryVectors[$row['category']] = [];
            }
        }

        $this->totalDocs = count($documents);
        if ($this->totalDocs === 0) return;

        // Calculate TF-IDF for each document
        $this->calculateTFIDF($documents);

        // Create category vectors
        foreach ($documents as $doc) {
            $vector = $this->createVector($doc['text']);
            foreach ($vector as $term => $weight) {
                if (!isset($this->categoryVectors[$doc['category']][$term])) {
                    $this->categoryVectors[$doc['category']][$term] = 0;
                }
                $this->categoryVectors[$doc['category']][$term] += $weight;
            }
        }

        // Normalize category vectors
        foreach ($this->categoryVectors as &$vector) {
            $magnitude = sqrt(array_sum(array_map(function ($x) {
                return $x * $x;
            }, $vector)));
            if ($magnitude > 0) {
                foreach ($vector as &$weight) {
                    $weight /= $magnitude;
                }
            }
        }
    }

    private function preprocessText($text)
    {
        // Convert to lowercase
        $text = strtolower($text);

        // Remove special characters and extra spaces
        $text = preg_replace('/[^\w\s]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        // Remove common stop words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $words = explode(' ', $text);
        $words = array_diff($words, $stopWords);

        return implode(' ', $words);
    }

    private function calculateTFIDF($documents)
    {
        // Calculate document frequency
        $df = [];
        foreach ($documents as $doc) {
            $terms = array_unique(explode(' ', $doc['text']));
            foreach ($terms as $term) {
                if (!isset($df[$term])) $df[$term] = 0;
                $df[$term]++;
            }
        }

        // Calculate IDF
        foreach ($df as $term => $freq) {
            $this->idfValues[$term] = log($this->totalDocs / $freq);
        }
    }

    private function createVector($text)
    {
        $terms = explode(' ', $this->preprocessText($text));
        $tf = array_count_values($terms);
        $vector = [];

        foreach ($tf as $term => $freq) {
            if (isset($this->idfValues[$term])) {
                $vector[$term] = $freq * $this->idfValues[$term];
            }
        }

        // Normalize vector
        $magnitude = sqrt(array_sum(array_map(function ($x) {
            return $x * $x;
        }, $vector)));
        if ($magnitude > 0) {
            foreach ($vector as &$weight) {
                $weight /= $magnitude;
            }
        }

        return $vector;
    }

    public function classify($title, $content)
    {
        if (empty($this->categories)) {
            // Fallback to rule-based if no training data
            return $this->ruleBasedClassify($title, $content);
        }

        $text = $title . ' ' . $content;
        $vector = $this->createVector($text);

        $scores = [];
        foreach ($this->categoryVectors as $category => $categoryVector) {
            $scores[$category] = $this->cosineSimilarity($vector, $categoryVector);
        }

        // Get top 3 categories with confidence scores
        arsort($scores);
        $topCategories = array_slice($scores, 0, 3, true);

        return [
            'primary' => key($topCategories),
            'confidence' => current($topCategories),
            'alternatives' => $topCategories
        ];
    }

    private function cosineSimilarity($vector1, $vector2)
    {
        $dotProduct = 0;
        foreach ($vector1 as $term => $weight) {
            if (isset($vector2[$term])) {
                $dotProduct += $weight * $vector2[$term];
            }
        }
        return $dotProduct;
    }

    public function learnFromFeedback($documentId, $correctCategory)
    {
        // Get the document
        $stmt = $this->conn->prepare("SELECT measure_title, measure_content FROM m6_measuredocketing_fromresearch WHERE m6_MD_ID = ?");
        $stmt->bind_param('i', $documentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $doc = $result->fetch_assoc();

        if ($doc) {
            // Update the category in the database
            $updateStmt = $this->conn->prepare("UPDATE m6_measuredocketing_fromresearch SET category = ? WHERE m6_MD_ID = ?");
            $updateStmt->bind_param('si', $correctCategory, $documentId);
            $updateStmt->execute();

            // Reinitialize the classifier with new data
            $this->initializeFromDatabase();
        }
    }

    private function ruleBasedClassify($title, $content)
    {
        // Existing rule-based classification as fallback
        $categories = [
            'general welfare' => ['welfare', 'community', 'public', 'social'],
            'administrative' => ['administration', 'policy', 'procedure', 'management'],
            'health and sanitation' => ['health', 'medical', 'sanitation', 'hospital', 'clinic'],
            'education' => ['education', 'school', 'learning', 'student', 'academic'],
            'taxation' => ['tax', 'revenue', 'fee', 'payment', 'collection'],
            'infrastructure' => ['infrastructure', 'construction', 'building', 'road', 'bridge'],
            'environment' => ['environment', 'ecology', 'green', 'conservation', 'waste'],
            'peace and order' => ['peace', 'order', 'security', 'police', 'safety']
        ];

        $text = strtolower($title . ' ' . $content);
        $maxScore = 0;
        $bestCategory = 'general welfare';

        foreach ($categories as $category => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                $score += substr_count($text, $keyword);
            }
            if ($score > $maxScore) {
                $maxScore = $score;
                $bestCategory = $category;
            }
        }

        return [
            'primary' => $bestCategory,
            'confidence' => 0.5,  // Default confidence for rule-based
            'alternatives' => [$bestCategory => 0.5]
        ];
    }
}
