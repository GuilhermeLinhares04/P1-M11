<?php
  header('Content-Type: text/plain');
  echo "OK!\n";

  // Simula um uso intensivo de CPU
  $x = 0.0001;
  for ($i = 0; $i <= 1000000; $i++) {
    $x += sqrt($x);
  }
?>