<?php

use CodeIgniter\Pager\PagerRenderer;

/** @var PagerRenderer $pager */
$pager->setSurroundCount(2);
$currentPage = $pager->getCurrentPageNumber();
$firstPage = $pager->getFirstPageNumber();
$lastPage = $pager->getLastPageNumber();
$totalPages = $pager->getPageCount();
?>
<?php if ($totalPages > 1): ?>
    <nav aria-label="Navigasi halaman">
        <ul class="pagination pagination-sm mb-0 flex-wrap">
            <li class="page-item <?= $pager->hasPreviousPage() ? '' : 'disabled' ?>">
                <?php if ($pager->hasPreviousPage()): ?>
                    <a class="page-link shadow-sm rounded-start-pill" href="<?= esc($pager->getPreviousPage()) ?>" aria-label="Halaman sebelumnya">&laquo;</a>
                <?php else: ?>
                    <span class="page-link shadow-sm rounded-start-pill" aria-hidden="true">&laquo;</span>
                <?php endif; ?>
            </li>

            <?php if ($firstPage > 1): ?>
                <li class="page-item"><a class="page-link shadow-sm" href="<?= esc($pager->getFirst()) ?>" aria-label="Halaman pertama">1</a></li>
                <?php if ($firstPage > 2): ?>
                    <li class="page-item disabled"><span class="page-link shadow-sm border-0 text-muted">...</span></li>
                <?php endif; ?>
            <?php endif; ?>

            <?php foreach ($pager->links() as $link): ?>
                <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
                    <a class="page-link shadow-sm" href="<?= esc($link['uri']) ?>" <?= $link['active'] ? 'aria-current="page"' : '' ?>><?= esc((string) $link['title']) ?></a>
                </li>
            <?php endforeach; ?>

            <?php if ($lastPage < $totalPages): ?>
                <?php if ($lastPage < $totalPages - 1): ?>
                    <li class="page-item disabled"><span class="page-link shadow-sm border-0 text-muted">...</span></li>
                <?php endif; ?>
                <li class="page-item"><a class="page-link shadow-sm" href="<?= esc($pager->getLast()) ?>" aria-label="Halaman terakhir"><?= $totalPages ?></a></li>
            <?php endif; ?>

            <li class="page-item <?= $pager->hasNextPage() ? '' : 'disabled' ?>">
                <?php if ($pager->hasNextPage()): ?>
                    <a class="page-link shadow-sm rounded-end-pill" href="<?= esc($pager->getNextPage()) ?>" aria-label="Halaman berikutnya">&raquo;</a>
                <?php else: ?>
                    <span class="page-link shadow-sm rounded-end-pill" aria-hidden="true">&raquo;</span>
                <?php endif; ?>
            </li>
        </ul>
    </nav>
<?php endif; ?>
