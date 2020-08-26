<?php

/**
 * @see       https://github.com/laminas/laminas-mvc-skeleton for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc-skeleton/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc-skeleton/blob/master/LICENSE.md New BSD License
 */
declare(strict_types = 1);

namespace Movie\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Movie\Cache\Movie as MovieCache;

class IndexController extends AbstractActionController {

    const SERVICE_UNAVAILABLE = "Service is down; Please try again later!";

    /**
     *
     * @var ViewModel 
     */
    protected $viewModel = null;

    /**
     *
     * @var array 
     */
    protected $items = null;

    public function __construct() {
        $this->init();
    }

    protected function init() {
        $this->viewModel = new ViewModel([]);
        $movieCache = new MovieCache();
        try {
            $this->items = $movieCache->getItems();
        } catch (Exception $ex) {
            $this->viewModel->setVariable('serviceUnavailable', self::SERVICE_UNAVAILABLE);
        }
    }

    public function indexAction() {
        return $this->viewModel;
    }

    public function listAction() {
        $this->viewModel->setVariable('movies', $this->items);
        return $this->viewModel;
    }

    public function viewAction() {
        try {
            $params = $this->params()->fromRoute();
            if (isset($params['movieId']) && is_numeric($params['movieId'])) {
                $this->viewModel->setVariable("movieId", $params['movieId']);
                $this->viewModel->setVariable("countedMovies", count($this->items));
                $this->viewModel->setVariable("movie", $this->items[$params['movieId']]);
            }
        } catch (\Exception $ex) {
            throw new \Exception('Invalid or not provided: movie Id ', 0x100001);
        }
        return $this->viewModel;
    }

}
