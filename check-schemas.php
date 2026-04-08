<?php
$dbDsn = 'pgsql:host=aws-1-ap-southeast-2.pooler.supabase.com;port=5432;dbname=postgres;sslmode=require';
$dbUser = 'postgres.fcociwpbmvcadlomdrkm';  
$dbPass = 'Lawrencetabutol_31';

try {
    $pdo = new PDO($dbDsn, $dbUser, $dbPass);
    
    // Check laravel schema
    echo "===== LARAVEL SCHEMA =====\n";
    $result = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='laravel' AND table_type='BASE TABLE'");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . count($tables) . "\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
    echo "\n===== PUBLIC SCHEMA =====\n";
    $result = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public' AND table_type='BASE TABLE'");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . count($tables) . "\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
    // Check current schema
    echo "\n===== CURRENT SEARCH PATH =====\n";
    $result = $pdo->query("SHOW search_path");
    $path = $result->fetch(PDO::FETCH_ASSOC);
    echo "Search Path: " . $path['search_path'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
