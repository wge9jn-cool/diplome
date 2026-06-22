<?php
declare(strict_types=1);
$scriptBase = $footerPrefix ?? '';
$scriptVer = (int) @filemtime(__DIR__ . '/../script.js');
?>
<script src="<?php echo htmlspecialchars($scriptBase . 'script.js?v=' . $scriptVer, ENT_QUOTES, 'UTF-8'); ?>"></script>
