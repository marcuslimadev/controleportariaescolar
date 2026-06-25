<?php
require __DIR__ . '/../includes/bootstrap.php';
if (!empty($_SESSION['responsavel_id'])) redirect('feed.php');
if (($_SESSION['role'] ?? '') === 'portaria') redirect('portaria/index.php');
if (in_array(($_SESSION['role'] ?? ''), ['admin','secretaria','professor'], true)) redirect('feed.php');
redirect('login.php');
