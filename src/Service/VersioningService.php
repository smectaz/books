<?php
// création d'un service pour pouvoir récuperer la version contenue dans le champ accept du header de la requete

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class VersioningService
{
    private $requestStack;

    public function __construct(RequestStack $requestStack, ParameterBagInterface $params)
    {
        $this -> requestStack = $requestStack;
        $this -> defaultVersion = $params -> get('default_api_version');
    }

    public function getVersion(): string
    {
        $version = $this -> defaultVersion;

        $request = $this -> requestStack -> getCurrentRequest();
        $accept = $request -> get('accept');
        // Récupération du numéro de version dans la chaîne  de caractères du accept :
        // exemple "application/json; test=bidule; version=2.0" => 2.0
        $entete = explode(';', $accept);
       
        // On parcours toutes les entêtes pour trouver la version
        foreach ($entete as $value) {
            if (strpos($value, 'version') !== false) {
                $version = explode('=', $value);
                $version = $version[1];
                break;
            }
        }
        return $version;
    }
}