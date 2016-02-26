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

		$bdd->exec("SET CHARACTER SET utf8");
		$req2 = $bdd->prepare ("SELECT * FROM events_users WHERE event_id=:id");
		$req2->execute(array('id'=>$don[0])) or die (print_r($req2->errorInfo()));
		while($vbn=$req2->fetch()){
			$bdd_dash->exec("SET CHARACTER SET utf8");
			$wxc2 = $bdd_dash->prepare ("SELECT * FROM participes WHERE foreign_key=:id and old_id=:ident and type='user'");
			$wxc2->execute(array('id'=>$lid,'ident'=>$vbn['id'])) or die (print_r($wxc2->errorInfo()));
			echo 'research';
			var_dump(array('id'=>$lid,'ident'=>$vbn['id']));
			$compt2=0;
			while($fgh2 =$wxc2->fetch()){
				echo 'find';
				$compt2=1;
				$lid2=$fgh2['id'];
			}
			if($compt2==0){
				echo 'marque';
				var_dump($vbn);
				$poi2 = $bdd_dash->prepare("INSERT INTO participes SET old_user=:user,model=:model,foreign_key=:foreign_key,old_id=:old_id")or exit(print_r($bdd->errorInfo()));
                                   
				$poi2->execute(array(
					'user'=>$vbn['user_id'],
					'model'=>'Event',
					'foreign_key'=>$lid,
					'old_id'=>$vbn['id']
				));
				echo 'save';
				$lid2=$bdd_dash->lastInsertId();	
			}
		}

		$bdd->exec("SET CHARACTER SET utf8");
		$req2 = $bdd->prepare ("SELECT * FROM events_teams WHERE event_id=:id");
		$req2->execute(array('id'=>$don[0])) or die (print_r($req2->errorInfo()));
		echo $don[0];
		while($vbn=$req2->fetch()){
			//var_dump($vbn);
		}
	}
	
	
		
	
}