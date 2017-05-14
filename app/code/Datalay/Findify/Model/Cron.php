<?php
namespace Datalay\Findify\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
//use Magento\ImportExport\Model\Export\Adapter\Csv;
use Magento\Catalog\Helper\Image;
use Magento\Eav\Api\AttributeRepositoryInterface;

class Cron
{
    protected $productRepository;
    protected $categoryRepository;
    protected $searchCriteriaBuilder;
    protected $filterBuilder;
    //protected $csv;
    protected $logger;
    //protected $date;
    protected $productImageHelper;
    protected $attributeRepository;
    
    public function __construct(
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        //Csv $csv,
        \Psr\Log\LoggerInterface $logger,
        //\Magento\Framework\Stdlib\DateTime\DateTime $date
        Image $productImageHelper,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        //$this->csv = $csv;
        $this->logger = $logger;
        //$this->date = $date;
        $this->image = $productImageHelper;
        $this->attributeRepository = $attributeRepository;
    }

    public function export()
    {
        $this->logger->info('Export info starts running...');
        //$this->logger->debug('Export debug starts running...');
 
        $items = $this->getProducts();    
        $this->writeToFile($items);
    }

    public function getProducts()
    {
        $this->logger->info('Getting product list...');
        
        // $filters = [];
        
        /*$filters[] = $this->filterBuilder
            ->setField('created_at')
            ->setConditionType('lt')
            ->setValue($date->format('Y-m-d H:i:s'))
            ->create(); */
        
        //$this->searchCriteriaBuilder->addFilter($filters);
        
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults = $this->productRepository->getList($searchCriteria);
        return $searchResults->getItems();
    }

    protected function writeToFile($items)
    {
        $this->logger->info('Processing items list...');
        
        if (count($items) > 0) {
            $this->logger->info('We have >0 items...');
        
	    $jsondata = array();

	    $storeCode = 1; // cambiar en bucle

            // get filename from system configuration, or use default json_feed-<storeCode> if empty
            //$configfilename = Mage::getStoreConfig('attributes/feedinfo/feedfilename',$storeId);
	    $configfilename = '';
            $filename = str_replace("/", "", $configfilename);
            if(empty($filename)){
                    $filename = 'jsonl_feed-'.$storeCode;
            }
            $file = 'var/findify/'.$filename.'.gz';

            $this->logger->info('Before main foreach...');
        
            foreach ($items as $item) {

                $this->logger->info('Processing item... '.$item->getSku());
                        
                $product_data = array();

                $this->logger->info('Step 1...');

                $product_data['id'] = $item->getId();

                $this->logger->info('Step 2...');
                $product_data['sku'] = $item->getSku();

                $this->logger->info('Step 3...');
                $product_data['visibility'] = $item->getVisibility();

                $this->logger->info('Step 4...');
                $product_data['type_id'] = $item->getTypeId();

                $this->logger->info('Step 5...');
                
		// we use created_at or news_from_date attribute value, whichever is newer
                $createdAt = $item->getCreatedAt();
                $newsFromDate = $item->getNewsFromDate();
                if($newsFromDate && (strtotime($newsFromDate) > strtotime($createdAt))){
                    //$datetime = $this->date($newsFromDate);
                    //we should probably use \Magento\Framework\Stdlib\DateTime\DateTime $date etc
                    $datetime = new \DateTime($newsFromDate);
                }else{
                    //$datetime = $this->date($createdAt);
                    $datetime = new \DateTime($createdAt);
                }
                $product_data['created_at'] = $datetime->format(\DateTime::ATOM);

                $this->logger->info('Step 6...');             

                // if this product has children, set id as item_group_id                      
                if ($product_data['type_id'] == "configurable" || $product_data['type_id'] == "grouped") {
                    $product_data['item_group_id'] = $product_data['id'];
                }else{
                    $product_data['item_group_id'] = '';
                }

                $this->logger->info('Step 7...');

                // Product categories as breadcrumbs Father > Child > Grandchild
                $pathArray = array();
                //$productCategories = $item->getCategoryCollection() // this could be faster in big catalogs if we store id - path as an array previously
                //        ->addAttributeToSelect('path');
                //foreach($productCategories as $category){            

                $this->logger->info('Getting product categories...');
                
		if ($categoryIds = $item->getCategoryIds()) {
		    foreach ($categoryIds as $categoryId) {

		        $this->logger->info('$categoryId: '.$categoryId);
		        
		        $category = $this->categoryRepository->get($categoryId);
                        $pathIds = explode('/', $category->getPath()); // getPath() returns category IDs path as '1/2/53', we store that as an array ['1','2','53']

                        //$this->logger->info('category path IDs: '.$pathIds);
                        
                        $pathByName = array();
                        foreach($pathIds as $pathId){
                                // $pathByName[] = $categoryIdNames[$pathId];
                                $pathByName[] = $pathId;
                        }
                        array_splice($pathByName, 0, 2); // remove first two elements in path, which are always: Root Catalog > Default Category
                        $pathArray[] = implode(' > ', $pathByName);
                    }
                }
                $product_data['category'] = $pathArray;

		$product_data['title'] = $item->getName(); // name
                $product_data['price'] = sprintf('%0.2f',$item->getPrice()); // price excl tax, two decimal places
                $product_data['product_url'] = $item->getProductUrl(); // full url

    		$specialprice = $item->getSpecialPrice();
                if ($specialprice){
                    $specialPriceFromDate = $item->getSpecialFromDate();
                    $specialPriceToDate = $item->getSpecialToDate();
                    //if (Mage::app()->getLocale()->isStoreDateInInterval($item->getStoreId(), $specialPriceFromDate, $specialPriceToDate)){ 
                        $product_data['sale_price'] = sprintf('%0.2f',$specialprice);
                    //}
                }        

                $this->logger->info('Step 8...');
                
                // Product availability, as Magento calculates it
                $product_data['availability'] = $item->isSaleable() ? "in stock" : "out of stock";

		// images
		//ini_set('memory_limit','512M');
                $product_data['image_url'] = $this->image->init($item, 'product_thumbnail_image')->resize(180,180)->getUrl();
                $product_data['thumbnail_url'] = $this->image->init($item, 'product_thumbnail_image')->resize(65,65)->getUrl();
                                                                                
                // Long and short descriptions
                $product_data['description'] = $item->getDescription();
                $product_data['short_description'] = $item->getShortDescription();

                // User selected attributes via System / Configuration:
		//(...)

                $this->logger->info('Step 9...');
                
                //$this->csv->writeRow(['description'=>$item->getDescription(), 'short_description'=>$item->getShortDescription()]);

                $this->logger->info('Adding data to jsondata[]...');
                
		$jsondata[] = json_encode($product_data)."\n"; // Add this product data to main json array

                $this->logger->info('End of foreach items as item...');
                
            } // end foreach items as item
            
            // Write product feed array to gzipped file
            file_put_contents("compress.zlib://$file", $jsondata);

            /* $endtime = new DateTime('NOW');
            $runinterval = $starttime->diff($endtime);
            $elapsed = $runinterval->format('%S'); // elapsed seconds
            $fileurl = $urlmediapath.'/findify/'.$filename.'.gz';

            $extradata['feeds'][$storeCode] = array(
                    'feed_url' => $fileurl,
                    'last_generation_duration' => $elapsed,
                    'last_generation_time' => $starttime
            );
            */            
            
        } // end if count items > 0
    } // end writetofile
} // end Cron class definition

