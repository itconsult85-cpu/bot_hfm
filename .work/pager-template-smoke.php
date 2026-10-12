<?php
function esc($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
class PagerFixture {
    public function setSurroundCount(?int $count = null) {}
    public function getCurrentPageNumber(): int { return 5; }
    public function getFirstPageNumber(): int { return 3; }
    public function getLastPageNumber(): int { return 7; }
    public function getPageCount(): int { return 10; }
    public function hasPreviousPage(): bool { return true; }
    public function getPreviousPage(): string { return '?page=4'; }
    public function hasNextPage(): bool { return true; }
    public function getNextPage(): string { return '?page=6'; }
    public function getFirst(): string { return '?page=1'; }
    public function getLast(): string { return '?page=10'; }
    public function links(): array { $out=[]; for($i=3;$i<=7;$i++) $out[]=['uri'=>'?page='.$i,'title'=>$i,'active'=>$i===5]; return $out; }
}
$pager = new PagerFixture();
include 'app/Views/pager/consistent_full.php';