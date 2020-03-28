<body>
<a href="settings.php">Einstellungen</a>
<div class="info">
<h1>PRM Aufbereiter</h1>
Dieses Tool bereitet CSV-Dateien aus dem PRM statistisch auf. Dazu die CSV-Werte in das Input-Feld eingeben und "Umwandeln" drücken. 
</div>

<style type="text/css" media="all">
    .error {
        font-weight: 500;
        background: darkred;
        color: white;
        text-align: center;
        padding: 1rem;
        width: 90%;       
        margin: auto; 
    }
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
	/* Styles for all media */
	#print_helper {
	  display: none;
	}
	.important {
	  color: #330;
	  background: #ffd;
	  border: 2px solid #dd4;
	  padding: 1em;
	  margin: 1em 0;
	}
	.important a:link,
	.important a:visited {
	  color: #591;

	}
</style>
<style type="text/css" media="print">
  /* Styles for print */
	#print_helper {
		display: block;
		overflow: visible;
		font-family: Menlo, "Deja Vu Sans Mono", "Bitstream Vera Sans Mono", Monaco, monospace;
		white-space: pre;
		white-space: pre-wrap;
        
	}
	#the_textarea {
	  display: none;
	}
	#print_placeholder:after {
		content: "The print stylesheet has been applied. ✓";
		display: inline;
        
	}
</style>

<script type="text/javascript" src="jquery.min.js"></script>
<script type="text/javascript">
jQuery(function($){
  function copy_to_print_helper(){
    $('#print_helper').text($('#the_textarea').val());
  }
  $('#the_textarea').bind('keydown keyup keypress cut copy past blur change', function(){
    copy_to_print_helper(); // consider debouncing this to avoid slowdowns!
  });
  copy_to_print_helper(); // on initial page load
});
</script>

<form action="#" method="POST">
CSV INPUT:<br>
<textarea name="csvin" style="width: 100%; min-height: 30vh;background: white;"></textarea>

<button type="submit" class="btn" style="font-size: 150%; padding: 1rem; border: 2px solid black;">UMWANDELN</button>
</form>

<?php
include 'mapping.php';
$error = "";


$file_ = fopen("set.conf", "r");
$settings = json_decode(fread($file_,filesize('set.conf')), true);
fclose($file_);

if ($file_ == False) {
    try {
        $file_ = fopen("set.conf", "w");
        fwrite($file_, json_encode($default_settings));
        fclose($file_);
        $settings = $default_settings;
        
    } catch (Exception $e) {
        echo ("Can not write. Stop execution.");
        exit();
    
    }
}   



if (isset($_POST['csvin'])) {
    // initialize everything
    $stats = [];
    $vals =[];
    

    // parse csv
    try {
        $lines = array_filter(explode("\n", trim($_POST['csvin'])));
        $lines[0] = trim(preg_replace('/\s+/', '',$lines[0]));
    } catch (Exception $e) {
        $error .= "Format konnte nicht richtig gelesen werden. ";
    }

    $header = explode(",", $lines[0]);
    unset($lines[0]);

    foreach ($lines as $line) {
        $entry = [];
        $i = 0;
        $line = explode(",", trim($line));
        while ($i < sizeof($header)) {
            
            try {
                $entry[$header[$i]] = $line[$i];
                
            } catch (Exception $e) {
                $entry[$header[$i]] = "";
                $error .= "Spaltenzahl nicht konsistent! ";
            }

            $i += 1;
        }
        array_push($vals, $entry);
    }

    // calc stats

    

        // enter all the fields for that the amount of members should be calculated => map them to a output-name
        $fields = array("comp_user_lv" => 'foreign_country_members', "comp_user_landkreis" => 'landkreis_members', "terr_caption" => 'gliederung_members', "comp_user_wahlkreisbtw" => 'wk-btw_members', "comp_user_wahlkreisltw" => 'wk-ltw_members', "comp_user_kreis" => 'kreis_members', "comp_user_bezirk" => 'bezirk_members', "comp_user_ov" => 'ov_members');

        foreach ($fields as $field => $value) {
            $stats[$value]=[];
            $stats['age'] = 0;
            $stats['age_cnt'] = 0;
            $stats["members_total"] = 0;
            $stats["members_vote"] = 0;
            foreach ($vals as $entry) {
                //calc average age
                if  (in_array("comp_user_geburtsdatum", $header)) {
                    try {
                        $date_birth = DateTime::createFromFormat('d.m.Y', $entry["comp_user_geburtsdatum"]);
                        $date_diff = $date_birth->diff(new DateTime('NOW'));
                        
                        $stats['age'] += $date_diff->format("%y");
                        $stats['age_cnt'] += 1;
                    }
                    catch (Exception $e) {
                        $error .= "<br> Alter nicht richtig gesetzt, ignoriere dieses Alter. ";
                    }
                }

                 // total member statistics        
                $stats['members_total'] += 1;
                if(in_array("comp_user_zstimmberechtigung", $header) && $entry["comp_user_zstimmberechtigung"] == "Ja"){
                    $stats['members_vote'] += 1;
                }

        
                if  (in_array($field, $header)) {
                    
                    if  (array_key_exists($entry[$field], $stats[$value])) {
                        if (array_key_exists("total", $stats[$value][$entry[$field]])) {
                            $stats[$value][$entry[$field]]['total'] += 1;
                        } else {
                            $stats[$value][$entry[$field]]['total'] = 1;
                        }
                        
                    } else {
                        $stats[$value][$entry[$field]]['total'] = 1;
                    }

                } 
               
                // calc age average per lv
                if  (in_array($field, $header) && in_array("comp_user_geburtsdatum", $header)) {
                    if  (array_key_exists($entry[$field], $stats[$value]) ) {
                        $date_birth = DateTime::createFromFormat('d.m.Y', $entry["comp_user_geburtsdatum"]);
                        $date_diff = $date_birth->diff(new DateTime('NOW'));
                       
                        if (array_key_exists("age", $stats[$value][$entry[$field]])) {
                            $stats[$value][$entry[$field]]['age'] += $date_diff->format("%y");
                        $stats[$value][$entry[$field]]['age_cnt'] += 1;

                            
                        } else {
                            $stats[$value][$entry[$field]]['age'] = $date_diff->format("%y");
                            $stats[$value][$entry[$field]]['age_cnt'] = 1;
                            
                        }
                        
                    } 
                }

                if  (in_array($field, $header) && in_array("comp_user_zstimmberechtigung", $header)) {
                    $entry["comp_user_zstimmberechtigung"] = trim(preg_replace('/\s+/', '',$entry["comp_user_zstimmberechtigung"]));
                    if  (array_key_exists($entry[$field], $stats[$value]) ) {
                        
                        
                        if (array_key_exists("vote", $stats[$value][$entry[$field]])) {
                            if($entry["comp_user_zstimmberechtigung"] == "Ja"){
                                
                                $stats[$value][$entry[$field]]['vote'] += 1;
                            }
                            
                        } else {
                            if($entry["comp_user_zstimmberechtigung"] == "Ja"){
                                $stats[$value][$entry[$field]]['vote'] = 1;
                                
                            } else {
                                $stats[$value][$entry[$field]]['vote'] = 0;
                                
                            }
                        }
                        
                    } 
                }

            }

         

    }

}
$out = "";
if (isset($_POST['csvin'])) {
    // format output
    $out = "Stand: ".date("d.m.y");

    // Altersschnitt
    if ($settings['show_overall_age'] != 'false' && $stats['age'] != 0) {
        $out .= "\n\nDurchschnittsalter (gesamt): ".round($stats['age']/$stats['age_cnt'])." Jahre";
    }

    // overall statistics 
    if ($settings['show_overall_stats'] != 'false' && $stats['age'] != 0) {
        $out .= "\n\nMitglieder (gesamt): ".$stats['members_total']." davon ".$stats['members_vote']." (".round(100*$stats['members_vote']/$stats['members_total'])."%) stimmberechtigt.";
    }
    
    
    $out .= "\n\n## Nach Gliederung";

    foreach ($stats['foreign_country_members'] as $key => $val) {
        $name = replace_lv($key);
        if ($name == False) {
            continue;
        }
        if (isset($val['vote'])) {
            $out .= "\n\n".$name.": ".$val['total']." Mitglieder, davon ".$val['vote']." (".round(($val['vote']/$val['total'])*100)." %) stimmberechtigt. ";
        } else {
            $out .= "\n\n".$name.": ".$val['total']." Mitglieder. ";
        }
        if (isset($val['age']) && $settings['show_age'] != 'false' && $val['age'] != 0) {
            $out .= "Durchschnittsalter: ".round($val['age']/$val['age_cnt'])." Jahre. ";
        }
    } 
    foreach ($stats['bezirk_members'] as $key => $val) {
        $name = replace_bz($key);
        if ($name == False) {
            continue;
        }
        if (isset($val['vote'])) {
            $out .= "\n\n".$name.": ".$val['total']." Mitglieder, davon ".$val['vote']." (".round(($val['vote']/$val['total'])*100)." %) stimmberechtigt.";
        } else {
            $out .= "\n\n".$name.": ".$val['total']." Mitglieder";
        }
    } 
    foreach ($stats['kreis_members'] as $key => $val) {
        $name = replace_kr($key);
        if ($name == False) {
            continue;
        }
        if (isset($val['vote'])) {
            $out .= "\n\n".$name.": ".$val['total']." Mitglieder, davon ".$val['vote']." (".round(($val['vote']/$val['total'])*100)." %) stimmberechtigt.";
        } else {
            $out .= "\n\n".$name.": ".$val['total']." Mitglieder";
        }
    } 

    $out .= "\n\n## Nach Kreis";

    
    foreach ($stats['landkreis_members'] as $key => $val) {
        $name = $key;
        if ($name == False) {
            continue;
        }
        if (isset($val['vote'])) {
            $out .= "\n\n".$name.": ".$val['total']." Mitglieder, davon ".$val['vote']." (".round(($val['vote']/$val['total'])*100)." %) stimmberechtigt";
        } else {
            $out .= "\n\n".$name.": ".$val['total']." Mitglieder";
        }
    } 

if ($settings['show_btw'] != 'false') {
    $out .= "\n\n## Nach Bundestagswahlkreis";
    ksort($stats['wk-btw_members']);
    ksort($stats['wk-ltw_members']);
    foreach ($stats['wk-btw_members'] as $key => $val) {
        $name = replace_btw($key);
        if ($name == False) {
            continue;
        }
        /*if (isset($val['vote'])) {
            $out .= "\n\n[".$key."] ".$name.": ".$val['total']." Mitglieder, davon ".$val['vote']." (".round(($val['vote']/$val['total'])*100)." %) stimmberechtigt";
        } else {*/
            $out .= "\n\n[".$key."] ".$name.": ".$val['total']." Mitglieder";
        //}
    } 
}

if ($mappings[$settings['adapt_for']]["ltw"] == True && $settings['show_ltw'] != "false") {

    $out .= "\n\n## Nach Landtagsswahlkreis";

    foreach ($stats['wk-ltw_members'] as $key => $val) {
        $name = replace_ltw($key);
        if ($name == False) {
            continue;
        }
        /*if (isset($val['vote'])) {
            $out .= "\nv[".$key."] ".$name.": ".$val['total']." Mitglieder, davon ".$val['vote']." (".round(($val['vote']/$val['total'])*100)." %) stimmberechtigt";
        } else {*/
            $out .= "\n\n[".$key."] ".$name.": ".$val['total']." Mitglieder";
        //}
    } 
}
}
    ?>

<?php if (strlen($error) > 1): ?>
<div class="error">
<?php echo($error);?>
</div>

<?php endif;?>
<?php echo($error);?>
STATISTIK OUTPUT:<br>
    <textarea name="textarea" wrap="wrap" id="the_textarea" style="width:100%;min-height:40vh;background: white;">
<?php echo $out;?>
</textarea>
<div id="print_helper"><?php echo $out;?></div>

<?php

# mapping functions for the names

function replace_lv($short) {
    $map = array(
        "BW" => "Landesverband Baden-Württemberg",
        "" => False
    );

    if (isset($map[$short])) {
        return $map[$short];
    } else {
        return $short;
    }
}
function replace_bz($short) {
    $map = array(
        "BW.FR" => "Bezirksverband Freiburg",
        "BW.KA" => "Regierungsbezirk Karlsruhe",
        "BW.S" => "Bezirksverband Stuttgart",
        "BW.TÜ" => "Bezirksverband Südwürttemberg",
        "" => False
    );

    if (isset($map[$short])) {
        return $map[$short];
    } else {
        return $short;
    }
}

function replace_kr($short) {
    $map = array(
        "BW.KA.BAD" => "Kreisgruppe Mittelbaden",
        "BW.KA.HD" => "Kreisverband Rhein-Neckar/Heidelberg",
        "BW.S.S" => "Kreisverband Stuttgart",
        "BW.TÜ.UL" => "Kreisverband Ulm/Alb-Donaukreis",
        "" => False
    );

    if (isset($map[$short])) {
        return $map[$short];
    } else {
        return $short;
    }
}

function replace_btw($short) {
    global $maps;
    $map = $maps["map_btw"];

    if (isset($map[$short])) {
        return $map[$short];
    } else {
        return $short;
    }
}

function replace_ltw($short) {
    global $settings;
    global $maps;
    $name = "map_".$settings["adapt_for"]."_ltw";

    if (!isset($maps[$name])) {
        return False;
    }
    $map = $maps[$name];

    if (isset($map[$short])) {
        return $map[$short];
    } else {
        return $short;
    }
}

    ?>
</body>
