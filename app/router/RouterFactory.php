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
        $router[] = new Route('/new', 'Entry:new');
        $router[] = new Route('/[<action>/][<id [0-9]+>]', array(
            'presenter' => 'Entry',
            'action' => 'default',
            'id' => NULL
        ));
        $router[] = new Route('/print/', 'Entry:print');
        $router[] = new Route('/s/<query>', 'Search:default');
        $router[] = new Route('/tag/<tagText>', 'Search:tag');
        $router[] = new Route('/table/', 'Table:default');
        $router[] = new Route('/table/<table>', 'Table:table');
        $router[] = new Route('/table/<table>/new/', 'Table:newItem');
        $router[] = new Route('/checklist/<id>', 'Checklist:default');
        $router[] = new Route('/login', 'Login:default');
        $router[] = new Route('/user', 'User:default');
        $router[] = new Route('/api/<action>', 'Api:default');
        $router[] = new Route('/setup/<action>', 'Setup:default');

        return $router;
    }
}
