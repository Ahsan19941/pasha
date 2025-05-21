<?php
// File: app/controllers/OfferController.php
// Offer Controller for the PASHA Benefits Portal

namespace app\controllers;

use app\core\Controller;
use app\models\OfferModel;
use app\models\PartnerModel;

class OfferController extends Controller {
    /**
     * @var OfferModel Offer model
     */
    private $offerModel;
    
    /**
     * @var PartnerModel Partner model
     */
    private $partnerModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->offerModel = new OfferModel();
        $this->partnerModel = new PartnerModel();
    }
    
    /**
     * List all public offers
     * 
     * @return void
     */
    public function listPublic() {
        // Get pagination parameters
        $page = (int) $this->input('page', 1);
        $search = $this->input('search');
        $category = $this->input('category');
        
        // Get offers with pagination
        $offersData = $this->offerModel->getPublicOffers($page, ITEMS_PER_PAGE, $search, $category);
        
        // Get all categories for filtering
        $categories = $this->offerModel->getAllCategories();
        
        $this->render('offers/list', [
            'offers' => $offersData['offers'],
            'pagination' => $offersData['pagination'],
            'categories' => $categories,
            'currentCategory' => $category,
            'search' => $search
        ]);
    }
    
    /**
     * List offers by category
     * 
     * @param string $category Category slug
     * @return void
     */
    public function listByCategory($category) {
        // Redirect to the offers page with category filter
        $this->redirect('/offers?category=' . urlencode($category));
    }
    
    /**
     * View a single offer
     * 
     * @param int $id Offer ID
     * @return void
     */
    public function view($id) {
        // Get the offer with partner information
        $offer = $this->offerModel->getWithPartner($id, true);
        
        if (!$offer) {
            $this->flash('error', 'Offer not found or no longer available');
            $this->redirect('/offers');
        }
        
        // Get related offers (same category, excluding current)
        $relatedOffersSql = "SELECT o.*, p.name as partner_name, p.logo_url as partner_logo 
                            FROM offers o
                            LEFT JOIN partners p ON o.partner_id = p.id
                            WHERE o.category = ? AND o.id != ? AND o.status = 'active'
                            AND (o.start_date <= CURDATE() AND (o.end_date IS NULL OR o.end_date >= CURDATE()))
                            ORDER BY o.start_date DESC
                            LIMIT 3";
        
        $relatedOffers = $this->offerModel->query($relatedOffersSql, [$offer['category'], $id]);
        
        $this->render('offers/view', [
            'offer' => $offer,
            'relatedOffers' => $relatedOffers
        ]);
    }
}
