<?php
// File: app/controllers/HomeController.php
// Home Controller for the PASHA Benefits Portal

namespace app\controllers;

use app\core\Controller;
use app\models\OfferModel;
use app\models\MemberModel;

class HomeController extends Controller {
    /**
     * @var OfferModel Offer model
     */
    private $offerModel;
    
    /**
     * @var MemberModel Member model
     */
    private $memberModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->offerModel = new OfferModel();
        $this->memberModel = new MemberModel();
    }
    
    /**
     * Display home page
     * 
     * @return void
     */
    public function index() {
        // Get latest offers for homepage
        $latestOffers = $this->offerModel->getPublicOffers(1, 6);
        
        // Get offer categories for navigation
        $categories = $this->offerModel->getAllCategories();
        
        $this->render('home/index', [
            'offers' => $latestOffers['offers'],
            'categories' => $categories
        ]);
    }
}
