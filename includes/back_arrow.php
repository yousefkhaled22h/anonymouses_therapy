<?php
// includes/back_arrow.php
$dash_link = 'dashboard.php';
$_ba_color = '#6c4b2a';
$_ba_border = '#ede8e1';
$_is_therapist = false;
if(isset($_SESSION['role'])) {
    $r = strtolower($_SESSION['role']);
    if($r === 'therapist') { 
        $dash_link = 'therapist_dashboard.php'; 
        $_ba_color = '#337AB7'; 
        $_ba_border = '#D1E5F7'; 
        $_is_therapist = true;
    }
    if($r === 'volunteer') { 
        $dash_link = 'volunteer/dashboard.php'; 
        $_ba_color = '#27ae60'; 
        $_ba_border = '#d1f0dd'; 
    }
}
?>
<div style="margin-bottom: 20px;">
    <?php if ($_is_therapist): ?>
        <a href="<?php echo htmlspecialchars($dash_link); ?>" style="display: inline-flex; align-items: center; justify-content: center; color: <?php echo $_ba_color; ?>; background: transparent; border: none; font-size: 1.5rem; transition: transform 0.2s; text-decoration: none;" onmouseover="this.style.transform='scale(1.15)';" onmouseout="this.style.transform='scale(1)';">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    <?php else: ?>
        <a href="<?php echo htmlspecialchars($dash_link); ?>" style="border-radius: 50%; width: 45px; height: 45px; display: inline-flex; align-items: center; justify-content: center; padding: 0; color: <?php echo $_ba_color; ?>; border: 2px solid <?php echo $_ba_border; ?>; background: white; transition: all 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-decoration: none;" onmouseover="this.style.transform='scale(1.05)';" onmouseout="this.style.transform='scale(1)';">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
    <?php endif; ?>
</div>
