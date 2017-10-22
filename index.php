<?php

/**
 * Application d'exemple Agence de voyages Silex
 */

// require_once __DIR__.'/vendor/autoload.php';
$vendor_directory = getenv ( 'COMPOSER_VENDOR_DIR' );
if ($vendor_directory === false) {
	$vendor_directory = __DIR__ . '/vendor';
}
require_once $vendor_directory . '/autoload.php';

// Initialisations
$app = require_once 'initapp.php';

require_once 'agvoymodel.php';

// Routage et actions


//////Route Front Office//////

//Accueil :

$app->get('/',
    function() use ($app)
    {
        $programmations=get_all_programmations();

        return $app['twig']->render('/front/accueil.html.twig', ['programmations'=>$programmations]);
    }
)->bind('accueil');

// circuitlist : Liste tous les circuits
$app->get ( '/circuit',
    function () use ($app) 
    {
    	$circuitslist = get_all_circuits ();
    	$numberOfRows=ceil(count($circuitslist)/3);
    	// print_r($circuitslist);
    	
    	return $app ['twig']->render ( '/front/circuitslist.html.twig', [
    			'circuitslist' => $circuitslist,
                'numberOfRows' => $numberOfRows
    	] );
    }
)->bind ( 'circuitlist' );

// circuitshow : affiche les détails d'un circuit
$app->get ( '/circuit/{id}', 
	function ($id) use ($app) 
	{
		$circuit = get_circuit_by_id ( $id );
		// print_r($circuit);
		$programmations = get_programmations_by_circuit_id ( $id );
		//$circuit ['programmations'] = $programmations;

		return $app ['twig']->render ( 'circuitshow.html.twig', [ 
				'id' => $id,
				'circuit' => $circuit 
			] );
	}
)->bind ( 'circuitshow' );

// programmationlist : liste tous les circuits programmés
$app->get ( '/programmation', 
	function () use ($app) 
	{
		$programmationslist = get_all_programmations ();
		// print_r($programmationslist);

		return $app ['twig']->render ( '/front/programmationslist.html.twig', [
				'programmationslist' => $programmationslist 
			] );
	}
)->bind ( 'programmationlist' );



//////Routes back office//////

$app->get('/back',
    function() use ($app)
    {
        $programmations=get_all_programmations();

        return $app['twig']->render('/back/accueil.html.twig', ['programmations'=>$programmations]);
    }
)->bind('backaccueil');

// circuitlist : Liste tous les circuits
$app->get ( '/back/circuit',
    function () use ($app)
    {
        $circuitslist = get_all_circuits ();
        $numberOfRows=ceil(count($circuitslist)/3);
        // print_r($circuitslist);

        return $app ['twig']->render ( '/back/circuitslist.html.twig', [
            'circuitslist' => $circuitslist,
            'numberOfRows' => $numberOfRows
        ] );
    }
)->bind ( 'backcircuitlist' );


// programmationlist : liste tous les circuits programmés
$app->get ( '/back/programmation',
    function () use ($app)
    {
        $programmationslist = get_all_programmations ();
        // print_r($programmationslist);

        return $app ['twig']->render ( '/back/programmationslist.html.twig', [
            'programmationslist' => $programmationslist
        ] );
    }
)->bind ( 'backprogrammationlist' );


$app->run ();