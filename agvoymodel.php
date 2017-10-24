<?php
// agvoymodel.php

/**
 * Gestion du 'modèle de données' de l'application via une base de données SQLite
 * 
 * Note : ce code n'a pas une qualité industrielle, mais est principalement destiné à des tests
 * de réalisation de l'application sur la gestion du CRUD dans les controleurs
 * 
 * Cf. create-db-sqlite.sql pour le modèle physique des données dans la base
 */

use Model\Circuit;
use Model\Etape;
use Model\ProgrammationCircuit;

/**
 * Renvoie tous les Circuits
 * 
 * @return array
 */
function get_all_circuits()
{
    global $app;

    $list_of_circuits = array();
    
    $returned_circuits = $app['db']->fetchAll("SELECT * FROM circuit;");
    
    foreach($returned_circuits as $c) {
        
    	$circuit = new Circuit($c['id']);
    	$circuit->setDescription($c['description']);
    	$circuit->setPaysDepart($c['pays_depart']);
    	$circuit->setVilleDepart($c['ville_depart']);
    	$circuit->setVilleArrivee($c['ville_arrivee']);
    	$circuit->setDureeCircuit($c['duree_circuit']);
        $circuit->setDateAjout($c['date_ajout']);

    	$programmation_list=get_programmations_by_circuit_id($circuit->getId());

    	foreach ($programmation_list as $p){
    	    $circuit->addProgrammation($p);
        }


    	array_push($list_of_circuits, $circuit);
    }
     
    return $list_of_circuits;
}

/**
 * Récupère un Circuit d'identifiant donné 
 * 
 * @param int $id
 * @return NULL|Circuit
 */
function get_circuit_by_id($id)
{
	global $app;
	
	$circuit = null;
	
	$returned_circuits = $app['db']->fetchAll("SELECT * FROM circuit WHERE id = :id",
			array('id' => $id));

	// normally only one iteration
	foreach($returned_circuits as $c) 
	{   
		$circuit = new Circuit($c['id']);
		
		$circuit->setDescription($c['description']);
		$circuit->setPaysDepart($c['pays_depart']);
		$circuit->setDateAjout($c['date_ajout']);
		$returned_etapes =  $app['db']->fetchAll(
		    "SELECT * FROM etape WHERE circuit_id = :id ORDER BY numero_etape",
			array('id' => $id));
		
		foreach($returned_etapes as $e) 
		{
			$circuit->addEtape($e['ville_etape'], $e['nombre_jours'], $e['id']);
		}

		// computed atributes, but restore as they are in the DB
		$circuit->setVilleDepart($c['ville_depart']);
		$circuit->setVilleArrivee($c['ville_arrivee']);
		$circuit->setDureeCircuit($c['duree_circuit']);
				
		$returned_programmations =  $app['db']->fetchAll(
		    "SELECT * FROM programmation_circuit WHERE circuit_id = :id ORDER BY date_depart",
			array('id' => $id));
		
		foreach($returned_programmations as $p) 
		{
			$programmation = new ProgrammationCircuit($p['date_depart'], $p['nombre_personnes'], $p['prix'], $circuit, $p['id']);
			
			$circuit->addProgrammation($programmation);
		}
		
	}
	return $circuit;
}

/**
 * Ajoute un circuit dans la base
 * 
 * @param string $description
 * @param string $pays_depart
 * @param string $ville_depart
 * @param string $ville_arrivee
 * @param int $duree_circuit
 * 
 * @return NULL|Circuit
 */
function add_circuit($description, $pays_depart, $ville_depart, $ville_arrivee, $duree_circuit) {

	global $app;
	
	$executed = $app['db']->insert('circuit',
			array(
						"description" => $description,
						"pays_depart" => $pays_depart,
						"ville_depart" => $ville_depart,
						"ville_arrivee" => $ville_arrivee,
						"duree_circuit" => $duree_circuit
				));
	
	$circuit = null;
	if ($executed == 1) {
		
	    // Identifiers are generated by the DBMS
		$id = $app['db']->lastInsertId();
		
		$circuit = get_circuit_by_id($id);
	}
	return $circuit;
}

/**
 * Save a Circuit to the DB
 * 
 * @param Circuit $circuit
 * 
 */
function save_circuit($circuit) {
	
	global $app;
	
	$executed = $app['db']->update('circuit', 
	    array(
	 			"description" => $circuit->getDescription(),
	 			"pays_depart" => $circuit->getPaysDepart(),
	 			"ville_depart" => $circuit->getVilleDepart(),
	 			"ville_arrivee" => $circuit->getVilleArrivee(),
	 			"duree_circuit" => $circuit->getDureeCircuit()
		), 
	    array("id" => $circuit->getId()) );
	
	return $executed;
}

/**
 * Destroy a circuit stored in the DB (and linked entities)
 * 
 * @param int $id
 */
function remove_circuit_by_id($id)
{
	global $app;

	// we should check proper completion, but that's quick & dirty example code, eh ;-)
	$executed = $app['db']->delete('programmation_circuit', 
	    array('circuit_id' => $id) );
	
	$executed = $app['db']->delete('etape', array(
			'circuit_id' => $id
	));
	
	$executed = $app['db']->delete('circuit', array(
			'id' => $id
	));
}

/**
 * Load an Etape (in the context of its Circuit, also loaded)
 * 
 * @param int $etape_id
 * @return NULL|Etape
 */
function get_etape_by_id($etape_id)
{
    global $app;
    
    $etape = null;
    
    // Fetch the circuit of that etape
    $returned_circuitids = $app['db']->fetchAll(
        "SELECT circuit_id FROM etape WHERE id = :id",
        array(
            'id' => $etape_id
        ));
    
    // normally only one circuit_id found
    foreach($returned_circuitids as $c) 
    {
        $circuit = get_circuit_by_id($c['circuit_id']);
        
        foreach($circuit->getEtapes() as $e) 
        {
            if($e->getId() == $etape_id) 
            {
                $etape = $e;
                break;
            }
        }
    }
    
    return $etape;
}

/**
 * Add an etape to the database
 * 
 * Note that this variant doesn't generate an Etape object but only returns the etape's id in the DB
 * 
 * @param Circuit $circuit
 * @param int $numero_etape
 * @param string $ville_etape
 * @param int $nombre_jours
 * @return NULL|int
 */
function add_etape($circuit, $numero_etape, $ville_etape, $nombre_jours) {
    
    global $app;
    
    $circuit_id = $circuit->getId();
    
    $executed = $app['db']->insert('etape',
        array(
            'circuit_id' => $circuit_id,
            'numero_etape' => $numero_etape,
            'ville_etape' => $ville_etape,
            'nombre_jours' => $nombre_jours
        ));
    
    $id = null;
    if ($executed == 1) 
    {
        $id = $app['db']->lastInsertId();    
    }
    return $id;
}

/**
 * Remove an etape from the DB
 * 
 * @param int $id
 */
function remove_etape_by_id($id)
{
    global $app;
    
    $executed = $app['db']->delete('etape', array(
        'id' => $id
    ));
    
    return $executed;
}

/**
 * Update the DB to save refreshed etapes (after renumbering for instance)
 * 
 * @param Circuit $circuit
 * @return Circuit
 */
function save_refreshed_etapes($circuit) 
{
    global $app;
   
    foreach($circuit->getEtapes() as $etape) 
    {
        $executed = $app['db']->update('etape', array(
            'numero_etape' => $etape->getNumeroEtape(),
            'ville_etape' => $etape->getVilleEtape(),
            'nombre_jours' => $etape->getNombreJours()
        ), array(
            'id' => $etape->getId(),
            'circuit_id' => $circuit->getId()
        ));  
    }
    // save the Circuit as well to the DB (updated duration, etc.)
    save_circuit($circuit);
    
    return $circuit;
}


/**
 * Renvoie tous les circuits programmés
 *
 * @return array
 */
function get_all_programmations()
{
	global $app;
	
	$list_of_programmations = array();
	
	$returned_circuitids = $app['db']->fetchAll(
	    "SELECT DISTINCT(circuit_id) FROM programmation_circuit;" );
	
	foreach($returned_circuitids as $c) 
	{
		$circuit = get_circuit_by_id( $c['circuit_id'] );
		
		foreach($circuit->getProgrammations() as $programmation) 
		{
			array_push($list_of_programmations, $programmation);
		}
	}

	return $list_of_programmations;
}

/**
 * Récupère un circuit programmé (et son Circuit)
 *
 * @param int $id
 * @return NULL|ProgrammationCircuit
 */
function get_programmation_by_id($id)
{
	global $app;
	
	$programmation = null;
	
	$returned_circuitids = $app['db']->fetchAll(
	    "SELECT circuit_id FROM programmation_circuit WHERE id = :id", 
		array(
				'id' => $id
		));
	
	// normally only one circuit_id
	foreach($returned_circuitids as $c) {
		$circuit = get_circuit_by_id($c['circuit_id']);
		
		foreach($circuit->getProgrammations() as $p) {
			if($p->getId() == $id) {
				$programmation = $p;
				break;
			}
		}
		break;
	}

	return $programmation;
 }

/**
 * Récupère les programmations d'un circuit d'identifiant de circuit donné
 *
 * @param int $id
 * @return NULL|array
 */
function get_programmations_by_circuit_id($id)
{
    global $list_of_programmations;
    global $app;

    $found = array();

    $list_of_programmations = $app['db']->fetchAll("SELECT * FROM programmation_circuit WHERE circuit_id==$id");


    foreach ($list_of_programmations as $p) {
        $programmation=new ProgrammationCircuit($p['date_depart'], $p['nombre_personnes'], $p['prix'],$p['circuit_id'], $p['id']);

        $found[] = $programmation;
    }
    return $found;
}


/**
  * Add a programmation of a Circuit to the DB 
  * 
  * @param Circuit $circuit
  * @param string $date_depart
  * @param int $nombre_personnes
  * @param int $prix
  * @return NULL|ProgrammationCircuit
  */
 function add_programmation($circuit, $date_depart, $nombre_personnes, $prix) {
     
     global $app;
     
     $circuit_id = $circuit->getId();
     
     $executed = $app['db']->insert('programmation_circuit',
         array(
             'circuit_id' => $circuit_id, 
             'date_depart' => $date_depart, 
             'nombre_personnes' => $nombre_personnes, 
             'prix' => $prix
         ));
     
     $programmation = null;
     if ($executed == 1) {
         
         // Ids are generated by the RDBMS
         $id = $app['db']->lastInsertId();
         
         $programmation = get_programmation_by_id($id);
     }
     return $programmation;
 }
 
 /**
  * Save a ProgrammationCircuit to the DB
  * 
  * @param ProgrammationCircuit $programmation
  */
 function save_programmation($programmation) {
     
     global $app;
     
     $executed = $app['db']->update('programmation_circuit', 
         array(
            'date_depart' => $programmation->getDateDepart(),
            'nombre_personnes' => $programmation->getNombrePersonnes(),
            'prix' => $programmation->getPrix()), 
         array("id" => $programmation->getId()));
     
     return $executed;
 }
 
 /**
  * Remove a programmation from the DB
  *  
  * @param int $id
  */
 function remove_programmation_by_id($id)
 {
     global $app;
     
     $executed = $app['db']->delete('programmation_circuit', array(
         'id' => $id
     ));
 }


 function getNewCircuits(){
     $circuitslist = get_all_circuits ();
     $newCircuits=array();
     $today=date('d-m-Y', time());

     foreach($circuitslist as $circuit){
        if($circuit->getDateAjout()->diff($today)<20){     //TODO condition
            $newCircuits[]=$circuit;
        }
     }

     return $newCircuits;
 }

 function getSoonProgrammations(){
     $circuitslist = get_all_circuits ();
     $prog=array();
     foreach($circuitslist as $circuit){
         if(count($circuit->getProgrammations())>0){
             $prog[]=$circuit;
         }
     }
 }