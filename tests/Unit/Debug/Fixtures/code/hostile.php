<?php

// <script>alert('comment')</script> & "quotes"
$html = '<img src=x onerror=alert(1)>';
echo "<b>$html</b>";
?>
<div onclick="alert(1)">inline html & stuff</div>
