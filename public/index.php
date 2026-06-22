<?php
require __DIR__ . '/../includes/bootstrap.php';
if (!empty($_SESSION['responsavel_id'])) redirect('responsavel/index.php');
if (($_SESSION['role'] ?? '') === 'portaria') redirect('portaria/index.php');
if (($_SESSION['role'] ?? '') === 'admin') redirect('admin/index.php');
redirect('login.php');

