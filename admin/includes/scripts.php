<?php
declare(strict_types=1);
/** Подключать перед </body> на страницах admin/ */
$adminScriptBase = $logoBase ?? '../';
?>
<script src="<?php echo htmlspecialchars($adminScriptBase . 'script.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
