<?php
echo "<h1>PHP + Apache jalan!</h1>";
$c = new mysqli("db", "user", "pass", "web");
echo $c->connect_error ? "MySQL gagal" : "MySQL terkoneksi";