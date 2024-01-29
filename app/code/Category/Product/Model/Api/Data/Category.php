<?php
namespace Category\Product\Model\Api\Data;
interface Category
{
	public function getEntityId();
	public function setEntityId($entityId);

	
	public function getAssignedProduct();
	public function setAssignedProduct($assignProduct);


	public function getSelectedCategory();
	public function setSelectedCategory($selectedCategory);
	
	public function getCheckedCategory();
	public function setCheckedCategory($checkedCategory);

	public function getProductRecurisive();
	public function setProductRecurisive($productRecurisive);
	
	public function getCreatedAt();
	public function setCreatedAt($createdAt);

	public function getUpdatedAt();
	public function setUpdatedAt($updatedAt);
}