<?php
/**
 * Routage et actions pour le backoffice de l'application
 
 * @copyright  2017 Telecom SudParis
 * @license    "MIT/X" License - cf. LICENSE file at project root
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
//use Symfony\Component\Form\Extension\Core\Type\SubmitType;
//use Symfony\Component\HttpFoundation\Response;

//use DateTime;

global $app;

// Allow other methods like DELETE (cf. https://silex.sensiolabs.org/doc/1.3/usage.html#other-methods)
Request::enableHttpMethodParameterOverride();

// Below, we use the approach of binding closures to variable names to ease their 
// spotting in the IDE. This may not be the best writing style, though. 

//-----------------------
// Circuits
//-----------------------

/**
 * Liste tous les circuits (backoffice)
 * 
 * @var \Closure $admin_circuitlist_action
 */


/**
 * @var \Closure $admin_circuitshow
 * 
 * affiche les détails d'un circuit (backoffice)
 */
$admin_circuitshow = function ($id) use ($app) 
{
    $circuit = get_circuit_by_id( $id );
    
    return $app ['twig']->render ( 'backoffice/circuitshow.html.twig', [
        'id' => $id,
        'circuit' => $circuit
    ] );
};
$app->get( '/admin/circuit/{id}', $admin_circuitshow)
    ->bind ( 'admin_circuitshow' );

//---------- Formulaires Circuit

/* 
 * Fonction utilitaire créant un formulaire pour un Circuit 
 */

function circuitnewget_form($app) 
{
    // variante PHP classique "verbeuse"
    //     $formbuilder = $app['form.factory']->createBuilder(FormType::class);
    //     $formbuilder->add('description');
    //     $formbuilder->add('paysDepart');
    //     $formbuilder->add('villeDepart');
    //     $formbuilder->add('villeArrivee');
    //     $formbuilder->add('dureeCircuit');
    //     $form = $formbuilder->getForm();
    
    // On préfère une variante compacte
    $form = $app['form.factory']->createBuilder(FormType::class)
    ->add('description')
    ->add('paysDepart')
    ->add('villeDepart')
    ->add('villeArrivee')
    ->add('dureeCircuit')
    ->add('dateAjout')
    ->getForm();
    return $form;
}

/**
 * @var \Closure $admin_circuitnew_getaction
 * 
 * Affichage d'un formulaire d'ajout de nouveau circuit
 * 
 * Voir $admin_circuitnew_postaction pour gestion du POST correspondant
 */
$admin_circuitnew_getaction = function() use ($app) 
{
    $formulaire = circuitnewget_form($app);
    
    $formview = $formulaire->createView();
    
    // display the form
    return $app['twig']->render('backoffice/circuitnew.html.twig',
        array('formulaire' => $formview)
        );
};

// GET
$app->get('/back/circuitnew', $admin_circuitnew_getaction)
    ->bind('admin_circuitnew');

/**
 * @var \Closure $admin_circuitnew_postaction
 *
 * Soumission d'un formulaire d'ajout de nouveau circuit (POST)
 */
 $admin_circuitnew_postaction = function(Request $request) use ($app) 
 {
    $form = circuitnewget_form($app);
    
    $form->handleRequest($request);
    
    // Data is supposed to be valid, but we actually don't use validators
    if ($form->isValid()) 
    {
        $data = $form->getData();
                
        add_circuit($data['description'],
            $data['paysDepart'],
            $data['villeDepart'],
            $data['villeArrivee'],
            $data['dureeCircuit'],
            $data['dateAjout']
            );
        
        // Make sure message will be displayed after redirect
        $app['session']->getFlashBag()->add('message', 'circuit bien ajouté');
        
        $url = $app["url_generator"]->generate("backcircuitlist");
        return $app->redirect($url);
    }
    // for now, don't manage the case of non-valid data
};
// POST
$app->post('/back/circuitnew', $admin_circuitnew_postaction);
    
/**
 * @var \Closure $admin_circuitmodify_action
 * 
 * Gestion d'un formulaire de modification d'un circuit (gère GET et POST dans même 
 * méthode)
 * 
 * $id : identifiant du circuit
 */
$admin_circuitmodify_action = function (Request $request, $id) use ($app) {
    
    $circuit = get_circuit_by_id($id);
    
    // prefill the form with values of the Circuit
    $form = $app['form.factory']->createBuilder(FormType::class, 
        $circuit)
    ->add('description')
    ->add('paysDepart')
    ->add('villeDepart')
    ->add('villeArrivee')
    ->add('dureeCircuit')
    ->getForm();
    
    $form->handleRequest($request);
    
    // if form was posted
    if ($form->isValid()) {
        
        save_circuit($circuit);
        
        $app['session']->getFlashBag()
            ->add('message', 'circuit modifé');
        
        return $app->redirect($app["url_generator"]->generate("backcircuitshow",
            array(
                'id' => $circuit->getId()
            )));
    }
    
    // display the form (GET or failed POST)
    return $app['twig']->render('backoffice/circuitmodify.html.twig', 
        array(
            'formulaire' => $form->createView()
        ));
};
// handle both GET and POST
$app->match('/admin/circuitmodify/{id}', $admin_circuitmodify_action)
    ->bind('admin_circuitmodify');
    
/**
 * @var \Closure $admin_circuitdelete_action
 * 
 * Gestion de la suppression d'un circuit (DELETE)
 */
$admin_circuitdelete_action = function ($id) use ($app) {
    
    remove_circuit_by_id($id);
    
    $app['session']->getFlashBag()->add('message', 'circuit suprimé');
    
    return $app->redirect($app["url_generator"]->generate("backcircuitlist"));
    
};
// DELETE (mais grâce à Request::enableHttpMethodParameterOverride)
$app->delete('/admin/circuit/{id}', $admin_circuitdelete_action)
    ->bind('admin_circuitdelete');

    
//-----------------------
// Étapes
//-----------------------

//---------- Formulaire Etape
    
/*
 * Fonction utilitaire créant un formulaire pour une Etape
 */
function etapenewget_form($app) 
{
    $form = $app['form.factory']->createBuilder(FormType::class)
    // Attribut calculé : pas modifiable
    //->add('numeroEtape')
    ->add('villeEtape')
    ->add('nombreJours')
    ->getForm();
    return $form;
}

/**
 * @var \Closure $admin_etapenew_getaction
 *
 * Affichage d'un formulaire d'ajout de nouvelle étape (GET)
 */
$admin_etapenew_getaction = function($circuit_id) use ($app) 
{    
    $circuit = get_circuit_by_id( $circuit_id );
    
    $formulaire = etapenewget_form($app);
    
    $formview = $formulaire->createView();
    
    // display the form
    return $app['twig']->render('backoffice/etapenew.html.twig',
        array(
            'circuit' => $circuit,
            'formulaire' => $formview)
        );
};
// GET
$app->get('/admin/etapenew/{circuit_id}', $admin_etapenew_getaction)
    ->bind('admin_etapenew');

/**
 * @var \Closure $admin_etapenew_postaction
 *
 * Soumission d'un formulaire d'ajout de nouvelle étape (POST)
 */
$admin_etapenew_postaction = function(Request $request, $circuit_id) use ($app) 
{    
    $circuit = get_circuit_by_id( $circuit_id );
    
    $form = etapenewget_form($app);
    
    $form->handleRequest($request);
    
    if ($form->isValid()) 
    {
        $data = $form->getData();
        
        // Ajout de l'étape (calcul des attributs, etc)
        $etape = $circuit->addEtape($data['villeEtape'], $data['nombreJours']);
                       
        // Persistence en base de données
        add_etape($circuit, $etape->getNumeroEtape(), 
            $etape->getVilleEtape(), 
            $etape->getNombreJours());
        // the duration has changed, so better save it
        save_circuit($circuit);
        
        $app['session']->getFlashBag()->add('message', 'étape bien ajoutée');
        
        $url = $app["url_generator"]->generate("backcircuitshow",
            array(
                'id' => $circuit->getId()
            ));
        return $app->redirect($url);
    }
    // for now, don't manage the case of non-valid data
};
// POST
$app->post('/admin/etapenew/{circuit_id}', $admin_etapenew_postaction);

/**
 * @var \Closure $admin_etapedelete_action
 *
 * Gestion de la suppression d'une étape (DELETE)
 */
$admin_etapedelete_action = function ($id) use ($app) {
    
    $etape = get_etape_by_id($id);
    
    $circuit = $etape->getCircuit();
    $found = $circuit->removeEtape($etape);
    
    // remove from the DB
    remove_etape_by_id($id);
    
    // refresh and save etapes which need to be renumbered
    $circuit = save_refreshed_etapes($circuit);
    
    $app['session']->getFlashBag()->add('message', 'étape suprimée');
    
    return $app->redirect($app["url_generator"]->generate("backcircuitshow",
        array(
            'id' => $circuit->getId()
        )));
};
$app->delete('/admin/etape/{id}', $admin_etapedelete_action)
    ->bind('admin_etapedelete');





////NOTRE
///

$app->get('/back',
    function() use ($app)
    {
        $programmations=get_all_programmations();

        return $app['twig']->render('/back/accueil.html.twig', ['programmations'=>$programmations]);
    }
)->bind('backaccueil');

// circuitlist : Liste tous les circuits

$admin_circuitlist_action = function () use ($app)
{
    $circuitslist = get_all_circuits ();
    $numberOfRows=ceil(count($circuitslist)/3);
    // print_r($circuitslist);

    return $app ['twig']->render ( '/back/circuitslist.html.twig', [
        'circuitslist' => $circuitslist,
        'numberOfRows' => $numberOfRows
    ] );
};

$app->get ( '/back/circuit', $admin_circuitlist_action

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

// circuitshow : affiche les détails d'un circuit
$app->get ( '/back/circuit/{id}',
    function ($id) use ($app)
    {
        $circuit = get_circuit_by_id ( $id );
        // print_r($circuit);
        $programmations = get_programmations_by_circuit_id ( $id );
        //$circuit ['programmations'] = $programmations;

        return $app ['twig']->render ( '/back/circuitshow.html.twig', [
            'id' => $id,
            'circuit' => $circuit
        ] );
    }
)->bind ( 'backcircuitshow' );

//-----------------------
// Programmations
//-----------------------

function prognewget_form($app)
{

    $form = $app['form.factory']->createBuilder(FormType::class)
        ->add('circuit')
        ->add('dateDepart')
        ->add('nombrePersonnes')
        ->add('prix')
        ->getForm();
    return $form;
}

$admin_prognew_getaction = function($circuit_id) use ($app)
{
    $prog = get_programmations_by_circuit_id( $circuit_id );

    $formulaire = prognewget_form($app);

    $formview = $formulaire->createView();

    // display the form
    return $app['twig']->render('back/prognew.html.twig',
        array(
            'prog' => $prog,
            'formulaire' => $formview)
    );
};
// GET
$app->get('/back/prognew/{circuit_id}', $admin_prognew_getaction)
    ->bind('backprognew');


$admin_prognew_postaction = function(Request $request, $circuit_id) use ($app)
{
    $programmations=get_programmations_by_circuit_id($circuit_id);
    $circuit=get_circuit_by_id($circuit_id);
    $form = prognewget_form($app);

    $form->handleRequest($request);

    if ($form->isValid())
    {
        $data = $form->getData();

        // Ajout de l'étape (calcul des attributs, etc)
        $prog = $circuit->addProgrammation(new \Model\ProgrammationCircuit($data['date'],$data['nombrePersonnes'], $data['prix'], $circuit));

        // Persistence en base de données
        add_programmation($circuit, $prog->getDateDepart(),
            $prog->getNombrePersonnes(),
            $prog->getPrix());
        // the duration has changed, so better save it
        save_circuit($circuit);

        $app['session']->getFlashBag()->add('message', 'Programmation bien ajoutée');

        $url = $app["url_generator"]->generate("backprogrammationlist",
            array(
                'id' => $circuit->getId()
            ));
        return $app->redirect($url);
    }
    // for now, don't manage the case of non-valid data
};
// POST
$app->post('/back/prognew/{circuit_id}', $admin_prognew_postaction);

/**
 * @var \Closure $admin_etapedelete_action
 *
 * Gestion de la suppression d'une étape (DELETE)
 */
$admin_etapedelete_action = function ($id) use ($app) {

    $etape = get_etape_by_id($id);

    $circuit = $etape->getCircuit();
    $found = $circuit->removeEtape($etape);

    // remove from the DB
    remove_etape_by_id($id);

    // refresh and save etapes which need to be renumbered
    $circuit = save_refreshed_etapes($circuit);

    $app['session']->getFlashBag()->add('message', 'étape suprimée');

    return $app->redirect($app["url_generator"]->generate("backcircuitshow",
        array(
            'id' => $circuit->getId()
        )));
};
$app->delete('/admin/etape/{id}', $admin_etapedelete_action)
    ->bind('admin_etapedelete');

