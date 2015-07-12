<?php

namespace App;

use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

/**
 * Router factory.
 */
class RouterFactory
{
    /**
     * @return \Nette\Application\IRouter
     */
    public static function createRouter()
    {
        $router = new RouteList();
        $router[] = new Route('/', 'Homepage:default');
        $router[] = new Route('/new', 'Kb:new');
        $router[] = new Route('/<id [0-9]+>', 'Kb:default');
        $router[] = new Route('/edit/<id [0-9]+>', 'Kb:edit');
        $router[] = new Route('/s/<query>', 'Search:default');
        $router[] = new Route('/tag/<tagText>', 'Search:tag');
        $router[] = new Route('/table/', 'Table:default');
        $router[] = new Route('/table/<table>', 'Table:table');
        $router[] = new Route('/login', 'Login:default');
        $router[] = new Route('/api/<action>', 'Api:default');
        $router[] = new Route('/setup/<action>', 'Setup:default');

        return $router;
    }
}
