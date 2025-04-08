<?php
class FineCalculator {
    private $daily_rate = 1.00; // $1 per day
    private $max_fine = 50.00;  // Maximum fine amount
    
    public function calculateFine($due_date, $return_date = null) {
        if (!$return_date) {
            $return_date = date('Y-m-d');
        }
        
        $due = new DateTime($due_date);
        $return = new DateTime($return_date);
        $days_late = $return->diff($due)->days;
        
        if ($days_late <= 0) {
            return 0;
        }
        
        $fine = $days_late * $this->daily_rate;
        return min($fine, $this->max_fine);
    }
    
    public function updateFines($conn) {
        $sql = "UPDATE book_issues 
                SET fine = LEAST(DATEDIFF(CURRENT_DATE, return_date) * ?, ?),
                    status = 'overdue'
                WHERE status = 'issued' 
                AND return_date < CURRENT_DATE";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dd", $this->daily_rate, $this->max_fine);
        return $stmt->execute();
    }
}
?>