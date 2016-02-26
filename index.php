<?php
try
{
	$bdd = new PDO('mysql:host=localhost;dbname=b7h379b9jugketh6', 'root', '');
}
catch (Exception $e)
{
        die('Erreur : ' . $e->getMessage());
}
try
{
	$bdd_dash = new PDO('mysql:host=localhost;dbname=dbk', 'root', '');
}
catch (Exception $e)
{
        die('Erreur : ' . $e->getMessage());
}

$bdd_dash->exec("SET CHARACTER SET utf8");
$res = $bdd_dash->prepare ("SELECT * FROM associations ");
$res->execute(array()) or die (print_r($res->errorInfo()));
while($data =$res->fetch()){
	$bdd->exec("SET CHARACTER SET utf8");
	$req = $bdd->prepare ("SELECT * FROM multievents WHERE id=:old_id");
	$req->execute(array('old_id'=>$data['old_id'])) or die (print_r($req->errorInfo()));
	$passage=array();
	while($donnees=$req->fetch()){
		$rep = $bdd_dash->prepare('UPDATE associations SET name=:title,start_date=:start_date,end_date=:end_date,old_id=:id WHERE id=:idd')or exit(print_r($bdd->errorInfo()));
                                   
		$rep->execute(array(
			'title'=>$donnees['title'],
			'start_date'=>$donnees['start_date'],
			'end_date'=>$donnees['end_date'],
			'id'=>$donnees['id'],
			'idd'=>$data['id']
		));
		$passage[$data['old_id']]=$data['id'];
	}
	//mise a jour des epreuves
	$bdd->exec("SET CHARACTER SET utf8");
	$req = $bdd->prepare ("SELECT * FROM events INNER JOIN event_info ON events.id=event_info.event_id WHERE events.multievent_id=:old_id");
	$req->execute(array('old_id'=>$data['old_id'])) or die (print_r($req->errorInfo()));
	
	while($don=$req->fetch()){
		$bdd_dash->exec("SET CHARACTER SET utf8");
		$wxc = $bdd_dash->prepare ("SELECT * FROM epreuves WHERE old_id=:id");
		$wxc->execute(array('id'=>$don[0])) or die (print_r($res->errorInfo()));
		$compt=0;
		while($fgh =$wxc->fetch()){
			$compt=1;
			$lid=$fgh['id'];
		}
		if($compt==0){
			$poi = $bdd_dash->prepare('INSERT INTO epreuves SET name=:name,start=:start,end=:end,foreign_key=:foreign_key,old_id=:old_id')or exit(print_r($bdd->errorInfo()));
                                   
			$poi->execute(array(
				'name'=>$don['name'],
				'start'=>$don['start_date'],
				'end'=>$don['end_date'],
				'old_id'=>$don[0],
				'foreign_key'=>$data['id']
			));
			$lid=$bdd_dash->lastInsertId();	
		}
	}
	//mise a jour Champs Add-plus tard
	echo '*'.$data['old_id'].'*';
	$bdd->exec("SET CHARACTER SET utf8");
	$req = $bdd->prepare ("SELECT * FROM additional_fields WHERE foreign_key=:old_id and model='Multievent'");
	$req->execute(array('old_id'=>$data['old_id'])) or die (print_r($req->errorInfo()));
	$fields=array();
	$matrice=array();
	$champs=array();
	while($donnees=$req->fetch()){
		$a['id']=$donnees['id'];
		$a['model']=$donnees['model'];
		$a['allowed_type']=$donnees['allowed_type'];
		$a['title']=$donnees['title'];
		$a['placeholder']=$donnees['placeholder'];
		$a['select_fields']=$donnees['select_fields'];
		$a['required']=$donnees['required'];
		$champs[$donnees['id']]=$donnees['foreign_key'];
		//$fields[$donnees['id']] = $a;
		if($donnees['allowed_type']=='User'){
			$cle=explode("#",$donnees['title']);
			if(count($cle)==3){
				if(!isset($fields[$cle[0]])){
					$fields[$cle[0]]=array();
				}
				$fields[$cle[0]][$cle[1]]=$a;
				$matrice[$a['id']]=array('inscri'=>$cle[0],'champs'=>$cle[1],'nom'=>$cle[2]);
				//creer le champs si besoin
				if($cle[1]>8){
					//il faut créer un champs
				}
			}
		}else{
			echo 'error team'.'<br/>';
		}
	}
	//var_dump($matrice);
	//mise a jour des values
	$stack=array();
	foreach ($matrice as $key => $value) {
		$bdd->exec("SET CHARACTER SET utf8");
		$req = $bdd->prepare ("SELECT * FROM additional_values WHERE additional_field_id=:id");
		$req->execute(array('id'=>$key)) or die (print_r($req->errorInfo()));
		while($donnees=$req->fetch()){
			//var_dump($donnees);
			if(!isset($stack[$donnees['user_id']])){
				$stack[$donnees['user_id']]=array();
			}
			$stack[$donnees['user_id']][$key]=$donnees['value'];
		}	
	}
	//var_dump($stack);

	foreach ($stack as $user_id => $klm){
		$bdd->exec("SET CHARACTER SET utf8");
		$req = $bdd->prepare ("SELECT * FROM events_users WHERE user_id=:id");
		$req->execute(array('id'=>$user_id)) or die (print_r($req->errorInfo()));
		echo $user_id.'-';
		while($vbn=$req->fetch()){
			var_dump($vbn);
		}
		$bdd->exec("SET CHARACTER SET utf8");
		$req = $bdd->prepare ("SELECT * FROM events_teams_users WHERE user_id=:id");
		$req->execute(array('id'=>$user_id)) or die (print_r($req->errorInfo()));
		echo $user_id.'-';
		while($vbn=$req->fetch()){
			var_dump($vbn);
		}
		$store=array();

		foreach ($klm as $key =>$value){
		
			if(!isset($store[$matrice[$key]['inscri']])){
				$store[$matrice[$key]['inscri']]=array('ins'=>array());
			}
			if($matrice[$key]['champs']<9){
				$store[$matrice[$key]['inscri']]['ins'][$matrice[$key]['champs']]=$value;
			}else{
				//rien pour l'instant
			}
		}
		var_dump($store);
		
		foreach($store as $key=>$value){
			//si existe update sinon creer
			//var_dump($value);
			if($value['ins'][1]!=null && $value['ins'][2]!=null){
				$bdd_dash->exec("SET CHARACTER SET utf8");
				$wxc = $bdd_dash->prepare ("SELECT * FROM inscriptions WHERE user_id=:user_id AND name=:name AND firstname=:firstname");
				$wxc->execute(array('user_id'=>$user_id,'name'=>$value['ins'][1],'firstname'=>$value['ins'][2])) or die (print_r($res->errorInfo()));
				$compt=0;
				while($fgh =$wxc->fetch()){
					$compt=1;
				}
				if($compt==0){
					$poi = $bdd_dash->prepare('INSERT INTO inscriptions SET name=:name,firstname=:firstname,user_id=:user_id,foreign_key=:foreign_key')or exit(print_r($bdd->errorInfo()));
            	                       
					$poi->execute(array(
						'name'=>$value['ins'][1],
						'firstname'=>$value['ins'][2],
						'user_id'=>$user_id,
						'foreign_key'=>$passage[$champs[484]]
					));
				}
			}
		}
	}
	echo 'Relation de passage:';
	var_dump($passage);
	var_dump($champs);	
}