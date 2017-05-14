<?php

namespace Datalay\Findify\Block;
class CustomerGroup extends \Magento\Framework\View\Element\Html\Select {
   /**
     * methodList
     *
     * @var array
     */
    protected $groupfactory;
   /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Braintree\Model\System\Config\Source\Country $countrySource
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param array $data
     */
    public function __construct(
    \Magento\Framework\View\Element\Context $context, \Magento\Customer\Model\GroupFactory $groupfactory, array $data = []
    ) {
        parent::__construct($context, $data);
        $this->groupfactory = $groupfactory;
    } 
    /**
     * Returns countries array
     *
     * @return array
     */ 
     /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml() {
        if (!$this->getOptions()) {
            //$customerGroupCollection = $this->groupfactory->create()->getCollection();
            //foreach ($customerGroupCollection as $customerGroup) {
            //         $this->addOption($customerGroup->getCustomerGroupId(), $customerGroup->getCustomerGroupCode());
            //}
            $this->addOption('134','activity');
            $this->addOption('153','climate');
            $this->addOption('93','color');
            $this->addOption('139','gender');
            $this->addOption('83','manufacturer');
        }

/*        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
            ->getItems();
        foreach ($attributes as $attribute){
            $attributecode = $attribute->getAttributecode();
            $attributelabel = $attribute->getFrontendLabel();
            if ($attributelabel == ''){
                continue;
            }
            $attributelabel = str_replace("'", '', $attributelabel); // if an attribute contains ' it will break the js template so we remove it
            $this->addOption($attributecode, $attributelabel);
	}
*/

/*$customer_attributes = $this->objectManager->get('Magento\Catalog\Model\Product')->getAttributes();
    $attributesArrays = array();
    foreach($customer_attributes as $cal=>$val){*/
      /*$attributesArrays[] = array(
            'label' => $cal,
            'value' => $val
       );*/
   /*                  $this->addOption($customerGroup->getCustomerGroupId(), $customerGroup->getCustomerGroupCode());

    }
}*/

        return parent::_toHtml();
    }
    /**
     * Sets name for input element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value) {
        return $this->setName($value);
    }
}
