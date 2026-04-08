<?php
// Save this file as artisan-web.php in your project root (not public_html)
// Visit it in your browser to run migrations and clear caches safely

if (php_sapi_name() === 'cli') {
    exit("This script is for web use only.\n");
}

set_time_limit(300);

function runArtisan($command) {
    $output = [];
    $return = 0;
    exec("php artisan $command 2>&1", $output, $return);
    echo "<h3>php artisan $command</h3><pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
    if ($return !== 0) {
        echo "<b style='color:red'>Error running: $command</b><br>";
    }
}

?><!DOCTYPE html>
<html><head><title>Laravel Safe Maintenance</title></head><body>
<h2>Laravel Safe Maintenance Script</h2>
<?php
runArtisan('migrate --force');
runArtisan('db:seed --force');
runArtisan('config:cache');
runArtisan('route:cache');
runArtisan('view:cache');
echo "<b>All done! You can now delete this file for security.</b>";
?>
</body></html>
