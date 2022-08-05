<?php
session_start();

header("Content-type: image/png");//on va commencer par declarer que l'on veut creer une image

//ensuite on defini la taille de l'image
$perso_carte = imagecreate(603,603)  or die ("Cannot Initialize new GD image stream");
$image_carte = imagecreatefrompng("carte/carte.png");

//maintenant on donne une couleur a notre image (ici un fond noir)
$fond_perso=Imagecolorallocate($perso_carte, 250, 250, 250);

// on definit le font de l'image perso_carte comme transparent
imagecolortransparent($perso_carte,$fond_perso);

//Commme d'ab
require_once "../fonctions.php";

$mysqli = db_connexion();

// couleurs perso_carte
$noir 							= Imagecolorallocate($perso_carte, 0, 0, 0); // noir
$couleur_vert 					= Imagecolorallocate($perso_carte, 10, 254, 10); // vert bien voyant
$couleur_perso_clan1 			= Imagecolorallocate($perso_carte, 10, 10, 254); // bleu bien voyant
$couleur_perso_clan2 			= Imagecolorallocate($perso_carte, 254, 10, 10); // rouge bien voyant
$couleur_bat_clan1 				= Imagecolorallocate($perso_carte, 75, 75, 254); // bleu batiments
$couleur_bat_clan2 				= Imagecolorallocate($perso_carte, 254, 75, 75); // rouge batiments
$couleur_bat_neutre				= Imagecolorallocate($perso_carte, 130, 130, 130); // gris batiments
$couleur_rail					= Imagecolorallocate($perso_carte, 200, 200, 200); // gris rails
$couleur_brouillard_plaine		= Imagecolorallocate($perso_carte, 208, 192, 122); // Chamois
$couleur_brouillard_eau			= Imagecolorallocate($perso_carte, 187, 174, 152); // Grège
$couleur_brouillard_montagne	= Imagecolorallocate($perso_carte, 47, 27, 12); // Cachou
$couleur_brouillard_colinne		= Imagecolorallocate($perso_carte, 133, 109, 77); // Bistre
$couleur_brouillard_desert		= Imagecolorallocate($perso_carte, 225, 206, 154); // Vanille
$couleur_brouillard_foret		= Imagecolorallocate($perso_carte, 97, 77, 26); // Vanille

// je vais chercher les rails dans ma table
$sql = "SELECT x_carte, y_carte FROM carte 
		WHERE (fond_carte='rail.gif' OR fond_carte='rail_1.gif' OR fond_carte='rail_2.gif' OR fond_carte='rail_3.gif' OR fond_carte='rail_4.gif' OR fond_carte='rail_5.gif' OR fond_carte='rail_7.gif' OR fond_carte='railP.gif')";
$res = $mysqli->query($sql);

while ($t = $res->fetch_assoc()){
	
	$x = $t["x_carte"];
	$y = $t["y_carte"];
	
	imagefilledrectangle ($perso_carte, (($x*3)-1), (((600-($y*3)))-1), (($x*3)+1), (((600-($y*3)))+1), $couleur_rail);
	
}

// je vais chercher les pnj dans ma table
$sql = "SELECT x_i, y_i FROM instance_pnj WHERE pv_i>0";
$res = $mysqli->query($sql);

while ($t = $res->fetch_assoc()){
	
	$x = $t["x_i"];
	$y = $t["y_i"];
	$color = $noir;
	
	imagefilledrectangle ($perso_carte, (($x*3)-1), (((600-($y*3)))-1), (($x*3)+1), (((600-($y*3)))+1), $color);
}

// je vais chercher les perso dans ma table
$sql = "SELECT id_perso, x_perso, y_perso, clan FROM perso WHERE pv_perso>0 and est_gele='0'";
$res = $mysqli->query($sql);

while ($t = $res->fetch_assoc()){
	
	$x 		= $t["x_perso"];
	$y 		= $t["y_perso"];
	$id_p	= $t["id_perso"];
	$clan 	= $t["clan"];
	
	if($clan == '1'){
		$color = $couleur_perso_clan1;
	}
	if($clan == '2'){
		$color = $couleur_perso_clan2;
	}
	
		
	// On regarde si le perso est en foret
	$sql_f = "SELECT fond_carte FROM carte WHERE x_carte='$x' AND y_carte='$y'";
	$res_f = $mysqli->query($sql_f);
	$t_f = $res_f->fetch_assoc();
	
	$fond_carte = $t_f["fond_carte"];
	
	if ($fond_carte != '7.gif') {
		imagefilledrectangle ($perso_carte, (($x*3)-1), (((600-($y*3)))-1), (($x*3)+1), (((600-($y*3)))+1), $color);
	}
}

// je vais chercher les batiments dans ma table
$sql = "SELECT x_instance, y_instance, camp_instance, taille_batiment, batiment.id_batiment FROM instance_batiment, batiment WHERE batiment.id_batiment = instance_batiment.id_batiment AND (pv_instance>0 OR instance_batiment.id_batiment = 13)";
$res = $mysqli->query($sql);

while ($t = $res->fetch_assoc()){
	
	$x 			= $t["x_instance"];
	$y 			= $t["y_instance"];
	$camp 		= $t["camp_instance"];
	$taille_bat = $t["taille_batiment"];
	$id_bat 		= $t["id_batiment"];
	
	switch($camp){
	case "1":
		$color = $couleur_bat_clan1;
		break;
	case "2":
		$color = $couleur_bat_clan2;
		break;
	default:
		$color = $couleur_bat_neutre;
	}
	
	imagefilledrectangle ($perso_carte, (($x*3)-$taille_bat), (((600-($y*3)))-$taille_bat), (($x*3)+$taille_bat), (((600-($y*3)))+$taille_bat), $color);

	// Met en évidence les points stratégiques
	if ($id_bat == 13) {
		imageellipse($perso_carte, 3*$x, 600-3*$y, 20, 20, $color);
		imageellipse($perso_carte, 3*$x, 600-3*$y, 21, 21, $color);
	}
}

$date = date('Y-m-d-H-i-s');

// creation de l'image carte_sl
imagecopymerge ($image_carte, $perso_carte, 0, 0, 0, 0, 603, 603, 100);

// creation de l'image carte
imagepng($image_carte, "histo_carte/carte-$date.png");

ImageDestroy ($perso_carte);
ImageDestroy ($image_carte);
?>
