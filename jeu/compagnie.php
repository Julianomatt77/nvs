<?php
session_start();
require_once("../fonctions.php");

$mysqli = db_connexion();

include ('../nb_online.php');

// recupération config jeu
$sql = "SELECT disponible FROM config_jeu";
$res = $mysqli->query($sql);
$t_dispo = $res->fetch_assoc();
$dispo = $t_dispo["disponible"];

if($dispo){

	if (@$_SESSION["id_perso"]) {
		
		//recuperation des variables de sessions
		$id = $_SESSION["id_perso"];
		
		$sql = "SELECT pv_perso FROM perso WHERE id_perso='$id'";
		$res = $mysqli->query($sql);
		$tpv = $res->fetch_assoc();
		$testpv = $tpv['pv_perso'];
		
		if ($testpv <= 0) {
			echo "<font color=red>Vous êtes mort...</font>";
		}
		else {
			$erreur = "<div class=\"erreur\">";
	
	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<title>Nord VS Sud</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	</head>
	<body>
		<p align="center"><input type="button" value="Fermer la fenêtre de compagnie" onclick="window.close()"></p>
	<?php
	if (isset($_GET["id_compagnie"])){
		
		$verif = preg_match("#^[0-9]+$#i",$_GET["id_compagnie"]);
		
		if($verif){
			
			$id_compagnie = $_SESSION["id_compagnie"] = $_GET["id_compagnie"];
			
			// vérification que la compagnie existe
			$sql = "SELECT id_clan from compagnies where id_compagnie='$id_compagnie'";
			$res = $mysqli->query($sql);
			$t_c = $res->fetch_assoc();
			
			$exist = $res->num_rows;
			$clan_compagnie = $t_c["id_clan"];
			
			// récupération du clan du perso
			$sql = "SELECT clan FROM perso WHERE id_perso='$id'";
			$res = $mysqli->query($sql);
			$t_cp = $res->fetch_assoc();
			
			$clan_perso = $t_cp["clan"];
			
			if($exist){
				
				// vérification que le perso est bien du meme camp que la compagnie				
				if($clan_perso == $clan_compagnie){
					
					if (isset($_GET["rejoindre"])) { 
					
						// on souhaite rejoindre une compagnie
						if($_GET["rejoindre"] == "ok") {
							
							$ok_n = 1;
							
							// verification que le perso est bien du meme camp que la compagnie				
							if($clan_perso == $clan_compagnie){
							
								// verification que le perso n'est pas deja dans la compagnie
								$sql = "SELECT id_perso FROM perso_in_compagnie WHERE id_compagnie='$id_compagnie'";
								$res = $mysqli->query($sql);
								
								while ($n = $res->fetch_assoc()){
									$id_n = $n["id_perso"];
									if ($id_n == $id) {
										$ok_n = 0;
										break;
									}
								}
								
								// vefication que le perso n'est pas deja dans une compagnie ou en attente sur une autre
								$sql = "SELECT id_perso FROM perso_in_compagnie WHERE id_perso='$id'";
								$res = $mysqli->query($sql);
								$est_deja = $res->num_rows;
								
								if($est_deja){
									$ok_n = 0;
								}
								
								// Verification nombre dans la compagnie
								// recuperation des information sur la compagnie
								$sql = "SELECT genie_civil FROM compagnies WHERE id_compagnie=$id_compagnie";
								$res = $mysqli->query($sql);
								$sec = $res->fetch_assoc();
								$genie_compagnie		= $sec["genie_civil"];
								
								if ($genie_compagnie) {
									$nb_persos_compagnie_max = 60;
								} else {
									$nb_persos_compagnie_max = 80;
								}
								
								// Récupération nombre perso dans la compagnie
								$sql = "SELECT count(*) as nb_persos_compagnie FROM perso_in_compagnie WHERE id_compagnie=$id_compagnie AND (attenteValidation_compagnie='0' OR attenteValidation_compagnie='2')";
								$res = $mysqli->query($sql);
								$tab = $res->fetch_assoc();
								
								$nb_persos_compagnie = $tab["nb_persos_compagnie"];
								
								if ($nb_persos_compagnie >= $nb_persos_compagnie_max) {
									$ok_n = 0;
								}
								
								// si il peut postuler
								if($ok_n == 1) {
									
									// Verification que le type de perso peut postuler dans cette compagnie
									$sql = "SELECT type_perso FROM perso WHERE id_perso='$id'";
									$res = $mysqli->query($sql);
									$t_type = $res->fetch_assoc();
									
									$type_perso = $t_type["type_perso"];
									
									$sql = "SELECT * FROM compagnie_as_contraintes WHERE id_compagnie='$id_compagnie' AND contrainte_type_perso='$type_perso'";
									$res = $mysqli->query($sql);
									$nb_res = $res->num_rows;
									
									if ($nb_res >= 1) {
										
										// mise a jour de la table perso_in_compagnie
										$sql = "INSERT INTO perso_in_compagnie VALUES ('$id','$id_compagnie','5','1')";
										$mysqli->query($sql);
										
										echo "<center><font color='blue'>Vous venez de poser votre candidature dans une compagnie, vous devez attendre que le chef de compagnie ou le recruteur valide votre adhésion</font></center><br>";
										
									} else {
										echo "<center><font color='red'>Vous ne pouvez pas postuler dans cette compagnie, contraintes non respectées</font></center>";
									}
					
									echo "<a href='compagnie.php'> [retour] </a>";
								}
								else {
									echo "<center><font color='red'>Vous êtes déjà inscrit dans une compagnie</font></center>";
								}
							}
							else {
								echo "<center><font color='red'>Vous n'avez pas le droit de postuler dans une compagnie adverse...</font></center>";
							}
						}
						
						if($_GET["rejoindre"] == "off") { 
						
							// on souhaite quitter la compagnie
							// verification si le perso est le chef
							$sql = "SELECT id_perso, poste_compagnie FROM perso_in_compagnie WHERE id_compagnie=$id_compagnie AND id_perso=$id";
							$res = $mysqli->query($sql);
							$verif = $res->fetch_assoc();
							$chef = $verif["poste_compagnie"];
							
							// si c'est le chef de la compagnie
							if ($chef == 1) { 
								echo "<center><font color = red>Vous devez d'abords choisir un nouveau chef avant de quitter la compagnie</font></center><br>";
								echo "<center><a href='chef_compagnie.php'>changer de chef</a></center>";
								echo "<center><a href='compagnie.php'> [retour] </a></center>";
							}
							else { 
						
								// MAJ demande de sortie de la compagnie 
								$sql = "UPDATE perso_in_compagnie SET attenteValidation_compagnie = '2' WHERE id_perso='$id'";
								$mysqli->query($sql);
							
								echo "<center><font color='blue'>Votre demande pour quitter la compagnie a bien été effectuée</font></center>";
								echo "<center><a href='compagnie.php'> [retour] </a></center>";
							}
						}
					}
					else { 
						// on souhaite juste avoir des infos sur la compagnie
						// recuperation des information sur la compagnie
						$sql = "SELECT id_compagnie, nom_compagnie, image_compagnie, resume_compagnie, description_compagnie, genie_civil FROM compagnies WHERE id_compagnie=$id_compagnie";
						$res = $mysqli->query($sql);
						$sec = $res->fetch_assoc();
						
						$id_compagnie 			= $sec["id_compagnie"];
						$nom_compagnie 			= $sec["nom_compagnie"];
						$image_compagnie 		= $sec["image_compagnie"];
						$resume_compagnie 		= $sec["resume_compagnie"];
						$description_compagnie 	= $sec["description_compagnie"];
						$genie_compagnie		= $sec["genie_civil"];
						
						if ($genie_compagnie) {
							$nb_persos_compagnie_max = 60;
						} else {
							$nb_persos_compagnie_max = 80;
						}
						
						// Récupération nombre perso dans la compagnie
						$sql = "SELECT count(*) as nb_persos_compagnie FROM perso_in_compagnie WHERE id_compagnie=$id_compagnie AND (attenteValidation_compagnie='0' OR attenteValidation_compagnie='2')";
						$res = $mysqli->query($sql);
						$tab = $res->fetch_assoc();
						
						$nb_persos_compagnie = $tab["nb_persos_compagnie"];
						
						// affichage des information de la compagnie
						echo "<center><b>$nom_compagnie</b></center>";
						echo "<table border=\"1\" width = 100%><tr><td width=40 height=40><img src=\"".htmlspecialchars($image_compagnie)."\" width=\"40\" height=\"40\"></td><td>".bbcode(htmlentities(stripslashes($resume_compagnie)))."</td><td width=20%><center>Liste des membres (". $nb_persos_compagnie ."/".$nb_persos_compagnie_max.")</center></td>";
						echo "</tr><tr><td></td><td>".bbcode(htmlentities(stripslashes($description_compagnie)))."</td><td>";
						
						// recuperation de la liste des membres de la compagnie
						$sql = "SELECT nom_perso, poste_compagnie FROM perso, perso_in_compagnie 
								WHERE perso_in_compagnie.id_perso=perso.ID_perso AND id_compagnie=$id_compagnie AND (attenteValidation_compagnie='0' OR attenteValidation_compagnie='2') 
								ORDER BY poste_compagnie";
						$res = $mysqli->query($sql);
						
						while ($membre = $res->fetch_assoc()) {
							
							$poste_compagnie 	= $membre["poste_compagnie"];
							$nom_membre 		= $membre["nom_perso"];
							
							if($poste_compagnie != 5){
								
								// recuperation du nom de poste
								$sql2 = "SELECT nom_poste FROM poste WHERE id_poste=$poste_compagnie";
								$res2 = $mysqli->query($sql2);
								$t_p = $res2->fetch_assoc();
								$nom_poste = $t_p["nom_poste"];
								
								echo "<center>".$nom_membre." ($nom_poste)</center>";
							}
							else
								echo "<center>".$nom_membre."</center>";
						}
						
						echo "</td>";
						echo "</tr></table><br>";
						
						if(isset($_GET['voir_compagnie']) && $_GET['voir_compagnie'] == 'ok'){
							echo "";
						}
						else {
							if ($nb_persos_compagnie < $nb_persos_compagnie_max) {
								echo "<center><a href='compagnie.php?id_compagnie=$id_compagnie&rejoindre=ok'> >>Rejoindre</a></center>";
							}
						}
						echo "<br><center><a href=\"compagnie.php\"><font color=\"#000000\" size=\"1\" face=\"Verdana, Arial, Helvetica, sans-serif\">[ retour ]</font></a></center>";
					}
				}
				else {
					echo "<center><center><font color = 'red'>Vous n'avez pas accés aux informations de cette compagnie</font></center>";
				}
			}
			else {
				echo "<center><center><font color = 'red'>La compagnie demandé n'existe pas</font></center>";
			}
		}
		else {
			echo "<center><center><font color = 'red'>La compagnie demandé n'existe pas</font></center>";
		}
	}
	else {
		// si le perso souhaite voir la liste des compagnies
		if(isset($_GET['voir_compagnie']) && $_GET['voir_compagnie']=='ok'){
			
			echo "<br/><center><b><u>Liste des compagnies déjà existants</u></b></center><br/>";
			
			// recuperation des compagnies existantes
			$sql = "SELECT id_compagnie, nom_compagnie, image_compagnie, resume_compagnie, description_compagnie FROM compagnies, perso WHERE id_perso = $id AND compagnies.id_clan = perso.clan";
			$res = $mysqli->query($sql);
			
			while ($sec = $res->fetch_assoc()) {
				
				$id_compagnie 			= $sec["id_compagnie"];
				$nom_compagnie 			= $sec["nom_compagnie"];
				$image_compagnie 		= $sec["image_compagnie"];
				$resume_compagnie 		= $sec["resume_compagnie"];
				$description_compagnie 	= $sec["description_compagnie"];
						
				// creation des tableau avec les compagnies existantes
				echo "<table border=\"1\" width = 100%><tr>
				<td width=40 height=40><img src=\"".htmlspecialchars($image_compagnie)."\" width=\"40\" height=\"40\"></td>
				<th width=25%>$nom_compagnie</th>
				<td>".bbcode(htmlentities(stripslashes($resume_compagnie)))."</td>
				<td width=80><a href='compagnie.php?id_compagnie=$id_compagnie&voir_compagnie=ok'><center>Plus d'infos</center></a></td>";
				echo "</tr></table>";
			}
		}
		else {
			
			// verification si le perso appartient deja a une compagnie
			$sql = "SELECT id_compagnie FROM perso_in_compagnie WHERE id_perso = '$id' AND (attenteValidation_compagnie='0' OR attenteValidation_compagnie='2')";
			$res = $mysqli->query($sql);
			$c = $res->fetch_row();
			
			// il appartient a une compagnie
			if ($c != 0) {
				
				// recuperation de la compagnie a laquelle on appartient
				$id_compagnie = $c[0];
				
				// recuperation des information sur la compagnie
				$sql = "SELECT id_compagnie, nom_compagnie, image_compagnie, resume_compagnie, description_compagnie FROM compagnies WHERE id_compagnie=$id_compagnie";
				$res = $mysqli->query($sql);
				$sec = $res->fetch_assoc();
				
				$id_compagnie 			= $sec["id_compagnie"];
				$nom_compagnie 			= $sec["nom_compagnie"];
				$image_compagnie 		= $sec["image_compagnie"];
				$resume_compagnie 		= $sec["resume_compagnie"];
				$description_compagnie 	= $sec["description_compagnie"];
					
				// affichage des information de la compagnie
				echo "<center><b>$nom_compagnie</b></center>";
				echo "<table border=\"1\" width = 100%><tr><td width=40 height=40><img src=\"".htmlspecialchars($image_compagnie)."\" width=\"40\" height=\"40\"></td><td>".bbcode(htmlentities(stripslashes($resume_compagnie)))."</td><td width=20%><center>Liste des membres</center></td>";
				echo "</tr><tr><td></td><td>".bbcode(htmlentities(stripslashes($description_compagnie)))."</td><td>";
					
				// recuperation de la liste des membres de la compagnie
				$sql = "SELECT nom_perso, poste_compagnie FROM perso, perso_in_compagnie 
						WHERE perso_in_compagnie.id_perso=perso.ID_perso AND id_compagnie=$id_compagnie AND (attenteValidation_compagnie='0' OR attenteValidation_compagnie='2') 
						ORDER BY poste_compagnie";
				$res = $mysqli->query($sql);
				
				while ($membre = $res->fetch_assoc()) {
					
					$nom_membre 		= $membre["nom_perso"];
					$poste_compagnie 	= $membre["poste_compagnie"];
					
					if($poste_compagnie != 5){
						
						// recuperation du nom de poste
						$sql2 = "SELECT nom_poste FROM poste WHERE id_poste=$poste_compagnie";
						$res2 = $mysqli->query($sql2);
						$t_p = $res2->fetch_assoc();
						
						$nom_poste = $t_p["nom_poste"];
						
						echo "<center>".$nom_membre." ($nom_poste)</center>";
					}
					else {
						echo "<center>".$nom_membre."</center>";
					}
				}
				
				echo "</td>";
				echo "</tr></table><br>";
				
				echo "<center><a href='banque_compagnie.php?id_compagnie=$id_compagnie'>Deposer des sous à la banque de la compagnie</a></center>";
				
				// verification si le perso est le chef de la compagnie
				$sql = "SELECT poste_compagnie FROM perso_in_compagnie WHERE id_perso=$id";
				$res = $mysqli->query($sql);
				$boss = $res->fetch_assoc();
				$poste_s = $boss["poste_compagnie"];
				
				// le perso a un poste
				if($poste_s != 5) { 
				
					// c'est le chef
					if($poste_s == 1) { 
						echo "<center><a href='admin_compagnie.php?id_compagnie=$id_compagnie'> Page d'administration de la compagnie</a></center>";
					}
					
					// c'est le tresorier
					if($poste_s == 2){ 
					
						// verification si quelqu'un a demande un emprunt
						$sql = "SELECT banque_compagnie.id_perso FROM banque_compagnie, perso_in_compagnie WHERE demande_emprunt='1' AND id_compagnie=$id_compagnie AND banque_compagnie.id_perso=perso_in_compagnie.id_perso";
						$res = $mysqli->query($sql);
						
						$nb = $res->num_rows;
						
						echo "<center><a href='tresor_compagnie.php?id_compagnie=$id_compagnie'> Page tresorerie de la compagnie</a><font color=red>($nb persos en attente)</font></center>";
					}
					
					// c'est le recruteur
					if($poste_s == 3 || $poste_s == 1){ 
					
						// on verifie si il y a des nouveau persos qui veulent integrer la compagnie
						$sql = "SELECT nom_perso, perso_in_compagnie.id_perso FROM perso_in_compagnie, perso 
								WHERE perso.ID_perso=perso_in_compagnie.id_perso AND id_compagnie=$id_compagnie AND attenteValidation_compagnie='1'";
						$res = $mysqli->query($sql);
						
						// nombre de persos en attente de validation pour rentrer
						$num_e = $res->num_rows; 
						
						// on verifie si il y a des nouveau persos qui veulent quitter la compagnie
						$sql = "SELECT nom_perso, perso_in_compagnie.id_perso FROM perso_in_compagnie, perso 
								WHERE perso.ID_perso=perso_in_compagnie.id_perso AND id_compagnie=$id_compagnie AND attenteValidation_compagnie='2'";
						$res = $mysqli->query($sql);
						
						// nombre de persos en attente pour quitter la compagnie
						$num_q = $res->num_rows; 
						
						$num_a = $num_e + $num_q;
						
						echo "<center><a href='recrut_compagnie.php?id_compagnie=$id_compagnie'> Page de recrutement de la compagnie</a><font color=red> ($num_a persos en attente)</font></center>";
					}
					
					// c'est le diplomate
					if($poste_s == 4){ 
						echo "<center><a href='diplo_compagnie.php?id_compagnie=$id_compagnie'> Page diplomatie de la compagnie</a></center>";
					}
				}
				
				echo "<br/><center><a href='compagnie.php?id_compagnie=$id_compagnie&rejoindre=off'"?> OnClick="return(confirm('êtes vous sûr de vouloir quitter la compagnie ?'))" <?php echo"><b> >>Demander à quitter la compagnie</b></a></center>";
				echo "<br/><br/><a href='compagnie.php?voir_compagnie=ok'>Voir les autres compagnies</a>";
			}
			else {
			
				// verification si le perso est en attente de validation
				$sql = "SELECT id_compagnie FROM perso_in_compagnie WHERE id_perso = '$id' and attenteValidation_compagnie='1'";
				$res = $mysqli->query($sql);
				$c = $res->fetch_row();
			
				if(isset($_GET['annuler']) && $_GET['annuler']=='ok'){
					
					$sql ="delete from perso_in_compagnie where id_perso='$id'";
					$mysqli->query($sql);
			
					echo "Vous venez d'annuler votre demande d'adhésion <br />";
					echo "<a href='compagnie.php'>[ retour ]</>";
				}
				else{
					// en attente de validation
					if ($c != 0) { 
						echo "Vous êtes en attente de validation pour une compagnie";
						echo "<br/><a href='compagnie.php?annuler=ok'>annuler sa candidature</a>";
						echo "<br/><br/><a href='compagnie.php?voir_compagnie=ok'>Voir les autres compagnies</a>";
					}
					else {
				
						// il n'appartient a aucune compagnie
						
						// A t-il demandé la création d'une compagie ?
						$sql = "SELECT count(id_em_creer_compagnie) as verif_creer_comp FROM em_creer_compagnie WHERE id_perso='$id'";
						$res = $mysqli->query($sql);
						$t = $res->fetch_assoc();
						
						$verif_creer_comp = $t["verif_creer_comp"];
						
						if ($verif_creer_comp > 0) {
							echo "<center>Vous avez demandé la création d'un nouvelle compagnie, vous devez attendre la délibération de votre état major</a></center>";
						}
						else {

							echo "<center><a href='creer_compagnie.php'>Créer une nouvelle compagnie</a></center>";
							
							echo "<br/><center><b><u>Liste des compagnies déjà existants</u></b></center><br/>";
							
							// recuperation des compagnies existantes dans lesquels il peut postuler
							$sql = "SELECT compagnies.id_compagnie, nom_compagnie, image_compagnie, resume_compagnie, description_compagnie 
									FROM compagnies, perso, compagnie_as_contraintes
									WHERE id_perso = $id 
									AND compagnies.id_compagnie = compagnie_as_contraintes.id_compagnie
									AND compagnies.id_clan = perso.clan
									AND compagnie_as_contraintes.contrainte_type_perso = perso.type_perso";
							$res = $mysqli->query($sql);
							
							while ($sec = $res->fetch_assoc()) {
								
								$id_compagnie 			= $sec["id_compagnie"];
								$nom_compagnie 			= $sec["nom_compagnie"];
								$image_compagnie 		= $sec["image_compagnie"];
								$resume_compagnie 		= $sec["resume_compagnie"];
								$description_compagnie 	= $sec["description_compagnie"];
							
								// creation des tableau avec les compagnies existantes
								echo "<table border=\"1\" width = 100%><tr>
								<td width=40 height=40><img src=\"".htmlspecialchars($image_compagnie)."\" width=\"40\" height=\"40\"></td>
								<th width=25%>$nom_compagnie</th>
								<td>".bbcode(htmlentities(stripslashes($resume_compagnie)))."</td>
								<td width=80><a href='compagnie.php?id_compagnie=$id_compagnie'><center>Plus d'infos</center></a></td>
								<td width=100><a href='compagnie.php?id_compagnie=$id_compagnie&rejoindre=ok'><center> >>Rejoindre</center></a></td>";
								echo "</tr></table>";
							}
						}
					}
				}
			}
		}
	}
	?>
	</body>
</html>
	<?php
		}
	}
	else{
		echo "<center><font color='red'>Vous ne pouvez pas accéder à cette page, veuillez vous loguer.</font></center>";
	}?>
<?php
}
else {
	// logout
	$_SESSION = array(); // On écrase le tableau de session
	session_destroy(); // On détruit la session
	
	header("Location: index2.php");
}
?>