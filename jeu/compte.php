<?php
session_start();
require_once("../fonctions.php");

$mysqli = db_connexion();

include ('../nb_online.php');
include ('../forum/config.php');

define('IN_PHPBB', true);
$phpEx = 'php';

$phpbb_root_path = '../forum/';
require_once($phpbb_root_path ."common.php");

$request->enable_super_globals();

// recupération config jeu
$dispo = config_dispo_jeu($mysqli);
$admin = admin_perso($mysqli, $_SESSION["id_perso"]);

if($dispo || $admin){

	if (isset($_SESSION["id_perso"])) {
		//recuperation des variables de sessions
		$id = $_SESSION["id_perso"];
		$sql = "SELECT pv_perso, a_gele, est_gele, nom_perso, chef FROM perso WHERE id_perso='$id'";
		$res = $mysqli->query($sql);
		$tpe = $res->fetch_assoc();
		
		$testpv 	= $tpe['pv_perso'];
		$a_g 		= $tpe['a_gele'];
		$e_g 		= $tpe['est_gele'];
		$pseudo_p 	= $tpe['nom_perso'];
		$chef		= $tpe['chef'];
		
		$mess 		= "";
		$mess_err 	= "";
		
		if($e_g){
			// redirection
			header("location:../tour.php");
		}
		else {
			if (isset($_GET["gele"]) && $_GET["gele"] == "ok"){
				
				if ($a_g){
					echo "<font color=red>Vous avez déjà demandé de geler votre perso, le gel sera effectif à minuit</font><br />";
				}
				else {
					$date_gele = time();
					
					// maj du perso => statut en gele
					$sql = "UPDATE perso SET a_gele='1', date_gele=FROM_UNIXTIME($date_gele) WHERE id_perso='$id'";
					$mysqli->query($sql);
					
					// redirection vers la page d'accueil
					header("location:../logout.php");
				}
			}
			
			if ($testpv <= 0) {
				echo "<font color=red>Vous êtes mort...</font>";
			}
			else {
				$sql= "SELECT idJoueur_perso FROM perso WHERE id_perso ='".$id."'";
				$res = $mysqli->query($sql);
				$t = $res->fetch_assoc();
				
				$id_joueur = $t["idJoueur_perso"];
				
				$sql = "SELECT mdp_joueur FROM joueur WHERE id_joueur ='".$id_joueur."'";
				$res = $mysqli->query($sql);
				$tabAttr = $res->fetch_assoc();
				
				$mdp_joueur = $tabAttr["mdp_joueur"];				
	
				// Changement de MDP
				if (isset($_POST['mdp_change']) && $_POST['mdp_change'] != "" ) {
					
					if (isset($_POST['verif_mdp']) && $mdp_joueur == md5($_POST['verif_mdp'])) {
						
						$mdp = $_POST['mdp_change'];
						
						$mdp_hash = md5($_POST['mdp_change']);
						$sql = "UPDATE joueur SET mdp_joueur='$mdp_hash' WHERE id_joueur ='".$id_joueur."'";
						$mysqli->query($sql);
						
						$new_mdp_forum = phpbb_hash($mdp);
						
						// Récupération de l'id de l'utilisateur sur le forum 
						$sql = "SELECT user_id FROM ".$table_prefix."users WHERE username IN 
									(SELECT nom_perso FROM perso WHERE idJoueur_perso IN 
										(SELECT idJoueur_perso FROM perso WHERE id_perso='$id') AND chef='1')";
						$res = $mysqli->query($sql);
						$t = $res->fetch_assoc();
						
						$id_user_forum = $t['user_id'];
						
						$sql = "UPDATE ".$table_prefix."users SET user_password='$new_mdp_forum' WHERE user_id='$id_user_forum'";
						$mysqli->query($sql);
						
						$mess .=  "Mot de passe chang&eacute;";
					}
					else {
						$mess_err .=  "Mot de passe incorrect";
					}
				}
				
				// Changement email
				if ($_POST['email_change'] != "" ) {
				
					if (!filtremail($_POST['email_change'])){
						$mess_err .= "email incorrect";
					}
					else {
						
						if (isset($_POST['verif_mdp_email']) && $mdp_joueur == md5($_POST['verif_mdp_email'])) {
						
							$email = $_POST['email_change'];
							$sql = "UPDATE joueur SET email_joueur='$email' WHERE id_joueur ='".$id_joueur."'";
							$mysqli->query($sql);
							
							$mess .= "Email modifié avec succés.";
						}
						else {
							$mess_err .=  "Mot de passe incorrect";
						}
					}
				}
	
				if (isSet($_POST['eval_compte']) == "Enregistrer") {
					
					// Age
					if (isset($_POST['age_change']) && $_POST['age_change'] != "") {
						
						$age = $_POST['age_change'];
						$sql = "UPDATE joueur SET age_joueur='$age' WHERE id_joueur ='".$id_joueur."'";
						$mysqli->query($sql);
						
						$mess .= "Age modifié avec succés.";
					}
					
					// Pays
					if (isset($_POST['pays_change']) && $_POST['pays_change'] != "") {
						
						$pays = $_POST['pays_change'];
						$sql = "UPDATE joueur SET pays_joueur='$pays' WHERE id_joueur ='".$id_joueur."'";
						$mysqli->query($sql);
						
						$mess .= "Pays modifié avec succés.";
					}
					
					// Region
					if (isset($_POST['region_change']) && $_POST['region_change'] != "") {
						
						$region = $_POST['region_change'];
						$sql = "UPDATE joueur SET region_joueur='$region' WHERE id_joueur ='".$id_joueur."'";
						$mysqli->query($sql);
						
						$mess .= "Région modifié avec succés.";
					}
					
					// Coche mail attaque
					if (isset($_POST['mail_info'])){
						
						$statut = $_POST['mail_info'];
						
						if($statut == 'on'){
							$sql = "UPDATE joueur SET mail_info='1' WHERE id_joueur ='".$id_joueur."'";
							$mysqli->query($sql);
						}
					} 
					else {
						$sql = "UPDATE joueur SET mail_info='0' WHERE id_joueur ='".$id_joueur."'";
						$mysqli->query($sql);
					}
					
					// Dossier img
					if (isset($_POST["select_dossier_img"])) {
						
						$new_dossier_img = $_POST["select_dossier_img"];
						
						$sql = "UPDATE joueur SET dossier_img='$new_dossier_img' WHERE id_joueur ='".$id_joueur."'";
						$mysqli->query($sql);
					}
				}
				
				//recup infos			
				$sql = "SELECT * FROM joueur WHERE id_joueur ='".$id_joueur."'";
				$res = $mysqli->query($sql);
				$t = $res->fetch_assoc();
				
				$nom_joueur 		= $t["nom_joueur"];
				$email_joueur 		= $t["email_joueur"];
				$mdp_joueur 		= $t["mdp_joueur"];
				$age_joueur 		= $t["age_joueur"];
				$pays_joueur 		= $t["pays_joueur"];
				$region_joueur 		= $t["region_joueur"];
				$description_joueur = $t["description_joueur"];
				$mail_info_joueur 	= $t["mail_info"];
				$dossier_img_joueur = $t["dossier_img"];
	?>
<html>
	<head>
		<title>Nord VS Sud</title>
		
		<!-- Required meta tags -->
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		
		<!-- Bootstrap CSS -->
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
		
	</head>
	
	<body>
		<div class="container-fluid">
	
			<nav class="navbar navbar-expand-lg navbar-light">
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="navbarSupportedContent">
					<ul class="navbar-nav mr-auto nav-pills">
						<li class="nav-item">
							<a class="nav-link" href="profil.php">Profil</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="ameliorer.php">Améliorer son perso</a>
						</li>
						<?php
						if($chef) {
							echo "<li class='nav-item'><a class='nav-link' href=\"recrutement.php\">Recruter des grouillots</a></li>";
							echo "<li class='nav-item'><a class='nav-link' href=\"gestion_grouillot.php\">Gérer ses grouillots</a></li>";
						}
						?>
						<li class="nav-item">
							<a class="nav-link" href="equipement.php">Equiper son perso</a>
						</li>
						<li class="nav-item">
							<a class="nav-link active" href="#">Gérer son Compte</a>
						</li>
					</ul>
				</div>
			</nav>
			
			<hr>
		
			<div class="row">
				<div class="col-12">
					<center><h1>Mon Compte</h1></center>
				</div>
			</div>
			
			<div class="row">
				<div class="col-12">
					<div align=center><input type="button" value="Fermer cette fenêtre" onclick="window.close()"></div>
				</div>
			</div>
			
			<br />
			
			<center><a class='btn btn-danger' href="compte.php?gele=ok" OnClick="return(confirm('êtes vous sûr de vouloir geler votre perso ?'))">Geler son compte</a></center><br />
			
			<div class="row">
				<div class="col-12">
					<font color='blue'><?php echo $mess; ?></font>
					<font color='red'><b><?php echo $mess_err; ?></b></font>
				</div>
			</div>
			
			<hr />
			
			<div class="row">
				<div class="col-12">
					<h4>Modifier mon mot de passe</h4>
				</div>
			</div>
			<div class="row">
				<form method="post" class="form-inline" action="compte.php">
					<div class="form-group col-5">
						<label for="motDePasse">Mot de passe actuel :&nbsp;</label>
						<input type="password" class="form-control" name="verif_mdp" id="motDePasse" value="" maxlength="20">
					</div>
					<div class="form-group col-5">
						<label for="NouveauMotDePasse">Nouveau mot de passe :&nbsp;</label>
						<input type="password" class="form-control" name="mdp_change" id="NouveauMotDePasse" value="" maxlength="20">
					</div>

					<button type="submit" class="btn btn-primary align-bottom">Submit</button>
				</form>
			</div>
			
			<hr>
			
			<div class="row">
				<div class="col-12">
					<h4>Modifier mon email</h4>
				</div>
			</div>
			<div class="row">
				<div class="col-12">
					<font color='red'>Veuillez entrer votre mot de passe afin de pouvoir modifier votre email</font>
				</div>
			</div>
			<div class="row">
				<form method="post" class="form-inline" action="compte.php">
					<div class="form-group col-5">
						<label for="motDePasseEmail">Mot de passe actuel :&nbsp;</label>
						<input type="password" class="form-control" name="verif_mdp_email" id="motDePasseEmail" value="" maxlength="20">
					</div>
					<div class="form-group col-5">
						<label for="NouveauEmail">Nouvel email :&nbsp;</label>
						<input type="text" class="form-control" name="email_change" id="NouveauEmail" value="" maxlength="40">
					</div>
					<button type="submit" class="btn btn-primary align-bottom">Submit</button>
				</form>
			</div>
			
			<hr>
			
			<br />
			
			<form method="post" action="compte.php">
				<div class="row">
					<div class="col-2">
						Votre email : 
					</div>
					<div class="col-10">
						<?php echo $email_joueur; ?>
					</div>
				</div>
				
				<div class="row">
					<div class="col-2">
						Votre âge : 
					</div>
					<div class="col-4">
						<?php 
						if($age_joueur != NULL) {
							echo $age_joueur; 
						}
						else {
							echo "Non renseigné";
						}
						?>
					</div>
					<div class="col-2">
						Changer votre âge : 
					</div>
					<div class="col-4">
						<input type="text" name="age_change" value="" maxlength="3" >
					</div>
				</div>
				
				<div class="row">
					<div class="col-2">
						Votre pays : 
					</div>
					<div class="col-4">
						<?php 
						if($pays_joueur != NULL) {
							echo $pays_joueur;
						}
						else {
							echo "Non renseigné";
						}
						?>
					</div>
					<div class="col-2">
						Changer votre pays : 
					</div>
					<div class="col-4">
						<input type="text" name="pays_change" value="" maxlength="40" >
					</div>
				</div>
				
				<div class="row">
					<div class="col-2">
						Votre région : 
					</div>
					<div class="col-4">
						<?php 
						if($region_joueur != NULL) { 
							echo $region_joueur;
						}
						else {
							echo "Non renseigné";
						}
						?>
					</div>
					<div class="col-2">
						Changer votre région : 
					</div>
					<div class="col-4">
						<input type="text" name="region_change" value="" maxlength="40" >
					</div>
				</div>
				
				<br />
				
				<div class="row">
					<div class="col-2">
						<input type='checkbox' name='mail_info' <?php if($mail_info_joueur) echo 'checked';?> /> Recevoir un mail si on m'attaque
					</div>
				</div>
				
				<br />
				
				<table>
					<tr>
						<td>Images unités à utiliser :</td>
						<td>
							<select name='select_dossier_img'>
								<option value='v1' <?php if ($dossier_img_joueur == 'v1') { echo "selected"; } ?>>V1</option>
								<option value='v2' <?php if ($dossier_img_joueur == 'v2') { echo "selected"; } ?>>V2</option>
							</select>
						</td>
						<td><a class='btn btn-primary' href="compte.php?voir_img=ok">Voir les images</a></td>
					</tr>
				</table>
				
				<?php
				if (isset($_GET['voir_img']) && $_GET['voir_img'] == "ok") {
				?>
				
				<table border='1'>
					<tr>
						<th>Unité</th><th>v1</th><th>v2</th>
					</tr>
					<tr>
						<td>Cavalerie</td><td><img src="../images_perso/v1/cavalerie_nord.gif"> <img src="../images_perso/v1/cavalerie_sud.gif"></td><td><img src="../images_perso/v2/cavalerie_nord.gif"> <img src="../images_perso/v2/cavalerie_sud.gif"></td>
					</tr>
					<tr>
						<td>Infanterie</td><td><img src="../images_perso/v1/infanterie_nord.gif"> <img src="../images_perso/v1/infanterie_sud.gif"></td><td><img src="../images_perso/v2/infanterie_nord.gif"> <img src="../images_perso/v2/infanterie_sud.gif"></td>
					</tr>
					<tr>
						<td>Soigneur</td><td><img src="../images_perso/v1/soigneur_nord.gif"> <img src="../images_perso/v1/soigneur_sud.gif"></td><td><img src="../images_perso/v2/soigneur_nord.gif"> <img src="../images_perso/v2/soigneur_sud.gif"></td>
					</tr>
					<tr>
						<td>Artillerie</td><td><img src="../images_perso/v1/artillerie_nord.gif"> <img src="../images_perso/v1/artillerie_sud.gif"></td><td><img src="../images_perso/v2/artillerie_nord.gif"> <img src="../images_perso/v2/artillerie_sud.gif"></td>
					</tr>
					<tr>
						<td>Chien</td><td><img src="../images_perso/v1/toutou_nord.gif"> <img src="../images_perso/v1/toutou_sud.gif"></td><td><img src="../images_perso/v2/toutou_nord.gif"> <img src="../images_perso/v2/toutou_sud.gif"></td>
					</tr>
				</table>
				
				<?php
				}
				?>
				
				<input type="submit" name="eval_compte" value="Enregistrer">
			</form>
		</div>
	<?php		
			}
		}
	}
	else{
		echo "<font color=red>Vous ne pouvez pas accéder à cette page, veuillez vous loguer.</font>";
	}
	?>
		<!-- Optional JavaScript -->
		<!-- jQuery first, then Popper.js, then Bootstrap JS -->
		<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
	
	</body>
</html>
<?php
}
else {
	// logout
	$_SESSION = array(); // On ecrase le tableau de session
	session_destroy(); // On detruit la session
	
	header("Location:../index2.php");
}
?>