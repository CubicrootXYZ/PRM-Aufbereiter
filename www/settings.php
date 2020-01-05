<?php 
include 'mapping.php';
?>

<style>
.btn:hover {
        background: black;
        color: white;
    }
    body {
        background: #ff8800;
        font-family: "Arial", sans-serif;
        font-weight: 700;
    }
    .btn {
        border-radius: 0.5rem;
        background: white;
        display: block;
        margin: auto;
        margin-top: 1rem;
    }
    .in {
        background: white;
    }
    .info {
        padding: 1rem;
        text-align: center;
        color: white;
    }
    .error {
        font-weight: 500;
        background: darkred;
        color: white;
        text-align: center;
        padding: 1rem;
        width: 90%;       
        margin: auto; 

    }
</style>

<body>
<a href="index.php">Zurück</a>

<div class="info">
<h1>PRM Aufbereiter Einstellungen</h1>
</div>

<?php

$error = "";

$file = "";

try {
    $file_ = fopen("set.conf", "r");

} catch (Exception $e) {
    
}
if ($file_ == False) {
    $error .= "No Settings found, overwrite with default";
    try {
        $file_ = fopen("set.conf", "w");
        fwrite($file_, json_encode($default_settings));
        fclose($file_);
        $settings = $default_settings;
        
    } catch (Exception $e) {
        echo ("Can not write. Stop execution.");
        exit();
    
    }

} else {
    $settings = json_decode(fread($file_,filesize('set.conf')), true);
    fclose($file_);
}



if(isset($_POST['validate'])) {
    $settings = $_POST;
    unset($settings['validate']);
    $file_ = fopen("set.conf", "w");
    if (!isset($settings['show_ltw'])) {
        $settings['show_ltw'] = "true";
    }
    if (!isset($settings['show_btw'])) {
        $settings['show_btw'] = "true";
    }
    if (!isset($settings['adapt_for'])) {
        $settings['adapt_for'] = "bund";
    }
    if (!isset($settings['show_overall_age'])) {
        $settings['show_overall_age'] = "true";
    }
    if (!isset($settings['show_age'])) {
        $settings['show_age'] = "true";
    }
    if (!isset($settings['show_overall_stats'])) {
        $settings['show_overall_stats'] = "true";
    }
    fwrite($file_, json_encode($settings));
    fclose($file_);
    if ($file_ == False ) {
        $error .= "Konnte Eingabe nicht speichern. ";
    }
}
?>

<form action="#" method="POST">
Daten anpassen für: 
<select name="adapt_for">
<option value="<?php echo ($settings["adapt_for"]);?>"><?php if (isset($mappings[$settings["adapt_for"]]["display_name"])) {echo $mappings[$settings["adapt_for"]]["display_name"];} else {echo "Eintrag nicht mehr verfügbar!";}?></option>
<?php foreach ($mappings as $key => $val): ?>
<option  value="<?php echo ($key);?>"><?php echo $val["display_name"];?></option>
<?php endforeach;?>
</select><br><br>
In der Aufschlüsselung verstecken:
<input type="checkbox" <?php if (isset($settings['show_ltw']) && $settings['show_ltw'] == "false") {echo ("checked");}?> id="mc" name="show_ltw" value="false">
    <label for="mc" > Landtagswahlkreise</label> 
    <input type="checkbox" <?php if (isset($settings['show_btw']) && $settings['show_btw'] == "false") {echo ("checked");}?> id="mc2" name="show_btw" value="false">
    <label for="mc2"> Bundestagswahlkreise</label> 
    <input type="checkbox" <?php if (isset($settings['show_overall_age']) && $settings['show_overall_age'] == "false") {echo ("checked");}?> id="mc3" name="show_overall_age" value="false">
    <label for="mc3"> Gesamtaltersschnitt</label> 
    <input type="checkbox" <?php if (isset($settings['show_age']) && $settings['show_age'] == "false") {echo ("checked");}?> id="mc4" name="show_age" value="false">
    <label for="mc4"> Altersschnitt nach Landesverband</label> 
    <input type="checkbox" <?php if (isset($settings['show_overall_stats']) && $settings['show_overall_stats'] == "false") {echo ("checked");}?> id="mc5" name="show_overall_stats" value="false">
    <label for="mc5"> Gesamtstatistik</label> 
    


<input type="hidden" name="validate">

<button type="submit" class="btn" style="font-size: 150%; padding: 1rem; border: 2px solid black;">Speichern</button>
</form>

<?php if (strlen($error) > 2): ?>
<div class="error">
<?php echo ($error);?>
</div>
<?php endif;?>

</body>